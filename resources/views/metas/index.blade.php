@extends('layouts.appAdmin2')

@section('openMetas')
    menu-open
@endsection

@section('activeMetas')
    active
@endsection

@section('activeMetasListado', 'active')

@section('title', 'Listado de Metas')

@section('content')
    <div class="container-fluid">

        {{-- MENSAJES FLASH --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        {{-- CONFIGURACIÓN TIPO DE META --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Configuración de tipo de meta</h3>
            </div>
            <div class="card-body">

                @if ($tipoMeta)
                    <p class="mb-1"><strong>Tipo de meta configurado</strong>
                        <span class="badge badge-info text-uppercase">{{ $tipoMeta }}</span>
                    </p>
                @else
                    <p class="mb-1">Frecuencia pendiente de configurar</p>
                @endif
                @if(auth()->user()->isTenantOwner() && auth()->user()->can('list_companySetting'))
                    <a href="{{ route('companySetting.index') }}">Configuración de Empresa</a>
                @endif

            </div>
        </div>

        {{-- LISTADO DE METAS --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Listado de metas</h3>

                @can('create_metas')
                <div>
                    <a href="{{ $canCreateMetas ? route('metas.create') : '#' }}"
                       class="btn btn-success btn-sm {{ $canCreateMetas ? '' : 'disabled' }}"
                       @if(!$canCreateMetas) disabled title="Configura el tipo de meta primero" @endif>
                        <i class="fas fa-plus"></i> Crear meta
                    </a>
                </div>
                @endcan
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Nombre</th>
                            <th style="width: 120px;">Tipo</th>
                            <th style="width: 150px;">Monto</th>
                            <th style="width: 150px;">Usuarios</th>
                            <th style="width: 80px;">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($metas as $meta)
                            <tr data-meta-id="{{ $meta->id }}">
                                <td>{{ $meta->id }}</td>
                                <td>{{ $meta->nombre }}</td>
                                <td class="text-uppercase">{{ $meta->tipo }}</td>
                                <td>S/ {{ number_format($meta->monto, 2) }}</td>
                                <td>{{ $meta->users_count }}</td>
                                <td>
                                    @can('edit_metas')
                                    <a href="{{ route('metas.edit', $meta->id) }}"
                                       class="btn btn-warning btn-sm"
                                       title="Editar meta">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @endcan
                                    @can('destroy_metas')
                                    <button type="button"
                                            class="btn btn-danger btn-sm btn-delete-meta"
                                            data-id="{{ $meta->id }}"
                                            title="Eliminar meta">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No hay metas registradas.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($metas->hasPages())
                <div class="card-footer">
                    {{ $metas->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection


@section('scripts')
    <script>
        $(function() {
            $('.btn-delete-meta').on('click', function () {

                const id = $(this).data('id');
                const row = $('tr[data-meta-id="'+id+'"]');

                $.confirm({
                    title: 'Eliminar Meta',
                    content: '¿Estás seguro de <strong>eliminar esta meta</strong>? Esta acción no se puede deshacer.',
                    type: 'red',
                    theme: 'modern',
                    buttons: {
                        eliminar: {
                            text: 'Sí, eliminar',
                            btnClass: 'btn-red',
                            action: function () {

                                $.ajax({
                                    url: '{{ url('/dashboard/metas/delete/') }}/' + id,
                                    method: 'POST',
                                    data: {
                                        _method: 'DELETE',
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function (res) {
                                        if (res.ok) {
                                            row.fadeOut(300, function() { $(this).remove(); });
                                            $.alert({
                                                title: 'Eliminado',
                                                content: 'La meta fue eliminada correctamente.',
                                                type: 'green'
                                            });
                                        } else {
                                            $.alert({
                                                title: 'Error',
                                                content: 'No se pudo eliminar la meta.',
                                                type: 'red'
                                            });
                                        }
                                    },
                                    error: function () {
                                        $.alert({
                                            title: 'Error',
                                            content: 'Ocurrió un error al intentar eliminar la meta.',
                                            type: 'red'
                                        });
                                    }
                                });

                            }
                        },
                        cancelar: {
                            text: 'Cancelar',
                            btnClass: 'btn-secondary',
                            action: function () { }
                        }
                    }
                });

            });
        });
    </script>
@endsection