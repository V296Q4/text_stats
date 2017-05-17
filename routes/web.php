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

Route::get('/about', function () {
    return view('about');
});

Route::get('/stoplist', function () {
    return view('stoplist');
});

Auth::routes();

Route::get('/', 'HomeController@index');

Route::get('/home', 'HomeController@index');

Route::get('/random', 'DocumentController@random');

Route::get('/analyze', 'UploadController@index');

Route::post('/analyze_submitted', 'UploadController@submitted');

Route::get('/document/{id}', 'DocumentController@index');

Route::get('/document', 'DocumentController@guest_document');

Route::get('/document/{id}/raw', 'DocumentController@viewRaw');

Route::get('/delete_document/{id}', 'DocumentController@delete_document');

Route::get('/browse', 'DocumentController@browse_documents');

Route::get('/owned_documents', 'DocumentController@browse_owned_documents');

Route::get('/collections', 'CollectionController@view_owned_collections');

Route::get('/collection/{id}', 'CollectionController@view_collection');

Route::get('/create_collection', 'CollectionController@create_collection');

Route::post('/create_collection_submitted', 'CollectionController@create_submitted');

Route::get('/collection/{collection_id}/remove/{document_id}', 'CollectionController@remove_from_collection');

Route::post('/document/add_to_collection', 'CollectionController@add_to_collection');

Route::get('/delete_collection/{id}', 'CollectionController@delete_collection');

Route::get('/logout', 'HomeController@logout');
