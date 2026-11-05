<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/ServicioVenta.php';

final class ServicioVentaTest extends TestCase
{
    public function testRechazaCantidadDecimal(): void
    {
        $this->assertFalse(ServicioVenta::validarCantidad(1.5));
    }

    public function testRechazaCantidadCero(): void
    {
        $this->assertSame(false, ServicioVenta::validarCantidad(0));
    }

    public function testRechazaCantidadNegativa(): void
    {
        $this->assertSame(false, ServicioVenta::validarCantidad(-1));
    }

    public function testAceptaCantidadPositiva(): void
    {
        $this->assertSame(true, ServicioVenta::validarCantidad(1));
    }

    public function testAceptaVentaPorDebajoDelStock(): void
    {
        $this->assertSame(true, ServicioVenta::hayStock(4, 5));
    }

    public function testAceptaVentaIgualAlStock(): void
    {
        $this->assertSame(true, ServicioVenta::hayStock(5, 5));
    }

    public function testRechazaVentaPorEncimaDelStock(): void
    {
        $this->assertSame(false, ServicioVenta::hayStock(6, 5));
    }

    public function testNoAplicaDescuentoConSubtotalDebajoDeCien(): void
    {
        $this->assertEqualsWithDelta(0.0, ServicioVenta::calcularDescuento(99.99), 0.001);
    }

    public function testNoAplicaDescuentoConSubtotalIgualACien(): void
    {
        $this->assertEqualsWithDelta(0.0, ServicioVenta::calcularDescuento(100.00), 0.001);
    }

    public function testAplicaDescuentoConSubtotalSobreCien(): void
    {
        $this->assertEqualsWithDelta(5.00, ServicioVenta::calcularDescuento(100.01), 0.001);
    }
}