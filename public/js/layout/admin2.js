$(document).ready(function () {
    initializeNextRowActions();

    $.ajax({
        url: "/api/sunat",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            $('#tasaCompra').text(normalizeExchangeRateValue(json && json.precioCompra));
            $('#tasaVenta').text(normalizeExchangeRateValue(json && json.precioVenta));
            $('#tasaFecha').text(formatExchangeRateDate(json && json.fecha));
        }
    });

    $.ajax({
        url: "/dashboard/get/notifications",
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            var quantity_notifications_unread = 0;
            let popupNotifications = [];

            // Procesar notificaciones NO LEÍDAS
            data.notifications.forEach(function(notification) {
                if (notification.read == 0) {
                    quantity_notifications_unread++;
                    renderTemplateNotificationUnread(notification);

                    if (notification.is_popup) {
                        popupNotifications.push(notification);
                    }
                }
            });

            // Procesar notificaciones LEÍDAS
            data.notifications.forEach(function(notification) {
                if (notification.read == 1) {
                    renderTemplateNotificationRead(notification);
                }
            });

            // Mostrar el contador
            if (quantity_notifications_unread > 0) {
                $('#total_notifications').show();
                $('#read-all').show();
                $('#total_notifications').html(quantity_notifications_unread);
                $('#quantity_notifications').html(quantity_notifications_unread + ' notificación(es)');
            } else {
                $('#total_notifications').hide();
                $('#read-all').hide();
                $('#quantity_notifications').html('0 notificación(es)');
            }

            // Mostrar UNA sola ventana modal con todas las notificaciones tipo pop-up
            if (popupNotifications.length > 0) {
                let contentHtml = '<ul style="text-align: left;">';

                popupNotifications.forEach(function(n) {
                    contentHtml += `<li style="margin-bottom: 10px;">🟢 ${n.message}`;
                    if (n.url_go) {
                        contentHtml += `<br><a href="${n.url_go}" target="_blank">Ir al detalle</a>`;
                    }
                    contentHtml += '</li>';
                });

                contentHtml += '</ul>';

                $.confirm({
                    title: 'Notificaciones importantes',
                    content: contentHtml,
                    columnClass: 'medium',
                    buttons: {
                        markAsRead: {
                            text: 'Cerrar y marcar como leído',
                            btnClass: 'btn-green',
                            action: function () {
                                const ids = popupNotifications.map(n => n.id_notification_user);
                                $.ajax({
                                    url: '/dashboard/leer/notificaciones/pop_up',
                                    method: 'POST',
                                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                    data: { ids: ids },
                                    success: function (data) {
                                        $.alert(data.message);
                                        setTimeout(() => location.reload(), 1500);
                                    },
                                    error: function () {
                                        $.alert("Ocurrió un error al marcar las notificaciones.");
                                    }
                                });
                            }
                        },
                        close: {
                            text: 'Solo cerrar',
                            btnClass: 'btn-default'
                        }
                    }
                });
            }
        }
    });
    $(document).on('click', '[data-read]', readNotification);
    $('#read-all').on('click', readAllNotification);

});

function normalizeExchangeRateValue(value) {
    if (
        value === null ||
        typeof value === 'undefined' ||
        String(value).trim() === '' ||
        String(value).toLowerCase() === 'undefined' ||
        String(value).toLowerCase() === 'null'
    ) {
        return 'No disponible';
    }

    return String(value);
}

function formatExchangeRateDate(value) {
    var dateMatch = typeof value === 'string'
        ? value.match(/^(\d{4})-(\d{2})-(\d{2})/)
        : null;

    if (!dateMatch) {
        return 'No disponible';
    }

    return 'Actualizado: ' + dateMatch[3] + '/' + dateMatch[2] + '/' + dateMatch[1];
}

function initializeNextRowActions() {
    var triggerCounter = 0;

    $(document).on('show.bs.dropdown', '.next-row-actions', function () {
        var dropdown = this;
        var $dropdown = $(dropdown);
        var $trigger = $dropdown.children('.next-row-actions-trigger').first();
        var $menu = $dropdown.children('.next-row-actions-menu').first();

        if (!$menu.length) {
            return;
        }

        if (!$trigger.attr('id')) {
            triggerCounter += 1;
            $trigger.attr('id', 'next-row-actions-trigger-' + triggerCounter);
        }

        $menu
            .attr('aria-labelledby', $trigger.attr('id'))
            .attr('data-next-row-actions-portaled', 'true')
            .data('nextRowActionsOwner', dropdown)
            .data('nextRowActionsNextSibling', $menu[0].nextSibling)
            .appendTo(document.body);
    });

    $(document).on('hidden.bs.dropdown', '.next-row-actions', function () {
        var dropdown = this;
        var $menu = $('body > .next-row-actions-menu').filter(function () {
            return $(this).data('nextRowActionsOwner') === dropdown;
        }).first();

        if (!$menu.length) {
            return;
        }

        var nextSibling = $menu.data('nextRowActionsNextSibling');

        $menu.removeAttr('data-next-row-actions-portaled');

        if (nextSibling && nextSibling.parentNode === dropdown) {
            $menu.insertBefore(nextSibling);
        } else {
            $menu.appendTo(dropdown);
        }

        $menu
            .removeData('nextRowActionsOwner')
            .removeData('nextRowActionsNextSibling');
    });
}

function showNotificationModal(message, url) {
    $.confirm({
        title: 'Notificación',
        content: message + (url ? `<br><br><a href="${url}" class="btn btn-primary">Ir a detalle</a>` : ''),
        buttons: {
            ok: {
                text: 'Aceptar',
                btnClass: 'btn-blue'
            }
        }
    });
}

function readAllNotification() {
    $.confirm({
        icon: 'fas fa-check-double',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de marcar como leído todas las notificaciones?',
        content: '',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/leer/todas/notificaciones',
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            //console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                location.reload();
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Proceso cancelado.");
                },
            },
        }
    });
}

function readNotification() {
    var id_notification_user = $(this).attr('data-read');
    var content_message = $(this).attr('data-content');
    $.confirm({
        icon: 'fas fa-check-double',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de marcar como leído esta notificación?',
        content: content_message,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/read/notification/'+id_notification_user,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            //console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                location.reload();
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Proceso cancelado.");
                },
            },
        }
    });
}

function renderTemplateNotificationUnread(notification) {
    var clone = activateTemplate('#notification-unread');
    clone.querySelector("[data-message]").innerHTML = notification.message;
    clone.querySelector("[data-time]").innerHTML = notification.time;
    clone.querySelector("[data-read]").setAttribute('data-read', notification.id_notification_user);
    clone.querySelector("[data-read]").setAttribute('data-content', notification.message);
    clone.querySelector("[data-go]").setAttribute('href', notification.url_go);

    $('#body-notifications').append(clone);
}

function renderTemplateNotificationRead(notification) {
    var clone = activateTemplate('#notification-read');
    clone.querySelector("[data-message]").innerHTML = notification.message;
    clone.querySelector("[data-time]").innerHTML = notification.time;
    clone.querySelector("[data-go]").setAttribute('href', notification.url_go);

    $('#body-notifications').append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}

