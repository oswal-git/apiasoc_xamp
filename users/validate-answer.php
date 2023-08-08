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

        Helper::writeLog('data', $data);

        $in_answer = $auth->answer_user;

        $auth->getUserByUsernameAndAsociationIdAndQuestion();

        if (Globals::getError() != '') {
            Helper::writeLog('getUserByUsernameAndAsociationIdAndQuestion', 'error');
            return true;
        } elseif (Globals::getResult()['num_records'] === 0) {
            Helper::writeLog('getUserByUsernameAndAsociationIdAndQuestion', 'CERO');
            Globals::updateResponse(400, 'User not found', 'User not found', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif (Globals::getResult()['num_records'] > 1) {
            Helper::writeLog('getUserByUsernameAndAsociationIdAndQuestion', 'MÁS DE UNO');
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($auth->profile_user === 'superadmin') {
            Helper::writeLog('profile_user', $auth->profile_user);
            Globals::updateResponse(400, 'Operación no permitida', 'Operación no permitida', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif (in_array($auth->profile_user, array('admin', 'editor'))) {
            Helper::writeLog('profile_user', $auth->profile_user);
            Globals::updateResponse(400, 'Recuperar la clave mediante email', 'Recuperar la clave mediante email', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $encode_pass = hash('sha256', $in_answer . $_ENV['MAGIC_SEED']);
        if ($encode_pass !== $auth->answer_user) {
            Helper::writeLog('encode_pass', $encode_pass);
            Helper::writeLog('answer_user', $auth->answer_user);
            // echo "encode_pass: " . $encode_pass . "\n";
            // echo "answer_user: " . $auth->answer_user . "\n";
            Globals::updateResponse(400, 'Missmatch answer', 'Answer not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($auth->generatePassword()) {
            return true;
        }

        $new_password = $auth->password_user;
        $auth->recover_password_user = 1;
        $auth->password_user = hash('sha256', $new_password . $_ENV['MAGIC_SEED']);

        if ($auth->updatePassword()) {
            return true;
        }

        $result = array(
            'password_user' => $new_password,
            'avatar_user' => $auth->avatar_user,
        );

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