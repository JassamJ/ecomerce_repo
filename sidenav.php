<?php
require 'dbcon.php';
$username = $_SESSION['username'];
?>
<link rel="stylesheet" href="css/sidenav.css">
<script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <a class="navbar-brand ps-3" href="dashboard.php"><img style="width: 180px;" src="images/logo.png" alt=""></a>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>

        <!-- Espacio entre el logo y el botón de salir -->
        <div class="d-flex justify-content-end w-100">
            <a style="margin-right: 15px;" class="btn btn-warning" href="logout.php">Salir <i class="bi bi-box-arrow-right"></i></a>
        </div>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link" href="dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <a class="nav-link" href="configuraciones.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-gear-wide-connected"></i></div>
                            Configuraciones
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-person-fill"></i></div>
                            Usuarios
                        </a>
                        <a class="nav-link" href="marketing.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-send-fill"></i></div>
                            Marketing
                        </a>
                        <a class="nav-link" href="estadisticas.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                            Estadísticas
                        </a>
                        <div class="sb-sidenav-menu-heading">Modulos</div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                            Catálogos
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="vigentes.php">Productos activos</a>
                                <a class="nav-link" href="historicos.php">Productos inactivos</a>
                                <a class="nav-link" href="misvideos.php">Videos</a>
                                <a class="nav-link" href="miscatalogos.php">Catálogos</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayoutsLinea" aria-expanded="false" aria-controls="collapseLayoutsLinea">
                            <div class="sb-nav-link-icon"><i class="bi bi-cart"></i></div>
                            Tienda en línea
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayoutsLinea" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="compras-aprobadas.php">Compras</a>
                                <a class="nav-link" href="compras-finalizadas.php">Compras finalizadas</a>
                                <a class="nav-link" href="carga-tienda-en-linea.php">Productos activos</a>
                                <a class="nav-link" href="historicos-venta.php">Productos inactivos</a>
                                <a class="nav-link" href="cupones.php">Cupones</a>
                                <a class="nav-link" href="promociones.php">Promociones</a>
                            </nav>
                        </div>
                        <!-- <a class="nav-link" href="promociones.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-cash-coin"></i></div>
                            Promociones
                        </a> -->
                        <!-- <a class="nav-link" href="misvideos.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-play-btn-fill"></i></div>
                            Videos
                        </a> -->
                        <!-- <a class="nav-link" href="miscatalogos.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-journal-arrow-down"></i></div>
                            Catálogos
                        </a> -->
                        <div class="sb-sidenav-menu-heading">Panel de control</div>
                        <a class="nav-link" href="categorias.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-list"></i></div>
                            Categorías
                        </a>
                        <a class="nav-link" href="subcategorias.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-list-nested"></i></div>
                            Subcategorías
                        </a>
                        <a class="nav-link" href="industrias.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-building-fill"></i></div>
                            Industrias
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Usuario:</div>
                    <?php
                    if (isset($_SESSION['username'])) {
                        $registro_id = mysqli_real_escape_string($con, $_SESSION['username']);
                        $query = "SELECT * FROM usuarios WHERE username='$registro_id' ";
                        $query_run = mysqli_query($con, $query);

                        if (mysqli_num_rows($query_run) > 0) {
                            $registro = mysqli_fetch_array($query_run);
                    ?>
                            <p><?= $registro['nombre']; ?> <?= $registro['apellidopaterno']; ?> <?= $registro['apellidomaterno']; ?></p>

                    <?php
                        } else {
                            echo "<p>Error contacte a soporte</p>";
                        }
                    }
                    ?>
                </div>
            </nav>
        </div>
    </div>
</body>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="js/sidenav.js"></script>