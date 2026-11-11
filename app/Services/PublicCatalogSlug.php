<?php

namespace App\Services;

use Illuminate\Validation\Rule;

class PublicCatalogSlug
{
    public const PATTERN = '[a-z0-9]+(?:[-_][a-z0-9]+)*';

    public static function companyRules(?int $id = null): array
    {
        $reserved = ['api', 'storage', 'images', 'shop', 'store-web', 'admin', 'dashboard'];
        foreach (app('router')->getRoutes() as $route) {
            $segment = explode('/', $route->uri())[0];
            if (preg_match('/^'.self::PATTERN.'$/D', $segment)) {
                $reserved[] = $segment;
            }
        }
        $unique = Rule::unique('companies', 'catalog_slug');
        if ($id !== null) {
            $unique->ignore($id);
        }
        return ['bail', 'nullable', 'string', 'max:80', 'regex:/^'.self::PATTERN.'$/D', Rule::notIn(array_unique($reserved)), $unique];
    }

    public static function branchRules(?int $companyId = null, ?int $id = null): array
    {
        $rules = ['bail', 'nullable', 'string', 'max:80', 'regex:/^'.self::PATTERN.'$/D', Rule::notIn(['producto'])];
        if ($companyId !== null) {
            $unique = Rule::unique('branches', 'catalog_slug')->where('company_id', $companyId);
            if ($id !== null) {
                $unique->ignore($id);
            }
            $rules[] = $unique;
        }
        return $rules;
    }
}
