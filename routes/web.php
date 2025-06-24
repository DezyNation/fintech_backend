<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [config('app.name') => 'India'];
});

require __DIR__ . '/auth.php';
