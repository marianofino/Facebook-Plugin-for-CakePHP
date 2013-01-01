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

App::uses('AppHelper', 'View/Helper');

class FacebookHelper extends AppHelper {
	public $helpers = array('Html','Session');
	
    public function init() { // var_dump(FULL_BASE_URL); exit;
    	$script = "
    		<script>
			// Additional JS functions here
			window.fbAsyncInit = function() {
				FB.init({
				    appId      : '".Configure::read('FacebookAppId')."', // App ID
				    channelUrl : '".Router::url(array('plugin' => 'facebook', 'controller' => 'cache'), true)."', // Channel File
				    status     : true, // check login status
				    cookie     : true, // enable cookies to allow the server to access the session
				    xfbml      : true  // parse XFBML
			    });
			};
			
			// Load the SDK Asynchronously
			(function(d){
				var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
			    if (d.getElementById(id)) {return;}
			    js = d.createElement('script'); js.id = id; js.async = true;
			    js.src = '//connect.facebook.net/en_US/all.js';
			    ref.parentNode.insertBefore(js, ref);
			}(document));
			</script>
    	";

        return '<div id="fb-root"></div>'.$script;
    }
    
    public function likeBox($settings = array()) {
    	$default = array(
    		'data-send' => true,
    		'data-show-faces' => true,
    		'data-href' => '',
    		'data-width' => 200
    	);
    	
    	$options = array_merge($default,$settings);
    	
    	return $this->Html->div('fb-like', '', $options);	
    }
    
    public function loginButton($label = "Login", $options = array(), $permissions = array()) {
        // Facebook requests a csrf protection token
        if (!($csrf_token = $this->Session->read("state"))) {
            $csrf_token = md5(uniqid(rand(), TRUE));
            $_SESSION["state"] = $csrf_token; //CSRF protection
        }
        
        $scope = implode(",",$permissions);
        
    	return $this->Html->link($label, 'http://www.facebook.com/dialog/oauth?client_id='.Configure::read('FacebookAppId').'&redirect_uri='.Configure::read('OauthRedirectUrl').'&state='.$csrf_token.'&scope='.$scope, $options);
    }
}
?>