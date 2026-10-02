<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { Menu, X, ShoppingCart, Coffee, Heart, History, Utensils, ClipboardList, ChartColumn, LogOut, LogIn, UserPlus } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/ui/AppLogo.vue'
import NotificationCenter from '@/components/layout/NotificationCenter.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const isOpen = ref(false)
const menuButton = ref(null)
const drawer = ref(null)
const isLoggingOut = ref(false)
let previousOverflow = ''

defineProps({
  cartCount: { type: Number, default: 0 },
  showCart: { type: Boolean, default: false },
})
defineEmits(['cart-click'])

const links = computed(() => {
  const items = [{ name: 'home', label: 'Menu đồ uống', icon: Coffee }]
  if (auth.isAuthenticated) {
    items.push(
      { name: 'preferences', label: 'Sở thích của tôi', icon: Heart },
      { name: 'order-history', label: 'Lịch sử đơn hàng', icon: History },
    )
    if (auth.user?.role === 'admin') {
      items.push(
        { name: 'admin-menu', label: 'Quản lý menu', icon: Utensils },
        { name: 'admin-orders', label: 'Quản lý đơn hàng', icon: ClipboardList },
        { name: 'admin-reports', label: 'Báo cáo', icon: ChartColumn },
      )
    }
  } else {
    items.push(
      { name: 'login', label: 'Đăng nhập', icon: LogIn },
      { name: 'register', label: 'Đăng ký', icon: UserPlus },
    )
  }
  return items
})

function closeMenu() {
  isOpen.value = false
}

function onKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault()
    closeMenu()
  }
  if (event.key !== 'Tab') return
  const elements = drawer.value?.querySelectorAll('a[href], button:not([disabled])')
  if (!elements?.length) return
  const first = elements[0]
  const last = elements[elements.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

watch(isOpen, async (open) => {
  if (open) {
    previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    await nextTick()
    if (isOpen.value) drawer.value?.querySelector('button')?.focus()
  } else {
    document.body.style.overflow = previousOverflow
    menuButton.value?.focus()
  }
})
watch(() => route.fullPath, closeMenu)
onBeforeUnmount(() => {
  if (isOpen.value) document.body.style.overflow = previousOverflow
})

async function logout() {
  isLoggingOut.value = true
  try {
    await auth.logout()
    closeMenu()
    await router.replace({ name: 'home' })
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <header class="bg-card shadow-sm sticky top-0 z-50 border-b border-border">
    <div class="max-w-[1800px] mx-auto px-3 md:px-6 h-16 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2 min-w-0">
        <button ref="menuButton" type="button" class="shrink-0 p-2 rounded-xl text-foreground hover:bg-secondary focus-visible:outline-2 focus-visible:outline-ring"
          aria-label="Mở danh sách chức năng" aria-controls="app-navigation" :aria-expanded="isOpen" @click="isOpen = !isOpen">
          <Menu :size="22" />
        </button>
        <RouterLink :to="{ name: 'home' }" aria-label="Smart Drink — Trang chủ" class="shrink-0">
          <AppLogo size="sm" compact-on-mobile />
        </RouterLink>
      </div>
      <div class="flex items-center gap-1 sm:gap-3 shrink-0">
        <NotificationCenter v-if="auth.isAuthenticated" />
        <template v-if="!auth.isAuthenticated">
          <RouterLink :to="{ name: 'login' }" class="hidden sm:inline-flex text-xs font-semibold text-muted-foreground hover:text-primary rounded-lg px-3 py-2">Đăng nhập</RouterLink>
          <RouterLink :to="{ name: 'register' }" class="inline-flex text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary-hover rounded-lg px-3 py-2">Đăng ký</RouterLink>
        </template>
        <button v-if="showCart" type="button" aria-label="Mở giỏ hàng" class="relative p-2 text-muted-foreground hover:text-primary transition-colors" @click="$emit('cart-click')">
          <ShoppingCart :size="22" />
          <span v-if="cartCount > 0" class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-primary text-primary-foreground rounded-full text-[10px] font-bold flex items-center justify-center">{{ cartCount }}</span>
        </button>
      </div>
    </div>
  </header>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[70]" @keydown="onKeydown">
      <div class="absolute inset-0 bg-black/40" aria-hidden="true" @click="closeMenu" />
      <aside id="app-navigation" ref="drawer" role="dialog" aria-modal="true" aria-labelledby="navigation-title"
        class="relative h-dvh w-80 max-w-[calc(100vw-2rem)] bg-card shadow-xl flex flex-col">
        <div class="flex items-center justify-between gap-3 px-5 h-16 shrink-0 border-b border-border">
          <h2 id="navigation-title" class="font-heading font-bold text-foreground">Chức năng</h2>
          <button type="button" aria-label="Đóng danh sách chức năng" class="p-2 rounded-xl text-muted-foreground hover:bg-secondary focus-visible:outline-2 focus-visible:outline-ring" @click="closeMenu"><X :size="22" /></button>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
          <div class="mb-4 rounded-xl bg-background p-3">
            <p class="font-semibold text-foreground break-words">{{ auth.user?.name || 'Khách vãng lai' }}</p>
            <p class="text-xs text-muted-foreground mt-1">{{ auth.isAuthenticated ? (auth.user?.role === 'admin' ? 'Quản trị viên' : 'Khách hàng') : 'Đăng nhập để lưu sở thích và xem lịch sử' }}</p>
          </div>
          <nav aria-label="Điều hướng chính" class="flex flex-col gap-2">
            <RouterLink v-for="link in links" :key="link.name" :to="{ name: link.name }" :aria-current="route.name === link.name ? 'page' : undefined"
              class="flex items-center gap-3 rounded-xl border px-3 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-ring"
              :class="route.name === link.name ? 'bg-secondary border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:bg-secondary hover:text-primary'" @click="closeMenu">
              <component :is="link.icon" :size="20" class="shrink-0" />
              {{ link.label }}
            </RouterLink>
          </nav>
        </div>
        <div v-if="auth.isAuthenticated" class="p-4 border-t border-border">
          <button type="button" :disabled="isLoggingOut" class="flex items-center justify-center gap-2 w-full rounded-xl border border-border px-3 py-3 text-sm font-semibold text-destructive hover:bg-destructive-bg disabled:opacity-60" @click="logout">
            <LogOut :size="18" />{{ isLoggingOut ? 'Đang đăng xuất…' : 'Đăng xuất' }}
          </button>
        </div>
      </aside>
    </div>
  </Teleport>
</template>
