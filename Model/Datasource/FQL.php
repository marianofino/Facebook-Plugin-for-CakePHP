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

App::uses('HttpSocket', 'Network/Http');

class FQL extends DataSource {
	
    public $description = 'Facebook Datasource';

/**
 * Create our HttpSocket and handle any config tweaks.
 */
    public function __construct($config) {
        parent::__construct($config);
        $this->Http = new HttpSocket();
    }

/**
 * Since datasources normally connect to a database there are a few things
 * we must change to get them to work without a database.
 */

/**
 * listSources() is for caching. You'll likely want to implement caching in
 * your own way with a custom datasource. So just ``return null``.
 */
    public function listSources($data = null) {
        return null;
    }

/**
 * describe() tells the model your schema for ``Model::save()``.
 *
 * You may want a different schema for each model but still use a single
 * datasource. If this is your case then set a ``schema`` property on your
 * models and simply return ``$model->schema`` here instead.
 */
    public function describe($model) {
        return $model->_schema;
    }

/**
 * calculate() is for determining how we will count the records and is
 * required to get ``update()`` and ``delete()`` to work.
 *
 * We don't count the records here but return a string to be passed to
 * ``read()`` which will do the actual counting. The easiest way is to just
 * return the string 'COUNT' and check for it in ``read()`` where
 * ``$data['fields'] == 'COUNT'``.
 */
    public function calculate(Model $model, $func, $params = array()) {
        return 'COUNT';
    }

/**
 * Implement the R in CRUD. Calls to ``Model::find()`` arrive here.
 */
    public function read(Model $model, $queryData = array(), $recursive = null) {
        /**
         * Here we do the actual count as instructed by our calculate()
         * method above. We could either check the remote source or some
         * other way to get the record count. Here we'll simply return 1 so
         * ``update()`` and ``delete()`` will assume the record exists.
         */
        if ($queryData['fields'] == 'COUNT') {
            return array(array(array('count' => 1)));
        }
		
		// TODO: improve conditions to more supported by FQL
		// TODO: make recursive 2
		
		$queryData['fields'] = $this->getOrganizedFields($queryData['fields']);
		if (!is_null($queryData['conditions'])) {
			$queryData['conditions'] = $this->getOrganizedConditions($queryData['conditions']);
		}
		
		/**
		 * Build Statement
		 */ 
		$mainQuery = $this->buildStatement($queryData, $model->table, $model->alias);
		
		// Get Associated queries, based on recursive level
		$assoc_queries = "";
		if ($model->recursive > -1) {
			$enabled_assoc = array('belongsTo','hasOne');
			if ($model->recursive > 0) {
				$enabled_assoc = array_merge($enabled_assoc,array('hasMany','hasAndBelongsToMany'));
			}
			$assoc_queries = $this->getAssociatedQueries($model,$enabled_assoc,$queryData);
		}
		
		$fql = $this->formatQuery($model->alias,$mainQuery).$assoc_queries;
		
		 /**
         * Now we get, decode and return the remote data.
         */
		if (!is_null($fql)) {
			if (isset($_SESSION['access_token'])) {
				$params['access_token'] = $_SESSION['access_token'];
			}
			$params['format'] = "json-strings";
			$json = $this->Http->get('https://graph.facebook.com/fql?q={'.$fql.'}', $params);
		    $res = json_decode($json, true);
			if (isset($res['error'])) {
				$error = "Facebook API error code ".$res['error']['code']." (".$res['error']['type']."): ".$res['error']['message'];
            	throw new CakeException($error);
			}
		} else {
            $error = "No queries to execute";
            throw new CakeException($error);
		}
		
		// Format a CakePHP friendly result
		$result = array();
		foreach ($res['data'] as $r) {
			if (count($r["fql_result_set"]) == 1) {
				$result[$r["name"]] = $r["fql_result_set"][0];
			} else {
				$result[$r["name"]] = $r["fql_result_set"];
			}
		}
		
        return $result;
    }

