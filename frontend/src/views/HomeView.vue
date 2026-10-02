<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useMenuStore } from '@/stores/menu'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import WelcomeBanner from '@/components/menu/WelcomeBanner.vue'
import RecommendationBanner from '@/components/menu/RecommendationBanner.vue'
import CategorySidebar from '@/components/menu/CategorySidebar.vue'
import DrinkCard from '@/components/menu/DrinkCard.vue'
import CartSidebar from '@/components/menu/CartSidebar.vue'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const menu = useMenuStore()
const { drinks, isLoading, isRefreshing, loadError } = storeToRefs(menu)
const activeCategory = ref('all')
const currentPage = ref(1)
const menuSection = ref(null)
const itemsPerPage = 12

onMounted(() => menu.load())

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

const totalPages = computed(() => Math.max(1, Math.ceil(filteredDrinks.value.length / itemsPerPage)))

const paginatedDrinks = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredDrinks.value.slice(start, start + itemsPerPage)
})

const pageNumbers = computed(() =>
  Array.from({ length: totalPages.value }, (_, index) => index + 1),
)

const visibleRange = computed(() => {
  if (filteredDrinks.value.length === 0) return { from: 0, to: 0 }

  const from = (currentPage.value - 1) * itemsPerPage + 1
  return {
    from,
    to: Math.min(from + itemsPerPage - 1, filteredDrinks.value.length),
  }
})

const categoryLabel = computed(
  () => categories.value.find((cat) => cat.value === activeCategory.value)?.label ?? '',
)

watch(activeCategory, () => {
  currentPage.value = 1
})

watch(totalPages, (pages) => {
  if (currentPage.value > pages) currentPage.value = pages
})

function goToPage(page) {
  if (page < 1 || page > totalPages.value || page === currentPage.value) return

  currentPage.value = page
  menuSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}


function goToCheckout() {
  if (cart.items.length === 0) return
  router.push({ name: 'checkout' })
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <AppNavbar :cart-count="cart.totalQuantity" show-cart @cart-click="goToCheckout" />

    <div class="max-w-[1800px] mx-auto px-4 md:px-6 py-6 flex flex-col gap-6">
      <WelcomeBanner :name="auth.user?.name" />
      <RecommendationBanner />

      <p v-if="loadError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
        {{ loadError }}
      </p>

      <div class="flex flex-col lg:flex-row gap-6">
        <CategorySidebar v-model="activeCategory" :categories="categories" />

        <main ref="menuSection" class="flex-1 min-w-0 scroll-mt-24">
          <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="font-heading font-bold text-foreground">{{ categoryLabel }}</h2>
            <span v-if="isRefreshing" class="text-xs text-muted-foreground" role="status">
              Đang cập nhật menu…
            </span>
          </div>

          <div
            v-if="isLoading && drinks.length === 0"
            class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-5"
            aria-label="Đang tải menu"
          >
            <div
              v-for="index in 6"
              :key="index"
              class="h-[360px] rounded-2xl border border-border bg-card overflow-hidden animate-pulse"
            >
              <div class="h-52 bg-secondary"></div>
              <div class="p-5 flex flex-col gap-3">
                <div class="h-4 w-2/3 rounded bg-muted"></div>
                <div class="h-3 w-full rounded bg-muted"></div>
                <div class="h-3 w-1/2 rounded bg-muted"></div>
              </div>
            </div>
          </div>

          <div v-else-if="filteredDrinks.length === 0" class="flex flex-col items-center justify-center py-24 text-center text-muted-foreground">
            <span class="text-5xl mb-4">🍽️</span>
            <p class="font-medium">Chưa có món nào trong danh mục này</p>
          </div>

          <div v-else>
            <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-5">
              <DrinkCard v-for="drink in paginatedDrinks" :key="drink.id" :drink="drink" @add="cart.addItem" />
            </div>

            <nav
              v-if="totalPages > 1"
              class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3"
              aria-label="Phân trang menu"
            >
              <p class="text-xs text-muted-foreground">
                Hiển thị {{ visibleRange.from }}–{{ visibleRange.to }} trên
                {{ filteredDrinks.length }} món
              </p>

              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  class="h-9 px-3 rounded-lg border border-border bg-card text-sm font-medium text-muted-foreground transition hover:border-primary/50 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="currentPage === 1"
                  @click="goToPage(currentPage - 1)"
                >
                  Trước
                </button>

                <button
                  v-for="page in pageNumbers"
                  :key="page"
                  type="button"
                  class="h-9 min-w-9 px-2 items-center justify-center rounded-lg border text-sm font-semibold transition"
                  :class="[
                    Math.abs(page - currentPage) <= 1 ? 'inline-flex' : 'hidden sm:inline-flex',
                    page === currentPage
                      ? 'border-primary bg-primary text-primary-foreground'
                      : 'border-border bg-card text-muted-foreground hover:border-primary/50 hover:text-primary',
                  ]"
                  :aria-current="page === currentPage ? 'page' : undefined"
                  :aria-label="`Trang ${page}`"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>

                <button
                  type="button"
                  class="h-9 px-3 rounded-lg border border-border bg-card text-sm font-medium text-muted-foreground transition hover:border-primary/50 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="currentPage === totalPages"
                  @click="goToPage(currentPage + 1)"
                >
                  Sau
                </button>
              </div>
            </nav>
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
