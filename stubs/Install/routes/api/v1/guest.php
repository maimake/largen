<?php
use Illuminate\Http\Request;

Route::namespace('Api\V1')->group(function () {

    Route::get('/guest', function (Request $request) {
        return 'guest v1';
    });

    Route::get('/home/guest', 'HomeController@guest');
});

