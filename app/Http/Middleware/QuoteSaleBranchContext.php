<?php
namespace App\Http\Middleware;

use App\Services\Quotes\{QuoteBranchCatalog, QuoteBranchContext};
use Closure;

class QuoteSaleBranchContext
{
    public function handle($request, Closure $next)
    {
        $action = $request->route()->getActionMethod();
        $context = app(QuoteBranchContext::class);
        if (in_array($action, ['create', 'store', 'edit', 'update', 'updateEquipmentOfQuote', 'updateDatosGeneral',
            'renewQuote', 'destroy', 'raiseQuote', 'storeFromQuote', 'getDataIndividual', 'show', 'printQuoteToCustomer'], true)) {
            app(QuoteBranchCatalog::class)->validateContext();
        }
        if (in_array($action, ['store', 'update', 'updateEquipmentOfQuote', 'updateDatosGeneral', 'destroy'], true)) {
            $context->validateForm($request);
        }
        $id = null;
        if (in_array($action, ['update', 'updateDatosGeneral', 'storeFromQuote'], true)) { $id = $request->input('quote_id'); }
        if ($action === 'updateEquipmentOfQuote') { $id = $request->route('id_quote'); }
        if (in_array($action, ['edit', 'renewQuote', 'destroy', 'raiseQuote', 'show', 'printQuoteToCustomer'], true)) {
            $id = $request->route('quote');
        }
        if ($action === 'getDataIndividual') { $id = $request->route('id'); }
        if (is_object($id) && method_exists($id, 'getKey')) { $id = $id->getKey(); }
        if (in_array($action, ['edit', 'update', 'updateDatosGeneral', 'updateEquipmentOfQuote', 'renewQuote',
            'destroy', 'raiseQuote', 'storeFromQuote', 'getDataIndividual', 'show', 'printQuoteToCustomer'], true)) {
            $context->assertQuote($id, in_array($action, ['show', 'printQuoteToCustomer'], true));
        }
        return $next($request);
    }
}
