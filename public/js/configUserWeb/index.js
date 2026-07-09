let currentPage = 1;
let searchTimeout = null;

$(document).ready(function () {
    loadUsers();

    $('#searchUser').on('keyup', function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(function () {
            currentPage = 1;
            loadUsers();
        }, 400);
    });

    $('#statusFilter').on('change', function () {
        currentPage = 1;
        loadUsers();
    });

    $('#perPage').on('change', function () {
        currentPage = 1;
        loadUsers();
    });

    $('#formEditUser').on('submit', function (e) {
        e.preventDefault();

        const id = $('#editUserId').val();

        if (!id) {
            showWarning('No se encontró el usuario seleccionado.');
            return;
        }

        let formData = new FormData(this);

        $('#btnSaveUser')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $('#editUserErrors').addClass('d-none').html('');

        $.ajax({
            url: buildRoute(window.configUserWebRoutes.update, id),
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $('#modalEditUser').modal('hide');

                loadUsers(currentPage);

                showSuccess(response.message || 'Usuario actualizado correctamente.');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showEditUserErrors(xhr.responseJSON.errors);
                } else {
                    showError(getAjaxMessage(xhr, 'No se pudo actualizar el usuario.'));
                }
            },
            complete: function () {
                $('#btnSaveUser')
                    .prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Guardar cambios');
            }
        });
    });

    $('#btnResetPassword').on('click', function () {
        const id = $('#editUserId').val();

        if (!id) {
            showWarning('No se encontró el usuario seleccionado.');
            return;
        }

        confirmAction({
            title: 'Resetear contraseña',
            content: `
            <p>¿Está seguro de resetear la contraseña de este usuario?</p>
            <p class="mb-0 text-muted">
                La contraseña será reemplazada por la contraseña general configurada.
            </p>
        `,
            confirmText: 'Sí, resetear',
            cancelText: 'Cancelar',
            onConfirm: function () {
                resetUserPassword(id);
            }
        });
    });

    $('#editImage').on('change', function () {
        const file = this.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            $('#editImagePreview').attr('src', e.target.result);
        };

        reader.readAsDataURL(file);
    });
});

function resetUserPassword(id) {
    $('#btnResetPassword')
        .prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Reseteando...');

    $.ajax({
        url: buildRoute(window.configUserWebRoutes.resetPassword, id),
        type: 'POST',
        dataType: 'json',
        data: {
            _token: $('#formEditUser input[name="_token"]').val()
        },
        success: function (response) {
            showSuccess(response.message || 'La contraseña fue reseteada correctamente.');
        },
        error: function (xhr) {
            showError(getAjaxMessage(xhr, 'No se pudo resetear la contraseña.'));
        },
        complete: function () {
            $('#btnResetPassword')
                .prop('disabled', false)
                .html('<i class="fas fa-key"></i> Resetear contraseña');
        }
    });
}

function showSuccess(message) {
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
        return;
    }

    if (typeof $.alert === 'function') {
        $.alert({
            title: 'Correcto',
            content: message,
            type: 'green'
        });
        return;
    }

    alert(message);
}

function showError(message) {
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
        return;
    }

    if (typeof $.alert === 'function') {
        $.alert({
            title: 'Error',
            content: message,
            type: 'red'
        });
        return;
    }

    alert(message);
}

function showWarning(message) {
    if (typeof toastr !== 'undefined') {
        toastr.warning(message);
        return;
    }

    if (typeof $.alert === 'function') {
        $.alert({
            title: 'Atención',
            content: message,
            type: 'orange'
        });
        return;
    }

    alert(message);
}

function confirmAction(options) {
    const title = options.title || 'Confirmar acción';
    const content = options.content || '¿Está seguro de continuar?';
    const confirmText = options.confirmText || 'Sí, continuar';
    const cancelText = options.cancelText || 'Cancelar';
    const onConfirm = options.onConfirm || function () {};

    if (typeof $.confirm === 'function') {
        $.confirm({
            title: title,
            content: content,
            type: 'orange',
            buttons: {
                confirmar: {
                    text: confirmText,
                    btnClass: 'btn-orange',
                    action: onConfirm
                },
                cancelar: {
                    text: cancelText
                }
            }
        });

        return;
    }

    if (confirm(content.replace(/<[^>]*>?/gm, ''))) {
        onConfirm();
    }
}

function getAjaxMessage(xhr, defaultMessage) {
    if (xhr.responseJSON && xhr.responseJSON.message) {
        return xhr.responseJSON.message;
    }

    return defaultMessage;
}

function buildRoute(route, id) {
    return route.replace(':id', encodeURIComponent(id));
}

function clearEditUserForm() {
    $('#formEditUser')[0].reset();
    $('#editUserId').val('');
    $('#editImagePreview').attr('src', '');
    $('#editRoles').val('');
    $('#editUserErrors').addClass('d-none').html('');
}

function showEditUserErrors(errors) {
    let html = '<ul class="mb-0">';

    $.each(errors, function (key, messages) {
        messages.forEach(function (message) {
            html += `<li>${escapeHtml(message)}</li>`;
        });
    });

    html += '</ul>';

    $('#editUserErrors').removeClass('d-none').html(html);
}

