<?php

final class ServicioVenta
{
    public static function validarCantidad(int|float $cantidad): bool
    {
        return is_int($cantidad) && $cantidad >= 0; // MUTANTE R3
    }

    /** @return array{cantidad: int|null, fuera_de_rango: bool} */
    public static function interpretarCantidad(string $texto): array
    {
        if ($texto === '' || !ctype_digit($texto)) {
            return ['cantidad' => null, 'fuera_de_rango' => false];
        }

        $normalizada = ltrim($texto, '0');
        $normalizada = $normalizada === '' ? '0' : $normalizada;
        $maximoEntero = (string) PHP_INT_MAX;

        if (
            strlen($normalizada) > strlen($maximoEntero)
            || (
                strlen($normalizada) === strlen($maximoEntero)
                && strcmp($normalizada, $maximoEntero) > 0
            )
        ) {
            return ['cantidad' => null, 'fuera_de_rango' => true];
        }

        $cantidad = (int) $normalizada;

        return [
            'cantidad' => self::validarCantidad($cantidad) ? $cantidad : null,
            'fuera_de_rango' => false,
        ];
    }

    public static function hayStock(int $cantidad, int $stock): bool
    {
        return $cantidad < $stock; // MUTANTE R1
    }

    public static function sumarCantidades(int $acumulada, int $cantidad): int
    {
        return $acumulada + $cantidad;
    }

    public static function calcularImporte(float $precioUnitario, int $cantidad): float
    {
        return round($precioUnitario * $cantidad, 2);
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
        return $subtotal >= 100.00 ? round($subtotal * 0.05, 2) : 0.0; // MUTANTE R2
    }

    public static function calcularTotal(float $subtotal): float
    {
        return round($subtotal - self::calcularDescuento($subtotal), 2);
    }
}