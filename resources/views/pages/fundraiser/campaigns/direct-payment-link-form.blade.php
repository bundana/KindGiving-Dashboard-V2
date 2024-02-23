@extends('partials.menus.base')
@section('content')
    <!-- content @s
        -->
    <div class="nk-content ">
        <div class="container-fluid">
            <div class="nk-content-inner">
                <div class="nk-content-body">
                    <div class="components-preview wide-md mx-auto">
                        <div class="nk-block-head nk-block-head-lg wide-sm">
                            <div class="nk-block-head-content">
                                <h2 class="nk-block-title fw-normal">Generate initiate payment directly</h2>

                            </div>
                        </div><!-- .nk-block-head -->
                        <div class="nk-block nk-block-lg">

                            <div class="card">
                                <div class="card-inner">
                                    <form action="{{  url()->full()  }}" class="form"
                                          method="post">
                                        @csrf
                                        <div class="row g-gs">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="full_name">Full Name</label>
                                                    <div class="form-control-wrap">
                                                        <input type="text" class="form-control invalid"
                                                               id="full_name" name="full_name" required=""
                                                               value="{{ old('full_name') }}">
                                                    </div>
                                                    @error('full_name')
                                                    <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="email">Email address
                                                        (optional)</label>
                                                    <div class="form-control-wrap">
                                                        <div class="form-icon form-icon-right">
                                                            <em class="icon ni ni-mail"></em>
                                                        </div>
                                                        <input type="text" class="form-control" id="email"
                                                               name="email" value="{{ old('email') }}">
                                                    </div>
                                                    @error('email')
                                                    <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="phone">Phone</label>
                                                    <div class="form-control-wrap">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text" id="phone">+233</span>
                                                            </div>
                                                            <input type="text" class="form-control invalid" required=""
                                                                   id="phone" name="phone" value="{{ old('phone') }}">
                                                        </div>
                                                        @error('phone')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="amount">Amount</label>
                                                    <div class="form-control-wrap">
                                                        <input type="text" class="form-control invalid" id="amount"
                                                               name="amount" required="" value="{{ old('amount') }}">
                                                    </div>
                                                    @error('amount')
                                                    <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="message">Message</label>
                                                    <div class="form-control-wrap">
                                                        <textarea class="form-control form-control-sm"
                                                                  id="message" name="message"
                                                                  placeholder="Write your message">{{ old('message') }}</textarea>
                                                    </div>
                                                    @error('message')
                                                    <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-lg btn-primary">Create
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                    </form>
                                </div>
                            </div>
                        </div><!-- .nk-block -->

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
