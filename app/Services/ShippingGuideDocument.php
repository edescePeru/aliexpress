<?php
namespace App\Services;

use Illuminate\Validation\ValidationException;

class ShippingGuideDocument
{
    public const TYPES = ['pdf', 'xml', 'cdr'];

    public static function available($guide, $type)
    {
        if (ShippingGuideIdentityConflict::blocked($guide)) { return false; }
        if (!in_array($type, self::TYPES, true)) { return false; }
        $response = is_array($guide->last_nubefact_response) ? $guide->last_nubefact_response : [];
        return !empty($response[$type.'_zip_base64']) || !empty($guide->{$type.'_link'}) || !empty($response['enlace_del_'.$type]);
    }

    public static function resolve($guide, $type)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
        ShippingGuideIdentityConflict::assertUsable($guide);
        $response = is_array($guide->last_nubefact_response) ? $guide->last_nubefact_response : [];
        $zip = $response[$type.'_zip_base64'] ?? null;
        if ($zip) {
            if (!is_string($zip) || strlen($zip) > 28000000) { self::invalid(); }
            $bytes = base64_decode($zip, true);
            if ($bytes === false || strlen($bytes) < 22 || strlen($bytes) > 20000000
                || !in_array(substr($bytes, 0, 4), ["PK\x03\x04", "PK\x05\x06"], true)) { self::invalid(); }
            $name = preg_replace('/[^A-Za-z0-9_-]/', '-', $guide->serie.'-'.$guide->numero.'-'.$type);
            return ['mode' => 'zip', 'bytes' => $bytes, 'name' => substr($name, 0, 120).'.zip'];
        }
        $url = $guide->{$type.'_link'} ?: ($response['enlace_del_'.$type] ?? null);
        abort_unless($url, 404);
        return ['mode' => 'link', 'url' => self::providerUrl($url)];
    }

    public static function providerUrl($url)
    {
        if (!is_string($url)) { self::invalid(); }
        $url = trim($url);
        if (preg_match('/[\x00-\x20\x7f\\\\]/', rawurldecode($url)) || !filter_var($url, FILTER_VALIDATE_URL)) { self::invalid(); }
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || !($host === 'nubefact.com' || substr($host, -13) === '.nubefact.com')) { self::invalid(); }
        return preg_replace('/^http:/i', 'https:', $url);
    }

    public static function redactResponse(array $response)
    {
        foreach (['pdf_zip_base64','xml_zip_base64','cdr_zip_base64','enlace_del_pdf','enlace_del_xml','enlace_del_cdr','enlace'] as $field) { unset($response[$field]); }
        return $response;
    }

    private static function invalid()
    {
        throw ValidationException::withMessages(['document' => 'El documento guardado requiere revisión. Actualiza su estado con un usuario autorizado.']);
    }
}
