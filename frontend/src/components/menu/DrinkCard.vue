<script setup>
import { Plus } from 'lucide-vue-next'
import { getCategoryEmoji } from '@/constants/drinkOptions'

const props = defineProps({
  drink: {
    type: Object,
    required: true,
  },
})

defineEmits(['add'])

const isBestSeller = (tags) => Array.isArray(tags) && tags.includes('best_seller')
</script>

<template>
  <div
    class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden hover:shadow-md transition-shadow group flex flex-col"
  >
    <div class="relative h-32 shrink-0 overflow-hidden bg-secondary flex items-center justify-center">
      <img
        v-if="drink.image_url"
        :src="drink.image_url"
        :alt="drink.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      <span v-else class="text-5xl">{{ getCategoryEmoji(drink.category) }}</span>

      <span
        v-if="isBestSeller(drink.tags)"
        class="absolute top-2 left-2 bg-primary text-primary-foreground text-[10px] font-bold px-2 py-0.5 rounded-full"
      >
        Phổ biến
      </span>
    </div>

    <div class="p-4 flex flex-col flex-1">
      <h3 class="font-semibold text-foreground text-sm leading-tight">{{ drink.name }}</h3>
      <p class="text-xs text-muted-foreground mt-1 leading-relaxed line-clamp-2 flex-1">
        {{ drink.description }}
      </p>
      <div class="flex items-center justify-between mt-3">
        <span class="text-primary font-bold text-sm">
          {{ Number(drink.price).toLocaleString('vi-VN') }}đ
        </span>
        <button
          type="button"
          class="w-7 h-7 bg-primary hover:bg-primary-hover text-primary-foreground rounded-full flex items-center justify-center shadow-sm transition-colors shrink-0"
          @click="$emit('add', drink)"
        >
          <Plus :size="14" />
        </button>
      </div>
    </div>
  </div>
</template>
