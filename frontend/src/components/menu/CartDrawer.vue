<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import {
  X,
  ShoppingCart,
  Minus,
  Plus,
  Trash2,
  ArrowRight,
  Sparkles,
  ShoppingBag,
} from 'lucide-vue-next'
import DrinkThumbnail from '@/components/menu/DrinkThumbnail.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  cart: {
    type: Array,
    required: true,
  },
  cartTotal: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close', 'increase', 'decrease', 'remove', 'checkout'])

const drawerRef = ref(null)
let previousOverflow = ''

function onKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault()
    emit('close')
  }
}

watch(
  () => props.isOpen,
  async (open) => {
    if (open) {
      previousOverflow = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      await nextTick()
      drawerRef.value?.focus()
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
</script>

<template>
  <Teleport to="body">
    <Transition name="cart-slide">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[80] overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-drawer-title"
        @keydown="onKeydown"
      >
        <!-- Backdrop with blur -->
        <div
          class="cart-backdrop absolute inset-0 bg-black/45 backdrop-blur-xs transition-opacity duration-300"
          aria-hidden="true"
          @click="$emit('close')"
        />

        <!-- Drawer Panel -->
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <aside
            ref="drawerRef"
            tabindex="-1"
            class="cart-panel w-screen max-w-md bg-card shadow-2xl flex flex-col border-l border-border outline-hidden transform transition-transform duration-300 ease-out"
          >
          <!-- Drawer Header -->
          <div class="px-6 py-5 border-b border-border flex items-center justify-between bg-muted/20">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <ShoppingBag :size="18" />
              </div>
              <div>
                <h2 id="cart-drawer-title" class="font-heading font-bold text-lg text-foreground">
                  Giỏ hàng của bạn
                </h2>
                <p class="text-xs text-muted-foreground mt-0.5">
                  {{ cart.length }} món đang chọn
                </p>
              </div>
            </div>

            <button
              type="button"
              class="w-9 h-9 rounded-xl border border-border text-muted-foreground hover:bg-secondary hover:text-foreground flex items-center justify-center transition-colors"
              aria-label="Đóng giỏ hàng"
              @click="$emit('close')"
            >
              <X :size="18" />
            </button>
          </div>

          <!-- Empty State -->
          <div
            v-if="cart.length === 0"
            class="flex-1 flex flex-col items-center justify-center p-8 text-center"
          >
            <div class="w-20 h-20 rounded-3xl bg-secondary flex items-center justify-center text-primary/40 mb-4">
              <ShoppingCart :size="40" />
            </div>
            <h3 class="font-heading font-bold text-foreground text-lg">Giỏ hàng trống</h3>
            <p class="text-sm text-muted-foreground max-w-xs mt-1.5 leading-relaxed">
              Bạn chưa chọn món nào. Khám phá menu và thêm đồ uống bạn yêu thích nhé!
            </p>
            <button
              type="button"
              class="mt-6 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-primary-foreground font-semibold text-xs transition-colors shadow-sm"
              @click="$emit('close')"
            >
              Tiếp tục xem menu
            </button>
          </div>

          <!-- Items List -->
          <div v-else class="flex-1 overflow-y-auto px-6 py-4 flex flex-col divide-y divide-border/60">
            <div
              v-for="(item, index) in cart"
              :key="index"
              class="py-3.5 flex items-center gap-3.5 group"
            >
              <div class="shrink-0 rounded-xl overflow-hidden border border-border/80 bg-secondary/50 p-1">
                <DrinkThumbnail
                  :image-url="item.image_url"
                  :category="item.category"
                  :name="item.name"
                  class="w-14 h-14"
                />
              </div>

              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-semibold text-foreground truncate group-hover:text-primary transition-colors">
                  {{ item.name }}
                </h4>
                <p class="text-xs font-heading font-bold text-primary mt-0.5">
                  {{ (item.price * item.quantity).toLocaleString('vi-VN') }}đ
                </p>

                <!-- Quantity Stepper -->
                <div class="flex items-center gap-2 mt-2">
                  <div class="inline-flex items-center rounded-lg border border-border bg-background p-0.5">
                    <button
                      type="button"
                      class="w-6 h-6 rounded-md flex items-center justify-center text-muted-foreground hover:bg-muted hover:text-foreground transition-colors disabled:opacity-40"
                      aria-label="Giảm số lượng"
                      @click="$emit('decrease', index)"
                    >
                      <Minus :size="12" />
                    </button>
                    <span class="text-xs font-bold text-foreground w-6 text-center">
                      {{ item.quantity }}
                    </span>
                    <button
                      type="button"
                      class="w-6 h-6 rounded-md bg-primary text-primary-foreground flex items-center justify-center hover:bg-primary-hover transition-colors"
                      aria-label="Tăng số lượng"
                      @click="$emit('increase', index)"
                    >
                      <Plus :size="12" />
                    </button>
                  </div>

                  <button
                    type="button"
                    class="p-1.5 text-muted-foreground/60 hover:text-destructive transition-colors ml-auto rounded-lg hover:bg-destructive-bg"
                    title="Xóa món"
                    aria-label="Xóa món"
                    @click="$emit('remove', index)"
                  >
                    <Trash2 :size="15" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Customization Notice -->
            <div class="pt-4 pb-2">
              <div class="rounded-xl bg-secondary/60 border border-accent/60 p-3 flex items-start gap-2.5 text-xs text-secondary-foreground">
                <Sparkles :size="15" class="text-primary shrink-0 mt-0.5" />
                <p class="leading-relaxed">
                  Bạn có thể chọn lượng đường, đá và ghi chú riêng cho từng món ở bước xác nhận đơn hàng tiếp theo.
                </p>
              </div>
            </div>
          </div>

          <!-- Drawer Footer -->
          <div v-if="cart.length > 0" class="p-6 border-t border-border bg-muted/20 flex flex-col gap-3">
            <div class="flex items-center justify-between text-base">
              <span class="text-muted-foreground font-medium">Tạm tính</span>
              <span class="font-heading font-extrabold text-xl text-primary">
                {{ cartTotal.toLocaleString('vi-VN') }}đ
              </span>
            </div>

            <button
              type="button"
              class="w-full h-12 bg-primary hover:bg-primary-hover active:scale-[0.99] text-primary-foreground font-semibold rounded-xl flex items-center justify-center gap-2 shadow-md shadow-primary/20 transition-all text-sm"
              @click="$emit('checkout')"
            >
              <span>Tiến hành thanh toán</span>
              <ArrowRight :size="16" />
            </button>

            <button
              type="button"
              class="w-full text-center text-xs font-medium text-muted-foreground hover:text-foreground py-1 transition-colors"
              @click="$emit('close')"
            >
              Chọn thêm món khác
            </button>
          </div>
        </aside>
      </div>
    </div>
  </Transition>
</Teleport>
</template>

<style scoped>
.cart-slide-enter-active,
.cart-slide-leave-active {
  transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.cart-slide-enter-active .cart-panel,
.cart-slide-leave-active .cart-panel {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.cart-slide-enter-from .cart-backdrop,
.cart-slide-leave-to .cart-backdrop {
  opacity: 0;
}

.cart-slide-enter-from .cart-panel,
.cart-slide-leave-to .cart-panel {
  transform: translateX(100%);
}
</style>
