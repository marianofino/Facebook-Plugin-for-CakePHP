<?php
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
        
		/**
		 * Build Statement
		 */ 
		$fql = $this->buildStatement($queryData, $model->table);
		var_dump($fql); exit;
		 /**
         * Now we get, decode and return the remote data.
         */
		if (!is_null($fql)) {
			if (isset($_SESSION['access_token'])) {
				$params['access_token'] = $_SESSION['access_token'];
			}
			$json = $this->Http->get('https://graph.facebook.com/fql?q='.$fql, $params);
		    $res = json_decode($json, true);
		} else {
            $error = "error fql datasource";
            throw new CakeException($error);
		}
		
        return array($model->alias => $res);
    }

	/**
	 * Parses Conditions Data
	 */
	private function parseConditions($conditions) {
		$result = "";
		foreach ($conditions as $k => $v) {
			if (!is_array($v)) {
				$result .= $k."='".$v."' AND ";
			} else {
				if (key($v) == 'subquery') {
					$queryData = $v['subquery'];
					$fql = $this->buildStatement($queryData,$queryData['table']);
					$result .= $k." IN (".$fql.") AND ";
				}
			}
		}
		return substr($result,0,-5);
	}
	
	public function buildStatement($queryData, $table) {
		if (isset($queryData['fields']) && isset($queryData['conditions'])) {
			$fields = implode(",",$queryData['fields']);
			$conditions = $this->parseConditions($queryData['conditions']);
			
			return "SELECT+".$fields."+FROM+".$table."+WHERE+".$conditions;
		} else {
			return null;
		}
	}

}
?>