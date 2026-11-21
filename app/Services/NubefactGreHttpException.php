<?php
namespace App\Services;

/** Conserva solo metadatos permitidos; nunca cuerpo, URL ni credenciales. */
class NubefactGreHttpException extends \RuntimeException
{
    private $httpStatus;
    private $providerCode;
    private $providerDetail;

    public function __construct(int $httpStatus, $providerCode = null, ?string $providerDetail = null)
    {
        $this->httpStatus = $httpStatus;
        $this->providerCode = is_scalar($providerCode) && preg_match('/^[0-9]{1,8}$/D', (string) $providerCode)
            ? (string) $providerCode : null;
        $this->providerDetail = $providerDetail === null ? null : substr($providerDetail, 0, 32768);
        parent::__construct('Error HTTP Nubefact: '.$httpStatus);
    }

    public function providerDetail(): ?string { return $this->providerDetail; }

    public static function fromResponse(int $status, $body, NubefactCompanyConnection $connection): self
    {
        $errors = is_array($body) ? ($body['errors'] ?? null) : null;
        $detail = is_string($errors) ? $errors : ($errors === null ? null : json_encode($errors));
        if (is_string($detail)) {
            $detail = str_replace([$connection->token(), $connection->url()], ['[token omitido]', '[ruta omitida]'], $detail);
        }
        return new self($status, is_array($body) ? ($body['codigo'] ?? null) : null, is_string($detail) ? $detail : null);
    }

    public function diagnostic(): array
    {
        return ['http_status' => $this->httpStatus, 'provider_code' => $this->providerCode];
    }
}
