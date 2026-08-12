<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Tenant;
use App\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        $actions = Activity::query()
            ->where('log_name', 'platform')
            ->whereNotNull('description')
            ->distinct()
            ->orderBy('description')
            ->pluck('description');

        $tenants = Tenant::query()
            ->select(
                'id',
                'name'
            )
            ->orderBy('name')
            ->get();

        $platformUsers = User::query()
            ->where(
                'is_platform_admin',
                true
            )
            ->select(
                'id',
                'name',
                'email'
            )
            ->orderBy('name')
            ->get();

        return view(
            'platform.activityLog.index',
            compact(
                'actions',
                'tenants',
                'platformUsers'
            )
        );
    }

    public function data(Request $request)
    {
        $perPage = (int) $request->get(
            'per_page',
            10
        );

        if (
        !in_array(
            $perPage,
            [10, 25, 50],
            true
        )
        ) {
            $perPage = 10;
        }

        $query = Activity::query()
            ->where(
                'log_name',
                'platform'
            )
            ->with([
                'causer',
                'subject',
            ])
            ->orderByDesc('id');

        /*
         * Búsqueda general.
         */
        if ($request->filled('search')) {

            $search = trim(
                $request->get('search')
            );

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'description',
                        'like',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'subject_type',
                            'like',
                            '%' . $search . '%'
                        );

                }
            );
        }

        /*
         * Acción.
         */
        if ($request->filled('action')) {

            $query->where(
                'description',
                $request->get('action')
            );
        }

        /*
         * Usuario causante.
         */
        if ($request->filled('causer_id')) {

            $query->where(
                'causer_type',
                User::class
            )
                ->where(
                    'causer_id',
                    $request->get('causer_id')
                );
        }

        /*
         * Tenant almacenado en properties.
         *
         * MySQL soporta JSON_EXTRACT.
         */
        if ($request->filled('tenant_id')) {

            $tenantId =
                (int) $request->get(
                    'tenant_id'
                );

            $query->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(properties, '$.tenant_id')) = ?",
                [
                    (string) $tenantId
                ]
            );
        }

        /*
         * Fecha desde.
         */
        if ($request->filled('start_date')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->get('start_date')
            );
        }

        /*
         * Fecha hasta.
         */
        if ($request->filled('end_date')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->get('end_date')
            );
        }

        $activities =
            $query->paginate(
                $perPage
            );

        $activities->getCollection()
            ->transform(
                function ($activity) {

                    $properties =
                        $activity->properties
                            ? $activity
                            ->properties
                            ->toArray()
                            : [];

                    return [
                        'id' =>
                            $activity->id,

                        'action' =>
                            $activity->description,

                        'tenant_id' =>
                            $properties[
                            'tenant_id'
                            ] ?? null,

                        'tenant_name' =>
                            $properties[
                            'tenant_name'
                            ] ?? null,

                        'causer' => [
                            'id' =>
                                optional(
                                    $activity->causer
                                )->id,

                            'name' =>
                                optional(
                                    $activity->causer
                                )->name,

                            'email' =>
                                optional(
                                    $activity->causer
                                )->email,
                        ],

                        'subject' => [
                            'type' =>
                                class_basename(
                                    $activity
                                        ->subject_type
                                        ?: ''
                                ),

                            'id' =>
                                $activity
                                    ->subject_id,

                            'name' =>
                                $this
                                    ->resolveSubjectName(
                                        $activity
                                            ->subject
                                    ),
                        ],

                        'created_at' =>
                            optional(
                                $activity
                                    ->created_at
                            )->format(
                                'd/m/Y H:i:s'
                            ),
                    ];
                }
            );

        return response()->json(
            $activities
        );
    }

    public function show($id)
    {
        $activity = Activity::query()
            ->where(
                'log_name',
                'platform'
            )
            ->with([
                'causer',
                'subject',
            ])
            ->findOrFail($id);

        $properties =
            $activity->properties
                ? $activity
                ->properties
                ->toArray()
                : [];

        return response()->json([
            'id' =>
                $activity->id,

            'action' =>
                $activity->description,

            'tenant_id' =>
                $properties[
                'tenant_id'
                ] ?? null,

            'tenant_name' =>
                $properties[
                'tenant_name'
                ] ?? null,

            'causer' => [
                'name' =>
                    optional(
                        $activity->causer
                    )->name,

                'email' =>
                    optional(
                        $activity->causer
                    )->email,
            ],

            'subject' => [
                'type' =>
                    class_basename(
                        $activity
                            ->subject_type
                            ?: ''
                    ),

                'id' =>
                    $activity
                        ->subject_id,

                'name' =>
                    $this
                        ->resolveSubjectName(
                            $activity->subject
                        ),
            ],

            'ip' =>
                $properties[
                'ip'
                ] ?? null,

            'user_agent' =>
                $properties[
                'user_agent'
                ] ?? null,

            'old' =>
                $properties[
                'old'
                ] ?? null,

            'new' =>
                $properties[
                'new'
                ] ?? null,

            'permissions_added' =>
                $properties[
                'permissions_added'
                ] ?? [],

            'permissions_removed' =>
                $properties[
                'permissions_removed'
                ] ?? [],

            'created_at' =>
                optional(
                    $activity->created_at
                )->format(
                    'd/m/Y H:i:s'
                ),
        ]);
    }

    private function resolveSubjectName(
        $subject
    ) {
        if (!$subject) {
            return null;
        }

        foreach (
            [
                'name',
                'description',
                'code',
                'email',
            ] as $field
        ) {

            if (
                isset(
                    $subject->{$field}
                ) &&
                $subject->{$field}
            ) {
                return
                    $subject->{$field};
            }
        }

        return null;
    }
}
