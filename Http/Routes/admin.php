<?php
Route::get('/', 'AdminController@index');
Route::post('/import', 'PirepsController@import');