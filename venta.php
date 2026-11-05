<?php
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/src/RepositorioMySQL.php';

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$productos = [];
$error = null;

try {
    $repositorio = new RepositorioMySQL(conectar());
    $productos = $repositorio->listarProductos();
} catch (PDOException $exception) {
    $error = 'No se pudo cargar la lista de productos. Verifique la conexión con la base de datos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar venta | Bodega Doña Rosa</title>
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
    <h1 class="h3 mb-4">Registrar venta</h1>
    <?php if ($error !== null): ?>
        <div class="alert alert-danger" role="alert"><?= escapar($error) ?></div>
    <?php elseif ($productos === []): ?>
        <div class="alert alert-info" role="alert">No hay productos disponibles para agregar a una venta.</div>
    <?php else: ?>
        <form action="guardar_venta.php" method="post" class="card shadow-sm">
            <div class="card-body">
                <div class="mb-4">
                    <label for="cliente" class="form-label">Cliente</label>
                    <input type="text" class="form-control" id="cliente" name="cliente" maxlength="80" required>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Productos</h2>
                    <button type="button" class="btn btn-outline-success btn-sm" id="agregar-linea">
                        Agregar producto
                    </button>
                </div>
                <div id="lineas">
                    <div class="row g-2 align-items-end mb-3 linea">
                        <div class="col-md-8">
                            <label class="form-label">Producto</label>
                            <select class="form-select" name="lineas[0][producto_id]" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?= (int) $producto['id'] ?>"
                                        <?= (int) $producto['stock'] === 0 ? 'disabled' : '' ?>>
                                        <?= escapar((string) $producto['nombre']) ?>
                                        — S/ <?= number_format((float) $producto['precio'], 2, '.', ',') ?>
                                        (stock: <?= (int) $producto['stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" class="form-control" name="lineas[0][cantidad]"
                                   min="1" step="1" inputmode="numeric" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger quitar-linea" aria-label="Quitar línea"
                                    disabled>&times;</button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Guardar venta</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</main>
<template id="plantilla-linea">
    <div class="row g-2 align-items-end mb-3 linea">
        <div class="col-md-8">
            <label class="form-label">Producto</label>
            <select class="form-select" data-campo="producto_id" required>
                <option value="">Seleccione un producto</option>
                <?php foreach ($productos as $producto): ?>
                    <option value="<?= (int) $producto['id'] ?>"
                        <?= (int) $producto['stock'] === 0 ? 'disabled' : '' ?>>
                        <?= escapar((string) $producto['nombre']) ?>
                        — S/ <?= number_format((float) $producto['precio'], 2, '.', ',') ?>
                        (stock: <?= (int) $producto['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Cantidad</label>
            <input type="number" class="form-control" data-campo="cantidad"
                   min="1" step="1" inputmode="numeric" required>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger quitar-linea" aria-label="Quitar línea">&times;</button>
        </div>
    </div>
</template>
<script>
(() => {
    const contenedor = document.getElementById('lineas');
    const plantilla = document.getElementById('plantilla-linea');
    const actualizarBotones = () => {
        const lineas = contenedor.querySelectorAll('.linea');
        lineas.forEach((linea) => {
            linea.querySelector('.quitar-linea').disabled = lineas.length === 1;
        });
    };

    document.getElementById('agregar-linea')?.addEventListener('click', () => {
        const nuevaLinea = plantilla.content.firstElementChild.cloneNode(true);
        const indice = contenedor.querySelectorAll('.linea').length;
        nuevaLinea.querySelectorAll('[data-campo]').forEach((campo) => {
            campo.name = `lineas[${indice}][${campo.dataset.campo}]`;
        });
        contenedor.appendChild(nuevaLinea);
        actualizarBotones();
    });

    contenedor?.addEventListener('click', (evento) => {
        if (evento.target.matches('.quitar-linea')) {
            evento.target.closest('.linea').remove();
            actualizarBotones();
        }
    });
})();
</script>
</body>
</html>
