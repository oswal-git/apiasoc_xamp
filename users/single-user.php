<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\User;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = json_decode(file_get_contents("php://input"), true);

        $user = new User();

        $user->id_user = $data->id_user;

        if ($user->getDataUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $auth = new Auth();
        $asoc = new Asoc();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $token = $matches[1];
        if (!$token) {
            // No token was able to be extracted from the authorization header
            Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($token !== $user->token_user) {
            Globals::updateResponse(400, 'Token not match', 'Token not match', basename(__FILE__, ".php"), __FUNCTION__);
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

        if ($user->id_user === $auth->id_user) {
            // user query is himself
        } elseif ($auth->profile_user === 'superadmin') {
            // power
        } elseif (($auth->profile_user === 'admin') && ($auth->id_asociation_user === $user->id_asociation_user)) {
            // partial power
        } else {
            Globals::updateResponse(400, 'User not authorized to modify image', 'User not authorized to modify image.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $data_user = array();
        foreach ($user as $key => $value) {
            if ($key !== 'password_user') {
                $data_user["$key"] = $value;
            }
        }

        if ($user->id_asociation_user > 0) {
            $asoc->id_asociation = $user->id_asociation_user;
            if ($asoc->getAsociationById()) {
                return true;
            }
            $data_asoc = array();
            foreach ($asoc as $key => $value) {
                $data_asoc["$key"] = $value;
            }
            $result = array(
                'data_user' => $data_user,
                'data_asoc' => $data_asoc,
            );
        } else if ($auth->profile_user !== 'superadmin') {
            Globals::updateResponse(400, 'Missing asociation', 'Missing asociation. Please, contact with the association manager.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } else {
            $result = array(
                'data_user' => $data_user,
                'data_asoc' => null,
            );
        }

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
        return false;

    } else {
        Globals::updateResponse(500, 'Page not found', 'Page not found', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
        return true;
    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();