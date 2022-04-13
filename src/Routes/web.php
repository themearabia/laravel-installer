<?php

Route::group(['prefix' => 'install', 'as' => 'LaravelInstaller::', 'namespace' => 'Themearabia\LaravelInstaller\Controllers', 'middleware' => ['web', 'install']], function () {
    Route::get('/', [
        'as' => 'welcome',
        'uses' => 'WelcomeController@welcome',
    ]);

    Route::get('environment', [
        'as' => 'environment',
        'uses' => 'EnvironmentController@environmentMenu',
    ]);
  
    Route::post('environment/saveWizard', [
        'as' => 'environmentSaveWizard',
        'uses' => 'EnvironmentController@saveWizard',
    ]);

    Route::get('requirements', [
        'as' => 'requirements',
        'uses' => 'RequirementsController@requirements',
    ]);

    Route::get('permissions', [
        'as' => 'permissions',
        'uses' => 'PermissionsController@permissions',
    ]);

    Route::get('database', [
        'as' => 'database',
        'uses' => 'DatabaseController@database',
    ]);

    Route::get('admin', [
        'as' => 'admin',
        'uses' => 'AdminController@admin',
    ]);
    
    Route::post('admin/saveWizard', [
        'as' => 'adminSaveWizard',
        'uses' => 'AdminController@saveWizard',
    ]);

    Route::get('final', [
        'as' => 'final',
        'uses' => 'FinalController@finish',
    ]);
});

Route::group(['prefix' => 'update', 'as' => 'LaravelUpdater::', 'namespace' => 'Themearabia\LaravelInstaller\Controllers', 'middleware' => 'web'], function () {
    Route::group(['middleware' => ['update', 'admin']], function () {
        Route::get('/', [
            'as' => 'welcome',
            'uses' => 'UpdateController@welcome',
        ]);

        Route::get('overview', [
            'as' => 'overview',
            'uses' => 'UpdateController@overview',
        ]);

        Route::get('database', [
            'as' => 'database',
            'uses' => 'UpdateController@database',
        ]);
    });

    // This needs to be out of the middleware because right after the migration has been
    // run, the middleware sends a 404.
    Route::get('final', [
        'as' => 'final',
        'uses' => 'UpdateController@finish',
    ]);
});

Route::group(['prefix' => 'license', 'as' => 'LaravelInstaller::', 'namespace' => 'Themearabia\LaravelInstaller\Controllers', 'middleware' => ['web']], function () {

    Route::get('verify/{itemid?}', [
        'as' => 'verify',
        'uses' => 'LicenseController@index_verify',
    ]);
  
    Route::post('verify/activatorWizard', [
        'as' => 'activatorWizard',
        'uses' => 'LicenseController@activatorWizard',
    ]);

});

