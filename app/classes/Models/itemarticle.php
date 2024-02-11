<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

/**
 *
 */
class ItemArticle extends Mysql {

    public $id_item_article;
    public $id_article_item_article;
    public $text_item_article;
    public $image_item_article;
    public $images_id_item_article;
    public $date_created_item_article;

    public function __construct() {
        // echo "Create Item Article\n";
        parent::__construct();
    }

    public function getItemArticleById() {

        $arrData = array(
            $this->id_item_article,
        );

        $sql = "SELECT	  i.id_item_article
						, i.id_article_item_article
						, i.text_item_article
						, COALESCE(im.src_images, '' ) as image_item_article
						, i.images_id_item_article
						, i.date_created_item_article
                FROM item_article i
                LEFT OUTER JOIN images im
                  ON ( i.images_id_item_article = im.id_images )
                WHERE i.id_item_article = ?;";

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

    public function getListItemsOfArticle() {
        $sql = "SELECT 	  i.id_item_article
                        , i.id_article_item_article
                        , i.text_item_article
                        , COALESCE(im.src_images, '' ) as image_item_article
                        , i.images_id_item_article
						, i.date_created_item_article
                FROM item_article i
                LEFT OUTER JOIN images im
                  ON ( i.images_id_item_article = im.id_images )
                WHERE i.id_article_item_article = ?
                ORDER BY i.id_item_article ASC;";

        $arrData = array(
            $this->id_article_item_article,
        );

        $response = $this->getAll($sql, $arrData);

        return $response;

    }

    public function deleteItemsOfArticle() {
        $sql = "DELETE FROM item_article
                WHERE id_article_item_article = ? ";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->id_article_item_article,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function createItemArticle() {
        $this->image_item_article = '';

        $arrDatos = array(
            $this->id_item_article
            , $this->id_article_item_article
            , $this->text_item_article
            , $this->image_item_article
            , $this->images_id_item_article,
        );

        Helper::writeLog('$arrDatos', $arrDatos);

        $sql = "INSERT INTO item_article (
                                      id_item_article
                                    , id_article_item_article
                                    , text_item_article
                                    , image_item_article
                                    , images_id_item_article
                                    )
                        VALUES (?" . str_repeat(", ?", count($arrDatos) - 1) . ");";

        Helper::writeLog('$sql', $sql);

        $resUpdate = $this->insert($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateImageItem() {

        $sql = "UPDATE item_article
                SET image_item_article = ?
                   ,images_id_item_article = ?
                WHERE id_item_article = ?
                  AND id_article_item_article = ? ";

        $arrDatos = array(
            $this->image_item_article
            , $this->images_id_item_article
            , $this->id_item_article
            , $this->id_article_item_article,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function fillAsoc($record) {
        foreach ($record as $key => $value) {
            $this->$key = $value;
        }
    }
}