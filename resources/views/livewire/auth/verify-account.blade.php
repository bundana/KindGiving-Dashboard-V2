<form class="donate-now__personal-info-form" wire:submit.prevent="verifyAccount">
    <div class="form-group">
        <div class="form-label-group">
            {{-- <label class="form-label" for="default-01">Full Name</label> --}}
        </div>
        <div class="form-control-wrap">
            <input type="text" class="form-control form-control-lg" id="otp" wire:model="otp" name="otp"
                placeholder="Enter otp" required max="6">
            @error('otp')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            @if ($errorMessage)
                <span class="text-danger">{{ $errorMessage }}</span>
            @endif
        </div>
        @if ($successMessage)
            <span class="text-success">{{ $successMessage }}</span>
        @endif
    </div><!-- .form-group -->

    <div wire:loading>
        @include('livewire.placeholders.loading')
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-lg btn-primary btn-block">Verify Code</button>
    </div>
    <div class="form-note-s2 pt-4">
        <a href="#" id="resendLink"></a>

    </div>
</form><!-- form -->
@push('js')
    <!-- Include the necessary scripts for toastify -->
    <script>
        $(document).ready(function() {
            var resendLink = $('#resendLink');
            var countdown = 7; // 2 minutes in seconds

            // Function to update the link text with the countdown
            function updateLinkText() {
                var minutes = Math.floor(countdown / 60);
                var seconds = countdown % 60;
                resendLink.text('Resend Code (' + minutes + 'm ' + seconds + 's)');
            }

            // Function to disable the link and start the countdown
            function disableLink() {
                resendLink.off('click'); // Remove click event
                resendLink.addClass('disabled'); // Add a class for styling
                updateLinkText();

                // Update countdown every second
                var countdownInterval = setInterval(function() {
                    countdown--;
                    updateLinkText();

                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        enableLink();
                    }
                }, 1000);
            }

            // Function to enable the link and reattach click event
            function enableLink() {
                countdown = 0; // Reset countdown
                resendLink.removeClass('disabled');
                resendLink.text('Resend Code');
                resendLink.on('click', function(e) {
                    e.preventDefault();
                    disableLink();
                    // Add back the wire:click after timer
                    resendLink.attr('wire:click', 'resendOTP');
                });
            }

            // Disable the link initially
            disableLink();
        });
    </script>
@endpush
