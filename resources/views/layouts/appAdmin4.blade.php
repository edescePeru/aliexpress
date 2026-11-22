<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Dashboard sin areas y sin titulos -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ config('app.name', 'Sermeind') }} | @yield('title')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin/dist/img/icono_logo.ico') }}">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="{{ asset('admin/plugins/pace-progress/themes/black/pace-theme-flat-top.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/jquery-confirm/jquery-confirm.min.css') }}">

    @yield('styles-plugins')

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">

    <style>
        .dropdown-item.active, .dropdown-item:active{
            background-color: #ffffff !important;
        }
    </style>
    @yield('styles')

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini pace-primary">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link" style="color: red"> Tipo de cambio </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link" style="color: blue" id="tasaCompra"></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link" style="color: green" id="tasaVenta"></a>
            </li>
            {{--<li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link">Contact</a>
            </li>--}}
        </ul>

        <!-- SEARCH FORM -->
        {{--<form class="form-inline ml-3">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>--}}

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Messages Dropdown Menu -->
            {{--<li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-comments"></i>
                    <span class="badge badge-danger navbar-badge">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="{{asset('images/users/'.Auth::user()->image)}}" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Brad Diesel
                                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">Call me whenever you can...</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                </div>
            </li>--}}
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" id="showNotifications">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-danger navbar-badge" id="total_notifications"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header" id="quantity_notifications"></span>
                    <div class="dropdown-divider"></div>
                    <div id="body-notifications">

                    </div>
                    <template id="notification-unread">
                        <div class="dropdown-item" >
                            <p class="text-sm">
                                <i class="fas fa-envelope mr-2 text-danger"></i>
                                <span data-message="message" class="text-danger">Nueva cotizacion creada por Operador fgdfgdfgdfg</span>
                                <span class="float-right text-muted text-sm" data-time>Hace 3 mins</span>
                            </p>
                            <a href="#" style="margin-top: 20px" data-read data-content >
                                <span class="float-left text-sm">Marcar como leído</span>
                            </a>
                            <a href="#" style="margin-top: 20px" data-go>
                                <span class="float-right text-sm">Ir</span>
                            </a>
                        </div>
                    </template>
                    <template id="notification-read">
                        <div class="dropdown-item">
                            <p class="text-sm" style="margin-bottom: 10px">
                                <i class="fas fa-envelope mr-2"></i>
                                <span data-message="message">Nueva cotizacion creada por Operador fgdfgdfgdfg</span>
                                <span class="float-right text-muted text-sm" data-time>Hace 3 mins</span>
                            </p>
                            {{--<a href="#" style="margin-top: 20px" data-read>
                                <span class="float-left text-sm">Marcar como leído</span>
                            </a>--}}
                            <a href="#" style="margin-top: 20px" data-go>
                                <span class="float-right text-sm">Ir</span>
                            </a>
                        </div>
                    </template>

                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer" id="read-all">Marcar todos como leídos</a>
                </div>
            </li>
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="{{asset('images/users/'.Auth::user()->image)}}" class="user-image img-circle elevation-2" alt="User Image">
                    <span class="d-none d-md-inline">{{Auth::user()->name}}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <!-- User image -->
                    <li class="user-header bg-primary">
                        <img src="{{asset('images/users/'.Auth::user()->image)}}" class="img-circle elevation-2" alt="User Image">

                        <p>
                            {{ Auth::user()->name }}
                            <small>Member since Nov. 2012</small>
                        </p>
                    </li>
                    <!-- Menu Body -->
                    {{--<li class="user-body">
                    <div class="row">
                        <div class="col-4 text-center">
                            <a href="#">Followers</a>
                        </div>
                        <div class="col-4 text-center">
                            <a href="#">Sales</a>
                        </div>
                        <div class="col-4 text-center">
                            <a href="#">Friends</a>
                        </div>
                    </div>
                        <!-- /.row -->
                    </li>--}}
                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <a href="{{ route('user.profile') }}" class="btn btn-default btn-flat">Perfil</a>
                        <a class="btn btn-default btn-flat float-right" href="{{ route('logout') }}" onclick="event.preventDefault();
                             document.getElementById('logout-form').submit();">
                            <i class="fa fa-power-off"></i>
                            Cerrar sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>

        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" class="brand-link">
            <img src="{{ asset('admin/dist/img/logo_dashboard.jpg') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
                 style="opacity: .8">
            <span class="brand-text font-weight-light">{{ config('app.name', 'Sermeind') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{asset('images/users/'.Auth::user()->image)}}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="{{ route('dashboard.principal') }}" class="d-block">Dashboard</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    @can('enableSystems_navbar')
                    <li class="nav-header "><h5 class="text-bold text-success text-center ">AREA: SISTEMAS</h5></li>
                    @endcan
                    @can('enableLogistic_navbar')
                    <li class="nav-header"><h5 class="text-bold text-cyan text-center ">AREA: LOGISTICA</h5></li>
                    @endcan
                    @can('enablePrincipal_navbar')
                    <li class="nav-header"><h5 class="text-bold text-orange text-center ">AREA: GERENCIA</h5></li>
                    @endcan
                    @can('enableAlmacen_navbar')
                    <li class="nav-header"><h5 class="text-bold text-lightblue text-center ">AREA: ALMACEN</h5></li>
                    @endcan
                    @can('enableOperator_navbar')
                    <li class="nav-header"><h5 class="text-bold text-white text-center ">AREA: OPERACIONES</h5></li>
                    @endcan
                    @can('enableResourcesHumans_navbar')
                    <li class="nav-header"><h5 class="text-bold text-warning text-center ">AREA: RECURSOS HUMANOS</li>
                    @endcan
                    @can('access_permission')
                    <li class="nav-item has-treeview @yield('openAccess')">

                        <a href="#" class="nav-link @yield('activeAccess')">
                            <i class="nav-icon fas fa-eye-slash"></i>
                            <p>
                                Accesos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_permission')
                            <li class="nav-item">
                                <a href="{{ route('permission.index') }}" class="nav-link @yield('activePermissions')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Permisos</p>
                                </a>
                            </li>
                            @endcan
                            @can('list_role')
                            <li class="nav-item">
                                <a href="{{ route('role.index') }}" class="nav-link @yield('activeRoles')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Roles</p>
                                </a>
                            </li>
                            @endcan
                            @can('list_user')
                                <li class="nav-item">
                                    <a href="{{ route('user.index') }}" class="nav-link @yield('activeUser')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Usuarios Activos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.indexEnable') }}" class="nav-link @yield('activeUserEnable')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Usuarios Eliminados</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_customer')
                    <li class="nav-item has-treeview @yield('openCustomer')">

                        <a href="#" class="nav-link @yield('activeCustomer')">
                            <i class="nav-icon fas fa-briefcase"></i>
                            <p>
                                Clientes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_customer')
                            <li class="nav-item">
                                <a href="{{ route('customer.index') }}" class="nav-link @yield('activeListCustomer')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar clientes</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_customer')
                            <li class="nav-item">
                                <a href="{{ route('customer.create') }}" class="nav-link @yield('activeCreateCustomer')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear clientes</p>
                                </a>
                            </li>
                            @endcan
                            @can('destroy_customer')
                            <li class="nav-item">
                                <a href="{{ route('customer.indexrestore') }}" class="nav-link @yield('activeRestoreCustomer')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Restaurar clientes</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_contactName')
                    <li class="nav-item has-treeview @yield('openContactName')">
                        <a href="#" class="nav-link @yield('activeContactName')">
                            <i class="nav-icon fas fa-address-book"></i>
                            <p>
                                Contactos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_contactName')
                                <li class="nav-item">
                                    <a href="{{ route('contactName.index') }}" class="nav-link @yield('activeListContactName')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar contactos</p>
                                    </a>
                                </li>
                            @endcan
                            @can('create_contactName')
                                <li class="nav-item">
                                    <a href="{{ route('contactName.create') }}" class="nav-link @yield('activeCreateContactName')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear contacto</p>
                                    </a>
                                </li>
                            @endcan
                            @can('destroy_contactName')
                                <li class="nav-item">
                                    <a href="{{ route('contactName.indexrestore') }}" class="nav-link @yield('activeRestoreContactName')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Restaurar contactos</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_supplier')
                    <li class="nav-item has-treeview @yield('openSupplier')">
                        <a href="#" class="nav-link @yield('activeSupplier')">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                Proveedores
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_supplier')
                            <li class="nav-item">
                                <a href="{{ route('supplier.index') }}" class="nav-link @yield('activeListSupplier')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar proveedores</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_supplier')
                            <li class="nav-item">
                                <a href="{{ route('supplier.create') }}" class="nav-link @yield('activeCreateSupplier')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear proveedores</p>
                                </a>
                            </li>
                            @endcan
                            @can('destroy_supplier')
                                <li class="nav-item">
                                    <a href="{{ route('supplier.indexrestore') }}" class="nav-link @yield('activeRestoreSupplier')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Restaurar proveedores</p>
                                    </a>
                                </li>
                            @endcan
                            {{--@can('assign_supplier')
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Proveedores y materiales</p>
                                </a>
                            </li>
                            @endcan--}}
                        </ul>
                    </li>
                    @endcan

                    @can('enable_paymentDeadline')
                        <li class="nav-item has-treeview @yield('openPaymentDeadline')">
                            <a href="#" class="nav-link @yield('activePaymentDeadline')">
                                <i class="nav-icon fas fa-handshake"></i>
                                <p>
                                    Plazo de pago
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_paymentDeadline')
                                    <li class="nav-item">
                                        <a href="{{route('paymentDeadline.index')}}" class="nav-link @yield('activeListPaymentDeadline')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar plazos</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_paymentDeadline')
                                    <li class="nav-item">
                                        <a href="{{ route('paymentDeadline.create') }}" class="nav-link @yield('activeCreatePaymentDeadline')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear plazos</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @canany('list_material','list_unitMeasure', 'list_typeScrap', 'list_category', 'list_subcategory', 'list_materialType', 'list_subType', 'list_warrant', 'list_quality', 'list_brand', 'list_exampler')
                    <li class="nav-item has-treeview @yield('openConfig')">
                        <a href="#" class="nav-link @yield('activeConfig')">
                            <i class="fas fa-tools nav-icon"></i>
                            <p>
                                Configuraciones
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_unitMeasure')
                                <li class="nav-item has-treeview @yield('openUnitMeasure')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Unidad de Medida
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_unitMeasure')
                                        <li class="nav-item">
                                            <a href="{{ route('unitmeasure.index') }}" class="nav-link @yield('activeListUnitMeasure')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar unidades</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_unitMeasure')
                                        <li class="nav-item">
                                            <a href="{{ route('unitmeasure.create') }}" class="nav-link @yield('activeCreateUnitMeasure')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear unidades</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_typeScrap')
                                <li class="nav-item has-treeview @yield('openTypeScrap')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Tipo de retacería
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_typeScrap')
                                            <li class="nav-item">
                                                <a href="{{ route('typescrap.index') }}" class="nav-link @yield('activeListTypeScrap')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Listar Tipo retacería</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create_typeScrap')
                                            <li class="nav-item">
                                                <a href="{{ route('typescrap.create') }}" class="nav-link @yield('activeCreateTypeScrap')">
                                                    <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                    <p>Crear Tipo retacería</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_category')
                            <li class="nav-item has-treeview @yield('openCategory')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Categorías
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_category')
                                    <li class="nav-item">
                                        <a href="{{ route('category.index') }}" class="nav-link @yield('activeListCategory')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar categorias</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_category')
                                    <li class="nav-item">
                                        <a href="{{ route('category.create') }}" class="nav-link @yield('activeCreateCategory')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear categorias</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan

                            @can('list_subcategory')
                                <li class="nav-item has-treeview @yield('openSubcategory')">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Subcategorías
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_subcategory')
                                        <li class="nav-item">
                                            <a href="{{ route('subcategory.index') }}" class="nav-link @yield('activeListSubcategory')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar subcategorias</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_subcategory')
                                        <li class="nav-item">
                                            <a href="{{ route('subcategory.create') }}" class="nav-link @yield('activeCreateSubcategory')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear subcategorias</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_materialType')
                                <li class="nav-item has-treeview @yield('openMaterialType')">
                                    <a href="#" class="nav-link @yield('activeMaterialType')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Tipo Materiales
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_materialType')
                                        <li class="nav-item">
                                            <a href="{{ route('materialtype.index') }}" class="nav-link @yield('activeListMaterialType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar tipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_materialType')
                                        <li class="nav-item">
                                            <a href="{{ route('materialtype.create') }}" class="nav-link @yield('activeCreateMaterialType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear tipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_subType')
                                <li class="nav-item has-treeview @yield('openSubType')">
                                    <a href="#" class="nav-link @yield('activeSubType')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            SubTipos
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_subType')
                                        <li class="nav-item">
                                            <a href="{{ route('subtype.index') }}" class="nav-link @yield('activeListSubType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar Subtipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_subType')
                                        <li class="nav-item">
                                            <a href="{{ route('subtype.create') }}" class="nav-link @yield('activeCreateSubType')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear Subtipos</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_warrant')
                                <li class="nav-item has-treeview @yield('openWarrant')">
                                    <a href="#" class="nav-link @yield('activeWarrant')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Cédulas
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_warrant')
                                        <li class="nav-item">
                                            <a href="{{ route('warrant.index') }}" class="nav-link @yield('activeListWarrant')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar cédulas</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_warrant')
                                        <li class="nav-item">
                                            <a href="{{ route('warrant.create') }}" class="nav-link @yield('activeCreateWarrant')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear cédulas</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_quality')
                                <li class="nav-item has-treeview @yield('openQuality')">
                                    <a href="#" class="nav-link @yield('activeQuality')">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>
                                            Calidades
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('list_quality')
                                        <li class="nav-item">
                                            <a href="{{ route('quality.index') }}" class="nav-link @yield('activeListQuality')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Listar calidades</p>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('create_quality')
                                        <li class="nav-item">
                                            <a href="{{ route('quality.create') }}" class="nav-link @yield('activeCreateQuality')">
                                                <i class="far fa-dot-circle nav-icon text-warning"></i>
                                                <p>Crear calidades</p>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan

                            @can('list_brand')
                            <li class="nav-item has-treeview @yield('openBrand')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Marcas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_brand')
                                    <li class="nav-item">
                                        <a href="{{ route('brand.index') }}" class="nav-link @yield('activeListBrand')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar marcas</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_brand')
                                    <li class="nav-item">
                                        <a href="{{ route('brand.create') }}" class="nav-link @yield('activeCreateBrand')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear marcas</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan

                            @can('list_exampler')
                            <li class="nav-item has-treeview @yield('openExampler')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Modelos
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('list_exampler')
                                    <li class="nav-item">
                                        <a href="{{ route('exampler.index') }}" class="nav-link @yield('activeListExampler')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar modelos</p>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('create_exampler')
                                    <li class="nav-item">
                                        <a href="{{ route('exampler.create') }}" class="nav-link @yield('activeCreateExampler')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear modelos</p>
                                        </a>
                                    </li>
                                    @endcan
                                </ul>
                            </li>
                            @endcan


                        </ul>
                    </li>
                    @endcanany

                    @can('list_material')
                        <li class="nav-item has-treeview @yield('openMaterial')">
                            <a href="#" class="nav-link @yield('activeMaterial')">
                                <i class="nav-icon fas fa-boxes"></i>
                                <p>
                                    Materiales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_material')
                                <li class="nav-item">
                                    <a href="{{route('material.index')}}" class="nav-link @yield('activeListMaterial')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar materiales</p>
                                    </a>
                                </li>
                                @endcan
                                @can('create_material')
                                <li class="nav-item">
                                    <a href="{{ route('material.create') }}" class="nav-link @yield('activeCreateMaterial')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear materiales</p>
                                    </a>
                                </li>
                                @endcan
                                @can('enable_material')
                                    <li class="nav-item">
                                        <a href="{{ route('material.index.enable') }}" class="nav-link @yield('activeEnableMaterial')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Habilitar materiales</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('list_quote')
                        <li class="nav-item has-treeview @yield('openQuote')">
                            <a href="#" class="nav-link @yield('activeQuote')">
                                <i class="nav-icon fas fa-boxes"></i>
                                <p>
                                    Cotizaciones
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.list.general') }}" class="nav-link @yield('activeGeneralQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listado general</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_quote')
                                    <li class="nav-item">
                                        <a href="{{route('quote.index')}}" class="nav-link @yield('activeListQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar cotizaciones</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.create') }}" class="nav-link @yield('activeCreateQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear cotización</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('showRaised_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.raise') }}" class="nav-link @yield('activeRaiseQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Elevar cotización</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('destroy_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.deleted') }}" class="nav-link @yield('activeDeletedQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Anuladas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('finish_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.closed') }}" class="nav-link @yield('activeClosedQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Finalizadas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_quote')
                                    <li class="nav-item">
                                        <a href="{{ route('quote.list.lost') }}" class="nav-link @yield('activeLostQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Perdidas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_porcentageQuote')
                                    <li class="nav-item">
                                        <a href="{{ route('porcentageQuote.index') }}" class="nav-link @yield('activePorcentagesQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Porcentajes</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('list_area')
                    <li class="nav-item has-treeview @yield('openInventory')">
                        <a href="#" class="nav-link @yield('activeInventory')">
                            <i class="nav-icon fas fa-book"></i>
                            <p>
                                Inventario Físico
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_area')
                            <li class="nav-item">
                                <a href="{{ route('area.index') }}" class="nav-link @yield('activeAreas')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Áreas</p>
                                </a>
                            </li>
                            @endcan
                            @can('list_location')
                            <li class="nav-item">
                                <a href="{{ route('location.index') }}" class="nav-link @yield('activeLocations')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ubicaciones</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_transfer')
                    <li class="nav-item has-treeview @yield('openTransfer')">
                        <a href="#" class="nav-link @yield('activeTransfer')">
                            <i class="nav-icon fas fa-retweet"></i>
                            <p>
                                Transferencias
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_transfer')
                                <li class="nav-item">
                                    <a href="{{ route('transfer.index') }}" class="nav-link @yield('activeListTransfer')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar traslados</p>
                                    </a>
                                </li>
                            @endcan
                            @can('create_transfer')
                                <li class="nav-item">
                                    <a href="{{ route('transfer.create') }}" class="nav-link @yield('activeCreateTransfer')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear traslado</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_entryPurchase')
                    <li class="nav-item has-treeview @yield('openEntryPurchase')">
                        <a href="#" class="nav-link @yield('activeEntryPurchase')">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>
                                Por compra
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_entryPurchase')
                            <li class="nav-item">
                                <a href="{{ route('entry.purchase.index') }}" class="nav-link @yield('activeListEntryPurchase')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar entradas</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_entryPurchase')
                            <li class="nav-item">
                                <a href="{{ route('entry.purchase.create') }}" class="nav-link @yield('activeCreateEntryPurchase')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear entrada</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('order.purchase.list') }}" class="nav-link @yield('activeListOrdersInEntries')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar órdenes</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('report.materials.entries') }}" class="nav-link @yield('activeReportMaterialEntry')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Reporte Ingresos</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_entryScrap')
                    <li class="nav-item has-treeview @yield('openEntryScrap')">
                        <a href="#" class="nav-link @yield('activeEntryScrap')">
                            <i class="nav-icon fas fa-archive"></i>
                            <p>
                                Por retazos
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_entryScrap')
                            <li class="nav-item">
                                <a href="{{ route('entry.scrap.index') }}" class="nav-link @yield('activeListEntryScrap')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar entradas</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_entryScrap')
                            {{--<li class="nav-item">
                                <a href="{{ route('entry.scrap.create') }}" class="nav-link @yield('activeCreateEntryScrap')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear entrada</p>
                                </a>
                            </li>--}}
                            <li class="nav-item">
                                <a href="{{ route('entry.create.scrap') }}" class="nav-link @yield('activeCreateScrap')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear retazos</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('showMaterials_orderExecutionAlmacen')
                    <li class="nav-item has-treeview @yield('openExecutionsAlmacen')">
                        <a href="#" class="nav-link @yield('activeExecutionsAlmacen')">
                            <i class="nav-icon fas fa-hammer"></i>
                            <p>
                                Órdenes de ejecución
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('listOrders_orderExecutionAlmacen')
                            <li class="nav-item">
                                <a href="{{ route('order.execution.almacen') }}" class="nav-link @yield('activeListExecutionsAlmacen')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar Ordenes</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_orderExecution')
                        <li class="nav-item has-treeview @yield('openOrderExecutions')">
                            <a href="#" class="nav-link @yield('activeOrderExecutions')">
                                <i class="nav-icon fas fa-hammer"></i>
                                <p>
                                    Orden de ejecución
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('order.execution.index') }}" class="nav-link @yield('activeListOrderExecutions')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar Ordenes</p>
                                    </a>
                                </li>
                                {{--<li class="nav-item">
                                    <a href="{{ route('order.execution.finish') }}" class="nav-link @yield('activeListOrderExecutionsFinish')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ordenes Finalizadas</p>
                                    </a>
                                </li>--}}
                            </ul>
                        </li>
                    @endcan

                    @can('list_request')
                    <li class="nav-item has-treeview @yield('openOutputRequest')">
                        <a href="#" class="nav-link @yield('activeOutputRequest')">
                            <i class="nav-icon fas fa-file"></i>
                            <p>
                                Solicitudes
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_request')
                            <li class="nav-item">
                                <a href="{{ route('output.request.index') }}" class="nav-link @yield('activeListOutputRequest')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar solicitudes</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_request')
                            <li class="nav-item">
                                <a href="{{ route('output.request.create') }}" class="nav-link @yield('activeCreateOutputRequest')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear solicitudes</p>
                                </a>
                            </li>
                            @endcan
                            @can('report_output')
                                <li class="nav-item">
                                    <a href="{{ route('report.materials.outputs') }}" class="nav-link @yield('activeReportMaterialOutput')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Materiales en salidas</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('list_output')
                    <li class="nav-item has-treeview @yield('openOutputs')">
                        <a href="#" class="nav-link @yield('activeOutputs')">
                            <i class="nav-icon fas fa-share"></i>
                            <p>
                                Salidas
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('output.confirm') }}" class="nav-link @yield('activeListOutput')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar salidas</p>
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endcan

                    

                    

                    @canany('list_orderPurchaseNormal','list_orderPurchaseExpress')
                        <li class="nav-item has-treeview @yield('openOrderPurchaseGeneral')">
                            <a href="#" class="nav-link @yield('activeOrderPurchaseGeneral')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Órdenes de compra
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @canany('list_orderPurchaseNormal','list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.general.index')}}" class="nav-link @yield('activeListOrderPurchaseGeneral')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar ordenes general</p>
                                        </a>
                                    </li>
                                @endcanany
                                @can('list_orderPurchaseExpress')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.express.index')}}" class="nav-link @yield('activeListOrderPurchaseExpress')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar express</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.normal.index')}}" class="nav-link @yield('activeListOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar normal</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_orderPurchaseExpress')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.express.create') }}" class="nav-link @yield('activeCreateOrderPurchaseExpress')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear Express</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.normal.create') }}" class="nav-link @yield('activeCreateOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear Normal</p>
                                        </a>
                                    </li>
                                @endcan
                                @canany('list_orderPurchaseNormal','list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.list.regularize') }}" class="nav-link @yield('activeListOrderPurchaseRegularize')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar regularizadas</p>
                                        </a>
                                    </li>
                                @endcanany
                                @canany('destroy_orderPurchaseNormal','destroy_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.delete')}}" class="nav-link @yield('activeListOrderPurchaseDelete')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar eliminadas</p>
                                        </a>
                                    </li>
                                @endcanany
                                @canany('list_orderPurchaseNormal','list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.list.lost')}}" class="nav-link @yield('activeListOrderPurchaseLost')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ordenes perdidas</p>
                                        </a>
                                    </li>
                                @endcanany
                            </ul>
                        </li>
                    @endcanany
                    {{--@can('list_orderPurchaseExpress')
                    <li class="nav-item has-treeview @yield('openOrderPurchaseExpress')">
                        <a href="#" class="nav-link @yield('activeOrderPurchaseExpress')">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>
                                Ordenes Express
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('list_orderPurchaseExpress')
                                <li class="nav-item">
                                    <a href="{{route('order.purchase.express.index')}}" class="nav-link @yield('activeListOrderPurchaseExpress')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Listar ordenes</p>
                                    </a>
                                </li>
                            @endcan
                            @can('create_orderPurchaseExpress')
                                <li class="nav-item">
                                    <a href="{{ route('order.purchase.express.create') }}" class="nav-link @yield('activeCreateOrderPurchaseExpress')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Crear orden</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan--}}
                    {{--@can('list_orderPurchaseNormal')
                        <li class="nav-item has-treeview @yield('openOrderPurchaseNormal')">
                            <a href="#" class="nav-link @yield('activeOrderPurchaseNormal')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Ordenes Normales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{route('order.purchase.normal.index')}}" class="nav-link @yield('activeListOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar ordenes</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_orderPurchaseNormal')
                                    <li class="nav-item">
                                        <a href="{{ route('order.purchase.normal.create') }}" class="nav-link @yield('activeCreateOrderPurchaseNormal')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear orden</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan--}}

                    @can('enable_timeline')
                    <li class="nav-item has-treeview @yield('openTimelines')">
                        @can('enable_timeline')
                        <a href="#" class="nav-link @yield('activeTimelines')">
                            <i class="nav-icon far fa-calendar-alt"></i>
                            <p>
                                Cronogramas
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('index_timeline')
                            <li class="nav-item">
                                <a href="{{ route('index.timelines') }}" class="nav-link @yield('activeShowTimelines')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ver cronogramas</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('enableConfig_worker')
                    <li class="nav-item has-treeview @yield('openConfigRH')">
                        @can('enableConfig_worker')
                        <a href="#" class="nav-link @yield('activeConfigRH')">
                            <i class="fas fa-users-cog nav-icon"></i>
                            <p>
                                Configuraciones
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('contract_worker')
                            <li class="nav-item has-treeview @yield('openContract')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Contratos
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('contract.index') }}" class="nav-link @yield('activeListContract')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar contratos</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('contract.create') }}" class="nav-link @yield('activeCreateContract')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear contrato</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('contract.deleted') }}" class="nav-link @yield('activeListContractDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Contratos eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                            @can('statusCivil_worker')
                            <li class="nav-item has-treeview @yield('openCivilStatus')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Estado Civil
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('civilStatuses.index') }}" class="nav-link @yield('activeListCivilStatus')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar Estados Civil</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('civilStatuses.create') }}" class="nav-link @yield('activeCreateCivilStatus')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear Estados Civil</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('civilStatuses.deleted') }}" class="nav-link @yield('activeListCivilStatusDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Estados Civil eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                            @can('function_worker')
                            <li class="nav-item has-treeview @yield('openWorkFunction')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Cargos / Funciones
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('workFunctions.index') }}" class="nav-link @yield('activeListWorkFunction')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar cargos</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('workFunctions.create') }}" class="nav-link @yield('activeCreateWorkFunction')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear cargo</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('workFunctions.deleted') }}" class="nav-link @yield('activeListWorkFunctionDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Cargos eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                            @can('systemPension_worker')
                            <li class="nav-item has-treeview @yield('openPensionSystem')">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>
                                        Sistemas de Pensión
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ route('pensionSystems.index') }}" class="nav-link @yield('activeListPensionSystem')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Listar sistemas</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('pensionSystems.create') }}" class="nav-link @yield('activeCreatePensionSystem')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Crear sistema</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('pensionSystems.deleted') }}" class="nav-link @yield('activeListPensionSystemDeleted')">
                                            <i class="far fa-dot-circle nav-icon text-warning"></i>
                                            <p>Sistemas eliminados</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('enable_worker')
                    <li class="nav-item has-treeview @yield('openWorker')">
                        @can('enable_worker')
                        <a href="#" class="nav-link @yield('activeWorker')">
                            <i class="fas fa-user-tie nav-icon"></i>
                            <p>
                                Colaboradores
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('list_worker')
                            <li class="nav-item">
                                <a href="{{route('worker.index')}}" class="nav-link @yield('activeListWorker')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar colaboradores</p>
                                </a>
                            </li>
                            @endcan
                            @can('create_worker')
                            <li class="nav-item">
                                <a href="{{route('worker.create')}}" class="nav-link @yield('activeCreateWorker')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear colaboradores</p>
                                </a>
                            </li>
                            @endcan
                            @can('restore_worker')
                            <li class="nav-item">
                                <a href="{{route('worker.enable')}}" class="nav-link @yield('activeEnableWorker')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Habilitar colaboradores</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('enable_assistance')
                    <li class="nav-item has-treeview @yield('openAttendance')">
                        @can('enable_assistance')
                        <a href="#" class="nav-link @yield('activeAttendance')">
                            <i class="far fa-calendar-check nav-icon"></i>
                            <p>
                                Asistencia
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @endcan
                        <ul class="nav nav-treeview">
                            @can('register_assistance')
                            <li class="nav-item">
                                <a href="{{ route('workingDay.create') }}" class="nav-link @yield('activeListWorkingDay')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Jornadas Trabajo</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('assistance.index') }}" class="nav-link @yield('activeListAttendance')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar asistencia</p>
                                </a>
                            </li>
                            @endcan
                            @can('report_assistance')
                                <li class="nav-item">
                                    <a href="{{ route('assistance.show') }}" class="nav-link @yield('activeReportAttendance')">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reporte asistencia</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    @can('watch_orderService')
                        {{-- PAY-01: navegación de módulos legacy retirada. --}}
                    @endcan

                    @can('list_categoryInvoice')
                        <li class="nav-item has-treeview @yield('openCategoryInvoice')">
                            <a href="#" class="nav-link @yield('activeCategoryInvoice')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Categoría Finanzas
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_categoryInvoice')
                                    <li class="nav-item">
                                        <a href="{{route('categoryInvoice.index')}}" class="nav-link @yield('activeListCategoryInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar categorías</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_categoryInvoice')
                                    <li class="nav-item">
                                        <a href="{{ route('categoryInvoice.create') }}" class="nav-link @yield('activeCreateCategoryInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear categoría</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('list_invoice')
                        <li class="nav-item has-treeview @yield('openInvoice')">
                            <a href="#" class="nav-link @yield('activeInvoice')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    Facturas
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('list_invoice')
                                    <li class="nav-item">
                                        <a href="{{route('invoice.index')}}" class="nav-link @yield('activeListInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Listar compras/Servicios</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{route('report.invoice.finance')}}" class="nav-link @yield('activeReportInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Facturas finanzas</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('create_invoice')
                                    <li class="nav-item">
                                        <a href="{{ route('invoice.create') }}" class="nav-link @yield('activeCreateInvoice')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear compra/Servicios</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can('show_service')
                        {{-- PAY-01: navegación de módulos legacy retirada. --}}
                    @endcan
                    {{--@can('enable_credit')
                    <li class="nav-header">CRÉDITOS</li>
                    <li class="nav-item has-treeview @yield('openCredit')">
                        <a href="#" class="nav-link @yield('activeCreditSupplier')">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>
                                CRÉDITOS PROVEEDOR
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        @can('control_credit')
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{route('index.credit.supplier')}}" class="nav-link @yield('activeListCreditSupplier')">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Listar créditos</p>
                                </a>
                            </li>
                        </ul>
                        @endcan
                    </li>
                    @endcan--}}
                    {{--@canany('list_report')
                        <li class="nav-header">REPORTES</li>
                    @endcanany
                    @can('list_report')
                        <li class="nav-item has-treeview @yield('openReport')">
                            <a href="#" class="nav-link @yield('activeReport')">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>
                                    REPORTE COTIZACIÓN
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('quote_report')
                                    <li class="nav-item">
                                        <a href="{{route('report.quote.index')}}" class="nav-link @yield('activeReportQuote')">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Cotizaciones</p>
                                        </a>
                                    </li>
                                @endcan

                            </ul>
                        </li>
                    @endcan--}}
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        @yield('page-header')
                        {{--<h1 class="m-0 text-dark">Starter Page</h1>--}}

                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        @yield('page-breadcrumb')
                        {{--<ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Starter Page</li>
                        </ol>--}}

                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    @yield('page-title')
                    {{--<h5 class="card-title">Card header</h5>--}}
                </div>
                <div class="card-body">
                    @yield('content')
                    {{--<h5 class="card-title">Card title</h5>--}}
                </div>
                {{--<div class="card-footer text-muted">
                    <a href="#" class="btn btn-primary">Card link</a>
                    <a href="#" class="card-link">Another link</a>
                </div>--}}
            </div>
            @yield('content-report')
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <!-- Default to the left -->
        <strong>Copyright &copy; <script>document.write(new Date().getFullYear());</script> <a href="https://www.edesce.com/">EDESCE</a>.</strong> Todos los derechos reservados.
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('admin/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin/plugins/pace-progress/pace.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ asset('admin/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('admin/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>

@yield('plugins')

<!-- AdminLTE App -->
<script src="{{ asset('admin/dist/js/adminlte.min.js') }}"></script>
<script src="{{ asset('/js/layout/admin2.js') }}"></script>

@yield('scripts')

</body>
</html>
