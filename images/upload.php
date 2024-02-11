<?php
require_once "../config/bootstrap.php";
// require_once realpath("../vendor/samayo/bulletproof/src/bulletproof.php");

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\ItemArticle;
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
                return true;
            }

            $result = (object) Globals::getResult();
            Helper::writeLog('gettype $result', gettype($result));
            Helper::writeLog(' $result->data', $result->data);
            Helper::writeLog(' $result->data->id_user', $result->data->id_user);
            Helper::writeLog('gettype $result->data->id_user', gettype($result->data->id_user));

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

            $data['id'] = $_POST['user_id'];
            // Upload logo asociation
            if ($data['module'] === 'asociations') {
                $data['id_asociation'] = $_POST['asoc_id'];
                $data['date_updated_asociation'] = $_POST['date_updated_asociation'];
                $asoc->id_asociation = $data['id_asociation'];
                if ($asoc->getAsociationById()) {
                    return true;
                } elseif (Globals::getResult()['num_records'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                } elseif ($data['date_updated_asociation'] !== $asoc->date_updated_asociation) {
                    Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please. Logout and login again.', basename(__FILE__, ".php"), __FUNCTION__);
                    Helper::writeLog('gettype $data[date_updated_asociation]', gettype($data['date_updated_asociation']));
                    Helper::writeLog('$data[date_updated_asociation]', $data['date_updated_asociation']);
                    Helper::writeLog('gettype $asoc->date_updated', gettype($asoc->date_updated_asociation));
                    Helper::writeLog('$asoc->date_updated_asociation', $asoc->date_updated_asociation);
                    return true;
                }
                if ($auth->profile_user === 'superadmin') {
                    // power
                } elseif (($auth->profile_user === 'admin') && ((int) $auth->id_asociation_user === (int) $asoc->id_asociation)) {
                    // partial power
                } else {
                    Globals::updateResponse(400, 'User not authorized to modify logo', 'User not authorized to modify logo.', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
                // Upload images of articles
            } else if ($data['module'] === 'articles') {
                $data['cover'] = $_POST['cover'];
                Helper::writeLog('gettype $data[date_updated]', gettype($data['date_updated']));
                Helper::writeLog('gettype $data[date_updated]', gettype($data['date_updated']));
                $data['id_article'] = $_POST['id_article'];
                $data['id_asociation_article'] = $_POST['id_asociation_article'];
                $data['date_updated_article'] = $_POST['date_updated_article'];
                $article->id_article = (int) $data['id_article'];
                $article->id_asociation_article = (int) $data['id_asociation_article'];
                $article->date_updated_article = $data['date_updated_article'];
                if ($article->getArticleById()) {
                    return true;
                } elseif (Globals::getResult()['num_records'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                } elseif ($data['date_updated_article'] !== $article->date_updated_article) {
                    Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please.', basename(__FILE__, ".php"), __FUNCTION__);
                    Helper::writeLog('$data[date_updated_article]', $data['date_updated_article']);
                    Helper::writeLog('gettype $article->date_updated_article', gettype($article->date_updated_article));
                    Helper::writeLog('$article->date_updated_article', $article->date_updated_article);
                    return true;
                }
                Helper::writeLog('$auth->profile_user', $auth->profile_user);
                Helper::writeLog('(int) $article->id_asociation_article', (int) $article->id_asociation_article);
                Helper::writeLog('(int) str_repeat(9, 9)', (int) str_repeat('9', 9));
                if (($auth->profile_user === 'superadmin') && ((int) $article->id_asociation_article === (int) str_repeat('9', 9))) {
                    // power
                } elseif ((in_array($auth->profile_user, array('admin', 'editor'))) && ((int) $auth->id_asociation_user === (int) $article->id_asociation_article)) {
                    // partial power
                } else {
                    Globals::updateResponse(400, 'User not authorized to modify images', 'User not authorized to modify images. .', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
            } else {
                // Upload user avatar
                $user->id_user = $data['id'];
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
                    return true;
                }

                if ((int) $data['id'] === $result->data->id_user) {
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
            }

            //target folder
            $target_path = Globals::getDirUploads() . $data['module'] . DIRECTORY_SEPARATOR . $data['prefix'];
            $data['target_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_path);

            Helper::writeLog('$target_path', $data['target_path']);
            Helper::writeLog('$data id', $data['id']);
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
            if ($data['action'] !== 'delete') {

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
                $target_file = $data['target_path'] . DIRECTORY_SEPARATOR . $data['name'] . "." . $data['ext'];
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
                $allowed_types = array('image/jpg', 'image/png', 'image/jpeg');
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
            } else {
                $data['url_file'] = '';
                // $asoc->logo_asociation = '';
            }

            if ($data['module'] === 'asociations') {
                $asoc->logo_asociation = $data['url_file'];
                $asoc->date_updated_asociation = $data['date_updated_asociation'];
                if ($asoc->updateLogo()) {
                    return true;
                } elseif (Globals::getResult()['records_update'] !== 1) {
                    Globals::updateResponse(400, 'Logo not match', 'Logo not match', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }

                if ($asoc->getAsociationById()) {
                    return true;
                }

            } else if ($data['module'] === 'articles') {
                $item_article = new ItemArticle();

                $article->cover_image_article = $data['url_file'];
                $article->date_updated_article = $data['date_updated_article'];
                if ($article->updateCover()) {
                    return true;
                } elseif (Globals::getResult()['records_update'] !== 1) {
                    Globals::updateResponse(400, 'Article not match', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }

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
                    Helper::writeLog('image', $image);
                    $idImage = $items[$j]['images_id_item_article'];
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

            } else {
                $user->avatar_user = $data['url_file'];
                if ($user->updateAvatar()) {
                    return true;
                } elseif (Globals::getResult()['records_update'] !== 1) {
                    Globals::updateResponse(400, 'Avatar not match', 'Avatar not match', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
                if ($user->getDataUserById()) {
                    return true;
                }
            }

            // $result = array(
            //     'url' => $data['action'] === 'asociation' ? $asoc->logo_asociation : $user->avatar_user,
            //     'dir' => $data['action'] === 'delete' ? '' : $data['target_file'],
            // );

            // Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $result);
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