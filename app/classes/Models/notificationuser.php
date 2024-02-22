<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

/**
 *
 */
class NotificationUser extends Mysql {

    public $id_user_notification_user;
    public $id_asociation_notifications_user;
    public $id_article_notifications_user;
    public $date_created_notification_user;

    public function __construct() {
        // echo "Create Notification\n";
        parent::__construct();
    }

    public function getNotificationUserById() {

        $arrData = array(
            $this->id_user_notification_user,
            $this->id_asociation_notifications_user,
            $this->id_article_notifications_user,
        );

        $sql = "SELECT	  nu.id_user_notification_user
						, nu.id_asociation_notifications_user
						, nu.id_article_notifications_user
						, nu.date_created_notification_user
                FROM notification_user nu
                WHERE nu.id_user_notification_user = ?
                  AND nu.id_asociation_notifications_user = ?
                  AND nu.id_article_notifications_user_user = ?;";

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

    public function fillAsoc($record) {
        foreach ($record as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function deleteNotificationUser() {
        $sql = "DELETE FROM notifications
                WHERE id_user_notification_user = ?
                  AND id_asociation_notifications_user = ?
                  AND id_article_notifications_user_user = ?;";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->id_user_notification_user,
            $this->id_asociation_notifications_user,
            $this->id_article_notifications_user_user,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function createNotificationUser() {

        $sql = "INSERT INTO notification_user (
                                      id_user_notification_user
                                    , id_asociation_notifications_user
                                    , id_article_notifications_user
                                    )
                        VALUES (?" . str_repeat(', ?', 2) . ")";

        Helper::writeLog('$sql', $sql);

        $arrDatos = array(
            $this->id_user_notification_user
            , $this->id_asociation_notifications_user
            , $this->id_article_notifications_user,
        );

        Helper::writeLog('$arrDatos', $arrDatos);

        $resUpdate = $this->insert($sql, $arrDatos);
        return $resUpdate;
    }

}