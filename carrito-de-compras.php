<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';

header("Content-Type: text/html; charset=UTF-8");

$consultaEnvio = $con->query("SELECT valoruno, valordos FROM configuraciones WHERE nombre='Envio' LIMIT 1");
$env = $consultaEnvio->fetch_assoc();

$envioMinimo = $env['valoruno']; // Mínimo para envío gratis
$envioCosto = $env['valordos'];  // Costo de envío

$consultaComision = $con->query("SELECT valoruno FROM configuraciones WHERE id=4 LIMIT 1");
$com = $consultaComision->fetch_assoc();
$comisionValor = str_replace('%', '', $com['valoruno']); // Quitamos el % si existe
$comisionFactor = (float)$comisionValor / 100; // Ej: 0.03
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="images/ics.ico">
    <title>Carrito de compras | Mi Empresa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="shortcut icon" href="images/ico.ico" type="image/x-icon">
</head>

<body style="background-color: #f5f5f5;">
    <?php include 'componentes/menu.php'; ?>
    <div class="container-fluid">
        <div class="row mb-5 mt-5 justify-content-evenly" style="margin-top: 100px !important;padding:0px 10px;">

            <div class="col-12 col-md-6 p-4">
                <h2>CARRITO DE COMPRAS</h2>
                <p><b>Resumen de compra</b></p>
                <p>Total de productos: <span id="totalProductos">0</span></p>
                <table class="table table-cart">
                    <thead>
                        <tr>
                            <th>Cant.</th>
                            <th>Producto</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="detalleCompra"></tbody>
                </table>
                <p>Subtotal: <span id="subtotal">$ 0.00</span></p>
                <p id="row-descuento">Descuento: <span style="color: #389521ff;" id="descuento">$ 0.00</span></p>
                <p id="row-cupon">Cupón: <span style="color: #389521ff;" id="cupon">$ 0.00</span></p>
                <p id="enviop">Costo de envío: <span id="envio">$ 0.00</span></p>
                <p style="font-weight: 500;">Total a pagar: <span style="font-weight: 500;" id="totalPagar">$ 0.00</span></p>


                <!-- <button class="btn btn-secondary w-100 mt-5" disabled>Guardar carrito de compras</button> -->
                <a class="btn btn-danger w-100 mt-4 disabled" id="next" href="pedido.php">Continuar</a>

                <div class="mt-3">Tengo un cupón:
                    <div class="d-flex mt-1">
                        <input class="form-control ms-2" type="text" id="codigoCupon">
                        <button style="border-radius: 0px 10px 10px 0px;"
                            class="btn btn-secondary" id="canje">Canjear</button>
                    </div>
                </div>


                <div class="p-3 mt-3" id="envioCosto" style="background-color: #25456c2d;border:2px solid #25456c66;border-radius:10px">
                    <p class="text-dark" style="margin:0;"><small><i style="background-color: #25456c3b;color: #393939ff;padding:5px 5px 5px 10px;border-radius:50px;" class="bi bi-truck"></i> Para <b>envíos gratis</b> se requiere un <b>minimo de compra</b> de <b>$<?= number_format($envioMinimo) ?></b>.</small></p>
                </div>
                <div class="p-3 mt-3" id="envioGratis" style="background-color: #256c2a2d;border:2px solid #336c2566;border-radius:10px">
                    <p class="text-dark" style="margin:0;"><small><i style="background-color: #256c273b;color: #393939ff;padding:5px 5px 5px 10px;border-radius:50px;" class="bi bi-truck"></i> El <b>envío</b> de tus productos es <b>gratis</b>.</small></p>
                </div>
                <div class="p-3 mt-3" style="background-color: #ebbc5d78;border:2px solid #b5790066;border-radius:10px">
                    <p class="text-dark" style="margin:0;"><small><i style="background-color: #b692133b;color: #393939ff;padding:5px 5px 5px 10px;border-radius:50px;" class="bi bi-cash-coin"></i> El <b>pago es procesado</b> mediante <b>Openpay por BBVA</b> dentro de nuestro sitio web con maximos estandares de <b>seguridad y tecnología antifraude</b>.</small></p>
                </div>
            </div>
            <div class=" col-12 col-md-4 card-contain">
                <div class="row justify-content-start" id="productList">
                    <p>Cargando productos del carrito...</p>
                </div>
            </div>

        </div>
    </div>
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/filtros.js"></script>
    <script>
        let cuponDescuento = 0;
        let cuponeando = false;
        let alertaMostrada = false;
        let cuponCargadoInicial = false;
        const ENVIO_MINIMO = <?= $envioMinimo ?>;
        const ENVIO_COSTO = <?= $envioCosto ?>;
        const btnNext = document.getElementById("next");
        const COMISION_FACTOR = <?= $comisionFactor ?>;

        function getCart() {
            return JSON.parse(localStorage.getItem("empresaCart")) || [];
        }

        function saveCart(cart) {
            localStorage.setItem("empresaCart", JSON.stringify(cart));
        }

        // Agregar producto
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
            updateTotals();
        }

        // Cambiar cantidad
        function changeQuantity(id, change) {
            let cart = getCart();
            let existing = cart.find(item => item.id === id);

            if (existing) {
                existing.cantidad += change;
                if (existing.cantidad <= 0) {
                    cart = cart.filter(item => item.id !== id);
                    const card = document.getElementById(`card-${id}`);
                    if (card) card.remove();
                }
                saveCart(cart);
            } else if (change > 0) {
                cart.push({
                    id: id,
                    cantidad: 1
                });
                saveCart(cart);
            }
            updateQuantityDisplay(id);
            updateTotals();
            carritoCambio();
            checkCart();
        }

        // Actualiza cantidad visual
        function updateQuantityDisplay(id) {
            const cart = getCart();
            const item = cart.find(i => i.id === id);
            const qtySpan = document.getElementById(`qty-${id}`);
            if (qtySpan) qtySpan.textContent = item ? item.cantidad : 0;
        }

        function updateTotals() {
            const cart = getCart();
            let totalProductos = cart.length;
            let subtotalConComision = 0;
            let totalDescuentoBase = 0;
            let detalle = "";

            cart.forEach(item => {
                const elPrice = document.getElementById(`price-${item.id}`);
                if (!elPrice) return;

                const pUnitario = parseFloat(elPrice.dataset.precio || 0);
                const pMayoreo = parseFloat(elPrice.dataset.mayoreo || 0);
                const minMayoreo = parseInt(elPrice.dataset.minmayoreo || 0);
                const descuentoBase = parseFloat(elPrice.dataset.descuento || 0);
                const titulo = document.getElementById(`title-${item.id}`)?.textContent || "";

                // 1. Determinar si aplica Mayoreo
                let aplicaMayoreo = (minMayoreo > 0 && item.cantidad >= minMayoreo);

                let precioBaseSeleccionado = aplicaMayoreo ? pMayoreo : pUnitario;

                // 2. Si aplica mayoreo, el descuento es CERO
                let descuentoAplicable = aplicaMayoreo ? 0 : descuentoBase;

                const precioConComision = precioBaseSeleccionado * (1 + COMISION_FACTOR);

                subtotalConComision += precioConComision * item.cantidad;
                totalDescuentoBase += descuentoAplicable * item.cantidad;
                // --- LÓGICA DE VISIBILIDAD EN CARD ---
                let badgeMayoreo = aplicaMayoreo ? `<br><small class="text-success"><b>Precio Mayoreo Aplicado</b></small>` : "";

                // El descuento solo se muestra si NO es mayoreo y es mayor a 0
                let htmlDescuento = (descuentoAplicable > 0) ?
                    `<br>Desc: <span style="color:#389521ff">-$ ${descuentoAplicable.toFixed(2)}</span>` :
                    "";

                elPrice.innerHTML = `Precio unit: $ ${precioConComision.toFixed(2)} ${badgeMayoreo} ${htmlDescuento}`;

                const totalFila = (precioConComision - descuentoAplicable) * item.cantidad;
                detalle += `
    <tr>
        <td>${item.cantidad}</td>
        <td style="width: 50%;">${titulo}</td>
        <td>$ ${totalFila.toFixed(2)}</td>
    </tr>`;
            });

            // Cálculos finales
            let montoParaEnvio = subtotalConComision - totalDescuentoBase - cuponDescuento;
            let costoEnvioAplicado = 0;

            if (montoParaEnvio < ENVIO_MINIMO && cart.length > 0) {
                costoEnvioAplicado = ENVIO_COSTO;
                document.getElementById("envioCosto").style.display = "block";
                document.getElementById("envioGratis").style.display = "none";
                document.getElementById("enviop").style.display = "block";
                document.getElementById("envio").textContent = `$ ${ENVIO_COSTO.toFixed(2)}`;
            } else {
                costoEnvioAplicado = 0;
                document.getElementById("envioCosto").style.display = "none";
                document.getElementById("envioGratis").style.display = (cart.length > 0) ? "block" : "none";
                document.getElementById("enviop").style.display = "none";
            }

            let totalFinal = Math.max(0, subtotalConComision - totalDescuentoBase - cuponDescuento + costoEnvioAplicado);

            // --- LÓGICA DE VISIBILIDAD EN RESUMEN (Abajo de tabla) ---
            // Ocultar/Mostrar fila de Descuento
            document.getElementById("row-descuento").style.display = (totalDescuentoBase > 0) ? "block" : "none";

            // Ocultar/Mostrar fila de Cupón
            document.getElementById("row-cupon").style.display = (cuponDescuento > 0) ? "block" : "none";

            // Actualizar el DOM
            document.getElementById("detalleCompra").innerHTML = detalle;
            document.getElementById("totalProductos").textContent = totalProductos;
            document.getElementById("subtotal").textContent = `$ ${subtotalConComision.toFixed(2)}`;
            document.getElementById("descuento").textContent = `$ ${totalDescuentoBase.toFixed(2)}`;
            document.getElementById("cupon").textContent = `$ ${cuponDescuento.toFixed(2)}`;
            document.getElementById("totalPagar").textContent = `$ ${totalFinal.toFixed(2)}`;

            actualizarColoresDescuentos();
        }

        function actualizarColoresDescuentos() {
            const descuentoSpan = document.getElementById("descuento");
            const cuponSpan = document.getElementById("cupon");

            const descuentoVal = parseFloat(descuentoSpan.textContent.replace("$", "").trim());
            const cuponVal = parseFloat(cuponSpan.textContent.replace("$", "").trim());

            // Color descuento
            if (descuentoVal === 0) {
                descuentoSpan.style.color = "black";
            } else {
                descuentoSpan.style.color = "#389521ff";
            }

            // Color cupón
            if (cuponVal === 0) {
                cuponSpan.style.color = "black";
            } else {
                cuponSpan.style.color = "#389521ff";
            }
        }

        function checkCart() {
            let cart = localStorage.getItem("empresaCart");

            // Si no existe o está vacío
            if (!cart || cart === "[]" || cart.trim().length === 0) {
                btnNext.classList.add("disabled");
                btnNext.style.pointerEvents = "none";
            } else {
                btnNext.classList.remove("disabled");
                btnNext.style.pointerEvents = "auto";
            }
        }

        // Cargar productos del carrito
        document.addEventListener("DOMContentLoaded", () => {
            const cart = getCart();
            const ids = cart.map(item => item.id);

            if (ids.length === 0) {
                document.getElementById("productList").innerHTML = `
        <div style='min-height: 70vh; display: flex; justify-content: center; align-items: center; text-align: center;'>
            <div><p>No tienes productos en el carrito</p>
            <a href='tienda-en-linea.php' class='btn btn-secondary'>Tienda en línea</a></div>
        </div>`;
                updateTotals();
                return;
            }

            $.post("get_cart_products.php", {
                ids
            }, function(data) {
                if (!data || data.length === 0) {
                    $("#productList").html("<p>No se encontraron productos.</p>");
                    // Si no hay datos válidos, limpiar el carrito por completo
                    saveCart([]);
                    updateTotals();
                    const codigoGuardado = localStorage.getItem("empresaCupon");
                    if (codigoGuardado && !cuponCargadoInicial) {
                        cuponCargadoInicial = true;
                        validarCupon(codigoGuardado, true);
                    }

                    return;
                }

                // Validar que todos los IDs existan en la respuesta
                const validIDs = data.map(prod => String(prod.productoID));
                let cart = getCart();
                const filteredCart = cart.filter(item => validIDs.includes(String(item.id)));

                // Si se eliminaron productos inexistentes, actualizar el localStorage y mostrar alerta
                if (filteredCart.length !== cart.length) {
                    saveCart(filteredCart);
                    Swal.fire({
                        icon: 'info',
                        title: 'Productos actualizados',
                        text: 'Algunos productos ya no están disponibles y fueron eliminados de tu carrito.',
                        confirmButtonColor: '#c93434',
                        confirmButtonText: 'Entendido'
                    });
                }

                let html = "";
                data.forEach(prod => {
                    html += `
<div class="col-12 mt-3" id="card-${prod.productoID}">
    <div class="card" style="width: 100%;">
        <div class="row g-0">
            <div class="col-5 col-md-4">
                <div style="height: 160px; overflow: hidden;">
                    <a href="ver-producto.php?id=${prod.productoID}">
                        <img src="${prod.primer_medio || 'images/ico.ico'}" class="img-fluid rounded-start" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                </div>
            </div>
            <div class="col-7 col-md-8">
                <div class="card-body card-buy">
                    <h5 id="title-${prod.productoID}" class="card-title" style="text-transform: uppercase; font-weight: 600;">${prod.titulo}</h5>
                    <div class="ms-2 align-items-center">
                        <p id="price-${prod.productoID}" 
                           data-precio="${prod.preciounitario}" 
                           data-mayoreo="${prod.preciomayoreo}" 
                           data-minmayoreo="${prod.cantidadmayoreo}"
                           data-descuento="${prod.descuento}">
                           Cargando precio...
                        </p>
                        <button class="btn btn-sm btn-outline-secondary" onclick="changeQuantity('${prod.productoID}', -1)">−</button>
                        <span id="qty-${prod.productoID}" class="mx-2">0</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="changeQuantity('${prod.productoID}', 1)">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>`;
                });

                $("#productList").html(html);
                filteredCart.forEach(item => updateQuantityDisplay(item.id));
                updateTotals();
                const codigoGuardado = localStorage.getItem("empresaCupon");
                if (codigoGuardado && !cuponCargadoInicial) {
                    cuponCargadoInicial = true;
                    validarCupon(codigoGuardado, true);
                }

            }, "json");

            checkCart();
            window.addEventListener("storage", checkCart);
        });

        $("#canje").on("click", function(e) {
            e.preventDefault();

            let codigo = $("#codigoCupon").val().trim().toUpperCase();
            if (codigo === "") {
                Swal.fire("Código vacío", "Escribe un cupón para continuar", "warning");
                return;
            }

            validarCupon(codigo, false); // false = con alertas
        });


        function validarCupon(codigo, silencioso = false) {
            if (cuponeando) return;
            cuponeando = true;

            let total = parseFloat($("#subtotal").text().replace("$", "").trim());
            let descuento = parseFloat($("#descuento").text().replace("$", "").trim());
            let subtotal = total - descuento;

            $.ajax({
                url: "validar_cupon.php",
                type: "POST",
                data: {
                    codigo,
                    subtotal
                },
                dataType: "json",
                success: function(res) {

                    if (!res.ok) {

                        if (silencioso) {
                            Swal.fire("Cupón inválido", res.msg, "warning").then(() => {
                                cuponDescuento = 0;
                                localStorage.removeItem("empresaCupon");
                                updateTotals();
                                cuponeando = false;
                            });
                            return;
                        }

                        //  Si NO es silencioso (el usuario presionó Canjear)
                        Swal.fire("Cupón inválido", res.msg, "warning").then(() => {
                            cuponDescuento = 0;
                            localStorage.removeItem("empresaCupon");
                            updateTotals();
                            cuponeando = false;
                        });

                    } else {

                        // Cupón válido
                        cuponDescuento = res.descuento;
                        localStorage.setItem("empresaCupon", codigo);
                        updateTotals();

                        // Mostrar alerta SOLO si el usuario presionó Canjear
                        if (!silencioso) {
                            Swal.fire({
                                icon: "success",
                                title: "Cupón aplicado",
                                text: `Descuento aplicado: $${res.descuento.toFixed(2)}`,
                                confirmButtonColor: "#c93434"
                            });
                        }

                        cuponeando = false;
                    }
                }
            });
        }

        function carritoCambio() {
            updateTotals();

            // Si hay cupón guardado, reevaluar
            let codigo = localStorage.getItem("empresaCupon");
            if (codigo) {
                validarCupon(codigo, true); 
            }
        }
    </script>
</body>

</html>