<script setup>
import { getCategoryEmoji } from '@/constants/drinkOptions'
import { LayoutGrid } from 'lucide-vue-next'

defineProps({
  categories: {
    type: Array,
    required: true, // [{ value, label }]
  },
  modelValue: {
    type: String,
    required: true,
  },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <aside class="w-full lg:w-56 shrink-0">
    <!-- Desktop Header -->
    <div class="hidden lg:flex items-center gap-2 px-3 mb-3">
      <LayoutGrid :size="16" class="text-primary" />
      <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
        Danh mục thực đơn
      </span>
    </div>

    <!-- Category Pills / List -->
    <nav aria-label="Danh mục món uống">
      <ul
        class="flex lg:flex-col gap-1.5 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0 no-scrollbar scroll-smooth"
      >
        <li v-for="cat in categories" :key="cat.value" class="shrink-0">
          <button
            type="button"
            class="relative w-full flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 whitespace-nowrap text-left border group overflow-hidden active:scale-95"
            :class="
              modelValue === cat.value
                ? 'bg-primary text-primary-foreground border-primary shadow-sm font-semibold scale-[1.01]'
                : 'bg-card lg:bg-transparent text-muted-foreground hover:bg-secondary hover:text-primary hover:border-accent border-border lg:border-transparent'
            "
            :aria-current="modelValue === cat.value ? 'true' : undefined"
            @click="$emit('update:modelValue', cat.value)"
          >
            <!-- Active Glow Indicator for Desktop -->
            <span
              v-if="modelValue === cat.value"
              class="hidden lg:block absolute left-0 top-2 bottom-2 w-1 bg-white/70 rounded-r-full"
            />
            <span class="text-base shrink-0 group-hover:scale-110 transition-transform">
              {{ cat.value === 'all' ? '✨' : getCategoryEmoji(cat.value) }}
            </span>
            <span class="flex-1 truncate">{{ cat.label }}</span>
          </button>
        </li>
      </ul>
    </nav>
  </aside>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
