$(function () {

    const $app =
        $('#typeTaxApp');

    const storeUrl =
        $app.data(
            'store-url'
        );

    const updateUrlTemplate =
        $app.data(
            'update-url-template'
        );

    /*
     * ============================================================
     * NUEVO
     * ============================================================
     */

    $('#btnNewTypeTax').on(
        'click',
        function () {

            openCreateModal();
        }
    );

    function openCreateModal()
    {
        $.confirm({
            title:
                'Nuevo tipo de impuesto',

            type:
                'blue',

            columnClass:
                'col-md-6 col-md-offset-3',

            content: `
                <form>

                    <div class="form-group">

                        <label>
                            Código
                        </label>

                        <input
                            type="text"
                            name="code"
                            class="form-control"
                            placeholder="Ej. IGV_18"
                            maxlength="50"
                        >

                        <small class="form-text text-muted">
                            Clave técnica. Use letras, números y guion bajo.
                        </small>

                    </div>

                    <div class="form-group">

                        <label>
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            placeholder="Ej. IGV 18%"
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Tasa (%)
                        </label>

                        <input
                            type="number"
                            name="tax"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.0001"
                            value="0"
                        >

                    </div>

                    <div class="custom-control custom-checkbox mb-2">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="newTypeTaxDefault"
                            name="is_default"
                        >

                        <label
                            class="custom-control-label"
                            for="newTypeTaxDefault"
                        >
                            Predeterminado
                        </label>

                    </div>

                    <div class="custom-control custom-checkbox">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="newTypeTaxActive"
                            name="is_active"
                            checked
                        >

                        <label
                            class="custom-control-label"
                            for="newTypeTaxActive"
                        >
                            Activo
                        </label>

                    </div>

                </form>
            `,

            buttons: {

                save: {
                    text:
                        'Guardar',

                    btnClass:
                        'btn-primary',

                    action:
                        function () {

                            const $form =
                                this.$content
                                    .find('form');

                            const data =
                                readForm(
                                    $form,
                                    true
                                );

                            if (!data) {
                                return false;
                            }

                            createTypeTax(
                                data
                            );
                        }
                },

                cancel: {
                    text:
                        'Cancelar'
                }
            }
        });
    }

    /*
     * ============================================================
     * EDITAR
     * ============================================================
     */

    $(document).on(
        'click',
        '[data-edit-tax]',
        function () {

            const $button =
                $(this);

            const data = {
                id:
                    parseInt(
                        $button.data('id')
                    ),

                code:
                    $button.data('code'),

                name:
                    $button.data('name'),

                tax:
                    $button.data('tax'),

                is_default:
                    parseInt(
                        $button.data('default')
                    ) === 1,

                is_active:
                    parseInt(
                        $button.data('active')
                    ) === 1
            };

            openEditModal(
                data
            );
        }
    );

    function openEditModal(typeTax)
    {
        $.confirm({
            title:
                'Editar tipo de impuesto',

            type:
                'blue',

            columnClass:
                'col-md-6 col-md-offset-3',

            content: `
                <form>

                    <div class="form-group">

                        <label>
                            Código
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="${escapeHtml(typeTax.code)}"
                            readonly
                        >

                        <small class="form-text text-muted">
                            El código es una clave técnica y no puede modificarse.
                        </small>

                    </div>

                    <div class="form-group">

                        <label>
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="${escapeHtml(typeTax.name)}"
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Tasa (%)
                        </label>

                        <input
                            type="number"
                            name="tax"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.0001"
                            value="${typeTax.tax}"
                        >

                    </div>

                    <div class="custom-control custom-checkbox mb-2">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="editTypeTaxDefault"
                            name="is_default"
                            ${typeTax.is_default ? 'checked' : ''}
                        >

                        <label
                            class="custom-control-label"
                            for="editTypeTaxDefault"
                        >
                            Predeterminado
                        </label>

                    </div>

                    <div class="custom-control custom-checkbox">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="editTypeTaxActive"
                            name="is_active"
                            ${typeTax.is_active ? 'checked' : ''}
                        >

                        <label
                            class="custom-control-label"
                            for="editTypeTaxActive"
                        >
                            Activo
                        </label>

                    </div>

                </form>
            `,

            buttons: {

                save: {
                    text:
                        'Guardar',

                    btnClass:
                        'btn-primary',

                    action:
                        function () {

                            const $form =
                                this.$content
                                    .find('form');

                            const data =
                                readForm(
                                    $form,
                                    false
                                );

                            if (!data) {
                                return false;
                            }

                            updateTypeTax(
                                typeTax.id,
                                data
                            );
                        }
                },

                cancel: {
                    text:
                        'Cancelar'
                }
            }
        });
    }

    /*
     * ============================================================
     * FORM
     * ============================================================
     */

    function readForm(
        $form,
        includeCode
    ) {
        const name =
            $.trim(
                $form
                    .find(
                        '[name="name"]'
                    )
                    .val()
            );

        const tax =
            $.trim(
                $form
                    .find(
                        '[name="tax"]'
                    )
                    .val()
            );

        const isDefault =
            $form
                .find(
                    '[name="is_default"]'
                )
                .is(':checked');

        const isActive =
            $form
                .find(
                    '[name="is_active"]'
                )
                .is(':checked');

        if (!name) {

            toastr.warning(
                'Ingrese el nombre del tipo de impuesto.'
            );

            return null;
        }

        if (
            tax === '' ||
            isNaN(tax) ||
            parseFloat(tax) < 0 ||
            parseFloat(tax) > 100
        ) {

            toastr.warning(
                'Ingrese una tasa válida entre 0 y 100.'
            );

            return null;
        }

        if (
            isDefault &&
            !isActive
        ) {

            toastr.warning(
                'El tipo de impuesto predeterminado debe estar activo.'
            );

            return null;
        }

        const data = {
            name:
            name,

            tax:
            tax,

            is_default:
                isDefault ? 1 : 0,

            is_active:
                isActive ? 1 : 0
        };

        if (includeCode) {

            const code =
                $.trim(
                    $form
                        .find(
                            '[name="code"]'
                        )
                        .val()
                )
                    .toUpperCase();

            if (!code) {

                toastr.warning(
                    'Ingrese el código del tipo de impuesto.'
                );

                return null;
            }

            if (
                !/^[A-Z0-9_]+$/.test(
                    code
                )
            ) {

                toastr.warning(
                    'El código solo puede contener letras, números y guion bajo.'
                );

                return null;
            }

            data.code =
                code;
        }

        return data;
    }

    /*
     * ============================================================
     * AJAX CREATE
     * ============================================================
     */

    function createTypeTax(data)
    {
        $.ajax({
            url:
            storeUrl,

            method:
                'POST',

            headers: {
                'X-CSRF-TOKEN':
                    $(
                        'meta[name="csrf-token"]'
                    ).attr('content')
            },

            data:
            data,

            success:
                function (response) {

                    showSuccess(
                        response.message
                        || 'Tipo de impuesto creado correctamente.'
                    );
                },

            error:
                function (xhr) {

                    showError(
                        getErrorMessage(
                            xhr
                        )
                    );
                }
        });
    }

    /*
     * ============================================================
     * AJAX UPDATE
     * ============================================================
     */

    function updateTypeTax(
        id,
        data
    ) {
        const url =
            updateUrlTemplate
                .replace(
                    '__ID__',
                    id
                );

        $.ajax({
            url:
            url,

            method:
                'POST',

            headers: {
                'X-CSRF-TOKEN':
                    $(
                        'meta[name="csrf-token"]'
                    ).attr('content')
            },

            data:
            data,

            success:
                function (response) {

                    showSuccess(
                        response.message
                        || 'Tipo de impuesto actualizado correctamente.'
                    );
                },

            error:
                function (xhr) {

                    showError(
                        getErrorMessage(
                            xhr
                        )
                    );
                }
        });
    }

    /*
     * ============================================================
     * MENSAJES
     * ============================================================
     */

    function showSuccess(message)
    {
        $.alert({
            icon:
                'fas fa-check-circle',

            theme:
                'modern',

            animation:
                'zoom',

            type:
                'green',

            title:
                'Operación correcta',

            content:
                escapeHtml(
                    message
                ),

            buttons: {

                ok: {
                    text:
                        'Aceptar',

                    btnClass:
                        'btn-success',

                    action:
                        function () {

                            window.location.reload();
                        }
                }
            }
        });
    }

    function showError(message)
    {
        $.alert({
            icon:
                'fas fa-exclamation-triangle',

            theme:
                'modern',

            animation:
                'zoom',

            type:
                'orange',

            title:
                'No se pudo completar la operación',

            content:
                escapeHtml(
                    message
                ),

            buttons: {

                ok: {
                    text:
                        'Aceptar',

                    btnClass:
                        'btn-warning'
                }
            }
        });
    }

    function getErrorMessage(xhr)
    {
        if (
            xhr.responseJSON &&
            xhr.responseJSON.errors
        ) {

            const errors =
                xhr.responseJSON.errors;

            const firstKey =
                Object.keys(
                    errors
                )[0];

            if (
                firstKey &&
                errors[firstKey] &&
                errors[firstKey][0]
            ) {
                return errors[firstKey][0];
            }
        }

        if (
            xhr.responseJSON &&
            xhr.responseJSON.message
        ) {
            return xhr.responseJSON.message;
        }

        return 'Sucedió un error inesperado.';
    }

    function escapeHtml(value)
    {
        return $('<div>')
            .text(
                value ?? ''
            )
            .html();
    }
});