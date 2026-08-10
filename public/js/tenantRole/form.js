$(function () {

    const $app =
        $('#tenant-role-form-app');

    const mode =
        $app.data('mode');

    const urlIndex =
        $app.data('url-index');

    const urlPermissions =
        $app.data('url-permissions');

    const urlStore =
        $app.data('url-store');

    const urlShow =
        $app.data('url-show');

    const urlUpdate =
        $app.data('url-update');

    let permissionGroups = [];

    function initialize() {

        loadPermissions(
            function () {

                if (mode === 'edit') {
                    loadRole();
                } else {
                    refreshPermissionStatus();
                }

            }
        );

    }

    function loadPermissions(
        callback
    ) {

        $.ajax({

            url: urlPermissions,

            method: 'GET',

            success: function (
                response
            ) {

                permissionGroups =
                    response.groups || [];


                renderPermissionGroups();


                if (callback) {
                    callback();
                }

            },

            error: function (xhr) {

                showAjaxError(
                    xhr,
                    'No se pudieron cargar los permisos.'
                );

            }

        });

    }

    function loadRole() {

        $.ajax({

            url: urlShow,

            method: 'GET',

            success: function (
                response
            ) {

                let role =
                    response.role;


                $('#name')
                    .val(
                        role.name
                    );


                $('#description')
                    .val(
                        role.description
                    );


                $('#is_owner_assignable')
                    .prop(
                        'checked',
                        !!role.is_owner_assignable
                    );


                $('.permission-checkbox')
                    .prop(
                        'checked',
                        false
                    );


                $.each(
                    role.permissions,
                    function (
                        index,
                        permission
                    ) {

                        $('.permission-checkbox' +
                            '[value="' +
                            permission.id +
                            '"]'
                        )
                            .prop(
                                'checked',
                                true
                            );

                    }
                );


                refreshPermissionStatus();

            },

            error: function (xhr) {

                showAjaxError(
                    xhr,
                    'No se pudo cargar el rol.'
                );

            }

        });

    }

    function renderPermissionGroups() {

        let html = '';


        $.each(
            permissionGroups,
            function (
                groupIndex,
                group
            ) {

                let permissionsHtml = '';


                $.each(
                    group.permissions,
                    function (
                        permissionIndex,
                        permission
                    ) {

                        let checkboxId =
                            'permission_' +
                            permission.id;


                        permissionsHtml += `

                            <div
                                class="permission-item"
                                data-search="${escapeAttribute(
                            (
                                group.name +
                                ' ' +
                                permission.description +
                                ' ' +
                                permission.name
                            ).toLowerCase()
                        )}"
                            >

                                <div
                                    class="custom-control custom-checkbox"
                                >

                                    <input
                                        type="checkbox"

                                        class="
                                            custom-control-input
                                            permission-checkbox
                                        "

                                        id="${checkboxId}"

                                        name="permissions[]"

                                        value="${permission.id}"

                                        data-module="${groupIndex}"
                                    >

                                    <label
                                        class="custom-control-label"
                                        for="${checkboxId}"
                                    >
                                        ${escapeHtml(
                            permission.description
                        )}
                                    </label>

                                </div>


                                <span
                                    class="permission-code"
                                >
                                    ${escapeHtml(
                            permission.name
                        )}
                                </span>

                            </div>

                        `;

                    }
                );


                html += `

                    <div
                        class="
                            col-12
                            col-md-6
                            col-xl-4
                            permission-module-wrapper
                        "

                        data-module="${groupIndex}"

                        data-search="${escapeAttribute(
                    group.name.toLowerCase()
                )}"
                    >

                        <div
                            class="
                                card
                                card-outline
                                card-secondary
                                permission-module-card
                            "
                        >

                            <div
                                class="
                                    card-header
                                    permission-module-header
                                "
                            >

                                <div
                                    class="
                                        d-flex
                                        justify-content-between
                                        align-items-center
                                    "
                                >

                                    <div
                                        class="custom-control custom-checkbox"
                                    >

                                        <input
                                            type="checkbox"

                                            class="
                                                custom-control-input
                                                permission-module-checkbox
                                            "

                                            id="module_${groupIndex}"

                                            data-module="${groupIndex}"
                                        >

                                        <label
                                            class="
                                                custom-control-label
                                                permission-module-name
                                            "

                                            for="module_${groupIndex}"
                                        >
                                            ${escapeHtml(
                    group.name
                )}
                                        </label>

                                    </div>


                                    <div>

                                        <span
                                            class="
                                                badge
                                                badge-secondary
                                                permission-module-counter
                                            "

                                            data-module-counter="${groupIndex}"
                                        >
                                            0 /
                                            ${group.permissions.length}
                                        </span>


                                        <button
                                            type="button"

                                            class="
                                                btn
                                                btn-tool
                                                btn-toggle-module
                                            "

                                            data-module="${groupIndex}"
                                        >

                                            <i
                                                class="
                                                    fas
                                                    fa-minus
                                                "
                                            ></i>

                                        </button>

                                    </div>

                                </div>

                            </div>


                            <div
                                class="card-body permission-module-body"
                                data-module-body="${groupIndex}"
                            >
                                ${permissionsHtml}
                            </div>

                        </div>

                    </div>

                `;

            }
        );


        $('#permissionsContainer')
            .html(html);


        refreshPermissionStatus();

    }

    $(document).on(
        'change',
        '.permission-module-checkbox',
        function () {

            let module =
                $(this).data('module');

            let checked =
                $(this).is(':checked');


            $('.permission-checkbox' +
                '[data-module="' +
                module +
                '"]'
            )
                .prop(
                    'checked',
                    checked
                );


            refreshPermissionStatus();

        }
    );

    $(document).on(
        'change',
        '.permission-checkbox',
        function () {

            refreshPermissionStatus();

        }
    );

    $('#permissionSelectAll').on(
        'change',
        function () {

            let checked =
                $(this).is(':checked');


            $('.permission-checkbox:visible')
                .prop(
                    'checked',
                    checked
                );


            refreshPermissionStatus();

        }
    );

    $('#btnClearPermissions').on(
        'click',
        function () {

            $.confirm({

                title:
                    'Limpiar permisos',

                content:
                    '¿Está seguro de desmarcar todos los permisos seleccionados?',

                type:
                    'orange',

                buttons: {

                    confirm: {

                        text:
                            'Sí, limpiar',

                        btnClass:
                            'btn-warning',

                        action:
                            function () {

                                $('.permission-checkbox')
                                    .prop(
                                        'checked',
                                        false
                                    );


                                refreshPermissionStatus();

                            }

                    },

                    cancel: {
                        text:
                            'Cancelar'
                    }

                }

            });

        }
    );

    function refreshPermissionStatus() {

        let $permissions =
            $('.permission-checkbox');


        let total =
            $permissions.length;


        let selected =
            $permissions
                .filter(':checked')
                .length;


        $('#permissionCounter')
            .text(
                selected +
                ' / ' +
                total +
                ' seleccionados'
            );


        $('#bottomPermissionCounter')
            .text(
                selected +
                ' permisos seleccionados'
            );


        $('.permission-module-checkbox')
            .each(
                function () {

                    let $moduleCheckbox =
                        $(this);


                    let module =
                        $moduleCheckbox
                            .data('module');


                    let $modulePermissions =
                        $('.permission-checkbox' +
                            '[data-module="' +
                            module +
                            '"]'
                        );


                    let moduleTotal =
                        $modulePermissions
                            .length;


                    let moduleSelected =
                        $modulePermissions
                            .filter(
                                ':checked'
                            )
                            .length;


                    $moduleCheckbox
                        .prop(
                            'checked',
                            moduleTotal > 0 &&
                            moduleSelected ===
                            moduleTotal
                        );


                    $moduleCheckbox
                        .prop(
                            'indeterminate',
                            moduleSelected > 0 &&
                            moduleSelected <
                            moduleTotal
                        );


                    $('[data-module-counter="' +
                        module +
                        '"]'
                    ).text(
                        moduleSelected +
                        ' / ' +
                        moduleTotal
                    );

                }
            );


        $('#permissionSelectAll')
            .prop(
                'checked',
                total > 0 &&
                selected === total
            );


        $('#permissionSelectAll')
            .prop(
                'indeterminate',
                selected > 0 &&
                selected < total
            );

    }

    $(document).on(
        'click',
        '.btn-toggle-module',
        function (event) {

            event.preventDefault();
            event.stopPropagation();


            let module =
                $(this).data('module');


            let $body =
                $('[data-module-body="' +
                    module +
                    '"]'
                );


            let $icon =
                $(this).find('i');


            $body.slideToggle(
                150
            );


            $icon.toggleClass(
                'fa-minus fa-plus'
            );

        }
    );

    $('#btnExpandAll').on(
        'click',
        function () {

            $('.permission-module-body')
                .show();


            $('.btn-toggle-module i')
                .removeClass(
                    'fa-plus'
                )
                .addClass(
                    'fa-minus'
                );

        }
    );


    $('#btnCollapseAll').on(
        'click',
        function () {

            $('.permission-module-body')
                .hide();


            $('.btn-toggle-module i')
                .removeClass(
                    'fa-minus'
                )
                .addClass(
                    'fa-plus'
                );

        }
    );

    $('#permissionSearch').on(
        'keyup',
        function () {

            let search =
                $.trim(
                    $(this)
                        .val()
                        .toLowerCase()
                );


            $('.permission-module-wrapper')
                .each(
                    function () {

                        let $module =
                            $(this);


                        let moduleText =
                            String(
                                $module.data(
                                    'search'
                                ) || ''
                            );


                        let moduleMatch =
                            search === '' ||
                            moduleText.includes(
                                search
                            );


                        let visiblePermissions =
                            0;


                        $module
                            .find(
                                '.permission-item'
                            )
                            .each(
                                function () {

                                    let $permission =
                                        $(this);


                                    let text =
                                        String(
                                            $permission
                                                .data(
                                                    'search'
                                                ) || ''
                                        );


                                    let visible =
                                        search === '' ||
                                        moduleMatch ||
                                        text.includes(
                                            search
                                        );


                                    $permission.toggle(
                                        visible
                                    );


                                    if (visible) {
                                        visiblePermissions++;
                                    }

                                }
                            );


                        $module.toggle(
                            visiblePermissions > 0
                        );


                        if (
                            search !== '' &&
                            visiblePermissions > 0
                        ) {

                            $module
                                .find(
                                    '.permission-module-body'
                                )
                                .show();

                        }

                    }
                );

        }
    );

    function loadTemplate() {

        $.ajax({

            url: urlShow,

            method: 'GET',

            success: function (
                response
            ) {

                let template =
                    response.template;


                $('#code')
                    .val(
                        template.code
                    );


                $('#name')
                    .val(
                        template.name
                    );


                $('#description')
                    .val(
                        template.description
                        || ''
                    );


                $('#is_owner_assignable')
                    .prop(
                        'checked',
                        !!template.is_owner_assignable
                    );


                $('.permission-checkbox')
                    .prop(
                        'checked',
                        false
                    );


                $.each(
                    template.permissions,
                    function (
                        index,
                        permission
                    ) {

                        $('.permission-checkbox' +
                            '[value="' +
                            permission.id +
                            '"]'
                        )
                            .prop(
                                'checked',
                                true
                            );

                    }
                );


                refreshPermissionStatus();

            },

            error: function (xhr) {

                showAjaxError(
                    xhr,
                    'No se pudo cargar la plantilla.'
                );

            }

        });

    }

    $('#formTenantRole').on(
        'submit',
        function (event) {

            event.preventDefault();


            let $form =
                $(this);


            let $button =
                $('#btnSaveTenantRole');


            let url =
                mode === 'edit'
                    ? urlUpdate
                    : urlStore;


            $button
                .prop(
                    'disabled',
                    true
                )
                .html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i>' +
                    'Guardando...'
                );


            $.ajax({

                url: url,

                method: 'POST',

                data:
                    $form.serialize(),

                success: function (
                    response
                ) {

                    $.alert({

                        title:
                            'Correcto',

                        content:
                        response.message,

                        type:
                            'green',

                        buttons: {

                            ok: {

                                text:
                                    'Aceptar',

                                btnClass:
                                    'btn-success',

                                action:
                                    function () {

                                        window.location.href =
                                            urlIndex;

                                    }

                            }

                        }

                    });

                },

                error: function (xhr) {

                    showAjaxError(
                        xhr,
                        'No se pudo guardar el rol.'
                    );

                },

                complete: function () {

                    $button
                        .prop(
                            'disabled',
                            false
                        )
                        .html(
                            '<i class="fas fa-save mr-1"></i>' +
                            'Guardar rol'
                        );

                }

            });

        }
    );

    function showAjaxError(
        xhr,
        defaultMessage
    ) {

        let message =
            defaultMessage;


        if (
            xhr.status === 422 &&
            xhr.responseJSON &&
            xhr.responseJSON.errors
        ) {

            let first =
                Object.values(
                    xhr.responseJSON.errors
                )[0];


            if (
                first &&
                first[0]
            ) {
                message =
                    first[0];
            }

        } else if (
            xhr.responseJSON &&
            xhr.responseJSON.message
        ) {

            message =
                xhr.responseJSON.message;

        }


        $.alert({
            title: 'Aviso',
            content: message,
            type: 'orange'
        });

    }


    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }


        return $('<div>')
            .text(value)
            .html();

    }


    function escapeAttribute(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }


        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

    }


    initialize();

});