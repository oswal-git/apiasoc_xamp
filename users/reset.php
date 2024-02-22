<?php
require_once "../config/bootstrap.php";

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Apiasoc\Classes\Models\Asoc;
use Apiasoc\Classes\Models\Auth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function evaluate(&$data) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        Helper::writeLog('$data', $data);

        $auth = new Auth();
        $asoc = new Asoc();

        foreach ($data as $key => $value) {
            if (property_exists($item_aauthrticle, $key)) {
                $auth->$key = $value;
                // Helper::writeLog('$auth->$key', $auth->$key . ' = ' . $value);
            }
        }

        if ($auth->email_user !== '') {
            $email = true;
            $auth->getUserByEmail();
        } else {
            $email = false;
            $auth->getUserByAsociationUsername();
        }

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

        $mail = new PHPMailer(true);

        try {
            //code...

            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
            $mail->SMTPDebug = SMTP::DEBUG_OFF; //Disable verbose debug output
            $mail->isSMTP(); //Send using SMTP
            $mail->Host = 'smtp.gmail.com'; //Set the SMTP server to send through
            $mail->SMTPAuth = true; //Enable SMTP authentication
            $mail->Username = 'eglos2022w@gmail.com'; //SMTP username
            $mail->Password = 'ijvpqryisoxlovvo'; //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption.
            $mail->Port = 465; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            // Recipients
            $mail->setFrom('noresponda@mail.es', 'admin');
            // $mail->addAddress($auth->email_user,$auth->name_user);
            if ($email) {
                $mail->addAddress('prueba@workmail.com', $auth->name_user);
                // $mail->addReplyTo('prueba@workmail.com',$auth->name_user);
            } else {
                $mail->addAddress('oswal@workmail.com', $auth->name_user);
            }
            // $mail->addCC('oswal@workmail.com','oswal');
            $mail->addBCC('ocontrerasm@gmail.com', 'oswal');

            // Attachments: Uno o varios ficheros
            // $mail->addAttachment('file');

            $mail->isHTML(true);
            $mail->Subject = "Your Recovered Password";

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

            $mail->Body = $message;

            Helper::writeLog('subject', $mail->Subject);
            Helper::writeLog('message', $mail->Body);

            if (!$mail->send()) {
                // echo 'Error al enviar email';
                Helper::writeLog("mail error; Message could not be sent. Mailer Error", $mail->ErrorInfo);
                Globals::updateResponse(500, 'Mail error', 'Mail error', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
                return true;
            } else {
                // echo "Your Password has been sent to your email id" . PHP_EOL;
                Helper::writeLog('mail', 'Your Password has been sent to your email id');
            }

        } catch (\Exception $e) {
            Helper::writeLog("mail error; Message could not be sent. Mailer Error", $mail->ErrorInfo);
            Helper::writeLog("mail error: e; Message could not be sent. Mailer Error", $e);
            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            Globals::updateResponse(500, 'Mail error', 'Mail error', basename(__FILE__, ".php"), __FUNCTION__, $_SERVER['REQUEST_METHOD']);
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