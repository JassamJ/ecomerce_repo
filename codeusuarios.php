<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor\phpmailer\phpmailer\src\PHPMailer.php';
require 'vendor\phpmailer\phpmailer\src\SMTP.php';
require 'vendor\phpmailer\phpmailer\src\Exception.php';


require 'vendor/autoload.php';

if (isset($_POST['delete'])) {
    $registro_id = mysqli_real_escape_string($con, $_POST['delete']);

    $query = "DELETE FROM usuarios WHERE id='$registro_id' ";
    $query_run = mysqli_query($con, $query);

    if ($query_run) {
        $_SESSION['alert'] = [
            'message' => 'Usuario eliminado exitosamente',
            'title' => 'USUARIO ELIMINADO',
            'icon' => 'success'
        ];
        header("Location: usuarios.php");
        exit(0);
    } else {
        $_SESSION['alert'] = [
            'message' => 'Notifica a soporte',
            'title' => 'ERROR AL ELIMINAR',
            'icon' => 'error'
        ];
        header("Location: usuarios.php");
        exit(0);
    }
}

if (isset($_POST['update'])) {

    $id = mysqli_real_escape_string($con, $_POST['id']);
    $nombre = mysqli_real_escape_string($con, $_POST['nombre']);
    $apellidopaterno = mysqli_real_escape_string($con, $_POST['apellidopaterno']);
    $apellidomaterno = mysqli_real_escape_string($con, $_POST['apellidomaterno']);
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = $_POST['password']; // NO escapar todavía
    $rol = mysqli_real_escape_string($con, $_POST['rol']);
    $estatus = mysqli_real_escape_string($con, $_POST['estatus']);

    // Base del update
    $query = "
        UPDATE usuarios SET
            nombre = '$nombre',
            apellidopaterno = '$apellidopaterno',
            apellidomaterno = '$apellidomaterno',
            username = '$username',
            rol = '$rol',
            estatus = '$estatus'
    ";

    // 👉 Solo si el password NO está vacío
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password = '$hashed_password'";
    }

    $query .= " WHERE id = '$id'";

    $query_run = mysqli_query($con, $query);

    if ($query_run) {
        $_SESSION['alert'] = [
            'message' => 'Usuario editado exitosamente',
            'title' => 'USUARIO EDITADO',
            'icon' => 'success'
        ];
        header("Location: usuarios.php");
        exit;
    } else {
        $_SESSION['alert'] = [
            'message' => 'Notifica a soporte',
            'title' => 'ERROR AL EDITAR',
            'icon' => 'error'
        ];
        header("Location: usuarios.php");
        exit;
    }
}


