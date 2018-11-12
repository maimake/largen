<?php
use Illuminate\Http\Request;

Route::get('/guest', function (Request $request) {
    return 'guest v1';
});
