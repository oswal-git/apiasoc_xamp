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

        $id_asociation_article = (int) $data['data']['id_asociation_article'];

        $auth = new Auth();
        $headers = Helper::getAuthorizationHeader();
        // Helper::writeLog('headers', $headers);

        if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $token = $matches[1];
        if (!$token) {
            // No token was able to be extracted from the authorization header
            Globals::updateResponse(400, 'Token not was able', 'Token not was able', basename(__FILE__, ".php"), __FUNCTION__);
            Helper::writeLog(' ¿$token?', 'Token not was able');
            return true;
        } elseif ($auth->validateTokenJwt($token)) {
            // if (Globals::getError() !== 'Expired token') {
            Helper::writeLog(' Globals::getError()', Globals::getError());
            Helper::writeLog(' validateTokenJwt', 'Expired token');
            return true;
            // }
        }
        Helper::writeLog(' Globals::getError() 2', Globals::getError());

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
        Helper::writeLog(' $auth->profile_user', $auth->profile_user);
        Helper::writeLog(' $auth->profile_user === superadmin', $auth->profile_user === 'superadmin');
        Helper::writeLog(' (int) $id_asociation_article === 0)', (int) $id_asociation_article === 0);
        if (($auth->profile_user === 'superadmin') && ($id_asociation_article === 0)) {
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

        // $data = json_decode(file_get_contents("php://input"), true);

        $article = new Article();

        foreach ($data['data'] as $key => $value) {
            $article->$key = $value;
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
        if (count($data['items']) > 0) {
            $item_article = new ItemArticle();
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

        $article->endTransaccion();

        // Helper::writeLog(' $item_article', Globals::getResult()['records']);

        if ($article->getArticleById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Asociation created not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $article_data = Globals::getResult()['records'][0];
        $article_data['items_article'] = $items;

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $article_data);

        return false;

    }

}
$data = array();

$result = evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();