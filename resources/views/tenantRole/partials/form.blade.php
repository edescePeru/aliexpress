<div class="alert alert-light border">

    <strong>
        Tenant:
    </strong>

    {{ $tenant->name }}

    @if(isset($role) && $role->template)

        <br>

        <strong>
            Plantilla de origen:
        </strong>

        {{ $role->template->name }}

    @endif

</div>


<div class="row">

    <div class="col-lg-4 col-md-6">

        <div class="form-group">

            <label for="name">
                Código del rol
            </label>

            <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    maxlength="125"
                    value="{{
                    isset($role)
                        ? $role->name
                        : ''
                }}"
                    required
            >

        </div>

    </div>


    <div class="col-lg-5 col-md-6">

        <div class="form-group">

            <label for="description">
                Descripción
            </label>

            <input
                    type="text"
                    id="description"
                    name="description"
                    class="form-control"
                    maxlength="125"
                    value="{{
                    isset($role)
                        ? $role->description
                        : ''
                }}"
                    required
            >

        </div>

    </div>


    <div class="col-lg-3 col-md-12">

        <label>
            Configuración
        </label>

        <div class="mt-2">

            <div class="custom-control custom-checkbox">

                <input
                        type="checkbox"
                        class="custom-control-input"
                        id="is_owner_assignable"
                        name="is_owner_assignable"
                        value="1"

                        {{
                            isset($role) &&
                            $role->is_owner_assignable
                                ? 'checked'
                                : ''
                        }}
                >

                <label
                        class="custom-control-label"
                        for="is_owner_assignable"
                >
                    Asignable por Owner
                </label>

            </div>

        </div>

    </div>

</div>


@if(isset($role) && !$role->is_customized)

    <div class="alert alert-info">

        <i class="fas fa-info-circle mr-1"></i>

        Este rol todavía sigue la configuración estándar
        de su plantilla.

        <strong>
            Al guardar modificaciones se convertirá
            en un rol personalizado.
        </strong>

    </div>

@endif

<hr>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

    <div>

        <h5 class="mb-1">
            Permisos
        </h5>

        <small class="text-muted">
            Seleccione las funcionalidades incluidas en esta plantilla.
        </small>

    </div>


    <span
            id="permissionCounter"
            class="badge badge-info p-2 mt-2 mt-md-0"
    >
        0 / 0 seleccionados
    </span>

</div>

<div class="row mb-3">

    <div class="col-lg-6 col-md-12 mb-2">

        <div class="input-group">

            <div class="input-group-prepend">

                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>

            </div>

            <input
                    type="text"
                    id="permissionSearch"
                    class="form-control"
                    placeholder="Buscar módulo o permiso..."
            >

        </div>

    </div>


    <div class="col-lg-6 col-md-12 text-lg-right">

        <button
                type="button"
                id="btnExpandAll"
                class="btn btn-outline-secondary btn-sm mb-1"
        >
            <i class="fas fa-expand-alt"></i>
            Expandir todos
        </button>

        <button
                type="button"
                id="btnCollapseAll"
                class="btn btn-outline-secondary btn-sm mb-1"
        >
            <i class="fas fa-compress-alt"></i>
            Contraer todos
        </button>

        <button
                type="button"
                id="btnClearPermissions"
                class="btn btn-outline-danger btn-sm mb-1"
        >
            <i class="fas fa-times"></i>
            Limpiar
        </button>

    </div>

</div>

<div class="card card-outline card-secondary">

    <div class="card-header">

        <div class="custom-control custom-checkbox">

            <input
                    type="checkbox"
                    class="custom-control-input"
                    id="permissionSelectAll"
            >

            <label
                    class="custom-control-label font-weight-bold"
                    for="permissionSelectAll"
            >
                Seleccionar todos los permisos
            </label>

        </div>

    </div>


    <div class="card-body">

        <div
                id="permissionsContainer"
                class="row"
        >

            <div class="col-12 text-center text-muted py-5">

                <i class="fas fa-spinner fa-spin"></i>

                Cargando permisos...

            </div>

        </div>

    </div>

</div>

<div class="role-template-action-bar">

    <div class="container-fluid">

        <div
                class="
                d-flex
                justify-content-between
                align-items-center
            "
        >

            <span
                    id="bottomPermissionCounter"
                    class="text-muted d-none d-md-inline"
            >
                0 permisos seleccionados
            </span>


            <div class="ml-auto">

                <a
                        href="{{ route('roleTemplate.index') }}"
                        class="btn btn-outline-secondary mr-2"
                >
                    Cancelar
                </a>

                <button
                        type="submit"
                        id="btnSaveRoleTemplate"
                        class="btn btn-success"
                >
                    <i class="fas fa-save mr-1"></i>

                    Guardar rol
                </button>

            </div>

        </div>

    </div>

</div>