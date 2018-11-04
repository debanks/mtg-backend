<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
Route::group(['middleware' => ['api']], function() {
    Route::get('api/home', ['uses' => 'CardController@home']);
    Route::get('api/search', ['uses' => 'CardController@search']);
    Route::get('api/draft', ['uses' => 'CardController@draft']);
    Route::get('api/simulate/{deckId}', ['uses' => 'CardController@simulae']);
});
