<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    $query = "SELECT * FROM usuarios WHERE username = '$username'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
    } else {
        $_SESSION['alert'] = [
            'title' => 'USUARIO NO ENCONTRADO',
            'icon' => 'error'
        ];
        header('Location: login.php');
        exit();
    }
} else {
    $_SESSION['alert'] = [
        'message' => 'Para acceder debes iniciar sesión primero',
        'title' => 'SESIÓN NO INICIADA',
        'icon' => 'error'
    ];
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="images/ics.ico">
    <title>Compras aprobadas | Fastpack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="shortcut icon" href="images/ico.ico" type="image/x-icon">
</head>

<body class="sb-nav-fixed">
    <?php include 'sidenav.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <div class="container-fluid">
                <div class="row mb-5 mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 style="color:#fff" class="m-1">COMPRAS APROBADAS</h4>
                            </div>
                            <div class="card-body" style="overflow-y:scroll;">
                                <table id="miTabla" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Identificador</th>
                                            <th>Info cliente</th>
                                            <th>Dirección de envío</th>
                                            <th>Openpay ID</th>
                                            <th>Estatus pago</th>
                                            <th>Estatus envío</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM pedidos WHERE estatus = 1 AND openpay_id <> '' OR openpay_id <> NULL ORDER BY id DESC";
                                        $query_run = mysqli_query($con, $query);
                                        if (mysqli_num_rows($query_run) > 0) {
                                            foreach ($query_run as $registro) {
                                                $identificador = $registro['identificador'];
                                        ?>
                                                <tr>
                                                    <td>
                                                        <p><?= $registro['id']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?= $identificador ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?= $registro['nombre']; ?> <?= $registro['apellidop']; ?> <?= $registro['apellidom']; ?></p>
                                                        <p><?= $registro['email']; ?></p>
                                                        <p><?= $registro['telefono']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?= $registro['calle']; ?> #<?= $registro['exterior']; ?> <?= $registro['interior']; ?>, <?= $registro['colonia']; ?>, <?= $registro['ciudad']; ?>, <?= $registro['estado']; ?> CP <?= $registro['postal']; ?></p>
                                                    </td>

                                                    <td>
                                                        <p><?= $registro['openpay_id']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p>
                                                            <?php
                                                            if ($registro['status_pago'] === 'pagado') {
                                                                echo "<span class='bg-success text-light p-1' style='border-radius:10px'>Pagado</span>";
                                                            } else if ($registro['status_pago'] === 'Pendiente SPEI') {
                                                                echo "<span>Pendiente SPEI</span>";
                                                            } else {
                                                                echo "";
                                                            }
                                                            ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php
                                                            if ($registro['estatus'] === '1' && $registro['status_pago'] === 'pagado') {
                                                                echo "Pendiente de enviar";
                                                            } else if ($registro['estatus'] === '0') {
                                                                echo "Finalizado";
                                                            } else {
                                                                echo "";
                                                            }
                                                            ?>
                                                        </p>
                                                    </td>
                                                    <td>
                                                        <button
                                                            class="btn btn-info btn-sm ver-detalle m-1"
                                                            data-id="<?= $identificador ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#detalleModal">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>

                                                        <?php
                                                        if ($registro['status_pago'] === 'pagado') {
                                                        ?>
                                                            <button
                                                                class="btn btn-success btn-sm finalizar-envio m-1"
                                                                data-id="<?= $identificador ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#finalizarModal">
                                                                <i class="bi bi-check2"></i>
                                                            </button>

                                                            <a class="btn btn-sm btn-warning m-1" style="text-decoration: none;" href="generar-comprobante.php?id=<?= $identificador ?>"><i class="bi bi-download"></i></a>

                                                        <?php
                                                        }
                                                        ?>




                                                        <?php
                                                        if ($registro['status_pago'] === 'Pendiente SPEI') {
                                                        ?>
                                                            <a class="btn btn-sm btn-secondary m-1" style="text-decoration: none;" href="spei-respuesta.php?id=<?= $registro['openpay_id']; ?>"><i class="bi bi-arrow-repeat"></i></a>
                                                        <?php
                                                        }
                                                        ?>

                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo "<td colspan='5'><p> No se encontro ningun usuario </p></td>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="finalizarModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="max-height: 80vh; overflow-y: auto;">
                <div class="modal-header">
                    <h5 class="modal-title">FINALIZAR ENVÍO</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form action="codeenvio.php" method="POST">

                        <!-- Identificador visible -->
                        <div class="form-floating mb-3">
                            <input class="form-control" type="text" name="identificador" id="ordenVisible" readonly>
                            <label style="margin-left: 0px;">Orden ID</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input class="form-control" type="text" name="guia" placeholder="URL">
                            <label style="margin-left: 0px;">URL para rastreo</label>
                        </div>

                        <p class="small">
                            NOTA: El sistema enviará automáticamente un <strong>email</strong> al usuario.
                        </p>

                        <button type="submit" class="btn btn-primary w-100" name="finalizar">
                            Finalizar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">PRODUCTOS A ENVIAR ORDEN</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detalleContenido" class="text-center">
                        <div class="spinner-border"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@10'></script>
    <script>
        $(document).ready(function() {
            $('#miTabla').DataTable({
                "order": [
                    [0, "desc"]
                ]
            });
        });

        $(document).on('click', '.ver-detalle', function() {
            let identificador = $(this).data('id');

            $('#detalleContenido').html('<div class="spinner-border"></div>');

            $.ajax({
                url: 'detalle_ventas.php',
                type: 'POST',
                data: {
                    identificador
                },
                success: function(data) {
                    $('#detalleContenido').html(data);
                }
            });
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.finalizar-envio')) {
                const btn = e.target.closest('.finalizar-envio');
                const identificador = btn.getAttribute('data-id');

                document.getElementById('ordenVisible').value = identificador;
            }
        });
    </script>

</body>

</html>