(function () {
    const urlList = window.CASH_MOVEMENT_LIST_URL;

    const $bodyTable = $('#body-table');
    const $pagination = $('#pagination');
    const $textPagination = $('#textPagination');

    // Permisos
    let permissions = [];
    try { permissions = JSON.parse($('#permissions').val() || '[]'); } catch (e) { permissions = []; }
    const canRegularize = $.inArray('regularize_caja', permissions) !== -1;

    let currentPage = 1;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderPagination(meta) {
        $pagination.html('');
        if (!meta || meta.last_page <= 1) {
            $textPagination.text(meta && meta.total ? `Mostrando ${meta.total} registros` : '');
            return;
        }

        $textPagination.text(`Mostrando ${meta.from || 0} a ${meta.to || 0} de ${meta.total || 0}`);

        const current = meta.current_page;
        const last = meta.last_page;

        const make = (page, label, active, disabled) => {
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

        $pagination.append(make(Math.max(1, current - 1), '«', false, current === 1));

        const w = 2;
        const start = Math.max(1, current - w);
        const end = Math.min(last, current + w);

        if (start > 1) $pagination.append(make(1, '1', current === 1, false));
        if (start > 2) $pagination.append($('<li class="page-item disabled"><span class="page-link">...</span></li>'));

        for (let p = start; p <= end; p++) $pagination.append(make(p, String(p), p === current, false));

        if (end < last - 1) $pagination.append($('<li class="page-item disabled"><span class="page-link">...</span></li>'));
        if (end < last) $pagination.append(make(last, String(last), current === last, false));

        $pagination.append(make(Math.min(last, current + 1), '»', false, current === last));
    }

    function formatTypeLabel(typeRaw, regularize) {
        if ((regularize === 0 || regularize === '0') && (typeRaw === 'sale' || typeRaw === 'income')) return 'Regularizar';
        if (typeRaw === 'sale') return 'Venta';
        if (typeRaw === 'income') return 'Ingreso';
        if (typeRaw === 'expense') return 'Egreso';
        return typeRaw || '-';
    }

    function renderButtons(data) {
        const wrapper = document.createElement('div');

        const tpl = document.querySelector('#template-button');
        const cloneBtn2 = tpl.content.cloneNode(true);

        // Print solo si hay sale_id
        const printBtn = cloneBtn2.querySelector('[data-print_nota]');
        if (data.sale_id != null) {
            let url = document.location.origin + '/dashboard/imprimir/documento/venta/' + data.sale_id;
            printBtn.setAttribute('href', url);
            printBtn.setAttribute('data-id', data.id);
        } else {
            printBtn.style.display = 'none';
        }

        // Regularizar solo si permitido y pendiente y sale/income
        const regBtn = cloneBtn2.querySelector('[data-regularizar]');
        const isPending = (data.regularize === 0 || data.regularize === '0');
        const typeRaw = data.type_raw || data.type;
        const canBeRegularized = (typeRaw === 'sale' || typeRaw === 'income');

        if (canRegularize && isPending && canBeRegularized) {
            regBtn.setAttribute('data-id', data.id);
        } else {
            regBtn.style.display = 'none';
        }

        wrapper.appendChild(cloneBtn2);
        return wrapper;
    }

    function renderRow(data) {
        const row = document.querySelector('#item-table').content.cloneNode(true);

        const isPending = (data.regularize === 0 || data.regularize === '0');
        const typeRaw = data.type_raw || data.type;

        row.querySelector('[data-id]').textContent = data.id;
        row.querySelector('[data-user]').textContent = data.user_name || '-';
        row.querySelector('[data-cashbox]').textContent = data.cash_box_name || '-';
        row.querySelector('[data-type]').textContent = formatTypeLabel(typeRaw, data.regularize);
        row.querySelector('[data-subtype]').textContent = data.subtype_name || '-';

        row.querySelector('[data-status]').innerHTML = isPending
            ? '<span class="badge badge-warning">Pendiente</span>'
            : '<span class="badge badge-success">Confirmado</span>';

        row.querySelector('[data-date]').textContent = data.created_at || '-';
        row.querySelector('[data-amount]').textContent = data.amount != null ? data.amount : '0.00';
        row.querySelector('[data-amount_regularize]').textContent = data.amount_regularize != null ? data.amount_regularize : '-';
        row.querySelector('[data-commission]').textContent = data.commission != null ? data.commission : '-';

        let desc = data.description || '-';
        if (data.observation) desc += ' | ' + data.observation;
        row.querySelector('[data-description]').innerHTML = escapeHtml(desc);

        const btnCell = row.querySelector('[data-buttons]');
        btnCell.innerHTML = '';
        btnCell.appendChild(renderButtons(data));

        const tr = row.querySelector('tr');
        if (tr && isPending) tr.classList.add('regularize-row');

        return row;
    }

    function fetchList(page = 1) {
        currentPage = page;

        const params = {
            page: page,
            q: ($('#q').val() || '').trim(),
            user_id: $('#user_id').val(),
            cash_box_id: $('#cash_box_id').val(),
            type: $('#type').val(),
            subtype_id: $('#subtype_id').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
        };

        $.ajax({
            url: urlList,
            type: 'GET',
            dataType: 'json',
            data: params,
            success: function (res) {
                const items = res.data || [];
                const meta = res.meta || null;

                $bodyTable.html('');

                if (!items.length) {
                    $bodyTable.append(document.querySelector('#item-table-empty').content.cloneNode(true));
                    renderPagination(meta);
                    return;
                }

                items.forEach(it => $bodyTable.append(renderRow(it)));

                $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({ selector: '[data-toggle="tooltip"]' });

                renderPagination(meta);
            },
            error: function () {
                $bodyTable.html('');
                $bodyTable.append(document.querySelector('#item-table-empty').content.cloneNode(true));
            }
        });
    }

    // filtros
    $(document).on('click', '#btn-search', function () { fetchList(1); });
    $(document).on('keydown', '#q', function (e) { if (e.key === 'Enter') fetchList(1); });
    $(document).on('change', '#user_id,#cash_box_id,#type,#subtype_id,#date_from,#date_to', function () { fetchList(1); });

    // paginación
    $(document).on('click', '#pagination a.page-link', function (e) {
        e.preventDefault();
        const p = parseInt(this.dataset.page || '1', 10);
        if (!isNaN(p)) fetchList(p);
    });

    $(function () { fetchList(1); });
})();
