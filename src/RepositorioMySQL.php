<?php

final class RepositorioMySQL
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarProductos(): array
    {
        $consulta = $this->pdo->query(
            'SELECT id, nombre, precio, stock FROM producto ORDER BY nombre'
        );

        return $consulta->fetchAll();
    }

    public function obtenerProducto(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, nombre, precio, stock FROM producto WHERE id = ?'
        );
        $consulta->execute([$id]);
        $producto = $consulta->fetch();

        return $producto === false ? null : $producto;
    }

    public function registrarVenta(string $cliente, array $items): int
    {
        if (empty($items['lineas'])) {
            throw new RuntimeException('La venta debe incluir al menos un producto.');
        }

        $this->pdo->beginTransaction();

        try {
            $productos = [];
            $cantidades = [];
            $ids = [];

            foreach ($items['lineas'] as $linea) {
                $productoId = (int) $linea['producto_id'];
                $ids[$productoId] = $productoId;
                $cantidades[$productoId] = ($cantidades[$productoId] ?? 0) + (int) $linea['cantidad'];
            }

            sort($ids, SORT_NUMERIC);
            $bloquearProducto = $this->pdo->prepare(
                'SELECT id, nombre, precio, stock FROM producto WHERE id = ? FOR UPDATE'
            );

            foreach ($ids as $productoId) {
                $bloquearProducto->execute([$productoId]);
                $producto = $bloquearProducto->fetch();

                if ($producto === false) {
                    throw new RuntimeException('Uno de los productos seleccionados ya no existe.');
                }

                if (!ServicioVenta::hayStock($cantidades[$productoId], (int) $producto['stock'])) {
                    throw new RuntimeException(
                        'Stock insuficiente para el producto «' . $producto['nombre'] . '».'
                    );
                }

                $productos[$productoId] = $producto;
            }

            foreach ($items['lineas'] as $linea) {
                $producto = $productos[(int) $linea['producto_id']];

                if (round((float) $producto['precio'], 2) !== round((float) $linea['precio_unitario'], 2)) {
                    throw new RuntimeException(
                        'El precio de «' . $producto['nombre'] . '» cambió. Vuelva a intentar la venta.'
                    );
                }
            }

            $insertarVenta = $this->pdo->prepare(
                'INSERT INTO venta (cliente, subtotal, descuento, total) VALUES (?, ?, ?, ?)'
            );
            $insertarVenta->execute([
                $cliente,
                $items['subtotal'],
                $items['descuento'],
                $items['total'],
            ]);
            $ventaId = (int) $this->pdo->lastInsertId();

            $insertarDetalle = $this->pdo->prepare(
                'INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, importe)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $actualizarStock = $this->pdo->prepare(
                'UPDATE producto SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );

            foreach ($items['lineas'] as $linea) {
                $productoId = (int) $linea['producto_id'];
                $cantidad = (int) $linea['cantidad'];
                $precio = (float) $productos[$productoId]['precio'];

                $insertarDetalle->execute([
                    $ventaId,
                    $productoId,
                    $cantidad,
                    $precio,
                    ServicioVenta::calcularImporte($precio, $cantidad),
                ]);
                $actualizarStock->execute([$cantidad, $productoId, $cantidad]);

                if ($actualizarStock->rowCount() !== 1) {
                    throw new RuntimeException(
                        'No se pudo actualizar el stock de «' . $productos[$productoId]['nombre'] . '».'
                    );
                }
            }

            $this->pdo->commit();

            return $ventaId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
