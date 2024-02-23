   @php
       $user = auth()->user();
       $userRole = $user->role;
       $campaigns = $user->campaigns;

   @endphp <!-- wrap @s -->
   <div class="nk-wrap " wire:ignore>
       <!-- main header @s -->
       <div class="nk-header nk-header-fixed is-light">
           <div class="container-fluid">
               <div class="nk-header-wrap">
                   <div class="nk-menu-trigger d-xl-none ms-n1">
                       <a href="#" class="nk-nav-toggle nk-quick-nav-icon" data-target="sidebarMenu"><em
                               class="icon ni ni-menu"></em></a>
                   </div>
                   <div class="nk-header-brand d-xl-none">
                       <a wire:navigate href="#" class="logo-link">
                           <img class="logo-light logo-img" src="{{ asset('assets/images/logo-dark.png') }}"
                               srcset="{{ asset('assets/images/favicon.png 2x') }}" alt="logo">
                           <img class="logo-dark logo-img" src="{{ asset('assets/images/logo-dark.png') }}"
                               srcset="{{ asset('assets/images/logo-dark.png') }}" alt="logo-dark">
                       </a>
                   </div><!-- .nk-header-brand -->
                   <div class="nk-header-search ms-3 ms-xl-0">
                       <em class="icon ni ni-clock"></em>
                       <span id="current-time"></span>
                   </div><!-- .nk-header-news -->
                   <div class="nk-header-tools" wire:ignore>
                       <ul class="nk-quick-nav">

                           <li class="dropdown notification-dropdown">
                               <a href="#" class="dropdown-toggle nk-quick-nav-icon" data-bs-toggle="dropdown">
                                   <div class="icon-status icon-status-info"><em class="icon ni ni-bell"></em></div>
                               </a>
                               <div class="dropdown-menu dropdown-menu-xl dropdown-menu-end">
                                   <div class="dropdown-head">
                                       <span class="sub-title nk-dropdown-title">Notifications</span>
                                       <a href="#">Mark All as Read</a>
                                   </div>
                                   <div class="dropdown-body">
                                       <div class="nk-notification">
                                           <div class="nk-notification-item dropdown-inner">
                                               <div class="nk-notification-icon">
                                                   <em
                                                       class="icon icon-circle bg-warning-dim ni ni-curve-down-right"></em>
                                               </div>
                                               <div class="nk-notification-content">
                                                   <div class="nk-notification-text">You have requested to
                                                       <span>Widthdrawl</span>
                                                   </div>
                                                   <div class="nk-notification-time">2 hrs ago</div>
                                               </div>
                                           </div>

                                       </div><!-- .nk-notification -->
                                   </div><!-- .nk-dropdown-body -->
                                   <div class="dropdown-foot center">
                                       <a wire:navigate href="#">View All</a>
                                   </div>
                               </div>
                           </li>

                           <li class="dropdown user-dropdown">
                               <a href="#" class="dropdown-toggle me-n1" data-bs-toggle="dropdown">
                                   <div class="user-toggle">
                                       <div class="user-avatar sm">
                                           <img src="{{ $user->avatar }}">
                                       </div>
                                       <div class="user-info d-none d-xl-block">
                                           <div class="user-status user-status-unverified">
                                               {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $user->role)) }}
                                           </div>
                                           <div class="user-name dropdown-indicator">{{ $user->name }}</div>
                                       </div>
                                   </div>
                               </a>
                               <div class="dropdown-menu dropdown-menu-md dropdown-menu-end">
                                   <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                       <div class="user-card">
                                           <div class="user-avatar">
                                               <img src="{{ $user->avatar }}">
                                           </div>
                                           <div class="user-info">
                                               <span class="lead-text">{{ $user->name }}</span>
                                               <span class="sub-text">{{ $user->user_id }}</span>
                                           </div>
                                       </div>
                                   </div>
                                   <div class="dropdown-inner">
                                       <ul class="link-list">
                                           <li><a wire:navigate href="{{ route('manager.profile') }}"><em
                                                       class="icon ni ni-user-alt"></em><span>View Profile</span></a>
                                           </li>
                                           <li><a wire:navigate href="{{ route('manager.profile-settings') }}"><em
                                                       class="icon ni ni-setting-alt"></em><span>Account
                                                       Setting</span></a></li>
                                           <li><a wire:navigate href="{{ route('manager.activities') }}"><em
                                                       class="icon ni ni-activity-alt"></em><span>Login
                                                       Activity</span></a></li>
                                           <li><a class="dark-switch" href="#"><em
                                                       class="icon ni ni-moon"></em><span>Dark Mode</span></a></li>
                                       </ul>
                                   </div>
                                   <div class="dropdown-inner">
                                       <ul class="link-list">
                                           <li>
                                               <a  href="#" class="nav-author__signout text-danger"
                                                   data-bs-toggle="modal" data-bs-target="#user-logout-confirmed"><em
                                                       class="icon ni ni-signout"></em><span>Sign
                                                       out</span></a>
                                           </li>
                                       </ul>
                                   </div>
                               </div>
                           </li>
                       </ul>
                   </div>
               </div><!-- .nk-header-wrap -->
           </div><!-- .container-fliud -->
       </div>
       <!-- main header @e -->

       @push('js')
           <script>
               // Function to update the current time every second
               function updateTime() {
                   var currentTimeElement = document.getElementById('current-time');
                   var now = new Date();

                   // Format the time as hh:mm:ss AM/PM
                   var hours = now.getHours();
                   var minutes = now.getMinutes().toString().padStart(2, '0');
                   var seconds = now.getSeconds().toString().padStart(2, '0');
                   var ampm = hours >= 12 ? 'PM' : 'AM';

                   // Convert 24-hour time to 12-hour time
                   hours = hours % 12;
                   hours = hours ? hours : 12; // Set 12 for midnight

                   // Display the time
                   var formattedTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
                   currentTimeElement.textContent = formattedTime;
               }

               // Initial call to set the time immediately
               updateTime();

               // Update the time every second
               setInterval(updateTime, 1000);
           </script>
       @endpush
