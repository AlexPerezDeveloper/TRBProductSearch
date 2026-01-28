# PRD – TRB Product Search

## 1. Visión General

Desarrollar un **plugin básico para WordPress** que añada un **buscador de productos para WooCommerce**, enfocado en simplicidad, rendimiento aceptable y extensibilidad futura. Incluye funcionalidad de búsqueda en tiempo real (AJAX) con resultados desplegables, búsqueda por SKU, búsqueda por atributos, sinónimos y corrección de errores tipográficos.

El objetivo es permitir a los usuarios finales encontrar productos de forma rápida mediante múltiples criterios (título, descripción, SKU, atributos), dejando una base sólida y extensible para futuras mejoras.

---

## 2. Objetivos del Producto

### Objetivo principal

* Permitir la búsqueda de productos de WooCommerce por múltiples criterios: título, descripción, SKU y atributos.

### Objetivos secundarios

* Integración nativa y limpia con WordPress y WooCommerce.
* Código modular y extensible.
* Configuración flexible desde panel de administración.
* Compatible con la mayoría de themes estándar de WooCommerce.
* Sistema de sinónimos para mejorar la relevancia de búsquedas.
* Corrección automática de errores tipográficos.

### Fuera de alcance (v1)

* Filtros avanzados (precio, categorías múltiples).
* Búsqueda semántica o por relevancia avanzada.
* Autocompletado inteligente con suggest.
* Indexación externa (Elastic, Algolia, etc.).
* Búsqueda en variaciones de productos (búsqueda limitada a producto principal).

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
   * **[IMPLEMENTADO]** Búsqueda en tiempo real al escribir (mínimo 3 caracteres).
   * **[IMPLEMENTADO]** Resultados mostrados en un desplegable bajo el input.

2. **Resultados de búsqueda**

   * Listado de productos WooCommerce.
   * Información mínima:

     * Imagen destacada
     * Nombre del producto
     * Precio
     * Enlace al producto

3. **Búsqueda por SKU**

   * **[IMPLEMENTADO]** Opción configurable para habilitar/deshabilitar búsqueda por SKU.
   * Búsqueda parcial (LIKE) y búsqueda exacta para priorización.
   * Coincidencias exactas de SKU aparecen primero en resultados.

4. **Búsqueda por Atributos**

   * **[IMPLEMENTADO]** Opción configurable para habilitar/deshabilitar búsqueda por atributos.
   * Selección dinámica de atributos globales de WooCommerce (pa_*).
   * Búsqueda en múltiples atributos simultáneamente (lógica OR).
   * Atributos soportados: color, talla, marca, material, etc.

5. **Sistema de Sinónimos**

   * **[IMPLEMENTADO]** Configuración de grupos de sinónimos.
   * Un grupo por línea, términos separados por comas.
   * Ejemplo: `sneakers, trainers, running shoes`

6. **Corrección de Errores Tipográficos**

   * **[IMPLEMENTADO]** Índice automático de palabras de productos.
   * Sugerencias para búsquedas mal escritas.
   * Indexación de títulos, SKUs y atributos.

7. **Shortcode**

   * Shortcode para insertar el buscador en cualquier página o widget.
   * Ejemplo: `[trb_product_search]`

8. **Compatibilidad WooCommerce**

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
3. Si supera los 3 caracteres, se lanza una petición AJAX.
4. Se muestran los resultados en un desplegable.
5. El usuario hace clic en un producto y accede a su ficha.
6. Alternativamente, envía el formulario para ver resultados en página completa.

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
│   ├── class-plugin-init.php      # Inicialización del plugin
│   ├── class-search-form.php      # Formulario de búsqueda
│   ├── class-search-query.php     # Motor de búsqueda principal
│   ├── class-search-results.php   # Renderizado de resultados
│   ├── class-ajax-handler.php     # Manejo de peticiones AJAX
│   ├── class-settings.php         # Configuración del plugin
│   ├── class-typo-corrector.php   # Corrección de errores tipográficos
│   ├── class-sku-search.php       # Búsqueda por SKU
│   └── class-attributes-search.php # Búsqueda por atributos
├── templates/
│   └── results.php                # Plantilla de resultados
└── tests/
    ├── integration/               # Tests de integración
    │   ├── SearchQueryTest.php
    │   ├── SkuSearchTest.php
    │   ├── AttributesSearchTest.php
    │   └── SearchSettingsTest.php
    └── helpers.php                # Helpers para tests
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

#### 6.2.3 Motor de búsqueda (Search_Query)

