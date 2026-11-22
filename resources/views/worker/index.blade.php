@extends('layouts.appAdmin2')

@section('openWorker')
    menu-open
@endsection

@section('activeWorker')
    active
@endsection

@section('activeListWorker')
    active
@endsection

@section('title')
    Colaboradores
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- VDialog -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/vdialog/css/vdialog.css') }}">

@endsection



@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Personal · Colaboradores</span><h1 class="page-title">Colaboradores</h1><p class="next-page-description">Directorio de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Directorio de colaboradores</strong><span>Consulta los datos y la ficha laboral.</span></div><div class="next-toolbar-actions flex-wrap">
<div class="dropdown next-column-visibility">
<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-column-visibility-trigger" id="worker-columns-trigger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-columns mr-1" aria-hidden="true"></i> Columnas</button>
<div class="dropdown-menu dropdown-menu-right next-column-visibility-menu pre-scrollable" aria-labelledby="worker-columns-trigger">
<div class="next-column-visibility-header"><strong>Columnas visibles</strong><span>Personaliza esta tabla</span></div>
        <div class="next-column-visibility-grid">
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="code" checked data-column="0" class="custom-control-input" id="customSwitch1">
                <label class="custom-control-label" for="customSwitch1">Código</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="dni" checked data-column="1" class="custom-control-input" id="customSwitch2">
                <label class="custom-control-label" for="customSwitch2">DNI</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="first_name" checked data-column="2" class="custom-control-input" id="customSwitch3">
                <label class="custom-control-label" for="customSwitch3">Nombres</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="last_name" checked data-column="3" class="custom-control-input" id="customSwitch4">
                <label class="custom-control-label" for="customSwitch4">Apellidos</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="personal_address" data-column="4" class="custom-control-input" id="customSwitch5">
                <label class="custom-control-label" for="customSwitch5">Dirección</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="phone" data-column="5" class="custom-control-input" id="customSwitch6">
                <label class="custom-control-label" for="customSwitch6">Teléfono</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="email" data-column="6" class="custom-control-input" id="customSwitch7">
                <label class="custom-control-label" for="customSwitch7">Email</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="work_function_id" checked data-column="7" class="custom-control-input" id="customSwitch8">
                <label class="custom-control-label" for="customSwitch8">Cargo</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="gender" data-column="8" class="custom-control-input" id="customSwitch9">
                <label class="custom-control-label" for="customSwitch9">Género</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="birthplace" data-column="9" class="custom-control-input" id="customSwitch10">
                <label class="custom-control-label" for="customSwitch10">Fecha Nac.</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="age" data-column="10" class="custom-control-input" id="customSwitch11">
                <label class="custom-control-label" for="customSwitch11">Edad</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="level_school" data-column="11" class="custom-control-input" id="customSwitch12">
                <label class="custom-control-label" for="customSwitch12">Nivel Estudios</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="num_children" data-column="12" class="custom-control-input" id="customSwitch13">
                <label class="custom-control-label" for="customSwitch13">N° de Hijos</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="admission_date" data-column="13" class="custom-control-input" id="customSwitch14">
                <label class="custom-control-label" for="customSwitch14">Fecha Ingreso</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="termination_date" data-column="14" class="custom-control-input" id="customSwitch15">
                <label class="custom-control-label" for="customSwitch15">Fecha Cese</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="daily_salary" data-column="15" class="custom-control-input" id="customSwitch16">
                <label class="custom-control-label" for="customSwitch16">Salario Diario</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="monthly_salary" data-column="16" class="custom-control-input" id="customSwitch17">
                <label class="custom-control-label" for="customSwitch17">Salario Mensual</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="pension" data-column="17" class="custom-control-input" id="customSwitch18">
                <label class="custom-control-label" for="customSwitch18">Pension Alimentos</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="essalud" data-column="18" class="custom-control-input" id="customSwitch19">
                <label class="custom-control-label" for="customSwitch19">ESSALUD</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="assign_family" data-column="19" class="custom-control-input" id="customSwitch20">
                <label class="custom-control-label" for="customSwitch20">Asignación Familiar</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="five_category" data-column="20" class="custom-control-input" id="customSwitch21">
                <label class="custom-control-label" for="customSwitch21">Quinta Categoría</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" readonly data-column="21" class="custom-control-input" id="customSwitch22">
                <label class="custom-control-label" for="customSwitch22">Contrato</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="civil_status_id" data-column="22" class="custom-control-input" id="customSwitch23">
                <label class="custom-control-label" for="customSwitch23">Estado Civil</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="pension_system_id" data-column="23" class="custom-control-input" id="customSwitch24">
                <label class="custom-control-label" for="customSwitch24">Sistema Pensión</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="percentage_pension_system" data-column="24" class="custom-control-input" id="customSwitch25">
                <label class="custom-control-label" for="customSwitch25">Porc. Sistema Pensión</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="observation" data-column="25" class="custom-control-input" id="customSwitch26">
                <label class="custom-control-label" for="customSwitch26">Observación</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="area_worker_id" checked data-column="26" class="custom-control-input" id="customSwitch27">
                <label class="custom-control-label" for="customSwitch27">Área</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="profession" data-column="27" class="custom-control-input" id="customSwitch28">
                <label class="custom-control-label" for="customSwitch28">Profesión</label>
            </div>
            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success next-column-visibility-option">
                <input type="checkbox" data-key="reason_for_termination" data-column="28" class="custom-control-input" id="customSwitch29">
                <label class="custom-control-label" for="customSwitch29">Motivo de Cese</label>
            </div>
        </div>
