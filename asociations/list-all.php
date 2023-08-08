<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = json_decode(file_get_contents("php://input"), true);

        Helper::writeLog("list-all", 'auth');

        $auth = new Auth();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        if (!preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            // return true;
        } else {

            $token = $matches[1];
            if (!$token) {
                // No token was able to be extracted from the authorization header
                Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            if ($auth->validateTokenJwt($token)) {
                return true;
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

        $asoc = new Asoc();
        switch (true) {
        case $auth->profile_user === 'superadmin':
            if ($asoc->getAllAsociations()) {
                Helper::writeLog(' Globals::getResult() -> superadmin', Globals::getResult());
                return true;
            }
            break;
        case $auth->profile_user === 'admin' && $auth->id_asociation_user > 0:
            $asoc->id_asociation = $auth->id_asociation_user;
            if ($asoc->getAsociationById()) {
                Helper::writeLog(' Globals::getResult() -> admin', Globals::getResult());
                return true;
            }
            break;
        default:
            if ($asoc->getListAsociations()) {
                Helper::writeLog(' Globals::getResult() -> list', Globals::getResult());
                return true;
            }
            break;
        }
        Helper::writeLog(' Globals::getResult()', Globals::getResult());

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