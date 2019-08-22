<?php
class Conexao {

	const DB_HOST   = 'localhost';
	const DB_DRIVER = 'mysql';
	const DB_NAME   = 'loja';
	const DB_USER   = 'root';
	const DB_PASS   = '';

	private static $conexao;

	public static function conectar() {

		try {

			if (!isset(self::$conexao)){

				self::$conexao = new PDO(self::DB_DRIVER . ':host=' . self::DB_HOST . ';dbname=' . self::DB_NAME, self::DB_USER, self::DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
				self::$conexao->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

			}

		} catch (PDOException $e) {

			echo $e->getMessage();

		}

		return self::$conexao;

	}

}