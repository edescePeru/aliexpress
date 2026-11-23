@if (!empty($readOnly))
    @include('cashBox.summary_movements')
@else
    @include('cashBox.admin_readonly')
@endif
