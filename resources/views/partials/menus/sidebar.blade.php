@php
    $user = auth()->user();
    $userRole = $user->role;
    // Query donations based on the search keyword
    $donations = App\Models\Campaigns\Donations::where('method', 'receipt')->where('status', 'unpaid')->where('agent_id', $user->user_id)->latest()->get();
    $allDonations = $donations ?: [];
@endphp
    <!-- sidebar @s -->
<div class="nk-sidebar nk-sidebar-fixed is-light " data-content="sidebarMenu" wire:ignore>
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a href="{{ route('manager.all-donations') }}" class="logo-link nk-sidebar-logo">
                <img class="logo-light logo-img" src="{{ asset('assets/images/logo.png') }}"
                     srcset="{{ asset('assets/images/logo-dark.png 2x') }}" alt="logo">
                <img class="logo-dark logo-img" src="{{ asset('assets/images/logo-dark.png') }}"
                     srcset="{{ asset('assets/images/favicon.png 2x') }}" alt="logo-dark">
                <img class="logo-small logo-img logo-img-small" src="{{ asset('assets/images/favicon.png') }}"
                     srcset="{{ asset('assets/images/favicon.png 2x') }}" alt="logo-small">
            </a>
        </div>
        <div class="nk-menu-trigger me-n2">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu"><em
                    class="icon ni ni-arrow-left"></em></a>
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex"
               data-target="sidebarMenu"><em class="icon ni ni-menu"></em></a>
        </div>
    </div><!-- .nk-sidebar-element -->
    <div class="nk-sidebar-element">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu" data-simplebar>
                @if ($userRole == 'campaign_manager')
                    {{-- Fundraiser Menus --}}
                    <ul class="nk-menu">
                        <li class="nk-menu-item" style="margin: 10px">

