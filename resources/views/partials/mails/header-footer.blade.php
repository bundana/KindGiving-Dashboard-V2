<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $subject ?: Auth }} | {{ env('APP_NAME') }}</title>
        <!-- StyleSheets  -->
        <link rel="stylesheet" href="{{ asset('assets/css/dashlite.css') }}">
        <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
        <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/fontawesome/css/all.css') }}">
    </head>

    <body>
        @yield('content')
    </body>

</html>
