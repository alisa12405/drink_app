<script setup>
import { getCategoryEmoji } from '@/constants/drinkOptions'

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
  <aside class="w-full lg:w-44 shrink-0">
    <ul class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible pb-1 lg:pb-0">
      <li v-for="cat in categories" :key="cat.value" class="shrink-0">
        <button
          type="button"
          class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap border"
          :class="
            modelValue === cat.value
              ? 'bg-secondary text-primary border-accent'
              : 'text-muted-foreground hover:bg-muted hover:text-foreground border-transparent'
          "
          @click="$emit('update:modelValue', cat.value)"
        >
          <span class="text-base">{{ cat.value === 'all' ? '🔥' : getCategoryEmoji(cat.value) }}</span>
          {{ cat.label }}
        </button>
      </li>
    </ul>
  </aside>
</template>
