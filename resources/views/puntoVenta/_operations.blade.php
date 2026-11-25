    <div class="modal fade next-aux-modal" id="modalPagosParciales" tabindex="-1" role="dialog" aria-labelledby="pp_title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="pp_title">Pagos parciales</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body next-aux-modal-form">

                    <input type="hidden" id="pp_sale_id">


                    <div id="pp_feedback" class="alert alert-danger d-none" role="alert"></div>
                    <p id="pp_progress_text" class="text-muted" aria-live="polite"></p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Total venta</label>
                            <input type="text" id="pp_total_venta" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Total abonado</label>
                            <input type="text" id="pp_total_abonado" class="form-control form-control-sm" readonly>
                        </div>
                    </div>

                    <hr>


                    @if($salePermissions['payments'])
                    <h6><b>Datos del pago nuevo</b></h6>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Fecha <span class="right badge badge-danger">(*)</span></label>
                            <input type="date" id="pp_fecha_pago" class="form-control form-control-sm">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Monto <span class="right badge badge-danger">(*)</span></label>
                            <input type="number" step="0.01" min="0" id="pp_monto" class="form-control form-control-sm">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Caja <span class="right badge badge-danger">(*)</span></label>
                            <select id="pp_cash_box_id" class="form-control form-control-sm select2">
                                <option value=""></option>
                                @foreach($cashBoxes as $b)
                                    <option value="{{ $b['id'] }}"
                                            data-type="{{ $b['type'] }}"
                                            data-uses_subtypes="{{ $b['uses_subtypes'] ? 1 : 0 }}">
                                        {{ $b['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3" id="pp_cash_box_subtype_wrap" hidden>
                            <label>Canal / Subtipo <span class="right badge badge-danger">(*)</span></label>
                            <select id="pp_cash_box_subtype_id" class="form-control form-control-sm select2">
                                <option value=""></option>
                                @foreach($subtypes as $st)
                                    <option value="{{ $st['id'] }}"
                                            data-is_deferred="{{ $st['is_deferred'] ?? 0 }}" data-cash-box="{{ $st['cash_box_id'] ?? '' }}">
                                        {{ $st['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-right mt-3">
                        <button type="button" id="btnGuardarPagoParcial" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Agregar pago
                        </button>
                    </div>

                    <hr>


@endif
                    <h6><b>Pagos realizados</b></h6>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th class="text-center">Fecha</th>
                                <th class="text-right">Monto</th>
                                <th>Caja</th>
                                <th class="text-center">Acción</th>
                            </tr>
                            </thead>
                            <tbody id="pp_body_pagos">
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal" aria-label="Cerrar">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade next-aux-modal" id="modalGenerarComprobante" tabindex="-1" role="dialog" aria-labelledby="gc_title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="gc_title">Generar comprobante</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body next-aux-modal-form">

                    <input type="hidden" id="gc_sale_id">
                    <input type="hidden" id="gc_free_sale" value="0">

                    <ul class="nav nav-tabs" id="gc_tabs" role="tablist" data-boleta="{{ auth()->user()->can('generarBoleta_puntoVenta') ? 1 : 0 }}" data-factura="{{ auth()->user()->can('generarFactura_puntoVenta') ? 1 : 0 }}">
                        <li class="nav-item">
                            <a class="nav-link active" id="gc_boleta_tab" data-toggle="tab" href="#gc_boleta" role="tab">
                                Boleta
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="gc_factura_tab" data-toggle="tab" href="#gc_factura" role="tab">
                                Factura
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">

                        <div class="tab-pane fade show active" id="gc_boleta" role="tabpanel">
                            <div class="form-group mb-3">
                                <label>DNI <span class="text-danger">*</span></label>

                                <div class="input-group input-group-sm">
                                    <input
                                            type="text"
                                            maxlength="8"
                                            id="gc_dni"
                                            class="form-control"
                                            inputmode="numeric"
                                            autocomplete="off"
                                    >

                                    <div class="input-group-append">
                                        <button
                                                type="button"
                                                id="btnBuscarGcDni"
                                                class="btn btn-outline-primary"
                                                title="Buscar DNI"
                                        >
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Nombre <span class="text-danger">*</span></label>
                                <input type="text" id="gc_name" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="form-group mb-3">
                                <label>Email (Opcional)</label>
                                <input type="text" id="gc_email_boleta" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="tab-pane fade" id="gc_factura" role="tabpanel">
                            <div class="form-group mb-3">
                                <label>RUC <span class="text-danger">*</span></label>

                                <div class="input-group input-group-sm">
                                    <input
                                            type="text"
                                            maxlength="11"
                                            id="gc_ruc"
                                            class="form-control"
                                            inputmode="numeric"
                                            autocomplete="off"
                                    >

                                    <div class="input-group-append">
                                        <button
                                                type="button"
                                                id="btnBuscarGcRuc"
                                                class="btn btn-outline-primary"
                                                title="Buscar RUC"
                                        >
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Razón Social <span class="text-danger">*</span></label>
                                <input type="text" id="gc_razon_social" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="form-group mb-3">
                                <label>Dirección Fiscal <span class="text-danger">*</span></label>
                                <input type="text" id="gc_direccion_fiscal" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="form-group mb-3">
                                <label>Email (Opcional)</label>
                                <input type="text" id="gc_email_factura" class="form-control form-control-sm">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btnConfirmarGenerarComprobante" class="btn btn-primary btn-sm">
                        Generar comprobante
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal" aria-label="Cerrar">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade next-aux-modal" id="modalNotaCreditoParcial" tabindex="-1" role="dialog" aria-labelledby="nc_title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="nc_title">Nota de crédito parcial</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body next-aux-modal-form">
                    <input type="hidden" id="nc_partial_sale_id">

                    <div class="mb-2">
                        <strong>Venta:</strong> <span id="nc_partial_sale_code"></span><br>
                        <strong>Cliente:</strong> <span id="nc_partial_customer"></span><br>
                        <strong>Comprobante:</strong> <span id="nc_partial_document"></span>
                    </div>

                    <div class="table-responsive" tabindex="0"><table class="table table-bordered table-sm">
                        <thead>
                        <tr>
                            <th>Producto / Servicio</th>
                            <th class="text-center">Vendido</th>
                            <th class="text-center">Ya devuelto</th>
                            <th class="text-center">Disponible</th>
                            <th class="text-center">Devolver</th>
                            <th class="text-right">Total NC</th>
                        </tr>
                        </thead>
                        <tbody id="body-nc-partial-items"></tbody>
                    </table></div>

                    <div class="text-right">
                        <strong>Total Nota de Crédito: <span id="nc_partial_currency"></span> <span id="nc_partial_total">0.00</span></strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button id="btn-submit-nc-partial" class="btn btn-primary">
                        Generar Nota de Crédito Parcial
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" aria-label="Cerrar">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade next-aux-modal" id="modalNcPartialItems" tabindex="-1" role="dialog" aria-labelledby="nc_items_title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="nc_items_title">
                        Seleccionar productos a devolver
                    </h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body next-aux-modal-form">

                    <div class="alert alert-info">
                        Seleccione exactamente los productos que serán devueltos.
                    </div>

                    <div class="form-group mb-3">
                        <label for="nc_partial_item_search">
                            Buscar el código del item
                        </label>

                        <div class="input-group">
                            <input type="text"
                                   id="nc_partial_item_search"
                                   class="form-control"
                                   autocomplete="off"
                                   placeholder="Pistolee o escriba el código y presione Enter">

                            <div class="input-group-append">
                                <button type="button"
                                        id="btn-search-nc-partial-item"
                                        class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                    Buscar
                                </button>
                            </div>
                        </div>

                        <small class="form-text text-muted">
                            Al encontrar el código, el item será seleccionado automáticamente.
                        </small>
                    </div>

                    <div id="nc-partial-item-search-message" aria-live="polite"></div>

                    <div class="next-form-section">
                        <div id="body-nc-partial-item-selection"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            id="btn-confirm-nc-partial-items"
                            class="btn btn-primary">
                        Confirmar selección
                    </button>

                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-dismiss="modal" id="nc-items-back" aria-label="Volver">
                        Volver
                    </button>
                </div>

            </div>
        </div>
    </div>
