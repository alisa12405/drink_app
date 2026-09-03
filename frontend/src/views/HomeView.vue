<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { drinksApi } from '@/services/api'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import WelcomeBanner from '@/components/menu/WelcomeBanner.vue'
import RecommendationBanner from '@/components/menu/RecommendationBanner.vue'
import CategorySidebar from '@/components/menu/CategorySidebar.vue'
import DrinkCard from '@/components/menu/DrinkCard.vue'
import CartSidebar from '@/components/menu/CartSidebar.vue'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const drinks = ref([])
const loadError = ref('')
const activeCategory = ref('all')

onMounted(async () => {
  try {
    const { data } = await drinksApi.list()
    drinks.value = data.data ?? []
  } catch {
    loadError.value = 'Không tải được menu. Vui lòng thử lại sau.'
  }
})

const categories = computed(() => {
  const unique = [...new Set(drinks.value.map((drink) => drink.category))]
  return [
    { value: 'all', label: 'Tất cả' },
    ...unique.map((category) => ({ value: category, label: category })),
  ]
})

const filteredDrinks = computed(() => {
  if (activeCategory.value === 'all') return drinks.value
  return drinks.value.filter((drink) => drink.category === activeCategory.value)
})

const categoryLabel = computed(
  () => categories.value.find((cat) => cat.value === activeCategory.value)?.label ?? '',
)

async function onLogout() {
  await auth.logout()
  await router.replace({ name: 'login' })
}

function goToCheckout() {
  if (cart.items.length === 0) return
  router.push({ name: 'checkout' })
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <AppNavbar :cart-count="cart.totalQuantity" show-cart @cart-click="goToCheckout">
      <RouterLink :to="{ name: 'preferences' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Sở thích của tôi
      </RouterLink>
      <RouterLink :to="{ name: 'order-history' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Lịch sử đơn hàng
      </RouterLink>
      <template v-if="auth.user?.role === 'admin'">
        <RouterLink :to="{ name: 'admin-menu' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
          Quản lý menu
        </RouterLink>
        <RouterLink :to="{ name: 'admin-orders' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
          Quản lý đơn hàng
        </RouterLink>
        <RouterLink :to="{ name: 'admin-reports' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
          Báo cáo
        </RouterLink>
      </template>

      <template #actions>
        <div class="hidden md:block text-right leading-tight">
          <p class="text-xs font-semibold text-foreground">{{ auth.user?.name }}</p>
          <p class="text-[10px] text-muted-foreground uppercase tracking-wide">{{ auth.user?.role }}</p>
        </div>
        <button
          type="button"
          class="text-xs font-semibold text-muted-foreground hover:text-destructive border border-border rounded-lg px-3 py-1.5 transition-colors"
          @click="onLogout"
        >
          Đăng xuất
        </button>
      </template>
    </AppNavbar>

    <div class="max-w-[1400px] mx-auto px-4 md:px-6 py-6 flex flex-col gap-6">
      <WelcomeBanner :name="auth.user?.name" />
      <RecommendationBanner />

      <p v-if="loadError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
        {{ loadError }}
      </p>

      <div class="flex flex-col lg:flex-row gap-6">
        <CategorySidebar v-model="activeCategory" :categories="categories" />

        <main class="flex-1 min-w-0">
          <h2 class="font-heading font-bold text-foreground mb-4">{{ categoryLabel }}</h2>

          <div v-if="filteredDrinks.length === 0" class="flex flex-col items-center justify-center py-24 text-center text-muted-foreground">
            <span class="text-5xl mb-4">🍽️</span>
            <p class="font-medium">Chưa có món nào trong danh mục này</p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <DrinkCard v-for="drink in filteredDrinks" :key="drink.id" :drink="drink" @add="cart.addItem" />
          </div>
        </main>

        <CartSidebar
          :cart="cart.items"
          :cart-total="cart.totalPrice"
          @increase="cart.increase"
          @decrease="cart.decrease"
          @remove="cart.remove"
          @checkout="goToCheckout"
        />
      </div>
    </div>
  </div>
</template>
