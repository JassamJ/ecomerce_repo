<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ECOMMERCE PHP</title>
    
</head>
<body>
<div class="contenedor">
<?php
require_once("dbcon.php");
?>
<link rel="stylesheet" href="css/index.css">
<div class="emoji">🛍️</div>
<h1>ECOMMERCE PHP</h1>
<hr>
<p class="estado">Estado del sistema</p>
<div class="correcto">
    Yeii, Proyecto conectado correctamente a la base de datos.
</div>
<form action="login.php" method="get">
    <button type="submit"> Ir al Login </button>
</form>
<footer>
    Administración de Servidores
</footer>

</div>

</body>
</html>