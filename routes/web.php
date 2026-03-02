<?php

use illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function() {
    return Inertia::render('Client/Login');
})->name('login');

?>
