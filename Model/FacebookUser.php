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
 
App::uses('FacebookAppModel', 'Facebook.Model');
class FacebookUser extends FacebookAppModel {
    public $useDbConfig = 'facebook';
	public $useTable = "user";
	public $primaryKey = "uid";
	public $cacheQueries = true;
	
    public $hasAndBelongsToMany = array(
        'FacebookPage' => array(
            'className'    => 'Facebook.FacebookPage',
            'joinTable' => 'page_admin',
            'foreignKey'  => 'uid',
            'associationForeignKey' => 'page_id',
            'with' => 'Facebook.FacebookPageAdmin'
        )
    );

    public $hasMany = array(
        'FacebookAlbum' => array(
            'className'    => 'Facebook.FacebookAlbum',
            'foreignKey'  => 'owner'
        )
    );
	
	public function getLoginData() {
		$fields = array(
            "FacebookUser.uid",
            "FacebookUser.username"
		);
		return $this->find('first', array('fields' => $fields, 'conditions' => array('FacebookUser.uid = me()')));
	}
	
	public function parseDataForDb($fb_user) {
    	$user = array();
    	foreach ($fb_user['FacebookUser'] as $k => $field) {
    		$user['User'][$k] = $field;
    	}
		return $user;
	}
}
?>