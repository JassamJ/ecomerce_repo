<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['delete'])) {
    $id = mysqli_real_escape_string($con, $_POST['delete']);

    $query = "DELETE FROM productosventa WHERE id='$id' ";
    $query_run = mysqli_query($con, $query);
    $querymedio = "DELETE FROM mediosventa WHERE idproducto='$id' ";
    $querymedio_run = mysqli_query($con, $querymedio);
    $queryindustria = "DELETE FROM industriaasociadaventa WHERE idproducto='$id' ";
    $queryindustria_run = mysqli_query($con, $queryindustria);
    $querycategoria = "DELETE FROM categoriasasociadasventa WHERE idproducto='$id' ";
    $querycategoria_run = mysqli_query($con, $querycategoria);
    $querysubcategoria = "DELETE FROM subcategoriasasociadasventa WHERE idproducto='$id' ";
    $querysubcategoria_run = mysqli_query($con, $querysubcategoria);
    $queryasociarproductos = "DELETE FROM asociarproductos WHERE idproductopadre='$id' OR idproductopack='$id'";
    $queryasociarproductos_run = mysqli_query($con, $queryasociarproductos);
    $queryasociartallas = "DELETE FROM asociartallas WHERE idproductoprincipal='$id' OR idproductotalla='$id'";
    $queryasociartallas_run = mysqli_query($con, $queryasociartallas);
    if ($query_run) {
        $_SESSION['alert'] = [
            'title' => 'ELIMINADO',
            'message' => 'Producto eliminado exitosamente',
            'icon' => 'success'
        ];
        header("Location: carga-tienda-en-linea.php");
        exit(0);
    } else {
        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Notifica a soporte',
            'icon' => 'error'
        ];
        header("Location: carga-tienda-en-linea.php");
        exit(0);
    }
}

if (isset($_POST['deletemedio'])) {
    $id = mysqli_real_escape_string($con, $_POST['deletemedio']);
    $idproducto = mysqli_real_escape_string($con, $_POST['idproducto']);

    $query = "DELETE FROM mediosventa WHERE id='$id' ";
    $query_run = mysqli_query($con, $query);

    if ($query_run) {
        $_SESSION['alert'] = [
            'title' => 'MEDIO ELIMINADO',
            'message' => 'Medio eliminado exitosamente',
            'icon' => 'success'
        ];
        header('Location: editarproductoventa.php?id=' . $idproducto);
        exit();
    } else {
        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Medio no eliminado.',
            'icon' => 'error'
        ];
        header('Location: editarproductoventa.php?id=' . $idproducto);
        exit();
    }
}


