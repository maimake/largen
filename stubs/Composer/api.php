<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::namespace('Api')->group(function () {

    Route::group(['middleware' => ['client.credentials']], function() {
        Route::get('/test', function (Request $request) {
            return response()->json(['test'=>123]);
        });
    });

    Route::get('register', 'AuthController@index');

    Route::middleware(['auth:api'])->group(function (){

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get('users', function (){
            return [
                ['name'=>'user1',],
                ['name'=>'user2',],
            ];
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
});
