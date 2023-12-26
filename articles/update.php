<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\ItemArticle;
use Apiasoc\Classes\Models\Notifications;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = json_decode(file_get_contents("php://input"), true);
        // echo 'data' . PHP_EOL;
        // var_dump($data['data']);
        // echo 'items' . PHP_EOL;
        // for ($i = 0; $i < count($data['items']); $i++) {
        //     # code...
        //     var_dump($data['items'][$i]["id_item_article"]);
        //     var_dump($data['items'][$i]["text_item_article"]);
        // }

        $id_article = (int) $data['data']['id_article'];
        $id_asociation_article = (int) $data['data']['id_asociation_article'];
        $date_updated_article = $data['data']['date_updated_article'];

        $auth = new Auth();
        $headers = Helper::getAuthorizationHeader();
        // Helper::writeLog('headers', $headers);

        if (!preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $token = $matches[1];
        if (!$token) {
            // No token was able to be extracted from the authorization header
            Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($auth->validateTokenJwt($token)) {
            // if (Globals::getError() !== 'Expired token') {
            return true;
            // }
        }

        $result = (object) Globals::getResult();
        // Helper::writeLog('gettype $result 2', gettype($result));
        // Helper::writeLog(' $result->data', $result->data);
        Helper::writeLog(' $id_asociation_article', $id_asociation_article);
        Helper::writeLog(' (int) $id_asociation_article', (int) $id_asociation_article);

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

        $asoc = new Asoc();

        if ($auth->profile_user === 'superadmin') {
            // power
        } elseif ((in_array($auth->profile_user, array('admin', 'editor'))) && ((int) $auth->id_asociation_user === (int) $id_asociation_article)) {
            // less power by can
            // Helper::writeLog(' $id_asociation_article', $id_asociation_article);
            $asoc->id_asociation = (int) $id_asociation_article;
            $asoc->getAsociationById();

            if ($asoc->getAsociationById()) {
                return true;
            } elseif (Globals::getResult()['num_records'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
        } else {
            Globals::updateResponse(400, 'User not authorized to create Article', 'User not authorized to create Article!.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        // $data = json_decode(file_get_contents("php://input"), true);

        $article = new Article();
        Helper::writeLog('$auth->profile_user', $auth->profile_user);
        $article->id_article = $id_article;
        $article->id_asociation_article = $id_asociation_article;
        $article->date_updated_article = $date_updated_article;
        Helper::writeLog('$article->date_updated_article', $article->date_updated_article);
        if ($article->getArticleById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Article created not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($data['data']['date_updated_article'] !== $article->date_updated_article) {
            Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please..', basename(__FILE__, ".php"), __FUNCTION__);
            Helper::writeLog('gettype $data[date_updated_article]', gettype($data['data']['date_updated_article']));
            Helper::writeLog('$data[date_updated_article]', $data['data']['date_updated_article']);
            Helper::writeLog('gettype $user->date_updated_article', gettype($article->date_updated_article));
            Helper::writeLog('$article->date_updated_article', $article->date_updated_article);
            return true;
        }

        $state_article_before = $article->state_article;

        foreach ($data['data'] as $key => $value) {
            $article->$key = $value;
        }
        $state_article_after = $article->state_article;

        Helper::writeLog(' $transacction', 'transacction');
        $article->initTransaccion();

        // if ($state_article_before !== $state_article_after && $state_article_after === 'notificar') {
        //     $article->state_article = 'publicado';
        // }

        if ($article->ind_notify_article !== 3) {
            if ($state_article_before !== $state_article_after) {
                switch (true) {
                case ($article->state_article === 'redacción' && $article->ind_notify_article != 9):
                    $article->ind_notify_article = 0;
                    break;
                case ($article->state_article === 'revisión' && $article->ind_notify_article != 9):
                    $article->ind_notify_article = 0;
                    break;
                case ($article->state_article === 'publicado' && $article->ind_notify_article != 9):
                    $article->date_notification_article = date('Y-m-d H:i:s', microtime(true));
                    $article->ind_notify_article = 9;
                    break;
                case ($article->state_article === 'anulado'):
                    $article->ind_notify_article = 3;
                    break;
                case ($article->state_article === 'expirado'):
                    $article->ind_notify_article = 3;
                    break;
                default:
                    # code...
                    break;
                }
            }
        }

        if ($article->updateArticle()) {
            $article->abortTransaccion();
            return true;
        } elseif (Globals::getResult()['records_update'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
            $article->abortTransaccion();
            return true;
        }

        $item_article = new ItemArticle();
        $item_article->id_article_item_article = $article->id_article;
        if ($item_article->deleteItemsOfArticle()) {
            $article->abortTransaccion();
            return true;
        }

        $items = [];
        if (count($data['items']) > 0) {
            for ($i = 0; $i < count($data['items']); $i++) {
                # code...
                foreach ($data['items'][$i] as $key => $value) {
                    // Helper::writeLog(' $key', $key);
                    // Helper::writeLog(' $value', $value);
                    $item_article->$key = $value;
                }
                $item_article->id_item_article = $i;
                $item_article->id_article_item_article = $article->id_article;
                if ($item_article->createItemArticle()) {
                    $article->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['records_inserted'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record', 'Item article not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $article->abortTransaccion();
                    return true;
                }
            }

            if ($item_article->getListItemsOfArticle()) {
                return true;
            }

            $items = Globals::getResult()['records'];
        }

        if ($state_article_before !== $state_article_after && $state_article_after === 'notificar') {
            $notifications = new Notifications();
            $notifications->id_asociation_notifications = $article->id_asociation_article;
            $notifications->id_article_notifications = $article->id_article;
            if ($notifications->getNotificationById()) {
                $article->abortTransaccion();
                return true;
            } elseif (Globals::getResult()['num_records'] === 0) {
                if ($notifications->createNotification()) {
                    $article->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['records_inserted'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record for insert', 'Notification not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $article->abortTransaccion();
                    return true;
                }
            } elseif (Globals::getResult()['num_records'] === 1) {
                $notifications->state_notifications = (string) ((int) $notifications->state_notifications + 1);
                if ($notifications->updateNotification()) {
                    $article->abortTransaccion();
                    return true;
                } elseif (Globals::getResult()['records_update'] !== 1) {
                    Globals::updateResponse(400, 'Non unique record for update', 'Notification not match', basename(__FILE__, ".php"), __FUNCTION__);
                    $article->abortTransaccion();
                    return true;
                }
            } else {
                Globals::updateResponse(400, 'Non unique record', 'Notification not match', basename(__FILE__, ".php"), __FUNCTION__);
                $article->abortTransaccion();
                return true;
            }
        }

        $article->endTransaccion();
        // Helper::writeLog(' $item_article', Globals::getResult()['records']);

        if ($article->getArticleUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Asociation created not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        for ($j = 0; $j < count($items); ++$j) {
            $image = $items[$j]['image_item_article'];
            Helper::writeLog('image', $image);
            if ($image === '') {
                $image_item_article = array(
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
                    "src" => $image,
                    "nameFile" => '',
                    "filePath" => '',
                    "fileImage" => null,
                    "isSelectedFile" => false,
                    "isDefault" => false,
                    "isChange" => false,
                );
            }
            Helper::writeLog('image_item_article', $image_item_article);
            $items[$j]['image_item_article'] = $image_item_article;
            Helper::writeLog('items[' . $j . ']', $items[$j]);
        }

        $article_data = Globals::getResult()['records'][0];

        $cover = $article_data['cover_image_article'];
        Helper::writeLog('cover', $cover);
        if ($cover === '') {
            $cover_image_article = array(
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
        return false;

    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();