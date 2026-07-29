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
        $row = mysqli_fetch_assoc($result);
        $rolSesion = (int)$row['rol'];
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
    <title>Usuarios | Mi Empresa</title>
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
                                <h4 style="color:#fff" class="m-1">USUARIOS
                                    <button type="button" class="btn btn-primary btn-sm float-end btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                        Nuevo usuario
                                    </button>
                                </h4>
                            </div>
                            <div class="card-body" style="overflow-y:scroll;">
                                <table id="miTabla" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre</th>
                                            <th>Correo</th>
                                            <th>Rol</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM usuarios ORDER BY id DESC";
                                        $query_run = mysqli_query($con, $query);
                                        if (mysqli_num_rows($query_run) > 0) {
                                            foreach ($query_run as $registro) {
                                        ?>
                                                <tr>
                                                    <td>
                                                        <p><?= $registro['id']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?= $registro['nombre']; ?> <?= $registro['apellidopaterno']; ?> <?= $registro['apellidomaterno']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?= $registro['username']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php
                                                            if ($registro['rol'] === '1') {
                                                                echo "Administrador/a";
                                                            } else if ($registro['rol'] === '2') {
                                                                echo "Colaborador/a";
                                                            } else if ($registro['rol'] === '3') {
                                                                echo "Cliente/a";
                                                            } else {
                                                                echo "Error, contacte a soporte";
                                                            }
                                                            ?></p>
                                                    </td>
                                                    <td>
                                                        <?php


                                                        // ============================
                                                        // BOTÓN EDITAR
                                                        // ============================

                                                        // Rol 1: puede editar todos
                                                        if ($rolSesion == 1) {
                                                        ?>
                                                            <a href="editarusuario.php?id=<?= $registro['id']; ?>" class="btn btn-warning btn-sm m-1">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                        <?php
                                                        }

                                                        // Rol 2: solo su propia fila
                                                        if ($rolSesion == 2 && $registro['username'] === $username) {
                                                        ?>
                                                            <a href="editarusuario.php?id=<?= $registro['id']; ?>" class="btn btn-warning btn-sm m-1">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                        <?php
                                                        }

                                                        // ============================
                                                        // BOTÓN ELIMINAR (solo rol 1)
                                                        // ============================

                                                        if ($rolSesion == 1 && $registro['id'] != 1) {
                                                        ?>
                                                            <form action="codeusuarios.php" method="POST" class="d-inline">
                                                                <button type="submit" name="delete" value="<?= $registro['id']; ?>" class="btn btn-danger btn-sm m-1">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </button>
                                                            </form>
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

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">NUEVO USUARIO</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="codeusuarios.php" method="POST" class="row">

                        <div class="col-12 col-md-12 form-floating mb-3">
                            <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre" autocomplete="off" required>
                            <label for="nombre">Nombre</label>
                        </div>

                        <div class="col-12 col-md-6 form-floating mb-3">
                            <input type="text" class="form-control" name="apellidopaterno" id="apellidopaterno" placeholder="Apellido paterno" autocomplete="off" required>
                            <label for="apellidopaterno">Apellido paterno</label>
                        </div>

                        <div class="col-12 col-md-6 form-floating mb-3">
                            <input type="text" class="form-control" name="apellidomaterno" id="apellidomaterno" placeholder="Apellido materno" autocomplete="off" required>
                            <label for="apellidomaterno">Apellido materno</label>
                        </div>

                        <div class="col-12 form-floating mb-3">
                            <input type="email" class="form-control" name="username" id="username" placeholder="Correo" autocomplete="off" required>
                            <label for="username">Correo</label>
                        </div>

                        <div class="col-12 col-md-7 form-floating mb-3">
                            <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" autocomplete="off" minlength="8" required>
                            <label for="password">Contraseña</label>
                        </div>

                        <div class="col-12 col-md-5 form-floating mb-3 rolcol">
                            <select class="form-select" name="rol" id="rol" autocomplete="off" required>
                                <option selected disabled>Seleccione el rol</option>
                                <option value="1">Administrador</option>
                                <option value="2">Colaborador</option>
                            </select>
                            <label for="rol">Rol</label>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" name="save">Guardar</button>
                </div>
                </form>
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
    </script>

</body>

</html>
