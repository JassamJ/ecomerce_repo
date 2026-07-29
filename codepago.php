<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApiTransactionError;
use Openpay\Data\OpenpayApiRequestError;
use Openpay\Data\OpenpayApiConnectionError;
use Openpay\Data\OpenpayApiAuthError;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

require 'dbcon.php';

if (isset($_POST['delete'])) {
    $registro_id = mysqli_real_escape_string($con, $_POST['delete']);

    $query = "DELETE FROM pedidos WHERE id='$registro_id' ";
    $query_run = mysqli_query($con, $query);

    if ($query_run) {
        header("Location: industrias.php");
        exit(0);
    } else {
        header("Location: industrias.php");
        exit(0);
    }
}



if (isset($_POST['update'])) {

    if (!isset($_POST['identificador']) || empty($_POST['identificador'])) {
        die('Identificador no recibido');
    }

    $identificador = $_POST['identificador'];


    $stmt = $con->prepare("
    SELECT nombre, apellidop, apellidom, email, telefono, total
    FROM pedidos
    WHERE identificador = ?
    LIMIT 1
");

    if (!$stmt) {
        die($con->error);
    }

    $stmt->bind_param("s", $identificador);
    $stmt->execute();


    $stmt->bind_result(
        $nombre,
        $apellidop,
        $apellidom,
        $email,
        $telefono,
        $total
    );

    if (!$stmt->fetch()) {
        die('Pedido no encontrado');
    }


    $pedido = [
        'nombre'     => $nombre,
        'apellidop'  => $apellidop,
        'apellidom'  => $apellidom,
        'email'      => $email,
        'telefono'   => $telefono,
        'total'      => $total
    ];
    $stmt->close();


    $openpay = Openpay::getInstance(
        $_ENV['OPENPAY_ID'],
        $_ENV['OPENPAY_SK'],
        $_ENV['OPENPAY_COUNTRY'],
        $_SERVER['REMOTE_ADDR']
    );

    Openpay::setProductionMode(true);

    $customer = [
        'name'         => $pedido['nombre'],
        'last_name'    => trim($pedido['apellidop'] . ' ' . $pedido['apellidom']),
        'phone_number' => $pedido['telefono'],
        'email'        => $pedido['email'],
    ];

    $method = $_POST['payment_method'];

   $montoFinal = number_format((float)$pedido['total'], 2, '.', '');

    try {
        if ($method === 'card') {

            $chargeData = array(
                'method'            => 'card',
                'source_id'         => $_POST["token_id"],
                'amount'            => $montoFinal,
                'description'       => 'Pedido productos #' . '' . $identificador,
                'order_id'          => $identificador . '_' . time(),
                'device_session_id' => $_POST["deviceIdHiddenFieldName"],
                'customer'          => $customer,
                'redirect_url'      => 'https://midominio.mx/productos/openpay-respuesta.php'
            );
        } else {

            $chargeData = array(
                'method'      => 'bank_account',
                'amount'      => $montoFinal,
                'description' => 'Pedido #' . $identificador,
                'order_id'          => $identificador . '_' . time(),
                'customer'    => $customer
            );
        }
        $charge = $openpay->charges->create($chargeData);
        if ($method === 'bank_account') {

            $vigencia = $charge->due_date;
            $bank = $charge->payment_method->bank;
            $clabe = $charge->payment_method->clabe;
            $convenio = $charge->payment_method->agreement;
            $referencia = $charge->payment_method->name;
            $url_pdf = $charge->payment_method->url_spei;

            $fechaObj = new DateTime($vigencia);

            $formateador = new IntlDateFormatter(
                'es_ES',
                IntlDateFormatter::LONG,
                IntlDateFormatter::SHORT,
                'America/Mexico_City',
                IntlDateFormatter::GREGORIAN
            );


            $vigenciaAmigable = $formateador->format($fechaObj);

            $update_stmt = $con->prepare("UPDATE pedidos SET 
        status_pago = 'Pendiente SPEI', 
        openpay_id = ?, 
        pdf_url = ?, 
        clabe = ?,
        vigencia = ?,
        banco = ?,
        convenio = ?,
        referencia = ? 
        WHERE identificador = ?");

            $update_stmt->bind_param("ssssssss", $charge->id, $url_pdf, $clabe, $vigenciaAmigable, $bank, $convenio, $referencia, $identificador);
            $update_stmt->execute();

            notifyCustomer($identificador, $email, $bank, $clabe, $convenio, $referencia, $url_pdf, $montoFinal, $vigenciaAmigable);
            header("Location: orden.php?id=" . $identificador);
            exit();
        } else {
            if ($charge->status == 'completed') {

                // Caso A: Pago inmediato y exitoso
                $update_stmt = $con->prepare("UPDATE pedidos SET status_pago = 'Pagado', openpay_id = ? WHERE identificador = ?");
                $update_stmt->bind_param("ss", $charge->id, $identificador);
                $update_stmt->execute();

                header("Location: orden.php?id=" . $identificador);
                exit();
            } else if ($charge->status == 'charge_pending') {
                // Caso B: Requiere validación 3D Secure
                // Página del banco
                if ($method === 'bank_account') {
                    header("Location: " . $charge->payment_method->url_spei);
                } else {
                    header("Location: " . $charge->payment_method->url);
                }
                exit();
            }
        }
    } catch (OpenpayApiTransactionError $e) {
        handleOpenpayError($e, $identificador);
    } catch (OpenpayApiRequestError $e) {
        handleOpenpayError($e, $identificador);
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'title'   => 'ERROR DEL SISTEMA',
            'message' => 'Contacta a soporte: ' . $e->getMessage(),
            'icon'    => 'error'
        ];
        header("Location: pago.php?id=$identificador");
        exit(0);
    }

    exit(0);
}

function handleOpenpayError($e, $identificador)
{
    $errorCode = $e->getErrorCode();

    switch ($errorCode) {
        case 3001:
            $message = 'La tarjeta fue rechazada';
            break;
        case 3002:
            $message = 'La tarjeta ha expirado';
            break;
        case 3003:
            $message = 'Fondos insuficientes';
            break;
        case 3004:
            $message = 'La tarjeta fue rechazada';
            break;
        case 3005:
            $message = 'La tarjeta fue rechazada';
            break;
        case 2005:
            $message = 'La fecha de expiración es incorrecta';
            break;
        case 15001:
            $message = 'La autenticación de la tarjeta falló. Por favor, intenta con otro método de pago o contacta a tu banco.';
            break;
        default:
            $message = 'Error (' . $errorCode . '): ' . $e->getMessage();
            break;
    }

    $_SESSION['alert'] = [
        'title'   => 'PAGO NO APROBADO',
        'message' => $message,
        'icon'    => 'error'
    ];

    header("Location: pago.php?id=$identificador");
    exit(0);
}

if (isset($_POST['save'])) {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidop = trim($_POST['apellidop'] ?? '');
    $apellidom = trim($_POST['apellidom'] ?? '');
    $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telefono  = trim($_POST['telefono'] ?? '');
    $calle     = trim($_POST['calle'] ?? '');
    $exterior  = trim($_POST['exterior'] ?? '');
    $interior  = trim($_POST['interior'] ?? '');
    $colonia   = trim($_POST['colonia'] ?? '');
    $ciudad    = trim($_POST['ciudad'] ?? '');
    $estado    = trim($_POST['estado'] ?? '');
    $postal    = trim($_POST['postal'] ?? '');
    $pais      = trim($_POST['pais'] ?? '');
    $cupon     = trim($_POST['cuponLS'] ?? '');
    $productos = $_POST['cartLS'] ?? '';
    $estatus   = 1;

    $sql = "INSERT INTO pedidos 
            (nombre, apellidop, apellidom, email, telefono, calle, exterior, interior, colonia, ciudad, estado, postal, pais, cupon, productos, estatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);

    $stmt->bind_param(
        "sssssssssssssssi",
        $nombre,     // 1
        $apellidop,  // 2
        $apellidom,  // 3
        $email,      // 4
        $telefono,   // 5
        $calle,      // 6
        $exterior,   // 7
        $interior,   // 8
        $colonia,    // 9
        $ciudad,     // 10
        $estado,     // 11
        $postal,     // 12
        $pais,       // 13
        $cupon,      // 14
        $productos,  // 15
        $estatus     // 16
    );

    if ($stmt->execute()) {
        $last_id = $con->insert_id;
        $stmt->close();

        // Generar Identificador
        $folio_num = str_pad($last_id, 7, "0", STR_PAD_LEFT);
        $iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellidop, 0, 1) . substr($apellidom, 0, 1));
        $identificador = "MIEMPRESA-$folio_num-$iniciales";

        $up_stmt = $con->prepare("UPDATE pedidos SET identificador=? WHERE id=?");
        $up_stmt->bind_param("si", $identificador, $last_id);
        $up_stmt->execute();
        $up_stmt->close();

        header("Location: pago.php?id=$identificador");
        exit(0);
    } else {
        // error_log($stmt->error); 
        header("Location: pedido.php");
        exit(0);
    }
}


