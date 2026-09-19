<tr>
    <td>
        <div class="d-flex align-items-center">
            <span class="text-muted me-2">{{ str_repeat('-- ', $level) }}</span>
            <div class="fw-medium">{{ $menu->name }}</div>
        </div>
    </td>
    <td>{{ $menu->slug }}</td>
    <td>{{ $menu->target }}</td>
    <td>
        <input
            type="number"
            min="0"
            class="form-control form-control-sm sort-order-input"
            value="{{ $menu->sort_order }}"
            data-update-sort-order
            data-url="{{ route('backend.menus.update-sort-order', $menu) }}"
            data-initial-value="{{ $menu->sort_order }}"
        >
    </td>
    <td>
        <div class="d-inline-flex align-items-center gap-2">
            <button
                type="button"
                class="status-toggle {{ $menu->is_active ? 'is-active' : 'is-inactive' }}"
                data-toggle-status
                data-url="{{ route('backend.menus.toggle-status', $menu) }}"
                aria-pressed="{{ $menu->is_active ? 'true' : 'false' }}"
            ></button>
            <span class="status-toggle-label {{ $menu->is_active ? 'text-success' : 'text-danger' }}" data-status-label>
                {{ $menu->is_active ? 'Hien thi' : 'An' }}
            </span>
        </div>
    </td>
    <td class="text-end">
        <a href="{{ route('backend.menus.edit', $menu) }}" class="btn btn-sm btn-soft-warning">Sua</a>
        <form action="{{ route('backend.menus.destroy', $menu) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-message="Ban co chac muon xoa menu nay?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-soft-danger">Xoa</button>
        </form>
    </td>
</tr>

@foreach ($menu->children_tree as $child)
    @include('backend.menus._row', ['menu' => $child, 'level' => $level + 1])
@endforeach
