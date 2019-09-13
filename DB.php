<?php
class DB {

	private static $table, $pdo, $sql;

  	public function __construct() {

  		$Conexao = new Conexao();
		self::$pdo = $Conexao->conectar();
  	}

  	static function table($table) {

  		self::$table = $table;
  		return new self;
  	}

  	static function prefix() {

  		$prefix = preg_match("/SELECT/", self::$sql) ? "" : "SELECT * FROM ".self::$table;
  		self::$sql = $prefix.self::$sql;
  		return new self;
  	}

  	static function get() {

  		self::prefix();

		try {

			$qry = self::$pdo->prepare(self::$sql);
			$qry->execute();

			self::$sql = null;

			return $qry->fetchAll(PDO::FETCH_OBJ);

		} catch (PDOException $e) {

			die("get: ".$e->getMessage());
		}
	}

  	static function pluck($column) {

  		self::prefix();

  		self::$sql = str_replace('SELECT *', 'SELECT '.$column, self::$sql);

		try {

			$qry = self::$pdo->prepare(self::$sql);
			$qry->execute();

			$dados = $qry->fetchAll(PDO::FETCH_OBJ);

			$column = strpos($column, '.') ? explode(".", $column)[1] : $column;

			foreach ($dados as $dado) {

				$collection[] = $dado->{$column};
			}

			self::$sql = null;

			return ($qry->rowCount()>0) ? $collection : null;

		} catch (PDOException $e) {

			die("pluck: ".$e->getMessage());
		}
	}

  	static function count() {

  		self::prefix();

  		try {

			$qry = self::$pdo->prepare(self::$sql);
			$qry->execute();

			$dados = $qry->fetchAll(PDO::FETCH_OBJ);

			self::$sql = null;

			return $qry->rowCount();

		} catch (PDOException $e) {

			die("count: ".$e->getMessage());
		}
	}

	static function toSql() {

		self::prefix();
		echo self::$sql;
		self::$sql = null;
	}

	static function group($column) {

		$prefix = preg_match("/GROUP BY/", self::$sql) ? "," : " GROUP BY ";

		self::$sql .= $prefix." $column";
		return new self;

	}

	static function having($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		self::$sql .= " HAVING $column $o '$v' ";
		return new self;
	}

	static function order($column=null, $value='asc') {

		$column = isset($column) ? $column : ' ordem ';

		$prefix = preg_match("/ORDER BY/", self::$sql) ? "," : " ORDER BY ";

		self::$sql .= $prefix." $column $value ";
		return new self;
	}

	static function rand() {

		self::$sql .= " ORDER BY RAND() ";
		return new self;
	}

	static function random() {

		self::$sql .= " ORDER BY RANDOM() ";
		return new self;
	}

	static function first($column=null) {

		self::$sql .= (isset($column) ? " ORDER BY ".$column. " ASC " : "")." LIMIT 1 OFFSET 0";
		return self::get()[0];
	}

	static function last($column=null) {

		self::$sql .= " ORDER BY ".($column ?: "id"). " DESC LIMIT 1 OFFSET 0";
		return self::get()[0];
	}

	static function limit($limit, $offset=null) {

		$o = isset($offset) ? $limit : 0;
		$l = isset($offset) ? $offset : $limit;

		self::$sql .= " LIMIT $l OFFSET $o ";
		return new self;
	}

	static function offset($start) {

		self::$sql .= " LIMIT 18446744073709551615 OFFSET $start ";
		return new self;
	}

	static function select($columns='*') {

		if ($columns!='*') $columns = is_array($columns) ? implode(',', $columns) : $columns;

		self::$sql = "SELECT $columns FROM ".self::$table." ";
		return new self;
	}

	static function cont($column=null) {

		$column = isset($column) ? $column : 'id';

		self::prefix();
  		self::$sql = str_replace('SELECT *', 'SELECT COUNT('.$column.') AS total ', self::$sql);

		return self::get()[0]->total;
	}

	static function max($column, $alias=null) {

		$alias = isset($alias) ? $alias : 'max';

		self::prefix();
  		echo self::$sql = str_replace('SELECT *', 'SELECT MAX('.$column.') AS '.$alias.' ', self::$sql);

		return self::get()[0]->$alias;
	}

	static function min($column, $alias=null) {

		$alias = isset($alias) ? $alias : 'min';

		self::prefix();
  		self::$sql = str_replace('SELECT *', 'SELECT MIN('.$column.') AS '.$alias.' ', self::$sql);

		return self::get()[0]->$alias;
	}

	static function avg($column, $alias=null) {

		$alias = isset($alias) ? $alias : 'avg';

		self::prefix();
  		self::$sql = str_replace('SELECT *', 'SELECT AVG('.$column.') AS '.$alias.' ', self::$sql);

		return self::get()[0]->$alias;
	}

	static function sum($column, $alias=null) {

		$alias = isset($alias) ? $alias : 'sum';

  		self::prefix();
  		self::$sql = str_replace('SELECT *', 'SELECT SUM('.$column.') AS '.$name.' ', self::$sql);

		return self::get()[0]->$name;
	}

	static function join($table, $column, $column2, $operator=null) {

		$o  = isset($operator) ? $column2 : '=';
		$c2 = isset($operator) ? $operator : $column2;

		self::$sql .= " INNER JOIN $table ON $column $o $c2 ";
		return new self;
	}

	static function leftJoin($table, $column, $column2, $operator=null) {

		$o  = ($operator) ? $column2 : '=';
		$c2 = ($operator) ?: $column2;

		self::$sql .= " LEFT JOIN $table ON $column $o $c2 ";
		return new self;
	}

