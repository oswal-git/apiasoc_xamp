<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\Notifications;
use Apiasoc\Classes\Models\NotificationUser;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = json_decode(file_get_contents("php://input"), true);

        Helper::writeLog('pending: $_GET', $_GET);

        // $category = isset($_GET['category_article']) ? $_GET['category_article'] : '';
        // Helper::writeLog('category', $category);

        $auth = new Auth();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        $loged = false;

        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
            if ($token) {
                if (!$auth->validateTokenJwt($token)) {
                    $result = (object) Globals::getResult();
                    Helper::writeLog('gettype $result 2', gettype($result));
                    Helper::writeLog(' $result->data', $result->data);
                    Helper::writeLog(' $result->data->id_user', $result->data->id_user);

                    $auth->id_user = $result->data->id_user;
                    if ($auth->getUserById()) {
                        switch (Globals::getResult()) {
                        case 'Record not found':
                            Globals::updateMessageResponse('User connected not exist');
                            break;
                        case 'Duplicate record':
                            Globals::updateMessageResponse('User connected not exist');
                            break;
                        default:
                            break;
                        }
                        return true;
                    }

                    if ($auth->token_user !== $token) {
                        Globals::updateResponse(400, 'Missmatch token', 'Missmatch token. Reconnecte, please', basename(__FILE__, ".php"), __FUNCTION__);
                    }
                    $loged = true;
                } else {
                    Globals::updateResponse(400, 'Token not valid in request', 'Token not valid in request', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
            } else {
                Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

        } else {
            Globals::updateResponse(400, 'Token not match in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $notifications = new Notifications();
        $notifications->id_asociation_notifications = $auth->id_asociation_user;
        $notifications->id_user_notification_user = $auth->id_user;
        if ($notifications->getAllNotificationsForUser()) {
            Helper::writeLog(' Globals::getResult() -> superadmin', Globals::getResult());
            return true;
        }
        Helper::writeLog(' Globals::getResult()', Globals::getResult());

        $listNotifications = Globals::getResult()['records'];

        Helper::writeLog(' count($listNotifications)', count($listNotifications));
        $notification_user = new NotificationUser();
        if (count($listNotifications) > 0) {
            $notification_user->initTransaccion();

            for ($i = 0; $i < count($listNotifications); ++$i) {
                $notification_user->id_asociation_notifications_user = $listNotifications[$i]['id_asociation_notifications'];
                $notification_user->id_article_notifications_user = $listNotifications[$i]['id_article_notifications'];
                $notification_user->id_user_notification_user = $auth->id_user;
                if ($notification_user->createNotificationUser()) {
                    $notification_user->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['records_inserted'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $notification_user->abortTransaccion();
                    return true;
                }

            }

            $notification_user->endTransaccion();
        }

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $listNotifications);
        return false;

    } else {
        Globals::updateResponse(500, 'Page not found', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
        return true;
    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();