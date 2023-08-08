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
        Helper::writeLog('data', $data);

        $user_old = new User();
        $user = new User();
        $auth = new Auth();
        $asoc = new Asoc();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        if (!preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
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

        $user->id_user = $data['id_user'];

        if ($user->getDataUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($data['date_updated_user'] !== $user->date_updated_user) {
            Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please. Logout and login again.', basename(__FILE__, ".php"), __FUNCTION__);
            Helper::writeLog('gettype $data[date_updated_user]', gettype($data['date_updated_user']));
            Helper::writeLog('$data[date_updated_user]', $data['date_updated_user']);
            Helper::writeLog('gettype $user->date_updated_user', gettype($user->date_updated_user));
            Helper::writeLog('$user->date_updated_user', $user->date_updated_user);
            return true;
        }

        if ($auth->profile_user === 'superadmin') {
            // power
        } elseif (($auth->profile_user === 'admin') && ($auth->id_asociation_user === $user->id_asociation_user)) {
            if ($user->profile_user === 'superadmin') {
                $user->profile_user = 'asociado';
            }
        } else {
            Globals::updateResponse(400, 'User not authorized to create user', 'User not authorized to create user.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        Helper::copyClass($user, $user_old);

        foreach ($data as $key => $value) {
            $user->$key = $value;
        }

        if ($user->user_name_user !== $user_old->user_name_user) {
            $res = $user->existUserByAsociationUsername();
            if ($res['status']) {
                return true;
            }
            if ($res['exist_user']) {
                Globals::updateResponse(400, 'This user name has already being used in this asociation', 'This user name has already being used in this asociation', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
        }

        if ($user->email_user !== $user_old->email_user) {
            if ($user->email_user !== '') {
                $res = $user->existUserByEmail();
                if ($res['status']) {
                    return true;
                }
                if ($res['exist_user']) {
                    Globals::updateResponse(400, 'There is already an user with this email', 'There is already an user with this email', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
            } else {
                $user->profile_user = 'asociado';
            }
        }

        if ($user->updateUser()) {
            return true;
        } elseif (Globals::getResult()['records_update'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($user->getDataUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record.', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
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
        } else if ($user->profile_user !== 'superadmin') {
            Helper::writeLog(' $user->profile_user', $user->profile_user);
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

    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();