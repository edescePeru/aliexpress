@extends('layouts.appAdmin2')

@section('title', 'Dashboard')
@section('body-class', 'next-dashboard-shell')

@section('content')
    @php
        $dashboardCompany = \App\Support\TenantContext::company();
        $dashboardBranch = \App\Support\TenantContext::branch();
        $dashboardCompanyName = $dashboardCompany->trade_name ?: $dashboardCompany->business_name;
    @endphp
    <main class="next-dashboard" aria-label="Resumen principal">
        <section class="next-executive-hero" aria-labelledby="executive-hero-title">
            <div class="next-executive-hero-content">
                <span class="next-executive-hero-kicker">Resumen ejecutivo</span>
                <h2 id="executive-hero-title">Buen día, {{ Auth::user()->name }}</h2>
                <p>Indicadores, desempeño comercial y actividad financiera en una sola vista.</p>
                <div class="next-executive-hero-meta" aria-label="Contexto activo y fecha">
                    <span><i class="fas fa-building" aria-hidden="true"></i>{{ $dashboardCompanyName }}</span>
                    <span class="next-executive-meta-divider" aria-hidden="true">·</span>
                    <span><i class="fas fa-store" aria-hidden="true"></i>{{ $dashboardBranch->name }}</span>
                    <span class="next-executive-meta-separator" aria-hidden="true"></span>
                    <span><i class="far fa-calendar-alt" aria-hidden="true"></i>{{ now('America/Lima')->translatedFormat('d \d\e F \d\e Y') }}</span>
                </div>
            </div>
        </section>

        @can('showboxes_dashboard')
            <section class="next-dashboard-section" aria-labelledby="dashboard-kpis-title">
                <h2 id="dashboard-kpis-title" class="sr-only">Indicadores clave</h2>
                <div class="next-kpi-families">
                    @canany(['list_material', 'list_entryPurchase', 'list_request'])
                        <div class="next-kpi-family next-kpi-family--primary">
                            <div class="next-kpi-family-heading">
                                <span class="next-kpi-family-icon"><i class="fas fa-warehouse" aria-hidden="true"></i></span>
                                <div><strong>Operación</strong><span>Materiales y almacén</span></div>
                            </div>
                            <div class="next-kpi-grid next-kpi-grid--primary">
                                @can('list_material')
                                    @include('dashboard.partials.kpi', ['href' => route('material.index'), 'label' => 'Materiales', 'value' => $materialCount, 'icon' => 'fas fa-boxes', 'variant' => 'primary', 'tone' => 'brand'])
                                @endcan
                                @can('list_entryPurchase')
                                    @include('dashboard.partials.kpi', ['href' => route('entry.purchase.index'), 'label' => 'Entradas a almacén', 'value' => $entriesCount, 'icon' => 'fas fa-sign-in-alt', 'variant' => 'primary', 'tone' => 'success'])
                                @endcan
                                @can('list_request')
                                    @include('dashboard.partials.kpi', ['href' => route('output.request.index'), 'label' => 'Salidas de almacén', 'value' => $outputCount, 'icon' => 'fas fa-sign-out-alt', 'variant' => 'primary', 'tone' => 'info'])
                                @endcan
                            </div>
                        </div>
                    @endcanany

                    @can('list_invoice')
                        <div class="next-kpi-family next-kpi-family--finance">
                            <div class="next-kpi-family-heading">
                                <span class="next-kpi-family-icon"><i class="fas fa-coins" aria-hidden="true"></i></span>
                                <div><strong>Finanzas</strong><span>Documentos registrados</span></div>
                            </div>
                            <div class="next-kpi-grid next-kpi-grid--finance">
                                @include('dashboard.partials.kpi', ['href' => route('invoice.index'), 'label' => 'Facturas', 'value' => $invoiceCount, 'icon' => 'fas fa-file-invoice-dollar', 'variant' => 'primary', 'tone' => 'warning'])
                            </div>
                        </div>
                    @endcan

                    @canany(['list_customer', 'list_contactName', 'list_supplier'])
                        <div class="next-kpi-family next-kpi-family--secondary">
                            <div class="next-kpi-family-heading">
                                <span class="next-kpi-family-icon"><i class="fas fa-handshake" aria-hidden="true"></i></span>
                                <div><strong>Relaciones</strong><span>Directorio comercial</span></div>
                            </div>
                            <div class="next-kpi-grid next-kpi-grid--secondary">
                                @can('list_customer')
                                    @include('dashboard.partials.kpi', ['href' => route('customer.index'), 'label' => 'Clientes', 'value' => $customerCount, 'icon' => 'fas fa-user-tie', 'variant' => 'secondary'])
                                @endcan
                                @can('list_contactName')
                                    @include('dashboard.partials.kpi', ['href' => route('contactName.index'), 'label' => 'Contactos', 'value' => $contactNameCount, 'icon' => 'fas fa-address-book', 'variant' => 'secondary'])
                                @endcan
                                @can('list_supplier')
                                    @include('dashboard.partials.kpi', ['href' => route('supplier.index'), 'label' => 'Proveedores', 'value' => $supplierCount, 'icon' => 'fas fa-truck', 'variant' => 'secondary'])
                                @endcan
                            </div>
                        </div>
                    @endcanany
                </div>
            </section>
        @endcan

        @can('showBoxMetas_dashboard')
            <section class="next-dashboard-section" aria-labelledby="dashboard-goals-title">
                <div class="next-dashboard-panel next-goals-panel">
                    <header class="next-dashboard-panel-header">
                        <div class="next-goals-heading">
                            <span class="next-goals-heading-icon"><i class="fas fa-trophy" aria-hidden="true"></i></span>
                            <div>
                                <span class="next-section-kicker">Desempeño comercial</span>
                                <h2 id="dashboard-goals-title">Ranking de cumplimiento de metas</h2>
                                <p>{{ $rankingPeriodText ?: 'Periodo actual sin configuración disponible' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('metas.ranking') }}" class="btn btn-outline-secondary btn-sm">Ver ranking completo</a>
                    </header>
                    <div class="next-dashboard-panel-body">
                        @if(empty($rankingMetasDashboard))
                            <div class="next-dashboard-empty" role="status">
                                <span class="next-dashboard-empty-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                                <strong>Sin datos de metas</strong>
                                <span>No hay metas configuradas para el periodo actual.</span>
                            </div>
                        @else
                            <ol class="next-goals-ranking">
                                @foreach($rankingMetasDashboard as $row)
                                    @php
                                        $pct = max(0, min(100, $row['porcentaje']));
                                        $progressClass = $pct < 40 ? 'is-danger' : ($pct < 80 ? 'is-warning' : 'is-success');
                                    @endphp
                                    <li class="next-goals-item">
                                        <span class="next-goals-position">{{ $loop->iteration }}</span>
                                        <div class="next-goals-detail">
                                            <div class="next-goals-summary">
                                                <strong>{{ $row['worker_name'] }}</strong>
                                                <span>{{ number_format($row['porcentaje'], 1) }}%</span>
                                            </div>
                                            <div class="next-goals-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Cumplimiento de {{ $row['worker_name'] }}">
                                                <span class="{{ $progressClass }}" style="width: {{ $pct }}%"></span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </section>
        @endcan

        @can('showBoxGraficosVentas_dashboard')
            <section class="next-dashboard-section" aria-labelledby="dashboard-trends-title">
                <h2 id="dashboard-trends-title" class="next-dashboard-section-title">Actividad financiera</h2>
                <div class="next-chart-grid">
                    @include('dashboard.partials.chart', [
                        'kind' => 'sales', 'title' => 'Ventas', 'periodId' => 'sale-chart-period',
                        'canvasId' => 'sale-chart', 'buttonClass' => 'filter-btn-sale',
                    ])
                    @include('dashboard.partials.chart', [
                        'kind' => 'cashflow', 'title' => 'Ingresos vs egresos', 'periodId' => 'utilidad-chart-period',
                        'canvasId' => 'utilidad-chart', 'buttonClass' => 'filter-btn-utilidad',
                    ])
                </div>
            </section>
        @endcan
    </main>

    @can('showBoxGraficosVentas_dashboard')
        @include('dashboard.partials.range-modal', [
            'modalId' => 'dateRangeModalSale', 'title' => 'Rango de ventas', 'fieldSuffix' => 'sale',
            'filterClass' => 'filter-btn-sale', 'exportId' => 'btn-export-range-sales',
            'exportUrl' => route('sales.export.range'),
        ])
        @include('dashboard.partials.range-modal', [
            'modalId' => 'dateRangeModalUtilidad', 'title' => 'Rango de ingresos y egresos', 'fieldSuffix' => 'utilidad',
            'filterClass' => 'filter-btn-utilidad', 'exportId' => 'btn-export-cashflow-range',
            'exportUrl' => route('cashflow.export.range'),
        ])
    @endcan
@endsection

@section('scripts')
    @can('showBoxGraficosVentas_dashboard')
        <script src="{{ asset('admin/plugins/chart.js/Chart.min.js') }}"></script>
        <script src="{{ asset('js/dashboard/ordersChart.js') }}?v={{ filemtime(public_path('js/dashboard/ordersChart.js')) }}"></script>
    @endcan
@endsection
