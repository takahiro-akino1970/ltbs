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
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
