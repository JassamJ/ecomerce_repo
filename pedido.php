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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="shortcut icon" type="image/x-icon" href="images/ico.ico" />
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSynovc&libraries=places&callback=initMap" async></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let cart = localStorage.getItem("empresaCart");
            if (!cart || cart === "[]" || cart.trim() === "") {
                window.location.href = "tienda-en-linea.php";
            }
            try {
                let parsed = JSON.parse(cart);
                if (!Array.isArray(parsed) || parsed.length === 0) {
                    window.location.href = "tienda-en-linea.php";
                }
            } catch (e) {
                window.location.href = "tienda-en-linea.php";
            }
        });

        let autocompleteInstances = {};

        function initMap() {
            const addressInputs = document.querySelectorAll('input[name="calle"]');
            const postalCodeInput = document.getElementById('postal');

            addressInputs.forEach(input => {
                if (!autocompleteInstances[input.name]) {
                    autocompleteInstances[input.name] = new google.maps.places.Autocomplete(input, {
                        fields: ["place_id", "address_components"],
                        componentRestrictions: {
                            country: ["mx"]
                        }
                    });

                    autocompleteInstances[input.name].addListener("place_changed", () => {
                        const place = autocompleteInstances[input.name].getPlace();
                        handlePlaceChange(place);
                    });
                }
            });
        }

        function handlePlaceChange(place) {
            const postalCodeInput = document.getElementById('postal');

            if (!place.address_components) {
                document.querySelectorAll("[required]").forEach(i => i.value = "");
                return;
            }

            place.address_components.forEach(component => {
                const type = component.types[0];
                const longName = component.long_name;

                switch (type) {
                    case "street_number":
                        setValue("exterior", longName);
                        break;
                    case "route":
                        setValue("calle", longName);
                        break;
                    case "sublocality":
                    case "neighborhood":
                    case "political":
                        setValue("colonia", longName);
                        break;
                    case "locality":
                        setValue("ciudad", longName);
                        break;
                    case "administrative_area_level_1":
                        setValue("estado", longName);
                        break;
                    case "country":
                        setValue("pais", longName);
                        break;
                    case "postal_code":
                        postalCodeInput.value = longName;
                        postalCodeInput.dataset.touched = "true";
                        validarCampo(postalCodeInput);
                        break;
                }
            });

            validarFormulario();
        }
    </script>
    <title>Envío | Mi Empresa</title>
</head>

