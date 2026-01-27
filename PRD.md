# PRD – TRB Product Search

## 1. Visión General

Desarrollar un **plugin básico para WordPress** que añada un **buscador de productos para WooCommerce**, enfocado en simplicidad, rendimiento aceptable y extensibilidad futura.

El objetivo inicial es permitir a los usuarios finales encontrar productos de forma rápida mediante un campo de búsqueda, sin funcionalidades avanzadas (filtros complejos, IA, etc.), dejando una base sólida para iterar en el futuro.

---

## 2. Objetivos del Producto

### Objetivo principal

* Permitir la búsqueda de productos de WooCommerce por texto (nombre y/o descripción).

### Objetivos secundarios

* Integración nativa y limpia con WordPress y WooCommerce.
* Código modular y extensible.
* Fácil instalación y configuración mínima.
* Compatible con la mayoría de themes estándar de WooCommerce.

### Fuera de alcance (v1)

* Filtros avanzados (precio, atributos, categorías múltiples).
* Búsqueda semántica o por relevancia avanzada.
* Autocompletado inteligente.
* Búsqueda por SKU configurable.
* Indexación externa (Elastic, Algolia, etc.).

---

## 3. Usuarios Objetivo

### Usuario principal

* **Clientes finales de tiendas online WooCommerce**.
* Usuarios que navegan el catálogo y necesitan encontrar productos de forma rápida y sencilla.

### Usuario secundario

* Administradores de tiendas WooCommerce (configuración e inserción del buscador).

---

## 4. Alcance Funcional (v1)

### 4.1 Funcionalidades principales

1. **Campo de búsqueda de productos**

   * Input de texto.
   * Envío manual (botón o enter).

2. **Resultados de búsqueda**

   * Listado de productos WooCommerce.
   * Información mínima:

     * Imagen destacada
     * Nombre del producto
     * Precio
     * Enlace al producto

3. **Shortcode**

   * Shortcode para insertar el buscador en cualquier página o widget.
   * Ejemplo: `[trb_product_search]`

4. **Compatibilidad WooCommerce**

   * Uso de `WP_Query` o `WC_Product_Query`.
   * Respeto al estado del producto (publicado, visible en catálogo).

---

## 5. Flujo del Plugin

### 5.1 Flujo de desarrollo

1. Creación del esqueleto del plugin
2. Registro y validación de dependencias (WooCommerce activo)
3. Registro de shortcodes
4. Renderizado del formulario de búsqueda
5. Procesamiento de la búsqueda
6. Renderizado de resultados
7. Estilos básicos
8. Pruebas funcionales

---

### 5.2 Flujo de usuario final

1. El usuario accede a una página con el buscador.
2. Introduce texto en el campo de búsqueda.
3. Envía el formulario.
4. El plugin procesa la búsqueda.
5. Se muestran los productos coincidentes.
6. El usuario hace clic en un producto y accede a su ficha.

---

## 6. Arquitectura del Plugin

### 6.1 Estructura de archivos sugerida

```
trb-product-search/
│
├── trb-product-search.php
├── readme.txt
├── assets/
│   ├── css/
│   │   └── search.css
│   └── js/
│       └── search.js
├── includes/
│   ├── class-plugin-init.php
│   ├── class-search-form.php
│   ├── class-search-query.php
│   └── class-search-results.php
└── templates/
    └── results.php
```

---

### 6.2 Componentes clave

#### 6.2.1 Inicialización del plugin

* Comprobación de WooCommerce activo.
* Carga de clases.
* Definición de constantes.

#### 6.2.2 Formulario de búsqueda

* Renderizado HTML del formulario.
* Uso de `GET` para facilitar indexación.
* Nonce básico para seguridad.

#### 6.2.3 Motor de búsqueda

* Consulta de productos basada en:

  * Título
  * Descripción corta y larga (opcional)
* Soporte para paginación básica.

#### 6.2.4 Resultados

* Plantilla desacoplada.
* Posibilidad de override por el theme (futuro).

---

## 7. Funciones Clave

* `register_shortcode()`
* `render_search_form()`
* `handle_search_request()`
* `get_products_by_search()`
* `render_search_form()`
* `handle_search_request()`
* `get_products_by_search()`
* `render_results()`

---

## 8. Seguridad

* Escape de inputs (`sanitize_text_field`).
* Escape de outputs (`esc_html`, `esc_url`).
* Nonce para formularios.
* Validación de parámetros.

---

## 9. Rendimiento

* Consultas optimizadas.
* Límite de resultados por página.
* Sin cargas innecesarias de JS/CSS si el shortcode no está presente.

---

## 10. UX / UI

* Diseño limpio y minimalista.
* Sin dependencia de frameworks externos.
* Estilos fácilmente sobrescribibles.

---

## 11. Configuración (v1 mínima)

* Sin panel de opciones complejo.
* Parámetros opcionales vía shortcode:

  * `posts_per_page`
  * `show_price`
  * `show_image`

---

## 12. Extensibilidad Futura

Funcionalidades previstas para futuras versiones:

* Autocompletado AJAX.
* Filtros por categoría, precio y atributos.
* Búsqueda por SKU.
* Ranking de relevancia.
* Caché de resultados.
* Integración con bloques Gutenberg.

---

## 13. Testing

* Pruebas manuales:

  * WooCommerce activo/inactivo
  * Catálogo pequeño/grande
  * Themes distintos
* Compatibilidad con últimas versiones de WP y WooCommerce.

---

## 14. Métricas de Éxito

* Búsquedas exitosas.
* Tiempo de respuesta aceptable.
* Ausencia de errores PHP.

---

## 15. Riesgos y Consideraciones

* Rendimiento en catálogos grandes.
* Conflictos con themes muy personalizados.
* Limitaciones del buscador nativo de WordPress.

---

## 16. Preguntas Abiertas

(Se completará tras definición con el stakeholder)
