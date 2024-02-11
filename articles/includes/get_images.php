<?php

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

function getImageData($id_article, $image_file, $target_base_path, $module, $prefix) {
    $image_data = array();
    // $data['file_name'] = $_FILES['file']['name'];
    $image_data['type'] = $image_file['type'];
    $ext = pathinfo($image_file['name'], PATHINFO_EXTENSION);
    $base_name_man = basename($image_file['name'], '.' . $ext);
    $image_data['base_name'] = preg_replace('/[^A-Za-z0-9\-]/', '', $base_name_man);
    $image_data['chain'] = Helper::generateChain(6, 'letters');
    $image_data['name'] = $image_data['base_name'] . '-' . $image_data['chain'];
    $image_data['tmp_name'] = $image_file['tmp_name'];

    $url_path = Globals::getUrlUploads() . $module . '/' . $prefix;
    $image_data['url_path'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $url_path);
    //set target file path
    $image_data['target_path'] = $target_base_path . DIRECTORY_SEPARATOR . 'article-' . $id_article . DIRECTORY_SEPARATOR . $image_file['folder'];
    $target_file_tmp = $image_data['target_path'] . DIRECTORY_SEPARATOR . $image_file['image'] . '-' . $image_data['chain'] . "." . $ext;
    $image_data['target_file'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $target_file_tmp);
    $url_file_man = $url_path . URL_SEPARATOR . 'article-' . $id_article . URL_SEPARATOR . $image_file['folder'] . URL_SEPARATOR . $image_file['image'] . '-' . $image_data['chain'] . "." . $ext;
    $image_data['url_file'] = str_replace(array('/', '\\'), URL_SEPARATOR, $url_file_man);

    Helper::writeLog('$image_data', $image_data);
    Helper::writeLog('$file_type', $image_file['file_type']);
    Helper::writeLog('name', $image_file['name']);
    Helper::writeLog('$ext', $ext);
    Helper::writeLog('$url_path', $url_path);

    return $image_data;
}
function moveUpload($image_data) {
    // define allowed mime types to upload

    $allowed_types = array('image/jpg', 'image/png', 'image/jpeg', 'application/octet-stream');
    if (!in_array($image_data['type'], $allowed_types)) {
        Globals::updateResponse(400, 'Type ' . $image_data['file_type'] . ' not allowed', 'Type ' . $image_data['file_type'] . ' not allowed.', basename(__FILE__, ".php"), __FUNCTION__);
        return true;
    }

    if (!is_dir($image_data['target_path'])) {
        mkdir($image_data['target_path'], 0777, true);
    }

    if (move_uploaded_file($image_data['tmp_name'], $image_data['target_file'])) {
        return false;
    } else {
        Globals::updateResponse(400, 'File upload failed. Please try again.', 'File upload failed. Please try again.', basename(__FILE__, ".php"), __FUNCTION__);
        return true;
    }
}
