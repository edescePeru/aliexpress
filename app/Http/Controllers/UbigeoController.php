<?php

namespace App\Http\Controllers;

use App\Department;
use App\District;
use App\Province;
use Illuminate\Http\Request;

class UbigeoController extends Controller
{
    public function departmentsSelect2(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $page = (int)$request->get('page', 1);
        $perPage = 20;

        $query = Department::query()->orderBy('name');

        if ($q !== '') {
            $query->where('name', 'like', '%' . $q . '%')
                ->orWhere('id', 'like', $q . '%');
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $results = [];
        foreach ($items as $d) {
            $results[] = [
                'id' => $d->id,                 // "13"
                'text' => $d->id . ' - ' . $d->name,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    public function provincesSelect2(Request $request)
    {
        $departmentId = (string)$request->get('department_id', '');
        $q = trim((string)$request->get('q', ''));
        $page = (int)$request->get('page', 1);
        $perPage = 20;

        $query = Province::query()->orderBy('name');

        if ($departmentId !== '') {
            $query->where('department_id', $departmentId);
        }

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                    ->orWhere('id', 'like', $q . '%');
            });
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $results = [];
        foreach ($items as $p) {
            $results[] = [
                'id' => $p->id,                 // "1301"
                'text' => $p->id . ' - ' . $p->name,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    public function districtsSelect2(Request $request)
    {
        $provinceId = (string)$request->get('province_id', '');
        $departmentId = (string)$request->get('department_id', '');
        $q = trim((string)$request->get('q', ''));
        $page = (int)$request->get('page', 1);
        $perPage = 20;

        $query = District::query()->orderBy('name');

        if ($provinceId !== '') {
            $query->where('province_id', $provinceId);
        }
        if ($departmentId !== '') {
            $query->where('department_id', $departmentId);
        }

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                    ->orWhere('id', 'like', $q . '%');
            });
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $results = [];
        foreach ($items as $d) {
            // id = UBIGEO (6)
            $results[] = [
                'id' => $d->id, // "130101"
                'text' => $d->id . ' - ' . $d->name,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }
}
