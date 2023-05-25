<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth extends User {

    public string $new_password_user = '';

    public function __construct() {
        // Helper::writeLog("Auth", '__construct');
        parent::__construct();
    }

    public function getHeaders() {

    }

    public function createTokenJwt() {

        $payload = [
            'iss' => 'localhost',
            'aud' => 'localhost',
            'exp' => $this->token_exp_user, // 10'
            'data' => [
                'id_user' => $this->id_user,
            ],
        ];

        $jwt = JWT::encode($payload, $_ENV['MAGIC_SEED'], 'HS256');

        return $jwt;
    }

    public function validateTokenJwt(string $token) {

        if ($token === '') {
            Globals::updateResponse(400, 'Empty token', 'Empty token', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        try {
            $data = JWT::decode($token, new Key($_ENV['MAGIC_SEED'], 'HS256'));
            Helper::writeLog('validateTokenJwt: data', $data);
            Globals::updateResponse(200, '', 'Token ok', basename(__FILE__, ".php"), __FUNCTION__, $data);
            return false;

        } catch (\Exception $e) {
            switch ($e->getMessage()) {
            case 'Expired token':
                list($header, $payload, $signature) = explode(".", $token);
                $payload = json_decode(base64_decode($payload));
                Helper::writeLog('validateTokenJwt: payload', $payload);
                Globals::updateResponse(400, 'Expired token', 'Expired token', basename(__FILE__, ".php"), __FUNCTION__, $payload);
                return true;
                break;
            default:
                Globals::updateResponse(401, $e->getMessage(), $e->getMessage(), basename(__FILE__, ".php"), __FUNCTION__);
                return true;
            }
        }

        Globals::updateResponse(401, 'Unexpect decode token error', 'Unexpect decode token error', basename(__FILE__, ".php"), __FUNCTION__);
        return true;
    }

    public function updatePassword() {

        $sql = "UPDATE users
					SET password_user = ?
					  , token_user = ?
					  , token_exp_user = ?
					  , recover_password_user = ?
                WHERE id_user = ? ";

        $arrDatos = array(
            $this->password_user
            , $this->token_user
            , $this->token_exp_user
            , $this->recover_password_user
            , $this->id_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateToken() {

        $sql = "UPDATE users
					SET token_user = ?
					  , token_exp_user = ?
					  , recover_password_user = ?
                WHERE id_user = ? ";

        $arrDatos = array(
            $this->token_user
            , $this->token_exp_user
            , $this->recover_password_user
            , $this->id_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int $length      How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     * @return string
     */
    function generatePassword($length = 8, $keyspace = null) {
        if (is_null($keyspace)) {$keyspace = $_ENV['KEYSPACE'];}
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            Globals::updateResponse(400, '$keyspace must be at least two characters long', 'Error get new password', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        $this->password_user = $str;
        return false;
    }
}