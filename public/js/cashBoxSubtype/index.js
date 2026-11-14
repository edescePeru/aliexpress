(function () {
    const $bodyTable = $('#body-table');
    const $pagination = $('#pagination');
    const $textPagination = $('#textPagination');
    const $numberItems = $('#numberItems');

    const $modal = $('#modalSubtype');
    const $form = $('#formSubtype');

    const urlList = $form.data('url_list');
    const urlCreate = $form.data('url_create');
    const urlUpdate = $form.data('url_update');
    const urlToggle = $form.data('url_toggle');

    const permissions =
        JSON.parse(
            $('#permissions').val()
            || '{}'
        );

    const canEdit =
        !!permissions.can_edit;

    const canToggle =
        !!permissions.can_toggle;

    let currentPage = 1;
    let listRequest, listSequence = 0, returnFocus;

    function escapeHtml(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function badgeFromTemplate(templateId) {
        const tpl = document.querySelector(templateId);
        return tpl ? tpl.content.cloneNode(true) : document.createTextNode('');
    }

    function toggleCommissionUI() {
        const isDeferred = $('#st_is_deferred').is(':checked');
        if (isDeferred) {
            $('#wrap_requires_commission').show();
        } else {
            $('#wrap_requires_commission').hide();
            $('#st_requires_commission').prop('checked', false);
        }
    }

    function selectPersisted(selector, value, label) {
        const select = $(selector);
        if (value !== null && value !== undefined && value !== '' && !select.find('option').toArray().some(option => String(option.value) === String(value))) {
            $('<option>').val(value).text((label || 'Relación actual') + ' (no disponible en el catálogo actual)').attr('data-current-only', 'true').appendTo(select);
        }
        select.val(value);
    }

    function resetModal() {
        $form.find('[data-current-only]').remove();
        $('#subtypeTitle').text('Nuevo Subtipo');
        $('#st_id').val('');
        $('#st_cash_box_id').val('global');
        $('#st_code').val('');
        $('#st_name').val('');
        $('#st_position').val(0);


        // nuevos
        $('#st_is_deferred').prop('checked', false);
        $('#st_requires_commission').prop('checked', false);
        toggleCommissionUI();
    }

    function fillModal(item) {
        $('#subtypeTitle').text('Editar Subtipo');
        $('#st_id').val(item.id);
        selectPersisted('#st_cash_box_id', item.cash_box_id === null ? 'global' : item.cash_box_id, item.cash_box_name);
        $('#st_code').val(item.code || '');
        $('#st_name').val(item.name || '');
        $('#st_position').val(item.position !== null && item.position !== undefined ? item.position : 0);

        // nuevos
        $('#st_is_deferred').prop('checked', !!item.is_deferred);
        $('#st_requires_commission').prop('checked', !!item.requires_commission);
        toggleCommissionUI();
    }

    function renderButtons(item) {
        if (!canEdit && !canToggle) return document.createTextNode('—');
        const container = document.querySelector('#template-row-actions').content.cloneNode(true).firstElementChild;
        const trigger = container.querySelector('.next-row-actions-trigger');
        const menu = container.querySelector('.next-row-actions-menu');
        trigger.id = 'cash-subtype-actions-' + item.id;
        trigger.setAttribute('aria-label', 'Acciones de ' + item.name);
        menu.setAttribute('aria-labelledby', trigger.id);
        if (canEdit) {
            const button = document.querySelector('#template-btn-edit').content.cloneNode(true).querySelector('button');
            button.dataset.item = JSON.stringify(item);
            menu.appendChild(button);
        }
        if (canToggle) {
            const button = document.querySelector('#template-btn-toggle').content.cloneNode(true).querySelector('button');
            button.dataset.id = item.id;
            button.dataset.active = item.is_active ? '1' : '0';
            button.querySelector('[data-toggle-label]').textContent = item.is_active ? 'Desactivar' : 'Activar';
            menu.appendChild(button);
        }
        return container;
    }

    function renderRow(item) {
        const tpl = document.querySelector('#item-table').content.cloneNode(true);

        tpl.querySelector('[data-id]').textContent = item.id;
        tpl.querySelector('[data-scope]').textContent = item.scope_label || 'Toda la empresa';
        tpl.querySelector('[data-code]').textContent = item.code;
        tpl.querySelector('[data-name]').textContent = item.name;

        const defCell = tpl.querySelector('[data-deferred]');
        defCell.innerHTML = '';
        defCell.appendChild(badgeFromTemplate(item.is_deferred ? '#badge-yes' : '#badge-no'));

        const comCell = tpl.querySelector('[data-commission]');
        comCell.innerHTML = '';
        comCell.appendChild(badgeFromTemplate(item.requires_commission ? '#badge-yes' : '#badge-no'));

        tpl.querySelector('[data-position]').textContent = item.position;

        const statusCell = tpl.querySelector('[data-status]');
        statusCell.innerHTML = '';
        statusCell.appendChild(badgeFromTemplate(item.is_active ? '#badge-active' : '#badge-inactive'));

        const btnCell = tpl.querySelector('[data-buttons]');
        btnCell.innerHTML = '';
        btnCell.appendChild(renderButtons(item));

        return tpl;
    }

    function renderEmpty() {
        const tpl = document.querySelector('#item-table-empty').content.cloneNode(true);
        $bodyTable.html('');
        $bodyTable.append(tpl);
    }

    function renderPagination(meta) {
        $pagination.html('');

        if (!meta || meta.last_page <= 1) {
            $textPagination.text(`Mostrando ${meta && meta.total || 0} registros`);
            return;
        }

        $textPagination.text(`Mostrando ${meta.from || 0} a ${meta.to || 0} de ${meta.total || 0}`);

        const current = meta.current_page;
        const last = meta.last_page;

        const makePageItem = (page, label, active, disabled) => {
            const li = document.createElement('li');
            li.className = 'page-item';
            if (active) li.classList.add('active');
            if (disabled) li.classList.add('disabled');

            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            a.dataset.page = page;

            li.appendChild(a);
            return li;
        };

        $pagination.append(makePageItem(Math.max(1, current - 1), '«', false, current === 1));

        const windowSize = 2;
        const start = Math.max(1, current - windowSize);
        const end = Math.min(last, current + windowSize);

        if (start > 1) {
            $pagination.append(makePageItem(1, '1', current === 1, false));
            if (start > 2) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = `<span class="page-link">...</span>`;
                $pagination.append(dots);
            }
        }

        for (let p = start; p <= end; p++) {
            $pagination.append(makePageItem(p, String(p), p === current, false));
        }

        if (end < last) {
            if (end < last - 1) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = `<span class="page-link">...</span>`;
                $pagination.append(dots);
            }
            $pagination.append(makePageItem(last, String(last), current === last, false));
        }

        $pagination.append(makePageItem(Math.min(last, current + 1), '»', false, current === last));
    }

    function fetchList(page = 1) {
        currentPage = page;
        const sequence = ++listSequence;
        if (listRequest) listRequest.abort();
        $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
        $('#list-feedback').addClass('d-none').text('');
        $bodyTable.empty(); $pagination.empty(); $numberItems.text('—');
        $textPagination.text('Cargando…');

        const q = ($('#search').val() || '').trim();
        const cash_box_id = $('#filter_cash_box').val();

        listRequest = $.ajax({
            url: urlList,
            type: 'GET',
            dataType: 'json',
            data: { page: page, q: q, cash_box_id: cash_box_id },
            success: function (res) {
                if (sequence !== listSequence) return;
                $bodyTable.html('');

                const items = res.data || [];
                const meta = res.meta || null;

                $numberItems.text(meta && meta.total !== undefined ? meta.total : items.length);

                if (!items.length) {
                    renderEmpty();
                    renderPagination(meta);
                    return;
                }

                items.forEach(item => $bodyTable.append(renderRow(item)));

                renderPagination(meta);
            },
            error: function (xhr, status) {
                if (status === 'abort' || sequence !== listSequence) return;
                $bodyTable.empty(); $pagination.empty(); $textPagination.text('Consulta no disponible');
                $('#list-feedback').removeClass('d-none').text(xhr.status === 403 ? 'No tiene permiso para consultar este listado.' : 'No se pudo cargar el listado. Vuelva a buscar o recargue la página.');
            }
        });
    }

    function normalizeCode(code) {
        return (code || '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, '_');
    }

    function validateForm() {
        const code = normalizeCode($('#st_code').val());
        const name = ($('#st_name').val() || '').trim();

        if (!code) return 'El código es obligatorio.';
        if (!name) return 'El nombre es obligatorio.';

        return null;
    }

    function buildPayload() {
        return {
            id: $('#st_id').val() || null,
            cash_box_id: $('#st_cash_box_id').val(),
            code: normalizeCode($('#st_code').val()),
            name: ($('#st_name').val() || '').trim(),
            position: $('#st_position').val() || 0,

            // nuevos
            is_deferred: $('#st_is_deferred').is(':checked') ? 1 : 0,
            requires_commission: $('#st_requires_commission').is(':checked') ? 1 : 0,

            _token: csrf()
        };
    }

    function save() {
        const err = validateForm();
        if (err) {
            $.alert({ title: 'Validación', content: err, type: 'orange' });
            return;
        }

        const payload = buildPayload();
        const isEdit = !!payload.id;

        $.confirm({
            icon: 'fas fa-save',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'blue',
            title: isEdit ? 'Actualizar Subtipo' : 'Crear Subtipo',
            content: isEdit ? '¿Deseas guardar los cambios?' : '¿Deseas crear este subtipo?',
            buttons: {
                cancel: { text: 'Cancelar' },
                confirm: {
                    text: 'Confirmar',
                    btnClass: 'btn-primary',
                    action: function () {
                        $.ajax({
                            url: isEdit ? urlUpdate : urlCreate,
                            type: 'POST',
                            dataType: 'json',
                            data: payload,
                            success: function (res) {
                                $modal.modal('hide');
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message || 'Guardado correctamente', 'Éxito');
                                }
                                fetchList(currentPage);
                            },
                            error: function (xhr) {
                                let msg = 'Ocurrió un error.';
                                if (xhr.responseJSON) {
                                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                                    if (xhr.responseJSON.errors) {
                                        const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                                        if (firstKey) msg = xhr.responseJSON.errors[firstKey][0];
                                    }
                                }
                                $.alert({ title: 'Error', content: escapeHtml(msg), type: 'red' });
                            }
                        });
                    }
                }
            }
        });
    }

    function toggleActive(id, currentActive) {
        $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
        const willActivate = currentActive === '0';
        const title = willActivate ? 'Activar subtipo' : 'Desactivar subtipo';
        const content = willActivate ? '¿Deseas activarlo?' : '¿Deseas desactivarlo?';

        $.confirm({
            icon: 'fas fa-power-off',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'orange',
            title: title,
            content: content,
            buttons: {
                cancel: { text: 'Cancelar' },
                confirm: {
                    text: 'Confirmar',
                    btnClass: 'btn-warning',
                    action: function () {
                        $.ajax({
                            url: urlToggle,
                            type: 'POST',
                            dataType: 'json',
                            data: { id: id, _token: csrf() },
                            success: function (res) {
                                if (typeof toastr !== 'undefined') toastr.success(res.message || 'Actualizado', 'Éxito');
                                fetchList(currentPage);
                            },
                            error: function (xhr) {
                                let msg = 'Ocurrió un error.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                                $.alert({ title: 'Error', content: escapeHtml(msg), type: 'red' });
                            }
                        });
                    }
                }
            }
        });
    }

    // Events
    $(document).on('click', '#btn-search', function () { fetchList(1); });
    $(document).on('keydown', '#search', function (e) { if (e.key === 'Enter') fetchList(1); });
    $(document).on('change', '#filter_cash_box', function () { fetchList(1); });

    $(document).on('click', '[data-btn_create]', function () {
        returnFocus = this;
        $('#btnSaveSubtype').prop('disabled', !permissions.can_create);
        resetModal();
        $modal.modal('show');
    });

    $(document).on('click', '#btnSaveSubtype', function () { save(); });

    $(document).on('click', '[data-editar]', function () {
        const item = JSON.parse(this.dataset.item || '{}');
        returnFocus = document.getElementById('cash-subtype-actions-' + item.id);
        $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
        $('#btnSaveSubtype').prop('disabled', !permissions.can_edit);
        resetModal();
        fillModal(item);
        $modal.modal('show');
    });

    $(document).on('click', '[data-toggle-active]', function () {
        toggleActive(this.dataset.id, this.dataset.active);
    });

    $(document).on('click', '#pagination a.page-link', function (e) {
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const page = parseInt(this.dataset.page || '1', 10);
        if (!isNaN(page)) fetchList(page);
    });

    $(document).on('change', '#st_is_deferred', function () {
        toggleCommissionUI();
    });


    $form.on('submit', function (event) { event.preventDefault(); });
    $modal.on('shown.bs.modal', function () { $form.find('input[type="text"]').first().trigger('focus'); });
    $modal.on('hidden.bs.modal', function () { if (returnFocus && document.contains(returnFocus)) returnFocus.focus(); });
    $(document).on('hidden.bs.dropdown', '.next-row-actions', function () {
        $(this).children('.next-row-actions-trigger').attr('aria-expanded', 'false');
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        const menu = event.target.closest('.next-row-actions-menu');
        const id = menu && menu.getAttribute('aria-labelledby');
        if (!id || id.indexOf('cash-subtype-actions-') !== 0) return;
        event.preventDefault(); event.stopPropagation();
        const trigger = document.getElementById(id);
        $(trigger).dropdown('hide'); trigger.focus();
    }, true);

    $(function () {
        fetchList(1);
        toggleCommissionUI();
    });
})();
