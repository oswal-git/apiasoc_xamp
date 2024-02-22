<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

/**
 *
 */
class Images extends Mysql {

    public $id_images;
    public $src_images;
    public $type_images;
    public $article_id_images;
    public $item_article_id_images;
    public $date_created_images;

    public function __construct() {
        // echo "Create Article\n";
        parent::__construct();
    }

    public function getIImagesById() {

        $arrData = array(
            $this->id_images,
        );

        $sql = "SELECT	  i.id_images
						, i.src_images
						, i.type_images
						, i.article_id_images
						, i.item_article_id_images
						, i.date_created_images
                FROM images i
                WHERE i.id_images = ?;";

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

    public function getListImagesOfArticle() {
        $sql = "SELECT 	  i.id_images
                        , i.src_images
                        , i.type_images
                        , i.article_id_images
                        , i.item_article_id_images
                        , i.date_created_images
                FROM images i
                WHERE i.article_id_images = ?
                ORDER BY i.id_images ASC;";

        $arrData = array(
            $this->article_id_images,
        );

        $response = $this->getAll($sql, $arrData);

        return $response;

    }

    public function deleteImagesOfArticle() {
        $sql = "DELETE FROM images
                WHERE article_id_images = ? ";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->article_id_images,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function deleteAllArticleImages() {
        $sql = "DELETE FROM images
                WHERE article_id_images = ? ";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->article_id_images,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function deleteImages() {
        $sql = "DELETE FROM images
                WHERE id_images = ? ";

        Helper::writeLog('$sql', $sql);
        $arrData = array(
            $this->id_images,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function createImage() {
        $sql = "INSERT INTO images (
                                      src_images
                                    , type_images
                                    , article_id_images
                                    , item_article_id_images
                                    )
                        VALUES (?" . str_repeat(', ?', 3) . ")";

        Helper::writeLog('$sql', $sql);

        $arrDatos = array(
            $this->src_images
            , $this->type_images
            , $this->article_id_images
            , $this->item_article_id_images,
        );

        Helper::writeLog('$arrDatos', $arrDatos);

        $resUpdate = $this->insert($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateImageItem() {

        $sql = "UPDATE images
                  SET item_article_id_images = ?
                WHERE id_images = ? ";

        $arrDatos = array(
            $this->item_article_id_images
            , $this->id_images,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function fillAsoc($record) {
        foreach ($record as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}