</div></div>
<button type="button" class="btn btn-outline-secondary btn-sm" id="btn-exportExcel"><i class="far fa-file-excel" aria-hidden="true"></i> Exportar excel</button>
@can('create_worker')<a href="{{ route('worker.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus mr-1" aria-hidden="true"></i>Nuevo colaborador</a>@endcan
</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('worker.index') }}"><i class="fa fa-archive"></i> Colaboradores</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <section class="next-operational-list" aria-label="Directorio de colaboradores">

    <div class="next-form-section"><div class="table-responsive" tabindex="0" aria-label="Colaboradores; desplazamiento horizontal de tabla">
        <table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table">
            <thead>
            <tr>
                <th>Código</th>
                <th>DNI</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Género</th>
                <th>Fecha Nac.</th>
                <th>Edad</th>
                <th>Nivel Estudios</th>
                <th>N° de Hijos</th>
                <th>Fecha Ingreso</th>
                <th>Fecha Cese</th>
                <th>Salario Diario</th>
                <th>Salario Mensual</th>
                <th>Pension Alimentos</th>
                <th>ESSALUD</th>
                <th>Asignación Familiar</th>
                <th>Quinta Categoría</th>
                <th>Contrato</th>
                <th>Estado Civil</th>
                <th>Sistema Pensión</th>
                <th>Porc. de Sist. Pension</th>
                <th>Observación</th>
                <th>Área</th>
                <th>Profesión</th>
                <th>Motivo de Cese</th>
                <th></th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    </div></section>
    <div id="modalFinishContract" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Finalizar contracto</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formFinishContract" data-url="{{ route('contract.finish') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="type" name="type">
                        <input type="hidden" id="worker_id" name="worker_id">
                        <input type="hidden" id="contract_id" name="contract_id">
                        <input type="hidden" id="finish_contract_id" name="finish_contract_id">
                        <strong>¿Desea terminar el contrato actual activo?</strong> <br>
                        <strong>Colaborador: </strong>
                        <p id="name"></p>
                        <strong>Contrato Actual: </strong>
                        <p id="contrato"></p>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="date_finish">Fecha Finalización<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" id="date_finish" name="date_finish" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="reason">Motivo </label>

                                <textarea name="reason" id="reason" class="form-control"></textarea>

                            </div>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-finish_contract" class="btn btn-danger">Finalizar Contrato</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="modalFinishContractDelete" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Finalizar contracto</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formFinishContractDelete" data-url="{{ route('contract.finish.delete') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="type" name="type">
                        <input type="hidden" id="worker_id" name="worker_id">
                        <input type="hidden" id="contract_id" name="contract_id">
                        <input type="hidden" id="finish_contract_id" name="finish_contract_id">
                        <strong>¿Desea eliminar el término de contrato actual activo?</strong> <br>
                        <strong>Colaborador: </strong>
                        <p id="name"></p>
                        <strong>Contrato Actual: </strong>
                        <p id="contrato"></p>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-finish_contract_delete" class="btn btn-danger">Eliminar Finalización de Contrato</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Vdialog -->
    <script src="{{ asset('admin/plugins/vdialog/js/lib/vdialog.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>

@endsection

@section('scripts')
    <script>
        $(function () {
            //$('#datemask').inputmask()
            $('#date_finish').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });

        })
    </script>
    <script src="{{ asset('js/worker/index.js') }}"></script>
@endsection