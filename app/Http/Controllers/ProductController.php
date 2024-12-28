<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function getAllCategories()
    {
        $categories = Category::all();

        return response()->json([
            'status' => 200,
            'response_code' => 'PRODUCTS_FETCHED',
            'message' => __('messages.products_fetched'),
            'data' => $categories,
        ], 200);
    }

    public function getAllProducts()
    {
        $products = Product::all();

        return response()->json([
            'status' => 200,
            'response_code' => 'PRODUCTS_FETCHED',
            'message' => __('messages.products_fetched'),
            'data' => $products,
        ], 200);
    }

    public function addProductToCustomer(Request $request)
    {
        $customer = Customer::find(auth()->id());
        $productId = $request->input('product_id');

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'status' => 404,
                'response_code' => 'PRODUCT_NOT_FOUND',
                'message' => __('messages.product_not_found'),
            ], 404);
        }

        if ($customer->products()->where('product_id', $productId)->exists()) {
            return response()->json([
                'status' => 400,
                'response_code' => 'PRODUCT_ALREADY_ADDED',
                'message' => __('messages.product_already_added'),
            ], 400);
        }

        $customer->products()->attach($productId);

        return response()->json([
            'status' => 200,
            'response_code' => 'PRODUCT_ADDED',
            'message' => __('messages.product_added'),
        ], 200);
    }


    public function getCustomerProducts()
    {
        $customer = Customer::find(auth()->id());
        dd($customer,auth()->id());
        $products = $customer->products;

        return response()->json([
            'status' => 200,
            'response_code' => 'CUSTOMER_PRODUCTS_FETCHED',
            'message' => __('messages.customer_products_fetched'),
            'data' => $products,
        ], 200);
    }

    public function removeProductFromCustomer($productId)
    {
        $customer = Customer::find(auth()->id());

        if (!$customer->products()->where('product_id', $productId)->exists()) {
            return response()->json([
                'status' => 404,
                'response_code' => 'PRODUCT_NOT_FOUND',
                'message' => __('messages.product_not_found'),
            ], 404);
        }

        $customer->products()->detach($productId);

        return response()->json([
            'status' => 200,
            'response_code' => 'PRODUCT_REMOVED',
            'message' => __('messages.product_removed'),
        ], 200);
    }
}
