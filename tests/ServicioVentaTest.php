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
