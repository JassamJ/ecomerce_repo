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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'dbcon.php';



/*
|--------------------------------------------------------------------------
| ELIMINAR PEDIDO
|--------------------------------------------------------------------------
*/

if (isset($_POST['delete'])) {

    $registro_id = mysqli_real_escape_string(
        $con,
        $_POST['delete']
    );


    $query = "DELETE FROM pedidos WHERE id='$registro_id'";

    mysqli_query($con, $query);


    header("Location: industrias.php");
    exit();
}





/*
|--------------------------------------------------------------------------
| PROCESAR PAGO
|--------------------------------------------------------------------------
*/


if (isset($_POST['update'])) {


    if (
        !isset($_POST['identificador']) ||
        empty($_POST['identificador'])
    ) {
        die("Identificador no recibido");
    }


    $identificador = $_POST['identificador'];



    /*
    |--------------------------------------------------------------------------
    | Buscar pedido
    |--------------------------------------------------------------------------
    */


    $stmt = $con->prepare("
        SELECT 
            nombre,
            apellidop,
            apellidom,
            email,
            telefono,
            total
        FROM pedidos
        WHERE identificador = ?
        LIMIT 1
    ");


    $stmt->bind_param(
        "s",
        $identificador
    );


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

        die("Pedido no encontrado");

    }


    $stmt->close();



    /*
    |--------------------------------------------------------------------------
    | OPENPAY
    |--------------------------------------------------------------------------
    */


    $openpay = Openpay::getInstance(
        $_ENV['OPENPAY_ID'],
        $_ENV['OPENPAY_SK'],
        $_ENV['OPENPAY_COUNTRY'],
        $_SERVER['REMOTE_ADDR']
    );


    Openpay::setProductionMode(
        filter_var(
            $_ENV['OPENPAY_PRODUCTION_MODE'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        )
    );




    $customer = [

        "name" =>
            $nombre,

        "last_name" =>
            trim(
                $apellidop .
                " " .
                $apellidom
            ),

        "phone_number" =>
            $telefono,

        "email" =>
            $email

    ];



    $method = $_POST['payment_method'];


    $montoFinal = number_format(
        (float)$total,
        2,
        '.',
        ''
    );




    try {



        /*
        |--------------------------------------------------------------------------
        | TARJETA
        |--------------------------------------------------------------------------
        */


        if ($method === "card") {



            $chargeData = [

                "method" =>
                    "card",

                "source_id" =>
                    $_POST['token_id'],

                "amount" =>
                    $montoFinal,


                "description" =>
                    "Pedido ".$identificador,


                "order_id" =>
                    $identificador."_".time(),


                "device_session_id" =>
                    $_POST['deviceIdHiddenFieldName'],


                "customer" =>
                    $customer

            ];



        } else {



            /*
            |--------------------------------------------------------------------------
            | SPEI
            |--------------------------------------------------------------------------
            */


            $chargeData = [

                "method" =>
                    "bank_account",

                "amount" =>
                    $montoFinal,


                "description" =>
                    "Pedido ".$identificador,


                "order_id" =>
                    $identificador."_".time(),


                "customer" =>
                    $customer

            ];

        }




        /*
        |--------------------------------------------------------------------------
        | CREAR CARGO OPENPAY
        |--------------------------------------------------------------------------
        */


        $charge = $openpay->charges->create(
            $chargeData
        );



        /*
        |--------------------------------------------------------------------------
        | PAGO SPEI
        |--------------------------------------------------------------------------
        */


        if ($method === "bank_account") {


            $openpay_id =
                $charge->id;


            $vigencia =
                $charge->due_date;


            $bank =
                $charge->payment_method->bank;


            $clabe =
                $charge->payment_method->clabe;


            $convenio =
                $charge->payment_method->agreement;


            $referencia =
                $charge->payment_method->name;


            $url_pdf =
                $charge->payment_method->url_spei;



            $update_stmt = $con->prepare("
                UPDATE pedidos SET

                status_pago = 'Pendiente SPEI',
                openpay_id = ?,
                pdf_url = ?,
                clabe = ?,
                banco = ?,
                convenio = ?,
                referencia = ?

                WHERE identificador = ?

            ");



            $update_stmt->bind_param(
                "sssssss",
                $openpay_id,
                $url_pdf,
                $clabe,
                $bank,
                $convenio,
                $referencia,
                $identificador
            );


            $update_stmt->execute();

            $update_stmt->close();



            notifyCustomer(
                $identificador,
                $email,
                $bank,
                $clabe,
                $convenio,
                $referencia,
                $url_pdf,
                $montoFinal,
                $vigencia
            );



            header(
                "Location: orden.php?id=".$identificador
            );

            exit();


        }        /*
        |--------------------------------------------------------------------------
        | PAGO CON TARJETA
        |--------------------------------------------------------------------------
        */

        if ($method === "card") {


            if ($charge->status == "completed") {


                $openpay_id = $charge->id;



                $update_stmt = $con->prepare("
                    UPDATE pedidos SET

                    status_pago = 'Pagado',

                    openpay_id = ?

                    WHERE identificador = ?

                ");



                if (!$update_stmt) {

                    die(
                        "Error UPDATE: ".$con->error
                    );

                }



                $update_stmt->bind_param(
                    "ss",
                    $openpay_id,
                    $identificador
                );



                $update_stmt->execute();

                $update_stmt->close();



                enviarCorreoConfirmacion(
                    $email,
                    $nombre,
                    $identificador,
                    $montoFinal
                );



                header(
                    "Location: orden.php?id=".$identificador
                );

                exit();



            }


            else if ($charge->status == "charge_pending") {



                header(
                    "Location: ".$charge->payment_method->url
                );


                exit();

            }


        }



    }


    catch(OpenpayApiTransactionError $e){


        handleOpenpayError(
            $e,
            $identificador
        );


    }


    catch(OpenpayApiRequestError $e){


        handleOpenpayError(
            $e,
            $identificador
        );


    }


    catch(Exception $e){


        $_SESSION['alert'] = [

            "title" =>
                "ERROR DEL SISTEMA",

            "message" =>
                $e->getMessage(),

            "icon" =>
                "error"

        ];



        header(
            "Location: pago.php?id=".$identificador
        );

        exit();

    }


}






/*
|--------------------------------------------------------------------------
| ERRORES OPENPAY
|--------------------------------------------------------------------------
*/


function handleOpenpayError($e,$identificador)
{


    $codigo =
        $e->getErrorCode();



    switch($codigo){


        case 3001:
            $mensaje="La tarjeta fue rechazada";
            break;


        case 3002:
            $mensaje="La tarjeta expiró";
            break;


        case 3003:
            $mensaje="Fondos insuficientes";
            break;


        case 2005:
            $mensaje="Fecha incorrecta";
            break;


        case 15001:
            $mensaje="Falló la autenticación bancaria";
            break;


        case 1003:
            $mensaje="El token de tarjeta ya fue utilizado. Genera un nuevo pago.";
            break;


        default:

            $mensaje =
                "Error ".$codigo.
                ": ".$e->getMessage();

    }



    $_SESSION['alert']=[

        "title" =>
            "PAGO NO APROBADO",

        "message" =>
            $mensaje,

        "icon" =>
            "error"

    ];



    header(
        "Location: pago.php?id=".$identificador
    );

    exit();

}







/*
|--------------------------------------------------------------------------
| GUARDAR PEDIDO
|--------------------------------------------------------------------------
*/


if(isset($_POST['save'])){


    $nombre =
        trim($_POST['nombre']);


    $apellidop =
        trim($_POST['apellidop']);


    $apellidom =
        trim($_POST['apellidom']);


    $email =
        filter_var(
            $_POST['email'],
            FILTER_VALIDATE_EMAIL
        );


    $telefono =
        trim($_POST['telefono']);



    $productos =
        $_POST['cartLS'] ?? '';



    $estatus = 1;



    $stmt=$con->prepare("

        INSERT INTO pedidos

        (
        nombre,
        apellidop,
        apellidom,
        email,
        telefono,
        productos,
        estatus
        )

        VALUES(?,?,?,?,?,?,?)

    ");



    $stmt->bind_param(
        "ssssssi",
        $nombre,
        $apellidop,
        $apellidom,
        $email,
        $telefono,
        $productos,
        $estatus
    );



    $stmt->execute();



    $id =
        $con->insert_id;



    $identificador =
        "MIEMPRESA-".
        str_pad($id,7,"0",STR_PAD_LEFT);



    $up =
        $con->prepare("

        UPDATE pedidos
        SET identificador=?
        WHERE id=?

        ");



    $up->bind_param(
        "si",
        $identificador,
        $id
    );


    $up->execute();



    header(
        "Location:pago.php?id=".$identificador
    );


    exit();


}






/*
|--------------------------------------------------------------------------
| CORREO CONFIRMACION TARJETA
|--------------------------------------------------------------------------
*/


function enviarCorreoConfirmacion(
    $email,
    $nombre,
    $identificador,
    $total
){


    $mail =
        new PHPMailer(true);



    try{


        $mail->isSMTP();


        $mail->Host =
            "smtp.gmail.com";


        $mail->SMTPAuth =
            true;


        $mail->Username =
            "TU_CORREO@gmail.com";


        $mail->Password =
            "TU_PASSWORD_APP";


        $mail->Port =
            465;


        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_SMTPS;



        $mail->setFrom(
            "TU_CORREO@gmail.com",
            "Sistema Ecommerce"
        );


        $mail->addAddress(
            $email
        );


        $mail->isHTML(true);


        $mail->CharSet="UTF-8";


        $mail->Subject =
            "Pago confirmado ".$identificador;



        $mail->Body="

        <h2>Pago confirmado</h2>

        <p>Hola $nombre</p>

        <p>
        Tu pago fue recibido correctamente.
        </p>

        <p>
        Pedido:
        <b>$identificador</b>
        </p>

        <p>
        Total:
        <b>$$total</b>
        </p>

        ";



        $mail->send();



    }

    catch(Exception $e){


        error_log(
            "Correo error: ".$e->getMessage()
        );


    }

}

