<?php

namespace App\Services;

use App\TypeTax;
use RuntimeException;

class QuoteTaxCalculatorService
{
    /**
     * Calcula los totales tributarios de una cotización.
     *
     * Cada línea debe traer:
     *
     * - total
     * - type_tax_id
     * - tax_rate
     *
     * IMPORTANTE:
     *
     * "total" representa el precio FINAL de la línea.
     *
     * Es decir:
     *
     * - IGV_*:
     *   el impuesto ya está incluido.
     *
     * - EXONERADO:
     *   total = importe exonerado.
     *
     * - INAFECTO:
     *   total = importe inafecto.
     */
    public function calculate(
        array $productLines,
        array $serviceLines,
        array $discountConfig = []
    ): array {

        /*
         * ============================================================
         * 1. NORMALIZAR LÍNEAS
         * ============================================================
         */

        $lines = [];

        foreach ($productLines as $line) {
            $lines[] =
                $this->normalizeLine(
                    $line
                );
        }

        foreach ($serviceLines as $line) {

            /*
             * Solo los servicios facturables
             * participan del total.
             */
            if (
                isset($line['billable']) &&
                (int) $line['billable'] !== 1
            ) {
                continue;
            }

            $lines[] =
                $this->normalizeLine(
                    $line
                );
        }


        /*
         * ============================================================
         * 2. CLASIFICAR TRIBUTARIAMENTE
         * ============================================================
         */

        foreach (
            $lines as $index => $line
        ) {

            $typeTax =
                TypeTax::query()
                    ->where(
                        'id',
                        $line['type_tax_id']
                    )
                    ->first();

            if (!$typeTax) {
                throw new RuntimeException(
                    'No se pudo resolver uno de los tipos de impuesto de la cotización.'
                );
            }

            $code =
                strtoupper(
                    trim(
                        (string) $typeTax->code
                    )
                );

            if (
                strpos(
                    $code,
                    'IGV_'
                ) === 0
            ) {

                $category =
                    'gravada';

            } elseif (
                $code === 'EXONERADO'
            ) {

                $category =
                    'exonerada';

            } elseif (
                $code === 'INAFECTO'
            ) {

                $category =
                    'inafecta';

            } else {

                throw new RuntimeException(
                    'El tipo de impuesto "' .
                    $typeTax->code .
                    '" no tiene una clasificación tributaria soportada.'
                );
            }

            $lines[$index]['tax_code'] =
                $code;

            $lines[$index]['category'] =
                $category;


            /*
             * Calcular base original.
             */
            if ($category === 'gravada') {

                $factor =
                    $this->getTaxFactor(
                        (float) $line['tax_rate']
                    );

                $lines[$index][
                'base_before_discount'
                ] =
                    $this->round10(
                        $line['total'] /
                        $factor
                    );

            } else {

                /*
                 * Exonerado / inafecto no contienen impuesto.
                 */
                $lines[$index][
                'base_before_discount'
                ] =
                    $this->round10(
                        $line['total']
                    );
            }
        }


        /*
         * ============================================================
         * 3. SUBTOTAL BRUTO
         * ============================================================
         */

        $subtotal =
            $this->round10(
                array_sum(
                    array_column(
                        $lines,
                        'total'
                    )
                )
            );

        if ($subtotal < 0) {
            $subtotal = 0;
        }


        /*
         * ============================================================
         * 4. CONFIGURACIÓN DEL DESCUENTO
         * ============================================================
         */

        $discountType =
            $discountConfig['type']
            ?? 'amount';

        $discountMode =
            $discountConfig['mode']
            ?? 'without_igv';

        $discountValue =
            isset(
                $discountConfig['value']
            )
                ? (float) $discountConfig[
            'value'
            ]
                : 0;


        if (
        !in_array(
            $discountType,
            [
                'amount',
                'percent',
            ],
            true
        )
        ) {
            throw new RuntimeException(
                'El tipo de descuento no es válido.'
            );
        }

        if (
        !in_array(
            $discountMode,
            [
                'with_igv',
                'without_igv',
            ],
            true
        )
        ) {
            throw new RuntimeException(
                'El modo de descuento no es válido.'
            );
        }

        if ($discountValue < 0) {
            throw new RuntimeException(
                'El descuento no puede ser negativo.'
            );
        }

        if (
            $discountType === 'percent' &&
            $discountValue > 100
        ) {
            $discountValue = 100;
        }


        /*
         * ============================================================
         * 5. APLICAR DESCUENTO GLOBAL
         * ============================================================
         */

        $discountBaseTotal = 0;
        $discountGrossTotal = 0;


        if (
            $subtotal > 0 &&
            $discountValue > 0
        ) {

            if (
                $discountMode ===
                'with_igv'
            ) {

                /*
                 * ----------------------------------------------------
                 * DESCUENTO CON IGV / SOBRE EL TOTAL
                 * ----------------------------------------------------
                 *
                 * Ejemplo:
                 *
                 * Total = 60
                 * descuento = 10
                 *
                 * Total final = 50
                 *
                 * Si todo es IGV 18:
                 *
                 * Base original = 50.847...
                 * Base final    = 42.372...
                 *
                 * Quote.descuento ≈ 8.47
                 */

                $discountGrossTotal =
                    $this
                        ->resolveGrossDiscount(
                            $subtotal,
                            $discountType,
                            $discountValue
                        );

                $lines =
                    $this
                        ->applyGrossDiscount(
                            $lines,
                            $discountGrossTotal,
                            $subtotal
                        );

            } else {

                /*
                 * ----------------------------------------------------
                 * DESCUENTO SIN IGV / SOBRE LA BASE
                 * ----------------------------------------------------
                 *
                 * Solo afecta la base gravada.
                 *
                 * Exonerado e inafecto permanecen sin cambios.
                 */

                $gravadaBaseSubtotal = 0;

                foreach (
                    $lines as $line
                ) {

                    if (
                        $line['category'] !==
                        'gravada'
                    ) {
                        continue;
                    }

                    $gravadaBaseSubtotal =
                        $this->round10(
                            $gravadaBaseSubtotal +
                            $line[
                            'base_before_discount'
                            ]
                        );
                }

                $discountBaseTotal =
                    $this
                        ->resolveBaseDiscount(
                            $gravadaBaseSubtotal,
                            $discountType,
                            $discountValue
                        );

                $lines =
                    $this
                        ->applyBaseDiscount(
                            $lines,
                            $discountBaseTotal,
                            $gravadaBaseSubtotal
                        );
            }
        }


        /*
         * ============================================================
         * 6. CALCULAR RESULTADOS TRIBUTARIOS
         * ============================================================
         */

        $gravada = 0;
        $exonerada = 0;
        $inafecta = 0;
        $igvTotal = 0;
        $totalImporte = 0;

        /*
         * En WITH_IGV calculamos posteriormente cuánto
         * representó el descuento en base.
         */
        $discountBaseEquivalent = 0;


        foreach ($lines as $index => $line) {

            $category =
                $line['category'];

            $taxRate =
                (float) $line['tax_rate'];

            $lineFinal =
                $this->round10(
                    $line[
                    'total_after_discount'
                    ]
                );

            if ($lineFinal < 0) {
                $lineFinal = 0;
            }


            /*
             * ========================================================
             * GRAVADA
             * ========================================================
             */

            if (
                $category ===
                'gravada'
            ) {

                $factor =
                    $this->getTaxFactor(
                        $taxRate
                    );

                /*
                 * Si el descuento fue SIN IGV,
                 * applyBaseDiscount() ya calculó exactamente
                 * la base resultante.
                 *
                 * Si fue CON IGV, obtenemos la nueva base
                 * desde el total final.
                 */
                if (
                isset(
                    $line[
                    'base_after_discount'
                    ]
                )
                ) {

                    $base =
                        $this->round10(
                            $line[
                            'base_after_discount'
                            ]
                        );

                    /*
                     * Recalcular total final desde la base.
                     */
                    $tax =
                        $this->round10(
                            $base *
                            (
                                $taxRate /
                                100
                            )
                        );

                    $lineFinal =
                        $this->round10(
                            $base +
                            $tax
                        );

                    $lines[$index][
                    'total_after_discount'
                    ] =
                        $lineFinal;

                } else {

                    $base =
                        $this->round10(
                            $lineFinal /
                            $factor
                        );

                    $tax =
                        $this->round10(
                            $lineFinal -
                            $base
                        );
                }


                $gravada =
                    $this->round10(
                        $gravada +
                        $base
                    );

                $igvTotal =
                    $this->round10(
                        $igvTotal +
                        $tax
                    );


                /*
                 * Si fue descuento CON IGV,
                 * obtenemos su equivalente en base.
                 */
                if (
                    $discountMode ===
                    'with_igv'
                ) {

                    $baseDiscount =
                        $this->round10(
                            $line[
                            'base_before_discount'
                            ] -
                            $base
                        );

                    if ($baseDiscount < 0) {
                        $baseDiscount = 0;
                    }

                    $discountBaseEquivalent =
                        $this->round10(
                            $discountBaseEquivalent +
                            $baseDiscount
                        );
                }


                /*
                 * ========================================================
                 * EXONERADA
                 * ========================================================
                 */

            } elseif (
                $category ===
                'exonerada'
            ) {

                $exonerada =
                    $this->round10(
                        $exonerada +
                        $lineFinal
                    );


                /*
                 * En una línea sin impuesto,
                 * descuento bruto = descuento base.
                 */
                if (
                    $discountMode ===
                    'with_igv'
                ) {

                    $discountBaseEquivalent =
                        $this->round10(
                            $discountBaseEquivalent +
                            (
                                $line['total'] -
                                $lineFinal
                            )
                        );
                }


                /*
                 * ========================================================
                 * INAFECTA
                 * ========================================================
                 */

            } elseif (
                $category ===
                'inafecta'
            ) {

                $inafecta =
                    $this->round10(
                        $inafecta +
                        $lineFinal
                    );

                if (
                    $discountMode ===
                    'with_igv'
                ) {

                    $discountBaseEquivalent =
                        $this->round10(
                            $discountBaseEquivalent +
                            (
                                $line['total'] -
                                $lineFinal
                            )
                        );
                }
            }


            $totalImporte =
                $this->round10(
                    $totalImporte +
                    $lineFinal
                );
        }


        /*
         * ============================================================
         * 7. DESCUENTO QUE SE MOSTRARÁ / GUARDARÁ
         * ============================================================
         *
         * Quote.descuento mantiene la semántica histórica
         * mostrada en la pantalla:
         *
         * WITH_IGV:
         *      equivalente de descuento en base.
         *
         * WITHOUT_IGV:
         *      descuento aplicado directamente a la base.
         */

        $discountTotal =
            $discountMode === 'with_igv'
                ? $this->round10(
                $discountBaseEquivalent
            )
                : $this->round10(
                $discountBaseTotal
            );


        /*
         * ============================================================
         * RESULTADO
         * ============================================================
         */

        return [
            'subtotal' =>
                $subtotal,

            /*
             * Es el valor mostrado como:
             *
             * DESCUENTO (-)
             */
            'discount_total' =>
                $discountTotal,

            /*
             * Reducción económica real del total.
             *
             * Nos sirve para auditoría/pruebas,
             * aunque Quote.descuento no la guarda.
             */
            'discount_gross_total' =>
                $this->round10(
                    $subtotal -
                    $totalImporte
                ),

            'gravada' =>
                $gravada,

            'exonerada' =>
                $exonerada,

            'inafecta' =>
                $inafecta,

            'igv_total' =>
                $igvTotal,

            'total_importe' =>
                $totalImporte,

            'lines' =>
                $lines,
        ];
    }


