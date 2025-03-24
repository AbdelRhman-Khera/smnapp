<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function getCustomer()
    {
        // $customer = Customer::find($id);
        $customer = Customer::find(auth()->user()->id);

        if (!$customer) {
            return response()->json([
                'status' => 404,
                'response_code' => 'CUSTOMER_NOT_FOUND',
                'message' => __('messages.customer_not_found'),
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'CUSTOMER_FOUND',
            'message' => __('messages.customer_found'),
            'data' => $customer,
        ], 200);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => [
                'required',
                'string',
                'max:15',
                'unique:customers,phone',
                'regex:/^(5|05)(\d{8})$/',
            ],
            'email' => 'nullable|email|unique:customers,email',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);
        // $validatedData['otp'] = rand(1000, 9999);
        $validatedData['otp'] = 1111;
        $customer = Customer::create($validatedData);

        // Send OTP via SMS using 4jawaly API
        try {
            $smsResponse = Http::withBasicAuth(
                '1D28Ps65RmtoZ8jUCkiJkp4cEPuUmyLpuaieywCg',
                's6YnwNekVdyhAdp2lVfiPv5Vo5QBBr1bzl66wruUTtpUBlVz9GQyslv9mjPzr7w0DOZoch2pfgpzLJe7CaJghOJS7xx3E3Ch70d2'
            )->post('https://api-sms.4jawaly.com/api/v1/account/area/sms/send', [
                'messages' => [
                    [
                        'text' => "Your OTP is: " . $customer->otp,
                        'numbers' => [$customer->phone],
                        'sender' => 'SamnanCo',
                    ],
                ],
            ]);

            if ($smsResponse->failed()) {
                return response()->json([
                    'status' => 500,
                    'response_code' => 'SMS_ERROR',
                    'message' => __('messages.sms_failed'),
                    'data' => null,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'response_code' => 'SMS_EXCEPTION',
                'message' => __('messages.sms_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 201,
            'response_code' => 'CUSTOMER_REGISTERED',
            'message' => __('messages.register_success'),
            'data' => $customer,
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
            'otp' => 'required|string|max:10',
        ]);

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer || $customer->otp !== $request->otp) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_OTP',
                'message' => __('messages.otp_invalid'),
                'data' => null,
            ], 400);
        }

        $customer->authorized = 1;
        $customer->activated = 1;
        $customer->otp = null; // Clear OTP after verification
        $customer->save();

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'response_code' => 'OTP_VERIFIED',
            'message' => __('messages.otp_verified'),
            'data' => ['token' => $token, 'customer' => $customer],
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
        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json([
                'status' => 401,
                'response_code' => 'INVALID_CREDENTIALS',
                'message' => __('messages.invalid_credentials'),
                'data' => $customer,
            ], 401);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'response_code' => 'LOGIN_SUCCESS',
            'message' => __('messages.login_success'),
            'data' => ['token' => $token, 'customer' => $customer],
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

    public function updateProfile(Request $request)
    {
        $customer = $request->user();
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $customer->update($validatedData);

        return response()->json([
            'status' => 200,
            'response_code' => 'PROFILE_UPDATED',
            'message' => __('messages.profile_updated'),
            'data' => $customer,
        ], 200);
    }

    public function updatePhoneNumber(Request $request)
    {
        $customer = $request->user();
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15|unique:customers,phone,' . $customer->id,
            // 'phone' => [
            //     'required',
            //     'string',
            //     'max:15',
            //     'regex:/^(5|05)(\d{8})$/',
            //     'unique:customers,phone'. $customer->id,
            // ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        // $validatedData = $request->validate([
        //     'phone' => 'required|string|max:15|unique:customers,phone,' . $customer->id,
        // ]);

        $customer->update([
            'phone' => $validatedData['phone'],
            'otp' => rand(1000, 9999),
            'authorized' => 0,
        ]);

        // Send OTP logic
        // Mail::raw("Your OTP is: " . $customer->otp, function ($message) use ($customer) {
        //     $message->to($customer->email)->subject('Verify New Phone Number');
        // });

        return response()->json([
            'status' => 200,
            'response_code' => 'PHONE_UPDATED',
            'message' => __('messages.phone_updated'),
            'data' => $customer,
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^(5|05)(\d{8})$/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer) {
            return response()->json([
                'status' => 404,
                'response_code' => 'CUSTOMER_NOT_FOUND',
                'message' => __('messages.customer_not_found'),
                'data' => null,
            ], 404);
        }

        $customer->update(['otp' => rand(1000, 9999)]);

        // Send OTP logic
        // Mail::raw("Your OTP for password reset is: " . $customer->otp, function ($message) use ($customer) {
        //     $message->to($customer->email)->subject('Password Reset OTP');
        // });

        return response()->json([
            'status' => 200,
            'response_code' => 'OTP_SENT',
            'message' => __('messages.otp_sent'),
            'data' => null,
        ], 200);
    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
            'otp' => 'required|string|max:4',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if (!$customer || $customer->otp !== $request->otp) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_OTP',
                'message' => __('messages.otp_invalid'),
                'data' => null,
            ], 400);
        }

        $customer->update([
            'password' => Hash::make($request->password),
            'otp' => null,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'PASSWORD_RESET',
            'message' => __('messages.password_reset'),
            'data' => null,
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $customer = $request->user();
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'status' => 400,
                'response_code' => 'INVALID_CURRENT_PASSWORD',
                'message' => __('messages.invalid_current_password'),
                'data' => null,
            ], 400);
        }

        $customer->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status' => 200,
            'response_code' => 'PASSWORD_CHANGED',
            'message' => __('messages.password_changed'),
            'data' => null,
        ], 200);
    }

    public function removeCustomer(Request $request)
    {
        $customer = $request->user();
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->password, $customer->password)) {
            return response()->json([
                'status' => 401,
                'response_code' => 'INVALID_PASSWORD',
                'message' => __('messages.invalid_password'),
                'data' => null,
            ], 401);
        }

        $customer->delete();

        return response()->json([
            'status' => 200,
            'response_code' => 'CUSTOMER_REMOVED',
            'message' => __('messages.customer_removed'),
            'data' => null,
        ], 200);
    }


    public function updateFcmToken(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'FCM_TOKEN_UPDATED',
            'message' => __('messages.fcm_token_updated'),
            'data' => $customer,
        ], 200);
    }
}
