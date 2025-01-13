<?php

use App\Http\Controllers\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TechnicianController;
use App\Http\Middleware\SetLanguage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware([SetLanguage::class])->group(function () {
    Route::post('/customer/register', [CustomerController::class, 'register']);
    Route::post('/customer/verify-otp', [CustomerController::class, 'verifyOtp']);
    Route::post('/customer/login', [CustomerController::class, 'login'])->name('login');
    Route::post('/customer/forgot-password', [CustomerController::class, 'forgotPassword']);
    Route::post('/customer/reset-password', [CustomerController::class, 'resetPassword']);
    Route::post('/technician/login', [TechnicianController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/customer/logout', [CustomerController::class, 'logout']);
        Route::put('/customer/update-profile', [CustomerController::class, 'updateProfile']);
        Route::put('/customer/update-phone', [CustomerController::class, 'updatePhoneNumber']);
        Route::post('/customer/change-password', [CustomerController::class, 'changePassword']);
        Route::get('/customer', [CustomerController::class, 'getCustomer']);
        Route::delete('/customer/remove', [CustomerController::class, 'removeCustomer']);

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

        ////// technicians
        Route::post('/technician/change-password', [TechnicianController::class, 'changePassword']);
        Route::get('/technician', [TechnicianController::class, 'getTechnician']);
        Route::post('/technician/logout', [TechnicianController::class, 'logout']);
    });


    /// Master Data

    Route::get('/landing', [LandingController::class, 'getLandingPage']);
    Route::get('/sliders', [LandingController::class, 'sliders']);
    Route::get('/cities', [AddressController::class, 'cities']);
    Route::get('/cities/{city}/districts', [AddressController::class, 'getDistricts']);
    Route::get('/categories', [ProductController::class, 'getAllcategories']);
    Route::get('/products', [ProductController::class, 'getAllProducts']);
});
