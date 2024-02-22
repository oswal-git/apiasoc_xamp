<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\User;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        Helper::writeLog('_GET', $_GET);
        $data = json_decode(file_get_contents("php://input"), true);
        $data = $_GET;
        Helper::writeLog('data', $data);

        $auth = new Auth();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', (string) $headers);

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

        if ($auth->profile_user === 'superadmin') {
            // power
        } else {
            Globals::updateResponse(400, 'User not authorized to delete asociation', 'User not authorized to delete asociation.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $asoc = new Asoc();

        foreach ($data as $key => $value) {
            if (property_exists($asoc, $key)) {
                $asoc->$key = $value;
            }
        }

        $user = new User();
        $user->id_asociation_user = (int) $asoc->id_asociation;

        if ($user->getAllByIdAsociations()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 0) {
            Globals::updateResponse(400, 'There are users of this asociation', 'There are users of this asociation', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($asoc->getAsociationById()) {
            return true;
        }

        Helper::writeLog('$data[date_updated_asociation]', $data['date_updated_asociation']);
        Helper::writeLog('gettype $data[date_updated_asociation]', gettype($data['date_updated_asociation']));
        Helper::writeLog('$asoc->date_updated_asociation', $asoc->date_updated_asociation);
        Helper::writeLog('gettype $asoc->date_updated_asociation', gettype($asoc->date_updated_asociation));

        if (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Asociation id not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($data['date_updated_asociation'] !== $asoc->date_updated_asociation) {
            Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please. Logout and login again.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $logo = $asoc->logo_asociation;
        Helper::writeLog('$logo', $logo);
        if ($logo !== '') {
            $pos = strpos($logo, Globals::getUrlUploads());
            if ($pos === false) {
                Globals::updateResponse(400, 'Url files not found', 'logo not found', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            Helper::writeLog('$pos', $pos);
            $rest = substr($logo, $pos + strlen(Globals::getUrlUploads()));
            $path = Globals::getDirFiles() . $rest;
            $target_path = dirname($path);

            try {
                if (is_dir($target_path)) {
                    if (file_exists($target_path)) {
                        $di = new RecursiveDirectoryIterator($target_path, FilesystemIterator::SKIP_DOTS);
                        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                        foreach ($ri as $file) {
                            Helper::writeLog('delete $files', $file);
                            $file->isDir() ? rmdir($file) : unlink($file);
                        }
                    }
                }
            } catch (\Exception $e) {
                Globals::updateResponse(400, $e, 'Error deleting logo', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
        }

        if ($asoc->deleteAsociation()) {
            return true;
        } elseif ((int) Globals::getResult()['records_deleted'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Asociation not match for delete', basename(__FILE__, ".php"), __FUNCTION__);
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