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
    Route::get('api/simulate/{deckId}', ['uses' => 'DeckController@simulate']);
    Route::get('api/decks', ['uses' => 'DeckController@index']);
    Route::get('api/decks/{deckId}', ['uses' => 'DeckController@get']);
    Route::post('api/decks', ['uses' => 'DeckController@insert']);
    Route::post('api/decks/{id}', ['uses' => 'DeckController@update']);
});
