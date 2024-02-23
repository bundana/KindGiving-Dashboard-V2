@extends('partials.menus.base')
@section('content')
    <!-- content @s-->

        <div class="nk-content ">
            <div class="container-fluid">
                <div class="nk-content-inner">
                    <div class="nk-content-body">
                        <div class="nk-block-head nk-block-head-sm">
                            <div class="nk-block-between">
                                <div class="nk-block-head-content">
                                    <h3 class="nk-block-title page-title">Dashboard</h3>
                                </div><!-- .nk-block-head-content -->
                                <div class="nk-block-head-content">

                                </div><!-- .nk-block-head-content -->
                            </div><!-- .nk-block-between -->
                        </div><!-- .nk-block-head -->
                        <div class="nk-block">
                            <div class="row g-gs">
                                <div class="col-xxl-3 col-sm-4">
                                    <div class="card">
                                        <div class="nk-ecwg nk-ecwg6">
                                            <div class="card-inner">
                                                <div class="card-title-group">
                                                    <div class="card-title">
                                                        <h6 class="title">Total Donations</h6>
                                                    </div>
                                                </div>
                                                <div class="data">
                                                    <div class="data-group">
                                                        <div class="amount">
                                                            {{ Number::abbreviate($donations->sum('amount'), precision: 2) }}
                                                        </div>
                                                        <div class="nk-ecwg6-ck">
                                                            <canvas class="ecommerce-line-chart-s3" id="todayOrders"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- .card-inner -->
                                        </div><!-- .nk-ecwg -->
                                    </div><!-- .card -->
                                </div><!-- .col -->
                                <div class="col-xxl-3 col-sm-4">
                                    <div class="card">
                                        <div class="nk-ecwg nk-ecwg6">
                                            <div class="card-inner">
                                                <div class="card-title-group">
                                                    <div class="card-title">
                                                        <h6 class="title"> Total Donors</h6>
                                                    </div>
                                                </div>
                                                <div class="data">
                                                    <div class="data-group">
                                                        <div class="amount">{{ $donations->count() }}</div>
                                                        <div class="nk-ecwg6-ck">
                                                            <canvas class="ecommerce-line-chart-s3" id="todayRevenue"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- .card-inner -->
                                        </div><!-- .nk-ecwg -->
                                    </div><!-- .card -->
                                </div><!-- .col -->
                                <div class="col-xxl-3 col-sm-4">
                                    <div class="card">
                                        <div class="nk-ecwg nk-ecwg6">
                                            <div class="card-inner">
                                                <div class="card-title-group">
                                                    <div class="card-title">
                                                        <h6 class="title">All Agents</h6>
                                                    </div>
                                                </div>
                                                <div class="data">
                                                    <div class="data-group">
                                                        <div class="amount">{{ $agents->count() }}</div>
                                                        <div class="nk-ecwg6-ck">
                                                            <canvas class="ecommerce-line-chart-s3"
                                                                id="todayCustomers"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- .card-inner -->
                                        </div><!-- .nk-ecwg -->
                                    </div><!-- .card -->
                                </div><!-- .col -->


                                <div class="col-xxl-3 col-md-6">
                                    <div class="card card-full overflow-hidden">
                                        <div class="nk-ecwg nk-ecwg7 h-100">
                                            <div class="card-inner flex-grow-1">
                                                <div class="card-title-group mb-4">
                                                    <div class="card-title">
                                                        <h6 class="title">Donation Method Statistics</h6>
                                                    </div>
                                                </div>
                                                <div class="nk-ecwg7-ck">
                                                    <canvas class="ecommerce-doughnut-s1" id="orderStatistics"></canvas>
                                                </div>
                                                <ul class="nk-ecwg7-legends">
                                                    <li>
                                                        <div class="title">
                                                            <span class="dot dot-lg sq" data-bg="#816bff"></span>
                                                            <span>USSD</span>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="title">
                                                            <span class="dot dot-lg sq" data-bg="#13c9f2"></span>
                                                            <span>WEB</span>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="title">
                                                            <span class="dot dot-lg sq" data-bg="#ff82b7"></span>
                                                            <span>CASH</span>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div><!-- .card-inner -->
                                        </div>
                                    </div><!-- .card -->
                                </div><!-- .col -->
                                <div class="col-xxl-3 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-inner">
                                            <div class="card-title-group mb-2">
                                                <div class="card-title">
                                                    <h6 class="title">Balance</h6>
                                                </div>
                                            </div>
                                            <div class="nk-order-ovwg">
                                                <div class="row g-4 align-end">
                                                    <div class="col-xxl-4">
                                                        <div class="row g-4">
                                                            <div class="col-sm-6 col-xxl-12">
                                                                <div class="nk-order-ovwg-data buy">
                                                                    <div class="amount">{{ number_format($campaignBal, 2) }}
                                                                        <small class="currenct currency-usd">GH₵</small></div>
                                                                    <div class="title"><em
                                                                            class="icon ni ni-arrow-down-left"></em>Campaign
                                                                        Balance</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6 col-xxl-12">
                                                                <div class="nk-order-ovwg-data sell">
                                                                    <div class="amount">{{ number_format($accBal, 2) }} <small
                                                                            class="currenct currency-usd">GH₵</small></div>
                                                                    <div class="title"><em
                                                                            class="icon ni ni-arrow-up-left"></em>Account
                                                                        Balance</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div><!-- .col -->
                                                </div>
                                            </div><!-- .nk-order-ovwg -->
                                            <div class="user-account-actions">
                                                <ul class="g-3">
                                                    <li>
                                                        <button type="button" class="btn btn-lg btn-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#depositFundsModal"><span>Deposit</span></button>
                                                    <li>
                                                        <button type="button" class="btn btn-lg btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#depositFundsModal"><span>Withdraw</span></button>
                                                </ul>
                                            </div>



                                        </div><!-- .card-inner -->

                                    </div><!-- .card -->
                                </div><!-- .col -->

                                @php
                                    $recentDonations = $donations->take(10);
                                @endphp
                                <div class="col-xxl-8">
                                    <div class="card card-full">
                                        <div class="card-inner">
                                            <div class="card-title-group">
                                                <div class="card-title">
                                                    <h6 class="title">Recent Donations</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="nk-tb-list mt-n2">
                                            <div class="nk-tb-item nk-tb-head">
                                                <div class="nk-tb-col"><span>Ref No.</span></div>
                                                <div class="nk-tb-col tb-col-sm"><span>Donor</span></div>
                                                <div class="nk-tb-col"><span>Amount</span></div>
                                                <div class="nk-tb-col tb-col-md"><span>Date</span></div>
                                                <div class="nk-tb-col"><span class="d-none d-sm-inline">Status</span></div>
                                            </div>
                                            @if ($recentDonations->count() > 0)
                                                @foreach ($recentDonations as $recentDonor)
                                                    <div class="nk-tb-item">
                                                        <div class="nk-tb-col">
                                                            <span class="tb-lead"><a
                                                                    href="#">#{{ $recentDonor->donation_ref }}</a></span>
                                                        </div>
                                                        <div class="nk-tb-col tb-col-sm">
                                                            <div class="user-card">
                                                                {{-- <div class="user-avatar sm bg-purple-dim">
                                                            <span>AB</span>
                                                        </div> --}}
                                                                <div class="user-name">
                                                                    <span class="tb-lead">{{ $recentDonor->donor_name }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="nk-tb-col">
                                                            <span class="tb-sub tb-amount"><span>GH₵</span>
                                                                {{ number_format($recentDonor->amount, 2) }} </span>
                                                        </div>
                                                        <div class="nk-tb-col tb-col-md">
                                                            <span
                                                                class="tb-sub">{{ $recentDonor->created_at->format('M J, Y') }}</span>
                                                        </div>
                                                        <div class="nk-tb-col">
                                                            <span
                                                                class="badge badge-dot badge-dot-xs {{ $recentDonor->status === 'paid' ? 'bg-success' : 'bg-danger' }}">
                                                                {{ ucfirst($recentDonor->status) }}
                                                            </span>
                                                        </div>

                                                    </div>
                                                @endforeach
                                            @endif

                                        </div>
                                    </div><!-- .card -->
                                </div>

                            </div><!-- .row -->
                        </div><!-- .nk-block -->
                    </div>
                </div>
            </div>
        </div>
        <!-- content @e -->

        {{-- Modals --}}
        @include('partials.modals.fundraiser')
    @endsection

    @push('js')
        <script src="{{ asset('assets/js/charts/chart-ecommerce.js?ver=3.2.0') }}"></script>
    @endpush
