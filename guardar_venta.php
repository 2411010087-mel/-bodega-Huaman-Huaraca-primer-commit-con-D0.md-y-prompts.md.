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

        $resultadoCantidad = ServicioVenta::interpretarCantidad($cantidadTexto);
        if ($resultadoCantidad['fuera_de_rango']) {
            $errores[] = 'La cantidad ingresada está fuera del rango permitido.';
            continue;
        }

        $cantidad = $resultadoCantidad['cantidad'];
        if ($cantidad === null) {
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
            $cantidadesPorProducto[$productoId] = ServicioVenta::sumarCantidades(
                $cantidadesPorProducto[$productoId] ?? 0,
                $linea['cantidad']
            );

            if (!ServicioVenta::hayStock($cantidadesPorProducto[$productoId], (int) $producto['stock'])) {
                $errores[] = 'Stock insuficiente para el producto «' . $producto['nombre'] . '».';
                continue;
            }

            $precio = (float) $producto['precio'];
            $lineas[] = [
                'producto_id' => $productoId,
                'nombre' => (string) $producto['nombre'],
                'cantidad' => $linea['cantidad'],
                'precio_unitario' => $precio,
                'importe' => ServicioVenta::calcularImporte($precio, $linea['cantidad']),
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
    <link href="assets/estilo.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-cloud">
    <div class="container">
        <a class="navbar-brand" href="index.php">☁️ Bodega Doña Rosa</a>
        <a class="btn btn-outline-success" href="index.php">Ver inventario</a>
    </div>
</nav>
<main class="container py-4 py-lg-5">
    <?php if ($ventaId !== null && $resumen !== null): ?>
        <section class="card mx-auto" style="max-width: 760px" aria-labelledby="boleta-titulo">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="display-6" aria-hidden="true">☁️</div>
                    <p class="text-uppercase small fw-semibold mb-1">Bodega Doña Rosa</p>
                    <h1 class="h3 mb-2" id="boleta-titulo">✅ Venta registrada correctamente</h1>
                    <p class="mb-0">Boleta N.º <?= $ventaId ?></p>
                </div>
                <div class="rounded-3 p-3 mb-4" style="background-color: #f2f9ff">
                    <span class="text-secondary">Cliente</span>
                    <div class="fw-semibold"><?= escapar($cliente) ?></div>
                </div>
                <h2 class="h5 mb-3">Detalle de la compra</h2>
                <div class="table-shell table-responsive mb-4">
                    <table class="table align-middle">
                        <thead>
                        <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Importe</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lineas as $linea): ?>
                            <tr>
                                <td><?= escapar($linea['nombre']) ?></td>
                                <td><?= (int) $linea['cantidad'] ?></td>
                                <td>S/ <?= number_format((float) $linea['precio_unitario'], 2, '.', ',') ?></td>
                                <td>S/ <?= number_format((float) $linea['importe'], 2, '.', ',') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="ms-auto" style="max-width: 320px">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>S/ <?= number_format($resumen['subtotal'], 2, '.', ',') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Descuento</span>
                        <span>S/ <?= number_format($resumen['descuento'], 2, '.', ',') ?></span>
                    </div>
                    <div class="receipt-total d-flex justify-content-between pt-3">
                        <span>Total</span>
                        <span>S/ <?= number_format($resumen['total'], 2, '.', ',') ?></span>
                    </div>
                </div>
            </div>
        </section>
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary">Volver al inventario</a>
        </div>
    <?php else: ?>
        <div class="alert alert-danger" role="alert">
            <h1 class="h4">💗 No se pudo registrar la venta</h1>
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
    <?php if ($ventaId === null): ?>
        <a href="venta.php" class="btn btn-primary">Volver a ventas</a>
        <a href="index.php" class="btn btn-outline-secondary">Ir al inventario</a>
    <?php endif; ?>
</main>
</body>
</html>
