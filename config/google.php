<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by: Aerhon Oliveros
 * Developer account used: Aerhon Oliveros (Personal Account)
 * Extend administrator users:
 */

$config['google']['credentials'] = [
	'client_id' => '',
	'client_secret' => '',
];

$config['google']['login'] = [
	'application_name' => 'Login with ',
	'scopes' => ['profile', 'email'],
	'redirect_uri' => 'google/callback'
];