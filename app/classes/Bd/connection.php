<?php
namespace Apiasoc\Classes\Bd;

use Apiasoc\Classes\Globals;
use PDO;

class Connection {
    private static $instance;
    private $connection;

    public function __construct() {
        $stringConnection = "mysql:host=" . Globals::getDbHost() . "; dbname=" . Globals::getDbName() . "; " . Globals::getDbCharset();

        try {
            $opt = array(
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            );

            $this->connection = new PDO($stringConnection, Globals::getDbUser(), Globals::getDbPassword(), $opt);

            $strQuery = "SET NAMES 'utf8'";
            $query = $this->connection->prepare($strQuery);
            $resQuery = $query->execute();

            // this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_FOUND_ROWS => true);
            //echo "Conexión exitosa<br>";
        } catch (\Exception $e) {
            $this->connection = 'Errror de conexión';
            // echo "ERROR: " . $e->getMessage() . "<br>";00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000...........................................................................................................................................................................................................................
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Connection();
        }
        return self::$instance;
    }

    public function connection() {
        return $this->connection;
    }

    // Prevenir la clonación del objeto Singleton
    private function __clone() {}

    // Prevenir la deserialización del objeto Singleton
    function __wakeup() {}

}