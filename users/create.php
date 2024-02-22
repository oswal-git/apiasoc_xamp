<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\User;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = json_decode(file_get_contents("php://input"), true);

        $auth = new Auth();
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
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($token !== $auth->token_user) {
            Globals::updateResponse(400, 'Token not match', 'Token not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        // if (!isset($data['id_asociation_user'])) {
        //     $data['id_asociation_user'] = $auth->id_asociation_user;
        // }

        if ($auth->profile_user === 'superadmin') {
            // power
        } elseif (($auth->profile_user === 'admin') && ($auth->id_asociation_user === $data['id_asociation_user'])) {
            // partial power
        } else {
            Globals::updateResponse(400, 'User not authorized to create user', 'User not authorized to create user.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $user = new User();

        $user->email_user = $data['email_user'];
        Helper::writeLog(' $user->email_user', $user->email_user);

        if ($user->email_user == '') {
            $user->getUserByAsociationUsername();
            $error_text = 'user name';
        } else {
            $user->getUserByEmail();
            $error_text = 'email';
        }

        if (Globals::getError() != '') {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 0) {
            Globals::updateResponse(400, `There is already a user with this {$error_text}`, `There is already a user with this {$error_text}`, basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        foreach ($data as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }

        if ($user->id_asociation_user > 0) {
            $asoc = new Asoc();
            $asoc->id_asociation = $user->id_asociation_user;
            if ($asoc->getAsociationById()) {
                return true;
            }
        } else {
            Globals::updateResponse(400, 'There is not asociation selected', 'There is not asociation selected.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($user->userCreate()) {
            return true;
        } elseif (Globals::getResult()['records_inserted'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $user->id_user = Globals::getResult()['last_insertId'];
        if ($user->getDataUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        // Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
        return false;

    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();