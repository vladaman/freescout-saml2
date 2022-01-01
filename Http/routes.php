<?php

Route::group(['middleware' => 'web',
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\Saml\Http\Controllers'], function() {

	Route::get('/saml_login', 'SamlController@login')->name('saml_login');
});

Route::group([
	'middleware' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ],
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\Saml\Http\Controllers'], function() {

	Route::post('/saml_acs', 'SamlController@callback')->name('saml_acs');

});
