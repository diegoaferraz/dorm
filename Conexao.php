<?php
class Conexao {

	const DB_HOST    = 'localhost';
	const DB_DRIVER  = 'pgsql';
	const DB_NAME    = 'canalagricola';
	const DB_USER    = 'postgres';
	const DB_PASS    = '';
	const DB_CHARSET = self::DB_DRIVER=='mysql' ? 'charset=utf8;' : '';
	const DB_PORT    = null;

	// const DB_HOST   = 'localhost';
	// const DB_DRIVER = 'mysql';
	// const DB_NAME   = 'loja';
	// const DB_USER   = 'root';
	// const DB_PASS   = '';
	// const DB_PORT   = null;

	private static $conexao;

	public static function conectar() {

		try {

			if (!isset(self::$conexao)){

				self::$conexao = new PDO(self::DB_DRIVER . ':host=' . self::DB_HOST . ';'.(self::DB_PORT ? 'port='.self::DB_PORT.';' : '').self::DB_CHARSET.'dbname=' . self::DB_NAME, self::DB_USER, self::DB_PASS);
				self::$conexao->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

			}

		} catch (PDOException $e) {

			die($e->getMessage());
		}

		return self::$conexao;

	}

}