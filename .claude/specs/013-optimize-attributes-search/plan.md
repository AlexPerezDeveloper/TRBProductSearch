# Plan: Optimize Attributes Search Query Implementation

> **Issue**: #13 - Optimizar query de Attributes_Search (3→1 query)
> **Spec Version**: 1.0.0
> **Status**: Draft

---

## Architecture Overview

### Current Query Flow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  Query 1:       │     │  Query 2:       │     │  Query 3:       │
│  Find term_ids  │ ──▶ │  Find tt_ids    │ ──▶ │  Find products  │
│                 │     │                 │     │                 │
│  t + tt JOIN    │     │  tt table       │     │  tr table       │
└─────────────────┘     └─────────────────┘     └─────────────────┘
       │                       │                       │
       └───────────────────────┴───────────────────────┘
                               3 DB round trips
```

### Target Query Flow

```
┌───────────────────────────────────────────────────────────┐
│  Single Query:                                             │
│                                                           │
│  SELECT DISTINCT tr.object_id                             │
│  FROM wp_terms t                                          │
│  INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id│
│  INNER JOIN wp_term_relationships tr                     │
│      ON tt.term_taxonomy_id = tr.term_taxonomy_id         │
│  WHERE tt.taxonomy IN (...)                               │
│  AND t.name LIKE %term%                                   │
│                                                           │
│  1 DB round trip                                          │
└───────────────────────────────────────────────────────────┘
```

---

## Design Decisions

### Decision 1: Query Structure

**Options evaluated:**

| Option | Pros | Cons |
|--------|------|------|
| A. Subqueries | Más fácil de leer | MySQL no siempre optimiza bien |
| B. CTE (WITH clause) | Más estructurado | No soportado en MySQL 5.7 |
| C. JOINs | **Estándar, optimizable** | Sintaxis más compleja |

**Selected**: **Option C - JOINs**

**Rationale:**
- Compatible con todas las versiones de MySQL soportadas
- El optimizador de MySQL puede trabajar mejor con JOINs
- Es el patrón estándar para este tipo de consultas

---

### Decision 2: Placeholder Handling

**Challenge**: WordPress `$wpdb->prepare()` no soporta IN clauses con arrays directamente.

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| A. `vsprintf` manual | Flexible | Requiere escape manual |
| B. `implode` con escape manual | Simple | **Riesgo de SQL injection si mal hecho** |
| C. Escapar taxonomías primero y usar `IN (...)` | **Más seguro** | Requiere paso extra |

**Selected**: **Option C - Escapar taxonomías primero**

**Rationale:**
- Las taxonomías vienen de configuración del plugin (no input de usuario)
- Podemos sanitizarlas con `esc_sql()` e `array_map()`
- Es el patrón existente en el código actual (línea 108)

---

## Implementation Plan

### Phase 1: Optimize `get_matching_product_ids()`

**File**: `includes/class-attributes-search.php`

**Current code structure (lines 93-148):**
```php
public function get_matching_product_ids($term)
{
    if (!$this->is_enabled()) { return array(); }
    $selected_taxonomies = $this->get_selected_attributes();
    if (empty($selected_taxonomies)) { return array(); }

    global $wpdb;

    // Query 1: term_ids
    $term_ids = $wpdb->get_col(/* ... */);
    if (empty($term_ids)) { return array(); }

    // Query 2: term_taxonomy_ids
    $tt_ids = $wpdb->get_col(/* ... */);
    if (empty($tt_ids)) { return array(); }

    // Query 3: object_ids
    $product_ids = $wpdb->get_col(/* ... */);

    return array_map('intval', $product_ids);
}
```

**New implementation:**
```php
public function get_matching_product_ids($term)
{
    if (!$this->is_enabled()) {
        return array();
    }

    $selected_taxonomies = $this->get_selected_attributes();
    if (empty($selected_taxonomies)) {
        return array();
    }

    global $wpdb;

    // Escapar taxonomías para uso seguro en IN clause
    $taxonomies = array_map('esc_sql', $selected_taxonomies);
    $taxonomies_placeholder = "'" . implode("','", $taxonomies) . "'";
    $wildcard = '%';
    $like_term = $wpdb->esc_like($term);

    // Query única con 3 JOINs
    $sql = $wpdb->prepare(
        "SELECT DISTINCT tr.object_id
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        WHERE tt.taxonomy IN ($taxonomies_placeholder)
        AND t.name LIKE %s",
        $wildcard . $like_term . $wildcard
    );

    $product_ids = $wpdb->get_col($sql);

    return array_map('intval', $product_ids ?: array());
}
```

**Key changes:**
1. ✅ Eliminadas queries 2 y 3
2. ✅ Usado JOIN directo entre las 3 tablas
3. ✅ Mantenido comportamiento de empty check (operador `?:`)
4. ✅ `array_map('intval', ...)` mantiene consistencia de tipos

---

## Code Comparison

### Before (3 queries)

```php
// 1. Buscar term_ids (tabla terms + term_taxonomy)
$term_ids_sql = $wpdb->prepare(
    "SELECT DISTINCT t.term_id
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy IN ($taxonomies_placeholder)
    AND t.name LIKE %s",
    $wildcard . $like_term . $wildcard
);
$term_ids = $wpdb->get_col($term_ids_sql);

