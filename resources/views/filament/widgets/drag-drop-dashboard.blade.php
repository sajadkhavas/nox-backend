<x-filament-widgets::widget>
<div
    x-data="{
        widgets: @js($this->widgetOrder),
        dragging: null,
        dragOver: null,
        startDrag(id) { this.dragging = id; },
        onDragOver(id) { this.dragOver = id; },
        onDrop(id) {
            if (this.dragging === id) return;
            const from = this.widgets.indexOf(this.dragging);
            const to = this.widgets.indexOf(id);
            this.widgets.splice(from, 1);
            this.widgets.splice(to, 0, this.dragging);
            this.dragging = null;
            this.dragOver = null;
            $wire.saveOrder(this.widgets);
        }
    }"
    class="space-y-4"
>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">داشبورد سفارشی</h2>
        <span class="text-xs text-gray-400">ویجت‌ها را بکشید و جابجا کنید</span>
    </div>

    {{-- Stats Row --}}
    <template x-if="widgets.includes('stats')">
        <div
            draggable="true"
            @dragstart="startDrag('stats')"
            @dragover.prevent="onDragOver('stats')"
            @drop="onDrop('stats')"
            :class="dragOver === 'stats' ? 'ring-2 ring-primary-500' : ''"
            class="cursor-grab active:cursor-grabbing rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4"
        >
            <div class="flex items-center gap-2 mb-3">
                <span>📊</span>
                <h3 class="font-semibold text-gray-700 dark:text-gray-300">آمار کلی</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach($this->getStats() as $stat)
                <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-2xl mb-1">{{ $stat['icon'] }}</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</div>
                    <div class="text-xs text-gray-500">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </template>

    {{-- Reviews --}}
    <template x-if="widgets.includes('reviews')">
        <div
            draggable="true"
            @dragstart="startDrag('reviews')"
            @dragover.prevent="onDragOver('reviews')"
            @drop="onDrop('reviews')"
            :class="dragOver === 'reviews' ? 'ring-2 ring-primary-500' : ''"
            class="cursor-grab active:cursor-grabbing rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4"
        >
            <div class="flex items-center gap-2 mb-3">
                <span>⭐</span>
                <h3 class="font-semibold text-gray-700 dark:text-gray-300">آخرین نظرات</h3>
            </div>
            <div class="space-y-2">
                @forelse($this->getRecentReviews() as $review)
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div>
                        <span class="font-medium text-sm text-gray-900 dark:text-white">{{ $review['name'] }}</span>
                        <span class="text-xs text-gray-500 mr-2">{{ $review['title'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-yellow-500">{{ str_repeat('⭐', $review['rating']) }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $review['status'] === 'approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $review['status'] === 'approved' ? 'تایید' : 'در انتظار' }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-2">نظری ثبت نشده</p>
                @endforelse
            </div>
        </div>
    </template>

    {{-- Newsletter --}}
    <template x-if="widgets.includes('newsletter')">
        <div
            draggable="true"
            @dragstart="startDrag('newsletter')"
            @dragover.prevent="onDragOver('newsletter')"
            @drop="onDrop('newsletter')"
            :class="dragOver === 'newsletter' ? 'ring-2 ring-primary-500' : ''"
            class="cursor-grab active:cursor-grabbing rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4"
        >
            <div class="flex items-center gap-2 mb-3">
                <span>📧</span>
                <h3 class="font-semibold text-gray-700 dark:text-gray-300">آخرین مشترکین</h3>
            </div>
            <div class="space-y-2">
                @forelse($this->getRecentSubscribers() as $sub)
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $sub['email'] }}</span>
                    <span class="text-xs text-gray-400">{{ $sub['date'] }}</span>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-2">مشترکی ثبت نشده</p>
                @endforelse
            </div>
        </div>
    </template>

</div>
</x-filament-widgets::widget>
