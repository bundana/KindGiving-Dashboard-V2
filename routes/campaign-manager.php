<?php

use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\Campaigns\Campaign;
use App\Http\Controllers\Campaigns\CampaignsPayout;
use App\Http\Controllers\Campaigns\ReportController;
use App\Http\Controllers\Dashboard\Fundraiser\{FundraiserManagement, DonationManagement, AgentManagement};
use App\Http\Controllers\SupportDesk;
use App\Http\Controllers\Utilities\Payment\HubtelApiServices;
use App\Http\Controllers\Utilities\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


Route::middleware(['CheckUserRole:campaign_manager', 'campaign', 'auth'])->prefix('fundraiser')->name('manager.')->group(function () {
    Route::get('/', [FundraiserManagement::class, 'dashboard'])->name('index');
    Route::get('/dashboard', [FundraiserManagement::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [Login::class, 'logout'])->name('logout');
    Route::get('/profile', [FundraiserManagement::class, 'profile'])->name('profile');
    Route::get('/profile/settings', [FundraiserManagement::class, 'profile'])->name('profile-settings');
    Route::post('/profile/select-campaign', [FundraiserManagement::class, 'saveSelectedCampaign'])->name('save-selected-campaign');

    Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
    Route::post('/profile/referar', [ProfileController::class, 'referralProgram'])->name('generate-referrar');
    Route::get('/activities', [FundraiserManagement::class, 'index'])->name('activities');

    // campaigns routes
    Route::get('/campaigns', [FundraiserManagement::class, 'campaignIndex'])->name('campaigns');
    Route::get('/campaigns/create', [FundraiserManagement::class, 'createCampaignIndex'])->name('create-campaign');
    Route::post('/campaigns/create', [Campaign::class, 'create'])->name('create-campaign-post');
    Route::get('/campaigns/view/', [FundraiserManagement::class, 'viewCampaign'])->name('view-campaign');
    Route::get('/campaigns/edit/', [FundraiserManagement::class, 'updateCampaignIndex'])->name('update-campaign');
    Route::post('/campaigns/edit/', [Campaign::class, 'edit'])->name('update-campaign-post');
    Route::post('/campaigns/delete/', [Campaign::class, 'delete'])->name('delete-campaign');
    Route::get('/campaigns/reports', [FundraiserManagement::class, 'campaignsReportsIndex'])->name('campaigns-reports');
    Route::get('/campaigns/reports/search', [FundraiserManagement::class, 'campaignsReportsIndex'])->name('search-campaigns-reports');
    Route::get('/campaigns/reports/view/', [FundraiserManagement::class, 'viewCampaignReport'])->name('view-campaigns-reports');

    Route::post('/campaigns/view/visibility', [FundraiserManagement::class, 'campaignVisibility'])->name('campaigns-visibility');
    Route::get('/campaigns/donors', [FundraiserManagement::class, 'allDonors'])->name('all-donors');
    Route::get('/campaigns/donors', [FundraiserManagement::class, 'campaignDonors'])->name('campaign-donors');


    Route::get('/donations', [DonationManagement::class, 'allDonations'])->name('all-donations');
    Route::get('/donations/receipts', [DonationManagement::class, 'allReceipts'])->name('all-donation-receipts');

    Route::post('/donations/receipts', [DonationManagement::class, 'storeorRemoveSelectedReceipts'])->name('store-unpaid-donation-receipt');

    Route::get('/donations/receipts/create', [DonationManagement::class, 'newDonationsReceiptIndex'])->name('create-donation-receipt');
    Route::post('/donations/receipts/create', [DonationManagement::class, 'createReceipt'])->name('create-donation-receipt-post');

    Route::get('/donations/receipts/pay', [DonationManagement::class, 'payReceiptIndex'])->name('pay-donation-receipt');
    Route::post('/donations/receipts/pay/store', [DonationManagement::class, 'storeorRemoveSelectedReceipts'])->name('store-unpaid-donation-receipt');
    Route::get('/donations/receipts/make-payment', [DonationManagement::class, 'payReceiptCheckout'])->name('donation-receipt-payment');
    Route::post('/donations/receipts/unstore', [DonationManagement::class, 'storeorRemoveSelectedReceipts'])->name('remove-donation-receipt-payment');

    Route::post('/donations/receipts/pay', [HubtelApiServices::class, 'handleFormSubmit'])->name('pay-donation-receipt-post');

    Route::post('/donations/receipts/unpay', [DonationManagement::class, 'storeorRemoveSelectedReceipts'])->name('remove-unpaid-donation-receipt');
    Route::post('/export-donations', [DonationManagement::class, 'exportDonations'])->name('export.donations');
    Route::post('/donations/verify-payment', [DonationManagement::class, 'verifyDonation'])->name('verify-payment');

    Route::get('/donations/payment-link/', [DonationManagement::class, 'paymentLinksForm'])->name('payment-link');
    Route::post('/donations/payment-link/', [HubtelApiServices::class, 'generatePaymentLink'])->name('generate-payment-link-post');

    Route::get('/donations/direct-payment-link/', [DonationManagement::class, 'directPaymentLinkForm'])->name('direct-payment-link');
    Route::post('/donations/direct-payment-link/', [HubtelApiServices::class, 'handleDirectPaymentLinkFormSubmit'])->name('generate-direct-payment-link-post');

    Route::get('/users', [DonationManagement::class, 'users'])->name('all-users');
//    Route::get('/users/{id}', [FundraiserManagement::class, 'viewUser'])->name('view-user');
//    Route::post('/users/add', [FundraiserManagement::class, 'addAgentUser'])->name('add-user');
//    Route::post('/users/edit', [FundraiserManagement::class, 'editAgentUser'])->name('edit-user');
//    Route::post('/users/delete', [FundraiserManagement::class, 'deleteAgentUser'])->name('delete-user');
//    Route::post('/users/assign/{campaignId}', [FundraiserManagement::class, 'assignUserToCampaign'])->name('assign-user-to-campaign');


    Route::get('/users/agents', [AgentManagement::class, 'campaignAgents'])->name('all-agents');
    Route::post('/users/agents/create', [AgentManagement::class, 'campaignIndex'])->name('new-agent');
    Route::get('/users/agents/view/{id}', [AgentManagement::class, 'viewAgent'])->name('view-agent');
    Route::post('/users/agents/view/{id}', [AgentManagement::class, 'editAgent'])->name('edit-agent');
    Route::post('/users/agents/delete/{id}', [AgentManagement::class, 'deleteAgent'])->name('delete-agent');


    Route::get('/payout', [FundraiserManagement::class, 'campaignPayout'])->name('payout');
    Route::post('/payout/request', [FundraiserManagement::class, 'newCampaignPayout'])->name('payout-request');
    Route::get('/payout/settings/', [FundraiserManagement::class, 'payoutSettingIndex'])->name('payout-settings');
    Route::post('/payout/settings', [FundraiserManagement::class, 'verifyAccountInfo'])->name('payout-settings-post');


    Route::post('/payout/settings/account-name', [CampaignsPayout::class, 'getAccountName'])->name('payout-settings-account-name');
    Route::match(['get', 'post'], '/reports/generate/weekly/', [ReportController::class, 'weeklyData'])->name('generate-weekly-report');


    Route::get('/support', [FundraiserManagement::class, 'supportDesk'])->name('support');
    Route::get('/support/view', [FundraiserManagement::class, 'newSupportTicket'])->name('support-view');
    Route::get('/support/create', [FundraiserManagement::class, 'newSupportTicket'])->name('new-support-index');
    Route::post('/support/create', [FundraiserManagement::class, 'newSupportTicket'])->name('new-support-post');
});
