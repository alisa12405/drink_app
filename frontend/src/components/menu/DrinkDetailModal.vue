<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import {
  X,
  Plus,
  Minus,
  Sparkles,
  Flame,
  ShoppingBag,
} from 'lucide-vue-next'
import {
  SUGAR_OPTIONS,
  ICE_OPTIONS,
  getCategoryEmoji,
} from '@/constants/drinkOptions'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  drink: {
    type: Object,
    default: null,
  },
  recommendation: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'add'])

const selectedSugar = ref('100')
const selectedIce = ref('normal_ice')
const note = ref('')
const quantity = ref(1)
const modalRef = ref(null)
let previousOverflow = ''

watch(
  () => props.isOpen,
  async (open) => {
    if (open) {
      previousOverflow = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      selectedSugar.value = '100'
      selectedIce.value = 'normal_ice'
      note.value = ''
      quantity.value = 1
      await nextTick()
      modalRef.value?.focus()
    } else {
      document.body.style.overflow = previousOverflow
    }
  },
)

onBeforeUnmount(() => {
  if (props.isOpen) {
    document.body.style.overflow = previousOverflow
  }
})

function onKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault()
    emit('close')
  }
}

function increaseQty() {
  quantity.value = Math.min(20, quantity.value + 1)
}

function decreaseQty() {
  quantity.value = Math.max(1, quantity.value - 1)
}

const totalPrice = computed(() => {
  if (!props.drink) return 0
  return Number(props.drink.price) * quantity.value
})

const temperatureLabel = computed(() => {
  if (!props.drink?.temperature_type) return null
  const map = {
    hot: 'Món nóng',
    cold: 'Món đá',
    both: 'Nóng & Đá',
  }
  return map[props.drink.temperature_type] || null
})

const isBestSeller = computed(() => {
  return Array.isArray(props.drink?.tags) && props.drink.tags.includes('best_seller')
})

