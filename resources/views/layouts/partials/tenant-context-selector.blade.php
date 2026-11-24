@if(Auth::check() && !Auth::user()->isPlatformAdmin())
    @php
        $activeCompany = \App\Support\TenantContext::companyOrNull();
        $activeBranch = \App\Support\TenantContext::branchOrNull();
        $availableCompanies = Auth::user()->companies()->wherePivot('is_active', true)
            ->where('companies.tenant_id', Auth::user()->tenant_id)->where('companies.is_active', true)
            ->orderBy('companies.business_name')->get();
        $availableBranches = $activeCompany ? Auth::user()->branches()->wherePivot('is_active', true)
            ->where('branches.company_id', $activeCompany->id)->where('branches.is_active', true)->orderBy('branches.name')->get() : collect();
    @endphp
    <li class="nav-item dropdown tenant-context-navbar"
        data-branches-url="{{ route('tenant.context.branches') }}" data-update-url="{{ route('tenant.context.update') }}"
        data-clear-url="{{ route('tenant.context.clear') }}" data-home-url="{{ route('dashboard.principal') }}">
        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" title="Elegir empresa y local">
            <i class="fas fa-building mr-1"></i><span>{{ $activeCompany ? ($activeCompany->trade_name ?: $activeCompany->business_name) : 'Elegir empresa' }}</span>
            <span class="text-muted mx-1">—</span><i class="fas fa-store mr-1"></i><span>{{ $activeBranch ? $activeBranch->name : 'Elegir local' }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" onclick="event.stopPropagation();">
            <div class="dropdown-header text-left"><strong>Contexto de trabajo</strong></div>
            <div class="dropdown-divider"></div>
            <div class="px-3 py-2">
                <div class="form-group mb-2"><label for="tenant-context-company" class="mb-1 text-sm">Empresa</label>
                    <select id="tenant-context-company" class="form-control form-control-sm">
                        <option value="">Seleccione una empresa</option>
                        @foreach($availableCompanies as $company)
                            <option value="{{ $company->id }}" {{ $activeCompany && (int)$company->id === (int)$activeCompany->id ? 'selected' : '' }}>{{ $company->trade_name ?: $company->business_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-2"><label for="tenant-context-branch" class="mb-1 text-sm">Local</label>
                    <select id="tenant-context-branch" class="form-control form-control-sm" {{ !$activeCompany ? 'disabled' : '' }}>
                        <option value="">Seleccione un local</option>
                        @foreach($availableBranches as $branch)
                            <option value="{{ $branch->id }}" {{ $activeBranch && (int)$branch->id === (int)$activeBranch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="tenant-context-change" class="btn btn-primary btn-sm btn-block"><i class="fas fa-sync-alt mr-1"></i>Trabajar en este local</button>
                @if(Auth::user()->isTenantOwner() && $activeCompany && $activeBranch)
                    <button type="button" id="tenant-context-clear" class="btn btn-outline-secondary btn-sm btn-block mt-2">Volver a administración</button>
                @endif
                <p class="small text-muted mt-2 mb-0">Solo se muestran empresas y locales activos autorizados para tu usuario.</p>
            </div>
        </div>
    </li>
@endif
