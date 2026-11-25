@php
    $documents = [['name' => $entry->image, 'route' => 'entry.document.main', 'label' => 'Ver adjunto']];
    if (!empty($includeObservation)) $documents[] = ['name' => $entry->imageOb, 'route' => 'entry.document.observation', 'label' => 'Ver observación'];
    $documents = array_filter($documents, function ($document) {
        $name = $document['name'];
        return is_string($name) && $name !== '' && !in_array(strtolower($name), ['no_image.png', 'no_image.jpg'])
            && !preg_match('/[\x00-\x1f\x7f\\\\\/]/', $name) && preg_match('/\.(pdf|jpe?g|png|gif|webp)$/i', $name);
    });
@endphp
@can($documentPermission)
    <div class="next-toolbar-actions flex-wrap">
        @forelse($documents as $document)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route($document['route'], ['filename' => $document['name']]) }}" target="_blank" rel="noopener" data-entry-document>
                <i class="fas {{ preg_match('/\.pdf$/i', $document['name']) ? 'fa-file-pdf' : 'fa-image' }} next-row-action-item-icon" aria-hidden="true"></i>{{ $document['label'] }}
            </a>
        @empty
            <span class="text-muted">Sin adjuntos disponibles.</span>
        @endforelse
    </div>
@endcan
