<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';

$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($con, trim($_POST['username']));
    $password = $_POST['password'];

    $query = "SELECT * FROM usuarios WHERE username = '$username' LIMIT 1";
    $query_run = mysqli_query($con, $query);

    if ($query_run && mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_assoc($query_run);

        if ((int)$row['estatus'] !== 1) {
            $_SESSION['alert'] = [
                'title' => 'USUARIO INACTIVO',
                'message' => 'Tu cuenta se encuentra deshabilitada, contacta a soporte',
                'icon' => 'error'
            ];
            header("Location: login.php");
            exit(0);
        }

        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['rol'] = $row['rol'];

            header("Location: usuarios.php");
            exit(0);
        } else {
            $_SESSION['alert'] = [
                'title' => 'ERROR AL INICIAR SESIÓN',
                'message' => 'Correo o contraseña incorrectos',
                'icon' => 'error'
            ];
            header("Location: login.php");
            exit(0);
        }
    } else {
        $_SESSION['alert'] = [
            'title' => 'ERROR AL INICIAR SESIÓN',
            'message' => 'Correo o contraseña incorrectos',
            'icon' => 'error'
        ];
        header("Location: login.php");
        exit(0);
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="images/ics.ico">
    <title>Iniciar sesión | Mi Empresa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="shortcut icon" href="images/ico.ico" type="image/x-icon">
</head>

<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="col-11 col-sm-8 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="text-center mb-4">Iniciar sesión</h4>
                    <form action="login.php" method="POST">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="username" id="username" placeholder="Correo" autocomplete="off" required>
                            <label for="username">Correo</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" autocomplete="off" required>
                            <label for="password">Contraseña</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" name="login">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@10'></script>

    <?php if (!empty($alert)) {
        $title = isset($alert['title']) ? json_encode($alert['title']) : '"Notificación"';
        $message = isset($alert['message']) ? json_encode($alert['message']) : '""';
        $icon = isset($alert['icon']) ? json_encode($alert['icon']) : '"info"';
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: <?= $title; ?>,
                    <?= (!empty($alert['message']) ? "text: $message," : ""); ?>
                    icon: <?= $icon; ?>,
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php
        unset($_SESSION['alert']);
    } ?>

</body>

</html>