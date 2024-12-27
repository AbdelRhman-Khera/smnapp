<?php

use App\Http\Controllers\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\SetLanguage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware([SetLanguage::class])->group(function () {
    Route::post('/customer/register', [CustomerController::class, 'register']);
    Route::post('/customer/verify-otp', [CustomerController::class, 'verifyOtp']);
    Route::post('/customer/login', [CustomerController::class, 'login']);
    Route::post('/customer/forgot-password', [CustomerController::class, 'forgotPassword']);
    Route::post('/customer/reset-password', [CustomerController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/customer/logout', [CustomerController::class, 'logout']);
        Route::put('/customer/update-profile', [CustomerController::class, 'updateProfile']);
        Route::put('/customer/update-phone', [CustomerController::class, 'updatePhoneNumber']);
        Route::post('/customer/change-password', [CustomerController::class, 'changePassword']);
        Route::get('/customer/{id?}', [CustomerController::class, 'getCustomer']);

        //// address
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::get('/addresses/{address}', [AddressController::class, 'show']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

        //// products
        Route::post('/customer/products', [ProductController::class, 'addProductToCustomer']);
        Route::get('/customer/products', [ProductController::class, 'getCustomerProducts']);
        Route::delete('/customer/products/{productId}', [ProductController::class, 'removeProductFromCustomer']);
    });


    /// Master Data
    Route::get('/cities', [AddressController::class, 'cities']);
    Route::get('/cities/{city}/districts', [AddressController::class, 'getDistricts']);
    Route::get('/products', [ProductController::class, 'getAllProducts']);
});
