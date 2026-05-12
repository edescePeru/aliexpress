<!-- Portada -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ env('APP_NAME') }}</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="0ti5-pM4JvRkJ2Gwg5tqmsBXep9iU_7hz5LHDCIwFEM" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">
    <!-- Google Font: Source Sans Pro -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin/dist/img/logo_dashboard.ico') }}">


    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        {{--<img src="{{ asset('admin/dist/img/Logo.svg') }}" alt="" width="350px">--}}
        <img src="{{ asset('landing/img/logo_pdf.png') }}" alt="" width="350px">
    </div>
    <!-- /.login-logo -->
    <div class="card">
        <div class="card-body login-card-body">
            @guest
                <h4 class="login-box-msg">Sistema interno</h4>
            @else
                <h4 class="login-box-msg">Bienvenido a la Intranet</h4>
                <h4 class="login-box-msg">{{ Auth::user()->name }}</h4>
            @endguest
            <div class="row">
                {{--<div class="col-md-12">
                    <a href="https://www.sermeind.com.pe/" class="btn btn-primary btn-block">Regresar a la pagina principal</a>
                </div>
                <br><br>--}}
                @guest
                    <div class="col-md-12">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-block">Iniciar sesión</a>
                    </div>
                @else

                    @can('access_dashboard')
                        <div class="col-md-12">
                            <a href="{{ route('dashboard.principal') }}" class="btn btn-success btn-block">Ir al Dashboard</a>
                        </div>

                    @endcan
                    <br><br>
                    <div class="col-md-12">
                        <a class="btn btn-danger btn-block" href="{{ route('logout') }}"
                           onclick="event.preventDefault();
                           document.getElementById('logout-form').submit();">
                            <i class="fa fa-sign-out"></i>
                            {{ __('Cerrar Sesión') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>

                @endguest
            </div>

        </div>
        <!-- /.login-card-body -->
    </div>
    <div class="lockscreen-footer text-center">
        Copyright &copy;<script>document.write(new Date().getFullYear());</script> Todos los derechos reservados por <a href="https://www.edesce.com/" target="_blank">EDESCE</a>

    </div>
</div>

<!-- jQuery -->
<script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
