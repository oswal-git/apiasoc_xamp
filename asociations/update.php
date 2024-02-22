<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        Helper::writeLog('data', $data);

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

        $asoc->id_asociation = $data['id_asociation'];
        if ($auth->profile_user === 'superadmin') {
            // power
        } elseif (($auth->profile_user === 'admin') && ((int) $auth->id_asociation_user === (int) $asoc->id_asociation)) {
            // less power by can
        } else {
            Globals::updateResponse(400, 'User not authorized to create user', 'User not authorized to create user.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($asoc->getAsociationById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($data['date_updated_asociation'] !== $asoc->date_updated_asociation) {
            Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please. Logout and login again.', basename(__FILE__, ".php"), __FUNCTION__);
            Helper::writeLog('gettype $data[date_updated_asociation]', gettype($data['date_updated_asociation']));
            Helper::writeLog('$data[date_updated_asociation]', $data['date_updated_asociation']);
            Helper::writeLog('gettype $user->date_updated_asociation', gettype($asoc->date_updated_asociation));
            Helper::writeLog('$user->date_updated_asociation', $asoc->date_updated_asociation);
            return true;
        }

        foreach ($data as $key => $value) {
            if (property_exists($asoc, $key)) {
                $asoc->$key = $value;
            }
        }

        if ($asoc->updateAsociation()) {
            return true;
        } elseif (Globals::getResult()['records_update'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($asoc->getAsociationById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record.', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        // $data_asoc = array();
        // foreach ($asoc as $key => $value) {
        //     $data_asoc["$key"] = $value;
        // }

        // $result = array(
        //     'data_asoc' => $data_asoc,
        // );

        // Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
        return false;

    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();