if (isset($_POST['update'])) {
    $idproducto = intval($_POST['id']); 
    $titulo = mysqli_real_escape_string($con, $_POST['titulo']);
    $subtitulo = mysqli_real_escape_string($con, $_POST['subtitulo']);
    $estatus = mysqli_real_escape_string($con, $_POST['estatus']);
    $detalles = mysqli_real_escape_string($con, $_POST['detalles']);
    $stock = mysqli_real_escape_string($con, $_POST['stock']);
    $sku = mysqli_real_escape_string($con, $_POST['sku']);
    $stockminimo = mysqli_real_escape_string($con, $_POST['stockminimo']);
    $preciounitario = mysqli_real_escape_string($con, $_POST['preciounitario']);
    $preciomayoreo = mysqli_real_escape_string($con, $_POST['preciomayoreo']);
    $cantidadmayoreo = mysqli_real_escape_string($con, $_POST['cantidadmayoreo']);
    $descuento = mysqli_real_escape_string($con, $_POST['descuento']);
    $medios_delete = isset($_POST['medios_delete']) ? $_POST['medios_delete'] : [];

    // Actualizar producto
    $query = "UPDATE productosventa SET titulo='$titulo', subtitulo='$subtitulo', estatus='$estatus', detalles='$detalles', stock='$stock', sku='$sku', stockminimo='$stockminimo', preciounitario='$preciounitario', preciomayoreo='$preciomayoreo', cantidadmayoreo='$cantidadmayoreo', descuento='$descuento' WHERE id='$idproducto'";
    if (!mysqli_query($con, $query)) {
        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Error al actualizar el producto: ' . mysqli_error($con),
            'icon' => 'error'
        ];
        header('Location: editarproductoventa.php?id=' . $idproducto);
        exit();
    }

    // Eliminar categorías e industrias anteriores
    mysqli_query($con, "DELETE FROM categoriasasociadasventa WHERE idproducto = $idproducto");
    mysqli_query($con, "DELETE FROM subcategoriasasociadasventa WHERE idproducto = $idproducto");
    mysqli_query($con, "DELETE FROM industriaasociadaventa WHERE idproducto = $idproducto");

    // Insertar nuevas subcategorías
    if (!empty($_POST['subcategoria'])) {
        foreach ($_POST['subcategoria'] as $subcategoria) {
            $subcategoria = mysqli_real_escape_string($con, $subcategoria);
            mysqli_query($con, "INSERT INTO subcategoriasasociadasventa (idproducto, subcategoria) VALUES ('$idproducto', '$subcategoria')");
        }
    }

    // Insertar nuevas categorías
    if (!empty($_POST['categoria'])) {
        foreach ($_POST['categoria'] as $categoria) {
            $categoria = mysqli_real_escape_string($con, $categoria);
            mysqli_query($con, "INSERT INTO categoriasasociadasventa (idproducto, categoria) VALUES ('$idproducto', '$categoria')");
        }
    }

    // Insertar nuevas industrias
    if (!empty($_POST['industria'])) {
        foreach ($_POST['industria'] as $industria) {
            $industria = mysqli_real_escape_string($con, $industria);
            mysqli_query($con, "INSERT INTO industriaasociadaventa (idproducto, industria) VALUES ('$idproducto', '$industria')");
        }
    }

    // Eliminar medios seleccionados y sus archivos
    if (!empty($medios_delete) && is_array($medios_delete)) {
        foreach ($medios_delete as $medio_id) {
            $medio_id = intval($medio_id); // Asegúrate de que sea un número

            // Obtener la ruta del archivo
            $query_medio = "SELECT medio FROM mediosventa WHERE id = '$medio_id'";
            $result_medio = mysqli_query($con, $query_medio);
            if ($row = mysqli_fetch_assoc($result_medio)) {
                $file_path = $row['medio'];
                if (file_exists($file_path)) {
                    unlink($file_path); // Eliminar el archivo
                }
            }

            // Eliminar la entrada de la base de datos
            $query_delete_medios = "DELETE FROM mediosventa WHERE id = '$medio_id'";
            mysqli_query($con, $query_delete_medios);
        }
    }

    // Guardar nuevos medios en la carpeta y almacenar sus rutas en la base de datos
    if (isset($_FILES['medios']) && !empty($_FILES['medios']['tmp_name'][0])) {
        $directorio = 'productosventa/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        foreach ($_FILES['medios']['tmp_name'] as $key => $tmp_name) {
            $nombre_original = $_FILES['medios']['name'][$key];
            $tipo = $_FILES['medios']['type'][$key];
            $ext = pathinfo($nombre_original, PATHINFO_EXTENSION);

            // Generar un nombre de archivo único
            $nombre_archivo = uniqid() . ".jpg";

            if (in_array($tipo, ['image/jpeg', 'image/png', 'image/jpg'])) {
                $imagen = imagecreatefromstring(file_get_contents($tmp_name));
                if ($imagen !== false) {
                    imagejpeg($imagen, $directorio . $nombre_archivo);
                    imagedestroy($imagen);
                }
            } elseif ($ext === 'pdf' || $ext === 'mp4') {
                $nombre_archivo = uniqid() . "." . $ext;
                move_uploaded_file($tmp_name, $directorio . $nombre_archivo);
            } else {
                continue; 
            }

            $ruta_archivo = $directorio . $nombre_archivo;
            $query_medio = "INSERT INTO mediosventa (idproducto, medio) VALUES ('$idproducto', '$ruta_archivo')";
            mysqli_query($con, $query_medio);
        }
    }

    if (!empty($_POST['idproductopack'])) {

        $idproducto = mysqli_real_escape_string($con, $idproducto);
        $idasoc = mysqli_real_escape_string($con, $_POST['idasoc']);
        $idproductopadre = mysqli_real_escape_string($con, $_POST['idproductopack']);
        $cantidadpack = mysqli_real_escape_string($con, $_POST['cantidadpack'] ?? 1);

        if (empty($idasoc) || $idasoc == "0") {

            $query_pack = "INSERT INTO asociarproductos (idproductopack, idproductopadre, cantidadpack)
            VALUES ('$idproducto', '$idproductopadre', '$cantidadpack')
        ";
        } else {

            $query_pack = "UPDATE asociarproductos 
            SET cantidadpack='$cantidadpack', idproductopadre='$idproductopadre'
            WHERE idproductopack='$idproducto'
              AND idproductopadre='$idasoc'
        ";
        }
        mysqli_query($con, $query_pack);
    }


    $_SESSION['alert'] = [
        'title' => 'ACTUALIZADO',
        'message' => 'Producto actualizado con éxito',
        'icon' => 'success'
    ];
    header('Location: editarproductoventa.php?id=' . $idproducto);
    exit();
}



