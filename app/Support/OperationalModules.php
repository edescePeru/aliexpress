<?php
namespace App\Support;

class OperationalModules
{
    private const MODULES = [
        'industrial-quotes' => ['enabled' => false, 'status' => 423,
            'message' => 'Las cotizaciones industriales antiguas están deshabilitadas. Utilice Cotizaciones de venta. Se conservan el código y los datos históricos.'],
        'legacy-output-requests' => ['enabled' => false, 'status' => 423,
            'message' => 'Las solicitudes antiguas de salida están deshabilitadas en esta versión. Se conservan su código y sus datos históricos.'],
        'purchase-express' => ['enabled' => false, 'status' => 423,
            'message' => 'Compras Express está deshabilitada. Se conservan su código y sus datos históricos. Utilice Compras normales.'],
        'legacy-settings' => ['enabled' => false, 'status' => 410,
            'message' => 'Datos Generales ya no se utiliza. Use Configuraciones de la empresa. Los datos anteriores se conservan.'],
        'legacy-access' => ['enabled' => false, 'status' => 423,
            'message' => 'Los accesos antiguos están deshabilitados. Los usuarios se administran desde Usuarios Web y los perfiles desde Platform Admin.'],
        'free-sale' => ['enabled' => false, 'status' => 423,
            'message' => 'Venta Libre está temporalmente deshabilitada. Se conservan su código y sus datos para una futura habilitación.'],
    ];

    public static function enabled(string $key): bool
    {
        return isset(self::MODULES[$key]) && self::MODULES[$key]['enabled'];
    }

    public static function definition(string $key): array
    {
        abort_unless(isset(self::MODULES[$key]), 404);
        return self::MODULES[$key];
    }
}
