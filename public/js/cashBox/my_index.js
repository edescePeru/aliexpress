(function () {
    const urlList = window.CASH_MOVEMENT_LIST_URL;

    const $bodyTable = $('#body-table');
    const $pagination = $('#pagination');
    const $textPagination = $('#textPagination');
    const $numberItems = $('#numberItems');

    let currentPage = 1;

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

    function formatType(t) {
        if (t === 'sale') return 'Venta';
        if (t === 'income') return 'Ingreso';
        if (t === 'expense') return 'Egreso';
        return t || '-';
    }

    function fetchList(page = 1) {
        currentPage = page;

        const params = {
            page: page,
            q: ($('#q').val() || '').trim(),
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
                $numberItems.text(meta && meta.total !== undefined ? meta.total : items.length);

                if (!items.length) {
                    $bodyTable.append(document.querySelector('#item-table-empty').content.cloneNode(true));
                    renderPagination(meta);
                    return;
                }

                items.forEach(it => {
                    const row = document.querySelector('#item-table').content.cloneNode(true);
                    row.querySelector('[data-date]').textContent = it.created_at;
                    row.querySelector('[data-cashbox]').textContent = it.cash_box_name || '-';
                    row.querySelector('[data-type]').textContent = formatType(it.type);
                    row.querySelector('[data-subtype]').textContent = it.subtype_name || '-';
                    row.querySelector('[data-desc]').textContent = it.description || '-';
                    row.querySelector('[data-amount]').textContent = it.amount;

                    $bodyTable.append(row);
                });

                renderPagination(meta);
            },
            error: function () {
                $bodyTable.html('');
                $bodyTable.append(document.querySelector('#item-table-empty').content.cloneNode(true));
            }
        });
    }

    $(document).on('click', '#btn-search', function () { fetchList(1); });
    $(document).on('keydown', '#q', function (e) { if (e.key === 'Enter') fetchList(1); });
    $(document).on('change', '#cash_box_id,#type,#subtype_id,#date_from,#date_to', function () { fetchList(1); });

    $(document).on('click', '#pagination a.page-link', function (e) {
        e.preventDefault();
        const p = parseInt(this.dataset.page || '1', 10);
        if (!isNaN(p)) fetchList(p);
    });

    $(function () { fetchList(1); });

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    $(document).on('click', '[data-cashbox-btn]', function () {
        const $btn = $(this);
        const cashBoxId = $btn.data('cash_box_id');
        const name = $btn.data('cash_box_name');
        const type = $btn.data('cash_box_type'); // cash | bank
        const isOpen = String($btn.data('is_open')) === '1';

        // Si ya está abierta (verde) => ir a sesión
        if (isOpen) {
            window.location.href = window.CASH_REGISTER_SESSION_URL_BASE + '/' + cashBoxId;
            return;
        }

        // Si está cerrada (gris) => confirmar apertura
        let content = `¿Deseas iniciar sesión en la caja: <b>${name}</b>?`;
        let hasInput = (type === 'cash');

        if (hasInput) {
            content += `
            <br><br>
            <div class="form-group mb-0">
                <label>Saldo inicial (Efectivo)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="opening_balance" value="0.00">
            </div>
        `;
        }

        $.confirm({
            icon: 'fas fa-cash-register',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'blue',
            title: 'Iniciar sesión',
            content: content,
            buttons: {
                cancel: { text: 'Cancelar' },
                confirm: {
                    text: 'Confirmar',
                    btnClass: 'btn-primary',
                    action: function () {
                        const openingBalance = hasInput ? ($('#opening_balance').val() || 0) : 0;

                        $.ajax({
                            url: window.CASH_REGISTER_OPEN_URL,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                _token: csrf(),
                                cash_box_id: cashBoxId,
                                opening_balance: openingBalance
                            },
                            success: function (res) {
                                // Cambiar botón a verde
                                $btn.removeClass('btn-secondary').addClass('btn-success');
                                $btn.data('is_open', 1);
                                $btn.attr('data-is_open', '1');

                                if (typeof toastr !== 'undefined') {
                                    toastr.success(res.message || 'Sesión iniciada', 'OK');
                                }

                                // Redirigir a la sesión (opcional)
                                window.location.href = window.CASH_REGISTER_SESSION_URL_BASE + '/' + cashBoxId;
                            },
                            error: function (xhr) {
                                let msg = 'No se pudo iniciar la sesión.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                                $.alert({ title: 'Error', content: msg, type: 'red' });
                            }
                        });
                    }
                }
            }
        });
    });
})();
