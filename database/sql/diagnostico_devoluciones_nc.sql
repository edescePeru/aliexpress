-- Solo lectura. Cambiar AMBOS IDs y ejecutar en una misma conexión.
-- Los SET modifican variables de la conexión; no cambian registros del ERP.
SET @nc_tenant = 1;
SET @nc_company = 1;

-- Debe devolver la empresa esperada. Si no hay fila, revisar los IDs.
SELECT id AS company_id, tenant_id FROM companies
WHERE id = @nc_company AND tenant_id = @nc_tenant;

-- Notas cuyo stock ya se reingresó pero cuya evidencia de inventario falta o no cuadra.
SELECT n.id AS note_id, n.serie, n.numero, n.sale_id, s.branch_id,
       n.status AS estado_fiscal, n.internal_reversed_at AS fecha_reingreso,
       n.stock_return_version,
       COALESCE(d.qty, 0) AS cantidad_nota,
       COALESCE(r.qty, 0) AS cantidad_registrada,
       COALESCE(r.rows_count, 0) AS movimientos_registrados,
       CASE
           WHEN n.status <> 'accepted' THEN 'Conciliar estado fiscal y operación anterior'
           WHEN n.internal_reversed_at IS NULL THEN 'Falta fecha de reingreso demostrable'
           WHEN n.stock_return_version = 1 THEN 'Evidencia marcada pero cantidades incompatibles: revisar'
           ELSE 'Ejecutar diagnóstico por ID; todavía no aplicar'
       END AS siguiente_paso
FROM credit_notes n
JOIN sales s ON s.id = n.sale_id
LEFT JOIN (SELECT credit_note_id, SUM(quantity) AS qty FROM credit_note_details GROUP BY credit_note_id) d
    ON d.credit_note_id = n.id
LEFT JOIN (SELECT credit_note_id, SUM(quantity) AS qty, COUNT(*) AS rows_count
           FROM credit_note_stock_returns
           WHERE tenant_id = @nc_tenant AND company_id = @nc_company
           GROUP BY credit_note_id) r ON r.credit_note_id = n.id
WHERE s.tenant_id = @nc_tenant AND s.company_id = @nc_company
  AND n.credit_note_type = 'partial' AND n.internal_reversal_status = 'reversed'
  AND (n.stock_return_version IS NULL OR n.stock_return_version <> 1
       OR ABS(COALESCE(d.qty, 0) - COALESCE(r.qty, 0)) > 0.000001)
ORDER BY n.id;

-- Salidas de notas aplicadas sin costo histórico guardado. Costo cero explícito es válido.
SELECT n.id AS note_id, d.id AS credit_note_detail_id, od.id AS output_detail_id,
       od.stock_item_id, od.stock_lot_id, od.warehouse_id, od.unit_cost, od.total_cost
FROM credit_notes n
JOIN sales s ON s.id = n.sale_id
JOIN credit_note_details d ON d.credit_note_id = n.id
JOIN sale_details sd ON sd.id = d.sale_detail_id AND sd.sale_id = s.id
    AND sd.tenant_id = s.tenant_id AND sd.company_id = s.company_id
JOIN output_details od ON od.sale_detail_id = sd.id
    AND od.tenant_id = s.tenant_id AND od.company_id = s.company_id
WHERE s.tenant_id = @nc_tenant AND s.company_id = @nc_company
  AND n.credit_note_type = 'partial' AND n.internal_reversal_status = 'reversed'
  AND od.unit_cost IS NULL AND od.total_cost IS NULL
ORDER BY n.id, od.id;
