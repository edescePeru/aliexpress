@extends('layouts.appAdmin2')
@section('title', 'Sección no disponible')
@section('content')
<div class="alert alert-info" role="status">{{ $message }}</div>
<a href="{{ route('dashboard.principal') }}" class="btn btn-outline-secondary">Volver al inicio</a>
@endsection
