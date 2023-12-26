<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $auth = new Auth();
        $asoc = new Asoc();

        foreach ($data as $key => $value) {
            $auth->$key = $value;
        }

        $in_password = $auth->password_user;

        $auth->getUserByEmail();

        if (Globals::getError() != '') {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $encode_pass = hash('sha256', $in_password . $_ENV['MAGIC_SEED']);
        if ($encode_pass !== $auth->password_user) {
            echo "encode_pass: " . $encode_pass . "\n";
            echo "password_user: " . $auth->password_user . "\n";
            Globals::updateResponse(400, 'Missmatch password', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $auth->token_exp_user = time() + 60 * 60 * 6; // 6 hours
        $auth->token_user = $auth->createTokenJwt();
        // echo "token: " . $auth->token_user . "\n";
        if ($auth->updateToken()) {
            return true;
        }

        if ($auth->getDataUserById()) {
            return true;
        }
        $data_user = array();
        foreach ($auth as $key => $value) {
            if ($key !== 'password_user') {
                $data_user["$key"] = $value;
            }
        }

        if ($auth->id_asociation_user > 0) {
            $asoc->id_asociation = $auth->id_asociation_user;
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