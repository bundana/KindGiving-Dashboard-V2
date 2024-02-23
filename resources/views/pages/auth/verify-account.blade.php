@extends('partials.menus.auth')
@section('content')
    <!-- main  -->
    <div class="nk-main ">
        <!-- wrap  -->
        <div class="nk-wrap nk-wrap-nosidebar">
            <!-- content  -->
            <div class="nk-content ">
                <div class="nk-split nk-split-page nk-split-md">
                    <div class="nk-split-content nk-block-area nk-block-area-column nk-auth-container bg-white">
                        <div class="nk-block nk-block-middle nk-auth-body">
                            <div class="brand-logo pb-5">
                                <a href="{{ url()->full() }}" class="logo-link">
                                    <img class="logo-light logo-img logo-img-lg"
                                        src="{{ asset('assets/images/logo-dark.png') }}" width="140"
                                        srcset="{{ asset('assets/images/logo-dark.png') }}" alt="logo">
                                    <img class="logo-dark logo-img logo-img-lg"
                                        src="{{ asset('assets/images/logo-dark.png') }}" width="140"
                                        srcset="{{ asset('assets/images/logo-dark.png') }}" alt="logo-dark">
                                </a>
                            </div>
                            @php
                            use Illuminate\Support\Str; 

                                $phone = Str::of(233542345921)->mask('*', 3);
                                $email = Str::of('taylor@example.com')->mask('*', -15, 3); 
                            @endphp
                            <div class="nk-block-head">
                                <div class="nk-block-head-content">
                                    <h5 class="nk-block-title">Verify Account</h5>
                                    <div class="nk-block-des">
                                        <p>Enter 6 digits OTP sent to email {{$email}} or phone {{ $phone }}</p>
                                    </div>
                                </div>
                            </div><!-- .nk-block-head -->
                             
                           <livewire:auth.verify-account :user="$user" :tokenData="$tokenData" />

                          

                        </div><!-- .nk-block -->
                        @include('partials.menus.auth-footer')
                    </div><!-- .nk-split-content -->
                    <div class="nk-split-content nk-split-stretch bg-abstract"></div><!-- .nk-split-content -->
                </div><!-- .nk-split -->
            </div>
            <!-- wrap  -->
        </div>
        <!-- content  -->
    </div>
    <!-- main  -->
@endsection
