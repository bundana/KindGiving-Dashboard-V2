@extends('partials.menus.base')
@section('content')
    <!-- content @s
        -->
    <div class="nk-content ">
        <div class="container-fluid">
            <div class="nk-content-inner">
                <div class="nk-content-body">
                    <div class="nk-block-head nk-block-head-sm">
                        <div class="nk-block-between">
                            <div class="nk-block-head-content">
                                <h3 class="nk-block-title page-title">Agents Lists</h3>
                                <div class="nk-block-des text-soft">
                                    <p>You have total {{$agents->count()}} agents.</p>
                                </div>
                            </div><!-- .nk-block-head-content -->
                            <div class="nk-block-head-content">
                                <div class="toggle-wrap nk-block-tools-toggle">
                                    <a href="#" class="btn btn-icon btn-trigger toggle-expand me-n1"
                                       data-target="pageMenu"><em class="icon ni ni-menu-alt-r"></em></a>
                                    <div class="toggle-expand-content" data-content="pageMenu">
                                        <ul class="nk-block-tools g-3">

                                            <li style="margin: 2px" class="nk-block-tools-opt">
                                                <a href="{{ route('manager.create-donation-receipt') }}"
                                                   class="btn btn-icon btn-primary d-md-none"><em
                                                        class="icon ni ni-plus"></em></a>
                                                <a href="{{ route('manager.create-donation-receipt') }}"
                                                   class="btn btn-primary d-none d-md-inline-flex"><em
                                                        class="icon ni ni-plus"></em><span>Add </span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div><!-- .toggle-wrap -->
                            </div><!-- .nk-block-head-content -->
                        </div><!-- .nk-block-between -->
                    </div><!-- .nk-block-head -->
                    <div class="nk-block">
                        <div class="card card-stretch">
                            <div class="card-inner-group">
                                <div class="card-inner position-relative card-tools-toggle">
                                    <div class="card-title-group">
                                        <div class="card-tools">
                                            <div class="form-inline flex-nowrap gx-3">
                                                <!--blank -->
                                            </div><!-- .form-inline -->
                                        </div><!-- .card-tools -->
                                        <div class="card-tools me-n1">
                                            <ul class="btn-toolbar gx-1">
                                                <li>
                                                    <a href="#" class="btn btn-icon search-toggle toggle-search"
                                                       data-target="search"><em class="icon ni ni-search"></em></a>
                                                </li><!-- li -->
                                                <li class="btn-toolbar-sep"></li><!-- li -->

                                            </ul><!-- .btn-toolbar -->
                                        </div><!-- .card-tools -->
                                    </div><!-- .card-title-group -->
                                    <div class="card-search search-wrap" data-search="search">
                                        <div class="card-body">
                                            <form action="{{ url()->full() }}"  method="get">


                                            <div class="search-content">
                                                <a href="#" class="search-back btn btn-icon toggle-search"
                                                   data-target="search"><em class="icon ni ni-arrow-left"></em></a>
                                                <input type="text" name="search" value="{{ request()->query('search') }}"
                                                       class="form-control border-transparent form-focus-none"
                                                       placeholder="Search by user or email">
                                                <button class="search-submit btn btn-icon"><em
                                                        class="icon ni ni-search"></em></button>
                                            </div>
                                                </form>
                                        </div>
                                    </div><!-- .card-search -->
                                </div><!-- .card-inner -->
                                <div class="card-inner p-0">
                                    <div class="nk-tb-list nk-tb-ulist">
                                        <div class="nk-tb-item nk-tb-head">
                                            <div class="nk-tb-col"><span class="sub-text">Agent</span></div>
                                            <div class="nk-tb-col tb-col-md"><span class="sub-text">Phone</span></div>
                                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Email</span></div>
                                            <div class="nk-tb-col tb-col-lg"><span class="sub-text">Date Joined</span> </div>
                                             <div class="nk-tb-col tb-col-lg"><span class="sub-text">Actions</span> </div>
                                        </div><!-- .nk-tb-item -->
                                        @if($agents->count() > 0)
                                            @foreach($agents as $agent)
                                                <div class="nk-tb-item">
                                                    <div class="nk-tb-col">
                                                        <a href="{{ route('manager.view-agent',['id' => $agent->user->user_id]) }}">
                                                            <div class="user-card">
                                                                <div class="user-avatar bg-primary">
                                                                    <img src="{{$agent->user->avatar}}" alt="img">
                                                                </div>
                                                                <div class="user-info">
                                                            <span class="tb-lead">{{$agent->name}}<span
                                                                    class="dot dot-success d-md-none ms-1"></span></span>
                                                                    <span>{{ $agent->user->user_id }}</span>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </div>
                                                    <div class="nk-tb-col tb-col-md">
                                                        <span>{{ $agent->user->phone_number }}</span>
                                                    </div>
                                                    <div class="nk-tb-col tb-col-lg">
                                                        <span>{{ $agent->user->email }}</span>
                                                    </div>
                                                    <div class="nk-tb-col tb-col-lg">
                                                        <span>10 Feb 2020</span>
                                                    </div>
                                                    <div class="nk-tb-col nk-tb-col-tools">
                                                        <ul class="nk-tb-actisons gx-1">
                                                            <li>
                                                                <div class="drodown">
                                                                    <a href="#"
                                                                       class="dropdown-toggle btn btn-icon btn-trigger"
                                                                       data-bs-toggle="dropdown"><em
                                                                            class="icon ni ni-more-h"></em></a>
                                                                    <div class="dropdown-menu dropdown-menu-end">
                                                                        <ul class="link-list-opt no-bdr">
                                                                            <li><a href="{{ route('manager.view-agent',['id' => $agent->user->user_id]) }}"><em
                                                                                        class="icon ni ni-eye"></em><span>View Details</span></a>
                                                                            </li>
                                                                            <li><a href="{{ route('manager.view-agent',['id' => $agent->user->user_id]) }}"><em
                                                                                        class="icon ni ni-repeat"></em><span>Donations</span></a>
                                                                            </li>
                                                                            <li class="divider"></li>
                                                                            <li><a href="#"><em
                                                                                        class="icon ni ni-shield-star"></em><span>Reset Pass</span></a>
                                                                            </li>
                                                                            <li><a href="#"><em
                                                                                        class="icon ni ni-trash"></em><span>Suspend User</span></a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div><!-- .nk-tb-item -->
                                            @endforeach
                                        @else
                                            {{--                                          no agents--}}
                                            <div class="text-center"
                                            > No agents
                                            </div>
                                        @endif

                                    </div><!-- .nk-tb-list -->
                                </div><!-- .card-inner -->
                                <div class="card-inner">
                                    <div class="nk-block-between-md g-3">
                                        {{ $agents->links() }}
                                    </div><!-- .nk-block-between -->
                                </div><!-- .card-inner -->
                            </div><!-- .card-inner-group -->
                        </div><!-- .card -->
                    </div><!-- .nk-block -->
                </div>
            </div>
        </div>
    </div>
@endsection
