<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\Notifications;
use Apiasoc\Classes\Models\User;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = json_decode(file_get_contents("php://input"), true);

        Helper::writeLog('list-all: $_GET', $_GET);

        $auth = new Auth();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        $loged = false;

        if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } else {

            $token = $matches[1];
            if (!$token) {
                // No token was able to be extracted from the authorization header
                Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            if ($auth->validateTokenJwt($token)) {
                if (Globals::getError() !== 'Expired token') {
                    return true;
                }
            }

            $result = (object) Globals::getResult();
            Helper::writeLog('gettype $result 2', gettype($result));
            Helper::writeLog(' $result->data', $result->data);
            Helper::writeLog(' $result->data->id_user', $result->data->id_user);

            $auth->id_user = $result->data->id_user;
            if ($auth->getDataUserById()) {
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
        }

        $call = "CALL list_notifications_user(?)";

        $notifications = new Notifications();

        $notifications->id_user_notifications = $auth->id_user;
        if ($notifications->listNotificationsPendingByUser($call)) {
            return true;
        }

        Helper::writeLog(' Globals::getResult()', Globals::getResult());
        $listArticles = Globals::getResult();

        $max_date = '';
        $listIds = '';
        if (count($listArticles['records']) > 0) {

            Helper::writeLog('$listArticles["records"]  count:', count($listArticles['records']));
            $listIds = '(';
            for ($i = 0; $i < count($listArticles['records']); ++$i) {
                Helper::writeLog('$listArticles["records"]  i:', $i);
                if ($listArticles['records'][$i]['date_notification_article'] > $max_date) {
                    $max_date = $listArticles['records'][$i]['date_notification_article'];
                }
                if ($i < count($listArticles['records']) - 1) {
                    $listIds .= $listArticles['records'][$i]['id_article'] . ', ';
                    Helper::writeLog('n  listIds', $listIds);

                } else {
                    $listIds .= $listArticles['records'][$i]['id_article'] . ')';
                    Helper::writeLog('last  listIds', $listIds);
                }
            }

            $auth->date_last_notification_user = $max_date == '' ? date("Y-m-d H:i:s", time()) : $max_date;

            Helper::writeLog('  listIds', $listIds);
            Helper::writeLog('  max_date', $max_date);
            Helper::writeLog('  auth->date_last_notification_user', $auth->date_last_notification_user);

            if ($auth->updateDateLastNotificationUser($max_date)) {
                return true;
            } elseif (Globals::getResult()['records_update'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User not match for notification', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $user = new User();
            $user->id_user = $auth->id_user;

            if ($user->getDateUpdatedUserById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $listArticles['date_updated_user'] = $user->date_updated_user;
        }

        // Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__);

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $listArticles);
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