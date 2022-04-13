<?php

namespace Themearabia\LaravelInstaller\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use File;

class LicenseManager
{
    public $product_id;
    public $product_client;
    public $product_license;
    private $session_id;

    public $verification_period;
	private $api_url;
	private $api_key;
	private $license_file;
	private $extensions_file_json;

	private $current_version;
	private $current_path;
	private $root_path;
	private $api_debug = false;
	private $show_update_progress = false;

    public function __construct()
	{
		$this->api_url = 'https://themearabia.net/api/envato/';
		$this->api_key = 'T49QILVQAFD0NSX02XOV74CJSZYQ27ERFHCXNIDZFOHXUJF';
		$this->root_path = storage_path('upgrade');
        $this->verification_period = '1';
        $this->product_id = config('installer.project.product_id');
        $this->session_id = 'hGn7JpfCB5nYAqVp';
		$this->extensions_file_json = storage_path('extensions');
	}

    public function license_file($product_id)
    {
        return storage_path('license-'.$product_id);
    }

    public function get_trans($string, $replace = [])
    {
        return trans('installer_messages.licenses.'.$string, $replace);
    }
    
    public function call_api($method, $route, $data, $authorization = false)
    {
        $url = $this->api_url . $route;
        $response = Http::withHeaders($this->curlopt_http_header())->post($url, $data);
        if($response->successful()) {
            return $response->json();
        }
        else {
            return ['status' => false, 'code' => '401', 'message' => $this->get_trans('invalid_response')];
        }
    }

    private function curlopt_http_header($authorization = false)
	{
		$header['MEGA-API-LANG'] = 'english';
		$header['MEGA-API-KEY'] = $this->api_key;
		$header['MEGA-API-URL'] = url('/');
		$header['MEGA-API-IP'] = GetRealIp();
		if ($authorization) {
			$header['Authorization:Bearer'] = $authorization;
		}

		return $header;
	}

    public function check_connection($json = false)
	{
		$data_array = [];
		$response = $this->call_api('POST', 'check_connection', $data_array);
		return $response['status'];
	}

    public function activate_license_item()
    {
        $check_connection = $this->check_connection();
		if($check_connection['status']) {
			return $this->activate_license($this->product_license, $this->product_client);
		}
		else {
			return $check_connection;
		}
    }

    public function verify_license_status($request, $time_based_check = false)
    {
        $response = $this->verify_license($request, $time_based_check);
        return $response['status'];
    }

    public function verify_license($request, $time_based_check = false)
	{
		$this->session_id = md5($this->product_id);
        $data_array =  [];
		$response = [
            'status' => true, 
            'message' => $this->get_trans('verified_response')
        ];
		$this->license_file = $this->license_file($this->product_id);
		if (!empty($this->product_license) && !empty($this->product_client)) {
			$data_array = [
                'product_id' => $this->product_id,
				'license_file' => null,
				'license_code' => $this->product_license,
				'client_name' => $this->product_client
            ];
		} 
        else {
			if (is_file($this->license_file)) {
                $data_array = [
                    'product_id' => $this->product_id,
                    'license_file' => file_get_contents($this->license_file),
                    'license_code' => null,
                    'client_name' => null
                ];
			}
            else {
                $data_array = [
                    'product_id' => $this->product_id,
                    'license_file' => null,
                    'license_code' => null,
                    'client_name' => null
                ];
            }
		}
		if ($time_based_check && $this->verification_period > 0) {
			ob_start();
			if (session_status() == PHP_SESSION_NONE) {
				session_start();
			}
			$today = date('d-m-Y');
            $type_text = $this->verification_period . ' days';
			if (empty($_SESSION[$this->session_id])) {
				$_SESSION[$this->session_id] = '00-00-0000';
			}
			if (strtotime($today) >= strtotime($_SESSION[$this->session_id])) {
				$response = $this->call_api('POST', 'verify_license', $data_array);
				if ($response['status'] == true) {
					$tomo = date('d-m-Y', strtotime($today . ' + ' . $type_text));
					$_SESSION[$this->session_id] = $tomo;
				}
			}
			ob_end_clean();
		} else {
			$response = $this->call_api('POST', 'verify_license', $data_array);
		}

		return $response;
	}
	
    public function activate_license($create_lic = true)
	{
		$data_array = [
            'product_id' => $this->product_id,
            'license_code' => $this->product_license,
            'client_name' => $this->product_client
        ];
        $this->license_file = $this->license_file($this->product_id);
		$response = $this->call_api('POST', 'activate_license', $data_array);
		if (!empty($create_lic)) {
			if ($response['status']) {
				$licfile = trim($response['lic_response']);
				file_put_contents($this->license_file, $licfile, LOCK_EX);
			} else {
				@chmod($this->license_file, 0777);
				if (is_writeable($this->license_file)) {
					unlink($this->license_file);
				}
			}
		}
		return $response;
	}

	public function get_extensions($time_based_check = false)
	{
		$data_array = ['product_id' => $this->product_id];
		$response = [
            'status' => true, 
            'message' => $this->get_trans('verified_response')
        ];
		
		if($time_based_check){
			$response = $this->call_api('POST', 'get_extensions', $data_array);
			if($response['status'] and isset($response['extensions']))
			{
				file_put_contents($this->extensions_file_json, json_encode($response['extensions']), LOCK_EX);
			}
		}
		
		if(file_exists($this->extensions_file_json))
		{
			$extensions = json_decode(File::get($this->extensions_file_json), true);
			return $extensions;
		}
		else {
			return [];
		}
	}

	public function get_extension_data($slug)
	{
		$extensions = json_decode(File::get($this->extensions_file_json), true);
		if(isset($extensions[$slug])){
			return $extensions[$slug];
		}
		else {
			return false;
		}
	}

	public function verify_extensions()
	{
		$extensions = $this->get_extensions(true);
		foreach($extensions as $key => $extension) {
			if($extension['license'] == 'paid' and $extension['itemid']) {
				$verify = $this->verify_extension($extension['itemid']);
			}
		}

	}

	public function license_extensions($request)
	{
		if(file_exists($this->extensions_file_json)){
			$extensions = json_decode(File::get($this->extensions_file_json), true);
			$start_extensions = maybe_unserialize(get_option('start_extensions', []));
		}
		else{
			$extensions = [];
			$start_extensions = [];
		}
		foreach($extensions as $key => $extension) {
			if($extension['license'] == 'paid') {
				$this->product_id = $extension['itemid'];
				$verify = $this->verify_license($request, true);
				if($verify['status'] == false){
					$start_extensions = array_diff( $start_extensions, (array) $key );
				}
			}
		}
		update_option('start_extensions', maybe_serialize($start_extensions));
	}

	public function usnfs($dir) {
		foreach(glob($dir . '/*') as $file) {
			if(is_dir($file)){
				$this->usnfs($file);
			}
			else{
				@unlink($file);
			}
		}
		@rmdir($dir);
	}
}