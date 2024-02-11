<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\ItemArticle;

function evaluate(&$data) {

    Helper::writeLog('$_POST', $_POST);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = json_decode(file_get_contents("php://input"), true);
        Helper::writeLog('data', $data);

        try {
            $auth = new Auth();
            $article = new Article();

            $data['base_name_old'] = pathinfo($data['file_src'], PATHINFO_BASENAME);
            $data['file_type'] = 'image/png';

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
            } elseif ($auth->validateTokenJwt($token)) {
                return true;
            }

            $result = (object) Globals::getResult();
            Helper::writeLog(' $result->data->id_user', $result->data->id_user);

            $auth->id_user = (int) $result->data->id_user;
            if ($auth->getDataUserById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            } elseif ($token !== $auth->token_user) {
                Globals::updateResponse(400, 'Token not match', 'Token not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $article->id_article = $data['id_article'];
            if ($article->getArticleById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            } elseif ($data['date_updated_article'] !== $article->date_updated_article) {
                Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

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
            Helper::writeLog('user_name', $data['user_name']);
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

            Helper::writeLog('$index', $data['index']);
            if ($data['index'] === '1') {
                Helper::writeLog('1 $target_path', $data['target_path']);
                Helper::deleteFolder($data['target_path'] . '-old');

                if (is_dir($data['target_path'])) {
                    Helper::writeLog('rename $target_path', $data['target_path']);
                    Helper::writeLog('rename $target_path_old', $data['target_path'] . '-old');
                    rename($data['target_path'], $data['target_path'] . '-old');
                }

            }

            if (!is_dir($data['target_path'])) {
                mkdir($data['target_path'], 0777, true);
            }

            $target_file_old = $target_path . '-old' . DIRECTORY_SEPARATOR . $data['base_name_old'];
            if (!copy($target_file_old, $target_file)) {
                Globals::updateResponse(400, 'File upload copy failed. Please try again.', 'File upload failed. Please try again.', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            if ((int) $data['index'] === (int) $data['items']) {
                Helper::writeLog('2 rmdir($target_path_old)', $data['target_path'] . '-old');
                Helper::deleteFolder($data['target_path'] . '-old');
            }

            $item_article = new ItemArticle();
            Helper::writeLog('$id_item_article', $data['id_item_article']);

            $item_article->id_item_article = $data['id_item_article'];
            $item_article->id_article_item_article = $article->id_article;
            $item_article->image_item_article = $data['url_file'];
            if ($item_article->updateImageItem()) {
                return true;
            }

            // ----------------------------------------------------------------------//
            //                          GET ARTICLE                                  //
            // ----------------------------------------------------------------------//

            $item_article = new ItemArticle();
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

            for ($j = 0; $j < count($items); ++$j) {
                $image = $items[$j]['image_item_article'];
                $idImage = $items[$j]['images_id_item_article'];
                Helper::writeLog('image', $image);
                if ($image === '') {
                    $image_item_article = array(
                        "idImage" => $idImage,
                        "src" => '',
                        "nameFile" => '',
                        "filePath" => '',
                        "fileImage" => null,
                        "isSelectedFile" => false,
                        "isDefault" => true,
                        "isChange" => false,
                    );
                } else {
                    $image_item_article = array(
                        "idImage" => $idImage,
                        "src" => $image,
                        "nameFile" => '',
                        "filePath" => '',
                        "fileImage" => null,
                        "isSelectedFile" => false,
                        "isDefault" => false,
                        "isChange" => false,
                    );
                }
                Helper::writeLog('image_map_item_article', $image_item_article);
                $items[$j]['image_map_item_article'] = $image_item_article;
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