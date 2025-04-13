<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return "Optimization cleared!";
});
?>
