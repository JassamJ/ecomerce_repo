<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';

header("Content-Type: text/html; charset=UTF-8");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Google Tag Manager -->

    <!-- End Google Tag Manager -->
    <link rel="shortcut icon" type="image/x-icon" href="images/ics.ico">
    <title>Tienda en línea | Mi Empresa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/slickslider.css">
    <link rel="shortcut icon" href="images/ico.ico" type="image/x-icon">
</head>
<style>

</style>

<body style="background-color: #f5f5f5;">
    <!-- Google Tag Manager (noscript) -->
    <!-- End Google Tag Manager (noscript) -->
    <?php include 'componentes/menu.php'; ?>
    <div class="container-fluid">
        <div class="row mb-5 mt-5 justify-content-start" style="margin-top: 100px !important;padding:0px 10px;">
            <?php
            $query_promo = "SELECT * FROM promociones WHERE estatus = 1 ORDER BY id DESC";
            $query_run_promo = mysqli_query($con, $query_promo);

            if (mysqli_num_rows($query_run_promo) > 0) {
            ?>
                <div class="col-12 p-0 mb-3">
                    <div class="slickcard">
                        <?php foreach ($query_run_promo as $registro_promo): ?>
                            <div class="slickimg" data-aos="zoom-in">
                                <a style="width: 100%;" href="<?= $registro_promo['url']; ?>">
                                    <img src="<?= $registro_promo['medio']; ?>" alt="promo">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php
            }
            ?>

            <div class="col-12 col-md-2 category_list">

                <div class="form-floating mt-1 mb-3">
                    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Buscar producto...">
                    <label style="padding-left: 0px;" for="floatingInput">Buscar...</label>
                </div>

                <!-- Filtros de Industrias -->
                <p class="mt-0 mb-0"><small>Industrias:</small></p>
                <label>
                    <input type="checkbox" name="all" class="all_item" value="all">
                    Todo
                </label>
                <?php
                $query = "SELECT * FROM industrias ORDER BY id DESC";
                $query_run = mysqli_query($con, $query);
                if (mysqli_num_rows($query_run) > 0) {
                    foreach ($query_run as $registro) {
                ?>
                        <label>
                            <input type="checkbox" name="industry[]" class="industry_item" value="<?= htmlspecialchars($registro['industria'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($registro['industria'], ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                <?php
                    }
                } else {
                    echo "<div style='min-height:70vh;text-align:center;'><p>No se encontró ninguna industria</p></div>";
                }
                ?>

                <!-- Filtros de Categorías -->
                <p class="mt-3 mb-1"><small>Categorías:</small></p>
                <?php
                $query = "SELECT * FROM categorias ORDER BY id DESC";
                $query_run = mysqli_query($con, $query);
                if (mysqli_num_rows($query_run) > 0) {
                    foreach ($query_run as $registro) {
                ?>
                        <label>
                            <input type="checkbox" name="category[]" class="category_item" value="<?= $registro['categoria']; ?>">
                            <?= $registro['categoria']; ?>
                        </label>
                <?php
                    }
                } else {
                    echo "<div style='min-height:70vh;text-align:center;'><p>No se encontró ninguna categoría</p></div>";
                }
                ?>

            </div>

            <div class=" col-12 col-md-10 card-contain">

                <div class="row justify-content-start" id="productList">
                    <?php
                    $query_config = "SELECT valoruno FROM configuraciones WHERE id = 4 LIMIT 1";
                    $result_config = mysqli_query($con, $query_config);
                    $comision_porcentaje = 0;

                    if ($row_config = mysqli_fetch_assoc($result_config)) {
                        $valor_limpio = str_replace('%', '', $row_config['valoruno']);
                        $comision_porcentaje = (float)$valor_limpio / 100;
                    }

                    $query = "SELECT p.id AS productoID, p.titulo, p.subtitulo, p.preciounitario, p.descuento, p.preciomayoreo, p.cantidadmayoreo,
           GROUP_CONCAT(DISTINCT c.categoria ORDER BY c.categoria ASC SEPARATOR ', ') AS categorias,
           GROUP_CONCAT(DISTINCT s.subcategoria ORDER BY s.subcategoria ASC SEPARATOR ', ') AS subcategorias,
           GROUP_CONCAT(DISTINCT i.industria ORDER BY i.industria ASC SEPARATOR ', ') AS industrias,
           (SELECT medio FROM mediosventa WHERE idproducto = p.id ORDER BY id LIMIT 1) AS primer_medio
            FROM productosventa p
            LEFT JOIN categoriasasociadasventa c ON p.id = c.idproducto
            LEFT JOIN subcategoriasasociadasventa s ON p.id = s.idproducto
            LEFT JOIN industriaasociadaventa i ON p.id = i.idproducto
            WHERE NOT EXISTS (
            SELECT 1 
            FROM asociarproductos ap
            WHERE ap.idproductopack = p.id)
            GROUP BY p.id
            ORDER BY p.id DESC";

                    $query_run = mysqli_query($con, $query);
                    if (mysqli_num_rows($query_run) > 0) {
                        foreach ($query_run as $registro) {
                            // Cálculos de precios con comisión aplicada
                            // Precio final: (Unitario - Descuento) * (1 + Comision)
                            $precio_con_descuento_base = $registro['preciounitario'] - $registro['descuento'];
                            $precio_final_con_comision = $precio_con_descuento_base * (1 + $comision_porcentaje);

                            // Precio original tachado: Unitario * (1 + Comision)
                            $precio_original_con_comision = $registro['preciounitario'] * (1 + $comision_porcentaje);
                    ?>
                            <div class="col-6 col-md-3 product-item d-flex"
                                data-unitario="<?= $registro['preciounitario']; ?>"
                                data-mayoreo="<?= $registro['preciomayoreo']; ?>"
                                data-minmayoreo="<?= $registro['cantidadmayoreo']; ?>"
                                data-comision="<?= $comision_porcentaje; ?>"
                                data-descuento="<?= $registro['descuento']; ?>"
                                id="product-card-<?= $registro['productoID']; ?>"
                                data-industry="<?= htmlspecialchars($registro['industrias'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-category="<?= htmlspecialchars($registro['categorias'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-subcategory="<?= htmlspecialchars($registro['subcategorias'], ENT_QUOTES, 'UTF-8'); ?>">

                                <div class="card img-card-container" style="width: 100%;">
                                    <a style="text-decoration: none; color: #000;" href="ver-producto.php?id=<?= $registro['productoID']; ?>">
                                        <?php if ($registro['primer_medio']) { ?>
                                            <img src="<?= $registro['primer_medio']; ?>" class="card-img-top" style="object-fit: contain;" alt="...">
                                        <?php } else { ?>
                                            <img src="images/ico.ico" class="card-img-top" alt="Default Image">
                                        <?php } ?>
                                        <div class="card-body" style="padding-bottom: 0px !important;">
                                            <div>
                                                <h5 style="text-transform: uppercase; font-weight: 400;" class="card-title"><?= htmlspecialchars($registro['titulo'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                                <p style="margin-bottom: 0px;" class="card-text"><?= htmlspecialchars($registro['subtitulo'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            </div>
                                            <p style="margin-bottom: 0px !important; font-size: 15px; font-weight: 600;">
                                                <span id="price-display-<?= $registro['productoID']; ?>">
                                                    $<?= number_format($precio_final_con_comision, 2); ?>
                                                </span>

                                                <span id="old-price-display-<?= $registro['productoID']; ?>"
                                                    style="color: #39ad19ff; text-decoration: line-through; font-size: 13px; margin-left: 10px; 
      <?php
                            // Solo mostrar si tiene descuento real > 0
                            // No mostramos mayoreo aquí porque eso solo se sabe cuando el usuario elige cantidad
                            if (!((float)$registro['descuento'] > 0)) {
                                echo 'display:none;';
                            }
        ?>">
                                                    <b>$<?= number_format($precio_original_con_comision, 2); ?></b>
                                                </span>
                                            </p>

                                            <?php 
// Validamos que:
// 1. Tenga cantidad de mayoreo mayor a 0
// 2. Tenga precio de mayoreo mayor a 0
// 3. El precio de mayoreo sea realmente más barato que el unitario
if ($registro['cantidadmayoreo'] > 0 && $registro['preciomayoreo'] > 0 && $registro['preciomayoreo'] < $registro['preciounitario']): 
?>
    <div style="font-size: 11px; color: #39ad19ff; font-weight: 500; margin-top: 2px;">
        <i class="bi bi-tag-fill"></i> Precio mayoreo desde <?= $registro['cantidadmayoreo'] ?> pzs.
    </div>
<?php endif; ?>
                                        </div>
                                    </a>
                                    <!-- Botón de aadir carrito -->
                                    <div class="d-flex align-items-center p-3 mt-auto">
                                        <button onclick="addCart('<?= $registro['productoID']; ?>')"
                                            class="btn btn-danger w-100"
                                            id="btn-add-<?= $registro['productoID']; ?>">
                                            <small>
                                                <i class="bi bi-cart2"></i>
                                                <span class="add-text"> Añadir</span>
                                            </small>
                                        </button>

                                        <div id="counter-<?= $registro['productoID']; ?>" class="ms-2 align-items-center" style="display: none;">
                                            <button class="btn btn-sm btn-outline-secondary" onclick="changeQuantity('<?= $registro['productoID']; ?>', -1)">−</button>
                                            <span id="qty-<?= $registro['productoID']; ?>" class="mx-2">0</span>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="changeQuantity('<?= $registro['productoID']; ?>', 1)">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<div style='min-height: 70vh;display: flex;justify-content: center;align-items: center;text-align: center;'><p>No se encontró ningún producto</p></div>";
                    }
                    ?>

                </div>
            </div>

        </div>
    </div>
    <?php include 'footer.php'; ?>

    <div class="floating-button" id="cartButton" style="display: none;">
        <a href="carrito-de-compras.php">
            <span style="background-color: #fff; color: #213443; padding: 5px 5px 5px 7px; border-radius: 50px; margin-right: 10px;">
                <i class="bi bi-cart"></i>
                <span id="cartCount" style="font-weight: bold; margin-left: 4px;"></span>
            </span>
            Carrito de compras
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script src="js/slickpromo.js"></script>
    <script src="js/filtros.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const cartButton = document.getElementById("cartButton");
            const cartCount = document.getElementById("cartCount");

            function getCart() {
                return JSON.parse(localStorage.getItem("empresaCart")) || [];
            }

            function saveCart(cart) {
                localStorage.setItem("empresaCart", JSON.stringify(cart));
                window.dispatchEvent(new Event("cartUpdated"));
            }

            function actualizarBotonCarrito() {
                const cart = getCart();
                if (Array.isArray(cart) && cart.length > 0) {
                    cartButton.style.display = "block";
                    cartCount.textContent = cart.length;
                } else {
                    cartButton.style.display = "none";
                }
            }

            actualizarBotonCarrito();

            window.addEventListener("cartUpdated", actualizarBotonCarrito);

            window.addEventListener("storage", function(e) {
                if (e.key === "empresaCart") actualizarBotonCarrito();
            });

            function addCart(id) {
                let cart = getCart();
                let existing = cart.find(item => item.id === id);
                if (existing) {
                    existing.cantidad++;
                } else {
                    cart.push({
                        id: id,
                        cantidad: 1
                    });
                }
                saveCart(cart);
                updateQuantityDisplay(id);
            }

            function changeQuantity(id, change) {
                let cart = getCart();
                let existing = cart.find(item => item.id === id);

                if (existing) {
                    existing.cantidad += change;
                    if (existing.cantidad <= 0) {
                        cart = cart.filter(item => item.id !== id);
                    }
                } else if (change > 0) {
                    cart.push({
                        id: id,
                        cantidad: 1
                    });
                }

                saveCart(cart);
                updateQuantityDisplay(id);
            }

            function updateQuantityDisplay(id) {
                const cart = getCart();
                const item = cart.find(i => i.id === id);
                const qty = item ? item.cantidad : 0;

                const card = document.getElementById(`product-card-${id}`);
                if (!card) return;

                // Datos desde los atributos data-
                const pUnitario = parseFloat(card.dataset.unitario) || 0;
                const pMayoreo = parseFloat(card.dataset.mayoreo) || 0;
                const minMayoreo = parseInt(card.dataset.minmayoreo) || 0;
                const comision = parseFloat(card.dataset.comision) || 0;
                const descuento = parseFloat(card.dataset.descuento) || 0;

                const priceDisplay = document.getElementById(`price-display-${id}`);
                const oldPriceDisplay = document.getElementById(`old-price-display-${id}`);

                // VALIDACIÓN DE MAYOREO REAL: 
                // Debe haber una cantidad mínima configurada (>0)
                // El precio de mayoreo debe ser mayor a cero
                // Y el precio de mayoreo debe ser estrictamente menor al unitario
                let tieneMayoreoValido = (minMayoreo > 0 && pMayoreo > 0 && pMayoreo < pUnitario);

                // Se activa el beneficio si se cumple lo anterior Y el usuario alcanzó la cantidad
                let esMayoreoActivo = (tieneMayoreoValido && qty >= minMayoreo);

                // El precio base será el de mayoreo si está activo, de lo contrario el unitario
                let precioBase = esMayoreoActivo ? pMayoreo : pUnitario;

                // Cálculo de montos finales con comisión
                let precioFinal = (precioBase - descuento) * (1 + comision);
                let precioReferenciaOriginal = pUnitario * (1 + comision);

                // 1. Actualizar el precio principal que paga el cliente
                if (priceDisplay) {
                    priceDisplay.textContent = `$${precioFinal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                }

                // 2. Lógica del precio tachado (old-price)
                if (oldPriceDisplay) {
                    // Se muestra solo si hay un descuento directo O si el mayoreo está activo
                    if (descuento > 0 || esMayoreoActivo) {
                        oldPriceDisplay.style.display = "inline";
                        oldPriceDisplay.innerHTML = `<b>$${precioReferenciaOriginal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</b>`;
                    } else {
                        oldPriceDisplay.style.display = "none";
                    }
                }

                // Manejo de botones (se mantiene igual)
                const qtySpan = document.getElementById(`qty-${id}`);
                const counterDiv = document.getElementById(`counter-${id}`);
                const addBtn = document.getElementById(`btn-add-${id}`);

                if (qty > 0) {
                    if (qtySpan) qtySpan.textContent = qty;
                    if (counterDiv) counterDiv.style.display = "flex";
                    if (addBtn && addBtn.querySelector(".add-text")) addBtn.querySelector(".add-text").style.display = "none";
                } else {
                    if (counterDiv) counterDiv.style.display = "none";
                    if (addBtn && addBtn.querySelector(".add-text")) addBtn.querySelector(".add-text").style.display = "inline";
                }
            }

            getCart().forEach(item => updateQuantityDisplay(item.id));

            window.addCart = addCart;
            window.changeQuantity = changeQuantity;
        });
    </script>
</body>

</html>