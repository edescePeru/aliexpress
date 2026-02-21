<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Category;
use App\CategoryInvoice;
use App\DataGeneral;
use App\DiscountQuantity;
use App\Exampler;
use App\Genero;
use App\Http\Requests\DeleteMaterialRequest;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Location;
use App\Material;
use App\MaterialDetailSetting;
use App\MaterialDiscountQuantity;
use App\MaterialType;
use App\MaterialUnpack;
use App\MaterialVencimiento;
use App\Quality;
use App\Shelf;
use App\Specification;
use App\Item;
use APP\DetailEntry;
use App\StoreMaterial;
use App\StoreMaterialLocation;
use App\StoreMaterialVencimiento;
use App\Subcategory;
use App\Subtype;
use App\Talla;
use App\TipoVenta;
use App\Typescrap;
use App\UnitMeasure;
use App\Warrant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class MaterialController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.index', compact('permissions'));
    }

    public function listarActivosFijos()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.listarActivosIndex', compact('permissions'));
    }

    public function create()
    {
        /*$categories = Category::orderBy('name', 'asc')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $warrants = Warrant::orderBy('name', 'asc')->get();
        $qualities = Quality::orderBy('name', 'asc')->get();
        $typescraps = Typescrap::orderBy('name', 'asc')->get();
        $unitMeasures = UnitMeasure::orderBy('name', 'asc')->get();
        $generos = Genero::orderBy('name', 'asc')->get();
        $tallas = Talla::orderBy('name', 'asc')->get();
        $tipoVentas = TipoVenta::orderBy('description', 'asc')->get();
        $discountQuantities = DiscountQuantity::all();
        return view('material.create', compact('discountQuantities', 'tipoVentas','tallas','generos', 'categories', 'warrants', 'brands', 'qualities', 'typescraps', 'unitMeasures'));*/
        // 1) Leer configuración global
        $setting = MaterialDetailSetting::first();
        $enabled = [];
        if ($setting && is_array($setting->enabled_sections)) {
            $enabled = $setting->enabled_sections;
        }

        // 2) Siempre (porque ya los usas sí o sí hoy)
        $warrants = Warrant::orderBy('name', 'asc')->get();
        $qualities = Quality::orderBy('name', 'asc')->get();
        $typescraps = Typescrap::orderBy('name', 'asc')->get();
        $tipoVentas = TipoVenta::orderBy('description', 'asc')->get();
        $discountQuantities = DiscountQuantity::all();

        // 3) Condicionales según configuración
        $categories = in_array('category', $enabled, true)
            ? Category::orderBy('name', 'asc')->get()
            : collect();

        $brands = in_array('brand', $enabled, true)
            ? Brand::orderBy('name', 'asc')->get()
            : collect();

        $unitMeasures = in_array('unit_measure', $enabled, true)
            ? UnitMeasure::orderBy('name', 'asc')->get()
            : collect();

        $generos = in_array('genero', $enabled, true)
            ? Genero::orderBy('name', 'asc')->get()
            : collect();

        $tallas = in_array('talla', $enabled, true)
            ? Talla::orderBy('name', 'asc')->get()
            : collect();

        // Si luego agregas modelo/subcategoría, haces lo mismo:
        // $examplers = in_array('exampler', $enabled, true) ? Exampler::orderBy(...) : collect();
        // $subcategories = in_array('subcategory', $enabled, true) ? Subcategory::orderBy(...) : collect();

        return view('material.create', compact(
            'enabled',
            'discountQuantities',
            'tipoVentas',
            'tallas',
            'generos',
            'categories',
            'warrants',
            'brands',
            'qualities',
            'typescraps',
            'unitMeasures'
        ));
    }

    public function store(StoreMaterialRequest $request)
    {
        //dd($request);
        $validated = $request->validated();
        $mat = null;
        DB::beginTransaction();
        try {

            $material = Material::create([
                'description' => $request->get('description'),
                'unit_measure_id' => $request->get('unit_measure'),
                'stock_max' => $request->get('stock_max'),
                'stock_min' => $request->get('stock_min'),
                'unit_price' => $request->get('unit_price'),
                'stock_current' => 0,
                'priority' => 'Aceptable',
                'category_id' => $request->get('category'),
                'subcategory_id' => $request->get('subcategory'),
                'brand_id' => $request->get('brand'),
                'exampler_id' => $request->get('exampler'),
                'typescrap_id' => $request->get('typescrap'),
                'enable_status' => true,
                'codigo' => $request->get('codigo'),
                'warrant_id' => $request->get('genero'),
                'quality_id' => $request->get('talla'),
                'tipo_venta_id' => $request->get('tipo_venta'),
                'perecible' => $request->get('perecible'),
                'full_name' => $request->get('name'),
                'list_price' => (float)($request->get('unit_price')),
                'isPack' => $request->has('pack') ? 1 : 0,
                'quantityPack' => $request->has('pack') ? $request->get('inputPack') : 0,
            ]);

            $length = 5;
            $string = $material->id;
            $code = 'P-'.str_pad($string,$length,"0", STR_PAD_LEFT);
            //output: 0012345

            $material->code = $code;
            $material->save();

            // TODO: Tratamiento de un archivo de forma tradicional
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = $material->id . '.' . $image->getClientOriginalExtension();
                $path = public_path('images/material/' . $filename);

                // Guardar sin modificar tamaño
                Image::make($image)->save($path);

                $material->image = $filename;
                $material->save();
            } else {
                $material->image = 'no_image.png';
                $material->save();
            }

            //$mat = $material;
            // TODO: Guardar las promociones
            $discounts = $request->input('discount', []);
            $percentages = $request->input('percentage', []);

            foreach ($discounts as $discountId => $value) {
                // Verificamos si el checkbox fue marcado
                if (isset($value)) {
                    // Obtenemos el porcentaje correspondiente
                    $percentage = isset($percentages[$discountId]) ? $percentages[$discountId] : null;

                    // Guardamos la información en la base de datos
                    MaterialDiscountQuantity::create([
                        'material_id' => $material->id,
                        'discount_quantity_id' => $discountId,
                        'percentage' => $percentage
                    ]);
                }
            }
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        /*$mat->full_name = $material->full_description;
        $mat->save();*/

        return response()->json(['message' => 'Material guardado con éxito.'], 200);

    }

    public function show(Material $material)
    {
        //
    }

    public function edit($id)
    {
        /*$specifications = Specification::where('material_id', $id)->get();
        $brands = Brand::all();
        $categories = Category::all();
        $materialTypes = MaterialType::all();
        $material = Material::with(['category', 'materialType', ])->find($id);

        $typescraps = Typescrap::all();
        $unitMeasures = UnitMeasure::all();
        $generos = Genero::all();
        $tallas = Talla::all();
        $warrants = Warrant::all();
        $qualities = Quality::all();
        $tipoVentas = TipoVenta::all();
        $discountQuantities = DiscountQuantity::all();
        $materialsDiscounts = MaterialDiscountQuantity::where('material_id', $id)
            ->get()
            ->keyBy('discount_quantity_id')
            ->map(function($item) {
                return $item->percentage;
            })
            ->toArray();

        return view('material.edit', compact('materialsDiscounts', 'discountQuantities', 'generos','tallas','tipoVentas','unitMeasures','typescraps','qualities','warrants','specifications', 'brands', 'categories', 'materialTypes', 'material'));*/
// 1) Configuración global
        $setting = MaterialDetailSetting::first();
        $enabled = [];
        if ($setting && is_array($setting->enabled_sections)) {
            $enabled = $setting->enabled_sections;
        }

        // 2) Datos del material
        $material = Material::with(['category', 'materialType'])->find($id);

        $specifications = Specification::where('material_id', $id)->get();

        $materialTypes = MaterialType::all();

        // 3) Listas condicionales (igual que create)
        $brands = in_array('brand', $enabled, true) ? Brand::orderBy('name', 'asc')->get() : collect();
        $categories = in_array('category', $enabled, true) ? Category::orderBy('name', 'asc')->get() : collect();
        $unitMeasures = in_array('unit_measure', $enabled, true) ? UnitMeasure::orderBy('name', 'asc')->get() : collect();
        $generos = in_array('genero', $enabled, true) ? Genero::orderBy('description', 'asc')->get() : collect();
        $tallas = in_array('talla', $enabled, true) ? Talla::orderBy('name', 'asc')->get() : collect();

        // 4) Dependientes para EDIT (para que venga preseleccionado)
        $examplers = collect();
        if (in_array('exampler', $enabled, true)) {
            // si no está habilitado brand, igual puede pasar por config; pero store settings ya debe forzarlo
            $brandId = null;

            // Ajusta el nombre del campo según tu DB
            if ($material) {
                if (isset($material->brand_id)) $brandId = $material->brand_id;
                elseif (isset($material->brand)) $brandId = $material->brand;
            }

            if (!empty($brandId)) {
                $examplers = Exampler::where('brand_id', $brandId)->orderBy('name','asc')->get();
            }
        }

        $subcategories = collect();
        if (in_array('subcategory', $enabled, true)) {
            $categoryId = null;

            if ($material) {
                if (isset($material->category_id)) $categoryId = $material->category_id;
                elseif (isset($material->category)) $categoryId = $material->category;
            }

            if (!empty($categoryId)) {
                $subcategories = Subcategory::where('category_id', $categoryId)->orderBy('name','asc')->get();
            }
        }

        // 5) Lo demás que ya tenías (no parametrizable por ahora)
        $typescraps = Typescrap::all();
        $warrants = Warrant::all();
        $qualities = Quality::all();
        $tipoVentas = TipoVenta::all();
        $discountQuantities = DiscountQuantity::all();

        $materialsDiscounts = MaterialDiscountQuantity::where('material_id', $id)
            ->get()
            ->keyBy('discount_quantity_id')
            ->map(function ($item) {
                return $item->percentage;
            })
            ->toArray();

        return view('material.edit', compact(
            'enabled',
            'materialsDiscounts',
            'discountQuantities',
            'generos',
            'tallas',
            'tipoVentas',
            'unitMeasures',
            'typescraps',
            'qualities',
            'warrants',
            'specifications',
            'brands',
            'categories',
            'materialTypes',
            'material',
            'examplers',
            'subcategories'
        ));
    }

    public function update(UpdateMaterialRequest $request)
    {
        //dd($request->get('typescrap'));
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $material = Material::find($request->get('material_id'));

            $material->full_name = $request->get('name');
            $material->description = $request->get('description');
            $material->unit_measure_id = $request->get('unit_measure');
            $material->stock_max = $request->get('stock_max');
            $material->stock_min = $request->get('stock_min');
            $material->unit_price = $request->get('unit_price');
            $material->stock_current = $request->get('stock_current');
            $material->category_id = $request->get('category');
            $material->subcategory_id = $request->get('subcategory');
            $material->brand_id = $request->get('brand');
            $material->exampler_id = $request->get('exampler');
            $material->typescrap_id = $request->get('typescrap');
            $material->warrant_id = $request->get('genero');
            $material->quality_id = $request->get('talla');
            $material->tipo_venta_id = $request->get('tipo_venta');
            $material->perecible = $request->get('perecible');
            $material->codigo = $request->get('codigo');
            //$material->list_price = (float)($request->get('unit_price'));
            $material->isPack = $request->has('pack') ? 1 : 0;
            $material->quantityPack = $request->has('pack') ? $request->get('inputPack') : 0;
            $material->save();

            // TODO: Tratamiento de un archivo de forma tradicional
            if (!$request->file('image')) {
                if ($material->image == 'no_image.png' || $material->image == null) {
                    $material->image = 'no_image.png';
                    $material->save();
                }
            } else {
                $path = public_path().'/images/material/';
                $extension = $request->file('image')->getClientOriginalExtension();
                $filename = $material->id . '.' . $extension;
                $request->file('image')->move($path, $filename);
                $material->image = $filename;
                $material->save();
            }

            if ($material->wasChanged('typescrap_id') )
            {
                if ( $request->get('typescrap') != null )
                {
                    $typeScrap = Typescrap::find($request->get('typescrap'));
                    $items = Item::where('material_id', $material->id)
                        ->whereIn('state_item', ['entered', 'exited'])
                        ->get();
                    foreach ( $items as $item )
                    {
                        $item->length = (float)$typeScrap->length;
                        $item->width = (float)$typeScrap->width;
                        $item->typescrap_id = $typeScrap->id;
                        $item->save();
                    }
                }
            }

            if ($material->wasChanged('unit_price') )
            {
                $material->date_update_price = Carbon::now("America/Lima");
                $material->state_update_price = 1;
                $material->save();
            }

            // TODO: Guardar las promociones
            $old_discounts = MaterialDiscountQuantity::where('material_id',$material->id)->get();
            foreach ( $old_discounts as $discount )
            {
                $discount->delete();
            }

            $discounts = $request->input('discount', []);
            $percentages = $request->input('percentage', []);

            foreach ($discounts as $discountId => $value) {
                // Verificamos si el checkbox fue marcado
                if (isset($value)) {
                    // Obtenemos el porcentaje correspondiente
                    $percentage = isset($percentages[$discountId]) ? $percentages[$discountId] : null;

                    // Guardamos la información en la base de datos
                    MaterialDiscountQuantity::create([
                        'material_id' => $material->id,
                        'discount_quantity_id' => $discountId,
                        'percentage' => $percentage
                    ]);
                }
            }

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cambios guardados con éxito.'], 200);

    }

    public function destroy(DeleteMaterialRequest $request)
    {
        $validated = $request->validated();

        $material = Material::find($request->get('material_id'));
        Specification::where('material_id', $request->get('material_id'))->delete();

        $material->delete();

        return response()->json(['message' => 'Material eliminado con éxito.'], 200);

    }

    public function getDataMaterials(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description = $request->input('description');
        $code = $request->input('code');
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $material_type = $request->input('material_type');
        $sub_type = $request->input('sub_type');
        $cedula = $request->input('cedula');
        $calidad = $request->input('calidad');
        $marca = $request->input('marca');
        $retaceria = $request->input('retaceria');
        $rotation = $request->input('rotation');
        $isPack = $request->input('isPack');

        $query = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            /*->where('category_id', '<>', 8)*/
            /*->orderBy('rotation', "desc")*/
            ->orderBy('id');

        // Aplicar filtros si se proporcionan
        if ($description != "") {
            // Convertir la cadena de búsqueda en un array de palabras clave
            $keywords = explode(' ', $description);

            // Construir la consulta para buscar todas las palabras clave en el campo full_name
            $query->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where('full_name', 'LIKE', '%' . $keyword . '%');
                }
            });

            // Asegurarse de que todas las palabras clave estén presentes en la descripción
            foreach ($keywords as $keyword) {
                $query->where('full_name', 'LIKE', '%' . $keyword . '%');
            }
        }

        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');
        }

        if ($category != "") {
            $query->where('category_id', $category);
        }

        if ($subcategory != "") {
            $query->where('subcategory_id', $subcategory);
        }

        if ($material_type != "") {
            $query->where('material_type_id', $material_type);
        }

        if ($sub_type != "") {
            $query->where('subtype_id', $sub_type);
        }

        if ($cedula != "") {
            $query->where('warrant_id', $cedula);
        }

        if ($calidad != "") {
            $query->where('quality_id', $calidad);
        }

        if ($marca != "") {
            $query->where('brand_id', $marca);
        }

        if ($retaceria != "") {
            $query->where('typescrap_id', $retaceria);
        }

        if ( $rotation != "" ) {
            $query->where('rotation', $rotation);
        }

        if ( $isPack != "" ) {
            $query->where('isPack', $isPack);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $materials = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $rotacion = "";
            if ( $material->rotation == "a" )
            {
                $rotacion = '<span class="badge bg-success text-md">ALTA</span>';
            } elseif ( $material->rotation == "m" ) {
                $rotacion = '<span class="badge bg-warning text-md">MEDIA</span>';
            } else {
                $rotacion = '<span class="badge bg-danger text-md">BAJA</span>';
            }

            array_push($array, [
                "id" => $material->id,
                "codigo" => $material->code,
                "descripcion" => $material->full_name,
                "medida" => $material->measure,
                "unidad_medida" => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                "stock_max" => $material->stock_max,
                "stock_min" => $material->stock_min,
                "stock_actual" => $material->stock_current,
                "prioridad" => $priority,
                "precio_unitario" => $material->unit_price,
                "precio_lista" => $material->list_price,
                "categoria" => ($material->category == null) ? '': $material->category->name,
                "sub_categoria" => ($material->subcategory == null) ? '': $material->subcategory->name,
                "tipo" => ($material->materialType == null) ? '': $material->materialType->name,
                "sub_tipo" => ($material->subType == null) ? '': $material->subType->name,
                "cedula" => ($material->warrant == null) ? '':$material->warrant->name,
                "calidad" => ($material->quality == null) ? '': $material->quality->name,
                "marca" => ($material->brand == null) ? '': $material->brand->name,
                "modelo" => ($material->exampler == null) ? '': $material->exampler->name,
                "retaceria" => ($material->typeScrap == null) ? '':$material->typeScrap->name,
                "image" => ($material->image == null || $material->image == "" ) ? 'no_image.png':$material->image,
                "rotation" => $rotacion,
                "update_price" => $material->state_update_price,
                "isPack" => $material->isPack
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function indexV2()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $arrayCategories = Category::where('id', '<>', 8)->select('id', 'name')->get()->toArray();

        $arrayCedulas = Warrant::select('id', 'name')->get()->toArray();

        $arrayCalidades = Quality::select('id', 'name')->get()->toArray();

        $arrayMarcas = Brand::select('id', 'name')->get()->toArray();

        $arrayRetacerias = Typescrap::select('id', 'name')->get()->toArray();

        $arrayRotations = [
            ["value" => "a", "display" => "ALTA"],
            ["value" => "m", "display" => "MEDIA"],
            ["value" => "b", "display" => "BAJA"]
        ];

        $materials = Material::where('isPack', 0)
            ->where('enable_status', 1)->get();

        //dd($array);

        $arrayMaterials = [];
        foreach ( $materials as $material )
        {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
            ]);
        }

        $rows = Material::where('enable_status', 1)->resumenPorMaterial()->get();

        // Calculamos el estado de stock
        foreach ($rows as $row) {
            if ($row->stock_total <= 0) {
                $row->estado = 'desabastecido';
            } elseif ($row->stock_total <= $row->stock_min) {
                $row->estado = 'por_desabastecer';
            } else {
                $row->estado = 'ok';
            }
        }

        $hayAlertas = $rows->contains(function ($r) {
            return $r->estado === 'desabastecido' || $r->estado === 'por_desabastecer';
        });

        return view('material.indexv2', compact( 'permissions', 'arrayCategories', 'arrayCedulas', 'arrayCalidades', 'arrayMarcas', 'arrayRetacerias', 'arrayRotations', 'arrayMaterials', 'rows', 'hayAlertas'));

    }

    public function getAllMaterials()
    {
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->where('category_id', '<>', 8)
            ->get();
            //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();


        //dd($materials);
        //dd(datatables($materials)->toJson());
        return datatables($materials)->toJson();
    }

    public function indexMaterialsActivos()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.indexActivosFijos', compact('permissions'));
    }

    public function getAllMaterialsActivosFijos()
    {
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->where('category_id', '=', 8)
            ->get();
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();

        $items = Item::with('material')->where('type', true)->get();
        $items_quantity = [];
        $materials_quantity = [];
        foreach ( $items as $item )
        {
            if ( $item->material->category_id != 8 )
            {
                array_push($items_quantity, array('material_id'=>$item->material_id,'quantity'=> (float)$item->percentage));
            }
        }

        $new_arr = array();
        foreach($items_quantity as $item) {
            if(isset($new_arr[$item['material_id']])) {
                $new_arr[ $item['material_id']]['quantity'] += (float)$item['quantity'];
                continue;
            }

            $new_arr[$item['material_id']] = $item;
        }

        $materials_quantity = array_values($new_arr);

        $arrayMaterials = [];

        foreach($materials_quantity as $mat) {
            $material = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
                ->where('enable_status', 1)
                ->find($mat['material_id']);
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'description' => $material->full_description,
                'code' => $material->code,
                'priority' => $material->priority,
                'measure' => $material->measure,
                'unit_measure' => ($material->unit_measure_id == null) ? '': $material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min'=>$material->stock_min,
                'quantity_items'=>$material->quantity_items,
                'stock_current'=>$mat['quantity'],
                'unit_price'=>$material->price_final,
                'image'=>$material->image,
                'category' => ($material->category_id == null) ? '': $material->category->name,
                'subcategory' => ($material->subcategory_id == null) ? '': $material->subcategory->name,
                'material_type' => ($material->material_type_id == null) ? '': $material->materialType->name,
                'sub_type' => ($material->sub_type_id == null) ? '': $material->sub_type->name,
                'warrant' => ($material->warrant_id == null) ? '': $material->warrant->name,
                'quality' => ($material->quality_id == null) ? '': $material->quality->name,
                'brand' => ($material->brand_id == null) ? '': $material->brand->name,
                'exampler' => ($material->exampler_id == null) ? '': $material->exampler->name,
                'type_scrap' => ($material->type_scrap_id == null) ? '': $material->typeScrap->name,
            ]);
        }

        foreach($materials as $material) {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'description' => $material->full_description,
                'code' => $material->code,
                'priority' => $material->priority,
                'measure' => $material->measure,
                'unit_measure' => ($material->unit_measure_id == null) ? '': $material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min'=>$material->stock_min,
                'quantity_items'=>$material->quantity_items,
                'stock_current'=>$material->quantity_items,
                'unit_price'=>$material->price_final,
                'image'=>$material->image,
                'category' => ($material->category_id == null) ? '': $material->category->name,
                'subcategory' => ($material->subcategory_id == null) ? '': $material->subcategory->name,
                'material_type' => ($material->material_type_id == null) ? '': $material->materialType->name,
                'sub_type' => ($material->sub_type_id == null) ? '': $material->sub_type->name,
                'warrant' => ($material->warrant_id == null) ? '': $material->warrant->name,
                'quality' => ($material->quality_id == null) ? '': $material->quality->name,
                'brand' => ($material->brand_id == null) ? '': $material->brand->name,
                'exampler' => ($material->exampler_id == null) ? '': $material->exampler->name,
                'type_scrap' => ($material->type_scrap_id == null) ? '': $material->typeScrap->name,
            ]);
        }

        //dd($arrayMaterials);
        //dd(datatables($materials)->toJson());
        return datatables($arrayMaterials)->toJson();
    }

    public function getAllMaterialsSinOp()
    {
        $begin = microtime(true);
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->get();
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();

        $end = microtime(true) - $begin;

        dump($end. ' segundos');
        dd();
        //dd(datatables($materials)->toJson());
        //return datatables($materials)->toJson();
    }

    public function getAllMaterialsOp()
    {
        $begin = microtime(true);
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->get();

        $array = [];

        foreach ($materials as $material) {
            array_push($array, [
                'id'=> $material->id,
                'description' => $material->full_description,
                'code' => $material->code,
                'priority' => $material->priority,
                'measure' => $material->measure,
                'unit_measure' => ($material->unit_measure_id == null) ? '': $material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min'=>$material->stock_min,
                'stock_current'=>$material->stock_current,
                'unit_price'=>$material->price_final,
                'image'=>$material->image,
                'category' => ($material->category_id == null) ? '': $material->category->name,
                'subcategory' => ($material->subcategory_id == null) ? '': $material->subcategory->name,
                'material_type' => ($material->material_type_id == null) ? '': $material->materialType->name,
                'sub_type' => ($material->sub_type_id == null) ? '': $material->sub_type->name,
                'warrant' => ($material->warrant_id == null) ? '': $material->warrant->name,
                'quality' => ($material->quality_id == null) ? '': $material->quality->name,
                'brand' => ($material->brand_id == null) ? '': $material->brand->name,
                'exampler' => ($material->exampler_id == null) ? '': $material->exampler->name,
                'type_scrap' => ($material->type_scrap_id == null) ? '': $material->typeScrap->name,
            ]);

        }
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();

        $end = microtime(true) - $begin;

        dump($end. ' segundos');
        dd();
        //dd(datatables($materials)->toJson());
        //return datatables($materials)->toJson();
    }

    public function getJsonMaterialsTransfer()
    {
        $materials = Material::where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'code' => $material->code, ]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsEntry()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('enable_status', 1)
            ->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'material' => $material->full_name,
                'unit' => ($material->unitMeasure == null) ? "":$material->unitMeasure->name,
                'code' => $material->code,
                'price'=>$material->price_final,
                'typescrap'=>$material->typescrap_id,
                'full_typescrap'=>$material->typeScrap,
                'stock_current'=>$material->stock_current,
                'category'=>$material->category_id,
                'enable_status'=>$material->enable_status,
                'tipo_venta_id'=>$material->tipo_venta_id,
                'perecible' => ($material->perecible == null) ? 'n':$material->perecible
            ]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterials()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('enable_status', 1)
            ->where('category_id', '<>', 8)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'unit' => ($material->unitMeasure == null) ? '':$material->unitMeasure->name, 'code' => $material->code, 'price'=>$material->price_final, 'typescrap'=>$material->typescrap_id, 'full_typescrap'=>$material->typeScrap, 'stock_current'=>$material->stock_current]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsQuote()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->whereNotIn('category_id', [2])
            ->where('category_id', '<>', 8)
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'unit' => $material->unitMeasure->name, 'code' => $material->code]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsCombo()
    {
        $materials = Material::with('unitMeasure')
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_name, 'unit' => $material->unitMeasure->name, 'code' => $material->code, 'price' => $material->price_final]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsScrap()
    {
        $materials = Material::with('subcategory', 'materialType', 'subtype', 'warrant', 'quality')
            ->whereNotNull('typescrap_id')
            ->where('enable_status', 1)
            ->get();
        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'code' => $material->code , 'unit' => $material->unitMeasure->name, 'typescrap'=>$material->typescrap_id]);
        }

        //dd($materials);
        return $array;
    }

    public function getItems($id)
    {
        /*
        $items = Item::where('material_id', $id)->get();
        $brands = Brand::all();
        $categories = Category::all();
        $materialTypes = MaterialType::all();
        $material = Material::with(['category', 'materialType'])->find($id);
        return view('material.edit', compact('items', 'brands', 'categories', 'materialTypes', 'material'));
        */

        $material = Material::find($id);
        //$items = Item::where('material_id', $id)->get();
        //return view('material.items', compact('items', 'material'));
        return view('material.items', compact('material'));

    }

    public function getItemsMaterialActivo($id)
    {

        $material = Material::find($id);
        //$items = Item::where('material_id', $id)->get();
        //return view('material.items', compact('items', 'material'));
        return view('material.itemsActivos', compact('material'));

    }

    public function getItemsMaterialAllActivos($id)
    {
        $material = Material::find($id);

        $arrayItems = [];

        if ( $material->category_id == 8 )
        {
            $items = Item::where('material_id', $id)
                ->with(['location' => function ($query) {
                    $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
                }])
                ->with('material')
                ->with('typescrap')
                ->with('detailEntry')->get();
        } else {
            $items = Item::where('material_id', $id)
                ->where('type', 1)
                ->with(['location' => function ($query) {
                    $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
                }])
                ->with('material')
                ->with('typescrap')
                ->with('detailEntry')->get();
        }


        //dd(datatables($items)->toJson());
        return datatables($items)->toJson();

    }

    public function getItemsMaterial($id)
    {

        $items = Item::where('material_id', $id)
            ->whereIn('state_item', ['entered', 'scraped'])
            ->with(['location' => function ($query) {
                $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
            }])
            ->with('material')
            ->with('typescrap')
            ->with('DetailEntry')->get();

        //dd(datatables($items)->toJson());
        return datatables($items)->toJson();

    }

    public function disableMaterial(Request $request)
    {

        DB::beginTransaction();
        try {
            $material = Material::find($request->get('material_id'));
            $material->enable_status = 0;
            $material->save();
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Material inhabilitado con éxito.'], 200);

    }

    public function enableMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material = Material::find($request->get('material_id'));
            $material->enable_status = 1;
            $material->save();
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Material habilitado con éxito.'], 200);

    }

    public function getAllMaterialsDisable()
    {
        $materials = Material::with(
            'category:id,name',
            'materialType:id,name',
            'unitMeasure:id,name',
            'subcategory:id,name',
            'subType:id,name',
            'exampler:id,name',
            'brand:id,name',
            'warrant:id,name',
            'quality:id,name',
            'typeScrap:id,name'
        )
            ->where('enable_status', 0)
            ->where(function ($q) {
                $q->where('category_id', '<>', 8)
                    ->orWhereNull('category_id');
            })
            ->get();
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();


        //dd($materials);
        //dd(datatables($materials)->toJson());
        return datatables($materials)->toJson();
    }

    public function indexEnable()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.enable', compact('permissions'));
    }

    public function updateDescriptionLargeMaterials()
    {
        $begin = microtime(true);
        dump("Iniciano proceso");
        $materials = Material::all();

        foreach ($materials as $material) {
            $nombreCompleto = $material->full_description;
            $material->full_name = $nombreCompleto;
            $material->save();
        }
        $end = microtime(true) - $begin;
        dump($end);
        dd();
    }

    public function getDataMaterialsPack(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description = $request->input('description');
        $code = $request->input('code');
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $material_type = $request->input('material_type');
        $sub_type = $request->input('sub_type');
        $cedula = $request->input('cedula');
        $calidad = $request->input('calidad');
        $marca = $request->input('marca');
        $retaceria = $request->input('retaceria');
        $rotation = $request->input('rotation');

        $query = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->where('isPack',1)
            /*->orderBy('rotation', "desc")*/
            ->orderBy('id');

        // Aplicar filtros si se proporcionan
        if ($description != "") {
            // Convertir la cadena de búsqueda en un array de palabras clave
            $keywords = explode(' ', $description);

            // Construir la consulta para buscar todas las palabras clave en el campo full_name
            $query->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where('full_name', 'LIKE', '%' . $keyword . '%');
                }
            });

            // Asegurarse de que todas las palabras clave estén presentes en la descripción
            foreach ($keywords as $keyword) {
                $query->where('full_name', 'LIKE', '%' . $keyword . '%');
            }
        }

        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');
        }

        if ($category != "") {
            $query->where('category_id', $category);
        }

        if ($subcategory != "") {
            $query->where('subcategory_id', $subcategory);
        }

        if ($material_type != "") {
            $query->where('material_type_id', $material_type);
        }

        if ($sub_type != "") {
            $query->where('subtype_id', $sub_type);
        }

        if ($cedula != "") {
            $query->where('warrant_id', $cedula);
        }

        if ($calidad != "") {
            $query->where('quality_id', $calidad);
        }

        if ($marca != "") {
            $query->where('brand_id', $marca);
        }

        if ($retaceria != "") {
            $query->where('typescrap_id', $retaceria);
        }

        if ( $rotation != "" ) {
            $query->where('rotation', $rotation);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $materials = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $rotacion = "";
            if ( $material->rotation == "a" )
            {
                $rotacion = '<span class="badge bg-success text-md">ALTA</span>';
            } elseif ( $material->rotation == "m" ) {
                $rotacion = '<span class="badge bg-warning text-md">MEDIA</span>';
            } else {
                $rotacion = '<span class="badge bg-danger text-md">BAJA</span>';
            }

            array_push($array, [
                "id" => $material->id,
                "codigo" => $material->code,
                "descripcion" => $material->full_name,
                "medida" => $material->measure,
                "unidad_medida" => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                "stock_max" => $material->stock_max,
                "stock_min" => $material->stock_min,
                "stock_actual" => $material->stock_current,
                "prioridad" => $priority,
                "precio_unitario" => $material->price_final,
                "categoria" => ($material->category == null) ? '': $material->category->name,
                "sub_categoria" => ($material->subcategory == null) ? '': $material->subcategory->name,
                "tipo" => ($material->materialType == null) ? '': $material->materialType->name,
                "sub_tipo" => ($material->subType == null) ? '': $material->subType->name,
                "cedula" => ($material->warrant == null) ? '':$material->warrant->name,
                "calidad" => ($material->quality == null) ? '': $material->quality->name,
                "marca" => ($material->brand == null) ? '': $material->brand->name,
                "modelo" => ($material->exampler == null) ? '': $material->exampler->name,
                "retaceria" => ($material->typeScrap == null) ? '':$material->typeScrap->name,
                "image" => ($material->image == null || $material->image == "" ) ? 'no_image.png':$material->image,
                "rotation" => $rotacion,
                "update_price" => $material->state_update_price
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function materialSeparatePack()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $arrayCategories = Category::where('id', '<>', 8)->select('id', 'name')->get()->toArray();

        $arrayCedulas = Warrant::select('id', 'name')->get()->toArray();

        $arrayCalidades = Quality::select('id', 'name')->get()->toArray();

        $arrayMarcas = Brand::select('id', 'name')->get()->toArray();

        $arrayRetacerias = Typescrap::select('id', 'name')->get()->toArray();

        $arrayRotations = [
            ["value" => "a", "display" => "ALTA"],
            ["value" => "m", "display" => "MEDIA"],
            ["value" => "b", "display" => "BAJA"]
        ];

        $materials = Material::where('isPack', 0)
            ->where('enable_status', 1)->get();

        //dd($array);

        $arrayMaterials = [];
        foreach ( $materials as $material )
        {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
            ]);
        }

        return view('material.separatePack', compact( 'permissions', 'arrayCategories', 'arrayCedulas', 'arrayCalidades', 'arrayMarcas', 'arrayRetacerias', 'arrayRotations', 'arrayMaterials'));

    }

    public function storeSeparatePack(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $quantityUnpack = $request->get('packs_separate');
            $materialUnpack = $request->get('material');

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                // TODO: VERIFICAR EL TEMA DE LOS ITEMS
                $materialToUnpack = Material::find($materialUnpack);
                //$materialToUnpack->stock_unPack = $material->stock_unPack + ($quantityUnpack*$material->quantityPack);
                $materialToUnpack->stock_current = $materialToUnpack->stock_current + ($quantityUnpack*$material->quantityPack);
                $materialToUnpack->save();

                $material->stock_current = $material->stock_current - $quantityUnpack;
                $material->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Separación de paquetes con éxito.'], 200);

    }

    public function getPriceListMaterial($material)
    {
        $material = Material::find($material);

        if ( !isset($material) )
        {
            return response()->json(['message' => "No existe el material"], 422);
        } else {
            return response()->json([
                'priceList' => ($material->list_price == null) ? 0 : $material->list_price,
                'priceBase' => ($material->unit_price == null) ? 0 : $material->unit_price,
                'priceMin' => ($material->min_price == null) ? 0 : $material->min_price,
                'priceMax' => ($material->max_price == null) ? 0 : $material->max_price,
            ], 200);
        }
    }

    public function getPricePercentageMaterial($material)
    {
        $material = Material::find($material);

        if ( !isset($material) )
        {
            return response()->json(['message' => "No existe el material"], 422);
        } else {
            return response()->json(['pricePercentage' => ($material->percentage_price == null) ? 0 : 1-$material->percentage_price], 200);
        }
    }

    public function setPriceDirectoMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $price = $request->get('material_priceList');

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                $material->percentage_price = null;
                $material->list_price = $price;
                $material->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cambio de precio de lista con éxito.'], 200);
    }

    public function managePriceMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $price_list = $request->get('material_priceList');
            /*$price_min = $request->get('material_priceMin');
            $price_max = $request->get('material_priceMax');*/
            $price_base = $request->get('material_priceBase');

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                $material->percentage_price = null;
                $material->list_price = $price_list;
                $material->unit_price = $price_base;
                /*$material->min_price = $price_min;
                $material->max_price = $price_max;*/
                $material->save();
            }

            // Cambiar el precio en tienda
            $storeMaterials = StoreMaterial::where('material_id', $material_id)->get();
            foreach ( $storeMaterials as $storeMaterial ) {
                $storeMaterial->unit_price = $price_list;
                $storeMaterial->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cambio de precio de lista con éxito.'], 200);
    }

    public function setPricePorcentajeMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $price = (float)($request->get('material_pricePercentage'));

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                $material->percentage_price = (float)(1+($price/100));
                $material->list_price = ($material->unit_price*(1+($price/100)));;
                $material->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cambio de precio de porcentaje con éxito.'], 200);
    }

    public function sendMaterialToStore($material_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $material = Material::find($material_id);

        $data = DataGeneral::where('name', 'idWarehouseTienda')->first();

        $warehouse_id = $data->valueNumber; // TODO: HACERLO DINAMICO

        $shelves = Shelf::with('levels.containers.positions')
            ->where('warehouse_id', $warehouse_id)->get();

        $storeMaterials = StoreMaterial::where('material_id', $material_id)
            ->where('enable_status', 1)
            ->pluck('id');

        $locationIds = StoreMaterialLocation::whereIn('store_material_id', $storeMaterials)
            ->pluck('location_id')
            ->toArray();

        // TODO: Posiciones de material
        $positionIds = Location::whereIn('id', $locationIds)
            ->pluck('position_id')
            ->toArray();

        $storeMaterialsNotMaterial = StoreMaterial::where('material_id', '!=',$material_id)
            ->where('enable_status', 1)
            ->pluck('id');

        $locationIdsNotMaterial = StoreMaterialLocation::whereIn('store_material_id', $storeMaterialsNotMaterial)
            ->pluck('location_id')
            ->toArray();

        // TODO: Posiciones donde no hay material
        $positionIdsNotMaterial = Location::whereIn('id', $locationIdsNotMaterial)
            ->pluck('position_id')
            ->toArray();

        return view('material.sendToStore', compact( 'permissions', 'material', 'shelves', 'positionIds', 'positionIdsNotMaterial'));

    }

    public function guardarTraslado( Request $request )
    {
        $request->validate([
            'material_id'   => 'required|integer|exists:materials,id',
            'position_id'   => 'required|integer|exists:positions,id',
            'quantity'      => 'required|numeric|min:1',
            'unit_price'    => 'required|numeric|min:0.01',
            'fechas'        => 'nullable|array',
            'fechas.*'      => 'date|after_or_equal:today',
        ]);

        DB::beginTransaction();

        try {
            // 1. Buscar el material
            $material = Material::findOrFail($request->material_id);

            // 2. Crear StoreMaterial
            $storeMaterial = StoreMaterial::create([
                'material_id'    => $material->id,
                'full_name'      => $material->full_name,
                'stock_max'      => $material->stock_max,
                'stock_min'      => $material->stock_min,
                'stock_current'  => $request->quantity,
                'unit_price'     => $request->unit_price,
                'enable_status'  => 1,
                'codigo'         => $material->codigo,
                'isPack'         => $material->isPack,
                'quantityPack'   => $material->quantityPack,
            ]);

            // 3. Obtener location_id a partir del position_id
            $location = Location::where('position_id', $request->position_id)->first();
            if (!$location) {
                throw new \Exception("No se encontró una ubicación válida para esta posición.");
            }

            // 4. Crear StoreMaterialLocation
            StoreMaterialLocation::create([
                'store_material_id' => $storeMaterial->id,
                'location_id'       => $location->id,
            ]);

            // 5. Crear fechas de vencimiento
            foreach ($request->input('fechas', []) as $fecha) {
                StoreMaterialVencimiento::create([
                    'store_material_id'  => $storeMaterial->id,
                    'fecha_vencimiento'  => $fecha,
                ]);
            }

            // TODO: Falta disminuir del almacen general
            $material->stock_current = $material->stock_current - $request->quantity;
            $material->save();

            DB::commit();

            return response()->json(['message' => 'Traslado guardado correctamente.'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error al guardar el traslado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexMaterialStore()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $arrayCategories = Category::where('id', '<>', 8)->select('id', 'name')->get()->toArray();

        $arrayCedulas = Warrant::select('id', 'name')->get()->toArray();

        $arrayCalidades = Quality::select('id', 'name')->get()->toArray();

        $arrayMarcas = Brand::select('id', 'name')->get()->toArray();

        $arrayRetacerias = Typescrap::select('id', 'name')->get()->toArray();

        $arrayRotations = [
            ["value" => "a", "display" => "ALTA"],
            ["value" => "m", "display" => "MEDIA"],
            ["value" => "b", "display" => "BAJA"]
        ];

        $materials = Material::where('isPack', 0)
            ->where('enable_status', 1)->get();

        //dd($array);

        $arrayMaterials = [];
        foreach ( $materials as $material )
        {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
            ]);
        }

        $data = DataGeneral::where('name', 'idWarehouseTienda')->first();

        $warehouse_id = $data->valueNumber; // TODO: HACERLO DINAMICO

        $shelves = Shelf::with('levels.containers.positions')
            ->where('warehouse_id', $warehouse_id)->get();

        $rows = StoreMaterial::resumenPorMaterial()->get();

        // Calculamos el estado de stock
        foreach ($rows as $row) {
            if ($row->stock_total <= 0) {
                $row->estado = 'desabastecido';
            } elseif ($row->stock_total <= $row->stock_min) {
                $row->estado = 'por_desabastecer';
            } else {
                $row->estado = 'ok';
            }
        }

        $hayAlertas = $rows->contains(function ($r) {
            return $r->estado === 'desabastecido' || $r->estado === 'por_desabastecer';
        });

        return view('material.indexStore', compact( 'permissions', 'arrayCategories', 'arrayCedulas', 'arrayCalidades', 'arrayMarcas', 'arrayRetacerias', 'arrayRotations', 'arrayMaterials', 'shelves', 'rows', 'hayAlertas'));

    }

    public function getDataMaterialStore(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description = $request->input('description');
        $code = $request->input('code');
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $material_type = $request->input('material_type');
        $sub_type = $request->input('sub_type');
        $cedula = $request->input('cedula');
        $calidad = $request->input('calidad');
        $marca = $request->input('marca');
        $retaceria = $request->input('retaceria');
        $rotation = $request->input('rotation');
        $isPack = $request->input('isPack');

        $materialIds = StoreMaterial::where('enable_status', 1)
            ->pluck('material_id')
            ->unique()
            ->toArray();

        $query = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->whereIn('id', $materialIds)
            ->where('enable_status', 1)
            ->orderBy('id');

        // Aplicar filtros si se proporcionan
        if ($description != "") {
            // Convertir la cadena de búsqueda en un array de palabras clave
            $keywords = explode(' ', $description);

            // Construir la consulta para buscar todas las palabras clave en el campo full_name
            $query->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where('full_name', 'LIKE', '%' . $keyword . '%');
                }
            });

            // Asegurarse de que todas las palabras clave estén presentes en la descripción
            foreach ($keywords as $keyword) {
                $query->where('full_name', 'LIKE', '%' . $keyword . '%');
            }
        }

        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');
        }

        if ($category != "") {
            $query->where('category_id', $category);
        }

        if ($subcategory != "") {
            $query->where('subcategory_id', $subcategory);
        }

        if ($material_type != "") {
            $query->where('material_type_id', $material_type);
        }

        if ($sub_type != "") {
            $query->where('subtype_id', $sub_type);
        }

        if ($cedula != "") {
            $query->where('warrant_id', $cedula);
        }

        if ($calidad != "") {
            $query->where('quality_id', $calidad);
        }

        if ($marca != "") {
            $query->where('brand_id', $marca);
        }

        if ($retaceria != "") {
            $query->where('typescrap_id', $retaceria);
        }

        if ( $rotation != "" ) {
            $query->where('rotation', $rotation);
        }

        if ( $isPack != "" ) {
            $query->where('isPack', $isPack);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $materials = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $rotacion = "";
            if ( $material->rotation == "a" )
            {
                $rotacion = '<span class="badge bg-success text-md">ALTA</span>';
            } elseif ( $material->rotation == "m" ) {
                $rotacion = '<span class="badge bg-warning text-md">MEDIA</span>';
            } else {
                $rotacion = '<span class="badge bg-danger text-md">BAJA</span>';
            }

            array_push($array, [
                "id" => $material->id,
                "codigo" => $material->code,
                "descripcion" => $material->full_name,
                "medida" => $material->measure,
                "unidad_medida" => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                "stock_max" => $material->stock_max,
                "stock_min" => $material->stock_min,
                "stock_actual" => $material->stock_store,
                "prioridad" => $priority,
                "precio_unitario" => $material->unit_price,
                "precio_lista" => $material->list_price,
                "categoria" => ($material->category == null) ? '': $material->category->name,
                "sub_categoria" => ($material->subcategory == null) ? '': $material->subcategory->name,
                "tipo" => ($material->materialType == null) ? '': $material->materialType->name,
                "sub_tipo" => ($material->subType == null) ? '': $material->subType->name,
                "cedula" => ($material->warrant == null) ? '':$material->warrant->name,
                "calidad" => ($material->quality == null) ? '': $material->quality->name,
                "marca" => ($material->brand == null) ? '': $material->brand->name,
                "modelo" => ($material->exampler == null) ? '': $material->exampler->name,
                "retaceria" => ($material->typeScrap == null) ? '':$material->typeScrap->name,
                "image" => ($material->image == null || $material->image == "" ) ? 'no_image.png':$material->image,
                "rotation" => $rotacion,
                "update_price" => $material->state_update_price,
                "isPack" => $material->isPack
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function getFechasVencimiento($material_id)
    {
        $vencimientos = MaterialVencimiento::where('material_id', $material_id)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get(['id', 'fecha_vencimiento']);

        return response()->json($vencimientos);
    }

    public function deleteFechasVencimiento($id)
    {
        $vencimiento = MaterialVencimiento::findOrFail($id);
        $vencimiento->delete();

        return response()->json(['message' => 'Fecha eliminada exitosamente']);
    }

    public function ocupadas($materialId)
    {
        $storeMaterials = StoreMaterial::where('material_id', $materialId)
            ->where('enable_status', 1)
            ->pluck('id');

        $locationIds = StoreMaterialLocation::whereIn('store_material_id', $storeMaterials)
            ->pluck('location_id')
            ->toArray();

        $positionIds = Location::whereIn('id', $locationIds)
            ->pluck('position_id')
            ->toArray();

        return response()->json([
            'locations' => $positionIds
        ]);
    }

    public function obtenerDetalleUbicacion(Request $request)
    {
        $position_id = $request->position_id;

        $location = Location::where('position_id', $position_id)->first();
        if (!$location) {
            return response()->json(['error' => 'Ubicación no encontrada'], 404);
        }

        $storeLocation = StoreMaterialLocation::where('location_id', $location->id)->first();
        if (!$storeLocation) {
            return response()->json(['error' => 'No hay material en esta ubicación'], 404);
        }

        $storeMaterial = StoreMaterial::find($storeLocation->store_material_id);
        if (!$storeMaterial) {
            return response()->json(['error' => 'Material no encontrado'], 404);
        }

        return response()->json([
            'store_material_id' => $storeMaterial->id,
            'store_material_location_id' => $storeLocation->id,
            'stock_current' => $storeMaterial->stock_current,
            'material_name' => $storeMaterial->full_name
        ]);
    }

    public function eliminarUbicacionOcupada(Request $request)
    {
        $storeMaterialLocation = StoreMaterialLocation::find($request->store_material_location_id);

        if (!$storeMaterialLocation) {
            return response()->json(['error' => 'Ubicación no encontrada'], 404);
        }

        $storeMaterial = StoreMaterial::find($storeMaterialLocation->store_material_id);
        if (!$storeMaterial) {
            return response()->json(['error' => 'Material no encontrado'], 404);
        }

        $material = Material::find($storeMaterial->material_id);
        if ($material) {
            // Devolver stock al material original
            $material->stock_current += $storeMaterial->stock_current;
            $material->save();
        }

        // Eliminar vencimientos y ubicaciones
        StoreMaterialVencimiento::where('store_material_id', $storeMaterial->id)->delete();
        StoreMaterialLocation::where('store_material_id', $storeMaterial->id)->delete();
        $storeMaterial->delete();

        return response()->json(['success' => true]);
    }

    public function selectAjax(Request $request)
    {
        $term = $request->get('q');

        $query = Material::query()
            ->select('id', 'code', 'full_name');
            /*->where('inventory', 1); */// solo los que manejan stock (ajusta si quieres)

        if ($term) {
            $term = trim($term);
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%");
            });
        }

        $materials = $query
            ->orderBy('full_name')
            ->limit(20) // importante para no matar la BD
            ->get();

        // Formato compatible con select2
        $results = $materials->map(function ($m) {
            return [
                'id'   => $m->id,
                'text' => "{$m->code} - {$m->full_description}",
            ];
        });

        return response()->json($results);
    }

    public function select2(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 10;

        $query = Material::where('enable_status', 1);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('id', 'like', "%{$q}%");
            });
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderBy('full_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'full_name']);

        $results = $items->map(function ($m) {
            return [
                'id' => $m->id,
                'text' => $m->full_name,

                // extra para el JS
                'material_id' => $m->id,
                'codigo' => (string)$m->id,
                'descripcion' => $m->full_name,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }
}
