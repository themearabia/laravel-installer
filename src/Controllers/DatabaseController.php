<?php

namespace Themearabia\LaravelInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Themearabia\LaravelInstaller\Helpers\DatabaseManager;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * Migrate and seed the database.
     *
     * @return \Illuminate\View\View
     */
    public function database(Request $request)
    {
        $response  = $this->databaseManager->migrateAndSeed();

        $dboptions = [
            'sitename'          => env('APP_NAME'),
            'siteurl'           => env('APP_URL'),
            'content_editor'    => 'articleeditor',
            'language'          => (env('APP_LANG') == 'ar')? 'ar' : 'en',
            'direction'         => (env('APP_LANG') == 'ar')? 'rtl' : 'ltr',
            'admin_language'    => (env('APP_LANG') == 'ar')? 'ar' : 'en',
            'theme'             => 'default',
            'time_format'       => 'g:i a',
            'date_format'       => 'F j, Y',
            'webmaster_email'   => 'webmaster@email.com',
            'confirm_pincode'   => '1',
            'script_version'    => SCRIPT_VERSION,
        ];

        foreach($dboptions as $key => $val) {
            update_option($key, $val);
        }

        foreach(default_options() as $key => $val) {
            update_option($key, maybe_serialize($val));
        }

        DB::table('language')->insert(default_language());
        
        return redirect()->route('LaravelInstaller::admin')
                         ->with(['message' => $response]);
    }
}
