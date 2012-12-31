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

/**
 * If we want to create() or update() we need to specify the fields
 * available. We use the same array keys as we do with CakeSchema, eg.
 * fixtures and schema migrations.
 */
    protected $_schema = array(
        'id' => array(
            'type' => 'integer',
            'null' => false,
            'key' => 'primary',
            'length' => 11,
        ),
        'name' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'username' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
    );
	
	public function getFullData($ID = "me") {
		$fields = array(
            "id",
            "email",
            "first_name",
            "last_name",
            "birthday",
            "gender"
		);
		return $this->find('all', array('ID' => $ID, 'fields' => $fields));
	}
}
?>