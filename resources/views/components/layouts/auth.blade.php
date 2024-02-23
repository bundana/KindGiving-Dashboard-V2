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
        <title>{{ $title ?? 'Auth' }} | {{ env('APP_NAME') }}</title>
        <!-- StyleSheets  -->
        <link rel="stylesheet" href="{{ asset('assets/css/dashlite.css') }}">
        <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
        <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/fontawesome/css/all.css') }}">
        @livewireStyles
        @stack('css')
    </head>

    <body class="nk-body bg-white npc-default pg-auth">
        <div class="nk-app-root">
            <div class="nk-main ">
                @yield('content2')
            </div>
        </div>
    </body>

</html>
