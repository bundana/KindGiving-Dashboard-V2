<?php

use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\Campaigns\Campaign;
use App\Http\Controllers\Campaigns\CampaignsPayout;
use App\Http\Controllers\Campaigns\ReportController;
use App\Http\Controllers\Dashboard\{CampaignManager, Agent, Admin};
use App\Http\Controllers\SystemManagement;
use App\Http\Controllers\Utilities\Payment\Hubtel;
use App\Http\Controllers\Utilities\Payment\HubtelApiServices;
use App\Http\Controllers\Utilities\ProfileController;
use App\Http\Controllers\Utilities\USSDController;
use App\Models\UserAccountNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Route;

Route::middleware(['CheckUserRole:admin'])->prefix('backoffice')->name('admin.')->group(function () {
 Route::get('/dashboard', [Admin::class, 'index'])->name('index');
 Route::get('/', [Admin::class, 'index'])->name('index');

 Route::post('/logout', [Login::class, 'logout'])->name('logout');
 Route::get('/profile', [Admin::class, 'profile'])->name('profile');
 Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('update-profile');

 Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
 Route::post('/profile/refer', [ProfileController::class, 'referralProgram'])->name('generate-referrar');
 Route::get('/activities', [Admin::class, 'index'])->name('activities');


 // campaigns routes
 Route::get('/campaigns', [Admin::class, 'campaignIndex'])->name('campaigns');
 Route::get('/campaigns/search', [Admin::class, 'campaignIndex'])->name('search-campaigns');
 Route::get('/campaigns/new', [Admin::class, 'newCampaignIndex'])->name('create-campaign');
 Route::post('/campaigns/new', [Campaign::class, 'new'])->name('create-campaign-post');
 Route::get('/campaigns/view/{id}', [Admin::class, 'viewCampaign'])->name('view-campaign');
 Route::get('/campaigns/edit/{id}', [Admin::class, 'updateCampaignIndex'])->name('update-campaign');
 Route::post('/campaigns/edit/{id}', [Campaign::class, 'edit'])->name('update-campaign-post');
 Route::post('/campaigns/delete/{id}', [Campaign::class, 'delete'])->name('delete-campaign');
 Route::get('/campaigns/reports', [Admin::class, 'campaignsReportsIndex'])->name('campaigns-reports');
 Route::get('/campaigns/reports/search', [Admin::class, 'campaignsReportsIndex'])->name('search-campaigns-reports');
 Route::get('/campaigns/reports/view/{id}', [Admin::class, 'viewCampaignReport'])->name('view-campaigns-reports');

 Route::post('/campaigns/view/{id}/visibility', [CampaignManager::class, 'campaignVisibility'])->name('campaigns-visibility');

 Route::get('/campaigns/agents', [Admin::class, 'allAgents'])->name('all-agents');
 Route::get('/campaigns/{id}/agents', [Admin::class, 'campaignAgents'])->name('campaign-agents');
 Route::get('/campaigns/{id}/agents/search', [Admin::class, 'campaignAgents'])->name('search-campaign-agent');
 Route::post('/campaigns/{id}/agents/new', [Admin::class, 'campaignIndex'])->name('new-campaign-agent');
 Route::get('/campaigns/{campaignId}/agents/view/{agentId}', [Admin::class, 'viewAgent'])->name('view-campaign-agent');
 Route::post('/campaigns/{campaignId}/agents/view/{agentId}', [Admin::class, 'editAgent'])->name('edit-campaign-agent');
 Route::post('/campaigns/{campaignId}/agents/delete/{agentId}', [Admin::class, 'deleteAgent'])->name('delete-campaign-agent');

 Route::get('/campaigns/payouts', [CampaignsPayout::class, 'adminCampaignPayoutIndex'])->name('payout-index');
 Route::get('/campaigns/payouts/{id}', [CampaignsPayout::class, 'adminCampaignPayout'])->name('campaign-payout-index');
 Route::post('/campaigns/payouts/{id}/manage', [CampaignsPayout::class, 'adminManageStatus'])->name('campaign-payout-manage');

 Route::match(['get', 'post'], '/reports/generate/weekly/{id}', [ReportController::class, 'weeklyData'])->name('generate-weekly-report');

 Route::get('/users', [Admin::class, 'users'])->name('all-users');
 Route::get('/users/{id}', [Admin::class, 'viewUser'])->name('view-user');
 Route::post('/users/add', [Admin::class, 'addAgentUser'])->name('add-user');
 Route::post('/users/{id}/edit', [Admin::class, 'editAgentUser'])->name('edit-user');
 Route::post('/users/{id}/delete', [Admin::class, 'deleteAgentUser'])->name('delete-user');
 Route::post('/users/{id}/assign/{campaignId}', [Admin::class, 'assignUserToCampaign'])->name('assign-user-to-campaign');


 Route::get('/campaigns/donors', [Admin::class, 'allDonors'])->name('all-donors');
 Route::get('/campaigns/{id}/donors', [Admin::class, 'campaignDonors'])->name('campaign-donors');


 Route::get('/donations/stats', [Admin::class, 'donationsStats'])->name('all-web-donations');
 Route::get('/donations/stats/view/{id}', [Admin::class, 'viewDonationsStats'])->name('view-web-donation');

 Route::get('/donations/ussd', [Admin::class, 'ussdDonationsStats'])->name('all-ussd-donations');
 Route::get('/donations/ussd/{id}', [Admin::class, 'ussdDonationsStats'])->name('view-ussd-donations');

 Route::get('/donations', [Admin::class, 'donationsReceipt'])->name('donation-receipts');

 Route::get('/donations/receipts', [Admin::class, 'donationsReceipt'])->name('donation-receipts');
 Route::get('/donations/receipts/{id}', [Admin::class, 'campaignReceiptIndex'])->name('campaign-donation-receipts');
 Route::post('/donations/receipts/{id}', [Admin::class, 'storeorRemoveSelectedReceipts'])->name('store-unpaid-donation-receipt');

 Route::get('/donations/receipts/{id}/new', [Admin::class, 'newDonationsReceiptIndex'])->name('create-donation-receipt');
 Route::post('/donations/receipts/{id}/new', [Admin::class, 'createReceipt'])->name('create-donation-receipt-post');
 Route::get('/donations/receipts/{id}/pay', [Admin::class, 'payReceiptIndex'])->name('pay-donation-receipt');
 Route::post('/donations/receipts/{id}/pay', [Admin::class, 'payReceipt'])->name('pay-donation-receipts');

 Route::post('/donations/receipts/{id}/unpay', [Admin::class, 'storeorRemoveSelectedReceipts'])->name('remove-unpaid-donation-receipt');
 Route::post('/export-donations', [Admin::class, 'exportDonations'])->name('export.donations');
 Route::post('/donations/verify-payment', [Admin::class, 'verifyDonation'])->name('verify-payment');


 Route::get('/campaigns/users', [Admin::class, 'users'])->name('all-users');
 Route::get('/campaigns/users/{id}', [Admin::class, 'viewUser'])->name('view-user');
 Route::post('/campaigns/users/add', [Admin::class, 'addAgentUser'])->name('add-user');
 Route::post('/campaigns/users/{id}/edit', [Admin::class, 'editAgentUser'])->name('edit-user');
 Route::post('/campaigns/users/{id}/delete', [Admin::class, 'deleteAgentUser'])->name('delete-user');
 Route::post('/campaigns/users/{id}/assign/{campaignId}', [Admin::class, 'assignUserToCampaign'])->name('assign-user-to-campaign');


 Route::get('/campaigns/commission', [Admin::class, 'commission'])->name('all-commission');
 Route::post('/campaigns/commission/modify/{id?}', [Admin::class, 'createOrUpdateCommission'])->name('campaign-commission');


 Route::get('/system/users', [SystemManagement::class, 'systemUsers'])->name('system-users');
 Route::post('/system/users/add', [SystemManagement::class, 'addUser'])->name('add-system-user');
 Route::get('/system/users/view/{id}', [SystemManagement::class, 'viewUser'])->name('view-system-user');
 Route::post('/system/users/view/{id}', [SystemManagement::class, 'editUser'])->name('update-system-user');
 Route::post('/system/users/delete/{id}', [SystemManagement::class, 'deleteUser'])->name('delete-system-user');

 Route::get('/system/settings/exchnage-rate', [SystemManagement::class, 'exchangeRate'])->name('exchange-rate');
 Route::post('/system/settings/exchnage-rate', [SystemManagement::class, 'editExchangeRate'])->name('update-exchange-rate');

});
