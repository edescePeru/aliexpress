<?php

namespace App\Services;

/** No exponer mediante JSON, logs o respuestas del controlador. */
final class NubefactCompanyConnection
{
    private $url;
    private $token;
    private $ruc;
    private $series;

    public function __construct(string $url, string $token, string $ruc, array $series)
    {
        $this->url = $url;
        $this->token = $token;
        $this->ruc = $ruc;
        $this->series = $series;
    }

    public function url(): string { return $this->url; }
    public function token(): string { return $this->token; }
    public function ruc(): string { return $this->ruc; }

    public function saleSeries(string $documentType): string
    {
        if (!in_array($documentType, ['01', '03'], true)) {
            throw new \InvalidArgumentException('Tipo de comprobante no válido.');
        }
        return $this->series[$documentType === '01' ? 'factura' : 'boleta'];
    }

    public function creditNoteSeries(string $originalDocumentType): string
    {
        if (!in_array($originalDocumentType, ['01', '03'], true)) {
            throw new \InvalidArgumentException('Tipo de documento original no válido.');
        }
        $key = $originalDocumentType === '01' ? 'nc_factura' : 'nc_boleta';
        $value = $this->series[$key] ?? '';
        if ($value === '') {
            throw new \RuntimeException('La empresa no tiene configurada la serie de nota de crédito.');
        }
        return $value;
    }
}
