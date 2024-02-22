<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

/**
 *
 */
class Notifications extends Mysql {

    public $id_user_notification_user;
    public $id_user_notifications;
    public $id_asociation_notifications;
    public $id_article_notifications;
    public $state_notifications;
    public $date_updated_notifications;
    public $date_created_notifications;
    public $date_expired_notifications;

    public function __construct() {
        // echo "Create Notification\n";
        parent::__construct();
    }

    public function getNotificationById() {

        $arrData = array(
            $this->id_asociation_notifications,
            $this->id_article_notifications,
        );

        $sql = "SELECT	  n.id_asociation_notifications
						, n.id_article_notifications
						, n.state_notifications
						, n.date_updated_notifications
						, n.date_created_notifications
						, n.date_expired_notifications
                FROM notifications n
                WHERE n.id_asociation_notifications = ?
                  AND n.id_article_notifications = ?;";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
        if (Globals::getResult()['num_records'] == 1) {
            $this->fillAsoc(Globals::getResult()['records'][0]);
            return $response;
        }
        if (Globals::getResult()['num_records'] == 0) {
            // Globals::updateResponse(404, 'Record not found', 'Record not found', basename(__FILE__, ".php"), __FUNCTION__);
            return $response;
        }
        if (Globals::getResult()['num_records'] > 1) {
            Globals::updateResponse(404, 'Duplicate record', 'Duplicate record', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
    }

    public function getNotificationsByAsociation() {

        $sql = "SELECT	  n.id_asociation_notifications
						, n.id_article_notifications
						, n.state_notifications
						, n.date_updated_notifications
						, n.date_created_notifications
						, n.date_expired_notifications
                        , nu.id_user_notification_user
                        , nu.date_created_notification_user
                FROM notifications n
                LEFT OUTER JOIN notification_user nu
                  ON (    ( n.id_asociation_notifications = nu.id_asociation_notifications_user )
                      AND ( n.id_article_notifications = nu.id_article_notifications_user       ))
                WHERE n.id_asociation_notifications = ?
                ORDER BY n.date_expired_notifications ASC;";

        $arrData = array(
            $this->id_asociation_notifications,
        );

        $response = $this->getAll($sql, $arrData);

        return $response;

    }

    public function listNotificationsPendingByUser($call) {

        $arrData = array(
            $this->id_user_notifications,
        );

        $response = $this->callProcedure($call, $arrData);

        return $response;

    }

    public function getAllNotificationsForUser() {

        $sql = "SELECT	  n.id_asociation_notifications
						, n.id_article_notifications
						, n.state_notifications
						, n.date_updated_notifications
						, n.date_created_notifications
						, n.date_expired_notifications
                        , a.title_article
                        , a.abstract_article
                        , nu.id_user_notification_user
                        , nu.date_created_notification_user
                FROM notifications n
                LEFT OUTER JOIN notification_user nu
                  ON (    ( n.id_asociation_notifications = nu.id_asociation_notifications_user )
                      AND ( n.id_article_notifications = nu.id_article_notifications_user       )
                      AND ( nu.id_user_notification_user = ?                                    ))
                LEFT OUTER JOIN articles a
                  ON (    ( n.id_asociation_notifications = a.id_asociation_article )
                      AND ( n.id_article_notifications = a.id_article               ))
                WHERE n.id_asociation_notifications IN ( ?, 999999999)
                --   AND nu.id_user_notification_user = ?
                  AND n.date_expired_notifications >= current_timestamp()
                  AND  nu.id_user_notification_user IS NULL
                ORDER BY n.date_expired_notifications ASC;";

        $arrData = array(
            $this->id_asociation_notifications,
            $this->id_user_notification_user,
        );

        $response = $this->getAll($sql, $arrData);

        return $response;

    }

    public function fillAsoc($record) {
        foreach ($record as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function updateNotification() {
        $sql = "UPDATE notifications
                SET   date_expired_notifications = addtime(current_timestamp(),'1 0:0:0')
                WHERE id_asociation_notifications = ?
                  AND id_article_notifications = ?;";

        $arrDatos = array(
            $this->id_asociation_notifications
            , $this->id_article_notifications,
        );

        Helper::writeLog('$arrDatos', $arrDatos);

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function deleteNotification() {
        $sql = "DELETE FROM notifications
                WHERE id_asociation_notifications = ?
                  AND id_article_notifications = ?;";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->id_asociation_notifications,
            $this->id_article_notifications,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function createNotification() {

        $sql = "INSERT INTO notifications (
                                      id_asociation_notifications
                                    , id_article_notifications
                                    )
                        VALUES (?" . str_repeat(', ?', 1) . ")";

        Helper::writeLog('$sql', $sql);

        $arrDatos = array(
            $this->id_asociation_notifications
            , $this->id_article_notifications,
        );

        Helper::writeLog('$arrDatos', $arrDatos);

        $resUpdate = $this->insert($sql, $arrDatos);
        return $resUpdate;
    }

}