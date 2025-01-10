<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\Slider;
use Illuminate\Http\Request;

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

    function sliders() {
        $sliders = Slider::all();

        return response()->json([
            'status' => 200,
            'response_code' => 'SLIDERS_FETCHED_SUCCESSFULLY',
            'message' => __('messages.sliders_fetched_successfully'),
            'data' => $sliders,
        ], 200);
    }
}
