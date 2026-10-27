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
    Modificar colaborador
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Colaboradores</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar colaborador</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('worker.index') }}"><i class="fa fa-archive"></i> Colaboradores</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Modificar</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <form id="formCreate" class="form-horizontal" data-url="{{ route('worker.update', $worker->id) }}" enctype="multipart/form-data">
        @include('worker._systemUser')
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Datos generales</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">

                            <div class="col-md-6">
                                <label for="first_name">Nombres </label>
                                <input type="text" id="first_name" name="first_name" value="{{ $worker->first_name }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name">Apellidos </label>
                                <input type="text" id="last_name" name="last_name" value="{{ $worker->last_name }}" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="dni">DNI </label>
                                <input type="text" id="dni" name="dni" class="form-control" value="{{ $worker->dni }}" >
                            </div>

                            <div class="col-md-6">
                                <label for="gender">Género</label>
                                <div class="form-group row">
                                    <div class="col-md-6">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="gender1" name="gender" value="m" {{ ($worker->gender == 'm') ? 'checked':'' }} {{ ($worker->gender == null) ? 'checked':'' }}>
                                            <label for="gender1">Masculino
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="gender2" name="gender" value="f" {{ ($worker->gender == 'f') ? 'checked':'' }}>
                                            <label for="gender2">Femenino
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="work_function">Cargo </label>
                                <select id="work_function" name="work_function" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    <option value="0" {{ ($worker->work_function_id == null) ? 'selected':'' }}>NINGUNO</option>
                                    @foreach( $work_functions as $function )
                                        <option value="{{ $function->id }}" {{ ($worker->work_function_id == $function->id) ? 'selected':'' }}>{{ $function->description }}</option>
                                    @endforeach
                                </select>

                            </div>

                            <div class="col-md-6">
                                <label for="area_worker">Área </label>
                                <select id="area_worker" name="area_worker" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    <option value="0" {{ ($worker->area_worker_id == null) ? 'selected':'' }}>NINGUNO</option>
                                    @foreach( $areaWorkers as $areaWorker )
                                        <option value="{{ $areaWorker->id }}" {{ ($worker->area_worker_id == $areaWorker->id) ? 'selected':'' }}>{{ $areaWorker->name }}</option>
                                    @endforeach
                                </select>

                            </div>

                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="admission_date">Fecha de Ingreso </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" id="admission_date" name="admission_date" value="{{ ($worker->admission_date == null) ? '': $worker->admission_date->format('d/m/Y') }}" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="termination_date">Fecha de Cese </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" id="termination_date" name="termination_date" value="{{ ($worker->termination_date == null) ? '':$worker->termination_date->format('d/m/Y') }}" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <label for="observation">Motivo de Cese </label>
                                <textarea name="reason_for_termination" id="reason_for_termination" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese motivo ....">{{$worker->reason_for_termination}}</textarea>
                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="col-md-6">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Datos de Ubicación</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <label for="personal_address">Dirección </label>
                                <textarea name="personal_address" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese dirección ....">{{ $worker->personal_address }}</textarea>
                            </div>
                            <div class="col-sm-6">
                                <label for="email">Email </label>
                                <textarea name="email" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese email ....">{{ $worker->email }}</textarea>

                            </div>

                        </div>

                        <div class="form-group row">
                            <div class="col-sm-6">
                                <label for="phone">Teléfono </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" id="phone" name="phone" value="{{ $worker->phone }}" class="form-control" >
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="birthplace">Fecha de nacimiento </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" id="birthplace" name="birthplace" value="{{ ($worker->birthplace == null) ? '':$worker->birthplace->format('d/m/Y') }}" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                                </div>
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="level_school">Nivel de instrucción</label>
                                <input type="text" id="level_school" name="level_school" value="{{ $worker->level_school }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="profession">Profesión</label>
                                <input type="text" id="profession" name="profession" class="form-control" value="{{ $worker->profession }}">
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <label for="civil_status">Estado civil </label>
                                <select id="civil_status" name="civil_status" class="form-control select2" style="width: 100%;">
                                    <option></option>
                                    <option value="0" {{ ($worker->civil_status_id == null) ? 'checked':'' }}>NINGUNO</option>
                                    @foreach( $civil_statuses as $civil_status )
                                        <option value="{{ $civil_status->id }}" {{ ($worker->civil_status_id == $civil_status->id) ? 'selected':'' }}>{{ $civil_status->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="num_children">N° de hijos </label>
                                <input type="number" id="num_children" name="num_children" class="form-control" placeholder="0" min="0" value="{{ $worker->num_children }}" step="1">
                            </div>

                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Datos para RR.HH.</h3>

                        @role('resources_humans|contabilidad|admin|principal')
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                                </button>
                            </div>
                        @endrole
                    </div>
                    <div class="card-body">
                        <div class="form-group row">

                            <input type="hidden" id="value_assign_family" value="{{ $value_assign_family }}">

                            <div class="col-md-6">
                                <label for="daily_salary">Salario Diario </label>
                                <input type="number" id="daily_salary" name="daily_salary" class="form-control" placeholder="0.00" min="0" value="{{ $worker->daily_salary }}" step="0.01" >
                            </div>

                            <div class="col-md-6">
                                <label for="monthly_salary">Salario Mensual </label>
                                <input type="number" id="monthly_salary" name="monthly_salary" class="form-control" placeholder="0.00" min="0" value="{{ $worker->monthly_salary }}" step="0.01" >
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="pension_system">Sistema Pensión </label>
                                <select id="pension_system" name="pension_system" class="form-control select2 pension_system" style="width: 100%;">
                                    <option></option>
                                    <option value="0" {{ ($worker->pension_system_id == null) ? 'checked':'' }}>NINGUNO</option>
                                    @foreach( $pension_systems as $pension_system )
                                        <option value="{{ $pension_system->id }}" {{ ($worker->pension_system_id == $pension_system->id) ? 'selected':'' }} data-percentage="{{ $pension_system->percentage }}">{{ $pension_system->description }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="percentage_pension_system">Porcentage Sistema Pensión </label>
                                <input type="number" id="percentage_pension_system" name="percentage_pension_system" class="form-control" value="{{ $worker->percentage_pension_system }}" placeholder="0.00" min="0" step="0.01" >
                            </div>


                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="pension">Pension Alimentos </label>
                                <div class="input-group">
                                    <input type="number" id="pension" name="pension" class="form-control" placeholder="0.00" min="0" value="{{ $worker->pension }}" step="0.01" >
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                            {{--<div class="col-md-6">
                                <label for="working_day">Jornada Laboral </label>
                                <select id="working_day" name="working_day" class="form-control select2 working_day" style="width: 100%;">
                                    <option></option>
                                    <option value="0">NINGUNO</option>
                                    @foreach( $working_days as $working_day )
                                        <option value="{{ $working_day->id }}" {{ ($working_day->id == $worker->working_day_id) ? 'selected':'' }} >{{ $working_day->description }}</option>
                                    @endforeach
                                </select>
                            </div>--}}
                            <div class="col-md-6">
                                <label for="observation">Observación </label>
                                <textarea name="observation" id="observation" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese observación ....">{{ $worker->observation }}</textarea>
                            </div>

                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="col-md-6">
                <div class="card card-primary collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Contactos de Emergencia</h3>

                        @role('resources_humans|contabilidad|admin|principal')
                            <div class="card-tools">
                                <button type="button" id="newContact" class="btn btn-xs btn-warning" > <i class="fas fa-phone-volume"></i> Agregar Contacto </button>

                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                                </button>
                            </div>
                        @endrole
                    </div>
                    <div class="card-body" id="body-contacts">
                        @foreach( $worker->emergency_contacts as $emergency_contact )
                            <div class="callout callout-info">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="work_function">Nombre </label>
                                        <div class="input-group input-group-sm mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-keyboard"></i></span>
                                            </div>
                                            <input type="text" data-contactname name="contacts[]" class="form-control" placeholder="Nombre" value="{{ $emergency_contact->name }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="work_function">Parentesco </label>
                                            <select name="relations[]" class="relation form-control form-control-sm select2" style="width: 100%;">
                                                <option></option>
                                                @foreach( $relationships as $relationship )
                                                    <option value="{{ $relationship->id }}" {{ (optional($relationship)->id == optional($emergency_contact->relationship)->id) ? 'selected' : '' }}>{{ $relationship->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="work_function">Teléfono </label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                </div>
                                                <input type="text" data-phone name="phones[]" class="form-control" placeholder="000000000" value="{{ $emergency_contact->phone }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 offset-4">
                                        <button type="button" data-deletecontact class="btn btn-xs btn-outline-danger btn-block">Eliminar contacto</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar colaborador</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>

    <template id="template-contact">
        <div class="callout callout-info">
            <div class="row">
                <div class="col-md-12">
                    <label for="work_function">Nombre </label>
                    <div class="input-group input-group-sm mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-keyboard"></i></span>
                        </div>
                        <input type="text" data-contactname name="contacts[]" class="form-control" placeholder="Nombre" value="">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="work_function">Parentesco </label>
                        <select name="relations[]" class="relation form-control form-control-sm select2" style="width: 100%;">
                            <option></option>
                            @foreach( $relationships as $relationship )
                                <option value="{{ $relationship->id }}">{{ $relationship->description }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="work_function">Teléfono </label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            </div>
                            <input type="text" data-phone name="phones[]" class="form-control" placeholder="000000000" value="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 offset-4">
                    <button type="button" data-deletecontact class="btn btn-xs btn-outline-danger btn-block">Eliminar contacto</button>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <!-- InputMask -->
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //$('#datemask').inputmask()
            $('#admission_date').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });
            $('#birthplace').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });

            $('#termination_date').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });

            //Initialize Select2 Elements
            $('#work_function').select2({
                placeholder: "Selecione un cargo",
            });
            $('#pension_system').select2({
                placeholder: "Selecione un sistema",
            });
            $('#civil_status').select2({
                placeholder: "Selecione un estado civill",
            });
            $('#working_day').select2({
                placeholder: "Selecione un estado civill",
            });
            $('#area_worker').select2({
                placeholder: "Selecione un área",
            });
            $('.relation').select2({
                placeholder: "Selecione un parentesco",
            });

            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
        })
    </script>
    <script src="{{ asset('js/worker/edit.js') }}"></script>
@endsection
