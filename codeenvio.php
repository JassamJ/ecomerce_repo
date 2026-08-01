<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if (isset($_POST['finalizar'])) {

    $identificador = mysqli_real_escape_string($con, $_POST['identificador']);
    $guia = mysqli_real_escape_string($con, $_POST['guia']);
    $estatus = 0;

    $query = "UPDATE `pedidos` SET `estatus` = '$estatus', `guia` = '$guia' WHERE `pedidos`.`identificador` = '$identificador'";
    $query_run = mysqli_query($con, $query);

    if ($query_run) {


        $queryPedido = "SELECT * FROM pedidos WHERE identificador = '$identificador' LIMIT 1";

        $resultPedido = mysqli_query($con, $queryPedido);

        if (!$resultPedido || mysqli_num_rows($resultPedido) === 0) {
            throw new Exception('Pedido no encontrado');
        }

        $pedido = mysqli_fetch_assoc($resultPedido);

        $nombre       = $pedido['nombre'];
        $apellidop    = $pedido['apellidop'];
        $apellidom    = $pedido['apellidom'];
        $email        = $pedido['email'];
        $telefono     = $pedido['telefono'];

        $calle = $pedido['calle'] . ' #' . $pedido['exterior'] . ' ' . $pedido['interior'] .
            ', ' . $pedido['colonia'] . ', ' . $pedido['ciudad'] . ', ' .
            $pedido['estado'] . ' CP ' . $pedido['postal'];

        $subtotal     = (float)$pedido['subtotal'];
        $cuponMonto   = (float)$pedido['cuponMonto'];
        $envioMonto   = (float)$pedido['envioMonto'];
        $total        = (float)$pedido['total'];

        // Configuracion SMTP
        $host = 'mail.dominio.mx';
        $port = 465;
        $username = 'no-reply@dominio.mx';
        $password = '=@dH6mqA5H7%MEa,';
        $security = 'ssl';


        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $security;
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'error_log';


        $mail->setFrom('no-reply@dominio.mx', 'MI EMPRESA');
        // $mail->addReplyTo($email, $nombreuser);
        $mail->addAddress($email);
        $mail->Subject = 'PEDIDO' . ' ' . $identificador;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        

        $productosHTML = '';

        $queryVentas = "
    SELECT cantidad, titulo, sku, subtitulo, detalles, precio, descuento
    FROM ventas
    WHERE identificador = '$identificador'
";

        $resultVentas = mysqli_query($con, $queryVentas);

        if (mysqli_num_rows($resultVentas) > 0) {

            $productosHTML .= '
        <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse; background:#fff; color:#000;">
            <thead>
                <tr style="background:#f2f2f2;">
                    <th align="left">Cantidad</th>
                    <th align="left">Producto</th>
                </tr>
            </thead>
            <tbody>
    ';

            while ($row = mysqli_fetch_assoc($resultVentas)) {

            
                $cantidad  = (int)$row['cantidad'];
                $precioU   = (float)$row['precio'];
                $descuentoU = (float)$row['descuento'];

                $precioTotal    = $precioU * $cantidad;
                $descuentoTotal = $descuentoU * $cantidad;

                $productosHTML .= '
            <tr>
                <td>' . $cantidad . '</td>
                <td>
                    <strong>' . htmlspecialchars($row['titulo']) . '</strong><br>
                    <small>' . htmlspecialchars($row['subtitulo']) . '</small>
                    <small>SKU: ' . htmlspecialchars($row['sku']) . '</small>
                    <p style="font-size:11px;color: #696969ff;">' . htmlspecialchars($row['detalles']) . '</p>

                    
                <div style="text-align:right;">
                   <p>$' . number_format($precioTotal, 2) . '</p>
                   ' . ($descuentoTotal > 0
                    ? '<p>-$' . number_format($descuentoTotal, 2) . '</p>'
                    : ''
                ) .
                    '
                </div>
                </td>
            </tr>
        ';
            }

            $productosHTML .= '
            </tbody>
        </table>
    ';
        } else {
            $productosHTML = '<p>No se encontraron productos para este pedido.</p>';
        }

        $cuponHTML = '';
        $envioHTML = '';

        if ($cuponMonto > 0) {
            $cuponHTML = '<p><strong>Cupón: $' . number_format($cuponMonto, 2) . '</strong></p>';
        }

        if ($envioMonto > 0) {
            $envioHTML = '<p><strong>Envío: $' . number_format($envioMonto, 2) . '</strong></p>';
        } else {
            $envioHTML = '<p><strong>Envío: GRATIS</strong></p>';
        }


        $body = '
            <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background:#ffffff; font-family:Arial, sans-serif;">

   <div style="background-color: #f3f3f3; max-width: 600px; margin: 0px auto; text-align: center; line-height: 100px;">
     <img src="https://datallizer.com/images/logo.png" 
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
            PEDIDO EN CAMINO
        </h1>

        <p>En próximos días recibirás tu pedido en la dirección que nos proporcionaste</p>

        ' . (!empty($guia) ? '<p><strong>Guía de rastreo:</strong> <a href="' . $guia . '">' . $guia . '</a></p>' : '') . '

        <div style="
                    background: #2c3b5c; 
                    color:#fff; 
                    padding:15px; 
                    border-radius:3px;
                    margin:30px 0;
                ">
            <p><strong>Pedido ID ' . $identificador . ':</strong></p>
            <p><strong>Nombre:</strong> ' . $nombre . ' ' . $apellidop . ' ' . $apellidom . '</p>
            <p><strong>Teléfono:</strong> ' . $telefono . '</p>
            <p><strong>Domicilio de entrega:</strong> ' . $calle . '</p>
        </div>

        <p>TUS PRODUCTOS:</p>
            ' . $productosHTML . '

            <div style="text-align:right;">
            <p><strong>Subtotal: $' . number_format($subtotal, 2) . '</strong></p>
' . $cuponHTML . '
' . $envioHTML . '
<p><strong>Total: $' . number_format($total, 2) . '</strong></p>
</div>

        <p style="text-align:center;"><strong>Atentamente</strong></p>
        <p style="text-align:center;">MIEMPRESA</p>

        <p style="font-size:8px; color:#555;">
            Este es un email enviado automaticamente desde el canal de comunicación del sistema de planificación de recursos empresariales MIEMPRESA, la información previa a sido almacenada en la base de datos de MIEMPRESA, la información en este email fue ingresada manualmente por el usuario, es importante tener en cuenta que la presente información podrían estar desactualizada o contener errores. Le recomendamos verificar la precisión de la misma antes de tomar decisiones basadas en estos datos.
        </p>

    </div>
</body>

</html>';
        $mail->Body = $body;

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
                'message' => 'El pedido se actualizó pero el correo no pudo enviarse',
                'icon' => 'warning'
            ];
        }

        header("Location: compras-aprobadas.php");
        exit(0);
    } else {
        header("Location: compras-aprobadas.php");
        exit(0);
    }
}