function notifyCustomer($identificador, $email, $bank, $clabe, $convenio, $referencia, $url_pdf, $total, $vigenciaAmigable)
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'mail.dominio.mx';
    $mail->Port = 465;
    $mail->SMTPAuth = true;
    $mail->Username = 'no-reply@dominio.mx';
    $mail->Password = '=@dH6mqA5H7%MEa,';
    $mail->SMTPSecure = 'ssl';

    $mail->setFrom('no-reply@dominio.mx', 'MI EMPRESA');
    $mail->addAddress($email);
    $mail->Subject = 'Realiza tu pago por SPEI';
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    $body = '
            <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background:#ffffff; font-family:Arial, sans-serif;">

   <div style="background-color: #f3f3f3; max-width: 600px; margin: 0px auto; text-align: center; line-height: 100px;">
     <img src="https://dominio.com/images/logo.png" 
         style="width: 90%; vertical-align: middle; display: inline-block;padding: 10px 0;" 
         alt="">
</div>


    <div style="
                max-width:600px;
                background:#ffffff;
                margin:0px auto 10px;
                padding:15px;
            ">

       
        <h1 style="font-size:25px; margin:30px 0; text-align:left;">
            REALIZA TU PAGO POR SPEI
        </h1>

        <p>Estas a un paso de finalizar tu pedido, realiza tu pago por SPEI antes del <strong>' . $vigenciaAmigable . '</strong> con los siguientes datos:</p>

        <div style="
                    background: #2c3b5c; 
                    color:#fff; 
                    padding:15px; 
                    border-radius:3px;
                    margin:30px 0;
                ">
            <p><strong>Beneficiario:</strong> DOMINIO</p>
            <p><strong>Concepto:</strong> Pedido #' . $identificador . '</p>
            <p><strong>Total a pagar:</strong> $' . number_format($total, 2) . '</p>
            <p><strong>Banco:</strong> ' . $bank . '</p>
            <p><strong>Referencia:</strong> ' . htmlspecialchars(implode(' ', str_split($referencia, 4)), ENT_QUOTES, 'UTF-8') . '</p>
