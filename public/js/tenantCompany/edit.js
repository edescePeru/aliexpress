$(document).ready(function () {

    $('#formEditTenantCompany').on(
        'submit',
        function (e) {

            e.preventDefault();

            const form = $(this);

            const url =
                form.data('url');

            const button =
                form.find(
                    'button[type="submit"]'
                );


            button.prop(
                'disabled',
                true
            );


            $.ajax({

                url: url,

                method: 'POST',

                data: form.serialize(),

                success: function (response) {

                    toastr.success(
                        response.message
                    );

                    if (response.url) {

                        window.location.href =
                            response.url;
                    }
                },

                error: function (xhr) {

                    let message =
                        'No se pudo actualizar la empresa.';


                    /*
                     * Mensaje general enviado por backend.
                     */
                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {
                        message =
                            xhr.responseJSON.message;
                    }


                    /*
                     * Errores de validación Laravel.
                     */
                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {

                        const errors =
                            xhr.responseJSON.errors;

                        const firstKey =
                            Object.keys(errors)[0];

                        if (
                            firstKey &&
                            errors[firstKey] &&
                            errors[firstKey][0]
                        ) {
                            message =
                                errors[firstKey][0];
                        }
                    }


                    toastr.error(
                        message
                    );
                },

                complete: function () {

                    button.prop(
                        'disabled',
                        false
                    );
                }

            });

        }
    );

});