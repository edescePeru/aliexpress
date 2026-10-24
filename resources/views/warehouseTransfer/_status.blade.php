@php($colors = ['requested'=>'info', 'prepared'=>'primary', 'dispatched'=>'warning', 'partially_received'=>'warning', 'received'=>'success', 'cancelled'=>'secondary'])
<span class="badge badge-{{ $colors[$status] ?? 'secondary' }}">{{ $labels[$status] ?? $status }}</span>
