<?php
/*
 * 	$DB = new Dorm();
 *	$produtos = $DB->table('produtos')->all();
 * 	0.9
 *
 */

class Dorm {

	private $table, $pdo, $sql;

  	public function __construct() {

		$this->pdo = Conexao::conectar();
  	}

  	function table($table) {

  		$this->table = $table;
  		return $this;
  	}

  	function prefix() {

  		$prefix = preg_match("/select/", $this->sql) ? "" : "select * from $this->table ";
  		$this->sql = $prefix.$this->sql;
  		return $this;
  	}

  	function get() {

  		self::prefix();

		try {

			$qry = $this->pdo->prepare($this->sql);
			$qry->execute();

			$this->sql = null;

			return $qry->fetchAll(PDO::FETCH_OBJ);

		} catch (PDOException $e) {

			die("get: ".$e->getMessage());
		}
	}

  	function pluck($column) {

  		self::prefix();

  		$this->sql = str_replace('select *', 'select '.$column, $this->sql);

		try {

			$qry = $this->pdo->prepare($this->sql);
			$qry->execute();

			$dados = $qry->fetchAll(PDO::FETCH_OBJ);

			$column = strpos($column, '.') ? explode(".", $column)[1] : $column;

			foreach ($dados as $dado) {

				$collection[] = $dado->{$column};
			}

			$this->sql = null;

			return ($qry->rowCount()>0) ? $collection : null;

		} catch (PDOException $e) {

			die("pluck: ".$e->getMessage());
		}
	}

  	function count() {

  		self::prefix();

  		try {

			$qry = $this->pdo->prepare($this->sql);
			$qry->execute();

			$dados = $qry->fetchAll(PDO::FETCH_OBJ);

			$this->sql = null;

			return $qry->rowCount();

		} catch (PDOException $e) {

			die("count: ".$e->getMessage());
		}
	}

	function toSql() {

		self::prefix();
		echo $this->sql;
		$this->sql = null;
	}

	function group($column) {

		$prefix = preg_match("/group by/", $this->sql) ? "," : " group by ";

		$this->sql .= $prefix." $column";
		return $this;

	}

	function having($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		$this->sql .= " having $column $o '$v' ";
		return $this;
	}

	function order($column=null, $value='asc') {

		$column = isset($column) ? $column : ' ordem ';

		$prefix = preg_match("/order by/", $this->sql) ? "," : " order by ";

		$this->sql .= $prefix." $column $value ";
		return $this;
	}

	function rand() {

		$this->sql .= " order by rand() ";
		return $this;
	}

	function first() {

		$this->sql .= " limit 0,1 ";
		return $this->get()[0];
	}

	function limit($limit, $offset=null) {

		$o = isset($offset) ? $limit : 0;
		$l = isset($offset) ? $offset : $limit;

		$this->sql .= " limit $o, $l ";
		return $this;
	}

	function offset($start) {

		$this->sql .= " limit 18446744073709551615 offset $start ";
		return $this;
	}

	function select($columns='*') {

		if ($columns!='*') $columns = is_array($columns) ? implode(',', $columns) : $columns;

		$this->sql = "select $columns from $this->table ";
		return $this;
	}

	function cont($column=null, $condition=null) {

		$column = isset($column) ? $column : 'id';
		if ($condition) $condition = preg_match("/where/", $condition) ? $condition : ' where '.$condition;

		$this->sql = "select count($column) as total from $this->table $condition";

		return $this->get()[0]->total;
	}

	function max($column, $condition=null) {

		if ($condition) $condition = preg_match("/where/", $condition) ? $condition : ' where '.$condition;

		$this->sql = "select max($column) as max from $this->table $condition";

		return $this->get()[0]->max;
	}

	function min($column, $condition=null) {

		if ($condition) $condition = preg_match("/where/", $condition) ? $condition : ' where '.$condition;

		$this->sql = "select min($column) as min from $this->table $condition";

		return $this->get()[0]->min;
	}

	function avg($column, $condition=null) {

		if ($condition) $condition = preg_match("/where/", $condition) ? $condition : ' where '.$condition;

		$this->sql = "select avg($column) as avg from $this->table $condition";

		return $this->get()[0]->avg;
	}

	function sum($column, $condition=null) {

		if ($condition) $condition = preg_match("/where/", $condition) ? $condition : ' where '.$condition;

		$this->sql = "select sum($column) as sum from $this->table $condition";

		return $this->get()[0]->sum;
	}

	function join($table, $column, $column2, $operator=null) {

		$o  = isset($operator) ? $column2 : '=';
		$c2 = isset($operator) ? $operator : $column2;

		$this->sql .= " inner join $table on $column $o $c2 ";
		return $this;
	}

