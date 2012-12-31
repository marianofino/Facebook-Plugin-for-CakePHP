<?php
/**
 * Cache controller for JS SDK channel (better perfomance)
 *
 * @package		app.Plugin.Facebook.Controller
 * @author 		Mariano Finochietto
 * @copyright	Copyright 2012, Mariano Finochietto (http://marianofino.com)
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
 
class CacheController extends AppController {

/**
 * Displays cache in a blank view
 */
	public function index() {
		$this->layout = "ajax";
	}

}
?>