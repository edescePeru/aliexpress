(function (root) {
    'use strict';
    function can(permission, permissions) {
        return Array.isArray(permissions) && permissions.indexOf(permission) !== -1;
    }
    function transition(before, after, permissions) {
        if (['pending', 'pending50', 'canceled'].indexOf(after) === -1) return false;
        if (before === after) return true;
        if ((before === 'canceled' || after === 'pending') && !can('nopay_credit', permissions)) return false;
        if ((after === 'pending50' || after === 'canceled') && !can('pay_credit', permissions)) return false;
        return true;
    }
    function stateControl(id, state, permissions) {
        var labels = {pending: 'PENDIENTE', pending50: 'CANC./PEND.50%', canceled: 'CANCELADO'};
        if (!Object.prototype.hasOwnProperty.call(labels, state)) return '<span>Sin estado</span>';
        var allowed = Object.keys(labels).filter(function (next) { return transition(state, next, permissions); });
        if (allowed.length === 1) return '<span>' + labels[state] + '</span>';
        id = Number(id);
        if (!Number.isSafeInteger(id) || id <= 0) return '<span>' + labels[state] + '</span>';
        return '<select data-id="' + id + '" data-current="' + state + '" name="state_paid" class="form-control form-control-sm select2 state_paid" style="width: 115px;">' +
            allowed.map(function (value) { return '<option value="' + value + '"' + (value === state ? ' selected' : '') + '>' + labels[value] + '</option>'; }).join('') + '</select>';
    }
    function allow(permission, permissions) {
        var accepted = can(permission, permissions);
        if (!accepted && root.toastr) root.toastr.warning('No tienes permiso para realizar esta acción.');
        return accepted;
    }
    root.SupplierCreditUi = {can: can, transition: transition, stateControl: stateControl, allow: allow};
}(window));
