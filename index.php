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
    $error = 'No se pudo cargar el inventario. Verifique la conexión con la base de datos.';
}

$stockTotal = array_sum(array_map(
    static fn (array $producto): int => (int) $producto['stock'],
    $productos
));
$productosSinStock = count(array_filter(
    $productos,
    static fn (array $producto): bool => (int) $producto['stock'] === 0
));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario | Bodega Doña Rosa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/estilo.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-cloud">
    <div class="container">
        <a class="navbar-brand" href="index.php">☁️ Bodega Doña Rosa</a>
        <a class="btn btn-outline-success" href="venta.php">Registrar venta</a>
    </div>
</nav>
<main class="container py-4 py-lg-5">
    <div class="mb-4">
        <p class="text-uppercase small fw-semibold mb-1">Un rinconcito dulce y ordenado</p>
        <h1 class="h3 mb-0">Inventario y stock</h1>
    </div>
    <?php if ($error !== null): ?>
        <div class="alert alert-danger" role="alert">💗 <?= escapar($error) ?></div>
    <?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <article class="card summary-card">
                    <div class="card-body">
                        <div class="summary-label">Productos</div>
                        <p class="summary-value"><?= count($productos) ?></p>
                    </div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="card summary-card">
                    <div class="card-body">
                        <div class="summary-label">Stock total</div>
                        <p class="summary-value"><?= $stockTotal ?></p>
                    </div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="card summary-card">
                    <div class="card-body">
                        <div class="summary-label">Sin stock</div>
                        <p class="summary-value"><?= $productosSinStock ?></p>
                    </div>
                </article>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <label for="buscar-producto" class="form-label fw-semibold">Buscar producto</label>
                <input type="search" class="form-control search-box" id="buscar-producto"
                       placeholder="Escribe el nombre de un producto..." autocomplete="off">
            </div>
        </div>
        <div class="table-shell table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr><th>Producto</th><th>Precio</th><th>Stock actual</th></tr>
                </thead>
                <tbody id="productos-lista">
                <?php foreach ($productos as $producto): ?>
                    <tr data-nombre="<?= escapar((string) $producto['nombre']) ?>">
                        <td><?= escapar((string) $producto['nombre']) ?></td>
                        <td>S/ <?= number_format((float) $producto['precio'], 2, '.', ',') ?></td>
                        <td>
                            <?php
                            $stock = (int) $producto['stock'];
                            $claseStock = $stock === 0 ? 'stock-empty' : ($stock <= 5 ? 'stock-low' : 'stock-ok');
                            ?>
                            <span class="stock-badge <?= $claseStock ?>"><?= $stock ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($productos === []): ?>
                    <tr class="empty-message"><td colspan="3" class="text-center py-4">Aún no hay productos registrados.</td></tr>
                <?php endif; ?>
                <tr id="sin-resultados" class="empty-results">
                    <td colspan="3" class="text-center py-4">No encontramos productos con ese nombre ☁️</td>
                </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<script>
(() => {
    const buscador = document.getElementById('buscar-producto');
    const filas = [...document.querySelectorAll('#productos-lista tr[data-nombre]')];
    const aviso = document.getElementById('sin-resultados');

    buscador?.addEventListener('input', () => {
        const termino = buscador.value.trim().toLocaleLowerCase();
        let visibles = 0;

        filas.forEach((fila) => {
            const coincide = fila.dataset.nombre.toLocaleLowerCase().includes(termino);
            fila.hidden = !coincide;
            visibles += coincide ? 1 : 0;
        });

        aviso.hidden = termino === '' || visibles > 0;
        aviso.classList.toggle('empty-results', termino === '' || visibles > 0);
    });
})();
</script>
</body>
</html>
