<?php

App::uses('Router','Routing');
App::uses('ConnectionManager','Model');

$facebook_datasource = ConnectionManager::getDataSource('facebook');

$facebook_config = $facebook_datasource->config;

/**
 * 
 * Write global constants for plugin. Do not touch unless you know what you're doing.
 * 
 **/

Configure::write('FacebookAppId', $facebook_config['app_id']);
Configure::write('FacebookAppSecret', $facebook_config['app_secret']);
Configure::write('OauthRedirectUrl', $facebook_config['app_url'].Router::url($facebook_config['login_url']));

?>