<div class="flex items-center gap-2">
    @if(!empty($logoPath))
        <img
            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) }}"
            alt=""
            class="h-9 w-9 shrink-0 rounded-full border border-gray-200 object-cover dark:border-gray-600"
        >
    @else
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-xs font-bold text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-amber-400">
            {{ $fallback }}
        </span>
    @endif
    <span class="truncate font-medium">{{ $name }}</span>
</div>