if (isset($_POST['save'])) {

    mysqli_begin_transaction($con);

    try {

      
        $resCom = mysqli_query($con, "SELECT valoruno FROM configuraciones WHERE id=4 LIMIT 1");
        $comData = mysqli_fetch_assoc($resCom);
        $comisionValor = str_replace('%', '', $comData['valoruno']);
        $comisionFactor = (float)$comisionValor / 100;

        $productos = json_decode($_POST['cartLS'], true);
        if (!is_array($productos)) {
            throw new Exception("Carrito inválido");
        }

       
        $nombre     = mysqli_real_escape_string($con, $_POST['nombre']);
        $apellidop  = mysqli_real_escape_string($con, $_POST['apellidop']);
        $apellidom  = mysqli_real_escape_string($con, $_POST['apellidom']);
        $email = mysqli_real_escape_string(
            $con,
            strtolower(trim($_POST['email']))
        );
        $telefono   = mysqli_real_escape_string($con, $_POST['telefono']);
        $calle      = mysqli_real_escape_string($con, $_POST['calle']);
        $exterior   = mysqli_real_escape_string($con, $_POST['exterior']);
        $interior   = mysqli_real_escape_string($con, $_POST['interior']);
        $colonia    = mysqli_real_escape_string($con, $_POST['colonia']);
        $ciudad     = mysqli_real_escape_string($con, $_POST['ciudad']);
        $estado     = mysqli_real_escape_string($con, $_POST['estado']);
        $postal     = mysqli_real_escape_string($con, $_POST['postal']);
        $pais       = mysqli_real_escape_string($con, $_POST['pais']);
        $cupon      = mysqli_real_escape_string($con, $_POST['cuponLS']);

        $productos = json_decode($_POST['cartLS'], true);
        if (!is_array($productos)) {
            throw new Exception("Carrito inválido");
        }

        $estatus = 1;
        $subtotal = 0;
        $descuentoTotal = 0;
        $cuponMonto = 0;
        $envioMonto = 0;

        $productosAjustados = [];
        $alertasStock = [];

        
        if (!mysqli_query($con, "
            INSERT INTO pedidos
            (nombre, apellidop, apellidom, email, telefono, calle, exterior, interior, colonia, ciudad, estado, postal, pais, cupon, estatus)
            VALUES
            ('$nombre','$apellidop','$apellidom','$email','$telefono','$calle','$exterior','$interior','$colonia','$ciudad','$estado','$postal','$pais','$cupon','$estatus')
        ")) {
            throw new Exception(mysqli_error($con));
        }

        $pedido_id = mysqli_insert_id($con);

       
        $folio = str_pad($pedido_id, 7, "0", STR_PAD_LEFT);
        $iniciales = strtoupper(
            substr($nombre, 0, 1) .
                substr($apellidop, 0, 1) .
                substr($apellidom, 0, 1)
        );
        $identificador = "MIEMPRESA-$folio-$iniciales";

       
        foreach ($productos as &$item) {

            $id = (int)$item['id'];
            $cantidadSolicitada = (int)$item['cantidad'];

            $res = mysqli_query($con, "
                SELECT * FROM productosventa
                WHERE id = $id
                FOR UPDATE
            ");
            $producto = mysqli_fetch_assoc($res);

            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }

            $stock = (int)$producto['stock'];
            $cantidadFinal = min($cantidadSolicitada, $stock);

            if ($cantidadFinal < $cantidadSolicitada) {
                $alertasStock[] = [
                    'titulo' => $producto['titulo'],
                    'cantidad' => $cantidadFinal
                ];
            }

            if ($cantidadFinal <= 0) continue;


            if ($producto['cantidadmayoreo'] > 0 && $cantidadFinal >= $producto['cantidadmayoreo']) {
                $precioBase = $producto['preciomayoreo'];
                $mayoreo = "Si";
                $descuentoReal = 0; 
            } else {
                $precioBase = $producto['preciounitario'];
                $mayoreo = "No";
                $descuentoReal = $producto['descuento'];
            }

           
            $precioConComision = $precioBase * (1 + $comisionFactor);

            $subtotal += $cantidadFinal * $precioConComision;
            $descuentoTotal += $cantidadFinal * $descuentoReal;
            if (!mysqli_query($con, "
                INSERT INTO ventas
                (identificador, producto_id, titulo, subtitulo, detalles, sku, cantidad, mayoreo, precio, descuento)
                VALUES
                ('$identificador','$id','{$producto['titulo']}','{$producto['subtitulo']}','{$producto['detalles']}','{$producto['sku']}',
                 '$cantidadFinal','$mayoreo','$precioConComision','$descuentoReal')
            ")) {
                throw new Exception(mysqli_error($con));
            }

            if (!mysqli_query($con, "
                UPDATE productosventa
                SET stock = stock - $cantidadFinal
                WHERE id = $id
            ")) {
                throw new Exception(mysqli_error($con));
            }

            $item['cantidad'] = $cantidadFinal;
            $productosAjustados[] = $item;
        }

       
        $montoBase = $subtotal - $descuentoTotal;

        if (!empty($cupon)) {
            $resCupon = mysqli_query($con, "
                SELECT * FROM cupones
                WHERE codigo = '$cupon' AND estatus = 1
                LIMIT 1
            ");
            $cuponData = mysqli_fetch_assoc($resCupon);

            if ($cuponData) {
                $resCanjes = mysqli_query($con, "
                    SELECT COUNT(*) AS usados
                    FROM cuponescanjeados
                    WHERE codigo = '$cupon'
                ");
                $canjes = mysqli_fetch_assoc($resCanjes)['usados'];

                if ($canjes < $cuponData['canjes'] && $montoBase >= $cuponData['minimo']) {
                    $calc = ($cuponData['porcentaje'] / 100) * $montoBase;
                    $cuponMonto = min($calc, $cuponData['maximo']);
                } else {
                    $cupon = NULL;
                }
            } else {
                $cupon = NULL;
            }
        } else {
            $cupon = NULL;
        }

       
        $montoEnvioBase = $subtotal - $descuentoTotal - $cuponMonto;

        $resConfig = mysqli_query($con, "
            SELECT valoruno, valordos
            FROM configuraciones
            WHERE id = 1
            LIMIT 1
        ");
        $config = mysqli_fetch_assoc($resConfig);

        if ($montoEnvioBase < $config['valoruno']) {
            $envioMonto = $config['valordos'];
        }

        
        $total = $montoEnvioBase + $envioMonto;
        if ($total < 0) $total = 0;

      
        if (!mysqli_query($con, "
            UPDATE pedidos SET
                identificador = '$identificador',
                productos = '" . json_encode($productosAjustados) . "',
                cupon = " . ($cupon ? "'$cupon'" : "NULL") . ",
                subtotal = '$subtotal',
                descuentoTotal = '$descuentoTotal',
                cuponMonto = '$cuponMonto',
                envioMonto = '$envioMonto',
                total = '$total'
            WHERE id = '$pedido_id'
        ")) {
            throw new Exception(mysqli_error($con));
        }

      
        if ($cuponMonto > 0 && $cupon) {
            mysqli_query($con, "
                INSERT INTO cuponescanjeados (codigo, identificador, monto)
                VALUES ('$cupon','$identificador','$cuponMonto')
            ");
        }

        mysqli_commit($con);

        header("Location: pago.php?id=$identificador");
        exit;
    } catch (Exception $e) {

        mysqli_rollback($con);
        // echo "<pre>ERROR:\n" . $e->getMessage() . "</pre>";
        error_log($e->getMessage());
        header("Location: pedido.php");
        exit;
    }
}