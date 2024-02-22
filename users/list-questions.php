<?php

require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\User;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Helper::writeLog('_GET', $_GET);
        // json_decode -> no lleva nada
        // $data = json_decode(file_get_contents("php://input"), true);
        // Helper::writeLog('data json_decode', $data);
        $data = $_GET;
        Helper::writeLog('data _GET', $data);

        $user = new User();

        foreach ($data as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }

        // Helper::writeLog("list-questions", $user);

        if ($user->getAllQuestionByUsernameAndAsociationId()) {
            Helper::writeLog(' Globals::getResult() -> superadmin', Globals::getResult());
            return true;
        } elseif (Globals::getResult()['num_records'] === 0) {
            Globals::updateResponse(400, 'Non questions found', 'Non questions found for this user and asociation.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

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