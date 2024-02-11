<?php
require_once "../config/bootstrap.php";
// require_once realpath("../vendor/samayo/bulletproof/src/bulletproof.php");

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\User;

function evaluate(&$data) {

    Helper::writeLog('$_POST', $_POST);
    if (is_array($_FILES)) {
        Helper::writeLog('$_FILES', $_FILES);
        isset($_FILES['file']) ? Helper::writeLog('$_FILES file', $_FILES['file']) : 'No existe files en $_FILES';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        try {
            $auth = new Auth();
            $user = new User();
            $asoc = new Asoc();
            $article = new Article();

            // $headers = Helper::getAuthorizationHeader();
            // Helper::writeLog('headers', $headers);

            // if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            //     Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            //     return true;
            // }

            // $token = $matches[1];

            // user profile data
            $data['id_user'] = $_POST['id_user'];
            $data['user_name_user'] = $_POST['user_name_user'];
            $data['id_asociation_user'] = $_POST['id_asociation_user'];
            $data['time_notifications_user'] = $_POST['time_notifications_user'];
            $data['language_user'] = $_POST['language_user'];
            $data['date_updated_user'] = $_POST['date_updated_user'];

            // image data
            $data['action'] = $_POST['action'];
            $data['module'] = $_POST['module'];
            $data['prefix'] = $_POST['prefix'];
            $data['date_updated'] = $_POST['date_updated'];
            Helper::writeLog('$action', $data['action']);
            $data['token'] = $_POST['token'];
            Helper::writeLog('$token', $data['token']);
            Helper::writeLog('$date_updated', $data['date_updated']);
            $data['cover'] = '';

            if (!$data['token']) {
                // No token was able to be extracted from the authorization header
                Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if ($auth->validateTokenJwt($data['token'])) {
                if (Globals::getError() !== 'Expired token') {
                    return true;
                }
            }

            $result = (object) Globals::getResult();
            Helper::writeLog('gettype $result', gettype($result));
            Helper::writeLog(' $result->data', $result->data);
            Helper::writeLog(' $result->data->id_user', $result->data->id_user);
            Helper::writeLog('gettype $result->data->id_user', gettype($result->data->id_user));

            $user->id_user = $data['id_user'];
            if ($user->id_user !== $result->data->id_user) {
                Globals::updateResponse(400, 'User not authorized to modify this profile', 'User not authorized to modify this profile.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if ($user->getDataUserById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            } elseif ($data['date_updated'] !== $user->date_updated_user) {
                Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please. Logout and login again.', basename(__FILE__, ".php"), __FUNCTION__);
                Helper::writeLog('gettype $data[date_updated]', gettype($data['date_updated']));
                Helper::writeLog('$data[date_updated]', $data['date_updated']);
                Helper::writeLog('gettype $user->date_updated', gettype($user->date_updated_user));
                Helper::writeLog('$user->date_updated', $user->date_updated_user);
                // return true;
            } elseif ($data['token'] !== $user->token_user) {
                Globals::updateResponse(400, 'Token not match', 'Token not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $user_old = clone $user;

            $user->id_user = $data['id_user'];
            $user->user_name_user = $data['user_name_user'];
            $user->id_asociation_user = $data['id_asociation_user'];
            $user->time_notifications_user = $data['time_notifications_user'];
            $user->language_user = $data['language_user'];
            // $user->date_updated_user = $data['date_updated_user'];

            if ($user_old->id_asociation_user != $user->id_asociation_user || $user_old->user_name_user != $user->user_name_user) {
                $res = $user->existUserByAsociationUsername();
                if ($res['status']) {
                    return true;
                }
                if ($res['exist_user']) {
                    Globals::updateResponse(400, 'This user name has already being used in this asociation', 'This user name has already being used in this asociation', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
            }

            if ((int) $data['id_user'] === $result->data->id_user) {
                Helper::writeLog('user modifies his own avatar', '');
                // user modifies his own avatar
            } elseif ($auth->profile_user === 'superadmin') {
                // power
            } elseif (($auth->profile_user === 'admin') && ($auth->id_asociation_user === $user->id_asociation_user)) {
                // partial power
            } else {
                Globals::updateResponse(400, 'User not authorized to modify avatar', 'User not authorized to modify avatar.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            //target folder
            $target_path = Globals::getDirUploads() . $data['module'] . DIRECTORY_SEPARATOR . $data['prefix'];
            $data['target_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_path);

            Helper::writeLog('$target_path', $data['target_path']);
            Helper::writeLog('$data id', $data['id_user']);
            Helper::writeLog('$module', $data['module']);
            Helper::writeLog('$prefix', $data['prefix']);

            if ($data['action'] !== 'upload' || $data['module'] === 'articles') {
                if (is_dir($data['target_path'])) {
                    if (file_exists($data['target_path'])) {
                        $di = new RecursiveDirectoryIterator($data['target_path'], FilesystemIterator::SKIP_DOTS);
                        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                        foreach ($ri as $file) {
                            Helper::writeLog('read $files', $file);
                            if ($file->isDir()) {
                                Helper::writeLog('delete dir', $file);
                                rmdir($file);
                            } else {
                                Helper::writeLog('delete file', $file);
                                unlink($file);
                            }
                        }
                    }
                }
            }

            Helper::writeLog('After delete file', '');

            $data['file_name'] = $_FILES['file']['name'];
            $data['file_type'] = $_FILES['file']['type'];
            $data['ext'] = pathinfo($data['file_name'], PATHINFO_EXTENSION);
            $base_name = basename($data['file_name'], '.' . $data['ext']);
            $data['base_name'] = preg_replace('/[^A-Za-z0-9\-]/', '', $base_name);
            $chain = Helper::generateChain(6, 'letters');
            $data['name'] = $data['base_name'] . '-' . $chain;

            $url_path = Globals::getUrlUploads() . $data['module'] . '/' . $data['prefix'];
            $data['url_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $url_path);
            //set target file path
            $target_file = $target_path . DIRECTORY_SEPARATOR . $data['name'] . "." . $data['ext'];
            $data['target_file'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_file);
            $url_file = $url_path . URL_SEPARATOR . $data['name'] . "." . $data['ext'];
            $data['url_file'] = str_replace(array('/', '\\'), URL_SEPARATOR, $url_file);

            Helper::writeLog('$chain', $chain);
            Helper::writeLog('$file_name', $data['file_name']);
            Helper::writeLog('$file_type', $data['file_type']);
            Helper::writeLog('basename', $data['base_name']);
            Helper::writeLog('name', $data['name']);
            Helper::writeLog('$ext', $data['ext']);
            Helper::writeLog('$url_path', $data['url_path']);
            Helper::writeLog('$target_file', $data['target_file']);
            Helper::writeLog('$url_file', $data['url_file']);

            // define allowed mime types to upload
            $allowed_types = array('image/jpg', 'image/png', 'image/jpeg', 'application/octet-stream');
            if (!in_array($data['file_type'], $allowed_types)) {
                Globals::updateResponse(400, 'Type ' . $data['file_type'] . ' not allowed', 'Type ' . $data['file_type'] . ' not allowed.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if (!is_dir($data['target_path'])) {
                mkdir($data['target_path'], 0777, true);
            }

            if (move_uploaded_file($_FILES['file']['tmp_name'], $data['target_file'])) {
                // OK
            } else {
                Globals::updateResponse(400, 'File upload failed. Please try again.', 'File upload failed. Please try again.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            // $asoc->logo_asociation = $data['url_file'];

            $user->avatar_user = $data['url_file'];
            if ($user->updateProfileAvatar()) {
                return true;
            } elseif (Globals::getResult()['records_update'] !== 1) {
                Globals::updateResponse(400, 'User not match', 'User not match', basename(__FILE__, ".php"), __FUNCTION__);
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

            // Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
            Helper::writeLog(basename(__FILE__, ".php"), 'Finish ok');

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