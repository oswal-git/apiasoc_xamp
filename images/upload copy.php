<?php
require_once "../config/bootstrap.php";
// require_once realpath("../vendor/samayo/bulletproof/src/bulletproof.php");

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\User;
use Bulletproof\Image;

function evaluate(&$data) {

    // Helper::writeLog('$_POST', $_POST);
    // Helper::writeLog('$_FILES', $_FILES);
    // Helper::writeLog('$_FILES file', $_FILES['file']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        try {
            $auth = new Auth();
            $user = new User();

            // $headers = Helper::getAuthorizationHeader();
            // Helper::writeLog('headers', $headers);

            // if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            //     Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            //     return true;
            // }

            // $token = $matches[1];

            $data['token'] = $_POST['token'];
            Helper::writeLog('$token', $data['token']);
            if (!$data['token']) {
                // No token was able to be extracted from the authorization header
                Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if ($auth->validateTokenJwt($data['token'])) {
                return true;
            }

            $result = (object) Globals::getResult();
            Helper::writeLog('gettype $result 2', gettype($result));
            Helper::writeLog(' $result->data', $result->data);
            Helper::writeLog(' $result->data->id_user', $result->data->id_user);

            $data['user_id'] = $_POST['user_id'];
            $user->id_user = $data['user_id'];
            if ($user->getDataUserById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $auth->id_user = $result->data->id_user;
            if ($auth->getDataUserById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            } elseif ($data['token'] !== $auth->token_user) {
                Globals::updateResponse(400, 'Token not match', 'Token not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if ($data['user_id'] === $result->data->id_user) {
                // user modifies his own avatar
            } elseif ($auth->profile_user === 'superadmin') {
                // power
            } elseif (($auth->profile_user === 'admin') && ($auth->id_asociation_user === $user->id_asociation_user)) {
                // partial power
            } else {
                Globals::updateResponse(400, 'User not authorized to modify image', 'User not authorized to modify image.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            // $data['file_name'] = $_FILES['file']['name'];
            $data['file_name'] = $_FILES['file']['name'];
            $data['file_type'] = $_FILES['file']['type'];
            $data['module'] = $_POST['module'];
            $data['prefix'] = $_POST['prefix'];
            $data['ext'] = pathinfo($data['file_name'], PATHINFO_EXTENSION);
            $base_name = basename($data['file_name'], '.' . $data['ext']);
            $data['base_name'] = preg_replace('/[^A-Za-z0-9\-]/', '', $base_name);

            //target folder
            $target_path = Globals::getDirFiles() . "uploads" . DIRECTORY_SEPARATOR . $data['module'] . DIRECTORY_SEPARATOR . $data['prefix'];
            $data['target_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_path);
            $url_path = Globals::getUrlFiles() . "uploads" . '/' . $data['module'] . '/' . $data['prefix'];
            $data['url_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $url_path);
            //set target file path
            $target_file = $target_path . DIRECTORY_SEPARATOR . basename($data['file_name'], '.' . $data['ext']) . "." . $data['ext'];
            $data['target_file'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_file);
            $url_file = $url_path . URL_SEPARATOR . basename($data['file_name'], '.' . $data['ext']) . "." . $data['ext'];
            $data['url_file'] = str_replace(array('/', '\\'), URL_SEPARATOR, $url_file);

            Helper::writeLog('$file_name', $data['file_name']);
            Helper::writeLog('$file_type', $data['file_type']);
            Helper::writeLog('basename', $data['base_name']);
            Helper::writeLog('$ext', $data['ext']);
            Helper::writeLog('$target_path clean', $data['target_path']);
            Helper::writeLog('$url_path clean', $data['url_path']);
            Helper::writeLog('$target_file', $data['target_file']);
            Helper::writeLog('$url_file', $data['url_file']);

            Helper::writeLog('$data user_id', $data['user_id']);
            Helper::writeLog('$module', $data['module']);
            Helper::writeLog('$prefix', $data['prefix']);

            // define allowed mime types to upload
            $allowed_types = array('image/jpg', 'image/png', 'image/jpeg');
            // if (!in_array($data['file_type'], $allowed_types)) {
            //     Globals::updateResponse(400, 'Type ' . $data['file_type'] . ' not allowed', 'Type ' . $data['file_type'] . ' not allowed.', basename(__FILE__, ".php"), __FUNCTION__);
            //     return true;
            // }

            if (is_dir($data['target_path'])) {
                if (file_exists($data['target_path'])) {
                    $di = new RecursiveDirectoryIterator($data['target_path'], FilesystemIterator::SKIP_DOTS);
                    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($ri as $file) {
                        Helper::writeLog('delete $files', $file);
                        $file->isDir() ? rmdir($file) : unlink($file);
                    }
                }

            } else {
                mkdir($data['target_path'], 0777, true);
            }

            if (move_uploaded_file($_FILES['file']['tmp_name'], $data['target_file'])) {
                // OK
            } else {
                Globals::updateResponse(400, 'File upload failed. Please try again.', 'File upload failed. Please try again.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $user->avatar_user = $data['url_file'];
            if ($user->updateAvatar()) {
                return true;
            }

            $result = array(
                'url' => $data['url_file'],
                'dir' => $data['target_file'],
            );

            Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
            return false;

        } catch (\Exception $e) {
            Globals::updateResponse(500, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
            return true;
        }

    } else {
        Globals::updateResponse(500, 'Page not found', 'Page not found', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
        return true;
    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();