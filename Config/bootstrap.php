<?php
/**
 *  This file is part of Facebook Plugin for CakePHP.
 *
 *  Facebook Plugin for CakePHP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Facebook Plugin for CakePHP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with Facebook Plugin for CakePHP.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  @copyright Copyright (c) 2012 - Mariano Finochietto // twitter: @finomdq // github: @marianofino 
 */

App::uses('Router','Routing');
App::uses('ConnectionManager','Model');

$facebook_datasource = ConnectionManager::getDataSource('facebook');

$facebook_config = $facebook_datasource->config;

/**
 * 
 * Write global constants for plugin. Do not touch unless you know what you're doing.
 * 
 **/
$default_login_url = array('plugin' => 'facebook', 'controller' => 'users', 'action' => 'login');

Configure::write('FacebookAppId', $facebook_config['app_id']);
Configure::write('FacebookAppSecret', $facebook_config['app_secret']);
Configure::write('OauthRedirectUrl', $facebook_config['app_url'].Router::url($default_login_url));

?>