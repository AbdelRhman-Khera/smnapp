<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Helpers\ValidationHelper;

class CustomerController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => [
                'required',
                'string',
                'max:15',
                'regex:/^(5|05)(\d{8})$/',
            ],
            'email' => 'nullable|email|unique:customers,email',
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

        // Check if the customer already exists
        $existingCustomer = Customer::where('phone', $validatedData['phone'])->first();
        if ($existingCustomer) {
            return response()->json([
                'status' => 200,
                'response_code' => 'CUSTOMER_ALREADY_EXISTS',
                'message' => __('messages.customer_exists'),
                'data' => $existingCustomer,
            ], 200);
        }

        // Generate random password
        $generatedPassword = Str::random(8);
        $validatedData['password'] = Hash::make($generatedPassword);

        $customer = Customer::create($validatedData);

        // Send password via SMS
        try {
            Http::withBasicAuth(
                '1D28Ps65RmtoZ8jUCkiJkp4cEPuUmyLpuaieywCg',
                's6YnwNekVdyhAdp2lVfiPv5Vo5QBBr1bzl66wruUTtpUBlVz9GQyslv9mjPzr7w0DOZoch2pfgpzLJe7CaJghOJS7xx3E3Ch70d2'
            )->post('https://api-sms.4jawaly.com/api/v1/account/area/sms/send', [
                'messages' => [
                    [
                        'text' => "أهلاً بك في تطبيق سمنان للصيانة! كلمة المرور الخاصة بك للدخول إلى التطبيق هي: {$generatedPassword}",
                        'numbers' => [$customer->phone],
                        'sender' => 'SamnanCo',
                    ],
                ],
            ]);
        } catch (\Exception $e) {

            Log::error('SMS sending failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 201,
            'response_code' => 'CUSTOMER_REGISTERED',
            'message' => __('messages.register_success'),
            'data' => $customer,
        ], 201);
    }

    public function getAddresses($customer_id)
    {
        $addresses = Address::where('customer_id', $customer_id)->with(['city', 'district'])->get();

        if ($addresses->isEmpty()) {
            return response()->json([
                'status' => 404,
                'response_code' => 'ADDRESSES_NOT_FOUND',
                'message' => __('messages.customer_address_not_found'),
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'ADDRESSES_FETCHED',
            'message' => __('messages.addresses_fetched'),
            'data' => $addresses,
        ], 200);
    }

    public function addAddress(Request $request)
    {
        $validationError = ValidationHelper::validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id',
            'street' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'national_address' => 'nullable|string|max:255',
            'details' => 'nullable|string',
        ], $request);

        if ($validationError) {
            return $validationError;
        }

        $address = Address::create([
            'customer_id' => $request->customer_id,
            'name' => $request->name,
            'city_id' => $request->city_id,
            'district_id' => $request->district_id,
            'street' => $request->street,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'national_address' => $request->national_address,
            'details' => $request->details,
        ]);

        return response()->json([
            'status' => 200,
            'response_code' => 'ADDRESS_ADDED',
            'message' => __('messages.address_added'),
            'data' => $address,
        ], 200);
    }
}
