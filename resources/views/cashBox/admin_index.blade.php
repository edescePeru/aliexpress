@if (!empty($readOnly))
    @include('cashBox.summary_movements')
@else
    @include('cashBox._admin_legacy')
@endif
