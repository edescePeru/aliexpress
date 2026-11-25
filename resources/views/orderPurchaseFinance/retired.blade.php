@extends('layouts.appAdmin2')
@section('title', 'Sección retirada')
@section('content')
<div class="alert alert-info" role="status">{{ $message }}</div>
@can('list_orderPurchaseNormal')
<a class="btn btn-outline-primary" href="{{ route('order.purchase.normal.indexV2') }}">Ir a Órdenes de compra</a>
@endcan
@endsection
