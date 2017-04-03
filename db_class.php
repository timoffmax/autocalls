<?php
/**
* DB
*/
class DB
{
    //Your DB parametrs
    const HOST = '';
    const USER = '';
    const PASSWORD = '';
    const DBNAME = '';
    const CHARSET = 'utf8';

    private static $instance = null;

    private function __construct() {}
    private function __clone() {}

    static public function getInstance() {
        if(is_null(self::$instance)) {
            $opt = array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
            );

            $dsn = 'mysql:host='.self::HOST.';dbname='.self::DBNAME.';charset='.self::CHARSET;
            self::$instance = new PDO($dsn, self::USER, self::PASSWORD, $opt);
        }
        return self::$instance;
    }
}