	static function rightJoin($table, $column, $column2, $operator=null) {

		$o  = ($operator) ? $column2 : '=';
		$c2 = ($operator) ?: $column2;

		self::$sql .= " RIGHT JOIN $table ON $column $o $c2 ";
		return new self;
	}

	static function all() {

		return self::get();
	}

	static function actives($column=null) {

		$column = isset($column) ? $column : 'status';

		self::$sql = " SELECT * FROM ".self::$table." ";

		self::statement();

		self::$sql .= " $column = 1 ";

		return new self;
	}

	static function find($value, $column=null) {

		$v = isset($column) ? $column : $value;
		$c = isset($column) ? $value : 'id';

		self::$sql = " SELECT * FROM ".self::$table." WHERE $c = '$v' ";

		$cont = self::$pdo->query(self::$sql)->rowCount();

		return ($cont==1) ? self::get()[0] : false;

		self::$sql = null;
	}

	static function statement( $operator = " AND " ) {

		self::$sql .= preg_match("/WHERE/", self::$sql) ? $operator : " WHERE ";

		return new self;
	}

	static function where($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		self::statement();

		self::$sql .= " $column $o '$v' ";

		return new self;
	}

	static function orWhere($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		self::statement("OR");

		self::$sql .= " $column $o '$v' ";

		return new self;
	}

	static function whereIn($column, $array=null) {

		$c = isset($array) ? $column : 'id';
		$a = isset($array) ? $array : $column;

		self::statement();

		self::$sql .= " $c IN ('".implode('\',\'', $a)."') ";

		return new self;
	}

	static function whereNotIn($column, $array=null) {

		$c = isset($array) ? $column : 'id';
		$a = isset($array) ? $array : $column;

		self::statement();

		self::$sql .= " $c NOT IN ('".implode('\',\'', $a)."') ";

		return new self;
	}

	static function isNull($column, $operator=null) {

		self::statement( $operator );

		self::$sql .= " $column IS NULL ";

		return new self;
	}

	static function isNotNull($column, $operator=null) {

		self::statement( $operator );

		self::$sql .= " $column IS NOT NULL ";

		return new self;
	}


	static function like($column, $value) {

		self::statement();

		self::$sql .= " $column LIKE '%$value%' ";

		return new self;
	}

	static function startLike($column, $value) {

		self::statement();

		self::$sql .= " $column LIKE '$value%' ";

		return new self;
	}

	static function endLike($column, $value) {

		self::statement();

		self::$sql .= " $column LIKE '%$value' ";

		return new self;
	}

	static function between($column, $start, $end) {

		self::statement();

		self::$sql .= " $column BETWEEN '$start' AND '$end' ";

		return new self;
	}

	static function queryRaw($statement) {

		self::$sql = $statement;
		return new self;
	}

	static function raw($statement) {

		self::$sql .= $statement;
		return new self;
	}

	static function insert($request) {

		$values  = is_array($request) ? $request : (array)$request;
		$columns = array_keys($values);

	 	$sql  = "INSERT INTO ".self::$table." (".implode(',', $columns).") VALUES (:".implode(',:', $columns).")";

		try {

			$qry = self::$pdo->prepare($sql);

			$commit = $qry->execute($values);

			if ($commit) {

				$response['id'] 	= (int)self::$pdo->lastInsertId();
				$response['result'] = true;
			}
			else {

				$response['result'] = false;
			}

			self::$sql = null;

			return (object)$response;

		} catch (PDOException $e) {

			die("ins: " . $e->getMessage());
		}
	}

	static function update($request, $column=null, $operator=null, $value=null) {

		$values = is_array($request) ? $request : (array)$request;
		$fields = array_keys($values);

		$params = '';

		self::prefix();

		if (preg_match("/WHERE/", self::$sql)) {

			for ($i=0; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$statement = "UPDATE ".self::$table." SET ".substr($params, 0, -1);

			$sql = preg_match("/SELECT */", self::$sql) ? str_replace("SELECT * FROM ".self::$table, $statement, self::$sql) : "";

		}
		elseif (isset($column) && isset($column)) {

			for ($i=0; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$sql = "UPDATE ".self::$table." SET ".substr($params, 0, -1)." WHERE ".$column." ".$operator." '".$value."'";
		}
		else {

			for ($i=1; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$sql = "UPDATE ".self::$table." SET ".substr($params, 0, -1)." WHERE ".$fields[0].' = :'.$fields[0];
		}

		try {

			$qry = self::$pdo->prepare($sql);

			$commit = $qry->execute($values);

			self::$sql = null;

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("up: " . $e->getMessage());
		}
	}

	static function delete($value=null, $operator=null, $column=null) {

		if ($value) {

			$v = isset($column) ? $column : ($operator ?: $value);
			$o = isset($column) ? $operator : '=';
			$c = isset($column) || isset($operator) ? $value : 'id';

			$sql = "DELETE FROM ".self::$table." WHERE $c $o $v ";
		}
		else {

			self::prefix();
			$sql = preg_match("/SELECT */", self::$sql) ? str_replace("SELECT *", "DELETE", self::$sql) : "";
		}

		try {

			$qry = self::$pdo->prepare($sql);

			$commit = $qry->execute();

			self::$sql = null;

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("del: " . $e->getMessage());
		}
	}

	static function truncate() {

		$sql = "TRUNCATE ".self::$table;

		try {

			$qry = self::$pdo->prepare($sql);

			$commit = $qry->execute();

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("truncate: " . $e->getMessage());
		}
	}

}