{{--                            <form id="campaignForm" wire:ignore--}}
{{--                                  action="{{ route('manager.save-selected-campaign') }}" method="post">--}}
{{--                                @csrf--}}
                                 <form id="campaignForm" wire:ignore >
                                @php
                                    $userCampaign = App\Models\Campaigns\SelectedCampaign::where('user_id', $user->user_id)->first();
                                $selectedCampaign = [];
                                if (!$userCampaign) {
                                    $selectedCampaign = App\Models\Campaigns\Campaign::where('manager_id', $user->user_id)->first();
                                } else {
                                   $selectedCampaign = $userCampaign;
                                }
                                $campaigns = App\Models\Campaigns\Campaign::where('manager_id', $user->user_id)->get();
                                @endphp
                                <div class="form-group" wire:ignore>
                                    <label class="form-label" for="campaignSelect">Select campaign</label>
                                    <div class="form-control-wrap" wire:ignore>
                                        <select id="campaignSelect" class="form-select js-select2" data-search="on" name="campaign_id">
                                            @foreach ($campaigns as $campaign)
                                                <option value="{{ $campaign->campaign_id }}"
                                                        @if (isset($selectedCampaign) && $selectedCampaign->campaign_id == $campaign->campaign_id) selected @endif>
                                                    {{ $campaign->name }}
                                                </option>
                                            @endforeach
                                            <option value="new">Add new</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </li><!-- .nk-menu-item -->

                        <li class="nk-menu-item">
                            <a href="{{ route('manager.dashboard') }}" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-dashboard-fill"></em></span>
                                <span class="nk-menu-text">Dashboard</span>
                            </a>
                        </li><!-- .nk-menu-item -->

                        <li class="nk-menu-heading">
                            <h6 class="overline-title text-primary-alt">Donation Tools</h6>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.all-donations') }}" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon fa-solid fa-hand-holding-heart"></em></span>
                                <span class="nk-menu-text">All Donations</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.all-donation-receipts') }}"
                               class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-files-fill"></em></span>
                                <span class="nk-menu-text">All Receipt</span><span
                                    class="nk-menu-badge">{{ $allDonations->count() }}</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.create-donation-receipt') }}"
                               class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon fa-solid fa-receipt"></em></span>
                                <span class="nk-menu-text">Create Cash Receipt</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.pay-donation-receipt') }}"
                               class="nk-menu-link">
                                 <span class="nk-menu-icon"><em
                                         class="icon fa-solid  fa-circle-dollar-to-slot"></em></span>
                                <span class="nk-menu-text">Pay Cash Received</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="tel:*713*367#" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon fa-solid fa-asterisk"></em></span>
                                <span class="nk-menu-text">USSD Payment</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.payment-link') }}" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon fa-solid fa-link"></em></span>
                                <span class="nk-menu-text">Payment Links</span>
                            </a>
                        </li><!-- .nk-menu-item -->

                        <li class="nk-menu-item">
                            <a href="{{ route('manager.direct-payment-link') }}"
                               class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon fa-solid fa-globe"></em></span>
                                <span class="nk-menu-text">Direct Web Payment</span>
                            </a>
                        </li><!-- .nk-menu-item -->

                        <li class="nk-menu-heading">
                            <h6 class="overline-title text-primary-alt">Miscellaneous</h6>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.payout') }}" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-wallet-fill"></em></span>
                                <span class="nk-menu-text">Payouts</span>
                            </a>
                        </li><!-- .nk-menu-item -->

                        <li class="nk-menu-item has-sub">
                            <a href="#" class="nk-menu-link nk-menu-toggle">
                                <span class="nk-menu-icon"><em class="icon ni ni-user-fill"></em></span>
                                <span class="nk-menu-text">Team Members</span>
                            </a>
                            <ul class="nk-menu-sub">
                                <li class="nk-menu-item">
                                    <a href="{{ route('manager.all-agents') }}"
                                       class="nk-menu-link"><span class="nk-menu-text">Agents</span></a>
                                </li>
                                <li class="nk-menu-item">
                                    <a href="{{ route('manager.all-users') }}"
                                       class="nk-menu-link"><span class="nk-menu-text">Users</span></a>
                                </li>
                            </ul><!-- .nk-menu-sub -->
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="{{ route('manager.support') }}" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-setting-alt-fill"></em></span>
                                <span class="nk-menu-text">Support</span>
                            </a>
                        </li><!-- .nk-menu-item -->


                    </ul><!-- .nk-menu -->
                    {{-- End Fundraiser Menus --}}
                @elseif($userRole == 'agent')
                @endif


            </div><!-- .nk-sidebar-menu -->
        </div><!-- .nk-sidebar-content -->
    </div><!-- .nk-sidebar-element -->
</div>
<!-- sidebar @e -->
@section('content2')
    <div id="overlay"
         style="display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background-color: rgba(255, 255, 255, 0.91); z-index: 999;">
        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 24px;">
            <h3 class="ff-base fw-medium">
                Switching Campaign to
                <small class="text-soft"><span id="campaignSwitchName"></span></small>
            </h3>
        </div>
    </div>
@endsection
@push('js')
    <script>
        $('#campaignForm').on('change', '#campaignSelect', function () {
            let campaignId = $(this).val();
            let campaignName = $(this).find('option:selected').text();
            let campaignSwitchName = document.getElementById('campaignSwitchName');
            campaignSwitchName.textContent = campaignName;
            if (campaignId === 'new') {
                window.location.reload();
            } else {
                $('#overlay').show();

                // Disable interactions
                $('body').css('pointer-events', 'none');

                setTimeout(function () {
                    $.ajax({
                        url: '{{ route('manager.save-selected-campaign') }}',
                        type: 'POST',
                        data: {
                            campaign_id: campaignId,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            $('#overlay').hide();
                            // Re-enable interactions
                            $('body').css('pointer-events', 'auto');
                            location.reload();
                            // Optionally, show a success message
                            toastr.clear();
                            NioApp.Toast(response.message, 'success', {
                                position: 'top-right'
                            });
                        },
                        error: function (response) {
                            $('#overlay').hide();
                            // Re-enable interactions
                            $('body').css('pointer-events', 'auto');
                            // Optionally, show an error message
                            toastr.clear();
                            NioApp.Toast(response.message, 'success', {
                                position: 'top-right'
                            });
                        }
                    });
                }, 1000); // Wait for 5 seconds before submitting the form
            }

        });
    </script>
@endpush
