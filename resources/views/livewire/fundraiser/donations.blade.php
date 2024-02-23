<div>
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">{{ ucfirst($method) ? : 'All'}} Donations</h3>
            </div><!-- .nk-block-head-content -->
            <div class="nk-block-head-content">
                <ul class="nk-block-tools g-4">
                    <li style="margin: 2px" class="nk-block-tools-opt">
                        <a  href="{{ route('manager.create-donation-receipt') }}"
                           class="btn btn-icon btn-secondary d-md-none"><em class="icon ni ni-download-cloud"></em></a>
                        <button type="button" wire:click="exportDonations"
                                class="btn btn-secondary d-none d-md-inline-flex"><em
                                class="icon ni ni-download-cloud"></em><span>Export</span>
                        </button>
                    </li>
                    <li style="margin: 2px" class="nk-block-tools-opt">
                        <a  href="{{ route('manager.create-donation-receipt') }}"
                           class="btn btn-icon btn-primary d-md-none"><em class="icon ni ni-plus"></em></a>
                        <a  href="{{ route('manager.create-donation-receipt') }}"
                           class="btn btn-primary d-none d-md-inline-flex"><em class="icon ni ni-plus"></em><span>Add
                                Receipt</span></a>
                    </li>
                </ul>

            </div><!-- .nk-block-head-content -->
        </div><!-- .nk-block-between -->
    </div><!-- .nk-block-head -->
    <div class="nk-block">
        <div class="card card-stredtch" wire:ignore>
            <div class="card-inner-group">
                <div class="nk-tb-list is-separate is-medium mb-3">

                    <div class="card-inner position-relative card-tools-toggle" wire:ignore>
                        <div class="card-title-group">
                            <div class="card-tools">
                                <div class="form-inline flex-nowrap gx-3">
                                    <div class="form-wrap w-150px">
                                        <select id="method" class="form-select js-selsect2" data-search="on"
                                                wire:model.live="method" name="method">
                                            <option value="" selected>All Type</option>
                                            <option value="web"
                                                    @if (isset($method) && $method == 'web') selected @endif>Web
                                            </option>
                                            <option value="receipt"
                                                    @if (isset($method) && $method == 'receipt') selected @endif>
                                                Cash
                                            </option>
                                            <option value="ussd"
                                                    @if (isset($method) && $method == 'ussd') selected @endif>
                                                USSD
                                            </option>
                                        </select>
                                    </div>

                                </div><!-- .form-inline -->
                            </div><!-- .card-tools -->
                            <div class="card-tools me-n1" wire:ignore>
                                <ul class="btn-toolbar gx-1">
                                    <li>
                                        <a href="#" class="btn btn-icon search-toggle toggle-search"
                                           data-target="search"><em class="icon ni ni-search"></em></a>
                                    </li><!-- li -->
                                    <li class="btn-toolbar-sep"></li><!-- li -->
                                    <li>
                                        <div class="toggle-wrap" wire:ignore>
                                            <a href="#" class="btn btn-icon btn-trigger toggle"
                                               data-target="cardTools"><em class="icon ni ni-menu-right"></em></a>
                                            <div class="toggle-content" data-content="cardTools">
                                                <ul class="btn-toolbar gx-1">
                                                    <li class="toggle-close">
                                                        <a href="#" class="btn btn-icon btn-trigger toggle"
                                                           data-target="cardTools"><em
                                                                class="icon ni ni-arrow-left"></em></a>
                                                    </li><!-- li -->
                                                    <li>
                                                        <div class="dropdown">
                                                            <a href="#"
                                                               class="btn btn-trigger btn-icon dropdown-toggle"
                                                               data-bs-toggle="dropdown">
                                                                <div class="dot dot-primary"></div>
                                                                <em class="icon ni ni-filter-alt"></em>
                                                            </a>
                                                            <div
                                                                class="filter-wg dropdown-menu dropdown-menu-xl dropdown-menu-end">
                                                                <div class="dropdown-head">
                                                                    <span class="sub-title dropdown-title">Filter
                                                                        By Date</span>
                                                                </div>
                                                                <div class="dropdown-body dropdown-body-rg">
                                                                    <div class="row gx-6 gy-3">

                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    class="overline-title overline-title-alt">From</label>
                                                                                <input type="date"
                                                                                       class="form-control date-piscker"
                                                                                       wire:model.live="dateFrom"
                                                                                       name="dateFrom"/>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label
                                                                                    class="overline-title overline-title-alt">To</label>
                                                                                <input type="date"
                                                                                       class="form-control date-pickser"
                                                                                       wire:model.live="dateTo"
                                                                                       name="dateTo"/>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="dropdown-foot between">
                                                                    <button type="button"
                                                                            class="btn btn-secondary clickable"
                                                                            wire:click="clearFilters">Reset
                                                                        Filters
                                                                    </button>
                                                                </div>
                                                            </div><!-- .filter-wg -->
                                                        </div><!-- .dropdown -->
                                                    </li><!-- li -->

                                                </ul><!-- .btn-toolbar -->
                                            </div><!-- .toggle-content -->
                                        </div><!-- .toggle-wrap -->
                                    </li><!-- li -->
                                </ul><!-- .btn-toolbar -->
                            </div><!-- .card-tools -->
                        </div><!-- .card-title-group -->
                        <div class="card-search search-wrap" data-search="search">
                            <div class="card-body">
                                <div class="search-content">
                                    <a href="#" class="search-back btn btn-icon toggle-search"
                                       data-target="search"><em class="icon ni ni-arrow-left"></em></a>
                                    <input type="text" class="form-control border-transparent form-focus-none"
                                           placeholder="Search by name, email, phone, country, agent id"
                                           wire:model.live="keyword" value="{{ $keyword }}">
                                    <button class="search-submit btn btn-icon"><em
                                            class="icon ni ni-search"></em></button>
                                </div>
                            </div>
                        </div><!-- .card-search -->
                    </div>
                </div>
            </div>
        </div>
        <div class="nk-tb-list is-separate is-medium mb-3">
            <div class="nk-tb-item nk-tb-head">
                <div class="nk-tb-col nk-tb-col-check">
                    <div class="custom-control custom-control-sm custom-checkbox notext">
                        <input type="checkbox" class="custom-control-input" id="oid">
                        <label class="custom-control-label" for="oid"></label>
                    </div>
                </div>
                <div class="nk-tb-col"><span>Ref</span></div>
                <div class="nk-tb-col "><span>Name</span></div>
                <div class="nk-tb-col tb-col-md"><span>Phone</span></div>
                <div class="nk-tb-col "><span>Amount GH₵ </span></div>
                <div class="nk-tb-col tb-col-md"><span class="d-none d-sm-block">Status</span></div>
                <div class="nk-tb-col tb-col-md"><span>Date</span></div>
                <div class="nk-tb-col nk-tb-col-tools">
                    <ul class="nk-tb-actions gx-1 my-n1">
                        <li>
                            <div class="drodown">
                                <a href="#" class="dropdown-toggle btn btn-icon btn-trigger me-n1"
                                   data-bs-toggle="dropdown"><em class="icon ni ni-more-h"></em></a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <ul class="link-list-opt no-bdr">
                                        <li><a href="#"><em class="icon ni ni-edit"></em><span>Update
                                                    Status</span></a></li>

                                    </ul>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div><!-- .nk-tb-item -->
            @foreach ($donations as $donation)
                <div class="nk-tb-item">
                    <div class="nk-tb-col nk-tb-col-check">
                        <div class="custom-control custom-control-sm custom-checkbox notext">
                            <input type="checkbox" class="custom-control-input" id="oid01">
                            <label class="custom-control-label" for="oid01"></label>
                        </div>
                    </div>
                    <div class="nk-tb-col">
                        <span class="tb-lead"><a href="#">#{{ $donation->donation_ref }}</a></span>
                    </div>
                    <div class="nk-tb-col">
                        <span class="tb-sub">{{ $donation->donor_name }}</span>
                    </div>
                    <div class="nk-tb-col tb-col-md">
                        <span class="tb-sub">{{ $donation->momo_number }}</span>
                    </div>
                    <div class="nk-tb-col ">
                        <span class="tb-lead">{{ $donation->amount }}</span>
                    </div>
                    <div class="nk-tb-col tb-col-md">
                        @php
                            $statusColorCode = $donation->status === 'paid' ? 'success' : 'warning';
                        @endphp
                        <span class="dot bg-{{ $statusColorCode }} d-sm-none"></span>
                        <span
                            class="badge badge-sm badge-dot has-bg bg-{{ $statusColorCode }} d-none d-sm-inline-flex">{{ ucfirst($donation->status) }}</span>
                    </div>
                    <div class="nk-tb-col tb-col-md">
                        <span class="tb-lead">{{ $donation->created_at->format('M J, Y') }}</span>
                    </div>
                    <div class="nk-tb-col nk-tb-col-tools">
                        <ul class="nk-tb-actions gx-1">
                            <li class="nk-tb-action-hidden"><a href="#"
                                                               class="btn btn-icon btn-trigger btn-tooltip"
                                                               title="View Order">
                                    <em class="icon ni ni-eye"></em></a></li>
                            <li>
                                <div class="drodown me-n1">
                                    <a href="#" class="dropdown-toggle btn btn-icon btn-trigger"
                                       data-bs-toggle="dropdown"><em class="icon ni ni-more-h"></em></a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <ul class="link-list-opt no-bdr">
                                            <li><a href="#"><em
                                                        class="icon ni ni-eye"></em><span>View</span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div><!-- .nk-tb-item -->
            @endforeach
        </div><!-- .nk-tb-list -->
        @if ($donations->count() > 0)
            <div class="card">
                <div class="card-inner">
                    {{ $donations->links() }}
                </div>
            </div>
        @endif

    </div><!-- .nk-block -->
</div>