if (isset($_POST['save'])) {

    $nombre = mysqli_real_escape_string($con, $_POST['nombre']);
    $apellidopaterno = mysqli_real_escape_string($con, $_POST['apellidopaterno']);
    $apellidomaterno = mysqli_real_escape_string($con, $_POST['apellidomaterno']);
    $email = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $rol = mysqli_real_escape_string($con, $_POST['rol']);
    $estatus = "1";

    // Verificar el rol y asignar el nombre correspondiente
    if ($rol == 1) {
        $rol_nombre = "Administrador";
    } elseif ($rol == 2) {
        $rol_nombre = "Colaborador";
    } else {
        $rol_nombre = "Otro"; // Por si acaso el rol no es 1 ni 2
    }

    $check_email_query = "SELECT * FROM usuarios WHERE username='$email' LIMIT 1";
    $result = mysqli_query($con, $check_email_query);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Este correo ya está registrado',
            'icon' => 'error'
        ];
        header("Location: usuarios.php");
        exit(0);
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO usuarios SET nombre='$nombre', apellidopaterno='$apellidopaterno', apellidomaterno='$apellidomaterno', username='$email', password='$hashed_password', rol='$rol', estatus='$estatus'";

        $query_run = mysqli_query($con, $query);
        if ($query_run) {

            // Configuracion SMTP
            $host = 'smtp.gmail.com';
            $port = 587;
            $username = 'jassamdavila15@gmail.com';
            $password = "zwzcxepozuamycfd";
            $security = 'tls';


            // Crear instancia PHPMailer
            $mail = new PHPMailer(true);

            // Configurar SMTP
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = $security;
            // $mail->SMTPDebug = 2;
            // $mail->Debugoutput = 'error_log';


            // Configurar correo
            $mail->setFrom('no-reply@midominio.mx', 'Mi Empresa');
            // $mail->addReplyTo($email, $nombreuser);
            $mail->addAddress($email);
            $mail->Subject = 'NUEVO USUARIO';
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);

            // Cuerpo del mensaje

            $asunto = 'Solicitud para colaborar';
            $cuerpo = '
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body style="font-family: system-ui;text-align: justify;background-color: #e7e7e7;">
                    <div style="max-width:500px;margin: 0 auto;">
                        <img style="width: 100%;background-color: #1e375c;" src="#" alt="Cintillo superior">
                    <div style="padding: 0px 30px;padding-top: 35px;">
                        <p>Estimado/a ' . $nombre . '</p>
                        <p>Tu cuenta para gestionar el catálogo de productos y servicios de Mi Empresa se creo exitosamente.</p>
                        <p>Por seguridad no compartas tus credenciales con nadie.</p>

                        <div style="padding: 3px 20px;background-color:#efefef;color:#000000;border-radius: 3px;margin: 50px 0px;text-align:left;">
                        <p style="margin-bottom: 0px;"><b>Conoce los detalles de tu cuenta:</b></p>
                        <div style="display: flex; flex-direction: column; margin: 0 auto;">
                            <div style="display: flex; flex-wrap: wrap;">
                                <p style="margin-right: 5px;margin-bottom: 0px;"><b>Nombre:</b></p>
                                <p style="flex: 2;margin-bottom: 0px;">' . $nombre . ' ' . $apellidopaterno . ' ' . $apellidomaterno . '</p>
                            </div>
                        </div>
                        
                        <p><b>Correo:</b> ' . $email . '</p>
                        <p><b>Contraseña:</b> ' . $password . '</p>
                        <p><b>Rol:</b> ' . $rol_nombre . '</p>
                        </div>

                        <p style="text-align: center;margin-top:80px;margin-bottom:0px;">Atentamente</p>
                        <p style="text-align: center;margin-top:0px;margin-bottom:50px;"><b>Equipo administrativo</b></p>
                    </div>
                    <div style="background-color: #af3335;color: #ffffff;padding: 15px 15px;font-size: 10px;text-align: center;padding-bottom: 15px;margin-bottom: 25px;">
                        <p>Este correo es enviado de manera automática por nuestro sistema de respuesta rápida.</p>
                    </div>
                    </div>
                </body>
                
                </html>';

            $mail->Body = $cuerpo;

            $correoEnviado = false;

            try {
                $correoEnviado = $mail->send();
            } catch (Exception $e) {
                error_log('Error correo: ' . $mail->ErrorInfo);
            }

            if ($query_run && $correoEnviado) {
                $_SESSION['alert'] = [
                    'title' => 'SOLICITUD EXITOSA',
                    'message' => 'Revisa tu correo electrónico',
                    'icon' => 'success'
                ];
            } else {
                $_SESSION['alert'] = [
                    'title' => 'ERROR',
                    'message' => 'El usuario se creo pero el correo no pudo enviarse',
                    'icon' => 'warning'
                ];
            }

            header("Location: usuarios.php");
            exit(0);
        } else {
            $_SESSION['alert'] = [
                'title' => 'ERROR',
                'message' => 'Notifica a soporte',
                'icon' => 'error'
            ];
            header("Location: usuarios.php");
            exit(0);
        }
    }
}