function loadUsers(page = 1) {
    currentPage = page;

    const search = $('#searchUser').val();
    const status = $('#statusFilter').val();
    const perPage = $('#perPage').val();

    $('#usersTableBody').html(`
        <tr>
            <td colspan="7" class="text-center">
                Cargando usuarios...
            </td>
        </tr>
    `);

    $.ajax({
        url: window.configUserWebRoutes.getUsers,
        type: 'GET',
        dataType: 'json',
        data: {
            page: page,
            search: search,
            status: status,
            per_page: perPage
        },
        success: function (response) {
            renderUsers(response.data, response.from);
            renderPagination(response);
            renderPaginationInfo(response);
        },
        error: function (xhr) {
            console.error(xhr);

            $('#usersTableBody').html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        Ocurrió un error al cargar los usuarios.
                    </td>
                </tr>
            `);

            showError('No se pudo cargar el listado de usuarios.');
        }
    });
}

function renderUsers(users, from) {
    const tbody = $('#usersTableBody');

    if (!users || users.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="7" class="text-center">
                    No se encontraron usuarios.
                </td>
            </tr>
        `);
        return;
    }

    let html = '';

    users.forEach((user, index) => {
        const number = (from ?? 1) + index;

        const statusButton = user.enable == 1
            ? `
                <button type="button"
                        class="btn btn-outline-danger btn-sm"
                        onclick="changeStatus(${user.id}, 0)">
                    <i class="fas fa-trash"></i> Inhabilitar
                </button>
            `
            : `
                <button type="button"
                        class="btn btn-outline-success btn-sm"
                        onclick="changeStatus(${user.id}, 1)">
                    <i class="fas fa-check"></i> Activar
                </button>
            `;

        html += `
            <tr>
                <td>${number}</td>
                <td>${escapeHtml(user.name)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>${user.updated_at ?? '-'}</td>
                <td>${user.roles ? escapeHtml(user.roles) : '-'}</td>
                <td class="text-center">
                    <img src="${user.image}"
                         alt="Usuario"
                         style="width: 42px; height: 42px; object-fit: cover; border-radius: 50%;">
                </td>
                <td>
                    <button type="button"
                            class="btn btn-outline-warning btn-sm"
                            onclick="editUser(${user.id})">
                        <i class="fas fa-pencil-alt"></i> Editar
                    </button>

                    ${statusButton}
                </td>
            </tr>
        `;
    });

    tbody.html(html);
}

function renderPagination(response) {
    const pagination = $('#paginationLinks');

    const current = response.current_page;
    const last = response.last_page;

    if (last <= 1) {
        pagination.html('');
        return;
    }

    let html = '';

    html += `
        <li class="page-item ${current === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${current - 1})">
                Anterior
            </a>
        </li>
    `;

    const pages = getPaginationPages(current, last);

    pages.forEach(page => {
        if (page === '...') {
            html += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            `;
        } else {
            html += `
                <li class="page-item ${page === current ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${page})">
                        ${page}
                    </a>
                </li>
            `;
        }
    });

    html += `
        <li class="page-item ${current === last ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${current + 1})">
                Siguiente
            </a>
        </li>
    `;

    pagination.html(html);
}

function getPaginationPages(current, last) {
    let pages = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }

        return pages;
    }

    pages.push(1);

    if (current > 4) {
        pages.push('...');
    }

    let start = Math.max(2, current - 1);
    let end = Math.min(last - 1, current + 1);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    if (current < last - 3) {
        pages.push('...');
    }

    pages.push(last);

    return pages;
}

function renderPaginationInfo(response) {
    const info = $('#paginationInfo');

    if (response.total === 0) {
        info.html('Mostrando 0 registros');
        return;
    }

    info.html(`Mostrando registros del ${response.from} al ${response.to} de un total de ${response.total} registros`);
}

function editUser(id) {
    clearEditUserForm();

    $.ajax({
        url: buildRoute(window.configUserWebRoutes.edit, id),
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            $('#editUserId').val(response.id);
            $('#editName').val(response.name);
            $('#editEmail').val(response.email);
            $('#editRoles').val(response.roles ? response.roles : '-');
            $('#editImagePreview').attr('src', response.image);

            $('#modalEditUser').modal('show');
        },
        error: function (xhr) {
            showError(getAjaxMessage(xhr, 'No se pudo obtener la información del usuario.'));
        }
    });
}

function changeStatus(id, status) {
    const isActivating = parseInt(status) === 1;

    const title = isActivating ? 'Activar usuario' : 'Inhabilitar usuario';

    const content = isActivating
        ? `
            <p>¿Está seguro de activar este usuario?</p>
            <p class="mb-0 text-muted">
                El usuario podrá volver a ingresar al sistema.
            </p>
        `
        : `
            <p>¿Está seguro de inhabilitar este usuario?</p>
            <p class="mb-0 text-muted">
                El usuario ya no podrá ingresar al sistema.
            </p>
        `;

    const confirmText = isActivating ? 'Sí, activar' : 'Sí, inhabilitar';

    confirmAction({
        title: title,
        content: content,
        confirmText: confirmText,
        cancelText: 'Cancelar',
        onConfirm: function () {
            sendChangeStatus(id, status);
        }
    });
}

function sendChangeStatus(id, status) {
    $.ajax({
        url: buildRoute(window.configUserWebRoutes.changeStatus, id),
        type: 'POST',
        dataType: 'json',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: status
        },
        success: function (response) {
            showSuccess(response.message || 'Estado actualizado correctamente.');
            loadUsers(currentPage);
        },
        error: function (xhr) {
            showError(getAjaxMessage(xhr, 'No se pudo actualizar el estado del usuario.'));
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';

    return text
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}