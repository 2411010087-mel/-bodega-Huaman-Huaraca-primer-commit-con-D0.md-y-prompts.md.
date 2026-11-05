<?php
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/ServicioVenta.php';
require_once __DIR__ . '/src/RepositorioMySQL.php';

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$errores = [];
$cliente = $_POST['cliente'] ?? null;
$lineasRecibidas = $_POST['lineas'] ?? null;
$lineasValidadas = [];

if (!is_string($cliente) || trim($cliente) === '') {
    $errores[] = 'Ingrese el nombre del cliente.';
} elseif (mb_strlen(trim($cliente), 'UTF-8') > 80) {
    $errores[] = 'El nombre del cliente no puede superar 80 caracteres.';
} else {
    $cliente = trim($cliente);
}

if (!is_array($lineasRecibidas)) {
    $errores[] = 'Agregue al menos un producto a la venta.';
} else {
    foreach ($lineasRecibidas as $linea) {
        if (!is_array($linea)) {
            $errores[] = 'Una de las líneas de la venta no es válida.';
            continue;
        }

        $productoIdTexto = $linea['producto_id'] ?? '';
        $cantidadTexto = $linea['cantidad'] ?? '';

        if (
            (!is_string($productoIdTexto) && !is_int($productoIdTexto))
            || (!is_string($cantidadTexto) && !is_int($cantidadTexto))
        ) {
            $errores[] = 'El producto y la cantidad deben ser valores válidos.';
            continue;
        }

        $productoIdTexto = (string) $productoIdTexto;
        $cantidadTexto = (string) $cantidadTexto;

        if ($productoIdTexto === '' && $cantidadTexto === '') {
            continue;
        }

        if ($productoIdTexto === '' || !ctype_digit($productoIdTexto) || (int) $productoIdTexto < 1) {
            $errores[] = 'Seleccione un producto válido.';
            continue;
        }

        if ($cantidadTexto === '' || !ctype_digit($cantidadTexto)) {
            $errores[] = 'La cantidad debe ser un entero mayor que cero.';
            continue;
        }

        $cantidadNormalizada = ltrim($cantidadTexto, '0');
        $cantidadNormalizada = $cantidadNormalizada === '' ? '0' : $cantidadNormalizada;
        $maximoEntero = (string) PHP_INT_MAX;

        if (
            strlen($cantidadNormalizada) > strlen($maximoEntero)
            || (
                strlen($cantidadNormalizada) === strlen($maximoEntero)
                && strcmp($cantidadNormalizada, $maximoEntero) > 0
            )
        ) {
            $errores[] = 'La cantidad ingresada está fuera del rango permitido.';
            continue;
        }

        $cantidad = (int) $cantidadNormalizada;
        if (!ServicioVenta::validarCantidad($cantidad)) {
            $errores[] = 'La cantidad debe ser un entero mayor que cero.';
            continue;
        }

        $lineasValidadas[] = [
            'producto_id' => (int) $productoIdTexto,
            'cantidad' => $cantidad,
        ];
    }

    if ($lineasValidadas === [] && $errores === []) {
        $errores[] = 'Agregue al menos un producto a la venta.';
    }
}

$ventaId = null;
$resumen = null;
$errorBaseDatos = null;

if ($errores === []) {
    try {
        $repositorio = new RepositorioMySQL(conectar());
        $lineas = [];
        $cantidadesPorProducto = [];

        foreach ($lineasValidadas as $linea) {
            $producto = $repositorio->obtenerProducto($linea['producto_id']);

            if ($producto === null) {
                $errores[] = 'Uno de los productos seleccionados no existe.';
                continue;
            }

            $productoId = (int) $producto['id'];
            $cantidadTotal = ($cantidadesPorProducto[$productoId] ?? 0) + $linea['cantidad'];
            $cantidadesPorProducto[$productoId] = $cantidadTotal;

            if (!ServicioVenta::hayStock($cantidadTotal, (int) $producto['stock'])) {
                $errores[] = 'Stock insuficiente para el producto «' . $producto['nombre'] . '».';
                continue;
            }

            $precio = (float) $producto['precio'];
            $lineas[] = [
                'producto_id' => $productoId,
                'cantidad' => $linea['cantidad'],
                'precio_unitario' => $precio,
                'importe' => round($precio * $linea['cantidad'], 2),
            ];
        }

        if ($errores === []) {
            $subtotal = ServicioVenta::calcularSubtotal($lineas);
            $descuento = ServicioVenta::calcularDescuento($subtotal);
            $total = ServicioVenta::calcularTotal($subtotal);

            $ventaId = $repositorio->registrarVenta($cliente, [
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'lineas' => $lineas,
            ]);
            $resumen = compact('subtotal', 'descuento', 'total');
        }
    } catch (PDOException $exception) {
        $errorBaseDatos = 'No se pudo guardar la venta. Verifique la conexión con la base de datos e inténtelo de nuevo.';
    } catch (RuntimeException $exception) {
        $errores[] = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultado de venta | Bodega Doña Rosa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">Bodega Doña Rosa</a>
        <a class="btn btn-outline-light" href="index.php">Ver inventario</a>
    </div>
</nav>
<main class="container py-4">
    <?php if ($ventaId !== null && $resumen !== null): ?>
        <div class="alert alert-success" role="alert">
            <h1 class="h4">Venta registrada correctamente</h1>
            <p class="mb-1">Número de venta: <?= $ventaId ?></p>
            <p class="mb-1">Subtotal: S/ <?= number_format($resumen['subtotal'], 2, '.', ',') ?></p>
            <p class="mb-1">Descuento: S/ <?= number_format($resumen['descuento'], 2, '.', ',') ?></p>
            <p class="mb-0">Total: <strong>S/ <?= number_format($resumen['total'], 2, '.', ',') ?></strong></p>
        </div>
    <?php else: ?>
        <div class="alert alert-danger" role="alert">
            <h1 class="h4">No se pudo registrar la venta</h1>
            <?php if ($errorBaseDatos !== null): ?>
                <p class="mb-0"><?= escapar($errorBaseDatos) ?></p>
            <?php else: ?>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= escapar($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <a href="venta.php" class="btn btn-success">Volver a ventas</a>
    <a href="index.php" class="btn btn-outline-secondary">Ir al inventario</a>
</main>
</body>
</html>
