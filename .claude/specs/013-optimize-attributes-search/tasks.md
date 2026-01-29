# Tasks: Optimize Attributes Search Query Implementation

> **Issue**: #13 - Optimizar query de Attributes_Search (3→1 query)
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

### Task 1.1: Optimize get_matching_product_ids() with JOINs
- **Spec Reference**: FR-1, FR-2, FR-3
- **Dependencies**: None
- **Estimated**: 1 hour

#### Description
Reemplazar las 3 queries secuenciales en `get_matching_product_ids()` por 1 única query con JOINs.

#### File to modify
`includes/class-attributes-search.php` (lines 93-148)

#### Implementation

Replace current implementation with:

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

#### Key points
- ✅ Mantener early returns cuando disabled o sin taxonomías
- ✅ Usar `esc_sql()` para taxonomías (patrón existente línea 108)
- ✅ Usar `$wpdb->prepare()` para el search term
- ✅ Usar operador `?:` para evitar warning de get_col si retorna null/false
- ✅ Mantener `array_map('intval', ...)` para consistencia de tipos

#### Definition of Done
- [ ] Implementation completa
- [ ] Código sigue WordPress coding standards
- [ ] Firma del método sin cambios
- [ ] Comportamiento mantenido (mismos resultados)

---

## Phase 2: Testing

### Task 2.1: Add unit tests for query optimization
- **Spec Reference**: SC-3, SC-4
- **Dependencies**: Task 1.1
- **Estimated**: 30 minutes

#### Description
Añadir tests específicos para verificar que la optimización funciona correctamente.

#### File to modify
`tests/unit/AttributesSearchTest.php` (nuevo) o actualizar existentes

#### Tests to add

```php
public function test_returns_empty_when_disabled()
{
    // Set option to disabled
    TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

    $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();
    $result = $attr_search->get_matching_product_ids('test');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
}

public function test_returns_empty_when_no_attributes_selected()
{
    TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
    TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

    $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();
    $result = $attr_search->get_matching_product_ids('test');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
}

public function test_returns_product_ids_for_valid_search()
{
    // Setup mocked data
    TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
    TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

    $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();
    $result = $attr_search->get_matching_product_ids('rojo');

    $this->assertIsArray($result);
    $this->assertContainsOnly('int', $result);
}

public function test_returns_empty_for_no_match()
{
    TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
    TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

    $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();
    $result = $attr_search->get_matching_product_ids('xyzinexistente123');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
}
```

#### Definition of Done
- [ ] Tests creados
- [ ] Tests pasan
- [ ] Cobertura mantenida o mejorada

---

### Task 2.2: Update integration tests
- **Spec Reference**: SC-3, SC-4
- **Dependencies**: Task 1.1
- **Estimated**: 30 minutes

#### Description
Actualizar los tests de integración existentes para verificar que la optimización no rompe funcionalidad.

#### File to check
`tests/integration/AttributesSearchTest.php`

#### Verification
- [ ] Todos los tests existentes pasan
- [ ] No hay warnings o errors en PHPUnit
- [ ] Resultados consistentes con implementación anterior

---

## Phase 3: Validation

### Task 3.1: Performance validation and final checks
- **Spec Reference**: SC-1, SC-2
- **Dependencies**: Task 1.1, Task 2.1, Task 2.2
- **Estimated**: 30 minutes

#### Description
Validar que la optimización mejora performance según criterios de éxito.

#### Checklist

- [ ] Verificar que solo 1 query se ejecuta (manual debug o log)
- [ ] Comparar tiempo de ejecución antes/después (benchmark simple)
- [ ] Ejecutar `composer test` - todos los tests deben pasar
- [ ] Verificar que no hay regresiones en funcionalidad existente
- [ ] Code review de los cambios

#### Benchmark simple

```php
// Antes (con código original)
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = $attr_search->get_matching_product_ids('test');
}
$before = microtime(true) - $start;

// Después (con optimización)
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = $attr_search->get_matching_product_ids('test');
}
$after = microtime(true) - $start;

// Expected: $after < $before (al menos 30% más rápido)
```

#### Definition of Done
- [ ] Performance mejorada o mantenida
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
- [ ] Performance validada (30-50% más rápido o igual)
- [ ] Sin regresiones en funcionalidad
- [ ] Código siguiendo WordPress coding standards
- [ ] Listo para merge a main
