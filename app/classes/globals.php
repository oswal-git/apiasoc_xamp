<?php
namespace Apiasoc\Classes;

class Globals {
    private $base_dir;
    const BASE_URL = 'http://apiasoc.es/';
    const DB_NAME = 'db_asoc';
    const DB_USER = 'asocadminuser';
    const DB_PASSWORD = 'asl@#kjjsd%nhc$';
    const DB_HOST = 'localhost';
    const DB_CHARSET = 'charset=utf8';

    const TABLE_PREFIX = 'egl_';
    const SPD = ',';
    const SPM = '.';
    const SCURRENCY = '€';

    const URL_UPLOADS = 'http://apiasoc.es/uploads/';
    const URL_FILES = 'http://apiasoc.es/files/';

    private $dir_classes = array();
    private $dir_config;
    private static $dir_files;
    private static $dir_uploads;
    private $dir_logs;

    private static $log_file;
    private static $trace_log;

    private $nombre_web;
    private $nombre_remitente;
    private $email_remitente;
    private $email_no_responder;
    private $web_empresa;

    private $medios;

    private static $apiResponse = array(
        'status' => 0,
        'error' => '',
        'message' => '',
        'module' => '',
        'function' => '',
        'result' => null,
        'trace' => array(),
    );

    const VERSION = '2';

    public function __construct() {
        $this->init();
    }

    private function init() {
        $this->base_dir = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR;
        // $this->base_url = 'apiasoc.es/';
        // self::$db_name = 'db_asoc';
        // self::$db_user = 'asocadminuser';
        // self::$db_password = 'asl@#kjjsd%nhc$';
        // self::$db_host = 'localhost';
        // self::$db_charset = 'charset=utf8';
        // $this->table_prefix = 'egl_';
        // $this->spd = ',';
        // $this->spm = '.';
        // $this->scurrency = '€';

        self::$dir_files = $this->base_dir . 'files/';
        self::$dir_uploads = $this->base_dir . "uploads/";
        $this->dir_logs = self::$dir_files . "logs/";

        self::$trace_log = $this->dir_logs . 'log';
        self::$log_file = $this->dir_logs . 'debug';

        // array_push($this->dir_classes, $this->base_dir . "classes" . DIRECTORY_SEPARATOR);
        // array_push($this->dir_classes, $this->base_dir . "config" . DIRECTORY_SEPARATOR);

        // spl_autoload_register(array($this, 'autoLoadClasses'));
    }

    // public function autoLoadClasses($class) {

    //     for ($i = 0; $i < count($this->dir_classes); $i++):

    //         $filename = $this->dir_classes[$i] . strtolower($class) . ".php";

    //         str_replace("\\", DIRECTORY_SEPARATOR, $filename, $count);
    //         str_replace("/", DIRECTORY_SEPARATOR, $filename, $count);

    //         if (is_readable($filename)) {
    //             require $filename;
    //             return;
    //         }

    //     endfor;

    // }

    public static function updateResponse($status = 500, $error = 'unexpected error', $message = '', $module = '', $function = '', $result = null) {

        self::$apiResponse['status'] = $status;
        self::$apiResponse['error'] = $error;
        self::$apiResponse['message'] = $message;
        self::$apiResponse['module'] = $module;
        self::$apiResponse['function'] = $function;
        self::$apiResponse['result'] = $result;

        array_push(self::$apiResponse['trace'], array(
            'status' => $status,
            'error' => $error,
            'message' => $message,
            'module' => $module,
            'function' => $function,
            'result' => $result,
        ));

    }

    public static function updateMessageResponse($message) {

        self::$apiResponse['message'] = $message;

        array_push(self::$apiResponse['trace'], array(
            'status' => self::$apiResponse['status'],
            'error' => self::$apiResponse['error'],
            'message' => $message,
            'module' => self::$apiResponse['module'],
            'function' => self::$apiResponse['function'],
            'result' => self::$apiResponse['result'],
        ));

    }

    public static function httpResponse($trace = false) {

        if ($trace) {
            // Helper::displayArray(self::$apiResponse);
            Helper::writeLog('apiResponse', self::$apiResponse);
        }

        $response = array(
            'status' => self::$apiResponse['status'],
            'message' => self::$apiResponse['message'],
            'result' => self::$apiResponse['result'],
        );

        if (self::$apiResponse['error'] != '') {

        } else {

        }

        // var_dump(self::$apiResponse);
        // var_dump($response);
        http_response_code((int) self::$apiResponse['status']);

        Helper::writeLog('echo json_encode($response)', json_encode($response));
        echo json_encode($response);

    }

    public static function getError() {
        return self::$apiResponse['error'];
    }

    public static function getMessage() {
        return self::$apiResponse['message'];
    }

    public static function getResult() {
        return self::$apiResponse['result'];
    }

    public static function getApiResponse() {
        return self::$apiResponse;
    }

    /**
     * Get the value of url base
     */
    public static function getBaseUrl() {
        return self::BASE_URL;
    }

    /**
     * Get the value of db_name
     */
    public static function getDbName() {
        return self::DB_NAME;
    }

    /**
     * Get the value of db_user
     */
    public static function getDbUser() {
        return self::DB_USER;
    }

    /**
     * Get the value of db_password
     */
    public static function getDbPassword() {
        return self::DB_PASSWORD;
    }

    /**
     * Get the value of db_host
     */
    public static function getDbHost() {
        return self::DB_HOST;
    }

    /**
     * Get the value of db_charset
     */
    public static function getDbCharset() {
        return self::DB_CHARSET;
    }

    /**
     * Get the value of log_file
     */
    public static function getLoqFile() {
        return self::$log_file;
    }

    /**
     * Get the value of trace_log
     */
    public static function getTraceLog() {
        return self::$trace_log;
    }

    /**
     * Get the value of dir_files
     */
    public static function getDirFiles() {
        return self::$dir_files;
    }

    /**
     * Get the value of dir_files
     */
    public static function getDirUploads() {
        return self::$dir_uploads;
    }

    /**
     * Get the value of dir_files
     */
    public static function getUrlUploads() {
        return self::URL_UPLOADS;
    }

    /**
     * Get the value of dir_files
     */
    public static function getUrlFiles() {
        return self::URL_FILES;
    }
}