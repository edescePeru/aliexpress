<?php

namespace App\Http\Controllers;

use App\DetailEntry;
use App\Entry;
use App\Item;
use App\Material;
use App\Typescrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\TenantContext;
use App\Location;

class EntryScrapsController extends Controller
{
    public function indexScrapsMaterials()
    {
        return view('scraps.index_materials_scrap');
    }

    public function getJsonIndexMaterialsScraps()
    {
        $materials = Material::with('typeScrap:id,name,length,width')
            ->whereNotNull('typescrap_id')
            ->where('stock_current', '>=', 0)
            ->where('enable_status', 1)
            ->get();

        return datatables($materials)->toJson();
    }

    public function showItemsByMaterial( $material_id )
    {
        $material = Material::find($material_id);
        return view('scraps.index_items_material', compact('material'));
    }

    public function getJsonIndexItemsMaterial($material_id)
    {
        $items = Item::with(['material'])
            ->with(['detailEntry' => function ($query) {
                $query->with(['entry']);
            }])
            ->where('material_id', $material_id)
            ->whereNotIn('state_item', ['exited', 'reserved'])
            ->orderBy('created_at', 'DESC')
            ->orderBy('state_item', 'ASC')
            ->get();
        //dd($items);

        return datatables($items)->toJson();
    }

