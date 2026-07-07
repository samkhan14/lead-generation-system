<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="layout-menu-fixed layout-compact"
    data-assets-path="{{ asset('vendor/materio') }}/"
    data-template="vertical-menu-template-free"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

        <title inertia>{{ config('app.name', 'SalesIntel') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('vendor/materio/img/favicon/favicon.ico') }}" />

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="{{ asset('vendor/materio/fonts/iconify-icons.css') }}" />

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
