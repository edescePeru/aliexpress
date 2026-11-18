@extends('layouts.appAdmin2')
@section('title', 'Créditos de proveedores')
@section('openCredit', 'menu-open')
@section('activeCreditSupplier', 'active')
@section('activeListCreditSupplier', 'active')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Proveedores · créditos</span><h1 class="page-title">Créditos registrados</h1><p class="next-page-description">Consulta de la empresa activa, sin operaciones de pago.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Solo lectura.</span></div><div class="next-toolbar-actions flex-wrap"><a class="btn btn-outline-secondary btn-sm" href="{{ route('index.invoices.pending') }}">Facturas y saldos</a></div></div>
@endsection
@section('content')
@include('credit._readonly', ['creditMode' => 'credits'])
@endsection
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection
@section('scripts')
<script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/credit/supplier-readonly.js') }}"></script>
@endsection
