<script setup>
import { ShoppingCart } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/ui/AppLogo.vue'
import NotificationCenter from '@/components/layout/NotificationCenter.vue'

const auth = useAuthStore()

defineProps({
  cartCount: {
    type: Number,
    default: 0,
  },
  showCart: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['cart-click'])
</script>

<template>
  <nav class="bg-card shadow-sm sticky top-0 z-50 border-b border-border">
    <div class="max-w-[1800px] mx-auto px-4 md:px-6 h-16 flex items-center justify-between gap-4">
      <AppLogo size="sm" />

      <div class="hidden lg:flex items-center gap-6 flex-1 justify-center flex-wrap">
        <slot />
      </div>

      <div class="flex items-center gap-3 shrink-0">
        <NotificationCenter v-if="auth.isAuthenticated" />
        <slot name="actions" />

        <button
          v-if="showCart"
          type="button"
          class="relative p-2 text-muted-foreground hover:text-primary transition-colors"
          @click="$emit('cart-click')"
        >
          <ShoppingCart :size="22" />
          <span
            v-if="cartCount > 0"
            class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-primary text-primary-foreground rounded-full text-[10px] font-bold flex items-center justify-center"
          >
            {{ cartCount }}
          </span>
        </button>
      </div>
    </div>
  </nav>
</template>
