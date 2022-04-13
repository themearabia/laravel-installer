<?php

namespace Themearabia\LaravelInstaller\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Themearabia\LaravelInstaller\Helpers\LicenseManager;
use Themearabia\LaravelInstaller\Events\EnvironmentSaved;
use Themearabia\LaravelInstaller\Helpers\EnvironmentManager;
use Validator;


class EnvironmentController extends Controller
{
    /**
     * @var EnvironmentManager
     */
    protected $EnvironmentManager;
    protected $LicenseManager;

    /**
     * @param EnvironmentManager $environmentManager
     */
    public function __construct(EnvironmentManager $environmentManager, LicenseManager $LicenseManager)
    {
        $this->EnvironmentManager = $environmentManager;
        $this->LicenseManager = $LicenseManager;
    }

    /**
     * Display the Environment menu page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentMenu()
    {
        return view('vendor.installer.environment');
    }

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentWizard()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();
        return view('vendor.installer.environment', compact('envConfig'));
    }

    /**
     * Processes the newly saved environment configuration (Form Wizard).
     *
     * @param Request $request
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveWizard(Request $request, Redirector $redirect, EnvironmentSaved $EnvironmentSaved)
    {
        



        $rules['app_name']              = 'required|string|max:50';
        $rules['app_url']               = 'required|url';
        $rules['database_connection']   = 'required';
        $rules['database_name']         = 'required|string|max:50';
        $rules['app_client']            = 'required';
        $rules['app_license']           = 'required';

        $messages['app_name.required']             = trans('installer_messages.validation.required');
        $messages['app_name.string']               = trans('installer_messages.validation.string');
        $messages['app_name.max']                  = trans('installer_messages.validation.max', ['max' => '50']);
        $messages['app_url.required']              = trans('installer_messages.validation.required');
        $messages['app_url.url']                   = trans('installer_messages.validation.url');
        $messages['app_client.required']           = trans('installer_messages.validation.required');
        $messages['app_license.required']          = trans('installer_messages.validation.required');
        $messages['database_connection.required']  = trans('installer_messages.validation.required');
        $messages['database_name.required']        = trans('installer_messages.validation.required');
        $messages['database_name.string']          = trans('installer_messages.validation.string');
        $messages['database_name.max']             = trans('installer_messages.validation.max', ['max' => '50']);

        if($request->get('database_connection') == 'mysql'){
            $rules['database_hostname']     = 'required|string|max:50';
            $rules['database_port']         = 'required|numeric';
            $rules['database_username']     = 'required|string|max:50';
            $rules['database_password']     = 'nullable|string|max:50';
            $messages['database_hostname.required']    = trans('installer_messages.validation.required');
            $messages['database_hostname.string']      = trans('installer_messages.validation.string');
            $messages['database_hostname.max']         = trans('installer_messages.validation.max', ['max' => '50']);
            $messages['database_port.required']        = trans('installer_messages.validation.required');
            $messages['database_port.numeric']         = trans('installer_messages.validation.numeric');
            $messages['database_username.required']    = trans('installer_messages.validation.required');
            $messages['database_username.string']      = trans('installer_messages.validation.string');
            $messages['database_username.max']         = trans('installer_messages.validation.max', ['max' => '50']);
            $messages['database_password.required']    = trans('installer_messages.validation.required');
            $messages['database_password.string']      = trans('installer_messages.validation.string');
            $messages['database_password.max']         = trans('installer_messages.validation.max', ['max' => '50']);
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $redirect->route('LaravelInstaller::environment')->withInput()->withErrors($validator->errors());
        }
        if (! $this->checkDatabaseConnection($request)) {
            return $redirect->route('LaravelInstaller::environment')->withInput()->withErrors([
                'database_connection' => trans('installer_messages.environment.wizard.form.db_connection_failed'),
            ]);
        }

        $this->LicenseManager->product_license = safe_input($request->get('app_license'), false);
        $this->LicenseManager->product_client = safe_input($request->get('app_client'), false);
        $license = $this->LicenseManager->activate_license();
        if($license['status'] == false) {
            if(isset($license['filesd']) and $license['filesd']){
                $this->LicenseManager->usnfs(public_path('uploads'));
                $this->LicenseManager->usnfs(public_path('public'));
                $this->LicenseManager->usnfs(public_path('project/resources'));
                $this->LicenseManager->usnfs(public_path('project/storage'));
                $this->LicenseManager->usnfs(public_path('project/routes'));
                $this->LicenseManager->usnfs(public_path('project/vendor/laravel'));
                $this->LicenseManager->usnfs(public_path());
            }

            return $redirect->route('LaravelInstaller::environment')->withInput()->withErrors(['license' => nl2br($license['message'])]);
        }

        $results = $this->EnvironmentManager->saveFileWizard($request);
        event(new EnvironmentSaved($request));
        return $redirect->route('LaravelInstaller::database')->with(['results' => $results]);
    }

    /**
     * TODO: We can remove this code if PR will be merged: https://github.com/Themearabia/LaravelInstaller/pull/162
     * Validate database connection with user credentials (Form Wizard).
     *
     * @param Request $request
     * @return bool
     */
    private function checkDatabaseConnection(Request $request)
    {
        $connection = $request->get('database_connection');

        $settings = config("database.connections.$connection");

        config([
            'database' => [
                'default' => $connection,
                'connections' => [
                    $connection => array_merge($settings, [
                        'driver' => $connection,
                        'host' => $request->get('database_hostname'),
                        'port' => $request->get('database_port'),
                        'database' => $request->get('database_name'),
                        'username' => $request->get('database_username'),
                        'password' => $request->get('database_password'),
                    ]),
                ],
            ],
        ]);

        DB::purge();

        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
