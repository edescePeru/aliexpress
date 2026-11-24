@php
    $publishedBranches = app(\App\Services\PublicCatalog::class)->branches($catalogCompany)->filter(function ($branch) {
        return is_string($branch->catalog_slug) && preg_match('/^'.\App\Services\PublicCatalogSlug::PATTERN.'$/D', $branch->catalog_slug);
    });
@endphp
<div class="user-panel mt-3 pb-3 mb-3">
    <button type="button" class="btn btn-link btn-block text-left text-light d-flex align-items-center px-0"
            data-toggle="collapse" data-target="#sidebar-public-store-links" aria-expanded="false" aria-controls="sidebar-public-store-links">
        <span class="image"><img src="{{ asset('images/logo/store-icon.png') }}" class="img-circle elevation-2" alt=""></span>
        <span class="ml-2 font-weight-bold">Tienda Web</span>
        <i class="fas fa-chevron-down ml-auto mr-2" aria-hidden="true"></i>
    </button>
    <div class="collapse pl-3" id="sidebar-public-store-links">
        <a href="{{ route('publicCatalog.company', ['companySlug' => $catalogCompany->catalog_slug]) }}" target="_blank" rel="noopener noreferrer"
           class="d-block text-light text-wrap py-2 small"><i class="fas fa-globe mr-2" aria-hidden="true"></i>Catálogo general</a>
        @foreach($publishedBranches as $storeBranch)
            <a href="{{ route('publicCatalog.branch', ['companySlug' => $catalogCompany->catalog_slug, 'branchSlug' => $storeBranch->catalog_slug]) }}"
               target="_blank" rel="noopener noreferrer" class="d-block text-light text-wrap py-2 small">
                <i class="fas fa-store mr-2" aria-hidden="true"></i>{{ $storeBranch->name }}
                @if((int)$storeBranch->id === \App\Support\TenantContext::branchIdOrNull())<span class="badge badge-info ml-1">Local actual</span>@endif
            </a>
        @endforeach
        @if($publishedBranches->isEmpty())<p class="text-muted text-wrap small py-2 mb-0">Sin locales publicados.</p>@endif
    </div>
</div>
