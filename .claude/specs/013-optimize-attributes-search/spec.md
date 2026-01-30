# Spec: Optimize Attributes Search Query

> **Issue**: #13 - Optimizar query de Attributes_Search (3→1 query)
> **Version**: 1.0.0
> **Status**: Draft

---

## User Story

**As a** usuario del plugin de búsqueda
**I want** que las búsquedas por atributos sean más rápidas
**So that** la experiencia de búsqueda sea más fluida y el servidor tenga menos carga

---

## Stakeholders

| Role | Description | Impact |
|------|-------------|--------|
| **Primary** | Usuarios finales de la tienda | Búsquedas más rápidas |
| **Secondary** | Dueño de la tienda | Menor carga en servidor, mejor SEO |
| **Tertiary** | Equipo de desarrollo | Código más maintainable |

---

## Current State Analysis

### Existing Implementation (lines 93-148)

El método `get_matching_product_ids()` ejecuta **3 queries secuenciales**:

```php
// Query 1: Busca term_ids
SELECT DISTINCT t.term_id
FROM wp_terms t
INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy IN ('pa_color', 'pa_size', ...)
AND t.name LIKE '%term%'

// Query 2: Busca term_taxonomy_ids
SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id IN (...)

// Query 3: Busca product_ids
SELECT DISTINCT object_id FROM wp_term_relationships WHERE term_taxonomy_id IN (...)
```

### Problems

| Problem | Impact |
|---------|--------|
| **3 round trips** a base de datos | Latencia acumulada |
| **Transferencia de datos** innecesaria entre queries | Más uso de red/memoria |
| **Complejidad adicional** en código PHP | Más difícil de mantener |

---

## Functional Requirements

### FR-1: Single JOIN Query
Reemplazar las 3 queries secuenciales por **1 única query** con JOINs.

### FR-2: Maintain Functionality
- Debe retornar el mismo resultado que la implementación actual
- Debe respetar las taxonomías seleccionadas en configuración
- Debe mantener búsqueda parcial (LIKE %term%)

### FR-3: No Breaking Changes
- La firma del método no cambia
- El retorno sigue siendo `array[int]` de product IDs
- Comportamiento cuando está deshabilitado no cambia

---

## Success Criteria

| ID | Criterion | Metric | Target | How to Measure |
|----|-----------|--------|--------|----------------|
| SC-1 | Queries ejecutadas | 3 → 1 | 100% reducción | Contar queries antes/después |
| SC-2 | Performance improvement | Tiempo de ejecución | 30-50% más rápido | Benchmark con datos reales |
| SC-3 | Resultados correctos | Precisión del resultado | 100% match | Tests de integración |
| SC-4 | Sin regresiones | Tests pasando | 100% | `composer test` |

---

## Test Scenarios (Given/When/Then)

### Happy Path: Búsqueda normal
**Given** Attributes search habilitado con taxonomías `pa_color`, `pa_size`
**When** Usuario busca "rojo"
**Then** Se retorna array de product_ids que tienen productos con atributo "rojo"
**And** Solo se ejecuta 1 query SQL

### Happy Path: Múltiples taxonomías
**Given** Attributes search habilitado con `pa_color`, `pa_size`, `pa_material`
**When** Usuario busca "algodón"
**Then** Se retornan productos de cualquier taxonomía que contenga "algodón"
**And** Solo se ejecuta 1 query SQL

### Edge Case: No resultados
**Given** Attributes search habilitado
**When** Usuario busca "xyzinexistente"
**Then** Se retorna array vacío
**And** Query no retorna filas

### Edge Case: Deshabilitado
**Given** Attributes search deshabilitado
**When** Usuario busca cualquier término
**Then** Se retorna array vacío inmediatamente
**And** No se ejecuta ninguna query

### Edge Case: Sin taxonomías seleccionadas
**Given** Attributes search habilitado pero sin taxonomías seleccionadas
**When** Usuario busca cualquier término
**Then** Se retorna array vacío inmediatamente
**And** No se ejecuta ninguna query

---

## Explicit Constraints (DO NOT)

❌ **DO NOT** modificar la firma del método `get_matching_product_ids()`
❌ **DO NOT** cambiar el comportamiento de `is_enabled()` o `get_selected_attributes()`
❌ **DO NOT** modificar el método `search_attribute_terms()` (fuera de alcance)
❌ **DO NOT** usar consultas sin prepared statements
❌ **DO NOT** hardcode taxonomías (debe respetar configuración)

---

## Technical Context

### Target SQL Query

```sql
SELECT DISTINCT tr.object_id
FROM wp_terms t
INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
INNER JOIN wp_term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
WHERE tt.taxonomy IN ('pa_color', 'pa_size', ...)
AND t.name LIKE '%term%'
```

### WordPress Tables Involved

| Tabla | Rol |
|-------|-----|
| `wp_terms` | Contiene los nombres de los atributos (ej: "rojo", "grande") |
| `wp_term_taxonomy` | Vincula términos con su taxonomía (ej: "pa_color") |
| `wp_term_relationships` | Vincula productos con términos |

### Existing Tests

- `tests/integration/AttributesSearchTest.php` - Tests de integración existentes
- Deben pasar sin modificaciones

---

## Non-Functional Requirements

| Requirement | Specification |
|-------------|----------------|
| **Performance** | 30-50% más rápido que implementación actual |
| **Backward Compatibility** | 100% - mismo comportamiento y retorno |
| **Code Quality** | Seguir WordPress coding standards |
| **Security** | Usar `$wpdb->prepare()` para prevenir SQL injection |

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| WordPress `wpdb` | External | ✅ Available |
| WooCommerce `wc_get_attribute_taxonomies()` | External | ✅ Available |
| Options API | WordPress | ✅ Available |

---

## Open Questions

| Question | Answer | Decision Date |
|----------|--------|---------------|
| ¿Necesitamos índices adicionales? | TBD | Post-implementation |
| ¿Cómo medimos performance? | Benchmark con datos de prueba | Resolved |

---

## Out of Scope

- Optimizar `search_attribute_terms()` (fase futura)
- Modificar otras clases que usan este método
- Cambios en UI de configuración
