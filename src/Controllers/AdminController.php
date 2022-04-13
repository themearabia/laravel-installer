<?php

namespace Themearabia\LaravelInstaller\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Validator;

class AdminController extends Controller
{

    public function __construct()
    {
        
    }

    /**
     * Display the admin page.
     *
     * @return \Illuminate\View\View
     */
    public function admin()
    {
        return view('vendor.installer.admin');
    }

    //saveWizard
    /**
     * Processes the newly saved admin configuration (Form Wizard).
     *
     * @param Request $request
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveWizard(Request $request, Redirector $redirect)
    {
        $rules = config('installer.admin.form.rules');
        $messages = [
            'admin_username.required'   => trans('installer_messages.validation.required'),
            'admin_email.required'      => trans('installer_messages.validation.required'),
            'admin_email.email'         => trans('installer_messages.validation.email'),
            'admin_password.required'   => trans('installer_messages.validation.required'),
            'admin_pincode.required'    => trans('installer_messages.validation.required'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return $redirect->route('LaravelInstaller::admin')->withInput()->withErrors($validator->errors());
        }

        $user_id = DB::table(USERS_TABLE)->insertGetId([
            'username'          => safe_input($request->get('admin_username'), false),
            'email'             => safe_input($request->get('admin_email'), false),
            'password'          => Hash::make($request->get('admin_password')),
            'pincode'           => md5($request->get('admin_pincode')),
            'userlevel'         => 'admin',
            'status'            => '1',
            'remember_token'    => NULL,
            'active_key'        => NULL,
            'updated_at'        => now(),
            'created_at'        => now(),
        ]);

        update_user_meta('admin_language', 'en', $user_id);

        return $redirect->route('LaravelInstaller::final')
                        ->with(['results' => 'results']);
    }
}
