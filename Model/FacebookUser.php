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
            "email",
            "first_name",
            "last_name",
            "birthday",
            "gender"
		);
		return $this->find('all', array('fields' => $fields));
	}
}
?>