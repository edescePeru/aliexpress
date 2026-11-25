<li class="nav-header">ADMINISTRACIÓN DEL NEGOCIO</li>
@can('list_company')
    <li class="nav-item"><a href="{{ route('tenantCompany.index') }}" class="nav-link {{ request()->routeIs('tenantCompany.*', 'tenantBranch.*') ? 'active' : '' }}"><i class="nav-icon fas fa-building"></i><p>Empresas</p></a></li>
@endcan
@can('listUser_configUserWeb')
    <li class="nav-item"><a href="{{ route('configUserWeb.index') }}" class="nav-link @yield('activeConfigUserWeb')"><i class="nav-icon fas fa-users"></i><p>Usuarios Web</p></a></li>
@endcan
