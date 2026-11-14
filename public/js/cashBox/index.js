(function () {
    const $bodyTable = $('#body-table');
    const $pagination = $('#pagination');
    const $textPagination = $('#textPagination');
    const $numberItems = $('#numberItems');

    const $modal = $('#modalCashBox');
    const $form = $('#formCashBox');

    const urlList = $form.data('url_list');
    const urlCreate = $form.data('url_create');
    const urlUpdate = $form.data('url_update');
    const urlToggle = $form.data('url_toggle');

    let currentPage = 1;
    let listRequest, listSequence = 0, returnFocus;
    let lastQuery = '';

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function badgeFromTemplate(templateId) {
        const tpl = document.querySelector(templateId);
        return tpl ? tpl.content.cloneNode(true) : document.createTextNode('');
    }

    function setBankFieldsVisibility() {

        const type =
            $('#cb_type').val();

        if (
            type ===
            'bank'
        ) {

            $('#wrap_bank_fields')
                .show();

            $('#wrap_uses_subtypes')
                .show();

            /*
             * Moneda viene de CompanyBankAccount.
             */
            $('#wrap_currency')
                .hide();

        } else {

            $('#wrap_bank_fields')
                .hide();

            $('#wrap_uses_subtypes')
                .hide();

            $('#wrap_currency')
                .show();

            $('#cb_uses_subtypes')
                .prop(
                    'checked',
                    false
                );

            $('#cb_company_bank_account_id')
                .val('');
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
        $('#cashBoxTitle').text('Nueva Caja');
        $('#cb_id').val('');
        $('#cb_name').val('');
        $('#cb_type').val('cash');
        $('#cb_uses_subtypes').prop('checked', false);
        $('#cb_company_bank_account_id').val('');
        $('#cb_currency').val('PEN');
        $('#cb_position').val(0);
        $('#cb_branch_id').val('');

        setBankFieldsVisibility();
    }

    function fillModal(data) {
        $('#cashBoxTitle').text('Editar Caja');
        $('#cb_id').val(data.id);
        $('#cb_name').val(data.name || '');
        $('#cb_type').val(data.type || 'cash');
        $('#cb_uses_subtypes').prop('checked', !!data.uses_subtypes);
        selectPersisted('#cb_company_bank_account_id', data.company_bank_account_id || '', data.bank_account_title);
        $('#cb_currency').val(data.currency || '');
        $('#cb_position').val(data.position !== null && data.position !== undefined ? data.position : 0);
        selectPersisted('#cb_branch_id', data.branch_id || '', data.scope_label);

        setBankFieldsVisibility();
    }

    function renderButtons(item) {
        if (!window.canEditCashBox && !window.canToggleCashBox) return document.createTextNode('—');
        const container = document.querySelector('#template-row-actions').content.cloneNode(true).firstElementChild;
        const trigger = container.querySelector('.next-row-actions-trigger');
        const menu = container.querySelector('.next-row-actions-menu');
        trigger.id = 'cash-box-actions-' + item.id;
        trigger.setAttribute('aria-label', 'Acciones de ' + item.name);
        menu.setAttribute('aria-labelledby', trigger.id);
        if (window.canEditCashBox) {
            const button = document.querySelector('#template-btn-edit').content.cloneNode(true).querySelector('button');
            button.dataset.item = JSON.stringify(item);
            menu.appendChild(button);
        }
        if (window.canToggleCashBox) {
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

        tpl.querySelector('[data-name]').textContent = item.name || '';

        // Tipo
        const typeText = item.type === 'bank' ? 'Bancario' : 'Efectivo';
        tpl.querySelector('[data-type]').textContent = typeText;
        tpl.querySelector('[data-scope]').textContent = item.scope_label || 'Toda la empresa';

        // uses_subtypes
        const usesCell = tpl.querySelector('[data-uses_subtypes]');
        usesCell.innerHTML = '';
        usesCell.appendChild(badgeFromTemplate(item.uses_subtypes ? '#badge-yes' : '#badge-no'));

        const bankAccountCell =
            tpl.querySelector('[data-bank-account]');

        if (
            item.type === 'bank' &&
            item.company_bank_account_id
        ) {

            const bankName =
                item.bank_name || '';

            const accountTitle =
                item.bank_account_title || '';

            const accountNumber =
                item.account_number || '';

            const lines = [];

            if (bankName) {
                lines.push(
                    `<strong>${escapeHtml(bankName)}</strong>`
                );
            }

            const accountInfo = [
                accountTitle,
                accountNumber
            ]
                .filter(Boolean)
                .join(' · ');

            if (accountInfo) {
                lines.push(
                    escapeHtml(accountInfo)
                );
            }

            bankAccountCell.innerHTML =
                lines.join('<br>');

        } else {

            bankAccountCell.textContent = '-';
        }

        tpl.querySelector('[data-currency]').textContent = item.currency || '-';

        const statusCell = tpl.querySelector('[data-status]');
        statusCell.innerHTML = '';
        statusCell.appendChild(badgeFromTemplate(item.is_active ? '#badge-active' : '#badge-inactive'));

        // Buttons
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
        // meta esperado: { current_page, last_page, total, from, to }
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

        // Prev
        $pagination.append(makePageItem(Math.max(1, current - 1), '«', false, current === 1));

        // Window pages
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

        // Next
        $pagination.append(makePageItem(Math.min(last, current + 1), '»', false, current === last));
    }

    function fetchList(page = 1) {
        const q = ($('#search').val() || '').trim();
        lastQuery = q;
        currentPage = page;
        const sequence = ++listSequence;
        if (listRequest) listRequest.abort();
        $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
        $('#list-feedback').addClass('d-none').text('');
        $bodyTable.empty(); $pagination.empty(); $numberItems.text('—');
        $textPagination.text('Cargando…');

        listRequest = $.ajax({
            url: urlList,
            type: 'GET',
            dataType: 'json',
            data: { page: page, q: q },
            success: function (res) {
                if (sequence !== listSequence) return;
                // res esperado:
                // { data: [...], meta: { current_page, last_page, total, from, to }, permissions?: {...} }
                $bodyTable.html('');

                const items = res.data || [];
                const meta = res.meta || null;

                $numberItems.text(meta && meta.total !== undefined ? meta.total : items.length);

                if (!items.length) {
                    renderEmpty();
                    renderPagination(meta);
                    return;
                }

                items.forEach(item => {
                    $bodyTable.append(renderRow(item));
                });

                renderPagination(meta);
            },
            error: function (xhr, status) {
                if (status === 'abort' || sequence !== listSequence) return;
                $bodyTable.empty(); $pagination.empty(); $textPagination.text('Consulta no disponible');
                $('#list-feedback').removeClass('d-none').text(xhr.status === 403 ? 'No tiene permiso para consultar este listado.' : 'No se pudo cargar el listado. Vuelva a buscar o recargue la página.');
            }
        });
    }

    function validateForm() {
        const name = ($('#cb_name').val() || '').trim();
        const type = $('#cb_type').val();

        if (!name) return 'El nombre es obligatorio.';

        if (type !== 'cash' && type !== 'bank') return 'Tipo inválido.';

        // Si es efectivo, no debe usar subtypes
        if (type === 'cash' && $('#cb_uses_subtypes').is(':checked')) {
            return 'Efectivo no puede usar subtipos.';
        }

        if (
            type === 'bank' &&
            !$('#cb_company_bank_account_id')
                .val()
        ) {

            return 'Debe seleccionar una cuenta bancaria.';
        }

        return null;
    }

    function buildPayload() {

        return {

            id:
                $('#cb_id').val()
                || null,

            name:
                (
                    $('#cb_name').val()
                    || ''
                ).trim(),

            type:
                $('#cb_type').val(),

            branch_id:
                $('#cb_branch_id').val()
                || '',

            uses_subtypes:
                $('#cb_uses_subtypes')
                    .is(':checked')
                    ? 1
                    : 0,

            company_bank_account_id:
                $('#cb_company_bank_account_id')
                    .val()
                || '',

            currency:
                $('#cb_currency').val()
                || '',

            position:
                $('#cb_position').val()
                || 0,

            _token:
                csrf()
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
            title: isEdit ? 'Actualizar Caja' : 'Crear Caja',
            content: isEdit ? '¿Deseas guardar los cambios?' : '¿Deseas crear esta caja?',
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
        const title = willActivate ? 'Activar caja' : 'Desactivar caja';
        const content = willActivate
            ? '¿Deseas activar esta caja?'
            : '¿Deseas desactivar esta caja?';

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
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message || 'Actualizado', 'Éxito');
                                }
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

    // Capabilities supplied by the current backend; absent capabilities remain false.
    const permissions =
        JSON.parse(
            $('#permissions').val()
            || '{}'
        );

    window.canEditCashBox =
        !!permissions.can_edit;

    window.canToggleCashBox =
        !!permissions.can_toggle;

    // Events
    $(document).on('click', '#btn-search', function () {
        fetchList(1);
    });

    $(document).on('keydown', '#search', function (e) {
        if (e.key === 'Enter') fetchList(1);
    });

    $(document).on('click', '[data-btn_create]', function () {
        returnFocus = this;
        $('#btnSaveCashBox').prop('disabled', !permissions.can_create);
        resetModal();
        $modal.modal('show');
    });

    $(document).on('change', '#cb_type', function () {
        setBankFieldsVisibility();
    });

    $(document).on('click', '#btnSaveCashBox', function () {
        save();
    });

    $(document).on('click', '[data-editar]', function () {
        const item = JSON.parse(this.dataset.item || '{}');
        returnFocus = document.getElementById('cash-box-actions-' + item.id);
        $('.next-row-actions-trigger[aria-expanded="true"]').dropdown('hide');
        $('#btnSaveCashBox').prop('disabled', !permissions.can_edit);
        resetModal();
        fillModal(item);
        $modal.modal('show');
    });

    $(document).on('click', '[data-toggle-active]', function () {
        const id = this.dataset.id;
        const active = this.dataset.active;
        toggleActive(id, active);
    });

    $(document).on('click', '#pagination a.page-link', function (e) {
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const page = parseInt(this.dataset.page || '1', 10);
        if (!isNaN(page)) fetchList(page);
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
        if (!id || id.indexOf('cash-box-actions-') !== 0) return;
        event.preventDefault(); event.stopPropagation();
        const trigger = document.getElementById(id);
        $(trigger).dropdown('hide'); trigger.focus();
    }, true);

    // Init
    $(function () {
        fetchList(1);
    });
})();
