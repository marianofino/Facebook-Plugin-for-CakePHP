<?php

App::uses('Router','Routing');

/**
 * 
 * Configure your App Settings
 * 
 **/

$config = array(
	'app_url' => 'http://www.yourdomain.com/path/to/cake', // or just http://www.yourdomain.com if it's on the root
	'app_id' => '39614xxxxxxx',
	'app_secret' => '55b41b0e7594bcxxxxxxxxxxxxxx',
	'login_url' => array('controller' => 'users', 'action' => 'login') // probably right
);

/**
 * 
 * Write global constants for plugin. Do not touch unless you know what you're doing.
 * 
 **/

Configure::write('FacebookAppId', $config['app_id']);
Configure::write('FacebookAppSecret', $config['app_secret']);
Configure::write('OauthRedirectUrl', $config['app_url'].Router::url($config['login_url']));

?>