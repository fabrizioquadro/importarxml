<?php

$namespace = "fabrizioquadro\importarxml\Http\Controllers";

Route::group(['namespace' => $namespace, 'prefix' => 'importarxml'], function(){
    Route::get('/', 'ImportarXmlController@index')->name('importsxml.index');
    Route::get('/add', 'ImportarXmlController@add')->name('importsxml.add');
    Route::post('/store', 'ImportarXmlController@store')->name('importsxml.store');
    Route::get('/view/{id?}', 'ImportarXmlController@view')->name('importsxml.view');
});
