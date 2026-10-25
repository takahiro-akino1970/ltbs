<?php

$portal = config('app.portal');

if (! in_array($portal, ['order', 'admin'], true)) {
    throw new RuntimeException('APP_PORTAL must be set to order or admin.');
}

require __DIR__."/{$portal}.php";