function onAddToCart() {
  if (!props.drink) return
  emit('add', {
    drink: props.drink,
    options: {
      sugar_level: selectedSugar.value,
      ice_level: selectedIce.value,
      note: note.value.trim(),
      quantity: quantity.value,
    },
  })
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-bounce">
      <div
        v-if="isOpen && drink"
        class="fixed inset-0 z-[90] overflow-y-auto p-4 sm:p-6 flex items-center justify-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="drink-detail-title"
        @keydown="onKeydown"
      >
        <!-- Backdrop -->
        <div
          class="modal-backdrop fixed inset-0 bg-black/55 backdrop-blur-xs transition-opacity duration-300"
          aria-hidden="true"
          @click="$emit('close')"
        />

        <!-- Modal Dialog Card -->
        <div
          ref="modalRef"
          tabindex="-1"
          class="modal-card relative w-full max-w-lg bg-card rounded-3xl shadow-2xl border border-border overflow-hidden outline-hidden my-auto z-10 flex flex-col max-h-[92vh]"
        >
          <!-- Sticky Close Button -->
          <button
            type="button"
            class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-card/85 backdrop-blur-md border border-border text-foreground hover:bg-secondary flex items-center justify-center transition-all duration-200 shadow-sm cursor-pointer hover:scale-105 active:scale-90"
            aria-label="Đóng"
            @click="$emit('close')"
          >
            <X :size="18" />
          </button>

          <!-- Scrollable Content Area -->
          <div class="overflow-y-auto flex-1 p-5 sm:p-6">
            <!-- Header: Image Banner -->
            <div class="relative h-56 sm:h-64 rounded-2xl bg-gradient-to-b from-secondary/70 to-secondary flex items-center justify-center overflow-hidden p-4 mb-5 border border-border/60">
              <img
                v-if="drink.image_url"
                :src="drink.image_url"
                :alt="drink.name"
                class="w-full h-full object-contain drop-shadow-md"
              />
              <div v-else class="w-24 h-24 rounded-3xl bg-card flex items-center justify-center shadow-inner">
                <span class="text-6xl">{{ getCategoryEmoji(drink.category) }}</span>
              </div>

              <!-- Top Left Badges -->
              <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 items-center">
                <span
                  v-if="recommendation?.rank"
                  class="inline-flex items-center gap-1 bg-amber-500 text-white text-xs font-heading font-extrabold px-3 py-1 rounded-full shadow-md"
                >
                  <Sparkles :size="13" />
                  Top #{{ recommendation.rank }} Gợi ý
                </span>
                <span
                  v-if="isBestSeller"
                  class="inline-flex items-center gap-1 bg-gradient-to-r from-amber-600 to-primary text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-sm"
                >
                  <Flame :size="13" class="fill-white" />
                  Bán chạy
                </span>
              </div>

              <!-- Top Right Temperature Badge -->
              <div v-if="temperatureLabel" class="absolute bottom-3 right-3">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-card/90 backdrop-blur-xs border border-border text-foreground shadow-2xs">
                  {{ temperatureLabel }}
                </span>
              </div>
            </div>

            <!-- AI Recommendation Highlight Box (If opened from AI recommendation) -->
            <div
              v-if="recommendation?.explanation"
              class="mb-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-primary/10 to-amber-500/15 border border-amber-400/40 p-4 shadow-2xs"
            >
              <div class="flex items-center gap-2 mb-1.5">
                <div class="w-6 h-6 rounded-lg bg-primary text-white flex items-center justify-center shrink-0">
                  <Sparkles :size="13" />
                </div>
                <span class="text-xs font-bold text-primary uppercase tracking-wider">
                  Lý do AI gợi ý món này cho bạn
                </span>
              </div>
              <p class="text-sm text-foreground font-medium leading-relaxed pl-8">
                {{ recommendation.explanation }}
              </p>
            </div>

            <!-- Title & Basic Info -->
            <div class="flex items-start justify-between gap-4 mb-2">
              <div>
                <span class="text-xs font-bold uppercase tracking-wider text-primary">
                  {{ drink.category }}
                </span>
                <h2 id="drink-detail-title" class="font-heading font-extrabold text-xl sm:text-2xl text-foreground mt-0.5 leading-snug">
                  {{ drink.name }}
                </h2>
              </div>
              <div class="text-right shrink-0">
                <span class="font-heading font-extrabold text-2xl text-primary block leading-none">
                  {{ Number(drink.price).toLocaleString('vi-VN') }}đ
                </span>
                <span v-if="drink.calories" class="text-xs text-muted-foreground mt-1 block">
                  {{ drink.calories }} kcal
                </span>
              </div>
            </div>

            <!-- Full Description (Unclipped) -->
            <p class="text-sm text-muted-foreground leading-relaxed mt-2 pb-4 border-b border-border/80">
              {{ drink.description || 'Thức uống tươi ngon, được pha chế tỉ mỉ từ những nguyên liệu chọn lọc mang đến trải nghiệm hương vị trọn vẹn nhất.' }}
            </p>

            <!-- Customization Options -->
            <div class="mt-5 space-y-4">
              <!-- Sugar Level -->
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-2">
                  Lượng đường
                </label>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                  <button
                    v-for="opt in SUGAR_OPTIONS"
                    :key="opt.value"
                    type="button"
                    class="py-2 px-1 text-center rounded-xl text-xs font-semibold border transition-all duration-150 cursor-pointer"
                    :class="
                      selectedSugar === opt.value
                        ? 'bg-primary text-primary-foreground border-primary shadow-xs scale-102 font-bold'
                        : 'bg-background text-muted-foreground hover:bg-secondary hover:text-primary border-border'
                    "
                    @click="selectedSugar = opt.value"
                  >
                    {{ opt.value }}%
                  </button>
                </div>
              </div>

              <!-- Ice Level -->
              <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-foreground mb-2">
                  Lượng đá
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <button
                    v-for="opt in ICE_OPTIONS"
                    :key="opt.value"
                    type="button"
                    class="py-2 px-2 text-center rounded-xl text-xs font-semibold border transition-all duration-150 cursor-pointer"
                    :class="
                      selectedIce === opt.value
                        ? 'bg-primary text-primary-foreground border-primary shadow-xs scale-102 font-bold'
                        : 'bg-background text-muted-foreground hover:bg-secondary hover:text-primary border-border'
                    "
                    @click="selectedIce = opt.value"
                  >
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Special Note -->
              <div>
                <label for="drink-special-note" class="block text-xs font-bold uppercase tracking-wider text-foreground mb-1.5">
                  Ghi chú cho quán (tuỳ chọn)
                </label>
                <input
                  id="drink-special-note"
                  v-model="note"
                  type="text"
                  placeholder="Ví dụ: Ít ngọt, dùng ống hút to, uống liền..."
                  class="w-full h-10 px-3.5 rounded-xl border border-border bg-background text-xs text-foreground placeholder:text-muted-foreground/60 outline-hidden focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                />
              </div>
            </div>
          </div>

          <!-- Modal Footer: Quantity Stepper & Add to Cart Button -->
          <div class="p-4 sm:p-5 border-t border-border bg-muted/20 flex items-center justify-between gap-4 shrink-0">
            <!-- Quantity Stepper -->
            <div class="flex items-center rounded-xl border border-border bg-card p-1 shadow-2xs">
              <button
                type="button"
                class="w-8 h-8 rounded-lg text-muted-foreground hover:text-primary hover:bg-secondary flex items-center justify-center transition-colors disabled:opacity-40 cursor-pointer"
                :disabled="quantity <= 1"
                aria-label="Giảm số lượng"
                @click="decreaseQty"
              >
                <Minus :size="15" />
              </button>
              <span class="w-9 text-center font-bold text-sm text-foreground">
                {{ quantity }}
              </span>
              <button
                type="button"
                class="w-8 h-8 rounded-lg text-muted-foreground hover:text-primary hover:bg-secondary flex items-center justify-center transition-colors disabled:opacity-40 cursor-pointer"
                :disabled="quantity >= 20"
                aria-label="Tăng số lượng"
                @click="increaseQty"
              >
                <Plus :size="15" />
              </button>
            </div>

            <!-- Add to Cart CTA -->
            <button
              type="button"
              class="flex-1 h-11 px-5 rounded-xl bg-primary hover:bg-primary-hover active:scale-95 text-primary-foreground font-bold text-sm flex items-center justify-center gap-2 shadow-md shadow-primary/20 transition-all cursor-pointer"
              @click="onAddToCart"
            >
              <ShoppingBag :size="17" />
              <span>Thêm vào giỏ</span>
              <span class="ml-1 text-xs font-extrabold bg-black/15 px-2 py-0.5 rounded-lg">
                {{ totalPrice.toLocaleString('vi-VN') }}đ
              </span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-bounce-enter-active,
.modal-bounce-leave-active {
  transition: opacity 0.24s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-bounce-enter-active .modal-card,
.modal-bounce-leave-active .modal-card {
  transition: transform 0.24s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.24s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-bounce-enter-from .modal-backdrop,
.modal-bounce-leave-to .modal-backdrop {
  opacity: 0;
}

.modal-bounce-enter-from .modal-card {
  opacity: 0;
  transform: scale(0.94) translateY(12px);
}

.modal-bounce-leave-to .modal-card {
  opacity: 0;
  transform: scale(0.96) translateY(8px);
}
</style>
