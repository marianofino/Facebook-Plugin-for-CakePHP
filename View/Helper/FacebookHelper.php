<?php
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