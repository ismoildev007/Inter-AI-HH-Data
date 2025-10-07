<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['track.visits'])->get('/', function () {
    return view('welcome');
});