// 2. Buscar term_taxonomy_ids (solo term_taxonomy)
$term_ids_placeholder = implode(',', array_map('intval', $term_ids));
$tt_ids_sql = "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ($term_ids_placeholder)";
$tt_ids = $wpdb->get_col($tt_ids_sql);

// 3. Buscar object_ids (solo term_relationships)
$tt_ids_placeholder = implode(',', array_map('intval', $tt_ids));
$objects_sql = "SELECT DISTINCT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($tt_ids_placeholder)";
$product_ids = $wpdb->get_col($objects_sql);
```

### After (1 query)

```php
// Query única con 3 JOINs
$sql = $wpdb->prepare(
    "SELECT DISTINCT tr.object_id
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
    WHERE tt.taxonomy IN ($taxonomies_placeholder)
    AND t.name LIKE %s",
    $wildcard . $like_term . $wildcard
);
$product_ids = $wpdb->get_col($sql);
```

---

## Testing Strategy

### Unit Tests

| Test | Purpose |
|------|---------|
| `test_returns_empty_when_disabled` | Verify early return when disabled |
| `test_returns_empty_when_no_attributes_selected` | Verify early return when no config |
| `test_returns_product_ids_for_valid_search` | Verify correct results returned |
| `test_returns_empty_for_no_match` | Verify empty array when no results |
| `test_uses_single_query` | Verify only 1 query executed |

### Integration Tests

| Test | Purpose |
|------|---------|
| `test_attribute_search_with_mocked_db` | Verify SQL generated correctly |
| `test_attribute_search_multiple_taxonomies` | Verify searches across multiple attributes |
| `test_attribute_search_partial_match` | Verify LIKE wildcard works |

### Performance Benchmark

```php
// Benchmark simple
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = $attributes_search->get_matching_product_ids('test');
}
$duration = microtime(true) - $start;

// Expected: 30-50% faster than before
```

---

## Risk Analysis

| Risk | Impact | Mitigation |
|------|--------|------------|
| JOIN muy lento si hay muchos productos | Medium | Usar DISTINCT, índices existentes |
| Diferencia en resultados | High | Tests exhaustivos antes/after |
| Problemas con taxonomías mal configuradas | Low | Validar inputs |

---

## Rollback Plan

Si issues surgen:
1. Revertir cambios en `get_matching_product_ids()`
2. Verificar que todos los tests pasan
3. Limpiar cualquier cache de queries

**No database changes** = rollback limpio posible.
