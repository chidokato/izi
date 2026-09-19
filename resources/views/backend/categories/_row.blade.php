<tr>
    <td>
        <div class="d-flex align-items-center">
            <span class="text-muted me-2">{{ str_repeat('— ', $level) }}</span>
            <div>
                <div class="fw-medium">{{ $category->name }}</div>
                @if ($category->description)
                    <div class="text-muted small">{{ $category->description }}</div>
                @endif
            </div>
        </div>
    </td>
    <td>{{ $category->slug }}</td>
    <td>
        <input
            type="number"
            min="0"
            class="form-control form-control-sm sort-order-input"
            value="{{ $category->sort_order }}"
            data-update-sort-order
            data-url="{{ route('backend.categories.update-sort-order', $category) }}"
            data-initial-value="{{ $category->sort_order }}"
        >
    </td>
    <td>
        <div class="d-inline-flex align-items-center gap-2">
            <button
                type="button"
                class="status-toggle {{ $category->is_active ? 'is-active' : 'is-inactive' }}"
                data-toggle-status
                data-url="{{ route('backend.categories.toggle-status', $category) }}"
                aria-pressed="{{ $category->is_active ? 'true' : 'false' }}"
            ></button>
            <span class="status-toggle-label {{ $category->is_active ? 'text-success' : 'text-danger' }}" data-status-label>
                {{ $category->is_active ? 'Hien thi' : 'An' }}
            </span>
        </div>
    </td>
    <td class="text-end">
        <a href="{{ route('backend.categories.edit', $category) }}" class="btn btn-sm btn-soft-warning">Sua</a>
        <form action="{{ route('backend.categories.destroy', $category) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-message="Ban co chac muon xoa category nay?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-soft-danger">Xoa</button>
        </form>
    </td>
</tr>

@foreach ($category->children_tree as $child)
    @include('backend.categories._row', ['category' => $child, 'level' => $level + 1])
@endforeach
