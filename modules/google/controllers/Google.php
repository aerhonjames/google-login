<?php defined('BASEPATH') OR exit('No direct script access allowed');

use CI\Models\Customer;

class Google extends Public_Controller{

    function __construct(){
        parent::__construct();

        if($this->customer_account->has_active_session()) show_404();

        $this->load->library(['google_login']);
        $this->google_login->boot();
    }

    function login(){

        $redirect = $this->google_login->generate_login_url();
        
        redirect($redirect);
    }

    function callback(){

        $response_code = $this->input->get('code');

        if(!$response_code) show_404();
        
        $token = $this->google_login->get_client_access_token($response_code);
        $access_token = $token['access_token'];

        if(!$token){

            if(is_mobile()) redirect('mobile/login');
            redirect('login'); // redirect to homepage when access denied
        }

        $google_user_info = $this->google_login->get_client_info($token);

        // dd($google_user_info);
        $customer = new Customer;

        $_customer = $customer // check with google id 
            ->referenceId($google_user_info->id)
            ->first();
        
        if($_customer instanceOf Customer AND $_customer->exists){
            $_customer->reference_token = $access_token; // update access token

            if($_customer->isSuspended()){
                $this->message->set_error('Your account is currently suspended.');

                if(is_mobile()) redirect('mobile/login');
                redirect('login');
            }
            else{
                if($_customer->save()){
                    $this->customer_account->login($_customer);

                    if(is_mobile()) redirect('mobile/products/all');
                    redirect('products/all');
                }
            }
        }
        else{
            $_customer = $customer
                ->where('email', '=', $google_user_info->email)
                ->first();
            
            if($_customer instanceOf Customer AND $_customer->exists){ // if exists with email address update details

                if(!$_customer->reference_id){
                    $_customer->reference_id = $google_user_info->id;
                    $_customer->reference_token = $access_token;
                }

                if($_customer->isInactive()){
                    $_customer->status = 'active';
                    $_customer->resetRequestKey();
                }

                if($_customer->isSuspended()){
                    $this->message->set_error('Your account is currently suspended.');

                    if(is_mobile()) redirect('mobile/login');
                    redirect('login');
                }
                else{
                    if($_customer->save()){
                        $this->customer_account->login($_customer);

                        if(is_mobile()) redirect('mobile/products/all');
                        redirect('products/all');
                    }
                }
            }
            else{
                // Create new account
                $customer->email = $google_user_info->email;
                $customer->first_name = $google_user_info->givenName;
                $customer->last_name = $google_user_info->familyName;

                $customer->reference_id = $google_user_info->id;
                $customer->reference_token = $access_token;
                $customer->registration_type = 'google';
                $customer->status = 'active';
                $customer->password = random_string('alnum', 8);
                $customer->generateRequestKey();
                $customer->generateAccessKey();

                if($customer->save()){
                    $this->customer_account->login($customer);
                    // Send registration success
                    $this->notification
                        ->success_google_registration($customer)
                        ->send();

                    if(is_mobile()) redirect('mobile/products/all');
                    redirect('products/all');
                }
            }
        }
    }
}