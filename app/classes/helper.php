<?php
namespace Apiasoc\classes;

class Helper {
    private static $debug = true;

    public function __construct() {
    }

    public static function dep($comment, $datos, $salida = false) {
        $res = print_r('<pre>' . $comment, $salida);
        $res .= print_r($datos, $salida);
        $res .= print_r('</pre>', $salida);
        return $res;
    }

    public static function httpHeaderDev($options) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: access");
        header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if ($options == 'OPTIONS') {
            exit();
        }

        return;
    }

    /**
     * Get header Authorization
     * */
    public static function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public static function arrayToStringWithKey($array) {
        $strArray = implode(', ', array_map(
            function ($v, $k) {
                return sprintf("%s='%s'", $k, $v);
            },
            $array,
            array_keys($array)
        ));

        return $strArray;
    }

    public static function displayArray($array) {
        static $innerLevel = 0;
        static $fin = false;

        if ($innerLevel === 0) {
            echo "\n<br>********** Display array *****************<br>\n";
            echo "<br>\n";
        }

        if (array_values($array) === $array) {
            for ($i = 0; $i < count($array); $i++) {

                if (is_array($array[$i])) {
                    // $innerLevel++;
                    echo "<br>" . str_repeat('    ', $innerLevel * 0.5) . "Item: " . $i;
                    $innerLevel++;
                    self::displayArray($array[$i]);
                    $innerLevel--;
                } else {
                    if (is_object($array[$i])) {
                        echo "\n<br>  **** " . $i . ": " . print_r($array[$i], true);
                    } else {
                        echo "\n<br>  **** " . $i . ": " . $array[$i];
                    }
                }
            }
        } else {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    echo "\n<br>" . str_repeat('    ', $innerLevel * 0.4) . $key;
                    // $innerLevel++;
                    echo "\n<br>" . str_repeat('    ', $innerLevel * 0.6) . "num traces: " . count($value);
                    $innerLevel++;
                    self::displayArray($value);
                    $innerLevel--;
                } else {
                    if (is_object($value)) {
                        echo "\n<br>" . str_repeat('    ', $innerLevel) . $key . ": " . print_r($value, true);
                    } else {
                        echo "\n<br>" . str_repeat('    ', $innerLevel) . $key . ": " . $value;
                    }
                }
            }
        }

        // if (!$fin) {
        //     $fin = true;
        // }

        if ($innerLevel === 0) {
            echo "<br>\n";
            echo "<br>\n******* Fin  Display array **********   $innerLevel  <br>\n";
        }
    }

    public static function displayArrayToText($array, &$output) {
        static $innerLevel = 0;
        static $levels = 0;
        $fin = false;
        // static $output = '';

        if ($innerLevel === 0) {
            $output .= "\n" . $innerLevel . " ->  ********** Display array *****************\n";
            $output .= "\n" . $innerLevel . " ->  ";
        }

        if (array_values($array) === $array) {
            for ($i = 0; $i < count($array); $i++) {

                if (is_array($array[$i])) {
                    // $levels++;
                    // $innerLevel++;
                    $output .= "\n" . $innerLevel . " -> " . str_repeat('    ', $innerLevel + 0.5) . "Item: " . $i;
                    $levels++;
                    $innerLevel++;
                    self::displayArrayToText($array[$i], $output);
                    $levels--;
                    // $levels--;
                    // $innerLevel--;
                    $innerLevel--;
                } else {
                    if (is_object($array[$i])) {
                        $output .= "\n" . $innerLevel . " ->   **** " . $i . ": " . print_r($array[$i], true);
                    } else {
                        $output .= "\n" . $innerLevel . " ->   **** " . $i . ": " . $array[$i];
                    }
                }
            }
        } else {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $output .= "\n" . $innerLevel . " -> " . str_repeat('    ', $innerLevel * 0.5) . $key;
                    $levels++;
                    $innerLevel++;
                    // $output .= str_repeat('    ', $innerLevel) . "num traces: " . count($value) . " \n";
                    // $levels++;
                    // $innerLevel++;
                    self::displayArrayToText($value, $output);
                    $levels--;
                    $innerLevel--;
                } else {
                    if (is_object($value)) {
                        $output .= "\n" . $innerLevel . " ->    **** " . $key . ": " . print_r($value, true);
                    } else {
                        $output .= "\n" . $innerLevel . " -> " . str_repeat('    ', $innerLevel) . $key . ": " . $value;
                    }
                }
            }
        }

        // if (!$fin) {
        //     $output .= "\n" . $innerLevel . " -> ";
        //     $output .= "\n" . $innerLevel . " -> ******* Fin  Display array **********   $innerLevel  \n";
        //     $fin = true;
        // }

        // $innerLevel = $innerLevel - $levels;
        $levels = 0;
        if ($innerLevel === 0) {
            $output .= "\n" . $innerLevel . " -> ";
            $output .= "\n" . $innerLevel . " -> ******* Fin  level **********   $innerLevel  \n";
            $res = $output;
            // $output = '';
            return $res;
        }
    }

    public static function writeLog($comment, $message) {

        if (!self::$debug) {
            return;
        }

        // Determine log file
        $tracefile = Globals::getLoqFile() . "_" . date("Y-m-d", time()) . ".log";
        // echo $tracefile . "\n";

        // Get time of request
        if (($time = $_SERVER['REQUEST_TIME']) == '') {
            $time = time();
        }

        // Get IP address
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }

        // Get requested script
        if (($request_uri = $_SERVER['REQUEST_URI']) == '') {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }

        // Format the date and time
        $date = date("Y-m-d H:i:s", $time);

        // Data input
        $salidaText = '';
        if (\is_array($message)) {
            $input_message = self::displayArrayToText($message, $salidaText);
        } else {
            if (\is_object($message)) {
                $input_message = print_r($message, true);
            } else {
                $input_message = $message;
            }
        }

        $file_data = $date . ', '
        . $remote_addr . ', '
        . $request_uri . ', '
        . $comment . "\n"
        . "############# message:\n"
        . $input_message . "\n"
        // . "#####################################################################:\n\n\n"
        // . "############# salidaText:\n"
        // . $salidaText . "\n"
         . "#####################################################################:\n\n\n";

        // Append to the log file
        if ($fd = @fopen($tracefile, "a")) {
            $result = fwrite($fd, $file_data);
            fclose($fd);

            if ($result > 0) {
                return array('estado' => true);
            } else {
                return array('estado' => false, 'msg' => 'Unable to write to ' . $tracefile . '!');
            }

        } else {
            return array('estado' => false, 'msg' => 'Unable to open log ' . $tracefile . '!');
        }
    }

    public static function traceLog($data, $comment = '') {
        $tracefile = Globals::getTraceLog() . "_" . date("Y-m-d", time()) . ".log";
        // echo 'tracefile: ' . $tracefile;

        // Get time of request
        if (($time = $_SERVER['REQUEST_TIME']) == '') {
            $time = time();
        }

        // Get IP address
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }

        // Get requested script
        if (($request_uri = $_SERVER['REQUEST_URI']) == '') {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }

        // Format the date and time
        $date = date("Y-m-d H:i:s", $time);

        $salidaText = '';
        // Data input
        if (\is_array($data)) {
            $input_data = self::displayArrayToText($data, $salidaText);
            $input_data2 = $salidaText;
        } else {
            $input_data = $data;
            $input_data2 = $data;
        }

        $salidaText = '';
        // Data output
        $output_data = self::displayArrayToText(Globals::getApiResponse(), $salidaText);
        $output_data2 = $salidaText;

        // Append to the log file
        try {
            $new_data = $date . ', '
            . $remote_addr . ', '
            . $request_uri . ', '
            . $comment . "\n"
            . "input data:\n"
            . $input_data . "\n"
            . "output data:\n"
            . $output_data . "\n\n"
            // . "input data 2:\n"
            // . $input_data2 . "\n"
            // . "output data 2:\n"
            // . $output_data2 . "\n\n"
             . "#####################################################################:\n\n\n";

            $file_data = '';
            if (file_exists($tracefile)) {
                $file_data = file_get_contents($tracefile);
            }
            $result = file_put_contents($tracefile, $new_data . $file_data);

            if ($result > 0) {
                return array('estado' => true);
            } else {
                print_r(array('estado' => false, 'msg' => 'Unable to write to ' . $tracefile . '!'));
                return array('estado' => false, 'msg' => 'Unable to write to ' . $tracefile . '!');
            }

        } catch (\Exception $e) {
            print_r(array('estado' => false, 'msg' => $e->message . '!'));
            return array('estado' => false, 'msg' => $e->message . '!');
        }

    }

    public static function generateChain($length = 8, $type = 'all') {

        switch ($type) {
        case 'number':
            $keyspace = $_ENV['KEYSPACE_NUMBER'];
            break;

        case 'letters':
            $keyspace = $_ENV['KEYSPACE_LETTERS'];
            break;

        case 'all':
        default:
            $keyspace = $_ENV['KEYSPACE'];
            break;

        }

        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            Globals::updateResponse(400, '$keyspace must be at least two characters long', 'Error get new password', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }

        return $str;
    }

    public static function deleteFolder($directory) {
        if (is_dir($directory)) {
            if (file_exists($directory)) {
                $di = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);
                $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($ri as $file) {
                    Helper::writeLog('read $files', $file);
                    if ($file->isDir()) {
                        Helper::writeLog('delete dir', $file);
                        rmdir($file);
                    } else {
                        Helper::writeLog('delete file', $file);
                        unlink($file);
                    }
                }
                Helper::writeLog('delete dir', $directory);
                rmdir($directory);
            }
        }
    }
}