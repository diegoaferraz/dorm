<?php
class DB {

	private static $table, $pdo, $sql;

  	public function __construct() {

		self::$pdo = Conexao::conectar();
  	}

  	static function table($table) {

  		self::$table = $table;
  		return new self;
  	}

  	static function prefix() {

  		$prefix = preg_match("/select/", self::$sql) ? "" : "select * from ".self::$table;
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

  		self::$sql = str_replace('select *', 'select '.$column, self::$sql);

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

		$prefix = preg_match("/group by/", self::$sql) ? "," : " group by ";

		self::$sql .= $prefix." $column";
		return new self;

	}

	static function having($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		self::$sql .= " having $column $o '$v' ";
		return new self;
	}

	static function order($column=null, $value='asc') {

		$column = isset($column) ? $column : ' ordem ';

		$prefix = preg_match("/order by/", self::$sql) ? "," : " order by ";

		self::$sql .= $prefix." $column $value ";
		return new self;
	}

	static function rand() {

		self::$sql .= " order by rand() ";
		return new self;
	}

	static function random() {

		self::$sql .= " order by random() ";
		return new self;
	}

	static function first() {

		self::$sql .= " limit 1 offset 0 ";
		return self::get()[0];
	}

	static function limit($limit, $offset=null) {

		$o = isset($offset) ? $limit : 0;
		$l = isset($offset) ? $offset : $limit;

		self::$sql .= " limit $l offset $o ";
		return new self;
	}

	static function offset($start) {

		self::$sql .= " limit 18446744073709551615 offset $start ";
		return new self;
	}

	static function select($columns='*') {

		if ($columns!='*') $columns = is_array($columns) ? implode(',', $columns) : $columns;

		self::$sql = "select $columns from ".self::$table." ";
		return new self;
	}

	static function cont($column=null) {

		$column = isset($column) ? $column : 'id';

		self::prefix();
  		self::$sql = str_replace('select *', 'select count('.$column.') as total ', self::$sql);

		return self::get()[0]->total;
	}

	static function max($column) {

		self::prefix();
  		self::$sql = str_replace('select *', 'select max('.$column.') as max ', self::$sql);

		return self::get()[0]->max;
	}

	static function min($column) {

		self::prefix();
  		self::$sql = str_replace('select *', 'select min('.$column.') as min ', self::$sql);

		return self::get()[0]->min;
	}

	static function avg($column) {

		self::prefix();
  		self::$sql = str_replace('select *', 'select avg('.$column.') as avg ', self::$sql);

		return self::get()[0]->avg;
	}

	static function sum($column) {

  		self::prefix();
  		self::$sql = str_replace('select *', 'select sum('.$column.') as sum ', self::$sql);

		return self::get()[0]->sum;
	}

	static function join($table, $column, $column2, $operator=null) {

		$o  = isset($operator) ? $column2 : '=';
		$c2 = isset($operator) ? $operator : $column2;

		self::$sql .= " inner join $table on $column $o $c2 ";
		return new self;
	}

	static function leftJoin($table, $column, $column2, $operator=null) {

		$o  = ($operator) ? $column2 : '=';
		$c2 = ($operator) ?: $column2;

		self::$sql .= " left join $table on $column $o $c2 ";
		return new self;
	}

	static function rightJoin($table, $column, $column2, $operator=null) {

		$o  = ($operator) ? $column2 : '=';
		$c2 = ($operator) ?: $column2;

		self::$sql .= " right join $table on $column $o $c2 ";
		return new self;
	}

	static function all() {

		return self::get();
	}

	static function actives($column=null) {

		$column = isset($column) ? $column : 'status';

		self::$sql = " select * from ".self::$table." ";

		self::statement();

		self::$sql .= " $column = 1 ";

		return new self;
	}

	static function find($value, $column=null) {

		$v = isset($column) ? $column : $value;
		$c = isset($column) ? $value : 'id';

		self::$sql = " select * from ".self::$table." where $c = '$v' ";

		$cont = self::$pdo->query(self::$sql)->rowCount();


		return ($cont==1) ? self::get()[0] : false;

		self::$sql = null;
	}

	static function statement( $operator = " and " ) {

		self::$sql .= preg_match("/where/", self::$sql) ? $operator : " where ";

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

		self::statement("or");

		self::$sql .= " $column $o '$v' ";

		return new self;
	}

	static function whereIn($column, $array=null) {

		$c = isset($array) ? $column : 'id';
		$a = isset($array) ? $array : $column;

		self::statement();

		self::$sql .= " $c in (".implode(',', $a).") ";

		return new self;
	}

	static function whereNotIn($column, $array=null) {

		$c = isset($array) ? $column : 'id';
		$a = isset($array) ? $array : $column;

		self::statement();

		self::$sql .= " $column not in (".implode(',', $a).") ";

		return new self;
	}

	static function isNull($column, $operator=null) {

		self::statement( $operator );

		self::$sql .= " $column is null ";

		return new self;
	}

	static function isNotNull($column, $operator=null) {

		self::statement( $operator );

		self::$sql .= " $column is not null ";

		return new self;
	}


	static function like($column, $value) {

		self::statement();

		self::$sql .= " $column like '%$value%' ";

		return new self;
	}

	static function startLike($column, $value) {

		self::statement();

		self::$sql .= " $column like '$value%' ";

		return new self;
	}

	static function endLike($column, $value) {

		self::statement();

		self::$sql .= " $column like '%$value' ";

		return new self;
	}

	static function between($column, $start, $end) {

		self::statement();

		self::$sql .= " $column between '$start' and '$end' ";

		return new self;
	}

	static function raw($statement) {

		self::$sql = $statement;
		return new self;
	}

	static function whereRaw($statement) {

		if ($statement!=null) self::$sql .= $statement;
		return new self;
	}

	static function insert($request) {

		$values  = is_array($request) ? $request : (array)$request;
		$columns = array_keys($values);

	 	$sql  = "insert into ".self::$table." (".implode(',', $columns).") values (:".implode(',:', $columns).")";

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

			return (object)$response;

		} catch (PDOException $e) {

			die("ins: " . $e->getMessage());
		}
	}

	static function update($request, $column=null, $operator=null, $value=null) {

		$values = is_array($request) ? $request : (array)$request;
		$fields = array_keys($values);

		$params = '';

		if (isset($column) && isset($column)) {

			for ($i=0; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$sql = "update ".self::$table." set ".substr($params, 0, -1)." where ".$column." ".$operator." '".$value."'";
		}
		else {

			for ($i=1; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$sql = "update ".self::$table." set ".substr($params, 0, -1)." where ".$fields[0].' = :'.$fields[0];
		}

		try {

			$qry = self::$pdo->prepare($sql);

			$commit = $qry->execute($values);

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("up: " . $e->getMessage());
		}
	}

	static function delete($value, $operator=null, $column=null) {

		$v = isset($column) ? $column : ($operator ?: $value);
		$o = isset($column) ? $operator : '=';
		$c = isset($column) || isset($operator) ? $value : 'id';

		$sql = "delete from ".self::$table." where $c $o ?";

		try {

			$qry = self::$pdo->prepare($sql);
			$qry->bindParam(1, $v);

			$commit = $qry->execute();

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("del: " . $e->getMessage());
		}
	}

	static function truncate() {

		$sql = "truncate ".self::$table;

		try {

			$qry = self::$pdo->prepare($sql);

			$commit = $qry->execute();

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("truncate: " . $e->getMessage());
		}
	}

}