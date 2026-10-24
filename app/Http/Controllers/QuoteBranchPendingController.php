<?php
namespace App\Http\Controllers;

use App\Services\Quotes\QuoteBranchReadScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteBranchPendingController extends Controller
{
    public function index(Request $request, QuoteBranchReadScope $scope)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]);
        $search = trim($data['q'] ?? '');
        $query = $scope->pending(DB::table('quotes'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('quotes.code', 'LIKE', '%'.$search.'%')->orWhere('quotes.description_quote', 'LIKE', '%'.$search.'%');
            });
        }
        $quotes = $query->select('quotes.id', 'quotes.code', 'quotes.description_quote', 'quotes.date_quote', 'quotes.state')
            ->orderByDesc('quotes.id')->paginate(15)->appends(['q' => $search]);
        return view('quoteSale.pendingBranches', compact('quotes', 'search'));
    }

    public function show($id, QuoteBranchReadScope $scope)
    {
        $quote = $scope->pending(DB::table('quotes'))->where('quotes.id', $id)
            ->first(['quotes.id', 'quotes.code', 'quotes.description_quote', 'quotes.date_quote', 'quotes.state']);
        abort_unless($quote, 404);
        $tenant = \App\Support\TenantContext::tenantId();
        $lines = DB::table('equipment_consumables as c')->join('equipments as e', 'e.id', '=', 'c.equipment_id')
            ->leftJoin('stock_items as s', function ($join) use ($tenant) { $join->on('s.id', '=', 'c.stock_item_id')->where('s.tenant_id', '=', $tenant); })
            ->where('e.quote_id', $quote->id)->orderBy('c.id')
            ->select('c.id', 'c.stock_item_id', 'c.quantity', 's.display_name')->get();
        return view('quoteSale.pendingBranchDetail', compact('quote', 'lines'));
    }
}
