@if(
    Auth::check() &&
    !Auth::user()->isPlatformAdmin() &&
    \App\Support\TenantContext::hasContext()
)
    @php
        $activeCompany = \App\Support\TenantContext::company();
        $activeBranch = \App\Support\TenantContext::branch();

        $availableCompanies = Auth::user()
            ->companies()
            ->wherePivot('is_active', true)
            ->where('companies.tenant_id', Auth::user()->tenant_id)
            ->where('companies.is_active', true)
            ->orderByDesc('company_user.is_default')
            ->orderBy('companies.business_name')
            ->get();

        $availableBranches = Auth::user()
            ->branches()
            ->wherePivot('is_active', true)
            ->where('branches.company_id', $activeCompany->id)
            ->where('branches.is_active', true)
            ->orderByDesc('branch_user.is_default')
            ->orderByDesc('branches.is_main')
            ->orderBy('branches.name')
            ->get();
    @endphp

    <li class="nav-item dropdown d-none d-md-block tenant-context-navbar">
        <a
                href="#"
                class="nav-link dropdown-toggle"
                data-toggle="dropdown"
                title="Empresa y local activos"
        >
            <i class="fas fa-building mr-1"></i>

            <span>
                {{ $activeCompany->trade_name
                    ?: $activeCompany->business_name }}
            </span>

            <span class="text-muted mx-1">—</span>

            <i class="fas fa-store mr-1"></i>

            <span>
                {{ $activeBranch->name }}
            </span>
        </a>

        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" onclick="event.stopPropagation();" >
            <div class="dropdown-header text-left">
                <strong>Contexto de trabajo</strong>
            </div>

            <div class="dropdown-divider"></div>

            <div class="px-3 py-2">
                <div class="form-group mb-2">
                    <label
                            for="tenant-context-company"
                            class="mb-1 text-sm"
                    >
                        Empresa
                    </label>

                    <select
                            id="tenant-context-company"
                            class="form-control form-control-sm"
                    >
                        @foreach($availableCompanies as $company)
                            <option
                                    value="{{ $company->id }}"
                                    {{ $company->id === $activeCompany->id
                                        ? 'selected'
                                        : '' }}
                            >
                                {{ $company->trade_name
                                    ?: $company->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-2">
                    <label
                            for="tenant-context-branch"
                            class="mb-1 text-sm"
                    >
                        Local
                    </label>

                    <select
                            id="tenant-context-branch"
                            class="form-control form-control-sm"
                    >
                        @foreach($availableBranches as $branch)
                            <option
                                    value="{{ $branch->id }}"
                                    {{ $branch->id === $activeBranch->id
                                        ? 'selected'
                                        : '' }}
                            >
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                        type="button"
                        id="tenant-context-change"
                        class="btn btn-primary btn-sm btn-block"
                >
                    <i class="fas fa-sync-alt mr-1"></i>
                    Cambiar contexto
                </button>
            </div>
        </div>
    </li>
@endif