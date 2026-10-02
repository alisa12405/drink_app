<script setup>
import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-vue-next'
import DrinkThumbnail from '@/components/menu/DrinkThumbnail.vue'

defineProps({
  cart: {
    type: Array,
    required: true,
  },
  cartTotal: {
    type: Number,
    required: true,
  },
})

defineEmits(['increase', 'decrease', 'remove', 'checkout'])
</script>

<template>
  <aside class="w-full lg:w-80 shrink-0">
    <div class="bg-card rounded-2xl shadow-sm border border-border flex flex-col overflow-hidden lg:sticky lg:top-24">
      <div class="px-5 py-4 border-b border-border">
        <h2 class="font-heading font-bold text-foreground">Giỏ hàng của bạn</h2>
      </div>

      <div class="overflow-y-auto px-5 py-3 flex flex-col gap-3 max-h-[420px]">
        <div v-if="cart.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
          <ShoppingCart :size="36" class="text-border mb-3" />
          <p class="text-sm text-muted-foreground font-medium">Giỏ hàng trống</p>
          <p class="text-xs text-muted-foreground/70 mt-1">Thêm món từ menu bên trái</p>
        </div>

        <div v-for="(item, index) in cart" :key="index" class="flex items-center gap-3">
          <DrinkThumbnail
            :image-url="item.image_url"
            :category="item.category"
            :name="item.name"
          />
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-foreground truncate">{{ item.name }}</p>
            <p class="text-xs text-primary font-bold mt-0.5">
              {{ (item.price * item.quantity).toLocaleString('vi-VN') }}đ
            </p>
            <div class="flex items-center gap-2 mt-1.5">
              <button
                type="button"
                class="w-5 h-5 rounded-full border border-border flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors"
                @click="$emit('decrease', index)"
              >
                <Minus :size="10" />
              </button>
              <span class="text-xs font-semibold text-foreground w-4 text-center">{{ item.quantity }}</span>
              <button
                type="button"
                class="w-5 h-5 rounded-full bg-primary flex items-center justify-center text-primary-foreground hover:bg-primary-hover transition-colors"
                @click="$emit('increase', index)"
              >
                <Plus :size="10" />
              </button>
            </div>
          </div>
          <button
            type="button"
            class="text-muted-foreground/60 hover:text-destructive transition-colors shrink-0"
            @click="$emit('remove', index)"
          >
            <Trash2 :size="14" />
          </button>
        </div>

        <p v-if="cart.length > 0" class="text-[11px] text-muted-foreground/80 leading-relaxed pt-1">
          Chọn đường/đá và ghi chú cho từng món ở bước Giỏ hàng &amp; Thanh toán tiếp theo.
        </p>
      </div>

      <div class="px-5 py-4 border-t border-border bg-muted/40">
        <div class="flex justify-between text-base font-bold text-foreground">
          <span>Tổng cộng</span>
          <span class="text-primary text-lg">{{ cartTotal.toLocaleString('vi-VN') }}đ</span>
        </div>
      </div>

      <div class="px-5 pb-5">
        <button
          type="button"
          :disabled="cart.length === 0"
          class="w-full bg-primary hover:bg-primary-hover disabled:bg-muted disabled:text-muted-foreground text-primary-foreground font-semibold py-3 rounded-xl flex items-center justify-center gap-2 transition-colors text-sm shadow-sm"
          @click="$emit('checkout')"
        >
          <ShoppingCart :size="16" />
          Tiến hành đặt hàng
        </button>
      </div>
    </div>
  </aside>
</template>