<body>
    <?php include('componentes/menu.php'); ?>

    <div class="container-fluid">
        <div class="row mt-5 justify-content-center">
            <div class="col-12 col-md-8 mt-5 p-5">
                <h2>PASO 2: INFORMACIÓN PARA ENVÍO</h2>
                <form action="codeenvio.php" method="post" class="row mt-4">
                    <input type="hidden" name="cuponLS" id="cuponLS">
                    <input type="hidden" name="cartLS" id="cartLS">
                    <div class="form-floating col-12">
                        <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre" autocomplete="off" required maxlength="50">
                        <label for="nombre">Nombre</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="form-floating col-12 col-md-6 mt-3">
                        <input type="text" class="form-control" name="apellidop" id="apellidop" placeholder="Apellido paterno" autocomplete="off" required maxlength="35">
                        <label for="apellidop">Apellido paterno</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="form-floating col-12 col-md-6 mt-3">
                        <input type="text" class="form-control" name="apellidom" id="apellidom" placeholder="Apellido materno" autocomplete="off" required maxlength="35">
                        <label for="apellidom">Apellido materno</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>
                    <div class="form-floating col-12 col-md-8 mt-3">
                        <input type="email" class="form-control" name="email" id="email" placeholder="Email" autocomplete="off" required maxlength="80">
                        <label for="email">Email</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="form-floating col-12 col-md-4 mt-3">
                        <input type="text" class="form-control" name="telefono" id="telefono" placeholder="Telefono" autocomplete="off" required minlength="10" maxlength="10">
                        <label for="telefono">Teléfono (10 dígitos)</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <p class="small"><i class="bi bi-info-circle"></i> Asegurate de ingresar correctamente tu email ya que será donde recibirás los detalles de tu compra</p>
                    </div>

                    <div class="col-12 col-md-8 form-floating mb-3">
                        <input type="text" class="form-control" name="calle" placeholder="Calle" autocomplete="off" required maxlength="50">
                        <label for="calle">Calle</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="col-12 col-md-4 form-floating mb-3">
                        <input type="text" class="form-control" name="exterior" placeholder="Exterior" autocomplete="off" required maxlength="10">
                        <label for="exterior">Número exterior</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="col-12 col-md-4 form-floating mb-3">
                        <input type="text" class="form-control" name="interior" placeholder="Interior" autocomplete="off" maxlength="10">
                        <label for="interior">Número interior</label>
                    </div>
                    <div class="col-12 col-md-8 form-floating mb-3">
                        <input type="text" class="form-control" name="colonia" placeholder="Colonia" autocomplete="off" required maxlength="50">
                        <label for="colonia">Colonia / Fraccionamiento</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>
                    <div class="col-12 col-md-6 form-floating mb-3">
                        <input type="text" class="form-control" name="ciudad" placeholder="Ciudad" autocomplete="off" required maxlength="50">
                        <label for="ciudad">Ciudad / Municipio</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>
                    <div class="col-12 col-md-6 form-floating mb-3">
                        <input type="text" class="form-control" name="estado" placeholder="Estado" autocomplete="off" required maxlength="50">
                        <label for="estado">Estado</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>
                    <div class="col-12 col-md-7 form-floating mb-3">
                        <input type="text" class="form-control" name="postal" id="postal" placeholder="Postal" autocomplete="off" required maxlength="20">
                        <label for="postal">Código postal</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>
                    <div class="col-12 col-md-5 form-floating mb-3">
                        <input type="text" class="form-control" name="pais" placeholder="Pais" autocomplete="off" required maxlength="50">
                        <label for="web">País</label>
                        <div class="invalid-feedback">
                            Este campo es obligatorio
                        </div>
                    </div>

                    <div class="col-12"><button class="btn btn-danger w-100" id="btnGuardar" name="save" type="submit" disabled>Ir a pagar</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@10'></script>
    <script src="js/menu.js"></script>
    <script>
        function setValue(name, value) {
            const input = document.querySelector(`input[name="${name}"]`);
            if (!input) return;

            input.value = value;
            input.dataset.touched = "true"; // IMPORTANTE
            validarCampo(input);
        }


        function validarFormulario() {
            const form = document.querySelector("form");
            const requiredFields = form.querySelectorAll("[required]");
            const btnGuardar = document.getElementById("btnGuardar");
            let allFilled = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) allFilled = false;
            });

            const cartLS = JSON.parse(localStorage.getItem("empresaCart") || "[]");
            const cartNotEmpty = Array.isArray(cartLS) && cartLS.length > 0;

            btnGuardar.disabled = !(allFilled && cartNotEmpty);
        }

        document.querySelectorAll("[required]").forEach(input => {

            // Cuando el usuario entra al campo
            input.addEventListener("focus", () => {
                input.dataset.touched = "true";
            });

            // Cuando sale del campo
            input.addEventListener("blur", () => {
                validarCampo(input);
                validarFormulario();
            });

            // Mientras escribe
            input.addEventListener("input", () => {
                validarCampo(input);
                validarFormulario();
            });
        });

        function validarCampo(input) {
            if (!input.dataset.touched) return;

            const value = input.value.trim();
            let valido = true;
            let mensaje = "Este campo es obligatorio";

            // Campo vacío
            if (!value) {
                valido = false;
            }

            // Email
            if (valido && input.type === "email") {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    valido = false;
                    mensaje = "Ingresa un correo electrónico válido";
                }
            }

            // Teléfono (exactamente 10 dígitos)
            if (valido && input.name === "telefono") {
                if (!/^\d{10}$/.test(value)) {
                    valido = false;
                    mensaje = "El teléfono debe tener 10 dígitos";
                }
            }

            // Aplicar clases y mensaje
            const feedback = input.parentElement.querySelector(".invalid-feedback");
            if (!valido) {
                input.classList.add("is-invalid");
                input.classList.remove("is-valid");
                if (feedback) feedback.textContent = mensaje;
            } else {
                input.classList.remove("is-invalid");
                input.classList.add("is-valid");
            }
        }


        window.addEventListener("storage", validarFormulario);

        document.addEventListener("DOMContentLoaded", validarFormulario);

        document.getElementById("btnGuardar").addEventListener("click", function() {
            document.getElementById("cuponLS").value = localStorage.getItem("empresaCupon") || "";
            document.getElementById("cartLS").value = localStorage.getItem("empresaCart") || "[]";
        });

        document.getElementById("telefono").addEventListener("input", function() {
            this.value = this.value.replace(/\D/g, "");
        });
    </script>
</body>

</html>