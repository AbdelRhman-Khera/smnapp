<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SkipCsrfForPayment;
use App\Models\Invoice;
use App\Http\Controllers\SimulationController;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/test', function () {
   echo 'test';
    // return view('welcome');
});
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('optimize:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('route:clear');
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('config:cache');
    // $exitCode = Artisan::call('view:cache');
    // $exitCode = Artisan::call('storage:link');
    // $exitCode = Artisan::call('migrate');




    return 'DONE';
});

Route::get('/test2', [\App\Http\Controllers\MaintenanceRequestController::class, 'testpay']);
// Route::post('/payment/callback1', [\App\Http\Controllers\MaintenanceRequestController::class, 'paymentCallback'])->middleware(SkipCsrfForPayment::class)->name('payment.callback1');
Route::post('/payment/callback1', [\App\Http\Controllers\MaintenanceRequestController::class, 'paymentCallback'])->name('payment.callback1');

Route::get('/admin/sales-invoices/{invoice}/print', function (Invoice $invoice) {
    abort_unless(
        auth()->user()?->can('view_sales::invoice') || auth()->user()?->can('view_any_sales::invoice'),
        403,
    );

    $invoice->load([
        'maintenanceRequest.customer',
        'maintenanceRequest.address.city',
        'maintenanceRequest.address.district',
        'services',
        'spareParts',
    ]);

    return view('sales-invoices.print', compact('invoice'));
})->middleware('auth')->name('admin.sales-invoices.print');

Route::middleware('auth')->prefix('simulate')->name('simulation.')->group(function () {
    Route::get('/', [SimulationController::class, 'index'])->name('index');
    Route::post('/requests', [SimulationController::class, 'store'])->name('store');
    Route::post('/requests/{maintenanceRequest}/visit-fee', [SimulationController::class, 'payVisitFeeAction'])->name('visit-fee');
    Route::post('/requests/{maintenanceRequest}/assign', [SimulationController::class, 'assignTechnicianAction'])->name('assign');
    Route::post('/requests/{maintenanceRequest}/on-the-way', [SimulationController::class, 'onTheWayAction'])->name('on-the-way');
    Route::post('/requests/{maintenanceRequest}/in-progress', [SimulationController::class, 'inProgressAction'])->name('in-progress');
    Route::post('/requests/{maintenanceRequest}/final-invoice', [SimulationController::class, 'createFinalInvoiceAction'])->name('final-invoice');
    Route::post('/requests/{maintenanceRequest}/pay-final', [SimulationController::class, 'payFinalInvoiceAction'])->name('pay-final');
    Route::post('/requests/{maintenanceRequest}/complete-without-payment', [SimulationController::class, 'completeWithoutPaymentAction'])->name('complete-without-payment');
});
