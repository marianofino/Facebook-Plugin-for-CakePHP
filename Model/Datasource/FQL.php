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
	 * Used to store the data and its configuration
	 */
	
	public $classes = array();
	public $queryData = array();

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

		// Get new formatted queryData
		$this->queryData = $this->formatQueryData($queryData);
		
		// Get query valid associations
		$associations = $this->getAssociations($model->recursive);
		
		// Add primaryKey to main model
		$this->queryData["fields"][$model->alias][] = $model->primaryKey;
		
		// Add all relevant classes and its data
		$this->addClass($model);
		while (($assoc = each($associations)) && $assoc !== false) {
			$classes = $model->$assoc["value"];
			foreach ($classes as $k => $c) {
				// Check if there are fields to get
				if (isset($this->queryData["fields"][$k])) {
					$parent_model = $model->alias;
					$class = $this->parseClassName($c["className"]);
					App::uses($class["name"],$class["package"]);
					$related_model = new $class["name"]();
					
					$foreignKey = $this->resolveForeignKey($parent_model,$related_model);
					$c["parentKey"] = $foreignKey;
					switch ($assoc["value"]) {
						case "hasAndBelongsToMany":
							//instanciar la otra clase, armarla y agregarla a la related
							$join_class = $this->parseClassName($c["with"]);
							App::uses($join_class["name"],$join_class["package"]);
							$join_model = new $join_class["name"]();
							// TODO: don't depend on uid condition.. (username can be another one!)
							if (!isset($this->queryData["conditions"][$join_model->alias])) {
								$this->queryData["conditions"][$join_model->alias] = null;
							}
							$this->queryData["conditions"][$join_model->alias][$c["foreignKey"]] = $this->queryData["conditions"][$model->alias][$c["foreignKey"]];
							$this->queryData["fields"][$join_model->alias][] = $c["associationForeignKey"];
							$this->queryData["fields"][$join_model->alias][] = $c["foreignKey"];
							$c["join"] = array($join_model->alias => $this->createClass($join_model));
							$this->queryData["fields"][$related_model->alias][] = $c["associationForeignKey"];
							
							$subQueryData = array(
								"fields" => array($c["associationForeignKey"])
							);
							$subquery = $this->buildStatement($subQueryData,"%23".$join_model->alias);
							$this->queryData["conditions"][$related_model->alias][$c["associationForeignKey"]] = array($subquery);
							
						break;
						case "hasMany":
							$subQueryData = array(
								"fields" => array($model->primaryKey)
							);
							$subquery = $this->buildStatement($subQueryData,"%23".$model->alias);
							$this->queryData["conditions"][$related_model->alias][$c["foreignKey"]] = array($subquery);
							
							$this->queryData["fields"][$related_model->alias][] = $c["foreignKey"];
							$this->classes[$model->alias]["queryData"]["fields"][] = $foreignKey;
						break;
						case "hasOne":
						case "belongsTo":
							$this->queryData["conditions"][$related_model->alias][$c["foreignKey"]] = $this->queryData["conditions"][$model->alias][$foreignKey];
						
							$this->queryData["fields"][$related_model->alias][] = $c["foreignKey"];
							$this->classes[$model->alias]["queryData"]["fields"][] = $foreignKey;
						break;
					}
					$this->addClass($related_model,array("type" => $assoc["value"], "parent" => $parent_model, "attributes" => $c));
				}
			}
		}

		// Build FQL queries for every class
		$this->buildFQL($this->classes);
		
		// Build general FQL
		$fql = $this->getFullFQL($this->classes);
		
		// Run FQL
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
		
		// Save Result in param $classes
		$this->parseResult($this->classes, $res['data']);
				
		// Get & return results
		$ret = $this->getResults($this->classes);
		unset($this->classes);
		return $ret;
    }

	public function resolveForeignKey($parent_model, $related_model) {
		$associated = $related_model->getAssociated();
		$attributes = $related_model->$associated[$parent_model];
		if (isset($attributes[$parent_model]["associationForeignKey"])) {
			return $attributes[$parent_model]["associationForeignKey"];
		}
		return $attributes[$parent_model]["foreignKey"];
	}

	// Returns an array of associations, based on recursive level
	public function getAssociations($recursive) {
		$enabled_assoc = array();
		if ($recursive > -1) {
			$enabled_assoc = array('belongsTo','hasOne');
			if ($recursive > 0) {
				$enabled_assoc = array_merge($enabled_assoc,array('hasMany','hasAndBelongsToMany'));
			}
		}
		return $enabled_assoc;
	}
	
	// Returns a formatted array, for an easier lecture
	public function formatQueryData($queryData) {
		$queryData['conditions'] = $this->getOrganizedConditions($queryData['conditions']);
		$queryData['fields'] = $this->getOrganizedFields($queryData['fields']);
		return $queryData;
	}
	
	public function createClass($model) {
		if (!isset($this->queryData['conditions'][$model->alias])) {
			$this->queryData['conditions'][$model->alias] = null;
		}
		$classData = array(
			"queryData" => array(
				"conditions" => $this->queryData['conditions'][$model->alias],
				"fields" => $this->queryData['fields'][$model->alias]
			),
			"primaryKey" => $model->primaryKey,
			"table" => $model->table,
			"fql" => null,
			"results" => null,
			"associations" => null
		);
		return $classData;
	}
	
	// Adds a record to public classes
	public function addClass($model, $assoc = false) {
		$class = $this->createClass($model);
		if ($assoc === false) {
			$this->classes[$model->alias] = $class;
		} else {
			if ($assoc["type"] == "join") {
				foreach ($this->classes as $c) {
					if (!is_null($c["associations"])) {
						foreach ($c["associations"] as $a) {
							if (key($a) == $model) {
								$this->classes[key($c)]["associations"]["hasAndBelongsToMany"][$assoc["type"]] = array($mdoel->alias => $class);
							}
						}
					}
				}
			} else {
				if (!isset($this->classes[$assoc["parent"]]["associations"][$assoc["type"]])) {
					$this->classes[$assoc["parent"]]["associations"][$assoc["type"]] = null;
				}
				if (isset($assoc["attributes"])) {
					$class["attributes"] = $assoc["attributes"];
				}
				$this->classes[$assoc["parent"]]["associations"][$assoc["type"]][$model->alias] = $class;
			}
		}
	}
	
	// Build fql for each class
	public function buildFQL(&$classes) {
		foreach ($classes as $model => $data) {
			$classes[$model]['fql'] = $this->buildStatement($data['queryData'], $data['table']);
			$associations = $data["associations"];
			if (!is_null ($associations)) {
				foreach ($associations as $k => $a) {
					$this->buildFQL($classes[$model]["associations"][$k]);
					if ($k == "hasAndBelongsToMany") {
						$this->buildFQL($classes[$model]["associations"][$k][key($a)]["attributes"]["join"]);
					}
				}
			}
		}
	}
	
	// Create full fql to query
	public function getFullFQL($classes) {
		$q = "";
		foreach ($classes as $model => $data) {
			$q .= '"'.$model.'":"'.$data['fql'].'",';
			if (!is_null($data["associations"])) {
				foreach ($data["associations"] as $k => $a) {
					$q .= $this->getFullFQL($a).",";
					if ($k == "hasAndBelongsToMany") {
						$q .= $this->getFullFQL($a[key($a)]["attributes"]["join"]).",";
					}
				}
			}
		}
		return substr($q,0,-1);
	}
	
	// Save results in attrubute $classes
	public function parseResult(&$classes, $result) {
		foreach ($classes as $model => $data) {	
			$classes[$model]['results'] = $this->getResultData($result, $model);
			if (!is_null($data['associations'])) {
				foreach ($data['associations'] as $k => $a) {
					$this->parseResult($classes[$model]['associations'][$k], $result);
					if ($k == "hasAndBelongsToMany") {
					$this->parseResult($classes[$model]['associations'][$k][key($a)]["attributes"]["join"], $result);
					}
				}
			}
		}
	}
	
	// Get results from class
	public function getResultData($result, $className) {
		$i=0;
		while($result[$i]["name"] != $className) {
			$i++;
		}
		return $result[$i]["fql_result_set"];
	}
	
	public function getResults($classes, $key = null, $value = null) {
		foreach ($classes as $model => $data) {
			if (!is_null($data['associations'])) {
				foreach ($data["results"] as $d) {
					foreach ($data['associations'] as $k => $a) {
						switch ($k) {
							case "hasAndBelongsToMany":
								$join_class = $a[key($a)]["attributes"]["join"];
								$join_results = $join_class[key($join_class)]["results"];
							
								foreach ($join_results as $kr => $r) {
									$join_final = $this->filterResults($a[key($a)]["results"], $a[key($a)]["attributes"]["associationForeignKey"], $r[$a[key($a)]["attributes"]["associationForeignKey"]], true);
									$join_final[key($join_class)] = $r;
									$join_final[$a[key($a)]["attributes"]["foreignKey"]] = $r[$a[key($a)]["attributes"]["foreignKey"]];
									$new_results[] = $join_final;
								}
								
								$assoc_result = $this->filterResults($new_results, $a[key($a)]["attributes"]["foreignKey"], $d[$a[key($a)]["attributes"]["parentKey"]]);
								
								$result[] = array(
									$model => $d,
									key($a) => $assoc_result
								);
							break;
							case "hasMany":
								$assoc_result = $this->filterResults($a[key($a)]["results"], $a[key($a)]["attributes"]["foreignKey"], $d[$a[key($a)]["attributes"]["parentKey"]]);
								$d[key($a)] = $assoc_result;
								$result[] = array($model => $d);
							break;
							case "belongsTo":
							case "hasOne":
								$assoc_result = $this->filterResults($a[key($a)]["results"], $a[key($a)]["attributes"]["foreignKey"], $d[$a[key($a)]["attributes"]["parentKey"]]);
								$k = array(
									key($a) => $assoc_result,
									$model => $d
								);
								$result[] = $k;
							break;
						}
					}
				}
			} else {
				foreach ($data["results"] as $d) {
					$result[] = array($model => $d);
				}
			}
		}
		return $result;
	}
	
	public function filterResults($results, $key, $value, $return = false) {
		$i=0;
		$totalResults = count($results);
		if ($return) {
			while ($i<$totalResults) {
				if ($results[$i][$key] == $value) {
					return $results[$i];
				}
				$i++;
			}
		} else {
			$new = array();
			while ($i<$totalResults) {
				if ($results[$i][$key] == $value) {
					$new[] = $results[$i];
				}
				$i++;
			}
			return $new;
		}
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
			if (is_array($v)) {
				$result .= $k." IN (".implode(",",$v).") AND ";
			} elseif ($k === 0) {
				$result .= $v." AND ";
			} else {
				$result .= $k."='".$v."' AND ";
			}
		}
		return substr($result,0,-5);
	}
	
	public function getOrganizedConditions($conditions) {
		$parsed = array();
		while (($c = each($conditions)) && $c != false && (($c['key'] == 0 && strpos($c['value'],".") !== false) || strpos($c['key'],".") !== false)) {
			if ($c['key'] === 0) {
				$condition_key = explode(".",$c['value']);
				$parsed[$condition_key[0]][] = $condition_key[1];
			} else {
				$condition_key = explode(".",$c['key']);
				$parsed[$condition_key[0]][$condition_key[1]] = $c['value'];
			}
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
	
	public function buildStatement($queryData, $table) {
		if (isset($queryData['fields'])) {
			$fields = implode(",",$queryData['fields']);
			if (isset($queryData['conditions'])) {
				$conditions = $this->parseConditions($queryData['conditions']);
				$query = "SELECT ".$fields." FROM ".$table." WHERE ".$conditions;
			} else {
				$query = "SELECT ".$fields." FROM ".$table;
			}
			
			return str_replace(" ","+",$query);
		} else {
			return null;
		}
	}

}
?>