if (isset($_POST['save'])) {
    $titulo = mysqli_real_escape_string($con, $_POST['titulo']);
    $subtitulo = mysqli_real_escape_string($con, $_POST['subtitulo']);
    $detalles = mysqli_real_escape_string($con, $_POST['detalles']);
    $stock = mysqli_real_escape_string($con, $_POST['stock']);
    $sku = mysqli_real_escape_string($con, $_POST['sku']);
    $stockminimo = mysqli_real_escape_string($con, $_POST['stockminimo']);
    $preciounitario = mysqli_real_escape_string($con, $_POST['preciounitario']);
    $preciomayoreo = mysqli_real_escape_string($con, $_POST['preciomayoreo']);
    $cantidadmayoreo = mysqli_real_escape_string($con, $_POST['cantidadmayoreo']);
    $descuento = mysqli_real_escape_string($con, $_POST['descuento']);
    $estatus = '1';

    $query = "INSERT INTO productosventa SET titulo='$titulo', subtitulo='$subtitulo', detalles='$detalles', stock='$stock', sku='$sku', stockminimo='$stockminimo', preciounitario='$preciounitario', preciomayoreo='$preciomayoreo', cantidadmayoreo='$cantidadmayoreo', descuento='$descuento', estatus='$estatus'";
    $query_run = mysqli_query($con, $query);

    if ($query_run) {
        $idproducto = mysqli_insert_id($con);

        if (!empty($_POST['categoria'])) {
            foreach ($_POST['categoria'] as $categoria) {
                $categoria = mysqli_real_escape_string($con, $categoria);
                $query_categoria = "INSERT INTO categoriasasociadasventa SET idproducto='$idproducto', categoria='$categoria'";
                mysqli_query($con, $query_categoria);
            }
        }

        if (!empty($_POST['industria'])) {
            foreach ($_POST['industria'] as $industria) {
                $industria = mysqli_real_escape_string($con, $industria);
                $query_industria = "INSERT INTO industriaasociadaventa SET idproducto='$idproducto', industria='$industria'";
                mysqli_query($con, $query_industria);
            }
        }

        if (isset($_FILES['medios']) && !empty($_FILES['medios']['tmp_name'][0])) {
            $directorio = 'productosventa/';
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }

            foreach ($_FILES['medios']['tmp_name'] as $key => $tmp_name) {
                $nombre_original = $_FILES['medios']['name'][$key];
                $tipo = $_FILES['medios']['type'][$key];
                $ext = pathinfo($nombre_original, PATHINFO_EXTENSION);

                $nombre_archivo = uniqid() . ".jpg"; 

                if (in_array($tipo, ['image/jpeg', 'image/png', 'image/jpg'])) {
                    $imagen = imagecreatefromstring(file_get_contents($tmp_name));
                    if ($imagen !== false) {
                        imagejpeg($imagen, $directorio . $nombre_archivo);
                        imagedestroy($imagen);
                    }
                } elseif ($ext == 'pdf' || $ext == 'mp4') {
                    $nombre_archivo = uniqid() . "." . $ext;
                    move_uploaded_file($tmp_name, $directorio . $nombre_archivo);
                } else {
                    continue; 
                }

                $ruta_archivo = $directorio . $nombre_archivo;
                $query_medio = "INSERT INTO mediosventa SET idproducto='$idproducto', medio='$ruta_archivo'";
                mysqli_query($con, $query_medio);
            }
        }


        if (!empty($_POST['idproductopack'])) {
            $idproductopadre = mysqli_real_escape_string($con, $_POST['idproductopack']);
            $cantidadpack = mysqli_real_escape_string($con, $_POST['cantidadpack']);

            $query_pack = "INSERT INTO asociarproductos SET cantidadpack='$cantidadpack', idproductopack='$idproducto', idproductopadre ='$idproductopadre'";
            $query_run_pack = mysqli_query($con, $query_pack);
        }

        $_SESSION['alert'] = [
            'title' => 'REGISTRADO',
            'message' => 'Producto registrado con éxito',
            'icon' => 'success'
        ];
        header("Location: carga-tienda-en-linea.php");
        exit(0);
    } else {
        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Notifica a soporte',
            'icon' => 'error'
        ];
        header("Location: carga-tienda-en-linea.php");
        exit(0);
    }
}

