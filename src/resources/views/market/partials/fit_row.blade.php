<tr class="fit-row" data-name="{{ strtolower($fit->name) }}" data-label="{{ strtolower($fit->label) }}">
    <td><input type="checkbox" class="restock-check" value="{{ $fit->id }}"></td>
    <td>
        <span class="expand-row" style="cursor: pointer;" data-target="#fit-content-{{ $fit->id }}">
            <i class="fas fa-chevron-right mr-2 transition-icon"></i>
            <strong>{{ $fit->name }}</strong>
        </span>
        <i class="fas fa-pen edit-name-icon" onclick='editSavedFit(@json($fit))' title="Edit Fit Details"></i>
    </td>
    <td>
        @if($fit->reference_url)
            <a href="{{ $fit->reference_url }}" target="_blank" class="badge badge-info">
                <i class="fas fa-external-link-alt"></i> {{ $fit->label ?: 'Source' }}
            </a>
        @elseif($fit->label)
            <span class="badge badge-info">{{ $fit->label }}</span>
        @else
            -
        @endif
    </td>
    <td class="text-right">
        <div class="btn-group">
            <form action="{{ route('seat-assets::market.fittings') }}" method="POST" style="display:inline;">
                @csrf <input type="hidden" name="fit_text" value="{{ $fit->fit_text }}">
                <button type="submit" class="btn btn-xs btn-info">Load</button>
            </form>
            <form action="{{ route('seat-assets::market.fittings.delete', $fit->id) }}" method="POST" onsubmit="return confirm('Delete?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
<tr id="fit-content-{{ $fit->id }}" class="d-none bg-light">
    <td colspan="4">
        <pre class="compact-pre border bg-white" style="max-height: 150px; overflow-y: auto;">{{ $fit->fit_text }}</pre>
    </td>
</tr>
