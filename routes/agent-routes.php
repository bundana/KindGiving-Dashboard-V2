<?php

use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\Campaigns\CampaignsPayout;
use App\Http\Controllers\Dashboard\{Admin, Agent, CampaignManager};
use App\Http\Controllers\SupportDesk;
use App\Http\Controllers\Utilities\Payment\HubtelApiServices;
use App\Http\Controllers\Utilities\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Route;



Route::middleware(['CheckUserRole:agent'])->prefix('agent')->name('agent.')->group(function () {
 Route::get('/dashboard', [Agent::class, 'index'])->name('index');
 Route::get('/', [Agent::class, 'index'])->name('index');
 Route::post('/logout', [Login::class, 'logout'])->name('logout');
 Route::get('/profile', function () {
  return view('profile');
 })->name('profile');


 Route::post('/profile',  [ProfileController::class, 'updateProfile'])->name('update-profile');
 Route::get('/activities', [Agent::class, 'index'])->name('activities');
 Route::post('/profile/referar',  [ProfileController::class, 'referralProgram'])->name('generate-referrar');

 Route::get('/campaigns',  [Agent::class, 'campaigns'])->name('campaigns');
 Route::post('/campaigns',  [Agent::class, 'campaigns'])->name('campaigns-search');
 Route::get('/campaigns/view/{id}', [Agent::class, 'viewCampaign'])->name('view-campaign');

 Route::get('/donations', [Agent::class, 'donationsReceipt'])->name('donation-receipts');

 Route::get('/donations/receipts', [Agent::class, 'donationsReceipt'])->name('donation-receipts');
 Route::get('/donations/receipts/{id}', [Agent::class, 'campaignReceiptIndex'])->name('campaign-donation-receipts');
 Route::post('/donations/receipts/{id}', [CampaignManager::class, 'storeorRemoveSelectedReceipts'])->name('store-unpaid-donation-receipt');

 Route::get('/donations/receipts/{id}/new', [Agent::class, 'newDonationsReceiptIndex'])->name('create-donation-receipt');
 Route::post('/donations/receipts/{id}/new', [CampaignManager::class, 'createReceipt'])->name('create-donation-receipt-post');
 Route::get('/donations/receipts/{id}/pay', [Agent::class, 'payReceiptIndex'])->name('pay-donation-receipt');
 Route::post('/donations/receipts/{id}/pay', [HubtelApiServices::class, 'handleFormSubmit'])->name('pay-donation-receipts');
 
 Route::post('/donations/receipts/{id}/unpay', [CampaignManager::class, 'storeorRemoveSelectedReceipts'])->name('remove-unpaid-donation-receipt');

 Route::post('/donations/verify-payment', [CampaignManager::class, 'verifyDonation'])->name('verify-payment');


 Route::get('/donations/payment-link/', [Agent::class, 'paymentLinksIndex'])->name('payment-link');
 Route::get('/donations/generate-payment-link/{id}', [Agent::class, 'paymentLinksForm'])->name('generate-payment-link');
 Route::post('/donations/generate-payment-link/{id}', [HubtelApiServices::class, 'generatePaymentLink'])->name('generate-payment-link-post');

 Route::get('/donations/payment-link-direct/', [Agent::class, 'paymentLinkDirectIndex'])->name('payment-link-direct');
 Route::get('/donations/generate-payment-link-direct/{id}', [Agent::class, 'paymentLinkDirectForm'])->name('generate-payment-link-direct');
 Route::post('/donations/generate-payment-link-direct/{id}', [HubtelApiServices::class, 'handleDirectPaymentLinkFormSubmit'])->name('generate-payment-link-direct-post');


 Route::get('/donations/stats', [Agent::class, 'donationsStats'])->name('all-web-donations');
 Route::get('/donations/stats/view/{id}', [Agent::class, 'viewDonationsStats'])->name('view-web-donation');


 Route::get('/support', [SupportDesk::class, 'index'])->name('support-index');
 Route::get('/support/{id}/view', [SupportDesk::class, 'index'])->name('support-view');
 Route::get('/support/create', [SupportDesk::class, 'index'])->name('new-support-index');
 Route::post('/support/create', [SupportDesk::class, 'create'])->name('new-support-post');
});