if (isset($_POST['duplicar'])) {

    $idproducto = intval($_POST['id']);

    mysqli_begin_transaction($con);

    try {

        /* ==========================================================
           1️⃣ DUPLICAR PRODUCTO BASE
        ========================================================== */

        $query_producto = "
            INSERT INTO productosventa (
                titulo, subtitulo, estatus, detalles, stock, sku,
                stockminimo, preciounitario, preciomayoreo,
                cantidadmayoreo, descuento, talla
            )
            SELECT
                titulo, subtitulo, estatus, detalles, stock, sku,
                stockminimo, preciounitario, preciomayoreo,
                cantidadmayoreo, descuento, 'Unitalla'
            FROM productosventa
            WHERE id = $idproducto
        ";

        mysqli_query($con, $query_producto);

        $nuevoProductoId = mysqli_insert_id($con);

        /* ==========================================================
           2️⃣ DUPLICAR CATEGORÍAS
        ========================================================== */

        mysqli_query($con, "
            INSERT INTO categoriasasociadasventa (idproducto, categoria)
            SELECT $nuevoProductoId, categoria
            FROM categoriasasociadasventa
            WHERE idproducto = $idproducto
        ");

        /* ==========================================================
           3️⃣ DUPLICAR SUBCATEGORÍAS
        ========================================================== */

        mysqli_query($con, "
            INSERT INTO subcategoriasasociadasventa (idproducto, subcategoria)
            SELECT $nuevoProductoId, subcategoria
            FROM subcategoriasasociadasventa
            WHERE idproducto = $idproducto
        ");

        /* ==========================================================
           4️⃣ DUPLICAR INDUSTRIAS
        ========================================================== */

        mysqli_query($con, "
            INSERT INTO industriaasociadaventa (idproducto, industria)
            SELECT $nuevoProductoId, industria
            FROM industriaasociadaventa
            WHERE idproducto = $idproducto
        ");

        /* ==========================================================
           5️⃣ DUPLICAR MEDIOS (SIN SUBIR ARCHIVOS)
        ========================================================== */

        mysqli_query($con, "
            INSERT INTO mediosventa (idproducto, medio)
            SELECT $nuevoProductoId, medio
            FROM mediosventa
            WHERE idproducto = $idproducto
        ");

        /* ==========================================================
           6️⃣ DUPLICAR ASOCIARPRODUCTOS (SOLO INSERT)
        ========================================================== */

        $res_pack = mysqli_query($con, "
            SELECT idproductopadre, cantidadpack
            FROM asociarproductos
            WHERE idproductopack = $idproducto
        ");

        if ($res_pack && mysqli_num_rows($res_pack) > 0) {
            while ($row = mysqli_fetch_assoc($res_pack)) {

                if (!empty($row['idproductopadre'])) {
                    mysqli_query($con, "
                        INSERT INTO asociarproductos (idproductopack, idproductopadre, cantidadpack)
                        VALUES (
                            $nuevoProductoId,
                            {$row['idproductopadre']},
                            {$row['cantidadpack']}
                        )
                    ");
                }
            }
        }

        mysqli_commit($con);

        $_SESSION['alert'] = [
            'title' => 'Exito',
            'message' => 'Tallas agregadas correctamente',
            'icon' => 'success'
        ];
        header('Location: carga-tienda-en-linea.php');
        exit();
    } catch (Exception $e) {

        mysqli_rollback($con);
        $_SESSION['alert'] = [
            'title' => 'Error al duplicar el producto',
            'message' => 'Contacte a su proveedor.' .
                $e->getMessage(),
            'icon' => 'error'
        ];
        header('Location: carga-tienda-en-linea.php');
        exit();
    }
}


if (isset($_POST['saveTalla'])) {

    if (
        empty($_POST['idproductoprincipal']) ||
        empty($_POST['talla']) ||
        !is_array($_POST['talla'])
    ) {
        die('Datos incompletos');
    }

    $idProductoPrincipal = (int) $_POST['idproductoprincipal'];
    $tallas = $_POST['talla'];
    $cantidadpack = isset($_POST['cantidadpack']) ? (int)$_POST['cantidadpack'] : 1;

    $con->begin_transaction();

    try {

        /* =====================================================
           1️ PRODUCTO BASE
        ===================================================== */
        $stmt = $con->prepare("SELECT * FROM productosventa WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $idProductoPrincipal);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            throw new Exception('Producto no encontrado');
        }

        $meta = $stmt->result_metadata();
        $producto = [];
        $binds = [];

        while ($field = $meta->fetch_field()) {
            $binds[] = &$producto[$field->name];
        }

        call_user_func_array([$stmt, 'bind_result'], $binds);
        $stmt->fetch();

        $stmt->free_result();
        $stmt->close();


        /* =====================================================
           2️ PRIMERA TALLA → UPDATE
        ===================================================== */
        $primeraTalla = array_shift($tallas);

        $stmtUpdate = $con->prepare("
            UPDATE productosventa
            SET talla = ?
            WHERE id = ?
        ");
        $stmtUpdate->bind_param("si", $primeraTalla, $idProductoPrincipal);
        $stmtUpdate->execute();

        /* =====================================================
           3️ PREPARAR DUPLICADO PRODUCTO
        ===================================================== */
        unset($producto['id']);

        $columnas = array_keys($producto);
        $placeholders = implode(',', array_fill(0, count($columnas), '?'));

        $stmtInsertProducto = $con->prepare("
            INSERT INTO productosventa (" . implode(',', $columnas) . ")
            VALUES ($placeholders)
        ");

        $stmtInsertAsociarTallas = $con->prepare("
            INSERT INTO asociartallas (idproductoprincipal, idproductotalla, talla)
            VALUES (?, ?, ?)
        ");

        /* =====================================================
           4️ DETERMINAR idproductopadre REAL (packs)
        ===================================================== */
        $idProductoPadreFinal = null;

        // Caso A: es padre
        $stmtPadre = $con->prepare("
            SELECT idproductopadre 
            FROM asociarproductos 
            WHERE idproductopadre = ? 
            LIMIT 1
        ");
        $stmtPadre->bind_param("i", $idProductoPrincipal);
        $stmtPadre->execute();
        $stmtPadre->store_result();

        if ($stmtPadre->num_rows > 0) {
            $idProductoPadreFinal = $idProductoPrincipal;
            $stmtPadre->close();
        } else {
            // Caso B: es pack
            $stmtPack = $con->prepare("
                SELECT idproductopadre 
                FROM asociarproductos 
                WHERE idproductopack = ? 
                LIMIT 1
            ");
            $stmtPack->bind_param("i", $idProductoPrincipal);
            $stmtPack->execute();
            $stmtPack->store_result();

            if ($stmtPack->num_rows > 0) {
                $stmtPack->bind_result($idProductoPadreFinal);
                $stmtPack->fetch();
                $stmtPack->close();
            }
        }

        /* =====================================================
           5️ FUNCIÓN PARA DUPLICAR RELACIONES
        ===================================================== */
        function duplicarRelacion($con, $tabla, $idProductoOrigen, $idProductoNuevo)
        {
            $res = $con->query("SELECT * FROM $tabla WHERE idproducto = $idProductoOrigen");
            while ($row = $res->fetch_assoc()) {
                unset($row['id']);
                $row['idproducto'] = $idProductoNuevo;

                $cols = array_keys($row);
                $vals = array_values($row);
                $ph = implode(',', array_fill(0, count($cols), '?'));
                $types = str_repeat('s', count($vals));

                $stmt = $con->prepare(
                    "INSERT INTO $tabla (" . implode(',', $cols) . ") VALUES ($ph)"
                );
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
            }
        }

        /* =====================================================
           6️ DUPLICAR POR CADA TALLA
        ===================================================== */
        foreach ($tallas as $talla) {

            
            $producto['talla'] = $talla;
            $tipos = str_repeat('s', count($producto));
            $valores = array_values($producto);

            $stmtInsertProducto->bind_param($tipos, ...$valores);
            $stmtInsertProducto->execute();

            $idProductoTalla = $con->insert_id;

            $stmtInsertAsociarTallas->bind_param(
                "iis",
                $idProductoPrincipal,
                $idProductoTalla,
                $talla
            );
            $stmtInsertAsociarTallas->execute();

            duplicarRelacion($con, 'categoriasasociadasventa', $idProductoPrincipal, $idProductoTalla);
            duplicarRelacion($con, 'subcategoriasasociadasventa', $idProductoPrincipal, $idProductoTalla);
            duplicarRelacion($con, 'industriaasociadaventa', $idProductoPrincipal, $idProductoTalla);
            duplicarRelacion($con, 'mediosventa', $idProductoPrincipal, $idProductoTalla);

            if (!is_null($idProductoPadreFinal)) {
                $stmtInsertPack = $con->prepare("
                    INSERT INTO asociarproductos (idproductopadre, idproductopack, cantidadpack)
                    VALUES (?, ?, ?)
                ");
                $stmtInsertPack->bind_param(
                    "iii",
                    $idProductoPadreFinal,
                    $idProductoTalla,
                    $cantidadpack
                );
                $stmtInsertPack->execute();
            }
        }

        $con->commit();
        header('Location: carga-tienda-en-linea.php');
        exit;
    } catch (Exception $e) {
        $con->rollback();
        die('Error: ' . $e->getMessage());
    }
}