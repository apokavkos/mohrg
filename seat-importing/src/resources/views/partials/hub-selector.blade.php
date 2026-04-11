<div class="d-flex align-items-center flex-wrap gap-2">

    {{-- Hub dropdown --}}
    <div class="mr-2">
        <select id="hub-selector" class="form-control form-control-sm" style="min-width: 220px;">
            <option value="">— Select Hub —</option>
            @foreach ($hubs as $hub)
                <option value="{{ $hub->id }}"
                    {{ isset($selectedHub) && $selectedHub && $selectedHub->id === $hub->id ? 'selected' : '' }}>
                    {{ $hub->name }}
                    @if (! $hub->is_active)
                        (inactive)
                    @endif
                </option>
            @endforeach
        </select>
    </div>

    {{-- Quick stats for the selected hub --}}
    @if (isset($selectedHub) && $selectedHub)
        <small class="text-muted">
            ISK/m³:
            <strong>{{ number_format($selectedHub->effectiveIskPerM3(), 0) }}</strong>
        </small>
    @endif

    {{-- Manage button (requires permission) --}}
    @can('seat-importing.manage')
        <a href="{{ route('seat-importing.settings') }}" class="btn btn-sm btn-outline-secondary ml-auto">
            <i class="fas fa-cog"></i> Manage Hubs
        </a>
    @endcan

    {{-- Trigger import (requires permission) --}}
    @can('seat-importing.import')
        @if (isset($selectedHub) && $selectedHub)
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-trigger-import"
                data-hub-id="{{ $selectedHub->id }}">
                <i class="fas fa-cloud-download-alt"></i> Run Import
            </button>
        @endif
    @endcan

</div>

@can('seat-importing.import')
<script>
(function ($) {
    $('#btn-trigger-import').on('click', function () {
        var $btn   = $(this);
        var hubId  = $btn.data('hub-id');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Dispatching…');

        $.post('{{ route('seat-importing.import.run') }}', {
            hub_id: hubId,
            _token: '{{ csrf_token() }}'
        })
        .done(function () {
            $btn.html('<i class="fas fa-check text-success"></i> Dispatched');
            setTimeout(function () {
                $btn.prop('disabled', false).html('<i class="fas fa-cloud-download-alt"></i> Run Import');
            }, 3000);
        })
        .fail(function (xhr) {
            $btn.prop('disabled', false).html('<i class="fas fa-cloud-download-alt"></i> Run Import');
            alert('Import dispatch failed: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText));
        });
    });
}(jQuery));
</script>
@endcan
