<?php
namespace Apiasoc\Classes\Models;

use Apiasoc\Classes\Bd\Mysql;
use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;

// require_once __CORE__ . "mysql.php";
/**
 *
 */
class User extends Mysql {
    public int $id_user = 0;
    public int $id_asociation_user = 0;
    public string $user_name_user = '';
    public string $email_user = '';
    public string $password_user = '';
    public int $recover_password_user = 0;
    public string $token_user = '';
    public int $token_exp_user = 0;
    public string $question_user = '';
    public string $answer_user = '';
    public string $profile_user = '';
    public string $status_user = '';
    public string $name_user = '';
    public string $last_name_user = '';
    public string $avatar_user = '';
    public string $phone_user = '';
    public string $date_deleted_user = '';
    public string $date_created_user = '';
    public string $date_updated_user = '';
    public string $date_last_notification_user = '';
    public int $time_notifications_user = 24;
    public string $language_user = 'es';

    public function __construct() {
        // Helper::writeLog("User", '__construct');
        parent::__construct();

        // $props = get_class_vars(get_class($this));

        // foreach ($props as $nombre => $valor) {
        // echo "Type $nombre : " . gettype($valor) . PHP_EOL;
        // echo "$nombre : $valor\n";
        // }

    }

    public function userCreate() {

        $in_password = $this->password_user;
        $this->password_user = hash('sha256', $in_password . $_ENV['MAGIC_SEED']);
        $this->recover_password_user = 0;
        $this->token_user = '';
        $this->token_exp_user = 0;
        $this->time_notifications_user = 24;
        $this->language_user = 'es';

        $arrDatos = array(
            $this->id_asociation_user,
            $this->user_name_user,
            $this->email_user,
            $this->password_user,
            $this->profile_user,
            $this->status_user,
            $this->name_user,
            $this->last_name_user,
            $this->avatar_user,
            $this->phone_user,
            $this->time_notifications_user,
            $this->language_user,
        );

        $query_insert = "INSERT INTO users (
											 id_asociation_user
											,user_name_user
											,email_user
                                            ,password_user
											,profile_user
											,status_user
											,name_user
											,last_name_user
											,avatar_user
											,phone_user
                                            ,time_notifications_user
                                            ,language_user
											)
                                VALUES (?" . str_repeat(", ?", count($arrDatos) - 1) . ");";

        $resInsert = $this->insert($query_insert, $arrDatos);

        Helper::writeLog("resInsert", Helper::dep('', $resInsert, true));

        $response = $resInsert;