    /**
     * Normaliza una línea comercial.
     */
    private function normalizeLine(
        array $line
    ): array {

        $typeTaxId =
            isset(
                $line['type_tax_id']
            )
                ? (int) $line[
            'type_tax_id'
            ]
                : 0;

        $taxRate =
            isset(
                $line['tax_rate']
            )
                ? (float) $line[
            'tax_rate'
            ]
                : null;

        $total =
            isset(
                $line['total']
            )
                ? (float) $line[
            'total'
            ]
                : 0;


        if ($typeTaxId <= 0) {
            throw new RuntimeException(
                'Una línea de la cotización no tiene tipo de impuesto.'
            );
        }

        if ($taxRate === null) {
            throw new RuntimeException(
                'Una línea de la cotización no tiene tasa tributaria.'
            );
        }

        if ($total < 0) {
            throw new RuntimeException(
                'Una línea de la cotización tiene un importe negativo.'
            );
        }


        return [
            'type_tax_id' =>
                $typeTaxId,

            'tax_rate' =>
                $taxRate,

            'total' =>
                $this->round10(
                    $total
                ),

            'total_after_discount' =>
                $this->round10(
                    $total
                ),

            /*
             * Descuento bruto aplicado sobre la línea.
             */
            'discount_applied' =>
                0,

            /*
             * Descuento sobre base imponible.
             */
            'discount_base_applied' =>
                0,
        ];
    }


