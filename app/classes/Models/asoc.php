<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

/**
 *
 */
class Asoc extends Mysql {

    public $id_asociation;
    public $long_name_asociation;
    public $short_name_asociation;
    public $logo_asociation;
    public $email_asociation;
    public $name_contact_asociation;
    public $phone_asociation;
    public $date_deleted_asociation;
    public $date_created_asociation;
    public $date_updated_asociation;

    public function __construct() {
        // echo "Create Asoc\n";
        parent::__construct();
    }

    public function getAsociationById() {

        $arrData = array(
            $this->id_asociation,
        );

        $sql = "SELECT	  a.id_asociation
						, a.long_name_asociation
						, a.short_name_asociation
						, a.logo_asociation
						, a.email_asociation
						, a.name_contact_asociation
						, a.phone_asociation
						, COALESCE(a.date_deleted_asociation,'') as date_deleted_asociation
						, a.date_created_asociation
						, COALESCE(a.date_updated_asociation,'') as date_updated_asociation
                FROM asociations a
                WHERE a.id_asociation = ?;";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
        if (Globals::getResult()['num_records'] == 1) {
            $this->fillAsoc(Globals::getResult()['records'][0]);
            return $response;
        }
        if (Globals::getResult()['num_records'] == 0) {
            Globals::updateResponse(404, 'Record not found', 'Record not found', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        if (Globals::getResult()['num_records'] > 1) {
            Globals::updateResponse(404, 'Duplicate record', 'Duplicate record', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
    }

    public function getListAsociations() {
        $sql = "SELECT 	  a.id_asociation
                        , a.long_name_asociation
                        , a.short_name_asociation
                        , a.logo_asociation
						, a.email_asociation
						, a.name_contact_asociation
						, a.phone_asociation
                FROM asociations a
                ORDER BY a.long_name_asociation ASC;";

        $response = $this->getAll($sql);

        return $response;

    }

    public function getAllAsociations() {
        $sql = "SELECT 	  a.id_asociation
                        , a.long_name_asociation
                        , a.short_name_asociation
                        , a.logo_asociation
                        , a.email_asociation
                        , a.name_contact_asociation
                        , a.phone_asociation
						, COALESCE(a.date_deleted_asociation,'') as date_deleted_asociation
						, a.date_created_asociation
						, COALESCE(a.date_updated_asociation,'') as date_updated_asociation
                FROM asociations a
                ORDER BY a.long_name_asociation ASC;";

        $response = $this->getAll($sql);

        return $response;

    }

    public function fillAsoc($record) {
        foreach ($record as $key => $value) {
            $this->$key = $value;
        }
    }

    public function deleteAsociation() {
        $sql = "DELETE FROM asociations
                WHERE id_asociation = ?
                  AND COALESCE(date_updated_asociation,'') = ? ";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->id_asociation,
            $this->date_updated_asociation,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function createAsociation() {
        $sql = "INSERT INTO asociations (
                                      long_name_asociation
                                    , short_name_asociation
                                    , email_asociation
                                    , name_contact_asociation
                                    , phone_asociation
                                    )
                        VALUES (?, ?, ?, ?, ?)";

        $arrDatos = array(
            $this->long_name_asociation
            , $this->short_name_asociation
            , $this->email_asociation
            , $this->name_contact_asociation
            , $this->phone_asociation,
        );

        $resUpdate = $this->insert($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateAsociation() {
        $sql = "UPDATE asociations
                SET   long_name_asociation = ?
                    , short_name_asociation = ?
                    , logo_asociation = ?
                    , email_asociation = ?
                    , name_contact_asociation = ?
                    , phone_asociation = ?
                WHERE id_asociation = ?
                  AND COALESCE(date_updated_asociation,'') = ? ";

        $arrDatos = array(
            $this->long_name_asociation
            , $this->short_name_asociation
            , $this->logo_asociation
            , $this->email_asociation
            , $this->name_contact_asociation
            , $this->phone_asociation
            , $this->id_asociation
            , $this->date_updated_asociation,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateLogo() {
        $sql = "UPDATE asociations
                SET logo_asociation = ?
                WHERE id_asociation = ?
                  AND COALESCE(date_updated_asociation,'') = ? ";

        $arrDatos = array(
            $this->logo_asociation
            , $this->id_asociation
            , $this->date_updated_asociation,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function getAsociationByEmail() {

        $arrData = array(
            $this->email_asociation,
        );

        $sql = "SELECT 	  a.id_asociation
                        , a.long_name_asociation
                        , a.short_name_asociation
                        , a.logo_asociation
                        , a.email_asociation
                        , a.name_contact_asociation
                        , a.phone_asociation
                        , COALESCE(a.date_deleted_asociation,'') as date_deleted_asociation
                        , a.date_created_asociation
                        , COALESCE(a.date_updated_asociation,'') as date_updated_asociation
                FROM asociations a
                WHERE a.email_asociation = ?;";

        $response = $this->getAll($sql, $arrData);
        if (Globals::getResult()['num_records'] === 1) {
            $this->fillAsoc(Globals::getResult()['records'][0]);
        }
        return $response;
    }

}