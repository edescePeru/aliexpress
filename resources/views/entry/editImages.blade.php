@extends('layouts.appAdmin2')

@section('openEntryPurchase')
    menu-open
@endsection

@section('activeEntryPurchase')
    active
@endsection

@section('activeListEntryPurchase')
    active
@endsection

@section('title')
    Entradas Compra Imagenes
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/summernote/summernote-bs4.css') }}">
@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
        .item {
            position: relative;
            width: 100%;
            height: 300px;
            margin-bottom: 25px;
        }
        .item .zoo-item {
            border: 1px solid #EEEEEE;
            margin: 10px;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Imágenes Extras de Entradas</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar imágenes de entrada</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('entry.purchase.index') }}"><i class="fa fa-key"></i> Entradas por compra</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar imágenes</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Importante!</strong> Puede modifique el código de los documentos antiguos y agregue nuevas imágenes.
                <br>
                <strong>Importante!</strong> Si desea cambiar una imagen antigua entonces primero elimínela y vuelva a agregarla.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>
    <br>
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <form id="formEdit" class="form-horizontal" data-url="{{ route('save.images.entry', $entry->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="row" >
            <div class="col-md-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">ARCHIVOS DE FACTURAS</h3>

                        <div class="card-tools">
                            <a id="addImageInvoice" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Agregar imagen">
                                <i class="far fa-images"></i> Agregar Imagen
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" >
                        <div class="row" id="body-imagesInvoice">
                            @foreach( $imagesInvoices as $image )
                            <div class="col-md-4">
                                <div class="card card-outline card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Imagen Guardada</h3>

                                        <div class="card-tools">
                                            <button type="button" data-imageeditold class="btn btn-sm btn-outline-success" data-toggle="tooltip" data-placement="top" title="Guardar descripción y orden" ><i class="fas fa-save"></i></i>
                                            </button>
                                            <button type="button" data-imagedeleteold class="btn btn-sm btn-outline-danger" data-toggle="tooltip" data-placement="top" title="Quitar de la base de datos" ><i class="fas fa-trash"></i></i>
                                            </button>
                                        </div>
                                        <!-- /.card-tools -->
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body">
                                        <input type="hidden" value="{{ $image->id }}">
                                        <div class="form-group">
                                            <label for="description">Descripción <span class="right badge badge-danger">(*)</span></label>
                                            <textarea class="form-control" data-img data-imgold rows="2" placeholder="Enter ...">{{ $image->code }}</textarea>
                                            <input type="hidden" data-type value="i">
                                        </div>
                                        <div class="form-group">
                                            <label for="description">Imagen <span class="right badge badge-danger">(*)</span></label>
                                            <br>
                                            @if ( $image->type_file == 'pdf' )
                                                <a href="{{ route('entry.document.extra', ['filename' => $image->image]) }}" target="_blank" class="btn btn-outline-success ">Ver PDF</a>
                                            @else
                                                <button type="button" data-image="{{ route('entry.document.extra', ['filename' => $image->image]) }}" data-alt="{{ $image->code }}" class="btn btn-outline-primary ">Ver Imagen</button>
                                            @endif

                                        </div>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                            @endforeach
                        </div>


                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <br>
        <div class="row" >
            <div class="col-md-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">ARCHIVOS DE GUIAS DE REMISION</h3>

                        <div class="card-tools">
                            <a id="addImageGuide" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Agregar imagen">
                                <i class="far fa-images"></i> Agregar Imagen
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" >
                        <div class="row" id="body-imagesGuide">
                            @foreach( $imagesGuides as $image )
                                <div class="col-md-4">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">Imagen Guardada</h3>

                                            <div class="card-tools">
                                                <button type="button" data-imageeditold class="btn btn-sm btn-outline-success" data-toggle="tooltip" data-placement="top" title="Guardar descripción y orden" ><i class="fas fa-save"></i></i>
                                                </button>
                                                <button type="button" data-imagedeleteold class="btn btn-sm btn-outline-danger" data-toggle="tooltip" data-placement="top" title="Quitar de la base de datos" ><i class="fas fa-trash"></i></i>
                                                </button>
                                            </div>
                                            <!-- /.card-tools -->
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <input type="hidden" value="{{ $image->id }}">
                                            <div class="form-group">
                                                <label for="description">Descripción <span class="right badge badge-danger">(*)</span></label>
                                                <textarea class="form-control" data-img data-imgold rows="2" placeholder="Enter ...">{{ $image->code }}</textarea>

                                            </div>
                                            <div class="form-group">
                                                <label for="description">Imagen <span class="right badge badge-danger">(*)</span></label>
                                                <br>
                                                @if ( $image->type_file == 'pdf' )
                                                    <a href="{{ route('entry.document.extra', ['filename' => $image->image]) }}" target="_blank" class="btn btn-outline-success ">Ver PDF</a>
                                                @else
                                                    <button type="button" data-image="{{ route('entry.document.extra', ['filename' => $image->image]) }}" data-alt="{{ $image->code }}" class="btn btn-outline-primary ">Ver Imagen</button>
                                                @endif

                                            </div>
                                        </div>
                                        <!-- /.card-body -->
                                    </div>
                                    <!-- /.card -->
                                </div>
                            @endforeach
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <br>
        <div class="row" >
            <div class="col-md-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">ARCHIVOS DE OBSERVACIONES</h3>

                        <div class="card-tools">
                            <a id="addImageObservation" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Agregar imagen">
                                <i class="far fa-images"></i> Agregar Imagen
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" >
                        <div class="row" id="body-imagesObservation">
                            @foreach( $imagesObservations as $image )
                                <div class="col-md-4">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">Imagen Guardada</h3>

                                            <div class="card-tools">
                                                <button type="button" data-imageeditold class="btn btn-sm btn-outline-success" data-toggle="tooltip" data-placement="top" title="Guardar descripción y orden" ><i class="fas fa-save"></i></i>
                                                </button>
                                                <button type="button" data-imagedeleteold class="btn btn-sm btn-outline-danger" data-toggle="tooltip" data-placement="top" title="Quitar de la base de datos" ><i class="fas fa-trash"></i></i>
                                                </button>
                                            </div>
                                            <!-- /.card-tools -->
                                        </div>
                                        <!-- /.card-header -->
                                        <div class="card-body">
                                            <input type="hidden" value="{{ $image->id }}">
                                            <div class="form-group">
                                                <label for="description">Descripción <span class="right badge badge-danger">(*)</span></label>
                                                <textarea class="form-control" data-img data-imgold rows="2" placeholder="Enter ...">{{ $image->code }}</textarea>

                                            </div>
                                            <div class="form-group">
                                                <label for="description">Imagen <span class="right badge badge-danger">(*)</span></label>
                                                <br>
                                                @if ( $image->type_file == 'pdf' )
                                                    <a href="{{ route('entry.document.extra', ['filename' => $image->image]) }}" target="_blank" class="btn btn-outline-success ">Ver PDF</a>
                                                @else
                                                    <button type="button" data-image="{{ route('entry.document.extra', ['filename' => $image->image]) }}" data-alt="{{ $image->code }}" class="btn btn-outline-primary ">Ver Imagen</button>
                                                @endif

                                            </div>
                                        </div>
                                        <!-- /.card-body -->
                                    </div>
                                    <!-- /.card -->
                                </div>
                            @endforeach
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-12">
                <a href="{{ route('entry.purchase.index') }}" class="btn btn-outline-secondary">Regresar</a>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar nuevas imágenes</button>
            </div>
        </div>
    </form>

    <template id="template-image">
        <div class="col-md-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Imagen Seleccionada</h3>

                    <div class="card-tools">
                        <button type="button" data-imagedelete class="btn btn-sm btn-outline-danger" data-toggle="tooltip" data-placement="top" title="Quitar" ><i class="fas fa-trash"></i></i>
                        </button>
                    </div>
                    <!-- /.card-tools -->
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="form-group">
                        <label for="description">Código <span class="right badge badge-danger">(*)</span></label>
                        <textarea class="form-control" data-img name="codeimages[]" rows="2" placeholder="Enter ..."></textarea>
                        <input type="hidden" name="types[]">
                    </div>
                    <div class="form-group">
                        <label for="description">Imagen <span class="right badge badge-danger">(*)</span></label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" name="images[]" accept="image/*,application/pdf" class="form-control" onchange="previewFile(this)">
                            </div>
                        </div>
                        <img height="100px" width="100%" />

                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </template>

    <div id="modalImage" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver imagen</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" >
                    <div id="zoom">
                        <img id="imagePreview" src="" class="img-fluid" alt="">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script src="{{asset('admin/plugins/summernote/summernote-bs4.min.js')}}"></script>
    <script src="{{asset('admin/plugins/summernote/lang/summernote-es-ES.js')}}"></script>
    <script src="{{ asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>
    <script src="{{ asset('admin/plugins/zoom/jquery.zoom.js')}}"></script>
    <script src="{{ asset('js/entry/editImages.js') }}"></script>
    <script>
        $(document).ready(function(){


        });
    </script>
@endsection
