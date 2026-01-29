# Tasks: Optimize SKU Search Query Implementation

> **Issue**: #14 - Optimizar query de SKU_Search (2→1 query)
> **Spec Version**: 1.0.0
> **Plan Version**: 1.0.0

---

## Task Breakdown Summary

| Phase | Tasks | Estimated |
|-------|-------|-----------|
| Phase 1: Implementation | 1 task | 1 hour |
| Phase 2: Testing | 2 tasks | 1 hour |
| Phase 3: Validation | 1 task | 30 min |
| **Total** | **4 tasks** | **2.5 hours** |

---

## Phase 1: Implementation

### Task 1.1: Optimize get_matching_product_ids() with CASE
- **Spec Reference**: FR-1, FR-2, FR-3
- **Dependencies**: None
- **Estimated**: 1 hour

#### Description
Reemplazar las 2 queries secuenciales en `get_matching_product_ids()` por 1 única query con CASE statement.

#### File to modify
`includes/class-sku-search.php` (lines 60-105)

#### Implementation

Replace current implementation with:

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

#### Key points
- ✅ Mantener early return cuando disabled
- ✅ Usar `$wpdb->prepare()` para el search term
- ✅ CASE statement: variaciones retornan post_parent > 0
- ✅ Productos simples retornan su propio ID
- ✅ DISTINCT elimina duplicados en SQL
- ✅ Operador `?:` para null safety
- ✅ `array_map('intval', ...)` para consistencia de tipos

#### Definition of Done
- [ ] Implementation completa
- [ ] Código sigue WordPress coding standards
- [ ] Firma del método sin cambios
- [ ] Comportamiento mantenido

---

## Phase 2: Testing

### Task 2.1: Add unit tests for SKU optimization
- **Spec Reference**: SC-3, SC-4
- **Dependencies**: Task 1.1
- **Estimated**: 30 minutes

#### Description
Añadir tests específicos para verificar que la optimización funciona correctamente.

#### Tests to add/update

```php
public function test_returns_empty_when_disabled()
{
    TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

    $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
    $result = $sku_search->get_matching_product_ids('test');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
}

public function test_returns_simple_product_id()
{
    // Setup: Producto simple con SKU 'TEST-SKU-001'
    TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

    $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
    $result = $sku_search->get_matching_product_ids('TEST-SKU-001');

    $this->assertIsArray($result);
    $this->assertContainsOnly('int', $result);
}

public function test_returns_parent_id_for_variation()
{
    // Setup: Variación con SKU 'VAR-SKU-002'
    // Debe retornar el ID del producto padre
    TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

    $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
    $result = $sku_search->get_matching_product_ids('VAR-SKU-002');

    $this->assertIsArray($result);
    // Verificar que retornó el ID del padre, no de la variación
}

public function test_returns_empty_for_no_match()
{
    TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

    $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
    $result = $sku_search->get_matching_product_ids('xyz-inexistente-999');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
}

public function test_handles_duplicates_with_distinct()
{
    // Setup: Múltiples coincidencias que podrían duplicar
    TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

    $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
    $result = $sku_search->get_matching_product_ids('test');

    $this->assertIsArray($result);
    // Verificar que no hay duplicados (DISTINCT en SQL)
    $this->assertEquals(array_unique($result), $result);
}
```

#### Definition of Done
- [ ] Tests creados/actualizados
- [ ] Tests pasan
- [ ] Cobertura mantenida

---

### Task 2.2: Update integration tests
- **Spec Reference**: SC-3, SC-4
- **Dependencies**: Task 1.1
- **Estimated**: 30 minutes

#### Description
Actualizar los tests de integración existentes para verificar que la optimización no rompe funcionalidad.

#### File to check
`tests/integration/SkuSearchTest.php`

#### Verification
- [ ] Todos los tests existentes pasan
- [ ] No hay warnings o errors
- [ ] Resultados consistentes

---

## Phase 3: Validation

### Task 3.1: Performance validation and final checks
- **Spec Reference**: SC-1, SC-2
- **Dependencies**: Task 1.1, Task 2.1, Task 2.2
- **Estimated**: 30 minutes

#### Checklist

- [ ] Verificar que solo 1 query se ejecuta
- [ ] Comparar tiempo de ejecución antes/después
- [ ] Ejecutar `composer test`
- [ ] Verificar que no hay regresiones
- [ ] Code review

#### Definition of Done
- [ ] Performance mejorada
- [ ] Todos los tests pasan
- [ ] Sin regresiones
- [ ] Listo para code review

---

## Task Dependencies

```
Task 1.1 ──────────────────────────────────────┐
(Implementación)                               │
                                                ├──▶ Task 2.1 (Unit tests)
                                                └──▶ Task 2.2 (Integration tests)
                                                        │
                                                        ▼
                                                Task 3.1 (Validation)
```

---

## Definition of Done for Entire Feature

- [ ] Todas las tareas completadas
- [ ] Tests unitarios pasando
- [ ] Tests de integración pasando
- [ ] Performance validada
- [ ] Sin regresiones en funcionalidad
- [ ] Código siguiendo WordPress coding standards
- [ ] Listo para merge a main
