<?php
App::uses('AppModel', 'Model');
/**
 * Album Model
 *
 * @property Website $Website
 * @package       app.Model
 */
class FacebookUser extends AppModel {
    public $useDbConfig = 'facebook';
	
	public function getFullData() {
		$fields = array(
            "id",
            "username"
		);
		return $this->find('all', array('fields' => $fields));
	}
}
?>