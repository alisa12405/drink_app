<script setup>
import { Plus, Flame } from 'lucide-vue-next'
import { getCategoryEmoji } from '@/constants/drinkOptions'

const props = defineProps({
  drink: {
    type: Object,
    required: true,
  },
})

defineEmits(['add', 'select'])

const isBestSeller = (tags) => Array.isArray(tags) && tags.includes('best_seller')

const temperatureConfig = {
  hot: { label: 'Nóng', class: 'bg-rose-50 text-rose-700 border-rose-200' },
  cold: { label: 'Đá', class: 'bg-sky-50 text-sky-700 border-sky-200' },
  both: { label: 'Nóng/Đá', class: 'bg-amber-50 text-amber-700 border-amber-200' },
}

const tempInfo = props.drink.temperature_type
  ? temperatureConfig[props.drink.temperature_type] || null
  : null
</script>

<template>
  <div
    class="bg-card rounded-2xl shadow-sm hover:shadow-xl border border-border hover:border-primary/40 overflow-hidden group flex flex-col transition-all duration-300 ease-out hover:-translate-y-1.5 motion-reduce:transform-none cursor-pointer focus-visible:outline-2 focus-visible:outline-primary"
    tabindex="0"
    role="button"
    :aria-label="`Xem chi tiết món ${drink.name}`"
    @click="$emit('select', drink)"
    @keydown.enter="$emit('select', drink)"
  >
    <!-- Drink Image Container -->
    <div
      class="relative h-48 sm:h-52 xl:h-56 shrink-0 overflow-hidden bg-gradient-to-b from-secondary/60 to-secondary flex items-center justify-center p-3"
    >
      <img
        v-if="drink.image_url"
        :src="drink.image_url"
        :alt="drink.name"
        class="w-full h-full object-contain drop-shadow-sm group-hover:scale-108 transition-transform duration-500 ease-out"
        loading="lazy"
        decoding="async"
      />
      <div v-else class="w-24 h-24 rounded-2xl bg-white/80 border border-border/60 flex items-center justify-center shadow-inner">
        <span class="text-5xl transform group-hover:scale-110 transition-transform duration-300">
          {{ getCategoryEmoji(drink.category) }}
        </span>
      </div>

      <!-- Top Badges -->
      <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
        <span
          v-if="isBestSeller(drink.tags)"
          class="inline-flex items-center gap-1 bg-gradient-to-r from-amber-600 to-primary text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow-sm"
        >
          <Flame :size="12" class="fill-white" />
          Bán chạy
        </span>
      </div>

      <div class="absolute top-3 right-3 flex items-center gap-1.5">
        <span
          v-if="tempInfo"
          class="text-[10px] font-semibold px-2 py-0.5 rounded-full border shadow-2xs backdrop-blur-xs"
          :class="tempInfo.class"
        >
          {{ tempInfo.label }}
        </span>
      </div>
    </div>

    <!-- Drink Information -->
    <div class="p-5 flex flex-col flex-1">
      <div class="flex items-center justify-between gap-2 mb-1.5">
        <span class="text-[11px] font-semibold tracking-wider uppercase text-primary/80">
          {{ drink.category }}
        </span>
        <span v-if="drink.calories" class="text-[11px] font-medium text-muted-foreground/80">
          {{ drink.calories }} kcal
        </span>
      </div>

      <h3 class="font-heading font-bold text-foreground text-base sm:text-lg leading-snug group-hover:text-primary transition-colors line-clamp-1">
        {{ drink.name }}
      </h3>

      <p class="text-xs sm:text-sm text-muted-foreground mt-2 leading-relaxed line-clamp-2 flex-1">
        {{ drink.description || 'Hương vị thơm ngon đặc trưng, được pha chế tỉ mỉ từ nguyên liệu tuyển chọn.' }}
      </p>

      <!-- Price & CTA -->
      <div class="flex items-center justify-between mt-4 pt-3 border-t border-border/50">
        <div>
          <span class="text-[11px] text-muted-foreground block leading-none mb-1">Giá từ</span>
          <span class="text-primary font-heading font-extrabold text-lg sm:text-xl leading-none">
            {{ Number(drink.price).toLocaleString('vi-VN') }}<span class="text-xs font-semibold ml-0.5">đ</span>
          </span>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-hover active:scale-95 text-primary-foreground font-semibold text-xs rounded-xl shadow-sm shadow-primary/20 transition-all duration-200 cursor-pointer"
          aria-label="Thêm vào giỏ"
          @click.stop="$emit('add', drink)"
        >
          <Plus :size="15" />
          <span>Chọn món</span>
        </button>
      </div>
    </div>
  </div>
</template>
