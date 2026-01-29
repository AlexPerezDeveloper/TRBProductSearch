# Plan: Optimize SKU Search Query Implementation

> **Issue**: #14 - Optimizar query de SKU_Search (2→1 query)
> **Spec Version**: 1.0.0
> **Status**: Draft

---

## Architecture Overview

### Current Query Flow

```
┌─────────────────┐     ┌─────────────────┐
│  Query 1:       │     │  Procesamiento  │
│  Find post_ids  │ ──▶ │  PHP (foreach)  │
│  con SKU        │     │                 │
│  (postmeta)      │     │  - Filtrar tipos │
└─────────────────┘     │  - Resolver padres│
       │                │  - array_unique  │
       │                └─────────────────┘
       │                         │
       └─────────────────────────┴───────────────┐
       │                                         │
       ▼                                         ▼
┌─────────────────┐                     ┌──────────────┐
│  Query 2:       │                     │  Return      │
│  Get posts data │                     │  product_ids │
│  (posts)        │                     └──────────────┘
└─────────────────┘

2 DB round trips + PHP processing
```

### Target Query Flow

```
┌───────────────────────────────────────────────────────────┐
│  Single Query:                                             │
│                                                           │
│  SELECT DISTINCT CASE                                     │
│      WHEN p.post_type = 'product_variation'              │
│           AND p.post_parent > 0                           │
│      THEN p.post_parent                                   │
│      ELSE p.ID                                            │
│  END as product_id                                         │
│  FROM wp_postmeta pm                                       │
│  INNER JOIN wp_posts p ON pm.post_id = p.ID              │
│  WHERE pm.meta_key = '_sku'                               │
│  AND pm.meta_value LIKE %term%                             │
│                                                           │
│  1 DB round trip, SQL-level variation resolution          │
└───────────────────────────────────────────────────────────┘

┌─────────────────┐
│  Return         │
│  product_ids   │
│  (DISTINCT)     │
└─────────────────┘
```

---

## Design Decisions

### Decision 1: Use CASE Statement in SQL

**Options evaluated:**

| Option | Pros | Cons |
|--------|------|------|
| A. CASE in SQL | **Resolución en BD**, más rápido | Sintaxis más compleja |
| B. Subquery with IF | Más familiar | MySQL puede no optimizar bien |
| C. Keep PHP processing | **Simplicidad** | **Mantiene 2 queries** |

**Selected**: **Option A - CASE in SQL**

**Rationale:**
- Resolución de variaciones se hace en base de datos
- Elimina ciclo foreach en PHP
- DISTINCT maneja duplicados automáticamente
- CASE es estándar SQL y bien optimizado

---

### Decision 2: DISTINCT vs array_unique

**Options:**

| Option | Pros | Cons |
|--------|------|------|
| A. DISTINCT en SQL | **Maneja duplicados en BD** | - |
| B. GROUP BY | También funciona | Sobrekill para este caso |

**Selected**: **Option A - DISTINCT**

**Rationale:**
- SQL nativo, bien optimizado
- Reemplaza `array_unique()` de PHP
- Sintaxis más limpia que GROUP BY para este caso

---

## Implementation Plan

### Phase 1: Optimize `get_matching_product_ids()`

**File**: `includes/class-sku-search.php`

**Current code structure (lines 60-105):**
```php
public function get_matching_product_ids($term)
{
    if (!$this->is_enabled()) { return array(); }

    global $wpdb;

    // Query 1: Buscar post_ids con SKU
    $sql = $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = '_sku'
        AND meta_value LIKE %s",
        $wildcard . $like_term . $wildcard
    );
    $results = $wpdb->get_col($sql);

    if (empty($results)) { return array(); }

    // Query 2: Obtener datos de posts
    $ids_placeholder = implode(',', array_map('intval', $results));
    $posts_sql = "SELECT ID, post_parent, post_type FROM {$wpdb->posts} WHERE ID IN ($ids_placeholder)";
    $posts = $wpdb->get_results($posts_sql);

    // Procesar en PHP
    $product_ids = array();
    foreach ($posts as $post) {
        if ($post->post_type === 'product_variation' && $post->post_parent > 0) {
            $product_ids[] = $post->post_parent;
        } else {
            $product_ids[] = $post->ID;
        }
    }

    return array_unique($product_ids);
}
```

