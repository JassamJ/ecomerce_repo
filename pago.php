<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require 'dbcon.php';

$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;

if (!empty($alert)) {
    $title = isset($alert['title']) ? json_encode($alert['title']) : '"Notificación"';
    $message = isset($alert['message']) ? json_encode($alert['message']) : '""';
    $icon = isset($alert['icon']) ? json_encode($alert['icon']) : '"info"';

    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: $title,
                    " . (!empty($alert['message']) ? "text: $message," : "") . "
                    icon: $icon,
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Hacer algo si se confirma la alerta
                    }
                });
            });
        </script>";
    unset($_SESSION['alert']);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: tienda-en-linea.php');
    exit;
}

$stmt = $con->prepare("
    SELECT *
    FROM pedidos
    WHERE identificador = ?
    LIMIT 1
");

if (!$stmt) {
    die($con->error);
}

$stmt->bind_param('s', $_GET['id']);
$stmt->execute();

$resultado = $stmt->store_result();

if ($resultado === false || $stmt->num_rows === 0) {
    header('Location: tienda-en-linea.php');
    exit;
}

$pedido = [];
$meta = $stmt->result_metadata();
$fields = [];

while ($field = $meta->fetch_field()) {
    $fields[] = &$pedido[$field->name];
}

call_user_func_array([$stmt, 'bind_result'], $fields);
$stmt->fetch();

if (
    isset($pedido['status_pago']) &&
    strtolower($pedido['status_pago']) === 'pagado'
) {
    header('Location: tienda-en-linea.php');
    exit;
}

$ventas = [];

$stmtVentas = $con->prepare("
    SELECT titulo, sku, cantidad, precio, descuento
    FROM ventas
    WHERE identificador = ?
");

if (!$stmtVentas) {
    die($con->error);
}

$stmtVentas->bind_param('s', $pedido['identificador']);
$stmtVentas->execute();

$stmtVentas->bind_result(
    $titulo,
    $sku,
    $cantidad,
    $precio,
    $descuento
);

while ($stmtVentas->fetch()) {
    $ventas[] = [
        'titulo'     => $titulo,
        'sku'        => $sku,
        'cantidad'   => $cantidad,
        'precio'     => $precio,
        'descuento'  => $descuento
    ];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="shortcut icon" type="image/x-icon" href="images/ico.ico" />
    <title>Pago | Mi Emmpresa</title>
    <script type="text/javascript"
        src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script type="text/javascript"
        src="https://openpay.s3.amazonaws.com/openpay.v1.min.js"></script>
    <script type='text/javascript'
        src="https://openpay.s3.amazonaws.com/openpay-data.v1.min.js"></script>

    <script type="text/javascript">
        const OPENPAY_ID = "<?php echo $_ENV['OPENPAY_ID']; ?>";
        const OPENPAY_PK = "";

        $(document).ready(function() {

            OpenPay.setId(OPENPAY_ID);
            OpenPay.setApiKey(OPENPAY_PK);
            OpenPay.setSandboxMode(false);
            var deviceSessionId = OpenPay.deviceData.setup("payment-form", "deviceIdHiddenFieldName");

            // Ajuste en el click del botón para no pedir token si es transferencia
            $('#pay-button').on('click', function(event) {
                event.preventDefault();
                const method = $('input[name="payment_method"]:checked').val();

                $(this).prop("disabled", true);

                if (method === 'card') {
                    OpenPay.token.extractFormAndCreate('payment-form', sucess_callbak, error_callbak);
                } else {
                    // Si es transferencia, enviamos el formulario directo
                    $('#payment-form').submit();
                }
            });

            var sucess_callbak = function(response) {
                var token_id = response.data.id;
                $('#token_id').val(token_id);
                $('#payment-form').submit();
            };

            var error_callbak = function(response) {
                var desc = response.data.description != undefined ? response.data.description : response.message;
                alert("ERROR [" + response.status + "] " + desc);
                $("#pay-button").prop("disabled", false);
            };

        });
    </script>
</head>
<style>
    label {
        margin-left: 0px;
        margin-bottom: 10px;
    }

    body {
        background-color: #ecf0f3;
    }
</style>

<body>
    <?php include('componentes/menu.php'); ?>

    <div class="container-fluid bg-light">
        <div class="row mt-5 justify-content-center">

            <div class=" col-11 col-md-4 mt-5 p-5 mb-5 order-2" style="background-color: #e7e7e7;border-radius:15px;">
                <h4>Resumen de tu compra</h4>
                <p><b>ID Pedido:</b> <span class="small"><?= htmlspecialchars($pedido['identificador'], ENT_QUOTES, 'UTF-8'); ?></span></p>

                <p class="mb-0"><b>Recibe:</b></p>
                <p class="small mb-0"><?= htmlspecialchars($pedido['nombre'], ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($pedido['apellidop'], ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($pedido['apellidom'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="small mb-0"><?= htmlspecialchars($pedido['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="small"><?= htmlspecialchars($pedido['telefono'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="mb-0"><b>Envío a:</b></p>
                <p class="small"><?= htmlspecialchars($pedido['calle'], ENT_QUOTES, 'UTF-8'); ?> #<?= htmlspecialchars($pedido['exterior'], ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($pedido['colonia'], ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($pedido['ciudad'], ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($pedido['estado'], ENT_QUOTES, 'UTF-8'); ?>. CP <?= htmlspecialchars($pedido['postal'], ENT_QUOTES, 'UTF-8'); ?></p>

                <div id="descripcion" class="mt-3">

                    <p><b>Tus productos:</b></p>


                    <?php foreach ($ventas as $item): ?>
                        <div class="row">
                            <div class="col-9">
                                <p class="mb-1">
                                    <strong><?= (int)$item['cantidad'] ?> x <?= htmlspecialchars($item['titulo']) ?></strong>
                                </p>
                                <p class="mb-0 small text-muted">
                                    SKU: <?= htmlspecialchars($item['sku']) ?>
                                </p>

                            </div>

                            <div class="col-3 text-end">
                                <?php
                                $monto = $item['cantidad'] * $item['precio'];
                                $disc = $item['cantidad'] * $item['descuento'];
                                ?>

                                <p class="mb-0 small">
                                    $<?= number_format($monto, 2) ?>
                                </p>

                                <?php if ($disc > 0): ?>
                                    <p class="mb-0 small text-success">
                                        -$<?= number_format($disc, 2) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr>
                    <?php endforeach; ?>

                </div>

                <div class="text-end">
                    <p><b>Subtotal:</b>
                        $<?= number_format($pedido['subtotal'], 2); ?>
                    </p>

                    <?php if ($pedido['cuponMonto'] > 0): ?>
                        <p class="text-success"><b>Cupón:</b>
                            -$<?= number_format($pedido['cuponMonto'], 2); ?>
                        </p>
                    <?php endif; ?>

                    <p><b>Envío:</b>
                        <?= $pedido['envioMonto'] > 0 ? '$' . number_format($pedido['envioMonto'], 2) : 'GRATIS'; ?>
                    </p>

                    <p style="font-weight: 500;"><b>Total:</b>
                        $<?= number_format($pedido['total'], 2); ?>
                    </p>
                </div>
            </div>

            <div class="col-11 col-md-7 mt-5 mb-5 p-5 order-1">
                <h2>PASO 3: PAGO</h2>
                <form action="codepago.php" method="POST" id="payment-form" class="row justify-content-center">
                    <input type="hidden" name="identificador" value="<?= htmlspecialchars($pedido['identificador'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="token_id" id="token_id">
                    <input type="hidden" name="use_card_points" id="use_card_points" value="false">
                    <input type="hidden" name="deviceIdHiddenFieldName" id="deviceIdHiddenFieldName">
                    <input type="hidden" name="update" id="update" value="step">
                    <div class="col-12">
                        <p><b>Elige tu método de pago</b></p>
                        <div class="d-flex">
                            <div class="form-check m-1">
                                <input class="form-check-input" type="radio" name="payment_method" id="transferenciaRadio" value="bank_account">
                                <label class="form-check-label" for="transferenciaRadio">Transferencia</label>
                            </div>
                            <div class="form-check m-1">
                                <input class="form-check-input" type="radio" name="payment_method" id="tarjetaRadio" value="card" checked>
                                <label class="form-check-label" for="tarjetaRadio">Pago con tarjeta</label>
                            </div>
                        </div>
                    </div>
                    <div class="containerTarjeta row">
                        <div class="col-12 mt-3">
                            <h4>Tarjetas débito / crédito</h4>
                            <img src="cards1.png" alt="">
                        </div>
                        <div class="col-12 col-md-6 mt-3">
                            <label>Nombre del titular</label>
                            <input type="text" class="form-control" placeholder="Como aparece en la tarjeta" autocomplete="off" data-openpay-card="holder_name" minlength="10">
                        </div>

                        <div class="col-12 col-md-6 mt-3">
                            <label>Número de tarjeta</label>
                            <input type="text" class="form-control" autocomplete="off" data-openpay-card="card_number" minlength="16" maxlength="16">
                        </div>

                        <div class="col-12 col-md-6 mt-3">
                            <div class="row">
                                <div class="col-12"><label>Fecha de expiración</label></div>
                                <div class="col-6">
                                    <select id="expMonth" class="form-select" data-openpay-card="expiration_month">
                                    </select>
                                </div>

                                <div class="col-6">
                                    <select id="expYear" class="form-select" data-openpay-card="expiration_year">
                                        <?php
                                        $anioActual = date('Y');
                                        $anioFinal  = $anioActual + 20;

                                        for ($anio = $anioActual; $anio <= $anioFinal; $anio++) {
                                            $valor = substr($anio, -2);
                                            $selected = ($anio == $anioActual) ? 'selected' : '';
                                            echo "<option value='$valor' $selected>$anio</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 mt-3">
                            <div class="row">
                                <div class="col-12">
                                    <label>Código de seguridad</label>
                                </div>
                                <div class="col-12 col-md-6">
                                    <input type="text" class="form-control" placeholder="CVV" autocomplete="off" data-openpay-card="cvv2" minlength="3" maxlength="4">
                                </div>
                                <div class="col-12 col-md-6 mt-3 mt-md-0"> <img src="cvv.png" alt=""></div>
                            </div>
                        </div>

                    </div>
                    <button type="button" class="btn btn-danger mt-4" id="pay-button">
                        PAGAR $<?= number_format($pedido['total'], 2); ?>
                    </button>

                    <div class="col-12 col-md-6 mt-4">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <p style="font-size: 10px;margin:0px;font-weight:500">Transacciones realizadas vía:</p>
                                <img src="openpay.png" alt="">
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="row">
                                    <div class="col-3">
                                        <img src="security.png" alt="">
                                    </div>
                                    <div class="col">
                                        <p style="font-size: 10px;font-weight:500">Tus pagos se realizan de forma segura con encriptación de 256 bits</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="p-3 mt-3 row" style="background-color: #ebbc5d78;border:2px solid #b5790066;border-radius:10px;font-size:12px;">
                    <div class="col-1">
                        <i style="background-color: #b692133b;color: #393939ff;padding:5px 6px 5px 6px;border-radius:50px;" class="bi bi-headset"></i>
                    </div>
                    <div class="col">
                        <p class="text-dark" style="margin:0;font-weight:400;">¿Necesitas ayuda?</p>

                        <p class="text-dark" style="margin:0;">Dirección: Circuito Cobalto #189, Fracc. Cobalto Norte, Aguascalientes, Ags.</p>

                        <p class="text-dark" style="margin:0;">Tel: (449) 146 6072, (449) 973 6197, (449) 973 6681, (449) 965 7671</p>

                        <p class="text-dark" style="margin:0;">Email: ventas@fastpack.mx</p>
                    </div>
                </div>
            </div>


        </div>



    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@10'></script>
    <script src="js/menu.js"></script>
    <script>
        $(document).ready(function() {
            $('input[name="payment_method"]').on('change', function() {
                if ($(this).val() === 'bank_account') {
                    $('.containerTarjeta').hide();
                } else {
                    $('.containerTarjeta').show();
                }
                validateForm(); 
            });

           
            $('input[data-openpay-card="card_number"]').on('input', function() {
                let val = this.value.replace(/\D/g, "");
                this.value = val;

                
                if (val.startsWith('34') || val.startsWith('37')) {
                    $(this).attr('maxlength', 15).attr('minlength', 15);
                    $('input[data-openpay-card="cvv2"]').attr('maxlength', 4).attr('minlength', 4);
                } else {
                    $(this).attr('maxlength', 16).attr('minlength', 16);
                    $('input[data-openpay-card="cvv2"]').attr('maxlength', 3).attr('minlength', 3);
                }
                validateForm();
            });

            $('input, select').on('input change', validateForm);

            validateForm();
        });

        function validateForm() {
            const method = $('input[name="payment_method"]:checked').val();

           
            if (method === 'bank_account') {
                $("#pay-button").prop("disabled", false);
                return;
            }

            let card = $('input[data-openpay-card="card_number"]').val() || "";
            let cvv = $('input[data-openpay-card="cvv2"]').val() || "";
            let holder = $('input[data-openpay-card="holder_name"]').val() || "";
            let month = $('#expMonth').val();

            let isAmex = card.startsWith('34') || card.startsWith('37');
            let cardValid = isAmex ? card.length === 15 : card.length === 16;
            let cvvValid = isAmex ? cvv.length === 4 : cvv.length === 3;
            let holderValid = holder.trim().length >= 10;
            let dateValid = month !== "";

            $("#pay-button").prop("disabled", !(cardValid && cvvValid && holderValid && dateValid));
        }

        const monthSelect = document.getElementById('expMonth');
        const yearSelect = document.getElementById('expYear');

        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        const months = [{
                val: '01',
                text: 'Enero - 01'
            },
            {
                val: '02',
                text: 'Febrero - 02'
            },
            {
                val: '03',
                text: 'Marzo - 03'
            },
            {
                val: '04',
                text: 'Abril - 04'
            },
            {
                val: '05',
                text: 'Mayo - 05'
            },
            {
                val: '06',
                text: 'Junio - 06'
            },
            {
                val: '07',
                text: 'Julio - 07'
            },
            {
                val: '08',
                text: 'Agosto - 08'
            },
            {
                val: '09',
                text: 'Septiembre - 09'
            },
            {
                val: '10',
                text: 'Octubre - 10'
            },
            {
                val: '11',
                text: 'Noviembre - 11'
            },
            {
                val: '12',
                text: 'Diciembre - 12'
            }
        ];

        function loadMonths(selectedYear) {
            monthSelect.innerHTML = '<option value="">Mes</option>';

            months.forEach((month, index) => {
                const monthNumber = index + 1;

                if (
                    selectedYear > currentYear ||
                    (selectedYear === currentYear && monthNumber >= currentMonth)
                ) {
                    const option = document.createElement('option');
                    option.value = month.val;
                    option.textContent = month.text;


                    monthSelect.appendChild(option);
                }
            });
        }

        //  Carga inicial (año actual seleccionado)
        loadMonths(currentYear);

        yearSelect.addEventListener('change', function() {
            const selectedYear = parseInt(
                currentYear.toString().slice(0, 2) + this.value
            );

            loadMonths(selectedYear);

            //  Deshabilitar botón hasta que elijan mes válido
            $('#pay-button').prop('disabled', true);

            // 🔁 Revalidar estado del formulario
            validateForm();
        });
    </script>
</body>

</html>