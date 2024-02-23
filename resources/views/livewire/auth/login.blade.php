<form class="donate-now__personal-info-form" wire:submit.prevent="signIn">
    <div class="form-group">
        <div class="form-group">
            <div class="form-label-group">
                {{-- <label class="form-label" for="default-01">Email</label> --}}
            </div>
            <div class="form-control-wrap">
                <input type="text" class="form-control form-control-lg" id="email_phone" wire:model="email_phone" name="email_phone"
                    placeholder="Email address or phone number">
                @error('email_phone')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div><!-- .form-group -->
        <div class="form-group">
            <div class="form-control-wrap">
                <a tabindex="-1" href="#" class="form-icon form-icon-right passcode-switch lg"
                    data-target="password">
                    <em class="passcode-icon icon-show icon ni ni-eye"></em>
                    <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
                </a>
                <input type="password" class="form-control form-control-lg" id="password" wire:model="password"
                    name="password" placeholder="Password">
                @error('password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                @if ($errorMessage)
                    <span class="text-danger">{{ $errorMessage }}</span>
                @endif
            </div>
            <div wire:loading>
                @include('livewire.placeholders.loading')
            </div>
        </div><!-- .form-group -->
        <div class="form-group">
            <button type="submit" class="btn btn-lg btn-primary btn-block">Sign In</button>
        </div>
    </div>
</form><!-- form -->
