<?php
/**
 * Test Products Data
 *
 * This file contains test product data that can be used to:
 * 1. Populate a test WordPress/WooCommerce site
 * 2. Verify search functionality works correctly
 *
 * @package TRB_Product_Search\Tests
 */

return [
    // === Clothing Products (for testing "cami" -> "camisetas") ===
    [
        'name' => 'Camiseta Básica Algodón',
        'sku' => 'CAMI-001',
        'price' => '15.99',
        'description' => 'Camiseta de algodón 100% transpirable y cómoda.',
        'attributes' => ['color' => 'Blanco', 'talla' => 'M'],
        'categories' => ['ropa', 'camisetas'],
    ],
    [
        'name' => 'Camiseta Estampada Diseño Urbano',
        'sku' => 'CAMI-002',
        'price' => '19.99',
        'description' => 'Camiseta con estampado original de diseño urbano.',
        'attributes' => ['color' => 'Negro', 'talla' => 'L'],
        'categories' => ['ropa', 'camisetas'],
    ],
    [
        'name' => 'Camiseta Deportiva Running',
        'sku' => 'CAMI-DEPORT-01',
        'price' => '24.99',
        'description' => 'Camiseta técnica para running, transpirable y ligera.',
        'attributes' => ['color' => 'Azul', 'talla' => 'S'],
        'categories' => ['ropa', 'deportiva'],
    ],
    [
        'name' => 'Camiseta Manga Larga Invierno',
        'sku' => 'CAMI-LARGA-001',
        'price' => '29.99',
        'description' => 'Camiseta de manga larga para invierno, térmica.',
        'attributes' => ['color' => 'Gris', 'talla' => 'XL'],
        'categories' => ['ropa', 'invierno'],
    ],
    [
        'name' => 'Camisa Formal Blanca',
        'sku' => 'CAMI-FORMAL-01',
        'price' => '39.99',
        'description' => 'Camisa formal de vestir blanca, elegante para oficina.',
        'attributes' => ['color' => 'Blanco', 'talla' => 'M'],
        'categories' => ['ropa', 'formal'],
    ],

    // === Electronics (for testing "hdmi" searches) ===
    [
        'name' => 'Cable HDMI 2.1 2 metros',
        'sku' => 'HDMI-2M-001',
        'price' => '9.99',
        'description' => 'Cable HDMI 2.1 de alta velocidad para 4K@120Hz.',
        'attributes' => ['color' => 'Negro', 'longitud' => '2m'],
        'categories' => ['electronica', 'cables'],
    ],
    [
        'name' => 'Cable HDMI 4K 1.5 metros',
        'sku' => 'HDMI-4K-150',
        'price' => '7.99',
        'description' => 'Cable HDMI compatible 4K HDR, 1.5 metros de longitud.',
        'attributes' => ['color' => 'Negro', 'longitud' => '1.5m'],
        'categories' => ['electronica', 'cables'],
    ],
    [
        'name' => 'Adaptador HDMI a VGA',
        'sku' => 'HDMI-VGA-ADAPTER',
        'price' => '14.99',
        'description' => 'Adaptador de HDMI a VGA para conectar monitores antiguos.',
        'attributes' => ['color' => 'Negro', 'tipo' => 'adaptador'],
        'categories' => ['electronica', 'adaptadores'],
    ],

    // === More Electronics ===
    [
        'name' => 'Auriculares Bluetooth Inalámbricos',
        'sku' => 'AUDIO-BT-001',
        'price' => '49.99',
        'description' => 'Auriculares Bluetooth con cancelación de ruido activa.',
        'attributes' => ['color' => 'Negro', 'tipo' => 'inalambrico'],
        'categories' => ['electronica', 'audio'],
    ],
    [
        'name' => 'Cargador USB-C Rápido 20W',
        'sku' => 'CARG-USB-20W',
        'price' => '12.99',
        'description' => 'Cargador rápido USB-C 20W compatible con iPhone y Android.',
        'attributes' => ['color' => 'Blanco', 'potencia' => '20W'],
        'categories' => ['electronica', 'cargadores'],
    ],
    [
        'name' => 'Teclado Mecánico Gaming RGB',
        'sku' => 'TECL-MEC-GAMING',
        'price' => '79.99',
        'description' => 'Teclado mecánico gaming con retroiluminación RGB programable.',
        'attributes' => ['color' => 'Negro', 'tipo' => 'mecanico'],
        'categories' => ['electronica', 'perifericos'],
    ],

    // === Accessories ===
    [
        'name' => 'Mochila Portátil Impermeable',
        'sku' => 'MOCH-LAP-15',
        'price' => '34.99',
        'description' => 'Mochila para portátil hasta 15.6 pulgadas, impermeable.',
        'attributes' => ['color' => 'Gris', 'tamano' => '15.6"'],
        'categories' => ['accesorios', 'mochilas'],
    ],
    [
        'name' => 'Funda Móvil Silicona TPU',
        'sku' => 'FUNDA-SIL-001',
        'price' => '5.99',
        'description' => 'Funda de silicona TPU flexible para móvil.',
        'attributes' => ['color' => 'Transparente', 'material' => 'silicona'],
        'categories' => ['accesorios', 'fundas'],
    ],

    // === Shoes (for testing "zapa" -> "zapatillas") ===
    [
        'name' => 'Zapatillas Deportivas Running',
        'sku' => 'ZAPA-RUN-001',
        'price' => '59.99',
        'description' => 'Zapatillas running con amortiguación profesional.',
        'attributes' => ['color' => 'Rojo', 'talla' => '42'],
        'categories' => ['calzado', 'deportivo'],
    ],
    [
        'name' => 'Zapatillas Urbanas Casual',
        'sku' => 'ZAPA-URB-001',
        'price' => '44.99',
        'description' => 'Zapatillas urbanas cómodas para el día a día.',
        'attributes' => ['color' => 'Blanco', 'talla' => '41'],
        'categories' => ['calzado', 'urbano'],
    ],
    [
        'name' => 'Zapatillas Skate Cordones',
        'sku' => 'ZAPA-SKATE-01',
        'price' => '49.99',
        'description' => 'Zapatillas para skate con suela resistente al desgaste.',
        'attributes' => ['color' => 'Negro', 'talla' => '43'],
        'categories' => ['calzado', 'skate'],
    ],

    // === Home & Kitchen ===
    [
        'name' => 'Sartén Antiadherente 24cm',
        'sku' => 'SART-ANTI-24',
        'price' => '19.99',
        'description' => 'Sartén antiadherente de 24cm, apta inducción.',
        'attributes' => ['color' => 'Negro', 'tamano' => '24cm'],
        'categories' => ['hogar', 'cocina'],
    ],
    [
        'name' => 'Set Cuchillos Cocina Acero',
        'sku' => 'CUCH-SET-ACERO',
        'price' => '29.99',
        'description' => 'Set de 6 cuchillos de cocina acero inoxidable.',
        'attributes' => ['material' => 'Acero', 'piezas' => '6'],
        'categories' => ['hogar', 'cocina'],
    ],
    [
        'name' => 'Lámpara LED Mesa Escritorio',
        'sku' => 'LAMP-LED-MESA',
        'price' => '24.99',
        'description' => 'Lámpara LED de mesa para escritorio con brazo flexible.',
        'attributes' => ['color' => 'Blanco', 'tipo' => 'LED'],
        'categories' => ['hogar', 'iluminacion'],
    ],

    // === Products with specific attributes for testing attribute search ===
    [
        'name' => 'Camiseta Roja Talla L',
        'sku' => 'PROD-RED-L',
        'price' => '18.99',
        'description' => 'Camiseta roja talla grande.',
        'attributes' => ['color' => 'Rojo', 'talla' => 'L'],
        'categories' => ['ropa'],
    ],
    [
        'name' => 'Camiseta Azul Talla S',
        'sku' => 'PROD-BLU-S',
        'price' => '16.99',
        'description' => 'Camiseta azul talla pequeña.',
        'attributes' => ['color' => 'Azul', 'talla' => 'S'],
        'categories' => ['ropa'],
    ],
    [
        'name' => 'Camiseta Verde Talla M',
        'sku' => 'PROD-GRN-M',
        'price' => '17.99',
        'description' => 'Camiseta verde talla mediana.',
        'attributes' => ['color' => 'Verde', 'talla' => 'M'],
        'categories' => ['ropa'],
    ],

    // === Products for testing synonym functionality ===
    [
        'name' => 'Coche Eléctrico Juguete',
        'sku' => 'JUG-COCHE-001',
        'price' => '29.99',
        'description' => 'Coche eléctrico a control remoto para niños.',
        'attributes' => ['color' => 'Rojo', 'edad' => '3+'],
        'categories' => ['juguetes'],
    ],
    [
        'name' => 'Vehículo Automóvil Escala 1:24',
        'sku' => 'JUG-AUTO-024',
        'price' => '14.99',
        'description' => 'Modelo a escala de automóvil coleccionable.',
        'attributes' => ['color' => 'Azul', 'escala' => '1:24'],
        'categories' => ['juguetes', 'colección'],
    ],
    [
        'name' => 'Portátil Notebook 15.6"',
        'sku' => 'LAPTOP-15-6',
        'price' => '599.99',
        'description' => 'Ordenador portátil con pantalla Full HD 15.6 pulgadas.',
        'attributes' => ['color' => 'Plateado', 'ram' => '8GB'],
        'categories' => ['informatica', 'portatiles'],
    ],
    [
        'name' => 'Ordenador Portátil Gaming',
        'sku' => 'LAPTOP-GAM-01',
        'price' => '899.99',
        'description' => 'Portátil gaming con tarjeta gráfica dedicada.',
        'attributes' => ['color' => 'Negro', 'ram' => '16GB'],
        'categories' => ['informatica', 'gaming'],
    ],
];
