<?php
require_once "../config/bootstrap.php";
require_once './includes/get_images.php';

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\Images;
use Apiasoc\Classes\Models\ItemArticle;
use Apiasoc\Classes\Models\Notifications;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        try {
            //code...
            // Recibe el objeto article como JSON desde el campo de formulario 'article'
            $data['article'] = json_decode($_POST['article'], true);
            $data['items_article'] = $data['article']['items_article_plain'];
            $data['items_article_index'] = array();
            foreach ($data['items_article'] as $index => $item) {
                $data['items_article_index'][$item['id_item_article']] = $index;
            }
            $data['action'] = $_POST['action'];
            $data['module'] = $_POST['module'];
            $data['prefix'] = $_POST['prefix'];
            $data['token'] = $_POST['token'];
            $data['user_name'] = $_POST['user_name'];
            $data['images'] = array();

            // Recibe las imágenes y guárdalas
            foreach ($_FILES as $nombreCampo => $archivo) {
                // $nombreCampo es el nombre del campo de formulario donde se subió el archivo
                // $archivo es un array asociativo que contiene información sobre el archivo subido
                // Nombre original del archivo
                $nombre = $archivo['name'];
                $tipo = $archivo['type']; // Tipo MIME del archivo
                $tamano = $archivo['size']; // Tamaño del archivo en bytes
                $nombreTemporal = $archivo['tmp_name']; // Nombre temporal del archivo en el servidor
                $error = $archivo['error']; // Código de error (si lo hay)

                // Procesar el archivo como desees
                // echo "Nombre del archivo: $nombre <br>";
                // echo "Tipo MIME: $tipo <br>";
                // echo "Tamaño del archivo: $tamano bytes <br>";
                // echo "Nombre temporal del archivo: $nombreTemporal <br>";
                // echo "Código de error: $error <br>";

                if ($nombreCampo == 'file_cover') {
                    $data['images']['cover'] = $archivo;
                    $data['images']['cover']['image'] = 'cover';
                    $data['images']['cover']['folder'] = 'cover';
                } else {
                    $id = (int) substr($nombreCampo, strlen("file_"));
                    $data['images']['items'][$id]['items_image'] = $archivo;
                    $data['images']['items'][$id]['items_image']['image'] = 'item';
                    $data['images']['items'][$id]['items_image']['folder'] = 'items-images';
                    $data['items_article'][$data['items_article_index'][$id]] = array_merge($data['items_article'][$data['items_article_index'][$id]], $archivo);
                }
            }

            $id_asociation_article = (int) $data['article']['id_asociation_article'];

            $logged = false;
            $expired = false;

            // Validate user connected
            $auth = new Auth();
            $token = $data['token'];
            if ($token) {
                if ($auth->validateTokenJwt($token)) {
                    if (Globals::getError() !== 'Expired token') {
                        return true;
                    }
                    Helper::writeLog('expired', 'expired');
                    $expired = true;
                }

                $result = (object) Globals::getResult();
                Helper::writeLog('gettype $result 2', gettype($result));
                Helper::writeLog(' $result->data', $result->data);
                Helper::writeLog(' $result->data->id_user', $result->data->id_user);

                $auth->id_user = $result->data->id_user;
                if ($auth->getDataUserById()) {
                    switch (Globals::getResult()) {
                    case 'Record not found':
                        Globals::updateMessageResponse('User connected not exist');
                        break;
                    case 'Duplicate record':
                        Globals::updateMessageResponse('User connected not exist');
                        break;
                    default:
                        break;
                    }
                    return true;
                }

                if ($auth->token_user !== $token) {
                    Globals::updateResponse(400, 'Missmatch token', 'Missmatch token. Reconnecte, please', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }

                $logged = $expired ? false : true;

            }

            $asoc = new Asoc();
            Helper::writeLog(' $auth->profile_user', $auth->profile_user);
            Helper::writeLog(' $auth->profile_user === superadmin', $auth->profile_user === 'superadmin');
            Helper::writeLog(' (int) $id_asociation_article === 0)', (int) $id_asociation_article === 0);
            if (($auth->profile_user === 'superadmin') && (($id_asociation_article === 0) || ($id_asociation_article === (int) str_repeat('9', 9)))) {
                // power
                $id_asociation_article = (int) str_repeat('9', 9);
                Helper::writeLog(' $id_asociation_article', $id_asociation_article);
            } elseif (in_array($auth->profile_user, array('admin', 'editor')) && ((int) $auth->id_asociation_user === $id_asociation_article)) {
                // less power by can
                // Helper::writeLog(' $id_asociation_article', $id_asociation_article);
                $asoc->id_asociation = $id_asociation_article;
                $asoc->getAsociationById();

                if (Globals::getError() != '') {
                    return true;
                } elseif (Globals::getResult()['num_records'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'Asociation not match', basename(__FILE__, ".php"), __FUNCTION__);
                    return true;
                }
            } else {
                Globals::updateResponse(400, 'User not authorized to create Asociation', 'User not authorized to create Asociation...', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

            $article = new Article();

            foreach ($data['article'] as $key => $value) {
                if (property_exists($article, $key)) {
                    $article->$key = $value;
                }
            }

            $article->id_asociation_article = $id_asociation_article;

            // if ($article->expiration_date_article === '') {
            //     $article->expiration_date_article = '9999-12-31';
            // }

            $state_article = $article->state_article;

            // if ($article->state_article === 'notificar') {
            //     $article->state_article = 'publicado';
            // }
            $article->ind_notify_article = 0;

            switch ($article->state_article) {
            case 'redacción':
                $article->ind_notify_article = 0;
                break;
            case 'revisión':
                $article->ind_notify_article = 0;
                break;
            case 'publicado':
                $article->ind_notify_article = 9;
                $article->date_notification_article = date('Y-m-d H:i:s', microtime(true));
                break;
            case 'anulado':
                $article->ind_notify_article = 3;
                break;
            case 'expirado':
                $article->ind_notify_article = 3;
                break;

            default:
                $article->ind_notify_article = 0;
                $article->state_article = 'redacción';
                break;
            }

            Helper::writeLog(' $transacction', 'transacction');
            $article->initTransaccion();

            if ($article->createArticle()) {
                $article->abortTransaccion();
                return true;
            } elseif (Globals::getResult()['records_inserted'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                $article->abortTransaccion();
                return true;
            }
            $article->id_article = Globals::getResult()['last_insertId'];

            if ($state_article === 'notificar') {
                $notifications = new Notifications();
                $notifications->id_asociation_notifications = $article->id_asociation_article;
                $notifications->id_article_notifications = $article->id_article;
                if ($notifications->createNotification()) {
                    $article->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['records_inserted'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'Notification not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $article->abortTransaccion();
                    return true;
                }
            }

            $items = [];
            if (count($data['items_article']) > 0) {
                $item_article = new ItemArticle();
                $item_article->initTransaccion();
                // for ($i = 0; $i < count($data['items_article']); $i++) {
                foreach ($data['items_article'] as $index => $item) {
                    # code...
                    foreach ($item as $key => $value) {
                        // Helper::writeLog(' $key', $key);
                        // Helper::writeLog(' $value', $value);
                        $item_article->$key = $value;
                    }
                    $item_article->id_item_article = $index;
                    $item_article->id_article_item_article = $article->id_article;
                    if ($item_article->createItemArticle()) {
                        $article->abortTransaccion();
                        $item_article->abortTransaccion();
                        return true;
                    } elseif (Globals::getResult()['records_inserted'] !== 1) {
                        Globals::updateResponse(400, 'Non unique record', 'Item article not match', basename(__FILE__, ".php"), __FUNCTION__);
                        $article->abortTransaccion();
                        $item_article->abortTransaccion();
                        return true;
                    }
                }

            }

            //target folder
            $target_path_tmp = Globals::getDirUploads() . $data['module'] . DIRECTORY_SEPARATOR . $data['prefix'];
            $target_base_path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_path_tmp);

            Helper::writeLog('$target_path', $target_base_path);

            if (isset($data['images']['cover'])) {
                $image_file = $data['images']['cover'];

                $image_data = getImageData($article->id_article, $image_file, $target_base_path, $data['module'], $data['prefix']);

                if (moveUpload($image_data)) {
                    return true;
                }

                if ($article->getArticleById()) {
                    $article->abortTransaccion();
                    $item_article->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['num_records'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $article->abortTransaccion();
                    $item_article->abortTransaccion();
                    return true;
                }

                $article->cover_image_article = $image_data['url_file'];
                if ($article->updateCover()) {
                    $article->abortTransaccion();
                    $item_article->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['records_update'] !== 1) {
                    Globals::updateResponse(400, 'Article not match', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $article->abortTransaccion();
                    $item_article->abortTransaccion();
                    return true;
                }
            }

            $images = new Images();
            $images->initTransaccion();

            foreach ($data['images']['items'] as $index => $item) {
                if (isset($item['items_image'])) {
                    $image_file = $item['items_image'];

                    $image_data = getImageData($article->id_article, $image_file, $target_base_path, $data['module'], $data['prefix']);

                    if (moveUpload($image_data)) {
                        return true;
                    }

                    $images->src_images = $image_data['url_file'];
                    $images->type_images = 'item';
                    $images->article_id_images = $article->id_article;
                    $images->item_article_id_images = $index;

                    if ($images->createImage()) {
                        return true;
                        $article->abortTransaccion();
                        $item_article->abortTransaccion();
                        $images->abortTransaccion();
                    } elseif (Globals::getResult()['records_inserted'] !== 1) {
                        Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                        $article->abortTransaccion();
                        $item_article->abortTransaccion();
                        $images->abortTransaccion();
                        return true;
                    }

                    $images->id_images = Globals::getResult()['last_insertId'];

                    $item_article = new ItemArticle();
                    Helper::writeLog('$id_item_article', $data['id_item_article']);

                    $item_article->id_item_article = $index;
                    $item_article->id_article_item_article = $article->id_article;
                    $item_article->image_item_article = $image_data['url_file'];
                    $item_article->images_id_item_article = $images->id_images;
                    if ($item_article->updateImageItem()) {
                        $article->abortTransaccion();
                        $item_article->abortTransaccion();
                        $images->abortTransaccion();
                        return true;
                    }

                }
            }

            $article->endTransaccion();
            // $item_article->endTransaccion();
            // $images->endTransaccion();

            // Helper::writeLog(' $item_article', Globals::getResult()['records']);
            if ($item_article->getListItemsOfArticle()) {
                return true;
            }

            $items = Globals::getResult()['records'];

            if ($article->getArticleById()) {
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

            Globals::updateResponse(200, '', $expired ? 'expired' : 'ok', basename(__FILE__, ".php"), __FUNCTION__, $article_data);

            Helper::writeLog(basename(__FILE__, ".php"), 'Finish ok');
            return false;

        } catch (\Exception $e) {
            Globals::updateResponse(501, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
            return true;
        }

    } else {
        Globals::updateResponse(500, 'Page not found', 'Page not found', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
        return true;
    }

}
$data = array();

$result = evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();