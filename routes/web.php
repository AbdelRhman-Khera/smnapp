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
