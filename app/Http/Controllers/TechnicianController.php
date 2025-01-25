<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TechnicianController extends Controller
{
    public function getTechnician()
    {
        $technician = Technician::find(auth()->user()->id);

        if (!$technician) {
            return response()->json([
                'status' => 404,
                'response_code' => 'TECHNICIAN_NOT_FOUND',
                'message' => __('messages.technician_not_found'),
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIAN_FOUND',
            'message' => __('messages.technician_found'),
            'data' => $technician,
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $technician = Technician::where('phone', $request->phone)->first();

        if (!$technician || !Hash::check($request->password, $technician->password)) {
            return response()->json([
                'status' => 401,
                'response_code' => 'INVALID_CREDENTIALS',
                'message' => __('messages.invalid_credentials'),
                'data' => null,
            ], 401);
        }

        $token = $technician->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'response_code' => 'LOGIN_SUCCESS',
            'message' => __('messages.login_success'),
            'data' => ['token' => $token, 'technician' => $technician],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'response_code' => 'LOGOUT_SUCCESS',
            'message' => __('messages.logout_success'),
            'data' => null,
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $technician = $request->user();
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $technician->password)) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_CURRENT_PASSWORD',
                'message' => __('messages.invalid_current_password'),
                'data' => null,
            ], 400);
        }

        $technician->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status' => 200,
            'response_code' => 'PASSWORD_CHANGED',
            'message' => __('messages.password_changed'),
            'data' => null,
        ], 200);
    }

    public function getRequestsSummary(Request $request)
    {
        $technician = Technician::with(['districts', 'products'])->find(auth()->user()->id);

        // Count all maintenance requests assigned to the technician
        $totalRequests = MaintenanceRequest::where('technician_id', $technician->id)->count();

        // Count requests by type
        $requestsByType = MaintenanceRequest::where('technician_id', $technician->id)
            ->select('type', \DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Count requests by current status (latest status only)
        $requestsByStatus = \DB::table('maintenance_requests')
            ->join('request_statuses', function ($join) {
                $join->on('maintenance_requests.id', '=', 'request_statuses.maintenance_request_id')
                    ->whereRaw('request_statuses.id = (SELECT MAX(id) FROM request_statuses WHERE maintenance_requests.id = request_statuses.maintenance_request_id)');
            })
            ->select('request_statuses.status', \DB::raw('count(*) as count'))
            ->where('maintenance_requests.technician_id', $technician->id)
            ->groupBy('request_statuses.status')
            ->get();

        // Get the next request (nearest in time) from slots
        $nextRequest = MaintenanceRequest::with(['customer', 'address', 'products', 'statuses' => function ($query) {
            $query->latest();
        }, 'slot'])
            ->where('technician_id', $technician->id)
            ->whereHas('statuses', function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('status', 'technician_assigned')
                    ->orWhere('status', 'technician_on_the_way');
            })
            ->whereHas('slot', function ($query) {
                $query->where('is_booked', true)
                    ->whereDate('date', '>=', now())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');
            })
            ->first();

        // Get all requests (history) with slot info
        $allRequests = MaintenanceRequest::with(['customer', 'address', 'products', 'statuses' => function ($query) {
            $query->latest();
        }, 'slot'])
            ->where('technician_id', $technician->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        // Count completed and ongoing requests
        $completedRequests = \DB::table('maintenance_requests')
            ->join('request_statuses', function ($join) {
                $join->on('maintenance_requests.id', '=', 'request_statuses.maintenance_request_id')
                    ->whereRaw('request_statuses.id = (SELECT MAX(id) FROM request_statuses WHERE maintenance_requests.id = request_statuses.maintenance_request_id)');
            })
            ->where('maintenance_requests.technician_id', $technician->id)
            ->where('request_statuses.status', 'paid')
            ->count();

        $ongoingRequests = $totalRequests - $completedRequests;

        return response()->json([
            'status' => 200,
            'response_code' => 'TECHNICIAN_REQUESTS_SUMMARY',
            'message' => __('messages.technician_requests_summary'),
            'data' => [
                'technician' => $technician,
                'total_requests' => $totalRequests,
                'requests_by_type' => $requestsByType,
                'requests_by_status' => $requestsByStatus,
                'completed_requests' => $completedRequests,
                'ongoing_requests' => $ongoingRequests,
                'next_request' => $nextRequest,
                'all_requests' => $allRequests,
            ],
        ]);
    }
}
