<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { Search, ShoppingBag, ArrowRight } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useMenuStore } from '@/stores/menu'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import WelcomeBanner from '@/components/menu/WelcomeBanner.vue'
import RecommendationBanner from '@/components/menu/RecommendationBanner.vue'
import CategorySidebar from '@/components/menu/CategorySidebar.vue'
import DrinkCard from '@/components/menu/DrinkCard.vue'
import CartDrawer from '@/components/menu/CartDrawer.vue'
import DrinkDetailModal from '@/components/menu/DrinkDetailModal.vue'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const menu = useMenuStore()
const { drinks, isLoading, isRefreshing, loadError } = storeToRefs(menu)

const activeCategory = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const menuSection = ref(null)
const isCartOpen = ref(false)
const selectedDrink = ref(null)
const isDetailOpen = ref(false)
const itemsPerPage = 12

function onSelectDrink(drink) {
  selectedDrink.value = drink
  isDetailOpen.value = true
}

function closeDetail() {
  isDetailOpen.value = false
}

function onAddFromDetail(payload) {
  cart.addItem(payload.drink, payload.options)
}

onMounted(() => menu.load())

const categories = computed(() => {
  const unique = [...new Set(drinks.value.map((drink) => drink.category))]
  return [
    { value: 'all', label: 'Tất cả món' },
    ...unique.map((category) => ({ value: category, label: category })),
  ]
})

