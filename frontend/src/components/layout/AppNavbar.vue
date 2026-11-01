<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  Menu,
  X,
  ShoppingCart,
  Coffee,
  Heart,
  History,
  Utensils,
  ClipboardList,
  ChartColumn,
  Users,
  LogOut,
  LogIn,
  UserPlus,
  Shield,
  User,
} from 'lucide-vue-next'
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
        { name: 'admin-reports', label: 'Báo cáo doanh thu', icon: ChartColumn },
        { name: 'admin-users', label: 'Quản lý tài khoản', icon: Users },
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
  <header class="bg-card/95 backdrop-blur-md shadow-xs sticky top-0 z-50 border-b border-border/80">
    <div class="max-w-[1700px] mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-18 flex items-center justify-between gap-4">
      <!-- Left: Menu Drawer Toggle + Logo -->
      <div class="flex items-center gap-3 min-w-0">
        <button
          ref="menuButton"
          type="button"
          class="shrink-0 p-2 sm:p-2.5 rounded-xl text-foreground hover:bg-secondary focus-visible:outline-2 focus-visible:outline-ring border border-border/60 hover:border-border transition-colors"
          aria-label="Mở danh sách chức năng"
          aria-controls="app-navigation"
          :aria-expanded="isOpen"
          @click="isOpen = !isOpen"
        >
          <Menu :size="20" />
        </button>

        <RouterLink :to="{ name: 'home' }" aria-label="Smart Drink — Trang chủ" class="shrink-0">
          <AppLogo size="sm" compact-on-mobile />
        </RouterLink>
      </div>

      <!-- Center: Quick Nav for Desktop -->
      <nav class="hidden lg:flex items-center gap-1 xl:gap-2">
        <RouterLink
          :to="{ name: 'home' }"
          class="relative px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 group flex items-center gap-1.5"
          :class="route.name === 'home' ? 'text-primary bg-secondary/90 shadow-xs' : 'text-muted-foreground hover:text-foreground hover:bg-muted/60'"
        >
          <Coffee :size="16" class="transition-transform group-hover:scale-110" />
          <span>Menu</span>
          <span
            v-if="route.name === 'home'"
            class="absolute bottom-1 left-4 right-4 h-0.5 bg-primary rounded-full animate-in fade-in zoom-in-95 duration-200"
          />
        </RouterLink>

        <template v-if="auth.isAuthenticated">
          <RouterLink
            :to="{ name: 'preferences' }"
            class="relative px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 group flex items-center gap-1.5"
            :class="route.name === 'preferences' ? 'text-primary bg-secondary/90 shadow-xs' : 'text-muted-foreground hover:text-foreground hover:bg-muted/60'"
          >
            <Heart :size="16" class="transition-transform group-hover:scale-110" />
            <span>Sở thích</span>
            <span
              v-if="route.name === 'preferences'"
              class="absolute bottom-1 left-4 right-4 h-0.5 bg-primary rounded-full animate-in fade-in zoom-in-95 duration-200"
            />
          </RouterLink>
          <RouterLink
            :to="{ name: 'order-history' }"
            class="relative px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 group flex items-center gap-1.5"
            :class="route.name === 'order-history' ? 'text-primary bg-secondary/90 shadow-xs' : 'text-muted-foreground hover:text-foreground hover:bg-muted/60'"
          >
            <History :size="16" class="transition-transform group-hover:scale-110" />
            <span>Lịch sử đơn</span>
            <span
              v-if="route.name === 'order-history'"
              class="absolute bottom-1 left-4 right-4 h-0.5 bg-primary rounded-full animate-in fade-in zoom-in-95 duration-200"
            />
          </RouterLink>

          <!-- Admin Quick Pill -->
          <RouterLink
            v-if="auth.user?.role === 'admin'"
            :to="{ name: 'admin-menu' }"
            class="relative inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-200 ml-1.5"
            :class="route.name?.startsWith('admin-') ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-500/10 text-amber-700 border border-amber-300 hover:bg-amber-500/20'"
          >
            <Shield :size="14" />
            <span>Quản trị</span>
          </RouterLink>
        </template>
      </nav>

      <!-- Right Actions: Notifications + Auth/User + Cart -->
      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <!-- Notification Bell -->
        <NotificationCenter v-if="auth.isAuthenticated" />

        <!-- User Chip / Auth Buttons -->
        <template v-if="auth.isAuthenticated">
          <button
            type="button"
            class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border/80 hover:bg-secondary transition-colors text-left"
            @click="isOpen = true"
          >
            <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
              {{ auth.user?.name?.charAt(0) || 'U' }}
            </div>
            <div class="max-w-[100px] truncate">
              <p class="text-xs font-semibold text-foreground truncate">{{ auth.user?.name }}</p>
            </div>
          </button>
        </template>
        <template v-else>
          <RouterLink
            :to="{ name: 'login' }"
            class="hidden sm:inline-flex text-xs font-semibold text-muted-foreground hover:text-primary rounded-xl px-3 py-2 transition-colors"
          >
            Đăng nhập
          </RouterLink>
          <RouterLink
            :to="{ name: 'register' }"
            class="inline-flex text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary-hover rounded-xl px-3.5 py-2 shadow-xs transition-colors"
          >
            Đăng ký
          </RouterLink>
        </template>

        <!-- Cart Trigger Button -->
        <button
          v-if="showCart"
          type="button"
          aria-label="Mở giỏ hàng"
          class="relative inline-flex items-center gap-2 p-2 sm:px-3 sm:py-2 rounded-xl text-foreground hover:bg-secondary hover:text-primary transition-all duration-200 border border-border/80 hover:border-primary/40 group active:scale-95 cursor-pointer"
          @click="$emit('cart-click')"
        >
          <ShoppingCart :size="20" class="group-hover:scale-110 group-active:scale-90 transition-transform duration-200" />
          <span class="hidden md:inline text-xs font-semibold">Giỏ hàng</span>
          <span
            v-if="cartCount > 0"
            class="w-5 h-5 bg-primary text-primary-foreground rounded-full text-[10px] font-extrabold flex items-center justify-center shadow-xs animate-in zoom-in-75 duration-200"
          >
            {{ cartCount }}
          </span>
        </button>
      </div>
    </div>
  </header>

  <!-- Left Navigation Drawer -->
  <Teleport to="body">
    <Transition name="navbar-drawer">
      <div v-if="isOpen" class="fixed inset-0 z-[70]" @keydown="onKeydown">
        <div class="drawer-backdrop absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity duration-300" aria-hidden="true" @click="closeMenu" />
        <aside
          id="app-navigation"
          ref="drawer"
          role="dialog"
          aria-modal="true"
          aria-labelledby="navigation-title"
          class="drawer-panel relative h-dvh w-80 max-w-[calc(100vw-2rem)] bg-card shadow-2xl flex flex-col border-r border-border transition-transform duration-300 ease-out"
        >
          <div class="flex items-center justify-between gap-3 px-6 h-18 shrink-0 border-b border-border">
            <AppLogo size="sm" />
            <button
              type="button"
              aria-label="Đóng danh sách chức năng"
              class="p-2 rounded-xl text-muted-foreground hover:bg-secondary hover:text-foreground transition-colors"
              @click="closeMenu"
            >
              <X :size="20" />
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-5">
            <!-- User info card -->
            <div class="mb-5 rounded-2xl bg-secondary/70 border border-accent/50 p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary text-primary-foreground flex items-center justify-center font-bold text-sm shrink-0">
                  <User v-if="!auth.isAuthenticated" :size="18" />
                  <span v-else>{{ auth.user?.name?.charAt(0) || 'U' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="font-bold text-foreground text-sm truncate">{{ auth.user?.name || 'Khách vãng lai' }}</p>
                  <p class="text-[11px] text-muted-foreground mt-0.5 font-medium">
                    {{
                      auth.isAuthenticated
                        ? auth.user?.role === 'admin'
                          ? '👑 Quản trị viên'
                          : 'Khách hàng thân thiết'
                        : 'Đăng nhập để nhận gợi ý AI'
                    }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Navigation links -->
            <nav aria-label="Điều hướng chính" class="flex flex-col gap-1.5">
              <RouterLink
                v-for="link in links"
                :key="link.name"
                :to="{ name: link.name }"
                :aria-current="route.name === link.name ? 'page' : undefined"
                class="flex items-center gap-3 rounded-xl border px-3.5 py-3 text-sm transition-all"
                :class="
                  route.name === link.name
                    ? 'bg-primary text-primary-foreground border-primary font-semibold shadow-xs'
                    : 'border-transparent text-muted-foreground hover:bg-secondary hover:text-primary font-medium'
                "
                @click="closeMenu"
              >
                <component :is="link.icon" :size="18" class="shrink-0" />
                <span>{{ link.label }}</span>
              </RouterLink>
            </nav>
          </div>

          <!-- Footer / Logout -->
          <div v-if="auth.isAuthenticated" class="p-5 border-t border-border bg-muted/20">
            <button
              type="button"
              :disabled="isLoggingOut"
              class="flex items-center justify-center gap-2 w-full rounded-xl border border-destructive/20 bg-destructive-bg px-4 py-3 text-sm font-semibold text-destructive hover:bg-destructive/10 transition-colors disabled:opacity-60"
              @click="logout"
            >
              <LogOut :size="16" />
              <span>{{ isLoggingOut ? 'Đang đăng xuất…' : 'Đăng xuất' }}</span>
            </button>
          </div>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.navbar-drawer-enter-active,
.navbar-drawer-leave-active {
  transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.navbar-drawer-enter-active .drawer-panel,
.navbar-drawer-leave-active .drawer-panel {
  transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.navbar-drawer-enter-from .drawer-backdrop,
.navbar-drawer-leave-to .drawer-backdrop {
  opacity: 0;
}

.navbar-drawer-enter-from .drawer-panel,
.navbar-drawer-leave-to .drawer-panel {
  transform: translateX(-100%);
}
</style>