	public function getAssociatedQueries($model,$enabled_assoc,$mainQueryData) {
		$associations = $model->getAssociated();
		$queries = "";
		// Go through model associations
		foreach ($associations as $k => $a) {
			// Check if association corresponds to current level of recursiveness and has fields to fetch
			if (in_array($a,$enabled_assoc) && isset($mainQueryData['fields'][$k])) {
				$assoc_settings = $model->$a;
				//TODO: merge association options with model default ones
				
				// Get associated Model's object
				if ($a == "hasAndBelongsToMany") {
					// We need the Model that handles the relation, not the indirectly associated one
					$assoc_model_class = $assoc_settings[$k]['with'];
				} else {
					$assoc_model_class = $assoc_settings[$k]['className'];
				}
				$assoc_model_class = $this->parseClassName($assoc_model_class);
				$assoc_model_name = $assoc_model_class['name'];
				App::uses($assoc_model_name,$assoc_model_class['package']);
				$assoc_model = new $assoc_model_name();
				
				// Get associated Model's datasource
				$data_source = $assoc_model->useDbConfig;
				if ($data_source == "facebook") {
					// Build queries
					// First check if association is hasAndBelongsToMany because then we would need an extra query
					if ($a == "hasAndBelongsToMany") {
						$related_model_class = $this->parseClassName($assoc_settings[$k]['className']);
						$related_model_name = $related_model_class['name'];
						App::uses($related_model_name,$related_model_class['package']);
						$related_model = new $related_model_name();
						
						//TODO: Don't make it hardcoded
						//TODO: Check that primaryKey could not work.. See foreignKey use
						$selected_items = array('SELECT+'.$related_model->primaryKey.'+FROM+%23'.$assoc_model_name);
						
						$mainQueryData['conditions'][$related_model->alias][$related_model->primaryKey] = $selected_items;
						$queryData = array(
							'fields' => $mainQueryData['fields'],
							'conditions' => $mainQueryData['conditions']
						);
						$queries .= ",".$this->formatQuery($related_model->alias,$this->buildStatement($queryData,$related_model->table,$related_model->alias));
						
						// Add related Model's primary key to associated model fields for query
						$mainQueryData['fields'][$assoc_model->alias][] = $related_model->primaryKey;
					}
					// Build query
					$mainQueryData['conditions'][$assoc_model->alias][$assoc_settings[$k]['foreignKey']] = $mainQueryData['conditions'][$model->alias][$model->primaryKey];
					$queryData = array(
						'fields' => $mainQueryData['fields'],
						'conditions' => $mainQueryData['conditions']
					);
					$queries .= ",".$this->formatQuery($assoc_model->alias,$this->buildStatement($queryData,$assoc_model->table,$assoc_model->alias));
				}
			}
		}
		
		return $queries;
	}

	public function parseClassName($className) {
		if (strpos($className,".") !== false) {
			$className = explode(".",$className);
			$parsed = array(
				'package' => $className[0].".Model",
				'name' => $className[1]
			);
		} else {
			$parsed = array(
				'package' => "Model",
				'name' => $className
			);
		}
		return $parsed;
	}

	/**
	 * Parses Conditions Data
	 */
	private function parseConditions($conditions) {
		$result = "";
		foreach ($conditions as $k => $v) {
			if (!is_array($v)) {
				$result .= $k."='".$v."'+AND+";
			} else {
				$result .= $k."+IN+(".implode(",",$v).")+AND+";
			}
		}
		return substr($result,0,-5);
	}
	
	public function getOrganizedConditions($conditions) {
		$parsed = array();
		while (($c = each($conditions)) && $c != false && (strpos($c['key'],".") !== false)) {
			$condition_key = explode(".",$c['key']);
			$parsed[$condition_key[0]][$condition_key[1]] = $c['value'];
		}
		if ($c==false) {
			return $parsed;
		}
		return $conditions;
	}
	
	public function getOrganizedFields($fields) {
		$parsed = array();
		$i = 0;
		$totalFields = count($fields);
		while($i<$totalFields && (strpos($fields[$i],".") !== false)) {
			$field = explode(".",$fields[$i]);
			$parsed[$field[0]][] = $field[1];
			$i++;
		}
		if ($i==$totalFields) {
			return $parsed;
		}
		return $fields;
	}
	
	public function buildStatement($queryData, $table, $class_name) {
		if (isset($queryData['fields'][$class_name])) {
			if (!isset($queryData['conditions'][$class_name])) {
				//TODO: change uid+me() ??
				$conditions = "uid+=+me()";
			} else {
				$conditions = $this->parseConditions($queryData['conditions'][$class_name]);
			}
			$fields = implode(",",$queryData['fields'][$class_name]);
			
			return "SELECT+".$fields."+FROM+".$table."+WHERE+".$conditions;
		} else {
			return null;
		}
	}
	
	public function formatQuery($className,$query) {
		return '"'.$className.'":"'.$query.'"';
	}

}
?>