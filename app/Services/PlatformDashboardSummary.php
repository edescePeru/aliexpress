<?php
namespace App\Services;

use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};

class PlatformDashboardSummary
{
    public function data(): array
    {
        $user = Auth::user();
        abort_unless($user, 401);
        abort_unless($user->enable && $user->isPlatformAdmin(), 403);
        TenantContext::clear();

        $users = DB::table('users')->where('enable', true)->where('is_platform_admin', false)
            ->groupBy('tenant_id')->select('tenant_id')->selectRaw('COUNT(*) AS used_users');
        $companies = DB::table('companies')->where('is_active', true)->groupBy('tenant_id')
            ->select('tenant_id')->selectRaw('COUNT(*) AS used_companies');
        // The plan counts every active branch, even if its company is inactive.
        $branches = DB::table('branches as b')->join('companies as c', 'c.id', '=', 'b.company_id')
            ->where('b.is_active', true)->groupBy('c.tenant_id')->select('c.tenant_id')->selectRaw('COUNT(*) AS used_branches');
        $usage = DB::table('tenants as t')->leftJoin('plans as p', 'p.id', '=', 't.plan_id')
            ->leftJoinSub($users, 'u', function ($join) { $join->on('u.tenant_id', '=', 't.id'); })
            ->leftJoinSub($companies, 'c', function ($join) { $join->on('c.tenant_id', '=', 't.id'); })
            ->leftJoinSub($branches, 'b', function ($join) { $join->on('b.tenant_id', '=', 't.id'); });
        $review = (clone $usage)->where('t.is_active', true)->where(function ($query) {
            $query->whereNull('p.id')->orWhere('p.is_active', false)
                ->orWhereNull('p.max_active_users')->orWhereNull('p.max_active_companies')->orWhereNull('p.max_active_branches')
                ->orWhereRaw('COALESCE(u.used_users, 0) > p.max_active_users')
                ->orWhereRaw('COALESCE(c.used_companies, 0) > p.max_active_companies')
                ->orWhereRaw('COALESCE(b.used_branches, 0) > p.max_active_branches');
        });
        $reviewCount = (clone $review)->count('t.id');
        $rows = $review->select('t.id', 't.name', 'p.name as plan_name', 'p.is_active as plan_active',
            'p.max_active_users', 'p.max_active_companies', 'p.max_active_branches')
            ->selectRaw('COALESCE(u.used_users, 0) AS used_users, COALESCE(c.used_companies, 0) AS used_companies, COALESCE(b.used_branches, 0) AS used_branches')
            ->orderBy('t.name')->orderBy('t.id')->limit(8)->get();
        foreach ($rows as $row) {
            $reasons = [];
            if ($row->plan_name === null) { $reasons[] = 'Sin plan disponible'; }
            elseif (!$row->plan_active) { $reasons[] = 'Plan inactivo'; }
            foreach (['users'=>'usuarios', 'companies'=>'empresas', 'branches'=>'locales'] as $key=>$label) {
                $limit = $row->{'max_active_'.$key};
                if ($limit === null) { $reasons[] = 'Máximo de '.$label.' sin configurar'; }
                elseif ((int) $row->{'used_'.$key} > (int) $limit) { $reasons[] = 'Excede el máximo de '.$label; }
            }
            $row->review_reasons = $reasons;
        }

        return [
            'platformStats'=>[
                'tenants'=>DB::table('tenants')->count(),
                'active_tenants'=>DB::table('tenants')->where('is_active', true)->count(),
                'active_companies'=>DB::table('companies as c')->join('tenants as t', 't.id', '=', 'c.tenant_id')->where('c.is_active', true)->count('c.id'),
                'active_branches'=>DB::table('branches as b')->join('companies as c', 'c.id', '=', 'b.company_id')->join('tenants as t', 't.id', '=', 'c.tenant_id')->where('b.is_active', true)->count('b.id'),
                'active_users'=>DB::table('users as u')->join('tenants as t', 't.id', '=', 'u.tenant_id')->where('u.enable', true)->where('u.is_platform_admin', false)->count('u.id'),
            ],
            'platformReviewCount'=>$reviewCount, 'platformReviews'=>$rows,
        ];
    }
}
