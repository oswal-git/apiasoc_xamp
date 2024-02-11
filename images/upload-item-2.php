<?php
require_once "../config/bootstrap.php";
// require_once realpath("../vendor/samayo/bulletproof/src/bulletproof.php");

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\Images;
use Apiasoc\Classes\Models\ImagesItemArticle;
use Apiasoc\Classes\Models\ItemArticle;
use Apiasoc\Classes\Models\User;
use Bulletproof\Image;

function evaluate(&$data) {

    Helper::writeLog('$_POST', $_POST);
    Helper::writeLog('$_FILES', $_FILES);
    // Helper::writeLog('$_FILES file', $_FILES['file']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        try {
            $auth = new Auth();
            $user = new User();
            $asoc = new Asoc();
            $article = new Article();
            $images = new Images();

            // $headers = Helper::getAuthorizationHeader();
            // Helper::writeLog('headers', $headers);

            // if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            //     Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            //     return true;
            // }

            // $token = $matches[1];

            $data['action'] = $_POST['action'];
            // Helper::writeLog('$action', $data['action']);
            $data['module'] = $_POST['module'];
            $data['id'] = $_POST['user_id'];
            $data['prefix'] = $_POST['prefix'];
            $data['token'] = $_POST['token'];
            // Helper::writeLog('$token', $data['token']);
            $data['cover'] = $_POST['cover'];
            $data['id_item_article'] = $_POST['id_item_article'];
            $data['id_article_item_article'] = $_POST['id_article'];
            $data['name'] = $_POST['name'];
            $data['is_new'] = $_POST['is_new'];
            $data['index'] = $_POST['index'];
            $data['items'] = $_POST['items'];
            $data['first'] = $_POST['first'];
            $data['last'] = $_POST['last'];

            if ($data['is_new'] === 'true') {
                $data['file_name'] = $_FILES['file']['name'];
                $data['file_type'] = $_FILES['file']['type'];
            } else {
                $data['file_src'] = $_POST['file_src'];
                $data['base_name_old'] = pathinfo($data['file_src'], PATHINFO_BASENAME);
                $data['file_type'] = 'image/png';
            }

            $data['date_updated'] = $_POST['date_updated'];
            $data['date_updated_article'] = $_POST['date_updated_article'];
            // Helper::writeLog('$date_updated', $data['date_updated']);

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

            // Upload image item article

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

            //target folder
            $target_path = Globals::getDirUploads() . $data['module'] . DIRECTORY_SEPARATOR . $data['prefix'];
            $data['target_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_path);

            Helper::writeLog('$target_path', $data['target_path']);
            // Helper::writeLog('$data id', $data['id']);
            // Helper::writeLog('$module', $data['module']);
            // Helper::writeLog('$prefix', $data['prefix']);

            $data['ext'] = pathinfo($data['name'], PATHINFO_EXTENSION);
            $base_name = basename($data['name'], '.' . $data['ext']);
            $data['base_name'] = preg_replace('/[^A-Za-z0-9\-]/', '', $base_name);
            $chain = Helper::generateChain(6, 'letters');
            $data['name'] = $data['base_name'] . '-' . $chain;

            $url_path = Globals::getUrlUploads() . $data['module'] . '/' . $data['prefix'];
            $data['url_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $url_path);
            //set target file path
            $target_file = $target_path . DIRECTORY_SEPARATOR . $data['name'] . "." . $data['ext'];
            $data['target_file_old'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_file);
            $data['target_file'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_file);
            $url_file = $url_path . URL_SEPARATOR . $data['name'] . "." . $data['ext'];
            $data['url_file'] = str_replace(array('/', '\\'), URL_SEPARATOR, $url_file);

            // Helper::writeLog('$chain', $chain);
            Helper::writeLog('$name', $data['name']);
            Helper::writeLog('$file_type', $data['file_type']);
            Helper::writeLog('basename', $data['base_name']);
            Helper::writeLog('name', $data['name']);
            Helper::writeLog('$ext', $data['ext']);
            Helper::writeLog('$url_path', $data['url_path']);
            Helper::writeLog('$target_file', $data['target_file']);
            Helper::writeLog('$url_file', $data['url_file']);

            // define allowed mime types to upload
            $allowed_types = array('image/jpg', 'image/png', 'image/jpeg');
            if (!in_array($data['file_type'], $allowed_types)) {
                Globals::updateResponse(400, 'Type ' . $data['file_type'] . ' not allowed', 'Type ' . $data['file_type'] . ' not allowed.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            // Helper::writeLog('$index', $data['index']);
            // if ($data['index'] === '1') {
            //     Helper::writeLog('1 $target_path', $data['target_path']);
            //     Helper::deleteFolder($data['target_path'] . '-old');

            //     if (is_dir($data['target_path'])) {
            //         Helper::writeLog('rename $target_path', $data['target_path']);
            //         Helper::writeLog('rename $target_path_old', $data['target_path'] . '-old');
            //         rename($data['target_path'], $data['target_path'] . '-old');
            //     }

            // }

            if (!is_dir($data['target_path'])) {
                mkdir($data['target_path'], 0777, true);
            }

            // if ($data['is_new'] === 'true') {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $data['target_file'])) {
                // OK
            } else {
                Globals::updateResponse(400, 'File upload failed. Please try again.', 'File upload failed. Please try again.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            // } else {
            //     $target_file_old = $target_path . '-old' . DIRECTORY_SEPARATOR . $data['base_name_old'];
            //     if (!copy($target_file_old, $target_file)) {
            //         Globals::updateResponse(400, 'File upload copy failed. Please try again.', 'File upload failed. Please try again.', basename(__FILE__, ".php"), __FUNCTION__);
            //         return true;

            //     }
            // }

            // if ((int) $data['index'] === (int) $data['items']) {
            //     Helper::writeLog('2 rmdir($target_path_old)', $data['target_path'] . '-old');
            //     Helper::deleteFolder($data['target_path'] . '-old');
            // }

            $images = new Images();

            $images->src_images = $data['url_file'];
            $images->type_images = 'item';
            $images->article_id_images = $article->id_article;
            $images->item_article_id_images = $data['id_item_article'];

            Helper::writeLog(' $transacction', 'init transacction');
            $images->initTransaccion();

            if ($images->createImage()) {
                return true;
                $images->abortTransaccion();
            } elseif (Globals::getResult()['records_inserted'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                $images->abortTransaccion();
                return true;
            }

            $images->id_images = Globals::getResult()['last_insertId'];

            $item_article = new ItemArticle();
            Helper::writeLog('$id_item_article', $data['id_item_article']);

            $item_article->id_item_article = $data['id_item_article'];
            $item_article->id_article_item_article = $article->id_article;
            $item_article->image_item_article = $data['url_file'];
            $item_article->images_id_item_article = $images->id_images;
            if ($item_article->updateImageItem()) {
                $images->abortTransaccion();
                return true;
            }

            Helper::writeLog(' $transacction', 'end transacction');
            $images->endTransaccion();

            $item_article->id_article_item_article = $article->id_article;
            if ($item_article->getListItemsOfArticle()) {
                return true;
            }
            // Helper::writeLog(' $item_article', Globals::getResult()['records']);
            $items = Globals::getResult()['records'];

            if ($article->getArticleUserById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Asociation created not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $images_item_article = new ImagesItemArticle();
            for ($j = 0; $j < count($items); ++$j) {
                $image = $items[$j]['image_item_article'];
                $idImage = $items[$j]['images_id_item_article'];
                Helper::writeLog('image', $image);
                if ($image === '') {
                    $images_item_article->modify(
                        $idImage,
                        '',
                        '',
                        '',
                        null,
                        false,
                        true,
                        false,
                    );
                    // $image_item_article = $images_item_article->getArray();
                } else {
                    $images_item_article->modify(
                        $idImage,
                        $image,
                        '',
                        '',
                        null,
                        false,
                        false,
                        false,
                    );
                    // $image_item_article = $images_item_article->getArray();
                }
                Helper::writeLog('image_map_item_article', $images_item_article->getArray());
                $items[$j]['image_map_item_article'] = $images_item_article->getArray();
                Helper::writeLog('items[' . $j . ']', $items[$j]);
            }

            $article_data = Globals::getResult()['records'][0];

            $cover = $article_data['cover_image_article'];
            Helper::writeLog('cover', $cover);
            if ($cover === '') {
                $cover_image_article = array(
                    "idImage" => 0,
                    "src" => '',
                    "nameFile" => '',
                    "filePath" => '',
                    "fileImage" => null,
                    "isSelectedFile" => false,
                    "isDefault" => true,
                    "isChange" => false,
                );
            } else {
                $cover_image_article = array(
                    "idImage" => 0,
                    "src" => $cover,
                    "nameFile" => '',
                    "filePath" => '',
                    "fileImage" => null,
                    "isSelectedFile" => false,
                    "isDefault" => false,
                    "isChange" => false,
                );
            }
            Helper::writeLog('cover_image_article', $cover_image_article);
            $article_data['cover_image_article'] = $cover_image_article;
            Helper::writeLog('article_data', $article_data);
            $article_data['items_article'] = $items;

            Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $article_data);
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