<p><strong>CLABE (Con otros bancos):</strong> ' . htmlspecialchars(implode(' ', str_split($clabe, 4)), ENT_QUOTES, 'UTF-8') . '</p>
<p><strong>Convenio CIE (Con BBVA):</strong> ' . htmlspecialchars(implode(' ', str_split($convenio, 3)), ENT_QUOTES, 'UTF-8') . '</p>
        </div>

        <p>También puedes consultar la referencia de pago <a href="https://dominio.mx/productos/orden.php?id=' . $identificador . '" target="_blank">aquí</a>.</p>

        <p>¿Quieres cambiar tu método de pago o necesitas generar una nueva referencia SPEI? Ingresa a: <a href="https://dominio.mx/productos/pago.php?id=' . $identificador . '">https://dominio.mx/productos/pago.php?id=' . $identificador . '</a></p>

        <p style="text-align:center;"><strong>EQUIPO DE VENTAS</strong></p>
        <p style="text-align:center;">MI EMPRESA</p>

        <p style="font-size:8px; color:#555;">
            Este es un email enviado automaticamente desde el canal de comunicación del sistema de planificación de recursos empresariales MI EMPRESA.
        </p>

    </div>
</body>

</html>';

    $mail->Body = $body;
    try {
        $mail->send();
    } catch (Exception $e) {
        error_log('Error correo cliente: ' . $e->getMessage());
    }
}