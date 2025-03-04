<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return [config('app.name') => 'Dezynation'];
});

Route::get('test', function () {
    return openssl_decrypt(
        base64_decode("2VInlsGFjRZ9+GR/7PrAYP2mzEPK2HyabIvyZF7EaBr3K0eOeJpszC5OnIiFVFUwPTs+yi7hfkkNp6YQWPuDeYrWEKHyNc2NdEbr+/sW6+DnhPTKi/5mAeWXXLEp5rSnolH3N/+cuQ1Cao3/QMceOw=="),
        'AES-256-CBC',
        'd0143bc26b3d1c9a4f0d254bf7527268',
        OPENSSL_RAW_DATA,
        'CYNfA9Ru9qUTbYta'
    );
});

require __DIR__ . '/auth.php';
