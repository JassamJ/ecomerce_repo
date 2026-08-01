<link rel="stylesheet" href="css/menu.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<nav class="navbar navbar-expand-md navbar-light fixed-top" style="z-index:99;">
  <div class="container-fluid containernav" style="margin: 0px 45px;">
    <a class="navbar-brand" href="#">
      <div class="row justify-content-center align-items-center">
        <img src="images/logo.png" class="h-8 logomenu" alt="Logo">
      </div>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
        <img src="images/logo.png" class="offcanvas-title" style="width: 80%;" alt="Logo fastpack">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <!-- Navbar items aligned to the right -->
        <div class="navbar-nav ms-auto menucanvass">
          <p>Aqui va una descripcion para la versión movíl</p>
          <hr>
          <a class="nav-item nav-link" href="login-php">Intranet</a>
          <a class="nav-item nav-link" href="tienda-en-linea.php">Tienda en línea</a>
          <a class="nav-item nav-link" href="carrito-de-compras.php"><i class="fas fa-shopping-cart"></i></a>
        </div>
      </div>
    </div>
  </div>
</nav>

<script src="js/menu.js"></script>