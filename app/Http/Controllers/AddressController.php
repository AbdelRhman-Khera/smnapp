<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Helpers\ValidationHelper;
use App\Models\City;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $validationError = ValidationHelper::validate([
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
            'customer_id' => auth()->id(),
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

    public function update(Request $request, Address $address)
    {
        if ((int) $address->customer_id !== (int) auth()->id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED',
                'message' => __('messages.address_not_found'),
                'data' => null,
            ], 403);
        }

        $validationError = ValidationHelper::validate([
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

        $address->update($request->all());

        return response()->json([
            'status' => 200,
            'response_code' => 'ADDRESS_UPDATED',
            'message' => __('messages.address_updated'),
            'data' => $address,
        ], 200);
    }

    public function destroy(Address $address)
    {
        if ((int) $address->customer_id !== (int) auth()->id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED',
                'message' => __('messages.address_not_found'),
                'data' => null,
            ], 403);
        }

        $address->delete();

        return response()->json([
            'status' => 200,
            'response_code' => 'ADDRESS_DELETED',
            'message' => __('messages.address_deleted'),
            'data' => null,
        ], 200);
    }

    public function index()
    {
        $addresses = Address::where('customer_id', auth()->id())->with(['city', 'district'])->get();

        return response()->json([
            'status' => 200,
            'response_code' => 'ADDRESSES_FETCHED',
            'message' => __('messages.address_added'),
            'data' => $addresses,
        ], 200);
    }

    public function show($id)
    {

        $address = Address::with(['city', 'district'])->find($id);

        if (!$address) {
            return response()->json([
                'status' => 404,
                'response_code' => 'ADDRESS_NOT_FOUND',
                'message' => __('messages.address_not_found'),
                'data' => null,
            ], 404);
        }

        if ((int) $address->customer_id !== (int) auth()->id()) {
            return response()->json([
                'status' => 403,
                'response_code' => 'UNAUTHORIZED',
                'message' => __('messages.address_not_found'),
                'data' => null,
            ], 403);
        }

        return response()->json([
            'status' => 200,
            'response_code' => 'ADDRESS_FETCHED',
            'message' => __('messages.address_fetched'),
            'data' => $address,
        ], 200);
    }

    public function cities()
    {
        $cities = City::all();

        return response()->json([
            'status' => 200,
            'response_code' => 'CITIES_FETCHED',
            'message' => __('messages.cities_fetched'),
            'data' => $cities,
        ], 200);
    }

    public function getDistricts(City $city)
    {
        $districts = $city->districts;

        return response()->json([
            'status' => 200,
            'response_code' => 'DISTRICTS_FETCHED',
            'message' => __('messages.districts_fetched'),
            'data' => $districts,
        ], 200);
    }
}
