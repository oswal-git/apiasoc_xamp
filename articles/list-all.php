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

        $category = isset($_GET['category_article']) ? $_GET['category_article'] : '';
        $subcategory = isset($_GET['subcategory_article']) ? $_GET['subcategory_article'] : '';
        Helper::writeLog('category', $category);
        Helper::writeLog('subcategory', $subcategory);

        $auth = new Auth();

        $headers = Helper::getAuthorizationHeader();
        Helper::writeLog('headers', $headers);

        $loged = false;

        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
            if ($token) {
                if (!$auth->validateTokenJwt($token)) {
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
                    }
                    $loged = true;
                }
            }

        }

        $article = new Article();
        switch (true) {
        case !$loged:
            $article->id_asociation_article = (int) str_repeat('9', 9);
            break;
        case $auth->profile_user === 'superadmin':
            $article->id_asociation_article = (int) str_repeat('9', 9);
            break;
        case $auth->profile_user === 'admin' && $auth->id_asociation_user > 0:
            $article->id_asociation_article = $auth->id_asociation_user;
            break;
        default:
            $article->id_asociation_article = $auth->id_asociation_user;
            break;
        }

        $article->category_article = $category;
        $article->subcategory_article = $subcategory;

        if ($article->getAllArticlesOfAsociation()) {
            Helper::writeLog(' Globals::getResult() -> superadmin', Globals::getResult());
            return true;
        }
        Helper::writeLog(' Globals::getResult()', Globals::getResult());

        $listArticles = Globals::getResult()['records'];

        $item_article = new ItemArticle();
        for ($i = 0; $i < count($listArticles); ++$i) {
            $cover = $listArticles[$i]['cover_image_article'];
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
            $listArticles[$i]['cover_image_article'] = $cover_image_article;
            Helper::writeLog('listArticles[$i]', $listArticles[$i]);

            $item_article->id_article_item_article = $listArticles[$i]['id_article'];
            if ($item_article->getListItemsOfArticle()) {
                return true;
            }
            $items = Globals::getResult()['records'];

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
                Helper::writeLog('items[$i]', $items[$j]);
            }

            $listArticles[$i]['items_article'] = $items;
        }

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__, $listArticles);
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