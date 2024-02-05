<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

/**
 *
 */
class Article extends Mysql {

    public $id_article;
    public $id_asociation_article;
    public $id_user_article;
    public $category_article;
    public $subcategory_article;
    public $class_article;
    public $state_article;
    public $publication_date_article;
    public $effective_date_article;
    public $expiration_date_article;
    public $cover_image_article;
    public $title_article;
    public $abstract_article;
    public $ubication_article;
    public $date_deleted_article;
    public $date_created_article;
    public $date_updated_article;
    public $date_notification_article;
    public $ind_notify_article;

    public function __construct() {
        // echo "Create Article\n";
        parent::__construct();
    }

    public function getArticleById() {

        $arrData = array(
            $this->id_article,
        );

        $sql = "SELECT	  a.id_article
						, a.id_asociation_article
						, a.id_user_article
						, a.category_article
						, a.subcategory_article
						, a.class_article
						, a.state_article
						, a.publication_date_article
						, a.effective_date_article
						, a.expiration_date_article
						, a.cover_image_article
						, a.title_article
						, a.abstract_article
						, a.ubication_article
						, COALESCE(a.date_deleted_article,'') as date_deleted_article
						, a.date_created_article
						, COALESCE(a.date_updated_article,'') as date_updated_article
						, COALESCE(a.date_notification_article,'') as date_notification_article
						, a.ind_notify_article
                        , u.id_user
                        , u.id_asociation_user
                        , u.email_user
                        , u.profile_user
                        , u.name_user
                        , u.last_name_user
                        , u.avatar_user
                        , COALESCE(aso.long_name_asociation, 'Genérica') as long_name_asociation
                        , COALESCE(aso.short_name_asociation, 'Genérica') as short_name_asociation
                FROM articles a
                LEFT OUTER JOIN users u
                  ON ( u.id_user = a.id_user_article )
                LEFT OUTER JOIN asociations aso
                  ON ( aso.id_asociation = a.id_asociation_article )
                WHERE a.id_article = ?;";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
        if (Globals::getResult()['num_records'] == 1) {
            $this->fillAsoc(Globals::getResult()['records'][0]);
            return $response;
        }
        if (Globals::getResult()['num_records'] == 0) {
            Globals::updateResponse(404, 'Record not found', 'Record not found', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        if (Globals::getResult()['num_records'] > 1) {
            Globals::updateResponse(404, 'Duplicate record', 'Duplicate record', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
    }

    public function getArticleUserById() {

        $arrData = array(
            $this->id_article,
        );

        $sql = "SELECT	  a.id_article
						, a.id_asociation_article
						, a.id_user_article
						, a.category_article
						, a.subcategory_article
						, a.class_article
						, a.state_article
						, a.publication_date_article
						, a.effective_date_article
						, a.expiration_date_article
						, a.cover_image_article
						, a.title_article
						, a.abstract_article
						, a.ubication_article
						, COALESCE(a.date_deleted_article,'') as date_deleted_article
						, a.date_created_article
						, COALESCE(a.date_updated_article,'') as date_updated_article
                        , COALESCE(a.date_notification_article,'') as date_notification_article
						, a.ind_notify_article
                        , u.id_user
                        , u.id_asociation_user
                        , u.email_user
                        , u.profile_user
                        , u.name_user
                        , u.last_name_user
                        , u.avatar_user
                FROM articles a
                LEFT OUTER JOIN users u
                  ON ( u.id_user = a.id_user_article )
                WHERE a.id_article = ?;";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
        if (Globals::getResult()['num_records'] == 1) {
            $this->fillAsoc(Globals::getResult()['records'][0]);
            return $response;
        }
        if (Globals::getResult()['num_records'] == 0) {
            Globals::updateResponse(404, 'Record not found', 'Record not found', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        if (Globals::getResult()['num_records'] > 1) {
            Globals::updateResponse(404, 'Duplicate record', 'Duplicate record', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
    }

    public function getAllArticlesOfAsociation() {
        $sql = "SELECT	  a.id_article
                        , a.id_asociation_article
                        , a.id_user_article
                        , a.category_article
                        , a.subcategory_article
                        , a.class_article
                        , a.state_article
                        , a.publication_date_article
                        , a.effective_date_article
                        , a.expiration_date_article
                        , a.cover_image_article
                        , a.title_article
                        , a.abstract_article
                        , a.ubication_article
                        , COALESCE(a.date_deleted_article,'') as date_deleted_article
                        , a.date_created_article
                        , COALESCE(a.date_updated_article,'') as date_updated_article
                        , COALESCE(a.date_notification_article,'') as date_notification_article
						, a.ind_notify_article
                        , u.id_user
                        , u.id_asociation_user
                        , u.email_user
                        , u.profile_user
                        , u.name_user
                        , u.last_name_user
                        , u.avatar_user
                        , COALESCE(aso.long_name_asociation, 'Genérica') as long_name_asociation
                        , COALESCE(aso.short_name_asociation, 'Genérica') as short_name_asociation
                FROM articles a
                LEFT OUTER JOIN users u
                  ON ( u.id_user = a.id_user_article )
                LEFT OUTER JOIN asociations aso
                  ON ( aso.id_asociation = a.id_asociation_article )
                WHERE ( a.id_asociation_article = ? or a.id_asociation_article = '999999999' ) ";

        $sql .= ($this->category_article !== '') ? " AND a.category_article = ?  " : " ";
        $sql .= ($this->subcategory_article !== '') ? " AND a.subcategory_article = ?  " : " ";

        $sql .= " ORDER BY a.publication_date_article DESC;";

        $arrData = array(
            $this->id_asociation_article,
        );
        if ($this->category_article !== '') {
            array_push($arrData, $this->category_article);
        };
        if ($this->subcategory_article !== '') {
            array_push($arrData, $this->subcategory_article);
        };

        $response = $this->getAll($sql, $arrData);

        return $response;

    }

    public function getImages() {

        $arrData = array(
            $this->id_article,
            $this->id_article,
        );

        $sql = "SELECT	  'cover' as kind
						, a.cover_image_article as image
                FROM articles a
                WHERE a.id_article = ?
                  AND a.cover_image_article != ''
                UNION
                SELECT	  'items-images' as kind
						, im.src_images as image
                FROM item_article i
                LEFT OUTER JOIN images im
                  ON ( i.images_id_item_article = im.id_images )
                WHERE i.id_article_item_article = ?
                  AND im.src_images != '';";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
    }

    public function fillAsoc($record) {
        foreach ($record as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = is_null($value) ? '' : $value;
            }
        }

        Helper::writeLog("article: fillAsoc -> this", $this);
    }

    public function deleteArticle() {
        $sql = "DELETE FROM articles
                WHERE id_article = ?
                  AND COALESCE(date_updated_article,'') = ? ";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->id_article,
            $this->date_updated_article,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function createArticle() {
        $this->cover_image_article = '';

        $arrDatos = array(
            $this->id_asociation_article
            , $this->id_user_article
            , $this->category_article
            , $this->subcategory_article
            , $this->class_article
            , $this->state_article
            , $this->publication_date_article
            , $this->effective_date_article
            , $this->expiration_date_article
            , $this->cover_image_article
            , $this->title_article
            , $this->abstract_article
            , $this->ubication_article
            , $this->date_notification_article
            , $this->ind_notify_article,
        );

        $sql = "INSERT INTO articles (
                                      id_asociation_article
                                    , id_user_article
                                    , category_article
                                    , subcategory_article
                                    , class_article
                                    , state_article
                                    , publication_date_article
                                    , effective_date_article
                                    , expiration_date_article
                                    , cover_image_article
                                    , title_article
                                    , abstract_article
                                    , ubication_article
                                    , date_notification_article
                                    , ind_notify_article
                                    )
                                    VALUES (?" . str_repeat(", ?", count($arrDatos) - 1) . ");";
        // VALUES (?, ?, ?, ?, ?)";

        Helper::writeLog('$sql', $sql);
        Helper::writeLog('$arrDatos', $arrDatos);

        $resUpdate = $this->insert($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateArticle() {
        $sql = "UPDATE articles
                SET   id_user_article = ?
                    , category_article = ?
                    , subcategory_article = ?
                    , class_article = ?
                    , state_article = ?
                    , publication_date_article = ?
                    , effective_date_article = ?
                    , expiration_date_article = ?
                    , cover_image_article = ?
                    , title_article = ?
                    , abstract_article = ?
                    , ubication_article = ?";

        if ($this->date_notification_article !== '') {
            $sql .= "   , date_notification_article = ?";
        }

        $sql .= "   , ind_notify_article = ?
                WHERE id_article = ?
                  AND COALESCE(date_updated_article,'') = ? ";

        $arrDatos = array(
            $this->id_user_article
            , $this->category_article
            , $this->subcategory_article
            , $this->class_article
            , $this->state_article
            , $this->publication_date_article
            , $this->effective_date_article
            , $this->expiration_date_article
            , $this->cover_image_article
            , $this->title_article
            , $this->abstract_article
            , $this->ubication_article);

        if ($this->date_notification_article !== '') {
            array_push($arrDatos, $this->date_notification_article);
        }

        array_push($arrDatos
            , $this->ind_notify_article
            , $this->id_article
            , $this->date_updated_article

        );

        Helper::writeLog('$sql', $sql);
        Helper::writeLog('$arrDatos', $arrDatos);

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateCover() {
        $sql = "UPDATE articles
                SET cover_image_article = ?
                WHERE id_article = ?
                  AND COALESCE(date_updated_article,'') = ? ";

        $arrDatos = array(
            $this->cover_image_article
            , $this->id_article
            , $this->date_updated_article,
        );
        Helper::writeLog("article: updateCover -> arrDatos", $arrDatos);

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateindNotifyArticle() {
        // $this->ind_notify_article = '9';

        $sql = "UPDATE articles
                SET ind_notify_article = ?
                WHERE id_article = ?
                  AND COALESCE(date_updated_article,'') = ? ";

        $arrDatos = array(
            $this->ind_notify_article
            , $this->id_article
            , $this->date_updated_article,
        );
        Helper::writeLog("article: updateCover -> arrDatos", $arrDatos);

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

}