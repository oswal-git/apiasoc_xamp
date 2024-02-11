<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Article;
use Apiasoc\Classes\Models\Auth;
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
        $images = Globals::getResult()['records'];

        Helper::writeLog('$images', $images);

        for ($j = 0; $j < count($images); ++$j) {
            $image = $images[$j]['image'];
            $dir = $images[$j]['kind'];
            Helper::writeLog('$image', $image);
            $pos = strpos($image, Globals::getUrlFiles());
            if ($pos === false) {
                Globals::updateResponse(400, 'Url files not found', 'Image not found', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
            Helper::writeLog('$pos', $pos);
            $rest = substr($image, $pos + strlen(Globals::getUrlFiles()));
            $path = Globals::getDirFiles() . $rest;
            $target_path = dirname(dirname($path));
            Helper::writeLog('$target_path', $target_path);

            try {
                Helper::deleteFolder($target_path);
            } catch (\Exception $e) {
                Globals::updateResponse(400, $e, 'Error deleting article images', basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }

        }

        try {
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