    public function storeScrap(Request $request)
    {
        $materialId = (int) $request->get('material_id');
        $idItem = (int) $request->get('idItem');
        $typescrapId = (int) $request->get('typescrap');

        $price = (float) $request->get('price');

        $length = (float) $request->get('length');
        $width = (float) $request->get('width');

        $lengthNew = $request->get('length_new') === null
            ? 0
            : (float) $request->get('length_new');

        $widthNew = $request->get('width_new') === null
            ? 0
            : (float) $request->get('width_new');

        $state = $request->get('state');

        $blockAncho = (int) $request->get('blockAncho');
        $blockLargo = (int) $request->get('blockLargo');

        $companyId = TenantContext::companyId();

        DB::beginTransaction();

        try {

            /*
             * ============================================================
             * VALIDACIONES DE MEDIDAS
             * ============================================================
             */

            if (
                $typescrapId == 1 ||
                $typescrapId == 2 ||
                $typescrapId == 6
            ) {
                if ($lengthNew <= 0 || $widthNew <= 0) {
                    throw new \RuntimeException(
                        'Ingrese el largo y ancho mayores a cero.'
                    );
                }

                if ($blockAncho == 0 && $blockLargo == 0) {
                    throw new \RuntimeException(
                        'Bloquee una de las medidas para realizar el corte.'
                    );
                }

                if ($lengthNew > $length || $widthNew > $width) {
                    throw new \RuntimeException(
                        'El largo o ancho es incorrecto, creará ítems negativos.'
                    );
                }
            }


            if (
                $typescrapId == 3 ||
                $typescrapId == 4 ||
                $typescrapId == 5
            ) {
                if ($lengthNew <= 0) {
                    throw new \RuntimeException(
                        'Ingrese el largo mayor a cero.'
                    );
                }

                if ($lengthNew >= $length) {
                    throw new \RuntimeException(
                        'El largo es incorrecto, creará ítems negativos.'
                    );
                }
            }


            /*
             * ============================================================
             * ITEM ORIGINAL
             * ============================================================
             *
             * Item ya es Tenant-aware.
             * Además exigimos que pertenezca a la Company actual.
             */

            $itemSelected = Item::query()
                ->where('company_id', $companyId)
                ->where('id', $idItem)
                ->lockForUpdate()
                ->first();

            if (!$itemSelected) {
                throw new \RuntimeException(
                    'El ítem seleccionado no pertenece a la empresa actual.'
                );
            }


            /*
             * Defensa contra manipulación del material desde frontend.
             */

            if (
                $materialId > 0 &&
                (int) $itemSelected->material_id !== $materialId
            ) {
                throw new \RuntimeException(
                    'El material enviado no corresponde al ítem seleccionado.'
                );
            }


            /*
             * Para mantener la nueva trazabilidad, el Item origen
             * debe tener StockItem y StockLot.
             */

            if (!$itemSelected->stock_item_id) {
                throw new \RuntimeException(
                    'El ítem seleccionado no tiene StockItem asociado.'
                );
            }

            if (!$itemSelected->stock_lot_id) {
                throw new \RuntimeException(
                    'El ítem seleccionado no tiene lote de stock asociado.'
                );
            }

            if (!$itemSelected->warehouse_id || !$itemSelected->location_id) {
                throw new \RuntimeException(
                    'El ítem seleccionado no tiene almacén o ubicación asociados.'
                );
            }


            /*
             * ============================================================
             * MATERIAL
             * ============================================================
             */

            $materialSelected = Material::find(
                $itemSelected->material_id
            );

            if (!$materialSelected) {
                throw new \RuntimeException(
                    'No se encontró el material asociado al ítem.'
                );
            }


            /*
             * ============================================================
             * TIPO DE RETAZO
             * ============================================================
             */

            $typescrapSelected = Typescrap::find(
                $typescrapId
            );

            if (!$typescrapSelected) {
                throw new \RuntimeException(
                    'No se encontró el tipo de retazo seleccionado.'
                );
            }


            /*
             * Ubicación física heredada del Item origen.
             * NO usamos Location enviada por frontend.
             */
            $warehouseId = (int) $itemSelected->warehouse_id;
            $locationId = (int) $itemSelected->location_id;


            /*
             * ============================================================
             * PLANCHAS / FORMAS CON LARGO Y ANCHO
             * ============================================================
             */

            if (
                $typescrapId == 1 ||
                $typescrapId == 2 ||
                $typescrapId == 6
            ) {

                $newCode = $this->generateRandomString(25);

                $areaComplete = round(
                    (float) $typescrapSelected->length
                    *
                    (float) $typescrapSelected->width,
                    2
                );

                if ($areaComplete <= 0) {
                    throw new \RuntimeException(
                        'Las dimensiones del tipo de retazo son inválidas.'
                    );
                }

                $areaScrap = round(
                    $lengthNew * $widthNew,
                    2
                );

                $percentageNew = round(
                    $areaScrap / $areaComplete,
                    2
                );

                $priceNew = round(
                    $price * $percentageNew,
                    2
                );


                $percentageOld = 0;
                $priceOld = 0;
                $lengthOld = 0;
                $widthOld = 0;


                /*
                 * Se bloqueó el ancho:
                 * se realiza el corte sobre el largo.
                 */
                if ($blockAncho == 1) {

                    $areaOld = round(
                        (
                            (float) $itemSelected->length
                            -
                            $lengthNew
                        )
                        *
                        (float) $itemSelected->width,
                        2
                    );

                    $percentageOld = round(
                        $areaOld / $areaComplete,
                        2
                    );

                    $priceOld = round(
                        $price * $percentageOld,
                        2
                    );

                    $lengthOld = round(
                        (float) $itemSelected->length
                        -
                        $lengthNew,
                        2
                    );

                    $widthOld = round(
                        (float) $itemSelected->width,
                        2
                    );
                }


                /*
                 * Se bloqueó el largo:
                 * se realiza el corte sobre el ancho.
                 */
                if ($blockLargo == 1) {

                    $areaOld = round(
                        (float) $itemSelected->length
                        *
                        (
                            (float) $itemSelected->width
                            -
                            $widthNew
                        ),
                        2
                    );

                    $percentageOld = round(
                        $areaOld / $areaComplete,
                        2
                    );

                    $priceOld = round(
                        $price * $percentageOld,
                        2
                    );

                    $lengthOld = round(
                        (float) $itemSelected->length,
                        2
                    );

                    $widthOld = round(
                        (float) $itemSelected->width
                        -
                        $widthNew,
                        2
                    );
                }


                /*
                 * ========================================================
                 * ITEM AÚN DENTRO DEL INVENTARIO
                 * ========================================================
                 */

                if ($itemSelected->state_item !== 'exited') {

                    /*
                     * Compatibilidad legacy.
                     *
                     * TODO RETACERÍA:
                     * Material.stock_current debe dejar de utilizarse
                     * cuando migremos completamente este módulo a
                     * StockLot + InventoryLevel.
                     */

                    $materialSelected->stock_current =
                        (float) $materialSelected->stock_current
                        -
                        (float) $itemSelected->percentage;

                    $materialSelected->save();


                    $materialSelected->stock_current =
                        (float) $materialSelected->stock_current
                        +
                        $percentageOld
                        +
                        $percentageNew;

                    $materialSelected->save();


                    /*
                     * Actualizar el Item original con la parte restante.
                     */

                    $itemSelected->length = $lengthOld;
                    $itemSelected->width = $widthOld;
                    $itemSelected->price = $priceOld;
                    $itemSelected->percentage = $percentageOld;
                    $itemSelected->state_item = 'scraped';
                    $itemSelected->save();
                }


                /*
                 * ========================================================
                 * ITEM QUE YA HABÍA SALIDO
                 * ========================================================
                 */

                if ($itemSelected->state_item === 'exited') {

                    /*
                     * Compatibilidad legacy.
                     *
                     * TODO SCRAP-01:
                     * Esta reincorporación debe actualizar StockLot e
                     * InventoryLevel cuando migremos completamente
                     * Retacería.
                     */

                    $materialSelected->stock_current =
                        (float) $materialSelected->stock_current
                        +
                        $percentageNew;

                    $materialSelected->save();
                }


                /*
                 * ========================================================
                 * CREAR ENTRADA DE RETACERÍA
                 * ========================================================
                 */

                $entry = Entry::create([
                    'entry_type' => 'Retacería',
                    'date_entry' => Carbon::now(),
                    'finance' => false,
                ]);


                $detailEntry = DetailEntry::create([
                    'entry_id' => $entry->id,

                    'material_id' =>
                        $itemSelected->material_id,

                    'stock_item_id' =>
                        $itemSelected->stock_item_id,
                ]);


                /*
                 * ========================================================
                 * CREAR NUEVO ITEM
                 * ========================================================
                 *
                 * Hereda toda su trazabilidad física del Item original.
                 */

                Item::create([
                    'company_id' =>
                        $itemSelected->company_id,

                    'detail_entry_id' =>
                        $detailEntry->id,

                    'stock_item_id' =>
                        $itemSelected->stock_item_id,

                    'stock_lot_id' =>
                        $itemSelected->stock_lot_id,

                    'material_id' =>
                        $itemSelected->material_id,

                    'code' =>
                        $newCode,

                    'length' =>
                        $lengthNew,

                    'width' =>
                        $widthNew,

                    'weight' =>
                        0,

                    'price' =>
                        $priceNew,

                    'unit_cost' =>
                        (float) $itemSelected->unit_cost,

                    'percentage' =>
                        $percentageNew,

                    'typescrap_id' =>
                        $typescrapSelected->id,

                    'warehouse_id' =>
                        $warehouseId,

                    'location_id' =>
                        $locationId,

                    'state' =>
                        $state ?: $itemSelected->state,

                    'state_item' =>
                        'scraped',
                ]);
            }


            /*
             * ============================================================
             * TUBOS / ELEMENTOS QUE SOLO UTILIZAN LARGO
             * ============================================================
             */

            if (
                $typescrapId == 3 ||
                $typescrapId == 4 ||
                $typescrapId == 5
            ) {

                $newCode = $this->generateRandomString(25);


                if ((float) $typescrapSelected->length <= 0) {
                    throw new \RuntimeException(
                        'El largo del tipo de retazo es inválido.'
                    );
                }


                $percentageNew = round(
                    $lengthNew
                    /
                    (float) $typescrapSelected->length,
                    2
                );

                $priceNew = round(
                    $price * $percentageNew,
                    2
                );


                $lengthOld = round(
                    (float) $itemSelected->length
                    -
                    $lengthNew,
                    2
                );

                $percentageOld = round(
                    $lengthOld
                    /
                    (float) $typescrapSelected->length,
                    2
                );

                $priceOld = round(
                    $price * $percentageOld,
                    2
                );


                /*
                 * ========================================================
                 * ITEM AÚN DENTRO DEL INVENTARIO
                 * ========================================================
                 */

                if ($itemSelected->state_item !== 'exited') {

                    $materialSelected->stock_current =
                        (float) $materialSelected->stock_current
                        -
                        (float) $itemSelected->percentage;

                    $materialSelected->save();


                    $materialSelected->stock_current =
                        (float) $materialSelected->stock_current
                        +
                        $percentageOld
                        +
                        $percentageNew;

                    $materialSelected->save();


                    $itemSelected->length = $lengthOld;
                    $itemSelected->price = $priceOld;
                    $itemSelected->percentage = $percentageOld;
                    $itemSelected->state_item = 'scraped';

                    $itemSelected->save();
                }


                /*
                 * ========================================================
                 * ITEM QUE YA HABÍA SALIDO
                 * ========================================================
                 */

                if ($itemSelected->state_item === 'exited') {

                    /*
                     * TODO SCRAP-01:
                     * Posteriormente debe reincorporarse también en
                     * StockLot + InventoryLevel.
                     */

                    $materialSelected->stock_current =
                        (float) $materialSelected->stock_current
                        +
                        $percentageNew;

                    $materialSelected->save();
                }


                /*
                 * ========================================================
                 * CREAR ENTRADA
                 * ========================================================
                 */

                $entry = Entry::create([
                    'entry_type' => 'Retacería',
                    'date_entry' => Carbon::now(),
                    'finance' => false,
                ]);


                $detailEntry = DetailEntry::create([
                    'entry_id' =>
                        $entry->id,

                    'material_id' =>
                        $itemSelected->material_id,

                    'stock_item_id' =>
                        $itemSelected->stock_item_id,
                ]);


                /*
                 * ========================================================
                 * CREAR NUEVO ITEM
                 * ========================================================
                 */

                Item::create([
                    'company_id' =>
                        $itemSelected->company_id,

                    'detail_entry_id' =>
                        $detailEntry->id,

                    'stock_item_id' =>
                        $itemSelected->stock_item_id,

                    'stock_lot_id' =>
                        $itemSelected->stock_lot_id,

                    'material_id' =>
                        $itemSelected->material_id,

                    'code' =>
                        $newCode,

                    'length' =>
                        $lengthNew,

                    'width' =>
                        0,

                    'weight' =>
                        0,

                    'price' =>
                        $priceNew,

                    'unit_cost' =>
                        (float) $itemSelected->unit_cost,

                    'percentage' =>
                        $percentageNew,

                    'typescrap_id' =>
                        $typescrapSelected->id,

                    'warehouse_id' =>
                        $warehouseId,

                    'location_id' =>
                        $locationId,

                    'state' =>
                        $state ?: $itemSelected->state,

                    'state_item' =>
                        'scraped',
                ]);
            }


            DB::commit();

            return response()->json([
                'message' => 'Retazo guardado con éxito.',
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getJsonDataMaterial( $material_id )
    {
        $material = Material::with('typeScrap')
            ->find($material_id);
        return json_encode($material);
    }

    public function storeNewScrap(Request $request)
    {
        $materialId =
            (int) $request->get('material_id_nuevo');

        $price =
            (float) $request->get('price_nuevo');

        $typescrapId =
            (int) $request->get('typescrap_nuevo');

        $code =
            trim((string) $request->get('code_nuevo'));

        $lengthNew =
            $request->get('length_new_nuevo') === null
                ? 0
                : (float) $request->get('length_new_nuevo');

        $widthNew =
            $request->get('width_new_nuevo') === null
                ? 0
                : (float) $request->get('width_new_nuevo');

        $locationId =
            (int) $request->get('location_nuevo');

        $state =
            $request->get('state_nuevo');

        $companyId =
            TenantContext::companyId();


        DB::beginTransaction();

        try {

            /*
             * ============================================================
             * VALIDACIONES BÁSICAS
             * ============================================================
             */

            if (!$materialId) {
                throw new \RuntimeException(
                    'Debe seleccionar un material.'
                );
            }

            if (!$typescrapId) {
                throw new \RuntimeException(
                    'Debe seleccionar un tipo de retazo.'
                );
            }

            if (!$locationId) {
                throw new \RuntimeException(
                    'Debe seleccionar una ubicación.'
                );
            }


            /*
             * ============================================================
             * LOCATION / WAREHOUSE DE LA COMPANY ACTUAL
             * ============================================================
             */

            $location = Location::query()
                ->where('id', $locationId)
                ->where('company_id', $companyId)
                ->first();

            if (!$location) {
                throw new \RuntimeException(
                    'La ubicación seleccionada no pertenece a la empresa actual.'
                );
            }


            $warehouseId =
                (int) $location->warehouse_id;

            if (!$warehouseId) {
                throw new \RuntimeException(
                    'La ubicación seleccionada no tiene un almacén asociado.'
                );
            }


            /*
             * ============================================================
             * MATERIAL
             * ============================================================
             *
             * Material ya está protegido por TenantScope.
             */

            $materialSelected =
                Material::find($materialId);

            if (!$materialSelected) {
                throw new \RuntimeException(
                    'No se encontró el material seleccionado.'
                );
            }


            /*
             * ============================================================
             * TIPO DE RETAZO
             * ============================================================
             */

            $typescrapSelected =
                Typescrap::find($typescrapId);

            if (!$typescrapSelected) {
                throw new \RuntimeException(
                    'No se encontró el tipo de retazo seleccionado.'
                );
            }


            /*
             * ============================================================
             * VALIDACIONES DE MEDIDAS
             * ============================================================
             */

            if (
                $typescrapId == 1 ||
                $typescrapId == 2 ||
                $typescrapId == 6
            ) {
                if ($lengthNew <= 0 || $widthNew <= 0) {
                    throw new \RuntimeException(
                        'Ingrese el largo y ancho mayores a cero.'
                    );
                }
            }


            if (
                $typescrapId == 3 ||
                $typescrapId == 4 ||
                $typescrapId == 5
            ) {
                if ($lengthNew <= 0) {
                    throw new \RuntimeException(
                        'Ingrese el largo mayor a cero.'
                    );
                }
            }


            /*
             * ============================================================
             * CÓDIGO
             * ============================================================
             */

            if ($code === '') {
                $code =
                    $this->generateRandomString(25);
            }


            /*
             * ============================================================
             * PLANCHAS / RETAZOS CON LARGO Y ANCHO
             * ============================================================
             */

            if (
                $typescrapId == 1 ||
                $typescrapId == 2 ||
                $typescrapId == 6
            ) {

                $areaComplete = round(
                    (float) $typescrapSelected->length
                    *
                    (float) $typescrapSelected->width,
                    2
                );

                if ($areaComplete <= 0) {
                    throw new \RuntimeException(
                        'Las dimensiones del tipo de retazo son inválidas.'
                    );
                }


                $areaScrap = round(
                    $lengthNew * $widthNew,
                    2
                );

                $percentageNew = round(
                    $areaScrap / $areaComplete,
                    2
                );

                $priceNew = round(
                    $price * $percentageNew,
                    2
                );


                /*
                 * Compatibilidad legacy.
                 *
                 * TODO SCRAP-02:
                 * Este flujo debe migrarse posteriormente a
                 * StockItem + StockLot + InventoryLevel.
                 */

                $materialSelected->stock_current =
                    (float) $materialSelected->stock_current
                    +
                    $percentageNew;

                $materialSelected->save();


                $entry = Entry::create([
                    'entry_type' =>
                        'Retacería',

                    'date_entry' =>
                        Carbon::now(),

                    'finance' =>
                        false,
                ]);


                $detailEntry = DetailEntry::create([
                    'entry_id' =>
                        $entry->id,

                    'material_id' =>
                        $materialSelected->id,
                ]);


                Item::create([
                    /*
                     * Tenant se asigna mediante BelongsToTenant.
                     */

                    'company_id' =>
                        $companyId,

                    'detail_entry_id' =>
                        $detailEntry->id,

                    /*
                     * TODO SCRAP-02:
                     * stock_item_id y stock_lot_id deben incorporarse
                     * al migrar completamente Retacería.
                     */

                    'material_id' =>
                        $materialSelected->id,

                    'code' =>
                        $code,

                    'length' =>
                        $lengthNew,

                    'width' =>
                        $widthNew,

                    'weight' =>
                        0,

                    'price' =>
                        $priceNew,

                    'unit_cost' =>
                        0,

                    'percentage' =>
                        $percentageNew,

                    'typescrap_id' =>
                        $typescrapSelected->id,

                    'warehouse_id' =>
                        $warehouseId,

                    'location_id' =>
                        $location->id,

                    'state' =>
                        $state ?: 'good',

                    'state_item' =>
                        'scraped',
                ]);
            }


            /*
             * ============================================================
             * TUBOS / RETAZOS QUE SOLO UTILIZAN LARGO
             * ============================================================
             */

            if (
                $typescrapId == 3 ||
                $typescrapId == 4 ||
                $typescrapId == 5
            ) {

                if ((float) $typescrapSelected->length <= 0) {
                    throw new \RuntimeException(
                        'El largo del tipo de retazo es inválido.'
                    );
                }


                $percentageNew = round(
                    $lengthNew
                    /
                    (float) $typescrapSelected->length,
                    2
                );

                $priceNew = round(
                    $price * $percentageNew,
                    2
                );


                /*
                 * Compatibilidad legacy.
                 */

                $materialSelected->stock_current =
                    (float) $materialSelected->stock_current
                    +
                    $percentageNew;

                $materialSelected->save();


                $entry = Entry::create([
                    'entry_type' =>
                        'Retacería',

                    'date_entry' =>
                        Carbon::now(),

                    'finance' =>
                        false,
                ]);


                $detailEntry = DetailEntry::create([
                    'entry_id' =>
                        $entry->id,

                    'material_id' =>
                        $materialSelected->id,
                ]);


                Item::create([
                    'company_id' =>
                        $companyId,

                    'detail_entry_id' =>
                        $detailEntry->id,

                    'material_id' =>
                        $materialSelected->id,

                    'code' =>
                        $code,

                    'length' =>
                        $lengthNew,

                    'width' =>
                        0,

                    'weight' =>
                        0,

                    'price' =>
                        $priceNew,

                    'unit_cost' =>
                        0,

                    'percentage' =>
                        $percentageNew,

                    'typescrap_id' =>
                        $typescrapSelected->id,

                    'warehouse_id' =>
                        $warehouseId,

                    'location_id' =>
                        $location->id,

                    'state' =>
                        $state ?: 'good',

                    'state_item' =>
                        'scraped',
                ]);
            }


            DB::commit();

            return response()->json([
                'message' =>
                    'Retazo guardado con éxito.',
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }
}
