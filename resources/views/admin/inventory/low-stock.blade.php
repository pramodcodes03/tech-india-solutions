<x-layout.admin>
    <div>
        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Low Stock Alerts</h5>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Inventory
            </a>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Product Code</th>
                            <th class="px-4 py-2">Product Name</th>
                            <th class="px-4 py-2">Warehouse</th>
                            <th class="px-4 py-2">Current Stock</th>
                            <th class="px-4 py-2">Reorder Level</th>
                            <th class="px-4 py-2">Deficit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockItems as $item)
                            <tr class="bg-danger/10 dark:bg-danger/5">
                                <td class="px-4 py-2 font-semibold">{{ $item->product->code ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $item->product->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $item->warehouse->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span class="font-semibold text-danger">{{ $item->quantity }}</span>
                                </td>
                                <td class="px-4 py-2">{{ $item->product->reorder_level ?? 0 }}</td>
                                <td class="px-4 py-2">
                                    <span class="font-semibold text-danger">
                                        {{ ($item->product->reorder_level ?? 0) - $item->quantity }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No low stock items found. All products are well stocked.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($lowStockItems, 'hasPages') && $lowStockItems->hasPages())
                <div class="px-5 py-3">
                    {{ $lowStockItems->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
