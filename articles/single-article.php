<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\ItemArticle;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = json_decode(file_get_contents("php://input"), true);

        Helper::writeLog('list-all: $_GET', $_GET);

        $auth = new Auth();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        $loged = false;

        if (!preg_match('/Bearer\s(\S+)/', (string) $headers, $matches)) {
            Globals::updateResponse(400, 'Token not found in request', 'Token not found in request', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } else {

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
        }

        $id_article = isset($_GET['id_article']) ? (int) $_GET['id_article'] : 0;

        $article = new Article();

        $article->id_article = $id_article;

        if ($article->getArticleById()) {
            Helper::writeLog(' Globals::getResult() -> superadmin', Globals::getResult());
            return true;
        }

        Helper::writeLog(' Globals::getResult()', Globals::getResult());

        if ($article->id_asociation_article != $auth->id_asociation_user && $article->id_asociation_article != '999999999') {
            Globals::updateResponse(400, 'Article of another asociation', 'Article of another asociation', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $article = Globals::getResult()['records'][0];

        $item_article = new ItemArticle();

        $cover = $article['cover_image_article'];
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
        $article['cover_image_article'] = $cover_image_article;
        Helper::writeLog('article[$i]', $article);

        $item_article->id_article_item_article = $article['id_article'];
        if ($item_article->getListItemsOfArticle()) {
            return true;
        }
        $items = Globals::getResult()['records'];

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
            Helper::writeLog('items[$i]', $items[$j]);
        }

        $article['items_article'] = $items;

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $article);
        return false;

    } else {
        Globals::updateResponse(500, 'Page not found', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
        return true;
    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();