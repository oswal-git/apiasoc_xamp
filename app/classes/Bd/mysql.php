<?php
namespace Apiasoc\Classes\Bd;

use Apiasoc\Classes\Bd\Connection;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use PDO;

// require_once "connection.php";
/**
 *
 */
class Mysql extends Connection {
    private object $db;
    private $strQuery;
    private $arrValues;

    public function __construct() {

        $this->db = new Connection();
        $this->db = $this->db->connection();
    }

    public function insert(string $strQuery, array $arrValues) {
        $this->strQuery = $strQuery;
        $this->arrValues = $arrValues;

        try {
            $insert = $this->db->prepare($this->strQuery);
            $resInsert = $insert->execute($this->arrValues);
            if ($resInsert) {
                $lastInsertId = $this->db->lastInsertId();
                Helper::writeLog("Mysql: insert ok ", $resInsert);
                Helper::writeLog("Mysql: lastInsertId ", $lastInsertId);
                Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, array(
                    "records_inserted" => 1,
                    "last_insertId" => $lastInsertId,
                ));
                return false;
            } else {
                Helper::writeLog("Mysql: insert ko", $resInsert);
                $lastInsertId = 0;
                Globals::updateResponse(400, 'Failed to insert record', 'Failed to insert record', basename(__FILE__, ".php"), __FUNCTION__, array(
                    "records_inserted" => 0,
                    "last_insertId" => $lastInsertId,
                ));
                return true;
            }
        } catch (\PDOException $e) {
            $respuesta = array();
            Globals::updateResponse(404, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

    }

    public function getAll(string $strQuery, array $arrValues = array()) {
        $this->strQuery = $strQuery;
        // if (!empty($arrValues)) {
        $this->arrValues = $arrValues;
        // }

        Helper::writeLog("Mysql: getAll -> strQuery", $strQuery);
        Helper::writeLog("Mysql: getAll -> arrValues", $arrValues);

        try {
            $query = $this->db->prepare($this->strQuery);
            $resQuery = $query->execute($this->arrValues);
            $respuesta = $query->fetchall(PDO::FETCH_ASSOC);
            Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, array(
                "num_records" => count($respuesta),
                "records" => $respuesta,
            ));
            Helper::writeLog("Mysql: getAll -> num_records", count($respuesta));
            Helper::writeLog("Mysql: getAll -> records", $respuesta);
            return false;
        } catch (\PDOException $e) {
            $respuesta = array();
            Globals::updateResponse(404, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

    }

    public function update(string $strQuery, array $arrValues) {

        $this->strQuery = $strQuery;
        $this->arrValues = $arrValues;

        try
        {
            $update = $this->db->prepare($this->strQuery);
            $update->execute($this->arrValues);
            $resUpdate = $update->rowCount();
            Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, array(
                "records_update" => $resUpdate,
            ));
            return false;
        } catch (\PDOException $e) {
            Globals::updateResponse(404, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

    }

    public function delete(string $strQuery, array $arrValues) {
        $this->strQuery = $strQuery;
        $this->arrValues = $arrValues;
        try {
            $delete = $this->db->prepare($this->strQuery);
            $resDelete = $delete->execute($this->arrValues);
            if ($resDelete) {
                Helper::writeLog("Mysql: delete ok ", $delete->rowCount());
                Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, array(
                    "records_deleted" => $resDelete,
                ));
                return false;
            } else {
                Helper::writeLog("Mysql: delete ko", $resDelete);
                Globals::updateResponse(400, 'Failed to delete record', 'Failed to delete record', basename(__FILE__, ".php"), __FUNCTION__, array(
                    "records_deleted" => 0,
                ));
                return true;
            }
        } catch (\PDOException $e) {
            Globals::updateResponse(404, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

    }

    public function initTransaccion() {
        $this->db->beginTransaction();
    }

    public function endTransaccion() {
        $this->db->commit();
    }

    public function abortTransaccion() {
        $this->db->rollBack();
    }
}