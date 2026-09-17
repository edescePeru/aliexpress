<?php

namespace App\Http\Controllers;

use App\SettingDefinition;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanySettingController extends Controller
{
    public function index()
    {
        $definitions = SettingDefinition::query()
            ->where('scope', 'company')
            ->where('editable_by_owner', true)
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('label')
            ->get();

        $settings = app(SettingService::class);

        foreach ($definitions as $definition) {
            $definition->current_value =
                $settings->get($definition->key);
        }

        $modules = $definitions->groupBy('module');

        return view(
            'companySetting.index',
            compact('modules')
        );
    }

    public function update(Request $request)
    {
        $values = $request->input(
            'settings',
            []
        );

        if (!is_array($values)) {
            return response()->json([
                'success' => false,
                'message' => 'Los valores enviados no son válidos.',
            ], 422);
        }

        $definitions = SettingDefinition::query()
            ->where('scope', 'company')
            ->where('editable_by_owner', true)
            ->where('is_active', true)
            ->whereIn(
                'key',
                array_keys($values)
            )
            ->get()
            ->keyBy('key');

        $settings = app(SettingService::class);

        DB::transaction(function () use (
            $values,
            $definitions,
            $settings
        ) {
            foreach ($values as $key => $value) {
                if (!$definitions->has($key)) {
                    continue;
                }

                $settings->set(
                    $key,
                    $value
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Configuraciones actualizadas correctamente.',
        ]);
    }
}