	function leftJoin($table, $column, $column2, $operator=null) {

		$o  = ($operator) ? $column2 : '=';
		$c2 = ($operator) ?: $column2;

		$this->sql .= " left join $table on $column $o $c2 ";
		return $this;
	}

	function rightJoin($table, $column, $column2, $operator=null) {

		$o  = ($operator) ? $column2 : '=';
		$c2 = ($operator) ?: $column2;

		$this->sql .= " right join $table on $column $o $c2 ";
		return $this;
	}

	function all() {

		return $this->get();
	}

	function actives($column=null) {

		$column = isset($column) ? $column : 'status';

		$this->sql = " select * from $this->table where $column=1 ";

		return $this;
	}

	function find($value, $column=null) {

		$v = isset($column) ? $column : $value;
		$c = isset($column) ? $value : 'id';

		$this->sql = " select * from $this->table where $c = '$v' ";

		$cont = $this->pdo->query($this->sql)->rowCount();


		return ($cont==1) ?$this->get()[0] : false;

		$this->sql = null;
	}

	function statement( $operator = " and " ) {

		$this->sql .= preg_match("/where/", $this->sql) ? $operator : " where ";

		return $this;
	}

	function where($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		self::statement();

		$this->sql .= " $column $o '$v' ";

		return $this;
	}

	function orWhere($column, $value, $operator=null) {

		$o = isset($operator) ? $value : '=';
		$v = isset($operator) ? $operator : $value;

		self::statement("or");

		$this->sql .= " $column $o '$v' ";

		return $this;
	}

	function whereIn($column, $array=null) {

		$c = isset($array) ? $column : 'id';
		$a = isset($array) ? $array : $column;

		self::statement();

		$this->sql .= " $c in (".implode(',', $a).") ";

		return $this;
	}

	function whereNotIn($column, $array=null) {

		$c = isset($array) ? $column : 'id';
		$a = isset($array) ? $array : $column;

		self::statement();

		$this->sql .= " $column not in (".implode(',', $a).") ";

		return $this;
	}

	function isNull($column, $operator=null) {

		self::statement( $operator );

		$this->sql .= " $column is null ";

		return $this;
	}

	function isNotNull($column, $operator=null) {

		self::statement( $operator );

		$this->sql .= " $column is not null ";

		return $this;
	}


	function like($column, $value) {

		self::statement();

		$this->sql .= " $column like '%$value%' ";

		return $this;
	}

	function startLike($column, $value) {

		self::statement();

		$this->sql .= " $column like '$value%' ";

		return $this;
	}

	function endLike($column, $value) {

		self::statement();

		$this->sql .= " $column like '%$value' ";

		return $this;
	}

	function between($column, $start, $end) {

		self::statement();

		$this->sql .= " $column between '$start' and '$end' ";

		return $this;
	}

	function raw($statement) {

		$this->sql = $statement;
		return $this;
	}

	function whereRaw($statement) {

		if ($statement!=null) $this->sql .= $statement;
		return $this;
	}

	function insert($request) {

		$values  = is_array($request) ? $request : (array)$request;
		$columns = array_keys($values);

	 	$sql  = "insert into $this->table (".implode(',', $columns).") values (:".implode(',:', $columns).")";

		try {

			$qry = $this->pdo->prepare($sql);

			$commit = $qry->execute($values);

			if ($commit) {

				$response['id'] 	= (int)$this->pdo->lastInsertId();
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

	function update($request, $column=null, $operator=null, $value=null) {

		$values = is_array($request) ? $request : (array)$request;
		$fields = array_keys($values);

		$params = '';

		if (isset($column) && isset($column)) {

			for ($i=0; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$sql = "update $this->table set ".substr($params, 0, -1)." where ".$column." ".$operator." '".$value."'";
		}
		else {

			for ($i=1; $i<count($fields); $i++) {

				$params .= $fields[$i].'=:'.$fields[$i].',';
			}

			$sql = "update $this->table set ".substr($params, 0, -1)." where ".$fields[0].' = :'.$fields[0];
		}

		try {

			$qry = $this->pdo->prepare($sql);

			$commit = $qry->execute($values);

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("up: " . $e->getMessage());
		}
	}

	function delete($value, $operator=null, $column=null) {

		$v = isset($column) ? $column : ($operator ?: $value);
		$o = isset($column) ? $operator : '=';
		$c = isset($column) || isset($operator) ? $value : 'id';

		$sql = "delete from $this->table where $c $o ?";

		try {

			$qry = $this->pdo->prepare($sql);
			$qry->bindParam(1, $v);

			$commit = $qry->execute();

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("del: " . $e->getMessage());
		}
	}

	function truncate() {

		$sql = "truncate $this->table";

		try {

			$qry = $this->pdo->prepare($sql);

			$commit = $qry->execute();

			return $commit ? true : false;

		} catch (PDOException $e) {

			die("truncate: " . $e->getMessage());
		}
	}

}