const filteredDrinks = computed(() => {
  let list = drinks.value

  if (activeCategory.value !== 'all') {
    list = list.filter((drink) => drink.category === activeCategory.value)
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (drink) =>
        drink.name.toLowerCase().includes(q) ||
        (drink.description && drink.description.toLowerCase().includes(q)),
    )
  }

  return list
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

const categoryLabel = computed(() => {
  if (searchQuery.value.trim()) {
    return `Kết quả tìm kiếm cho "${searchQuery.value}"`
  }
  return categories.value.find((cat) => cat.value === activeCategory.value)?.label ?? 'Tất cả món'
})

watch([activeCategory, searchQuery], () => {
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

function onAddToCart(drink) {
  cart.addItem(drink)
}

function openCart() {
  isCartOpen.value = true
}

function closeCart() {
  isCartOpen.value = false
}

function goToCheckout() {
  isCartOpen.value = false
  if (cart.items.length === 0) return
  router.push({ name: 'checkout' })
}
</script>

<template>
  <div class="min-h-screen bg-background pb-20">
    <AppNavbar
      :cart-count="cart.totalQuantity"
      show-cart
      @cart-click="openCart"
    />

    <div class="max-w-[1700px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex flex-col gap-6 sm:gap-8">
      <!-- Top Banners -->
      <WelcomeBanner :name="auth.user?.name" />
      <RecommendationBanner />

      <p
        v-if="loadError"
        class="text-sm text-destructive bg-destructive-bg rounded-2xl border border-destructive/20 px-4 py-3"
      >
        {{ loadError }}
      </p>

      <!-- Main Layout: Categories on the left, Full Products Grid on the right -->
      <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start">
        <!-- Category Selector -->
        <CategorySidebar v-model="activeCategory" :categories="categories" />

        <!-- Products Area -->
        <main ref="menuSection" class="flex-1 min-w-0 w-full scroll-mt-24">
          <!-- Filter & Search Toolbar -->
          <div class="bg-card rounded-2xl border border-border p-4 mb-6 shadow-xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 min-w-0">
              <h2 class="font-heading font-bold text-lg sm:text-xl text-foreground truncate">
                {{ categoryLabel }}
              </h2>
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-secondary text-primary font-bold shrink-0">
                {{ filteredDrinks.length }} món
              </span>
              <span v-if="isRefreshing" class="text-xs text-muted-foreground animate-pulse ml-2 shrink-0">
                Đang làm mới…
              </span>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-64 md:w-80 shrink-0">
              <Search
                :size="16"
                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none"
              />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Tìm trà sữa, cà phê..."
                class="w-full h-10 pl-10 pr-4 rounded-xl border border-border bg-background text-sm text-foreground placeholder:text-muted-foreground/70 focus:outline-hidden focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
              />
              <button
                v-if="searchQuery"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground hover:text-foreground"
                @click="searchQuery = ''"
              >
                ✕
              </button>
            </div>
          </div>

          <!-- Loading Skeleton -->
          <div
            v-if="isLoading && drinks.length === 0"
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-6"
            aria-label="Đang tải menu"
          >
            <div
              v-for="index in 8"
              :key="index"
              class="h-[380px] rounded-2xl border border-border bg-card overflow-hidden animate-pulse flex flex-col"
            >
              <div class="h-52 bg-secondary/80"></div>
              <div class="p-5 flex flex-col gap-3 flex-1">
                <div class="h-3 w-1/3 rounded bg-muted"></div>
                <div class="h-4 w-3/4 rounded bg-muted"></div>
                <div class="h-3 w-full rounded bg-muted"></div>
                <div class="mt-auto h-8 w-full rounded bg-muted"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-else-if="filteredDrinks.length === 0"
            class="rounded-3xl border border-dashed border-border bg-card/60 p-12 text-center flex flex-col items-center justify-center my-6"
          >
            <div class="w-16 h-16 rounded-2xl bg-secondary flex items-center justify-center text-3xl mb-4">
              ☕
            </div>
            <h3 class="font-heading font-bold text-foreground text-lg">Không tìm thấy món phù hợp</h3>
            <p class="text-sm text-muted-foreground mt-1 max-w-sm">
              Thử tìm kiếm với từ khóa khác hoặc chuyển sang danh mục "Tất cả món".
            </p>
            <button
              type="button"
              class="mt-4 px-4 py-2 rounded-xl bg-secondary text-primary font-semibold text-xs hover:bg-accent transition-colors"
              @click="activeCategory = 'all'; searchQuery = ''"
            >
              Xem tất cả món
            </button>
          </div>

          <!-- Products Grid: MINIMUM 3 ITEMS PER ROW on desktop -->
          <div v-else>
            <Transition name="grid-fade" mode="out-in">
              <div
                :key="activeCategory + '-' + currentPage + '-' + (searchQuery ? searchQuery : 'all')"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-6"
              >
                <DrinkCard
                  v-for="(drink, index) in paginatedDrinks"
                  :key="drink.id"
                  :drink="drink"
                  class="animate-drink-in"
                  :style="{ animationDelay: `${index * 30}ms` }"
                  @add="onAddToCart"
                  @select="onSelectDrink"
                />
              </div>
            </Transition>

            <!-- Pagination Bar -->
            <nav
              v-if="totalPages > 1"
              class="mt-8 pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4"
              aria-label="Phân trang menu"
            >
              <p class="text-xs sm:text-sm text-muted-foreground">
                Hiển thị <span class="font-semibold text-foreground">{{ visibleRange.from }}–{{ visibleRange.to }}</span> trên tổng số
                <span class="font-semibold text-foreground">{{ filteredDrinks.length }}</span> món
              </p>

              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  class="h-9 px-3.5 rounded-xl border border-border bg-card text-xs sm:text-sm font-semibold text-muted-foreground transition-colors hover:border-primary/50 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="currentPage === 1"
                  @click="goToPage(currentPage - 1)"
                >
                  Trước
                </button>

                <button
                  v-for="page in pageNumbers"
                  :key="page"
                  type="button"
                  class="h-9 min-w-9 px-2 items-center justify-center rounded-xl border text-xs sm:text-sm font-bold transition-all"
                  :class="[
                    Math.abs(page - currentPage) <= 1 ? 'inline-flex' : 'hidden sm:inline-flex',
                    page === currentPage
                      ? 'border-primary bg-primary text-primary-foreground shadow-xs'
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
                  class="h-9 px-3.5 rounded-xl border border-border bg-card text-xs sm:text-sm font-semibold text-muted-foreground transition-colors hover:border-primary/50 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="currentPage === totalPages"
                  @click="goToPage(currentPage + 1)"
                >
                  Sau
                </button>
              </div>
            </nav>
          </div>
        </main>
      </div>
    </div>

    <!-- Floating Quick Cart Pill (Bottom) -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-12 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform translate-y-12 opacity-0"
    >
      <div
        v-if="cart.totalQuantity > 0 && !isCartOpen"
        class="fixed bottom-5 inset-x-4 sm:inset-x-auto sm:right-6 sm:max-w-md z-40"
      >
        <button
          type="button"
          class="w-full flex items-center justify-between gap-4 px-5 py-3.5 rounded-2xl bg-gradient-to-r from-primary to-primary-hover hover:from-primary-hover hover:to-primary text-primary-foreground shadow-xl shadow-primary/25 border border-white/20 hover:scale-[1.02] active:scale-95 transition-all duration-200 cursor-pointer"
          @click="openCart"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="relative w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
              <ShoppingBag :size="18" />
              <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-accent text-accent-foreground text-[10px] font-extrabold flex items-center justify-center">
                {{ cart.totalQuantity }}
              </span>
            </div>
            <div class="text-left truncate">
              <p class="text-xs text-primary-foreground/80 font-medium">Giỏ hàng của bạn</p>
              <p class="font-heading font-extrabold text-sm sm:text-base leading-tight">
                {{ cart.totalPrice.toLocaleString('vi-VN') }}đ
              </p>
            </div>
          </div>

          <div class="inline-flex items-center gap-1.5 text-xs font-bold bg-white/15 px-3 py-1.5 rounded-xl shrink-0">
            <span>Xem giỏ hàng</span>
            <ArrowRight :size="14" />
          </div>
        </button>
      </div>
    </Transition>

    <!-- Slide-over Cart Drawer -->
    <CartDrawer
      :is-open="isCartOpen"
      :cart="cart.items"
      :cart-total="cart.totalPrice"
      @close="closeCart"
      @increase="cart.increase"
      @decrease="cart.decrease"
      @remove="cart.remove"
      @checkout="goToCheckout"
    />

    <!-- Drink Detail Modal -->
    <DrinkDetailModal
      :is-open="isDetailOpen"
      :drink="selectedDrink"
      @close="closeDetail"
      @add="onAddFromDetail"
    />
  </div>
</template>
