@push('css')
 {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.19/build/css/intlTelInput.css"> --}}
@endpush
@push('js')
{{-- <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.19/build/js/intlTelInput.min.js"></script>
<script>
  const input = document.querySelector("#phone");
  window.intlTelInput(input, {
    initialCountry: "auto",
  geoIpLookup: callback => {
    fetch("https://ipapi.co/json")
      .then(res => res.json())
      .then(data => callback(data.country_code))
      .catch(() => callback("us"));
  },
     showSelectedDialCode: true,
    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.19/build/js/utils.js",
  });
</script> --}}
@endpush
<form class="donate-now__personal-info-form" wire:submit.prevent="createAccount">
      <div class="form-group">
          <div class="form-label-group">
              {{-- <label class="form-label" for="default-01">Full Name</label> --}}
          </div>
          <div class="form-control-wrap">
              <input type="text" class="form-control form-control-lg" id="name" wire:model="name" name="name"
                  placeholder="Full name">
              @error('name')
                  <span class="text-danger">{{ $message }}</span>
              @enderror
          </div>
      </div><!-- .form-group -->
      <div class="form-group">
          <div class="form-label-group">
              {{-- <label class="form-label" for="default-01">Email</label> --}}
          </div>
          <div class="form-control-wrap">
              <input type="text" class="form-control form-control-lg" id="email" wire:model="email"
                  name="email" placeholder="Email address">
              @error('email')
                  <span class="text-danger">{{ $message }}</span>
              @enderror
          </div>
      </div><!-- .form-group -->
      <div class="form-group">
          <div class="form-label-group">
              {{-- <label class="form-label" for="default-01">Phone No</label> --}}
          </div>
          <div class="form-control-wrap">
              <input type="text" class="form-control form-control-lg" id="phone" wire:model="phone"
                  name="phone" placeholder="Phone number">
              @error('phone')
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
              @if($errorMessage)
                  <span class="text-danger">{{ $errorMessage }}</span>
              @endif
          </div>
           <div wire:loading>
       @include('livewire.placeholders.loading')
    </div>
      </div><!-- .form-group -->
      <div class="form-group">
          <button type="submit" class="btn btn-lg btn-primary btn-block">Sign Up</button>
      </div>
  </form><!-- form -->
