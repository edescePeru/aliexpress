@php
    $selectedBank =
        old(
            'bank_id',
            $account ? $account->bank_id : ''
        );

    $selectedCurrency =
        old(
            'currency',
            $account ? $account->currency : ''
        );

    $isDefault =
        old(
            'is_default',
            $account ? $account->is_default : false
        );
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="bank_id">
                Banco
            </label>

            <select
                    name="bank_id"
                    id="bank_id"
                    class="form-control select2-bank @error('bank_id') is-invalid @enderror"
                    style="width:100%;"
            >
                <option value="">
                    Seleccione un banco
                </option>

                @foreach($banks as $bank)
                    <option
                            value="{{ $bank->id }}"
                            data-image="{{ $bank->image ? asset('images/bank/' . $bank->image) : '' }}"
                            {{ (string) $selectedBank === (string) $bank->id ? 'selected' : '' }}
                    >
                        {{ $bank->name }}
                    </option>
                @endforeach
            </select>

            @error('bank_id')
            <span class="invalid-feedback d-block">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="currency">
                Moneda
            </label>

            <select
                    name="currency"
                    id="currency"
                    class="form-control @error('currency') is-invalid @enderror"
            >
                <option value="">
                    Seleccione una moneda
                </option>

                <option
                        value="PEN"
                        {{ $selectedCurrency === 'PEN' ? 'selected' : '' }}
                >
                    Soles (PEN)
                </option>

                <option
                        value="USD"
                        {{ $selectedCurrency === 'USD' ? 'selected' : '' }}
                >
                    Dólares (USD)
                </option>
            </select>

            @error('currency')
            <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="title">
                Título
            </label>

            <input
                    type="text"
                    name="title"
                    id="title"
                    class="form-control @error('title') is-invalid @enderror"
                    value="{{ old('title', $account ? $account->title : '') }}"
                    placeholder="Ej. Cuenta corriente BCP"
            >

            @error('title')
            <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="account_holder">
                Titular
            </label>

            <input
                    type="text"
                    name="account_holder"
                    id="account_holder"
                    class="form-control @error('account_holder') is-invalid @enderror"
                    value="{{ old('account_holder', $account ? $account->account_holder : '') }}"
                    placeholder="Nombre o razón social"
            >

            @error('account_holder')
            <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="account_number">
                Número de cuenta
            </label>

            <input
                    type="text"
                    name="account_number"
                    id="account_number"
                    class="form-control @error('account_number') is-invalid @enderror"
                    value="{{ old('account_number', $account ? $account->account_number : '') }}"
                    placeholder="Número de cuenta"
            >

            @error('account_number')
            <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="cci">
                CCI
            </label>

            <input
                    type="text"
                    name="cci"
                    id="cci"
                    class="form-control @error('cci') is-invalid @enderror"
                    value="{{ old('cci', $account ? $account->cci : '') }}"
                    placeholder="Código de cuenta interbancario"
            >

            @error('cci')
            <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-0">
    <div class="custom-control custom-switch">
        <input
                type="checkbox"
                class="custom-control-input"
                id="is_default"
                name="is_default"
                value="1"
                {{ $isDefault ? 'checked' : '' }}
        >

        <label
                class="custom-control-label"
                for="is_default"
        >
            Cuenta predeterminada
        </label>
    </div>

    <small class="form-text text-muted">
        La cuenta predeterminada se mostrará primero en documentos y comprobantes.
    </small>
</div>