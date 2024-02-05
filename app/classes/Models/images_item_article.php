<?php
namespace Apiasoc\Classes\Models;

/**
 *
 */
class ImagesItemArticle {

    public $idImage;
    public $src;
    public $nameFile;
    public $filePath;
    public $fileImage;
    public $isSelectedFile;
    public $isDefault;
    public $isChange;

    public function __construct(
        $idImage = 0,
        $src = '',
        $nameFile = '',
        $filePath = '',
        $fileImage = null,
        $isSelectedFile = false,
        $isDefault = false,
        $isChange = false
    ) {
        $this->$idImage = $idImage;
        $this->$src = $src;
        $this->$nameFile = $nameFile;
        $this->$filePath = $filePath;
        $this->$fileImage = $fileImage;
        $this->$isSelectedFile = $isSelectedFile;
        $this->$isDefault = $isDefault;
        $this->$isChange = $isChange;
    }

    public function modify(
        $idImage = null,
        $src = null,
        $nameFile = null,
        $filePath = null,
        $fileImage = null,
        $isSelectedFile = null,
        $isDefault = null,
        $isChange = null
    ) {
        $this->$idImage = $idImage == null ? $this->$idImage : $idImage;
        $this->$src = $src == null ? $this->$src : $src;
        $this->$nameFile = $nameFile == null ? $this->$nameFile : $nameFile;
        $this->$filePath = $filePath == null ? $this->$filePath : $filePath;
        $this->$fileImage = $fileImage == null ? $this->$fileImage : $fileImage;
        $this->$isSelectedFile = $isSelectedFile == null ? $this->$isSelectedFile : $isSelectedFile;
        $this->$isDefault = $isDefault == null ? $this->$isDefault : $isDefault;
        $this->$isChange = $isChange == null ? $this->$isChange : $isChange;
    }

    public function getIdImage() {
        return $this->idImage;
    }

    public function getSrc() {
        return $this->src;
    }

    public function getNameFile() {
        return $this->nameFile;
    }

    public function getFilePath() {
        return $this->filePath;
    }

    public function getFileImage() {
        return $this->fileImage;
    }

    public function getIsSelectedFile() {
        return $this->isSelectedFile;
    }

    public function getIsDefault() {
        return $this->isDefault;
    }

    public function getIsChange() {
        return $this->isChange;
    }

    public function setIdImage($value) {
        $this->idImage = $value;
    }

    public function setSrc($value) {
        $this->src = $value;
    }

    public function setNameFile($value) {
        $this->nameFile = $value;
    }

    public function setFilePath($value) {
        $this->filePath = $value;
    }

    public function setFileImage($value) {
        $this->fileImage = $value;
    }

    public function setIsSelectedFile($value) {
        $this->isSelectedFile = $value;
    }

    public function setIsDefault($value) {
        $this->isDefault = $value;
    }

    public function setIsChange($value) {
        $this->isChange = $value;
    }

    public function getArray() {
        return array(
            "idImage" => $this->idImage,
            "src" => $this->src,
            "nameFile" => $this->nameFile,
            "filePath" => $this->filePath,
            "fileImage" => $this->fileImage,
            "isSelectedFile" => $this->isSelectedFile,
            "isDefault" => $this->isDefault,
            "isChange" => $this->isChange,
        );
    }

}