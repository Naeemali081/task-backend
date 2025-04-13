<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Auth::routes(['verify' => true]);

Route::get('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return "Optimization cleared!";
});
Route::get('/email/verify', function () {
    return view('auth.verify'); 
})->middleware('auth')->name('verification.verify');
Route::get('/password/reset', function () {
    return view('auth.reset'); 
})->middleware('auth')->name('password.reset');
?>