        return $response;
    }

    public function getDateUpdatedUserById() {

        $arrData = array(
            $this->id_user,
        );

        $sql = "SELECT 	 COALESCE(u.date_updated_user,'') as date_updated_user
                FROM users u
                WHERE u.id_user = ?;";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
        if (Globals::getResult()['num_records'] == 1) {
            $this->fillUser(Globals::getResult()['records'][0]);
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

    public function getDataUserById() {

        $arrData = array(
            $this->id_user,
        );

        $sql = "SELECT 	 u.id_user
                        ,u.id_asociation_user
                        ,u.user_name_user
                        ,u.email_user
                        ,COALESCE(u.token_user,'') as token_user
                        ,u.token_exp_user
                        ,u.recover_password_user
                        ,u.profile_user
                        ,u.status_user
                        ,u.name_user
                        ,u.last_name_user
                        ,u.avatar_user
                        ,u.phone_user
                        , COALESCE(u.date_deleted_user,'') as date_deleted_user
						,u.date_created_user
						, COALESCE(u.date_updated_user,'') as date_updated_user
                        ,u.time_notifications_user
                        ,u.language_user
                        ,a.long_name_asociation
						,a.short_name_asociation
						,a.logo_asociation
						,a.email_asociation
						,a.name_contact_asociation
						,a.phone_asociation
                FROM users u
                LEFT OUTER JOIN asociations a
                  ON ( u.id_asociation_user = a.id_asociation )
                WHERE u.id_user = ?;";

        $response = $this->getAll($sql, $arrData);
        if ($response) {
            return $response;
        }
        if (Globals::getResult()['num_records'] == 1) {
            $this->fillUser(Globals::getResult()['records'][0]);
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

    // public function getDataUserById() {

    //     $arrData = array(
    //         $this->id_user,
    //     );

    //     $sql = "SELECT      u.id_user
    //                     ,u.id_asociation_user
    //                     ,u.user_name_user
    //                     ,u.email_user
    //                     ,u.password_user
    //                     ,u.token_user
    //                     ,u.token_exp_user
    //                     ,u.recover_password_user
    //                     ,u.profile_user
    //                     ,u.status_user
    //                     ,u.name_user
    //                     ,u.last_name_user
    //                     ,u.avatar_user
    //                     ,u.phone_user
    //                     , COALESCE(u.date_deleted_user,'') as date_deleted_user
    //                     , u.date_created_user
    //                     , COALESCE(u.date_updated_user,'') as date_updated_user
    //                     ,u.time_notifications_user
    //                     ,u.language_user
    //                     ,a.long_name_asociation
    //                     ,a.short_name_asociation
    //                     ,a.logo_asociation
    //                     ,a.email_asociation
    //                     ,a.name_contact_asociation
    //                     ,a.phone_asociation
    //             FROM users u
    //             LEFT OUTER JOIN asociations a
    //               ON ( u.id_asociation_user = a.id_asociation )
    //             WHERE u.id_user = ?;";

    //     $response = $this->getAll($sql, $arrData);
    //     if ($response) {
    //         return $response;
    //     }
    //     if (Globals::getResult()['num_records'] == 1) {
    //         $this->fillUser(Globals::getResult()['records'][0]);
    //         return $response;
    //     }
    //     if (Globals::getResult()['num_records'] == 0) {
    //         Globals::updateResponse(404, 'Record not found', 'Record not found', basename(__FILE__, ".php"), __FUNCTION__);
    //         return true;
    //     }
    //     if (Globals::getResult()['num_records'] > 1) {
    //         Globals::updateResponse(404, 'Duplicate record', 'Duplicate record', basename(__FILE__, ".php"), __FUNCTION__);
    //         return true;
    //     }
    // }

    public function existUserByAsociationUsername() {

        $arrData = array(
            $this->id_asociation_user,
            $this->user_name_user,
        );

        $sql = "SELECT 	 count(*) as exist_user
                FROM users u
                WHERE u.id_asociation_user = ?
                  AND u.user_name_user = ?;";

        $response = array('status' => null, 'exist_user' => null);
        $response['status'] = $this->getAll($sql, $arrData);
        if (!$response['status']) {
            if (Globals::getResult()['num_records'] === 1) {
                Helper::writeLog('num_records ->', '1');
                // Helper::writeLog('exist_user', Helper::dep('dep: exist_user', Globals::getResult()['records'][0]['exist_user'] === 0, false));
                if ((int) Globals::getResult()['records'][0]['exist_user'] === 0) {
                    Helper::writeLog('exist_user -> ', 'no hay');
                    $response['exist_user'] = false;
                } else {
                    $response['exist_user'] = true;
                }
            } else {
                $response['status'] = true;
                Globals::updateResponse(404, 'Counter not work', 'Counter not work', basename(__FILE__, ".php"), __FUNCTION__);
            }
        }
        return $response;
    }

    public function existUserByEmail() {

        $arrData = array(
            $this->email_user,
        );

        $sql = "SELECT 	 count(*) as exist_user
                FROM users u
                WHERE u.email_user = ?;";

        $response = array('status' => '', 'exist_user' => null);
        $response['status'] = $this->getAll($sql, $arrData);
        Helper::writeLog('Globals::getResult()', Globals::getResult());

        if (!$response['status']) {
            Helper::writeLog('Globals::getResult()[records][0]', Globals::getResult()['records'][0]);
            Helper::writeLog('gettype: exist_user ->', gettype(Globals::getResult()['records'][0]['exist_user']));
            if (Globals::getResult()['num_records'] === 1) {
                if ((int) Globals::getResult()['records'][0]['exist_user'] === 0) {
                    $response['exist_user'] = false;
                } else {
                    $response['exist_user'] = true;
                }
            } else {
                $response['status'] = true;
                Globals::updateResponse(404, 'Counter not work', 'Counter not work', basename(__FILE__, ".php"), __FUNCTION__);
            }
        }
        return $response;
    }

    public function getUserByAsociationUsername() {

        $arrData = array(
            $this->id_asociation_user,
            $this->user_name_user,
        );

        $sql = "SELECT 	 u.id_user
                        ,u.id_asociation_user
                        ,u.user_name_user
                        ,u.email_user
                        ,u.password_user
                        ,u.recover_password_user
                        ,u.token_user
                        ,u.token_exp_user
                        ,u.profile_user
                        ,u.status_user
                        ,u.name_user
                        ,u.last_name_user
                        ,u.avatar_user
                        ,u.phone_user
                        , COALESCE(u.date_deleted_user,'') as date_deleted_user
						,u.date_created_user
						, COALESCE(u.date_updated_user,'') as date_updated_user
                        ,u.time_notifications_user
                        ,u.language_user
                FROM users u
                WHERE u.id_asociation_user = ?
                  AND u.user_name_user = ?;";

        $response = $this->getAll($sql, $arrData);
        if (!$response) {
            if (Globals::getResult()['num_records'] === 1) {
                $this->fillUser(Globals::getResult()['records'][0]);
            }
        }
        return $response;
    }

    public function getUserByEmail() {

        $arrData = array(
            $this->email_user,
        );

        $sql = "SELECT 	 u.id_user
                        ,u.id_asociation_user
                        ,u.user_name_user
                        ,u.email_user
                        ,u.password_user
                        ,u.recover_password_user
                        ,u.token_user
                        ,u.token_exp_user
                        ,u.profile_user
                        ,u.status_user
                        ,u.name_user
                        ,u.last_name_user
                        ,u.avatar_user
                        ,u.phone_user
                        , COALESCE(u.date_deleted_user,'') as date_deleted_user
						, u.date_created_user
						, COALESCE(u.date_updated_user,'') as date_updated_user
						, u.language_user
                FROM users u
                WHERE u.email_user = ?;";

        $response = $this->getAll($sql, $arrData);
        if (Globals::getResult()['num_records'] === 1) {
            $this->fillUser(Globals::getResult()['records'][0]);
        }
        return $response;
    }

    public function getAllUsers() {
        $sql = "SELECT 	 u.id_user
                        ,u.id_asociation_user
                        ,u.user_name_user
                        ,u.email_user
                        ,u.recover_password_user
                        ,u.token_user
                        ,u.token_exp_user
                        ,u.profile_user
                        ,u.status_user
                        ,u.name_user
                        ,u.last_name_user
                        ,u.avatar_user
                        ,u.phone_user
                        , COALESCE(u.date_deleted_user,'') as date_deleted_user
						, u.date_created_user
						, COALESCE(u.date_updated_user,'') as date_updated_user
						, u.language_user
                        ,a.long_name_asociation
						,a.short_name_asociation
						,a.logo_asociation
						,a.email_asociation
						,a.name_contact_asociation
						,a.phone_asociation
                FROM users u
                LEFT OUTER JOIN asociations a
                  ON ( u.id_asociation_user = a.id_asociation )
                ORDER BY u.email_user ASC;";

        $response = $this->getAll($sql);
        return $response;
    }

    public function getAllByIdAsociations() {

        $sql = "SELECT  u.id_user
                      , u.id_asociation_user
                      , u.user_name_user
                      , u.email_user
                      , u.recover_password_user
                      , u.token_user
                      , u.token_exp_user
                      , u.profile_user
                      , u.status_user
                      , u.name_user
                      , u.last_name_user
                      , u.avatar_user
                      , u.phone_user
                      , COALESCE(u.date_deleted_user,'') as date_deleted_user
					  , u.date_created_user
					  , COALESCE(u.date_updated_user,'') as date_updated_user
					  , u.language_user
                      , a.long_name_asociation
		              , a.short_name_asociation
		              , a.logo_asociation
		              , a.email_asociation
		              , a.name_contact_asociation
		              , a.phone_asociation
                FROM users u
                LEFT OUTER JOIN asociations a
                  ON ( u.id_asociation_user = a.id_asociation )
                WHERE u.id_asociation_user = ?
                ORDER BY u.id_user ASC;";

        $arrDatos = array(
            $this->id_asociation_user,
        );

        $response = $this->getAll($sql, $arrDatos);
        return $response;
    }

    public function deleteUser() {
        $sql = "DELETE FROM users
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrData = array(
            $this->id_user
            , $this->date_updated_user,
        );
        $response = $this->delete($sql, $arrData);

        return $response;
    }

    public function fillUser($record) {
        foreach ($record as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = is_null($value) ? '' : $value;
            }
        }
    }

    public function createProfile() {

        $in_answer = $this->answer_user;
        $this->answer_user = hash('sha256', $in_answer . $_ENV['MAGIC_SEED']);
        Helper::writeLog('$this->answer_user', $this->answer_user);
        $in_password = $this->password_user;
        $this->password_user = hash('sha256', $in_password . $_ENV['MAGIC_SEED']);
        Helper::writeLog('$this->password_user', $this->password_user);
        $this->recover_password_user = 0;
        $this->token_user = '';
        $this->token_exp_user = 0;
        $this->time_notifications_user = 24;
        $this->language_user = 'es';

        $arrData = array(
            $this->id_asociation_user
            , $this->user_name_user
            , $this->email_user
            , $this->password_user
            , $this->recover_password_user
            , $this->token_user
            , $this->token_exp_user
            , $this->question_user
            , $this->answer_user
            , $this->profile_user
            , $this->status_user
            , $this->name_user
            , $this->last_name_user
            , $this->avatar_user
            , $this->phone_user
            , $this->time_notifications_user
            , $this->language_user,
        );

        Helper::writeLog('$arrDatos', $arrData);

        $sql = "INSERT INTO users (
                            id_asociation_user
                            ,user_name_user
                            ,email_user
                            ,password_user
                            ,recover_password_user
                            ,token_user
                            ,token_exp_user
                            ,question_user
                            ,answer_user
                            ,profile_user
                            ,status_user
                            ,name_user
                            ,last_name_user
                            ,avatar_user
                            ,phone_user
                            ,time_notifications_user
                            ,language_user
                        )
                        VALUES (?" . str_repeat(", ?", count($arrData) - 1) . ");";

        Helper::writeLog('$sql', $sql);
        $resUpdate = $this->insert($sql, $arrData);

        return $resUpdate;
    }

    public function updateProfile() {
        $sql = "UPDATE users
                SET id_asociation_user = ?
                    , user_name_user = ?
                    , time_notifications_user = ?
                    , language_user = ?
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrDatos = array(
            $this->id_asociation_user
            , $this->user_name_user
            , $this->time_notifications_user
            , $this->language_user
            , $this->id_user
            , $this->date_updated_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateProfileStatus() {
        $sql = "UPDATE users
                SET profile_user = ?
                    , status_user = ?
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrDatos = array(
            $this->profile_user
            , $this->status_user
            , $this->id_user
            , $this->date_updated_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateProfileAvatar() {
        $sql = "UPDATE users
                SET id_asociation_user = ?
                    , user_name_user = ?
                    , avatar_user = ?
                    , time_notifications_user = ?
                    , language_user = ?
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrDatos = array(
            $this->id_asociation_user
            , $this->user_name_user
            , $this->avatar_user
            , $this->time_notifications_user
            , $this->language_user
            , $this->id_user
            , $this->date_updated_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateUserProfile() {
        $sql = "UPDATE users
                SET id_asociation_user = ?
                    , user_name_user = ?
                    , name_user = ?
                    , last_name_user = ?
                    , phone_user = ?
                    , time_notifications_user = ?
                    , language_user = ?
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrDatos = array(
            $this->id_asociation_user
            , $this->user_name_user
            , $this->name_user
            , $this->last_name_user
            , $this->phone_user
            , $this->time_notifications_user
            , $this->language_user
            , $this->id_user
            , $this->date_updated_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateUser() {
        $sql = "UPDATE users
                SET id_asociation_user = ?
                    , user_name_user = ?
                    , name_user = ?
                    , last_name_user = ?
                    , email_user = ?
                    , phone_user = ?
                    , profile_user = ?
                    , status_user = ?
                    , time_notifications_user = ?
                    , language_user = ?
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrDatos = array(
            $this->id_asociation_user
            , $this->user_name_user
            , $this->name_user
            , $this->last_name_user
            , $this->email_user
            , $this->phone_user
            , $this->profile_user
            , $this->status_user
            , $this->time_notifications_user
            , $this->language_user
            , $this->id_user
            , $this->date_updated_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateDateLastNotificationUser() {
        // $this->date_last_notification_user =  date("Y-m-d H:i:s",  time());

        $sql = "UPDATE users
                SET date_last_notification_user = DATE_ADD( ?, INTERVAL 1 SECOND)
                  , date_updated_user = ?
                WHERE id_user = ? ";

        $arrDatos = array(
            $this->date_last_notification_user
            , $this->date_updated_user
            , $this->id_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function updateAvatar() {
        $sql = "UPDATE users
                SET avatar_user = ?
                WHERE id_user = ?
                  and COALESCE(date_updated_user,'') = ? ";

        $arrDatos = array(
            $this->avatar_user
            , $this->id_user
            , $this->date_updated_user,
        );

        $resUpdate = $this->update($sql, $arrDatos);
        return $resUpdate;
    }

    public function getUserByUsernameAndAsociationIdAndQuestion() {
        $arrData = array(
            $this->user_name_user,
            $this->id_asociation_user,
            $this->question_user,
        );

        $sql = "SELECT 	 u.id_user
                        ,u.id_asociation_user
                        ,u.user_name_user
                        ,u.question_user
                        ,u.answer_user
                        ,u.profile_user
                        ,u.avatar_user
                        , COALESCE(u.date_deleted_user,'') as date_deleted_user
                        ,u.date_created_user
                        , COALESCE(u.date_updated_user,'') as date_updated_user
                        ,u.language_user
                        ,a.long_name_asociation
                        ,a.short_name_asociation
                        ,a.logo_asociation
                FROM users u
                LEFT OUTER JOIN asociations a
					ON ( u.id_asociation_user = a.id_asociation )
                    WHERE u.user_name_user = ?
                      and u.id_asociation_user = ?
                      and u.question_user = ?
					ORDER BY u.question_user ASC;";

        $response = $this->getAll($sql, $arrData);
        if (Globals::getResult()['num_records'] === 1) {
            $this->fillUser(Globals::getResult()['records'][0]);
        }
        return $response;

    }

    public function getAllQuestionByUsernameAndAsociationId() {

        $arrData = array(
            $this->user_name_user,
            $this->id_asociation_user,
        );

        $sql = "SELECT DISTINCT
                    	 u.id_user
                        ,u.id_asociation_user
                        ,u.user_name_user
                        ,u.question_user
                        , COALESCE(u.date_updated_user,'') as date_updated_user
                        ,u.language_user
                        ,a.long_name_asociation
                        ,a.short_name_asociation
                        ,a.logo_asociation
                FROM users u
                LEFT OUTER JOIN asociations a
					ON ( u.id_asociation_user = a.id_asociation )
                WHERE u.user_name_user = ?
                    and u.id_asociation_user = ?
                ORDER BY u.question_user ASC;";

        $response = $this->getAll($sql, $arrData);
        if (Globals::getResult()['num_records'] === 1) {
            $this->fillUser(Globals::getResult()['records'][0]);
        }
        return $response;

    }
}