* Consulta de productos basada en múltiples criterios:
  * Título y contenido (siempre)
  * **SKU** (opcional, vía meta_query)
  * **Atributos** (opcional, vía tax_query)
* Soporte para sinónimos con lógica OR.
* Priorización de resultados (SKU exacto > título > atributos).
* Soporte para paginación básica.

#### 6.2.4 Búsqueda por SKU (SKU_Search)

* Configuración habilitable/deshabilitable.
* Búsqueda parcial con `LIKE` en campo `_sku`.
* Búsqueda exacta para priorización en resultados.
* Integración con meta_query de WP_Query.

#### 6.2.5 Búsqueda por Atributos (Attributes_Search)

* Configuración habilitable/deshabilitable.
* Detección dinámica de atributos globales de WooCommerce.
* Selección de atributos específicos para buscar.
* Integración con tax_query de WP_Query.

#### 6.2.6 Corrección de errores tipográficos (Typo_Corrector)

* Indexación automática de palabras de productos.
* Indexación de títulos, SKUs y atributos.
* Algoritmo de distancia de Levenshtein para sugerencias.
* Reconstrucción de índice al guardar productos.

#### 6.2.7 Configuración (Settings)

* Panel de opciones en Ajustes > TRB Search.
* Configuración de sinónimos.
* Habilitar/deshabilitar búsqueda por SKU.
* Habilitar/deshabilitar búsqueda por atributos.
* Selección de atributos específicos.

#### 6.2.8 Resultados

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

## 12. Extensibilidad Futura (Roadmap)

### v1.1 - Implementado (Issue #5)

*   **[COMPLETADO]** Búsqueda por SKU configurable.
*   **[COMPLETADO]** Búsqueda por atributos de producto.
*   **[COMPLETADO]** Priorización de resultados por SKU exacto.
*   **[COMPLETADO]** Panel de configuración extendido.
*   **[COMPLETADO]** Tests de integración completos.

### v1.0 - Implementado

*   **[COMPLETADO]** Búsqueda en tiempo real con AJAX.
*   **[COMPLETADO]** Soporte para sinónimos.
*   **[COMPLETADO]** Corrección básica de errores tipográficos.
*   **[COMPLETADO]** Debounce en inputs (500ms).

### Próximas versiones (v1.2+)

1.  **Skeleton loaders**: Mejorar la experiencia visual mientras cargan los resultados.
2.  **Caché de resultados**: Almacenar búsquedas frecuentes para mejorar velocidad.
3.  **Lazy loading de imágenes**: Carga diferida de imágenes en resultados.
4.  **Búsqueda en variaciones**: Extender búsqueda a variaciones de producto.
5.  **Analytics**: Tracking de búsquedas realizadas (sin datos personales).
6.  **Resultados destacados**: Posibilidad de fijar productos en resultados.
7.  **Historial de búsquedas**: Mostrar búsquedas recientes al usuario.
8.  **Filtros por precio**: Rango de precios en búsqueda.

---

## 13. Testing

### Tests de Integración (PHPUnit)

* **SkuSearchTest** (12 tests)
  * Verificación de habilitación/deshabilitación
  * Pruebas de meta_query
  * Búsqueda exacta y parcial de SKU
  * Manejo de caracteres especiales

* **AttributesSearchTest** (17 tests)
  * Verificación de habilitación/deshabilitación
  * Pruebas de tax_query
  * Selección de atributos múltiples
  * Detección de atributos de WooCommerce

* **SearchSettingsTest** (23 tests)
  * Configuración de opciones SKU y atributos
  * Sanitización de inputs
  * Persistencia de configuración

* **SearchQueryTest** (existente, extendido)
  * Integración con sinónimos
  * Priorización de resultados

### Pruebas manuales

* WooCommerce activo/inactivo
* Catálogo pequeño/grande
* Themes distintos
* Compatibilidad con últimas versiones de WP y WooCommerce.

---

## 14. Métricas de Éxito

* Búsquedas exitosas (título, SKU, atributos).
* Tiempo de respuesta aceptable (< 500ms).
* Ausencia de errores PHP.
* Cobertura de tests > 80% en nuevas funcionalidades.
* Compatibilidad con WooCommerce 3.0+ y WordPress 5.0+.

---

## 15. Riesgos y Consideraciones

* Rendimiento en catálogos grandes.
* Conflictos con themes muy personalizados.
* Limitaciones del buscador nativo de WordPress.

---

## 16. Preguntas Abiertas

(Se completará tras definición con el stakeholder)
