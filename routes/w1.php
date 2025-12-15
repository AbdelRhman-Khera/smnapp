<?php

use App\Http\Controllers\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Chatbot\CustomerController;
use App\Http\Controllers\Chatbot\MaintenanceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TechnicianController;
use App\Http\Middleware\SetLanguage;
use App\Http\Middleware\BasicAuthMiddleware;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware([SetLanguage::class])->group(function () {
    Route::post('/customer/register', [CustomerController::class, 'register'])->middleware(BasicAuthMiddleware::class);
    Route::get('/customer/addresses/{customer_id}', [CustomerController::class, 'getAddresses'])->middleware(BasicAuthMiddleware::class);
    Route::post('/customer/addresses', [CustomerController::class, 'addAddress'])->middleware(BasicAuthMiddleware::class);
    Route::post('/maintenance-request', [MaintenanceController::class, 'create'])->middleware(BasicAuthMiddleware::class);
    Route::post('/new-installation', [MaintenanceController::class, 'newInstallation'])->middleware(BasicAuthMiddleware::class);
    Route::post('/get-available-slots', [MaintenanceController::class, 'getAvailableSlots'])->middleware(BasicAuthMiddleware::class);
    Route::post('/maintenance-request/assign', [MaintenanceController::class, 'assignSlot'])->middleware(BasicAuthMiddleware::class);
    Route::post('/maintenance-request/no-slot', [MaintenanceController::class, 'noSlot'])->middleware(BasicAuthMiddleware::class);
    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::post('/customer/logout', [CustomerController::class, 'logout']);
    //     Route::put('/customer/update-profile', [CustomerController::class, 'updateProfile']);
    //     Route::put('/customer/update-phone', [CustomerController::class, 'updatePhoneNumber']);
    //     Route::post('/customer/change-password', [CustomerController::class, 'changePassword']);
    //     Route::get('/customer', [CustomerController::class, 'getCustomer']);
    //     Route::delete('/customer/remove', [CustomerController::class, 'removeCustomer']);
    //     Route::post('/customer/update-fcm-token', [CustomerController::class, 'updateFcmToken']);

    //     //// address
    //     Route::get('/addresses', [AddressController::class, 'index']);
    //     Route::get('/addresses/{address}', [AddressController::class, 'show']);
    //     Route::post('/addresses', [AddressController::class, 'store']);
    //     Route::put('/addresses/{address}', [AddressController::class, 'update']);
    //     Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    //     //// products
    //     Route::post('/customer/products', [ProductController::class, 'addProductToCustomer']);
    //     Route::get('/customer/products', [ProductController::class, 'getCustomerProducts']);
    //     Route::delete('/customer/products/{productId}', [ProductController::class, 'removeProductFromCustomer']);

    //     ////// technicians
    //     Route::post('/technician/change-password', [TechnicianController::class, 'changePassword']);
    //     Route::get('/technician', [TechnicianController::class, 'getTechnician']);
    //     Route::post('/technician/logout', [TechnicianController::class, 'logout']);
    //     Route::get('/technician/requests-summary', [TechnicianController::class, 'getRequestsSummary']);
    //     Route::get('/technician/requests', [TechnicianController::class, 'getAllRequests']);
    //     Route::post('/technician/update-fcm-token', [TechnicianController::class, 'updateFcmToken']);
    //     // Route::delete('/technician/remove', [TechnicianController::class, 'removeTechnician']);


    //     Route::get('/sap-order/{id}', [MaintenanceRequestController::class, 'getSpecificProductByOrder']);


    //     ////// maintenance request
    //     Route::post('/maintenance-request', [MaintenanceRequestController::class, 'create']);
    //     Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index']);
    //     Route::get('/maintenance-request/{id}', [MaintenanceRequestController::class, 'show']);
    //     Route::post('/maintenance-request/{id}/cancel', [MaintenanceRequestController::class, 'cancel']);
    //     // Route::post('/maintenance-request/{maintenanceRequest}/rate', [MaintenanceRequestController::class, 'rate']);
    //     // Route::post('/maintenance-request/{maintenanceRequest}/status', [MaintenanceRequestController::class, 'updateStatus']);
    //     Route::post('/get-available-slots', [MaintenanceRequestController::class, 'getAvailableSlots']);
    //     Route::post('/maintenance-request/assign', [MaintenanceRequestController::class, 'assignSlot']);

    //     Route::post('/maintenance-request/{id}/set-on-the-way', [TechnicianController::class, 'setOnTheWay']);
    //     Route::post('/maintenance-request/{id}/set-in-progress', [TechnicianController::class, 'setInProgress']);
    //     Route::post('/maintenance-request/{id}/set-waiting-for-payment', [TechnicianController::class, 'setWaitingForPayment']);
    //     Route::post('/maintenance-request/{id}/confirm-cash-payment', [TechnicianController::class, 'confirmCashPayment']);
    //     Route::post('/maintenance-request/{id}/finish-installation', [TechnicianController::class, 'finishInstallation']);

    //     Route::post('/maintenance-request/{id}/set-payment-method', [MaintenanceRequestController::class, 'setPaymentMethod']);
    //     Route::post('/maintenance-request/{id}/submit-feedback', [MaintenanceRequestController::class, 'submitFeedback']);


    //     Route::post('/support-form', [LandingController::class, 'storeSupportForm']);

    //     /////notifications

    //     Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    //     Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
    //     Route::post('/notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
    //     Route::post('/notifications/read-all', [NotificationController::class, 'markAllNotificationsAsRead']);
    // });
    /////paytabs
    // Route::post('/payment/callback/{id}', [MaintenanceRequestController::class, 'paymentCallback'])->name('payment.callback');
    // Route::post('/payment/success/{id}', [MaintenanceRequestController::class, 'paymentCallback'])->name('payment.success');
    // Route::post('/payment/mobileCallback', [MaintenanceRequestController::class, 'paymentCallbackMobile'])->name('payment.mobileCallback');

    /// Master Data


    Route::get('/cities', [AddressController::class, 'cities'])->middleware(BasicAuthMiddleware::class);
    Route::get('/cities/{city}/districts', [AddressController::class, 'getDistricts'])->middleware(BasicAuthMiddleware::class);
    Route::get('/categories', [ProductController::class, 'getAllcategories'])->middleware(BasicAuthMiddleware::class);
    Route::get('/products', [ProductController::class, 'getAllProducts'])->middleware(BasicAuthMiddleware::class);
    Route::get('/spare-parts', [LandingController::class, 'getSpareParts'])->middleware(BasicAuthMiddleware::class);
    Route::get('/services', [LandingController::class, 'getServices'])->middleware(BasicAuthMiddleware::class);

    Route::post('/support-form', [CustomerController::class, 'storeSupportForm'])->middleware(BasicAuthMiddleware::class);
});
