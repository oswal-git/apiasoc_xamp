<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Auth;
use Apiasoc\Classes\Models\Images;
use Apiasoc\Classes\Models\ItemArticle;

function evaluate(&$data) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        Helper::writeLog('_GET', $_GET);

        $data['id_article'] = $_GET['id_article'];
        $data['date_updated_article'] = $_GET['date_updated_article'];

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
            return true;
        } elseif ($auth->validateTokenJwt($token)) {
            if (Globals::getError() !== 'Expired token') {
                return true;
            }
        }

        $result = (object) Globals::getResult(); // custom data in token

        $auth->id_user = $result->data->id_user;

        if ($auth->getDataUserById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($token !== $auth->token_user) {
            Globals::updateResponse(400, 'Token not match', 'Token not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $id_article = $data['id_article'];
        $date_updated_article = $data['date_updated_article'];
        $article = new Article();
        Helper::writeLog('$auth->profile_user', $auth->profile_user);
        $article->id_article = $id_article;
        Helper::writeLog('$article->date_updated_article', $article->date_updated_article);
        if ($article->getArticleById()) {
            return true;
        } elseif (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'Article created not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        } elseif ($date_updated_article !== $article->date_updated_article) {
            Globals::updateResponse(400, 'Record modified by another user', 'Record modified by another user. Refresh it, please..', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($auth->profile_user === 'superadmin') {
            // power
        } elseif (($auth->profile_user === 'admin') && ((int) $auth->id_asociation_user === (int) $article->id_asociation_article)) {
            // less power by can
        } else {
            Globals::updateResponse(400, 'User not authorized to create Article', 'User not authorized to create Article.', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        $article->getImages();

        Helper::writeLog('$images', Globals::getResult()['records']);

        $target_path = Globals::getDirUploads() . DIRECTORY_SEPARATOR . 'articles\images\asociation-' . $article->id_asociation_article . DIRECTORY_SEPARATOR . 'article-' . $article->id_article . DIRECTORY_SEPARATOR;

        try {
            Helper::deleteFolder($target_path . 'cover');
            Helper::deleteFolder($target_path . 'items-images');
            Helper::deleteFolder($target_path);
            Helper::writeLog('***************** Delete images floder', '');
        } catch (\Exception $e) {
            Globals::updateResponse(400, $e, 'Error deleting article images', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        try {
            $images = new Images();
            $images->article_id_images = $article->id_article;
            if ($images->deleteAllArticleImages()) {
                return true;
            }
            Helper::writeLog('***************** Delete images register', '');
            $item_article = new ItemArticle();
            $item_article->id_article_item_article = $article->id_article;
            if ($item_article->deleteItemsOfArticle()) {
                return true;
            }
            Helper::writeLog('***************** Delete items', '');
            if ($article->deleteArticle()) {
                return true;
            }

            if ((int) Globals::getResult()['records_deleted'] !== 1) {
                Globals::updateResponse(400, 'Non unique record', 'Article not match', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            Helper::writeLog('***************** Delete article', '');
            Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__);

            Helper::writeLog(basename(__FILE__, ".php"), 'Finish ok');
            return false;

        } catch (\Exception $e) {
            Globals::updateResponse(400, $e, 'Error deleting article', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        return false;

    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();