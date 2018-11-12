<?php
use Illuminate\Http\Request;

Route::namespace('Api\V1')->middleware('auth:api')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/home/user', 'HomeController@user');
});

