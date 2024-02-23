<!DOCTYPE html>
<html lang="zxx" class="js">

<head>
    <meta charset="utf-8">
    <meta name="author" content="Softnio">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description"
          content="A powerful and conceptual apps base dashboard template that especially build for developers and programmers.">
    <!-- Fav Icon  -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}">
    <!-- Page Title  -->
    <title>Dashboard | {{ env('APP_NAME') }} </title>
    <!-- StyleSheets  -->
    <link rel="stylesheet" href="{{ asset('assets/css/dashlite.css') }}">
    <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
    <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/fontawesome/css/all.css') }}">
    @livewireStyles
    @stack('css')
</head>

<body class="nk-body bg-lighter npc-general has-sidebar ">

<div class="nk-app-root"  >
    <div class="nk-main ">
        @include('partials.menus.sidebar')
        @include('partials.menus.top-header')
        @yield('content')
        @include('partials.modals.logout')
        @include('partials.menus.footer')
    </div>
    @yield('content2')
</div>

</body>
<script src="{{ asset('assets/js/bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.js') }}"></script>
<script src="{{ asset('assets/js/charts/chart-lms.js') }}"></script>
@livewireScripts
<script>
    $(document).ready(function () {
        @if (session('success'))
        toastr.clear();
        NioApp.Toast('<h5>Success</h5><p>{{ session('success') }}</p>', 'success', {
            position: 'top-right'
        });
        @elseif (session('error'))
        toastr.clear();
        NioApp.Toast('<h5>Opps</h5><p>{{ session('error') }}</p>', 'error', {
            position: 'top-right'
        });
        @endif
    });
</script>
@stack('js')
</html>
