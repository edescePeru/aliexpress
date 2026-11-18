(function () {
    'use strict';
    const root = document.getElementById('summary-movements');
    if (!root) return;
    const form = document.getElementById('summary-filters');
    const body = document.getElementById('body-table');
    const pagination = document.getElementById('pagination');
    const text = document.getElementById('textPagination');
    const error = document.getElementById('summary-error');
    let pending, sequence = 0;
    function cell(row, value, className) {
        const td = document.createElement('td');
        td.className = className || '';
        td.textContent = value === null || value === undefined || value === '' ? '—' : value;
        row.appendChild(td);
        return td;
    }
    function render(item) {
        const row = document.createElement('tr');
        const isPending = !item.deferred_cancelled && Number(item.regularize) === 0;
        const raw = item.type_raw || item.type;
        const type = item.regularization_source_id ? 'Abono por regularización' :
            (isPending && ['sale', 'income'].includes(raw) ? 'Regularizar' : ({sale:'Venta', income:'Ingreso', expense:'Egreso'}[raw] || raw));
        cell(row, item.id, 'text-center'); cell(row, item.created_at, 'text-center');
        const actor = cell(row, item.user_name);
        const detail = document.createElement('small'); detail.className = 'd-block text-muted';
        detail.textContent = [item.branch_name, item.cash_register_id ? '#' + item.cash_register_id : null].filter(Boolean).join(' · ');
        actor.appendChild(detail);
        cell(row, item.cash_box_name); cell(row, type, 'text-center'); cell(row, item.subtype_name);
        const status = cell(row, '', 'text-center'); status.textContent = '';
        const badge = document.createElement('span');
        badge.className = 'badge ' + (item.deferred_cancelled ? 'badge-secondary' : isPending ? 'badge-warning' : item.balance_effect === false ? 'badge-info' : 'badge-success');
        badge.textContent = item.deferred_cancelled ? 'Cancelado' : isPending ? 'Pendiente' : item.balance_effect === false ? 'Regularizado en otra sesión' : 'Confirmado';
        status.appendChild(badge);
        cell(row, item.balance_effect === true ? 'Sí' : item.balance_effect === false ? 'No' : 'No informado', 'text-center');
        cell(row, item.amount, 'text-right'); cell(row, item.amount_regularize, 'text-right'); cell(row, item.commission, 'text-right');
        cell(row, [item.description, item.observation].filter(Boolean).join(' | '), 'text-nowrap');
        body.appendChild(row);
    }
    function pageButton(page, label, disabled, active) {
        const li = document.createElement('li'); li.className = 'page-item' + (active ? ' active' : '');
        const button = document.createElement('button'); button.type = 'button'; button.className = 'page-link';
        button.textContent = label; button.disabled = disabled; if (active) button.setAttribute('aria-current', 'page');
        button.addEventListener('click', () => load(page)); li.appendChild(button); pagination.appendChild(li);
    }
    function load(page) {
        const version = ++sequence;
        if (pending) pending.abort();
        error.classList.add('d-none'); text.textContent = 'Cargando movimientos…';
        const params = Object.fromEntries(new FormData(form).entries()); params.page = page;
        pending = $.ajax({url:root.dataset.url, method:'GET', dataType:'json', data:params})
            .done(function (res) {
                if (version !== sequence) return;
                body.textContent = ''; pagination.textContent = '';
                (res.data || []).forEach(render);
                if (!res.data || !res.data.length) { const row = document.createElement('tr'); const td = cell(row, 'No se encontraron movimientos', 'next-table-empty'); td.colSpan = 12; body.appendChild(row); }
                const meta = res.meta || {};
                text.textContent = `Mostrando ${meta.from || 0} a ${meta.to || 0} de ${meta.total || 0} movimientos`;
                if (meta.last_page > 1) {
                    pageButton(Math.max(1, meta.current_page - 1), 'Anterior', meta.current_page <= 1, false);
                    for (let n = Math.max(1, meta.current_page - 2); n <= Math.min(meta.last_page, meta.current_page + 2); n++) pageButton(n, String(n), false, n === meta.current_page);
                    pageButton(Math.min(meta.last_page, meta.current_page + 1), 'Siguiente', meta.current_page >= meta.last_page, false);
                }
            }).fail(function (xhr, status) {
                if (status === 'abort' || version !== sequence) return;
                body.textContent = ''; pagination.textContent = ''; text.textContent = 'Consulta no disponible';
                error.classList.remove('d-none');
                error.textContent = 'No se pudieron consultar los movimientos' + (xhr.status ? ' (HTTP ' + xhr.status + ')' : '') + '. Revisa los filtros o vuelve a intentarlo.';
            });
    }
    form.addEventListener('submit', e => { e.preventDefault(); load(1); });
    form.addEventListener('change', e => { if (e.target.tagName === 'SELECT' || e.target.type === 'date') load(1); });
    load(1);
})();