**New implementation:**
```php
public function get_matching_product_ids($term)
{
    if (!$this->is_enabled()) {
        return array();
    }

    global $wpdb;

    $wildcard = '%';
    $like_term = $wpdb->esc_like($term);

    // Query única con CASE para resolver variaciones en SQL
    $sql = $wpdb->prepare(
        "SELECT DISTINCT CASE
            WHEN p.post_type = 'product_variation' AND p.post_parent > 0
            THEN p.post_parent
            ELSE p.ID
        END as product_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_sku'
        AND pm.meta_value LIKE %s",
        $wildcard . $like_term . $wildcard
    );

    $product_ids = $wpdb->get_col($sql);

    return array_map('intval', $product_ids ?: array());
}
```

**Key changes:**
1. ✅ Eliminada Query 2 (posts table lookup)
2. ✅ Eliminado procesamiento PHP foreach
3. ✅ CASE statement resuelve variaciones en SQL
4. ✅ DISTINCT maneja duplicados
5. ✅ `array_map('intval', ...)` mantiene consistencia de tipos
6. ✅ Operador `?:` para null safety

---

## Code Comparison

### Before (2 queries + PHP processing)

```php
// Query 1: Buscar SKUs
$sql = $wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta}
    WHERE meta_key = '_sku'
    AND meta_value LIKE %s",
    $wildcard . $like_term . $wildcard
);
$results = $wpdb->get_col($sql);

if (empty($results)) { return array(); }

// Query 2: Obtener datos de posts
$ids_placeholder = implode(',', array_map('intval', $results));
$posts_sql = "SELECT ID, post_parent, post_type FROM {$wpdb->posts} WHERE ID IN ($ids_placeholder)";
$posts = $wpdb->get_results($posts_sql);

// Procesamiento PHP
$product_ids = array();
foreach ($posts as $post) {
    if ($post->post_type === 'product_variation' && $post->post_parent > 0) {
        $product_ids[] = $post->post_parent;
    } else {
        $product_ids[] = $post->ID;
    }
}

return array_unique($product_ids);
```

### After (1 query, no PHP processing)

```php
$sql = $wpdb->prepare(
    "SELECT DISTINCT CASE
        WHEN p.post_type = 'product_variation' AND p.post_parent > 0
        THEN p.post_parent
        ELSE p.ID
    END as product_id
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = '_sku'
    AND pm.meta_value LIKE %s",
    $wildcard . $like_term . $wildcard
);

$product_ids = $wpdb->get_col($sql);

return array_map('intval', $product_ids ?: array());
```

---

## Testing Strategy

### Unit Tests

| Test | Purpose |
|------|---------|
| `test_returns_empty_when_disabled` | Verify early return when disabled |
| `test_returns_simple_product_id` | Verify simple product ID returned |
| `test_returns_parent_id_for_variation` | Verify variation returns parent ID |
| `test_returns_empty_for_no_match` | Verify empty array when no results |
| `test_uses_single_query` | Verify only 1 query executed |

### Integration Tests

| Test | Purpose |
|------|---------|
| `test_sku_search_simple_product` | Verify SKU search on simple products |
| `test_sku_search_variation` | Verify SKU search on variations returns parent |
| `test_sku_search_multiple_results` | Verify multiple matches with DISTINCT |
| `test_sku_search_partial_match` | Verify LIKE wildcard works |

---

## Risk Analysis

| Risk | Impact | Mitigation |
|------|--------|------------|
| CASE statement performance | Low | MySQL optimiza bien CASE |
| Incorrect parent resolution | High | Tests exhaustivos variaciones |
| Duplicates not handled | Medium | DISTINCT en SQL |

---

## Rollback Plan

Si issues surgen:
1. Revertir cambios en `get_matching_product_ids()`
2. Verificar que todos los tests pasan
3. Limpiar cualquier cache

**No database changes** = rollback limpio posible.