    /**
     * Resuelve un descuento cuyo valor se expresa
     * sobre el precio final.
     */
    private function resolveGrossDiscount(
        float $subtotal,
        string $type,
        float $value
    ): float {

        if (
            $subtotal <= 0 ||
            $value <= 0
        ) {
            return 0;
        }

        if ($type === 'percent') {

            $discount =
                $subtotal *
                (
                    $value /
                    100
                );

        } else {

            $discount =
                $value;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return $this->round10(
            $discount
        );
    }


    /**
     * Resuelve el descuento expresado sobre
     * la base gravada.
     */
    private function resolveBaseDiscount(
        float $baseSubtotal,
        string $type,
        float $value
    ): float {

        if (
            $baseSubtotal <= 0 ||
            $value <= 0
        ) {
            return 0;
        }

        if ($type === 'percent') {

            $discount =
                $baseSubtotal *
                (
                    $value /
                    100
                );

        } else {

            $discount =
                $value;
        }

        if ($discount > $baseSubtotal) {
            $discount =
                $baseSubtotal;
        }

        return $this->round10(
            $discount
        );
    }


    /**
     * Distribuye un descuento expresado
     * sobre el precio final entre TODAS las líneas.
     *
     * Usado para WITH_IGV.
     */
    private function applyGrossDiscount(
        array $lines,
        float $discount,
        float $subtotal
    ): array {

        if (
            $discount <= 0 ||
            $subtotal <= 0 ||
            empty($lines)
        ) {
            return $lines;
        }

        $distributed = 0;

        $lastIndex =
            count($lines) - 1;


        foreach (
            $lines as $index => $line
        ) {

            if (
                $index ===
                $lastIndex
            ) {

                $lineDiscount =
                    $this->round10(
                        $discount -
                        $distributed
                    );

            } else {

                $ratio =
                    $line['total'] /
                    $subtotal;

                $lineDiscount =
                    $this->round10(
                        $discount *
                        $ratio
                    );

                $distributed =
                    $this->round10(
                        $distributed +
                        $lineDiscount
                    );
            }


            if (
                $lineDiscount >
                $line['total']
            ) {
                $lineDiscount =
                    $line['total'];
            }


            $lines[$index][
            'discount_applied'
            ] =
                $lineDiscount;

            $lines[$index][
            'total_after_discount'
            ] =
                $this->round10(
                    $line['total'] -
                    $lineDiscount
                );
        }


        return $lines;
    }


    /**
     * Distribuye un descuento expresado en BASE
     * únicamente entre líneas gravadas.
     *
     * Usado para WITHOUT_IGV.
     */
    private function applyBaseDiscount(
        array $lines,
        float $discountBase,
        float $baseSubtotal
    ): array {

        if (
            $discountBase <= 0 ||
            $baseSubtotal <= 0 ||
            empty($lines)
        ) {
            return $lines;
        }


        $gravadaIndexes = [];

        foreach (
            $lines as $index => $line
        ) {

            if (
                $line['category'] ===
                'gravada'
            ) {
                $gravadaIndexes[] =
                    $index;
            }
        }


        if (empty($gravadaIndexes)) {
            return $lines;
        }


        $distributed = 0;

        $lastGravadaIndex =
            end(
                $gravadaIndexes
            );


        foreach (
            $gravadaIndexes as $index
        ) {

            $baseBefore =
                $lines[$index][
                'base_before_discount'
                ];


            if (
                $index ===
                $lastGravadaIndex
            ) {

                $lineBaseDiscount =
                    $this->round10(
                        $discountBase -
                        $distributed
                    );

            } else {

                $ratio =
                    $baseBefore /
                    $baseSubtotal;

                $lineBaseDiscount =
                    $this->round10(
                        $discountBase *
                        $ratio
                    );

                $distributed =
                    $this->round10(
                        $distributed +
                        $lineBaseDiscount
                    );
            }


            if (
                $lineBaseDiscount >
                $baseBefore
            ) {
                $lineBaseDiscount =
                    $baseBefore;
            }


            $baseAfter =
                $this->round10(
                    $baseBefore -
                    $lineBaseDiscount
                );


            $lines[$index][
            'discount_base_applied'
            ] =
                $lineBaseDiscount;

            $lines[$index][
            'base_after_discount'
            ] =
                $baseAfter;
        }


        return $lines;
    }


    /**
     * Obtiene factor tributario.
     */
    private function getTaxFactor(
        float $taxRate
    ): float {

        $factor =
            1 + (
                $taxRate /
                100
            );

        if ($factor <= 0) {
            throw new RuntimeException(
                'El factor tributario calculado es inválido.'
            );
        }

        return $factor;
    }


    /**
     * Redondeo de cálculo interno.
     */
    private function round10(
        $value
    ): float {

        return round(
            (float) $value,
            10
        );
    }
}