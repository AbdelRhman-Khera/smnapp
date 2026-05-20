<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Landing;
use App\Models\Page;
use App\Models\Service;
use App\Models\Slider;
use App\Models\SparePart;
use App\Models\SupportForm;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LandingController extends Controller
{
    public function getLandingPage()
    {
        $landing = Landing::first();
        if (!$landing) {
            return response()->json([
                'status' => 404,
                'response_code' => 'LANDING_NOT_FOUND',
                'message' => __('messages.landing_not_found'),
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'LANDING_FETCHED',
            'message' => __('messages.landing_fetched'),
            'data' => $landing,
        ], 200);
    }

    function sliders()
    {
        $sliders = Slider::all();

        return response()->json([
            'status' => 200,
            'response_code' => 'SLIDERS_FETCHED_SUCCESSFULLY',
            'message' => __('messages.sliders_fetched_successfully'),
            'data' => $sliders,
        ], 200);
    }

    public function storeSupportForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'user_id' => 'nullable|integer',
            'details' => 'required|string',
            'user_type' => 'required|in:technician,customer',
            'platform' => 'required|in:app,web',
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

        if($validatedData['user_type'] === 'technician') {
            $technician = Technician::find(auth()->id());
            $validatedData['name'] = $technician ? $technician->first_name . ' ' . $technician->last_name : 'Technician #' . $validatedData['user_id'];
            $validatedData['phone'] = $technician ? $technician->phone : null;
            $validatedData['user_id'] = auth()->id();
        } elseif($validatedData['user_type'] === 'customer') {
            $customer = Customer::find(auth()->id());
            $validatedData['name'] = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Customer #' . $validatedData['user_id'];
            $validatedData['phone'] = $customer ? $customer->phone : null;
            $validatedData['user_id'] = auth()->id();
        }


        $supportForm = SupportForm::create($validatedData);

        return response()->json([
            'status' => 201,
            'response_code' => 'SUPPORT_FORM_CREATED',
            'message' => 'Support form created successfully.',
            'data' => $supportForm,
        ], 201);
    }

    public function getPage($slug)
    {
        $page = Page::where('slug', $slug)->first();

        return response()->json([
            'status' => 200,
            'response_code' => 'PAGE_FETCHED',
            'message' => __('messages.page_fetched'),
            'data' => $page,
        ], 200);
    }

    public function getSpareParts()
    {
        // $spareParts = SparePart::where('stock', '>', 0)->get();
         $spareParts = SparePart::all();

        return response()->json([
            'status' => 200,
            'response_code' => 'SPARE_PARTS_FETCHED',
            'message' => __('messages.spare_parts_fetched'),
            'data' => $spareParts,
        ], 200);
    }

    public function getServices()
    {
        $services = Service::where('is_active', '1')->get();
        return response()->json([
            'status' => 200,
            'response_code' => 'SERVICES_FETCHED',
            'message' => __('messages.services_fetched'),
            'data' => $services,
        ], 200);
    }
}
