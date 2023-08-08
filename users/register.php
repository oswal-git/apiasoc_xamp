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

        $user = new User();
        foreach ($data as $key => $value) {
            $user->$key = $value;
        }

        $auth = new Auth();

        Helper::writeLog('getUserByAsociationUsername', '');
        $user->getUserByAsociationUsername();

        if (Globals::getError() != '') {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 0) {
            Globals::updateResponse(400, 'There is already a user with this name for this asociation', 'There is already a user with this name for this asociation.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        Helper::writeLog('getAsociationById', '');
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

        Helper::writeLog('Crear profile', '');
        $user->profile_user = "asociado";
        $user->status_user = "activo";

        if ($user->createProfile()) {
            return true;
        } elseif (Globals::getResult()['records_inserted'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $data_user = array();
        $user->id_user = Globals::getResult()['last_insertId'];
        if ($user->getDataUserById()) {
            return true;
        }
        $data_user = array();
        foreach ($user as $key => $value) {
            if ($key !== 'password_user' && $key !== 'question_user' && $key !== 'answer_user') {
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
        } else {
            Globals::updateResponse(400, 'Missing asociation', 'Missing asociation. Please, contact with the association manager.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
        return false;

    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();