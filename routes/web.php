<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/about', function () {
    return view('about');
});


Auth::routes();

Route::get('/home', 'HomeController@index');

Route::get('/random', 'ViewDocumentController@random');

Route::get('/upload', 'UploadController@index');

Route::post('/upload', 'UploadController@submitted');

Route::get('/document/{id}', 'ViewDocumentController@index');

Route::get('/browse', 'BrowseDocumentsController@index');

Route::get('/logout', 'HomeController@logout');

