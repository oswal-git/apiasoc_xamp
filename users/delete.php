<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
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

        $user = new User();

        foreach ($data as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }
        Helper::writeLog('$data[date_updated_user]', $data['date_updated_user']);
        Helper::writeLog('gettype $data[date_updated_user]', gettype($data['date_updated_user']));
        Helper::writeLog('$user->date_updated_user', $user->date_updated_user);
        Helper::writeLog('gettype $user->date_updated_user', gettype($user->date_updated_user));

        if ($user->getDataUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif (($user->profile_user === 'superadmin') || ($user->profile_user === 'admin')) {
            Globals::updateResponse(400, 'User administrator cannot be deleted', 'User administrator cannot be deleted', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($data['date_updated_user'] !== $user->date_updated_user) {
            Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please. Logout and login again.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($auth->profile_user === 'superadmin') {
            // power
        } elseif (($auth->profile_user === 'admin') && ($auth->id_asociation_user === $user->id_asociation_user)) {
            // partial power
        } else {
            Globals::updateResponse(400, 'User not authorized to create user', 'User not authorized to create user.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($user->token_user !== '' && $user->token_exp_user > time()) {
            Globals::updateResponse(400, 'User to delete has an active session', 'User to delete has an active session', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $avatar = $user->avatar_user;
        Helper::writeLog('$avatar', $avatar);
        Helper::writeLog('getUrlUploads', Globals::getUrlUploads());
        if ($avatar !== '') {
            $pos = strpos($avatar, Globals::getUrlUploads());
            if ($pos === false) {
                Globals::updateResponse(400, 'Url files not found', 'Avatar not found', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            Helper::writeLog('$pos', $pos);
            $rest = substr($avatar, $pos + strlen(Globals::getUrlUploads()));
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
                    rmdir($target_path);
                }
            } catch (\Exception $e) {
                Globals::updateResponse(400, $e, 'Error deleting avatar', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
        }

        if ($user->deleteUser()) {
            return true;
        } elseif ((int) Globals::getResult()['records_deleted'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
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