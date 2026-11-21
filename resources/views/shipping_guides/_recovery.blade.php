@if(auth()->user()->can('list_referralGuide') && auth()->user()->can('create_referralGuide') && auth()->user()->can('edit_referralGuide'))
    @php($recoveryPreview = app(\App\Services\ShippingGuideRecovery::class)->preview($guide))
    @if(!$guide->identity_conflict && $guide->status === 'SENT')
        <section class="card mb-3" aria-label="Recuperación de envío">
            <div class="card-header">Recuperar envío de {{ $guide->serie }}-{{ $guide->numero }}</div>
            <div class="card-body">
                @if($recoveryPreview['enabled'])
                    <p>Primero se consultará a Nubefact. Si responde para ese número, se detendrá para comparar la guía remota con la local, sin importar datos ni reenviarla.
                        Solo si responde «Documento no existe» y marcas la autorización opcional se intentará enviar una vez esta misma guía,
                        con el número y los datos guardados. No crea otra guía.</p>
                    <p>Si autorizas el envío y el número no existe, se archivarán los datos anteriores y se normalizarán solo las placas mostradas. No se cambiará el número, destinatario ni productos.</p>
                    <ul>
                        @foreach($recoveryPreview['plates'] as $plate)
                            <li>{{ $plate['from'] }} → {{ $plate['to'] }}</li>
                        @endforeach
                    </ul>
                    <form id="frmGreRecovery" action="{{ route('shipping_guides.recover', ['guide' => $guide->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="version" value="{{ $recoveryPreview['version'] }}">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirm_account" value="1" id="greRecoveryAccount" required>
                            <label class="form-check-label" for="greRecoveryAccount">Verifiqué que la conexión configurada corresponde a la cuenta emisora de esta guía.</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_send" value="1" id="greRecoverySend">
                            <label class="form-check-label" for="greRecoverySend">Opcional: revisé los datos y autorizo normalizar únicamente estas placas y enviar esta misma guía una vez si Nubefact confirma que no existe. Sin marcar, solo se comprueba.</label>
                        </div>
                        <button type="submit" id="btnGreRecovery" class="btn btn-warning">Comprobar guía y aplicar mi selección</button>
                    </form>
                @else
                    <p class="mb-0">{{ $recoveryPreview['reason'] }}</p>
                @endif
                <div id="greRecoveryResult" class="mt-3" role="status" aria-live="polite"></div>
            </div>
        </section>
    @endif
@endif
