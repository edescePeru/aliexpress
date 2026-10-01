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
     * total
     * type_tax_id
     * tax_rate
     *
     * El total recibido se considera precio final
     * con impuesto incluido cuando corresponda.
     */
    public function calculate(
        array $productLines,
        array $serviceLines,
        array $discountConfig = []
    ): array {

        $lines = [];

        foreach ($productLines as $line) {

            $lines[] = $this->normalizeLine(
                $line
            );
        }

        foreach ($serviceLines as $line) {

            /*
             * Solo servicios facturables afectan
             * el total comercial de Quote.
             */
            if (
                isset($line['billable']) &&
                (int) $line['billable'] !== 1
            ) {
                continue;
            }

            $lines[] = $this->normalizeLine(
                $line
            );
        }

        /*
         * Total bruto antes del descuento global.
         */
        $subtotal = $this->round10(
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
         * Resolver descuento global.
         */
        $discountTotal =
            $this->resolveDiscountTotal(
                $subtotal,
                $discountConfig
            );

        /*
         * Distribuir proporcionalmente el descuento
         * entre todas las líneas.
         */
        $lines =
            $this->applyProportionalDiscount(
                $lines,
                $discountTotal,
                $subtotal
            );

        $gravada = 0;
        $exonerada = 0;
        $inafecta = 0;
        $igvTotal = 0;
        $totalImporte = 0;

        foreach ($lines as $line) {

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

            $lineFinal =
                $this->round10(
                    $line['total_after_discount']
                );

            if ($lineFinal < 0) {
                $lineFinal = 0;
            }

            $code =
                strtoupper(
                    trim(
                        (string) $typeTax->code
                    )
                );

            $taxRate =
                (float) $line['tax_rate'];

            /*
             * Gravado.
             *
             * Todo código IGV_* se considera afecto.
             */
            if (
                strpos(
                    $code,
                    'IGV_'
                ) === 0
            ) {

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

            } elseif (
                $code === 'EXONERADO'
            ) {

                $exonerada =
                    $this->round10(
                        $exonerada +
                        $lineFinal
                    );

            } elseif (
                $code === 'INAFECTO'
            ) {

                $inafecta =
                    $this->round10(
                        $inafecta +
                        $lineFinal
                    );

            } else {

                throw new RuntimeException(
                    'El tipo de impuesto "' .
                    $typeTax->code .
                    '" no tiene una clasificación tributaria soportada.'
                );
            }

            $totalImporte =
                $this->round10(
                    $totalImporte +
                    $lineFinal
                );
        }

        return [
            'subtotal' =>
                $subtotal,

            /*
             * Por compatibilidad con Quote.descuento
             * guardaremos el descuento total aplicado.
             */
            'discount_total' =>
                $discountTotal,

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


    private function normalizeLine(
        array $line
    ): array {

        $typeTaxId =
            isset(
                $line['type_tax_id']
            )
                ? (int) $line['type_tax_id']
                : 0;

        $taxRate =
            isset(
                $line['tax_rate']
            )
                ? (float) $line['tax_rate']
                : null;

        $total =
            isset(
                $line['total']
            )
                ? (float) $line['total']
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

            'discount_applied' =>
                0,
        ];
    }


    private function resolveDiscountTotal(
        float $subtotal,
        array $config
    ): float {

        if ($subtotal <= 0) {
            return 0;
        }

        $type =
            $config['type']
            ?? 'amount';

        $value =
            isset(
                $config['value']
            )
                ? (float) $config['value']
                : 0;

        if ($value <= 0) {
            return 0;
        }

        if ($type === 'percent') {

            $percentage =
                $value /
                100;

            $discount =
                $subtotal *
                $percentage;

        } else {

            /*
             * Con múltiples tasas ya no tiene sentido
             * transformar un descuento "sin IGV"
             * usando un único factor.
             *
             * Por ahora el monto se interpreta
             * directamente sobre el total final.
             */
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


    private function applyProportionalDiscount(
        array $lines,
        float $discountTotal,
        float $subtotal
    ): array {

        if (
            $discountTotal <= 0 ||
            $subtotal <= 0 ||
            empty($lines)
        ) {
            return $lines;
        }

        $distributed =
            0;

        $lastIndex =
            count($lines) - 1;

        foreach (
            $lines as $index => $line
        ) {

            if ($index === $lastIndex) {

                /*
                 * La última línea absorbe cualquier
                 * diferencia decimal acumulada.
                 */
                $lineDiscount =
                    $this->round10(
                        $discountTotal -
                        $distributed
                    );

            } else {

                $ratio =
                    $line['total'] /
                    $subtotal;

                $lineDiscount =
                    $this->round10(
                        $discountTotal *
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


    private function round10(
        $value
    ): float {

        return round(
            (float) $value,
            10
        );
    }
}