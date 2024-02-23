   <div class="nk-block nk-auth-footer">
       <div class="nk-block-between">
           <ul class="nav nav-sm">
               <li class="nav-item">
                   <a class="nav-link" href="#">Privacy Policy</a>
               </li>
               <li class="nav-item">
                   <a class="nav-link" href="#">Help</a>
               </li>
               <li class="nav-item dropup">
                   <div class="form-group">
                       <div class="form-control-wrap">
                           <select class="form-select" id="theme-select">
                               <option value="light">Light Mode</option>
                               <option value="dark">Dark Mode</option>
                           </select>
                       </div>
                   </div>
               </li>
           </ul><!-- .nav -->
       </div>
       <div class="mt-3">
           <p>&copy; <?php echo date('Y'); ?> {{ env('APP_NAME') }}. All Rights Reserved.</p>
       </div>
   </div><!-- .nk-block -->
   <script src="{{ asset('assets/js/bundle.js') }}"></script>
   <script src="{{ asset('assets/js/scripts.js') }}"></script>
   @livewireScripts
   @stack('js')
   <script>
       const themeSelect=document.getElementById('theme-select');
       const body=document.body;

       // Check for saved theme in localStorage
       const savedTheme=localStorage.getItem('theme');

       // Apply saved theme if it exists
       if (savedTheme) {
           body.classList.toggle('dark-mode', savedTheme === 'dark');
           themeSelect.value=savedTheme;
       }

       // Function to update theme
       function updateTheme(theme) {
           body.classList.toggle('dark-mode', theme === 'dark');
           localStorage.setItem('theme', theme);
       }

       // Event listener for theme selection change
       themeSelect.addEventListener('change', function() {
               const selectedTheme=this.value;
               updateTheme(selectedTheme);
           });
   </script>
