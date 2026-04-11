<div class="d-flex align-items-center flex-wrap gap-2">
    <div class="mr-2">
        <select id="hub-selector" class="form-control form-control-sm" style="min-width: 220px;">
            <option value="">— Select Hub —</option>
            @foreach ($hubs as $hub)
                <option value="{{ $hub->id }}" {{ isset($selectedHub) && $selectedHub && $selectedHub->id === $hub->id ? 'selected' : '' }}>
                    {{ $hub->name }} {{ !$hub->is_active ? '(inactive)' : '' }}
                </option>
            @endforeach
            @can('market.settings')
                <option value="NEW_HUB" class="text-primary font-weight-bold">+ Add New Hub...</option>
            @endcan
        </select>
    </div>

    @if (isset($selectedHub) && $selectedHub)
        <small class="text-muted mr-2">ISK/m³: <strong>{{ number_format($selectedHub->effectiveIskPerM3(), 0) }}</strong></small>
    @endif

    @can('market.settings')
        <a href="{{ route('seat-importing.settings') }}" class="btn btn-sm btn-outline-secondary ml-auto">
            <i class="fas fa-cog"></i> Manage
        </a>
    @endcan

    @can('market.import')
        @if (isset($selectedHub) && $selectedHub)
            <button type="button" class="btn btn-sm btn-outline-primary ml-1" id="btn-trigger-import" data-hub-id="{{ $selectedHub->id }}">
                <i class="fas fa-cloud-download-alt"></i> Run Import
            </button>
        @endif
    @endcan
</div>

<script>
$(function() {
    $('#hub-selector').on('change', function () {
        var val = $(this).val();
        if (val === 'NEW_HUB') {
            $('#add-hub-modal').modal('show');
            $(this).val('{{ $selectedHub->id ?? "" }}');
        } else if (val) {
            window.location.href = '{{ route("seat-importing.hub.show", ["hub" => "__HUB__"]) }}'.replace('__HUB__', val);
        }
    });

    $('#btn-trigger-import').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Dispatching...');
        $.post('{{ route("seat-importing.import.run") }}', { hub_id: $btn.data('hub-id'), _token: '{{ csrf_token() }}' })
            .done(function() { $btn.html('<i class="fas fa-check text-success"></i> Dispatched'); location.reload(); })
            .fail(function(xhr) { alert('Failed: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Error')); $btn.prop('disabled', false).html('<i class="fas fa-cloud-download-alt"></i> Run Import'); });
    });
});
</script>
