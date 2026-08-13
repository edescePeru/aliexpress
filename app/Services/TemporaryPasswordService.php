<?php

namespace App\Services;

class TemporaryPasswordService
{
    public function generate($length = 10)
    {
        $upper =
            'ABCDEFGHJKLMNPQRSTUVWXYZ';

        $lower =
            'abcdefghijkmnopqrstuvwxyz';

        $numbers =
            '23456789';

        $symbols =
            '!@#$%&*';

        /*
         * Garantizamos como mínimo:
         * - mayúscula
         * - minúscula
         * - número
         * - símbolo
         */
        $password = [
            $upper[
            random_int(
                0,
                strlen($upper) - 1
            )
            ],

            $lower[
            random_int(
                0,
                strlen($lower) - 1
            )
            ],

            $numbers[
            random_int(
                0,
                strlen($numbers) - 1
            )
            ],

            $symbols[
            random_int(
                0,
                strlen($symbols) - 1
            )
            ],
        ];

        $all =
            $upper .
            $lower .
            $numbers .
            $symbols;

        while (
            count($password) < $length
        ) {
            $password[] =
                $all[
                random_int(
                    0,
                    strlen($all) - 1
                )
                ];
        }

        /*
         * Fisher-Yates usando random_int.
         */
        for (
            $i = count($password) - 1;
            $i > 0;
            $i--
        ) {

            $j =
                random_int(
                    0,
                    $i
                );

            $temp =
                $password[$i];

            $password[$i] =
                $password[$j];

            $password[$j] =
                $temp;
        }

        return implode(
            '',
            $password
        );
    }
}