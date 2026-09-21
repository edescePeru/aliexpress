<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateInventoryMovementsViewForCompanyContext extends Migration
{
    public function up()
    {
        DB::statement("
        CREATE OR REPLACE VIEW inventory_movements_view AS

        SELECT
            CONCAT('IN_', de.id, '_', sl.id) AS movement_id,
            'IN' AS movement_type,

            sl.tenant_id AS tenant_id,
            sl.company_id AS company_id,

            de.stock_item_id AS stock_item_id,
            de.material_id AS material_id,

            sl.warehouse_id AS warehouse_id,
            sl.location_id AS location_id,
            sl.id AS stock_lot_id,

            sl.lot_code AS lot_code,
            sl.expiration_date AS expiration_date,

            e.date_entry AS movement_date,

            de.entered_quantity AS quantity,

            CASE
                WHEN UPPER(
                    COALESCE(
                        sv.value_text,
                        sd.default_value,
                        'PEN'
                    )
                ) = 'USD'
                THEN
                    CASE UPPER(e.currency_invoice)

                        WHEN 'USD' THEN
                            CASE
                                WHEN
                                    de.total_detail IS NOT NULL
                                    AND de.entered_quantity > 0
                                THEN
                                    de.total_detail / de.entered_quantity
                                ELSE
                                    de.unit_price
                            END

                        WHEN 'PEN' THEN
                            CASE
                                WHEN
                                    e.currency_venta IS NOT NULL
                                    AND e.currency_venta > 0
                                THEN
                                    (
                                        CASE
                                            WHEN
                                                de.total_detail IS NOT NULL
                                                AND de.entered_quantity > 0
                                            THEN
                                                de.total_detail / de.entered_quantity
                                            ELSE
                                                de.unit_price
                                        END
                                    ) / e.currency_venta
                                ELSE
                                    CASE
                                        WHEN
                                            de.total_detail IS NOT NULL
                                            AND de.entered_quantity > 0
                                        THEN
                                            de.total_detail / de.entered_quantity
                                        ELSE
                                            de.unit_price
                                    END
                            END

                        ELSE
                            CASE
                                WHEN
                                    de.total_detail IS NOT NULL
                                    AND de.entered_quantity > 0
                                THEN
                                    de.total_detail / de.entered_quantity
                                ELSE
                                    de.unit_price
                            END
                    END

                WHEN UPPER(
                    COALESCE(
                        sv.value_text,
                        sd.default_value,
                        'PEN'
                    )
                ) = 'PEN'
                THEN
                    CASE UPPER(e.currency_invoice)

                        WHEN 'PEN' THEN
                            CASE
                                WHEN
                                    de.total_detail IS NOT NULL
                                    AND de.entered_quantity > 0
                                THEN
                                    de.total_detail / de.entered_quantity
                                ELSE
                                    de.unit_price
                            END

                        WHEN 'USD' THEN
                            CASE
                                WHEN
                                    e.currency_compra IS NOT NULL
                                    AND e.currency_compra > 0
                                THEN
                                    (
                                        CASE
                                            WHEN
                                                de.total_detail IS NOT NULL
                                                AND de.entered_quantity > 0
                                            THEN
                                                de.total_detail / de.entered_quantity
                                            ELSE
                                                de.unit_price
                                        END
                                    ) * e.currency_compra
                                ELSE
                                    CASE
                                        WHEN
                                            de.total_detail IS NOT NULL
                                            AND de.entered_quantity > 0
                                        THEN
                                            de.total_detail / de.entered_quantity
                                        ELSE
                                            de.unit_price
                                    END
                            END

                        ELSE
                            CASE
                                WHEN
                                    de.total_detail IS NOT NULL
                                    AND de.entered_quantity > 0
                                THEN
                                    de.total_detail / de.entered_quantity
                                ELSE
                                    de.unit_price
                            END
                    END

                ELSE
                    CASE
                        WHEN
                            de.total_detail IS NOT NULL
                            AND de.entered_quantity > 0
                        THEN
                            de.total_detail / de.entered_quantity
                        ELSE
                            de.unit_price
                    END
            END AS unit_cost,

            'entry' AS source_type,
            e.id AS source_id

        FROM detail_entries de

        INNER JOIN entries e
            ON e.id = de.entry_id

        INNER JOIN stock_lots sl
            ON sl.detail_entry_id = de.id

        LEFT JOIN setting_definitions sd
            ON sd.`key` = 'finance.base_currency'
            AND sd.scope = 'company'
            AND sd.is_active = 1

        LEFT JOIN setting_values sv
            ON sv.setting_definition_id = sd.id
            AND sv.tenant_id = sl.tenant_id
            AND sv.company_id = sl.company_id
            AND sv.branch_id IS NULL

        WHERE
            e.deleted_at IS NULL
            AND de.stock_item_id IS NOT NULL


        UNION ALL


        SELECT
            CONCAT('OUT_', od.id) AS movement_id,
            'OUT' AS movement_type,

            sl.tenant_id AS tenant_id,
            sl.company_id AS company_id,

            od.stock_item_id AS stock_item_id,
            od.material_id AS material_id,

            od.warehouse_id AS warehouse_id,
            od.location_id AS location_id,
            od.stock_lot_id AS stock_lot_id,

            sl.lot_code AS lot_code,
            sl.expiration_date AS expiration_date,

            o.request_date AS movement_date,

            od.percentage AS quantity,
            od.unit_cost AS unit_cost,

            'output' AS source_type,
            o.id AS source_id

        FROM output_details od

        INNER JOIN outputs o
            ON o.id = od.output_id

        LEFT JOIN stock_lots sl
            ON sl.id = od.stock_lot_id

        WHERE
            o.state IN ('attended', 'confirmed')
            AND od.stock_item_id IS NOT NULL
    ");
    }
}
