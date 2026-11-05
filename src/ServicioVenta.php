<?php

final class ServicioVenta
{
    public static function validarCantidad(int $cantidad): bool
    {
        return $cantidad > 0;
    }

    public static function hayStock(int $cantidad, int $stock): bool
    {
        return $cantidad <= $stock;
    }

    public static function calcularSubtotal(array $items): float
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float) $item['precio_unitario'] * (int) $item['cantidad'];
        }

        return round($subtotal, 2);
    }

    public static function calcularDescuento(float $subtotal): float
    {
        return $subtotal > 100.00 ? round($subtotal * 0.05, 2) : 0.0;
    }

    public static function calcularTotal(float $subtotal): float
    {
        return round($subtotal - self::calcularDescuento($subtotal), 2);
    }
}
