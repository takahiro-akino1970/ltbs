<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $portal = config('app.portal');
            $assets = match ($portal) {
                'order' => ['resources/js/order.ts', 'resources/scss/order.scss'],
                'admin' => ['resources/js/admin.ts', 'resources/scss/admin.scss'],
                default => throw new RuntimeException("Unsupported APP_PORTAL: {$portal}"),
            };
        @endphp
        @vite($assets)
        <title>{{ config('app.name') }}</title>
    </head>
    <body>
        <div
            id="app"
            data-app-name="{{ config('app.name') }}"
            data-portal="{{ config('app.portal') }}"
            data-page="{{ $page }}"
        ></div>
        <script id="page-props" type="application/json">@json((object) $props, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    </body>
</html>
