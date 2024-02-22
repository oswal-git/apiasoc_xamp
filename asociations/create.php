<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

        if ($auth->profile_user === 'superadmin') {
            // power
        } else {
            Globals::updateResponse(400, 'User not authorized to create Asociation', 'User not authorized to create Asociation.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        Helper::writeLog('data', $data);

        $asoc = new Asoc();

        $asoc->email_asociation = $data['email_asociation'];
        $asoc->getAsociationByEmail();

        if (Globals::getError() != '') {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 0) {
            Globals::updateResponse(400, 'There is already an asociation with this email', 'There is already an asociation with this email.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        foreach ($data as $key => $value) {
            if (property_exists($asoc, $key)) {
                $asoc->$key = $value;
            }
        }

        if ($asoc->createAsociation()) {
            return true;
        } elseif (Globals::getResult()['records_inserted'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Asociation not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $asoc->id_asociation = Globals::getResult()['last_insertId'];
        if ($asoc->getAsociationById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Asociation created not match', basename(__FILE__, ".php"), __FUNCTION__);
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