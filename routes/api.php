<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require base_path('routes/v1.php');
});

Route::prefix('w1')->group(function () {
    require base_path('routes/w1.php');
});
