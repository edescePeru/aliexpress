<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
    >

    <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
    >

    <title>
        {{ config('app.name', 'Venti360') }}
        | Cambiar contraseña
    </title>


    <link
            rel="stylesheet"
            href="{{
            asset(
                'admin/plugins/fontawesome-free/css/all.min.css'
            )
        }}"
    >

    <link
            rel="stylesheet"
            href="{{
            asset(
                'admin/dist/css/adminlte.min.css'
            )
        }}"
    >

    <link
            rel="stylesheet"
            href="{{
            asset(
                'admin/plugins/jquery-confirm/jquery-confirm.min.css'
            )
        }}"
    >


    <style>

        body {
            background:
                    #f4f6f9;
        }

        .password-change-wrapper {
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 20px;
        }

        .password-change-card {
            width: 100%;
            max-width: 470px;
        }

        .password-requirements {
            font-size: 0.85rem;
        }

    </style>

</head>


<body>

<div class="password-change-wrapper">

    <div
            class="
            card
            card-outline
            card-primary
            password-change-card
        "
    >

        <div class="card-header text-center">

            <h4 class="mb-0">
                Venti360
            </h4>

        </div>


        <div class="card-body">

            <div class="text-center mb-4">

                <i
                        class="
                        fas
                        fa-key
                        fa-3x
                        text-primary
                        mb-3
                    "
                ></i>

                <h5>
                    Cambia tu contraseña
                </h5>

                <p class="text-muted mb-0">
                    Estás utilizando una contraseña temporal.
                    Debes crear una nueva antes de continuar.
                </p>

            </div>


            <form
                    id="formRequiredPasswordChange"

                    data-url="{{
                    route(
                        'password.required.update'
                    )
                }}"
            >

                @csrf


                <div class="form-group">

                    <label for="password">
                        Nueva contraseña
                    </label>


                    <div class="input-group">

                        <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                autocomplete="new-password"
                                required
                        >


                        <div class="input-group-append">

                            <button
                                    type="button"
                                    class="
                                    btn
                                    btn-outline-secondary
                                    btn-show-password
                                "
                                    data-target="#password"
                            >

                                <i class="fas fa-eye"></i>

                            </button>

                        </div>

                    </div>

                </div>


                <div class="form-group">

                    <label for="password_confirmation">
                        Confirmar contraseña
                    </label>


                    <div class="input-group">

                        <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password"
                                required
                        >


                        <div class="input-group-append">

                            <button
                                    type="button"
                                    class="
                                    btn
                                    btn-outline-secondary
                                    btn-show-password
                                "
                                    data-target="#password_confirmation"
                            >

                                <i class="fas fa-eye"></i>

                            </button>

                        </div>

                    </div>

                </div>


                <div
                        class="
                        alert
                        alert-light
                        border
                        password-requirements
                    "
                >

                    <i
                            class="
                            fas
                            fa-info-circle
                            mr-1
                        "
                    ></i>

                    La contraseña debe tener
                    al menos 8 caracteres y ser
                    diferente a la contraseña temporal.

                </div>


                <button
                        type="submit"
                        id="btnSavePassword"
                        class="
                        btn
                        btn-primary
                        btn-block
                    "
                >

                    <i class="fas fa-save mr-1"></i>

                    Guardar nueva contraseña

                </button>

            </form>


            <hr>


            <div class="text-center">

                <form
                        action="{{ route('logout') }}"
                        method="POST"
                >

                    @csrf

                    <button
                            type="submit"
                            class="
                            btn
                            btn-link
                            text-muted
                            btn-sm
                        "
                    >

                        <i
                                class="
                                fas
                                fa-sign-out-alt
                                mr-1
                            "
                        ></i>

                        Cerrar sesión

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<script src="{{ asset( 'admin/plugins/jquery/jquery.min.js')}}"></script>

<script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

<script src="{{ asset( 'admin/plugins/jquery-confirm/jquery-confirm.min.js' ) }}" ></script>

<script src="{{ asset('js/auth/required-password-change.js')}}"></script>

</body>

</html>