        @can('showBoxAlmacen_dashboard')
            @canany(['list_entryPurchase', 'list_entryInventory'])
                <section class="next-dashboard-section" aria-labelledby="dashboard-entry-reports-title">
                    <div class="next-dashboard-panel">
                        <header class="next-dashboard-panel-header">
                            <div><h2 id="dashboard-entry-reports-title">Reportes de almacén</h2><p>Entradas por compra e inventario de la empresa activa.</p></div>
                            <div class="next-toolbar-actions"><button type="button" class="btn btn-outline-secondary btn-sm" id="btn-downloadEntries" data-toggle="modal" data-target="#modalEntries"><i class="fas fa-file-excel mr-1" aria-hidden="true"></i>Exportar entradas</button></div>
                        </header>
                    </div>
                </section>
                @include('dashboard.partials.entry-export')
            @endcanany
        @endcan
