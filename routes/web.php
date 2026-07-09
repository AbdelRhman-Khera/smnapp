<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SkipCsrfForPayment;
use App\Models\Invoice;
use App\Models\TechnicianPayoutRequest;
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

Route::get('/admin/technician-payouts/{payout}/print', function (TechnicianPayoutRequest $payout) {
    abort_unless(
        auth()->user()?->can('view_technician::payout::request')
            || auth()->user()?->can('view_any_technician::payout::request'),
        403,
    );

    abort_unless($payout->status === 'approved', 404);

    $payout->load([
        'technician',
        'processedBy',
        'earnings.maintenanceRequest',
    ]);

    $dashboardUrl = \App\Filament\Resources\TechnicianPayoutRequestResource::getUrl('view', ['record' => $payout->id]);

    return view('technician-payouts.print', compact('payout', 'dashboardUrl'));
})->middleware('auth')->name('admin.technician-payouts.print');

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
    Route::post('/requests/{maintenanceRequest}/withdrawals', [SimulationController::class, 'createWithdrawalAction'])->name('withdrawals.create');
    Route::post('/withdrawals/{withdrawalRequest}/approve', [SimulationController::class, 'approveWithdrawalAction'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawalRequest}/reject', [SimulationController::class, 'rejectWithdrawalAction'])->name('withdrawals.reject');
    Route::post('/withdrawals/{withdrawalRequest}/assign-delivery-technician', [SimulationController::class, 'assignWithdrawalDeliveryTechnicianAction'])->name('withdrawals.assign-delivery-technician');
    Route::post('/withdrawals/{withdrawalRequest}/receive-from-technician', [SimulationController::class, 'receiveWithdrawalFromTechnicianAction'])->name('withdrawals.receive-from-technician');
    Route::post('/withdrawals/{withdrawalRequest}/deliver-to-branch', [SimulationController::class, 'deliverWithdrawalToBranchAction'])->name('withdrawals.deliver-to-branch');
    Route::post('/withdrawals/{withdrawalRequest}/branch-receive', [SimulationController::class, 'branchReceiveWithdrawalAction'])->name('withdrawals.branch-receive');
    Route::post('/withdrawals/{withdrawalRequest}/start-repair', [SimulationController::class, 'startWithdrawalRepairAction'])->name('withdrawals.start-repair');
    Route::post('/withdrawals/{withdrawalRequest}/complete-repair', [SimulationController::class, 'completeWithdrawalRepairAction'])->name('withdrawals.complete-repair');
    Route::post('/withdrawals/{withdrawalRequest}/follow-up', [SimulationController::class, 'createWithdrawalFollowUpAction'])->name('withdrawals.follow-up');
    Route::post('/withdrawals/{withdrawalRequest}/deliver-to-customer', [SimulationController::class, 'deliverWithdrawalToCustomerAction'])->name('withdrawals.deliver-to-customer');
    Route::post('/withdrawals/{withdrawalRequest}/confirm-customer-receipt', [SimulationController::class, 'confirmWithdrawalCustomerReceiptAction'])->name('withdrawals.confirm-customer-receipt');
});
