@php($posPriceList = app(\App\Services\BranchPriceListService::class)->current())
<div id="pos-catalog-context" class="d-none"
     data-request-key="{{ bin2hex(random_bytes(16)) }}"
     data-currency="{{ $posPriceList ? $posPriceList->currency : '' }}"
     data-price-list="{{ $posPriceList ? $posPriceList->id : '' }}"
     data-tenant="{{ \App\Support\TenantContext::tenantId() }}"
     data-company="{{ \App\Support\TenantContext::companyId() }}"
     data-branch="{{ \App\Support\TenantContext::branchId() }}"
     data-search-url="{{ route('pos.catalog.search') }}"
     data-availability-url="{{ route('pos.catalog.availability', ['stockItem' => '__ID__']) }}"></div>
