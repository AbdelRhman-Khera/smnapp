<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    echo'Samnan';
    // return view('welcome');
});
Route::get('/test', function () {
   echo 'test';
    // return view('welcome');
});
Route::get('/clear-cache', function () {
    // $exitCode = Artisan::call('config:cache');
    // $exitCode = Artisan::call('cache:clear');
    // $exitCode = Artisan::call('storage:link');

    Artisan::call('db:seed', [
        '--class' => 'SlotSeeder',
    ]);

    return 'DONE';
});
