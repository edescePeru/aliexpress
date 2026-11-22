@extends('layouts.appAdmin2')

@section('openMetas')
    menu-open
@endsection

@section('activeMetas')
    active
@endsection

@section('activeMetasListado', 'active')

@section('title', 'Listado de Metas')

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Ventas · Metas</span><h1 class="page-title">Metas</h1><p class="next-page-description">Objetivos por usuario de la empresa · Todas las sucursales · PEN.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Metas</strong><span>Metas comerciales de la empresa activa.</span></div></div>
@endsection

@section('content')
    <div class="next-operational-list">

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
        <div class="next-form-section">
            <div class="next-section-header">
                <h3 class="card-title">Configuración de tipo de meta</h3>
            </div>
            <div class="p-0">

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
        <div class="next-form-section">
            <div class="next-page-toolbar flex-wrap">
                <h3 class="card-title mb-0">Listado de metas</h3>

                @can('create_metas')
                <div>
                    <a href="{{ $canCreateMetas ? route('metas.create') : '#' }}"
                       class="btn btn-primary btn-sm {{ $canCreateMetas ? '' : 'disabled' }}"
                       @if(!$canCreateMetas) disabled title="Configura el tipo de meta primero" @endif>
                        <i class="fas fa-plus"></i> Crear meta
                    </a>
                </div>
                @endcan
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover mb-0">
                        <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Nombre</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-right">Monto</th>
                            <th class="text-center">Usuarios</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($metas as $meta)
                            <tr data-meta-id="{{ $meta->id }}">
                                <td class="text-center">{{ $meta->id }}</td>
                                <td>{{ $meta->nombre }}</td>
                                <td class="text-center text-uppercase">{{ $meta->tipo }}</td>
                                <td class="text-right">S/ {{ number_format($meta->monto, 2) }}</td>
                                <td class="text-center">{{ $meta->users_count }}</td>
                                <td class="text-center">
                                    @can('edit_metas')
                                    <a href="{{ route('metas.edit', $meta->id) }}"
                                       class="btn btn-outline-secondary btn-sm"
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
                <div class="next-list-pagination">
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