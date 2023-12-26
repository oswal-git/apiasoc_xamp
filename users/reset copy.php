<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        Helper::writeLog('$data', $data);

        $auth = new Auth();
        $asoc = new Asoc();

        foreach ($data as $key => $value) {
            $auth->$key = $value;
            // Helper::writeLog('$auth->$key', $auth->$key . ' = ' . $value);
        }

        $auth->getUserByEmail();

        if (Globals::getError() != '') {
            return true;
        }

        if (Globals::getResult()['num_records'] !== 1) {
            Globals::updateResponse(400, 'Non unique record', 'User/password not match', basename(__FILE__, ".php"), __FUNCTION__);
            return true;
        }

        if ($auth->generatePassword()) {
            return true;
        }

        $new_password = $auth->password_user;
        $auth->token_exp_user = time() + 60 * 20; // 20 minutes
        $auth->token_user = '';
        $auth->recover_password_user = 1;
        $auth->password_user = hash('sha256', $new_password . $_ENV['MAGIC_SEED']);

        if ($auth->updatePassword()) {
            return true;
        }

        $asoc->id_asociation = $auth->id_asociation_user;
        if ($asoc->getAsociationById()) {
            return true;
        }

        $subject = "Your Recovered Password";

        // echo "Please use this password to login " . $new_password . PHP_EOL;
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: ' . $asoc->name_contact_asociation . ' <' . $asoc->email_asociation . '>' . "\r\n" . 'CC: ' . 'oswal@workmail.com' . "\r\n";

        $message = '<html>';
        $message .= '<head><title>Recuperación de la contraseña</title></head>';
        $message .= '<body><h1>Recuperación de la contraseña</h1>';

        $message .= 'Hola, ' . $auth->user_name_user . ' ' . $auth->last_name_user;
        $message .= '<br>Has solicitado un nuevo password.';
        $message .= '<br>Utilize este password <br><b>' . $new_password . '</b></br> para poder ingresar';
        $message .= '<br>Si no has sido tu el que has solicitado el nuevo password, ponte en contacto con tu administrador, ' . $asoc->name_contact_asociation . '.';
        $message .= '<br>';
        $message .= '<hr>';
        $message .= '<br>';
        $message .= 'El administrador<br>';
        $message .= $asoc->name_contact_asociation . '.';
        $message .= '<br>';
        $message .= '</body>';
        $message .= '</html>';

        Helper::writeLog('subject', $subject);
        Helper::writeLog('message', $message);
        Helper::writeLog('headers', $message);
        // if (mail($auth->email_user, $subject, $message, $headers)) {
        if (mail('prueba@workmail.com', $subject, $message, $headers)) {
            // echo "Your Password has been sent to your email id" . PHP_EOL;
            Helper::writeLog('mail', 'Your Password has been sent to your email id');
        } else {
            Globals::updateResponse(500, 'Failed mail send', 'Failed to Recover your password, try again', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
            return true;
        }

        Globals::updateResponse(200, '', 'ok', basename(__FILE__, ".php"), __FUNCTION__);
        return false;

    } else {
        Globals::updateResponse(500, 'Page not found', 'Page not found', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
        return true;
    }

}
$data = array();

evaluate($data);

Helper::traceLog($data);

Globals::httpResponse();