<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function ()
{
    Route::get('/oauth-user', function (Request $request) {
        return $request->user();
    });

    Route::get('testscope1', function (){
        return ['scope' => 'scope1'];
    })->middleware('scopes:scope1');

    Route::get('testscope2', function (){
        return ['scope' => 'scope2'];
    })->middleware('scopes:scope2');

    Route::get('testscopeboth', function (){
        return ['scope' => ['scope1', 'scope2']];
    })->middleware('scopes:scope1,scope2');

    Route::get('testscopeany', function (Request $request){
        $scope = ($request->user()->tokenCan('scope1')) ? 'scope1' : 'scope2';
        return ['scope' => $scope];
    })->middleware('scope:scope1,scope2');
});


Route::middleware('client.credentials')->group(function () {
    Route::get('/oauth-test', function (Request $request) {
        return response()->json(['test'=>123]);
    });
});
