<?php
require_once 'dbcon.php';

try {
   
    $query = "SELECT id, nombre, precio FROM productos LIMIT 6"; 
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Hubo un error al consultar los productos: " . $e->getMessage();
    $productos = []; // Evita que el HTML falle si no hay datos
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Tienda Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Mi Ecommerce</a>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="text-center mb-4">Nuestros Productos</h2>
        
        <div class="row">
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                
                                <p class="card-text text-success fw-bold">$<?php echo number_format($producto['precio'], 2); ?></p>
                                
                                <div class="mt-auto">
                                    <a href="producto-detalle.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary w-100">Ver Detalles</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center" role="alert">
                        No se encontraron productos disponibles en este momento. ¡Asegúrate de insertar algunos datos en tu tabla <strong>productos</strong> en phpMyAdmin!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>