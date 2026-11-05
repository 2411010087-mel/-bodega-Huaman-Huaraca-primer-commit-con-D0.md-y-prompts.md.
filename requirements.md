# Requirements Document

## Introduction

El Sistema Bodega es una aplicación de registro de ventas para la bodega de Doña Rosa en San Vicente de Cañete. Reemplaza un cuaderno de papel con un sistema digital que registra productos, registra ventas, descuenta stock y aplica descuentos por volumen automáticamente. Esta versión excluye autenticación, reportes y facturación electrónica.

## Glossary

- **Bodega_System**: La aplicación de registro de ventas que gestiona productos, ventas y stock para la bodega de Doña Rosa.
- **Producto**: Un artículo vendido en la bodega, identificado por nombre, precio unitario y cantidad de stock disponible.
- **Venta**: Una transacción que registra la venta de una cantidad de un producto a un cliente.
- **Stock**: La cantidad disponible de un producto disponible para la venta.
- **Subtotal**: El resultado de sumar el precio unitario multiplicado por la cantidad de cada línea de la venta, antes de aplicar cualquier descuento.
- **Descuento**: Una reducción porcentual del 5% aplicada sobre el subtotal cuando el subtotal supera S/ 100.00.

## Requirements

### Requirement 1: Registrar Productos

**User Story:** Como dueña de la bodega, quiero registrar productos con su stock inicial, para mantener un inventario de lo que vendo.

#### Acceptance Criteria

1. WHEN se envía un nuevo producto con un nombre, un precio unitario y un stock inicial, THE Bodega_System SHALL registrar el producto y almacenar los datos del producto.
2. IF se envía un producto con un nombre vacío o un precio unitario menor a cero, THEN THE Bodega_System SHALL rechazar el registro del producto y reportar el error.

### Requirement 2: Registrar Ventas

**User Story:** Como dueña de la bodega, quiero registrar ventas de productos, para llevar un control de lo que vendo a cada cliente.

#### Acceptance Criteria

1. WHEN se envía una venta con una o más líneas, cada una con un producto y una cantidad, THE Bodega_System SHALL registrar la venta y calcular el subtotal como la suma del precio unitario multiplicado por la cantidad de cada línea.
2. IF la cantidad es cero, negativa o un valor decimal, THEN THE Bodega_System SHALL rechazar la venta y reportar el error [R3].
3. IF la cantidad es mayor que el stock disponible del producto, THEN THE Bodega_System SHALL rechazar la venta y reportar el error [R1].
4. WHEN una venta tiene una cantidad igual al stock disponible, THE Bodega_System SHALL aceptar la venta y establecer el stock del producto en cero [R1].
5. WHEN se confirma una venta, THE Bodega_System SHALL reducir el stock de cada producto por la cantidad vendida dentro de la misma transacción de base de datos [R4].

### Requirement 3: Ver Stock Restante

**User Story:** Como dueña de la bodega, quiero ver el stock restante de los productos, para saber cuándo reabastecer.

#### Acceptance Criteria

1. WHEN la dueña de la bodega solicita la lista de stock, THE Bodega_System SHALL mostrar cada producto con el nombre del producto y el stock actual.
2. WHILE un producto tiene un stock igual a cero, THE Bodega_System SHALL mostrar el producto con un valor de stock de cero.

### Requirement 4: Aplicar Descuento por Volumen

**User Story:** Como dueña de la bodega, quiero que el sistema aplique descuentos por volumen automáticamente, para no cometer errores al calcular el descuento.

#### Acceptance Criteria

1. WHEN el subtotal de una venta es mayor a S/ 100.00, THE Bodega_System SHALL aplicar un descuento del 5% sobre el subtotal [R2].
2. WHEN el subtotal de una venta es exactamente S/ 100.00, THE Bodega_System SHALL establecer el descuento en cero [R2].
