<?php defined('BASEPATH') or exit('No direct script access allowed');

class Google_login{

    protected $client;
    protected $ci;
    
    function __construct(){
        $this->ci =& get_instance();
        $this->ci->load->config('google');
    }

    function boot(){

        $credentials = (object)$this->ci->config->item('credentials', 'google');
        $login_config = (object)$this->ci->config->item('login', 'google');

        $this->client = new Google_Client();
        $this->client->setApplicationName($login_config->application_name);
        $this->client->setClientId($credentials->client_id);
        $this->client->setClientSecret($credentials->client_secret);
 	    $this->client->setAccessType('offline'); //access offline
 	    $this->client->setIncludeGrantedScopes(TRUE); // incremental auth
 	    $this->client->addScope($login_config->scopes);
        $this->client->setRedirectUri(base_url($login_config->redirect_uri));
        // $this->client->setRedirectUri('http://appcenter.ph/cms4');
    }

    function generate_login_url(){
        if($this->client){
            return filter_var($this->client->createAuthUrl(), FILTER_SANITIZE_URL);
        }
        return NULL;
    }

    function get_client_access_token($response_code=NULL){
        if($this->client AND $response_code){
            $this->client->authenticate($response_code);
            
            if(is_array($this->client->getAccessToken())){
                return $this->client->getAccessToken();
            }
        }
        return NULL;
    }

    function get_client_info($access_token=NULL){
        $user = new StdClass();
		if($access_token){
			$this->client->setAccessToken($access_token);
			$google_oauthV2 = new Google_Service_Oauth2($this->client);

            $user = $google_oauthV2->userinfo->get();

			return $user;
		}
		return $user;
    }
}