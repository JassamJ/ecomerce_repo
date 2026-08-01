<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'dbcon.php'; // Tu archivo de conexión

// Importar PHPMailer (Asegúrate de que la ruta a tu vendor/autoload.php sea correcta)
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Recibimos el identificador por GET (ej: orden.php?id=MIEMPRESA-0000001-ABC)
$identificador = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($identificador)) {
    header("Location: index.php");
    exit();
}

// Consultar los detalles del pedido en la tabla 'pedidos'
$stmt = $con->prepare("SELECT * FROM pedidos WHERE identificador = ? LIMIT 1");
$stmt->bind_param("s", $identificador);
$stmt->execute();
$resultado = $stmt->get_result();
$orden = $resultado->fetch_assoc();
$stmt->close();

if (!$orden) {
    die("Pedido no encontrado.");
}

// ==========================================
// ENVÍO AUTOMÁTICO DE CORREO SI ESTÁ PAGADO
// ==========================================
// Puedes agregar una bandera en tu base de datos si deseas que se envíe una sola vez, 
// o dispararlo directamente si el estatus es 'Pagado'.
if ($orden['status_pago'] === 'Pagado') {
    
    $emailCliente  = $orden['email'];
    $nombreCliente = $orden['nombre'];
    $order_id      = $orden['identificador'];
    $total         = $orden['total'];

    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP de Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 465;
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lguadalupe.cvelasco@gmail.com';
        $mail->Password   = 'oupb dhdh acih vqkm';
        $mail->SMTPSecure = 'ssl';
        $mail->SMTPDebug  = 2; // Pon en 2 si necesitas depurar errores en el error_log
        $mail->Debugoutput = 'error_log';

        // Remitente y Destinatario
        $mail->setFrom('jassamdavila15@gmail.com', 'Sistema Ecommerce');
        $mail->addAddress($emailCliente, $nombreCliente);

        // Contenido del correo
        $mail->Subject = '¡Tu compra ha sido realizada con éxito! #' . $order_id;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        $mail->Body = '
        <h2>¡Gracias por tu compra!</h2>
        <p>Hola ' . htmlspecialchars($nombreCliente) . ',</p>
        <p>Tu pago ha sido procesado correctamente.</p>
        <hr>
        <p><strong>Número de pedido:</strong> ' . htmlspecialchars($order_id) . '</p>
        <p><strong>Total pagado:</strong> $' . number_format((float)$total, 2) . ' MXN</p>
        <hr>
        <p>Puedes consultar los detalles de tu compra en nuestro sitio web.</p>
        <p>Gracias por confiar en nosotros.</p>
        <br>
        <p>Atentamente,</p>
        <p><strong>Sistema Ecommerce</strong></p>
        ';

        $mail->AltBody = 'Tu compra fue realizada correctamente. Pedido: ' . $order_id . ' - Total: $' . number_format((float)$total, 2) . ' MXN';

        // Enviar correo (puedes omitir o guardar el resultado si gustas)
        $mail->send();

    } catch (Exception $e) {
        // Si falla el correo, queda registrado en los logs del servidor sin romper la vista
        error_log("Error al enviar correo en orden.php: " . $mail->ErrorInfo);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de la Compra</title>
    <link rel="stylesheet" href="css/orden.css">
</head>
<body>
    <div class="container">
        <h1>¡Gracias por tu compra!</h1>
        <p>Estado del pago: <strong><?php echo htmlspecialchars($orden['status_pago']); ?></strong></p>
        
        <div class="details-box">
            <h3>Resumen del Pedido #<?php echo htmlspecialchars($orden['identificador']); ?></h3>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($orden['fecha'] ?? 'Reciente'); ?></p>
            <p><strong>Total Pagado:</strong> $<?php echo number_format($orden['total'], 2); ?> MXN</p>
            
            <h4>Datos de Envío / Cliente</h4>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['nombre'] . ' ' . $orden['apellidop'] . ' ' . $orden['apellidom']); ?></p>
            <p><strong>Correo:</strong> <?php echo htmlspecialchars($orden['email']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($orden['telefono']); ?></p>

            <?php if (!empty($orden['pdf_url'])): ?>
                <p><a href="<?php echo htmlspecialchars($orden['pdf_url']); ?>" target="_blank" class="btn-pdf">Descargar ficha de pago SPEI</a></p>
            <?php endif; ?>
        </div>

        <a href="tienda-en-linea.php" class="btn">Volver a la tienda</a>
    </div>
</body>
</html>