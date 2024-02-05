<?php
require_once "../config/bootstrap.php";
// require_once realpath("../vendor/samayo/bulletproof/src/bulletproof.php");

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\Images;

function evaluate(&$data) {

    Helper::writeLog('$_POST', $_POST);
    Helper::writeLog('$_FILES', $_FILES);
    // Helper::writeLog('$_FILES file', $_FILES['file']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        try {
            $auth = new Auth();
            $article = new Article();
            $images = new Images();

            $data['mani'] = array();
            $data['action'] = $_POST['action'];
            // Helper::writeLog('$action', $data['action']);
            $data['token'] = $_POST['token'];
            $data['id'] = $_POST['user_id'];
            $data['module'] = $_POST['module'];
            $data['cover'] = $_POST['cover'];
            $data['id_article_item_article'] = $_POST['id_article'];
            $data['images_id_item_article'] = $_POST['images_id_item_article'];
            $data['prefix'] = $_POST['prefix'];
            $data['date_updated_article'] = $_POST['date_updated_article'];

            if (!$data['token']) {
                // No token was able to be extracted from the authorization header
                Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if ($auth->validateTokenJwt($data['token'])) {
                return true;
            }

            $result = (object) Globals::getResult();
            // Helper::writeLog('gettype $result', gettype($result));
            // Helper::writeLog(' $result->data', $result->data);
            Helper::writeLog(' $result->data->id_user', $result->data->id_user);
            // Helper::writeLog('gettype $result->data->id_user', gettype($result->data->id_user));

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

            $article->id_article = $data['id_article_item_article'];
            if ($article->getArticleById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            } elseif ($data['date_updated_article'] !== $article->date_updated_article) {
                Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please.', basename(__FILE__, ".php"), __FUNCTION__);
                // Helper::writeLog('gettype $data[date_updated]', gettype($data['date_updated']));
                // Helper::writeLog('$data[date_updated]', $data['date_updated']);
                // Helper::writeLog('gettype $asoc->date_updated', gettype($asoc->date_updated_asociation));
                // Helper::writeLog('$asoc->date_updated', $asoc->date_updated_asociation);
                return true;
            }
            // Helper::writeLog('$auth->profile_user', $auth->profile_user);
            // Helper::writeLog('(int) $article->id_asociation_article', (int) $article->id_asociation_article);
            // Helper::writeLog('(int) str_repeat(9, 9)', (int) str_repeat('9', 9));
            if (($auth->profile_user === 'superadmin') && ((int) $article->id_asociation_article === (int) str_repeat('9', 9))) {
                // power
            } elseif ((in_array($auth->profile_user, array('admin', 'editor'))) && ((int) $auth->id_asociation_user === (int) $article->id_asociation_article)) {
                // partial power
            } else {
                Globals::updateResponse(400, 'User not authorized to modify images', 'User not authorized to modify images.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $images = new Images();

            $images->id_images = $data['images_id_item_article'];
            $data['mani']['image'] = $images->id_images;
            if ($images->getIImagesById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Image not found', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            Helper::writeLog('$image', $images->src_images);
            Helper::writeLog('Globals::getUrlFiles()', Globals::getUrlUploads());
            $pos = strpos($images->src_images, Globals::getUrlUploads());
            if ($pos === false) {
                Globals::updateResponse(400, 'Url files not found', 'Image not found', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            Helper::writeLog('$pos', $pos);
            $rest = substr($images->src_images, $pos + strlen(Globals::getUrlUploads()));
            Helper::writeLog('$rest', $rest);
            $file_tmp = Globals::getDirUploads() . $rest;
            Helper::writeLog('$file_tmp', $file_tmp);
            $file = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file_tmp);
            Helper::writeLog('$file', $file);
            $data['mani']['file'] = $file;
            $target_path = dirname(dirname($file));
            Helper::writeLog('$target_path', $target_path);
            $data['mani']['target_path'] = $target_path;

            if (file_exists($file) && !is_dir($file)) {
                try {
                    Helper::writeLog('delete file', $file);
                    unlink($file);
                } catch (\Exception $e) {
                    Globals::updateResponse(400, $e, 'Error deleting item image', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
            } else {
                Helper::writeLog('delete file no exist', $file);
            }

            Helper::writeLog(' $transacction', 'init transacction');
            $images->initTransaccion();

            if ($images->deleteImages()) {
                return true;
                $images->abortTransaccion();
            } elseif (Globals::getResult()['records_deleted'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Image record not match', basename(__FILE__, ".php"), __FUNCTION__);
                $images->abortTransaccion();
                return true;
            }

            Helper::writeLog(' $transacction', 'end transacction');
            $images->endTransaccion();

            Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__);
            Helper::writeLog(basename(__FILE__, ".php"), 'Finish ok');
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