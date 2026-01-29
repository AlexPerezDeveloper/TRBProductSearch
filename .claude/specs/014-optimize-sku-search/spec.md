# Spec: Optimize SKU Search Query

> **Issue**: #14 - Optimizar query de SKU_Search (2→1 query)
> **Version**: 1.0.0
> **Status**: Draft

---

## User Story

**As a** usuario del plugin de búsqueda
**I want** que las búsquedas por SKU sean más rápidas
**So that** la experiencia de búsqueda sea más fluida y el servidor tenga menos carga

---

## Stakeholders

| Role | Description | Impact |
|------|-------------|--------|
| **Primary** | Usuarios finales de la tienda | Búsquedas más rápidas por SKU |
| **Secondary** | Dueño de la tienda | Menor carga en servidor |
| **Tertiary** | Equipo de desarrollo | Código más maintainable |

---

## Current State Analysis

### Existing Implementation (lines 60-105)

El método `get_matching_product_ids()` ejecuta **2 queries secuenciales**:

```php
// Query 1: Busca post_ids con SKU que coincide
SELECT post_id FROM wp_postmeta
WHERE meta_key = '_sku'
AND meta_value LIKE '%term%'

// Query 2: Obtiene post_type y post_parent de esos IDs
SELECT ID, post_parent, post_type FROM wp_posts WHERE ID IN (...)
// Luego en PHP filtra variaciones para usar post_parent
```

### Problems

| Problem | Impact |
|---------|--------|
| **2 round trips** a base de datos | Latencia acumulada |
| **Procesamiento en PHP** del resultado de variaciones | Más uso de CPU/memoria |
| **Array operations** (foreach, array_unique) | Complejidad adicional |

---

## Functional Requirements

### FR-1: Single Query with CASE
Reemplazar las 2 queries secuenciales por **1 única query** con CASE statement para resolver variaciones en SQL.

### FR-2: Maintain Functionality
- Debe retornar el mismo resultado que la implementación actual
- Productos simples retornan su propio ID
- Variaciones retornan el ID del producto padre
- Duplicados eliminados

### FR-3: No Breaking Changes
- La firma del método no cambia
- El retorno sigue siendo `array[int]` de product IDs (unique)
- Comportamiento cuando está deshabilitado no cambia

---

## Success Criteria

| ID | Criterion | Metric | Target | How to Measure |
|----|-----------|--------|--------|----------------|
| SC-1 | Queries ejecutadas | 2 → 1 | 50% reducción | Contar queries antes/después |
| SC-2 | Performance improvement | Tiempo de ejecución | 30-40% más rápido | Benchmark con datos reales |
| SC-3 | Resultados correctos | Precisión del resultado | 100% match | Tests de integración |
| SC-4 | Sin regresiones | Tests pasando | 100% | `composer test` |

---

## Test Scenarios (Given/When/Then)

### Happy Path: SKU en producto simple
**Given** SKU search habilitado
**When** Usuario busca "ABC-123" (SKU de producto simple)
**Then** Se retorna array con el ID del producto simple
**And** Solo se ejecuta 1 query SQL

### Happy Path: SKU en variación
**Given** SKU search habilitado
**When** Usuario busca "VAR-456" (SKU de variación)
**Then** Se retorna array con el ID del producto padre
**And** Solo se ejecuta 1 query SQL

### Happy Path: Múltiples coincidencias
**Given** SKU search habilitado
**When** Usuario busca "test" (coincide con varios SKUs)
**Then** Se retornan IDs únicos de productos (sin duplicados)
**And** Variaciones resueltas a sus padres correctamente
**And** Solo se ejecuta 1 query SQL

### Edge Case: No resultados
**Given** SKU search habilitado
**When** Usuario busca "xyz-inexistente"
**Then** Se retorna array vacío
**And** Query no retorna filas

### Edge Case: Deshabilitado
**Given** SKU search deshabilitado
**When** Usuario busca cualquier término
**Then** Se retorna array vacío inmediatamente
**And** No se ejecuta ninguna query

---

## Explicit Constraints (DO NOT)

❌ **DO NOT** modificar la firma del método `get_matching_product_ids()`
❌ **DO NOT** cambiar el comportamiento de `is_enabled()` o `get_exact_sku_match()`
❌ **DO NOT** modificar otros métodos de la clase
❌ **DO NOT** usar consultas sin prepared statements
❌ **DO NOT** cambiar el comportamiento de resolución de variaciones

---

## Technical Context

### Target SQL Query

```sql
SELECT DISTINCT CASE
    WHEN p.post_type = 'product_variation' AND p.post_parent > 0
    THEN p.post_parent
    ELSE p.ID
END as product_id
FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
WHERE pm.meta_key = '_sku'
AND pm.meta_value LIKE '%term%'
```

### WordPress Tables Involved

| Tabla | Rol |
|-------|-----|
| `wp_postmeta` | Contiene los SKUs (meta_key='_sku', meta_value) |
| `wp_posts` | Contiene post_type y post_parent de productos/variaciones |

### WooCommerce Product Types

| Tipo | Descripción | Comportamiento |
|------|-------------|-----------------|
| `product` | Producto simple | Retorna su propio ID |
| `product_variation` | Variación de producto | Retorna ID del padre |

---

## Non-Functional Requirements

| Requirement | Specification |
|-------------|----------------|
| **Performance** | 30-40% más rápido que implementación actual |
| **Backward Compatibility** | 100% - mismo comportamiento y retorno |
| **Code Quality** | Seguir WordPress coding standards |
| **Security** | Usar `$wpdb->prepare()` para prevenir SQL injection |

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| WordPress `wpdb` | External | ✅ Available |
| WooCommerce productos/variaciones | External | ✅ Available |
| Options API | WordPress | ✅ Available |

---

## Open Questions

| Question | Answer | Decision Date |
|----------|--------|---------------|
| ¿Necesitamos índices adicionales? | TBD | Post-implementation |

---

## Out of Scope

- Modificar `get_exact_sku_match()` (no requiere optimización)
- Modificar otras clases que usan este método
- Cambios en UI de configuración
