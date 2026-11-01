<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import {
  Clock3,
  CloudSun,
  LoaderCircle,
  LogIn,
  MapPin,
  Plus,
  RefreshCw,
  Sparkles,
  UserRoundCog,
  X,
} from 'lucide-vue-next'
import { preferencesApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useRecommendationStore } from '@/stores/recommendation'
import { getCategoryEmoji } from '@/constants/drinkOptions'
import DrinkDetailModal from '@/components/menu/DrinkDetailModal.vue'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const recStore = useRecommendationStore()

const {
  context,
  recommendations,
  meta,
  occasion,
  isContextLoading,
  isLoading,
  isLocating,
  hasRequested,
  errorMessage,
  locationMessage,
  cooldownSeconds,
} = storeToRefs(recStore)

const now = ref(new Date())
const showAuthModal = ref(false)
const showPreferenceModal = ref(false)
const selectedItem = ref(null)
const isDetailOpen = ref(false)
let clockTimer

function openDetail(item) {
  selectedItem.value = item
  isDetailOpen.value = true
}

function closeDetail() {
  isDetailOpen.value = false
}

function handleAddFromDetail(payload) {
  cart.addItem(payload.drink, payload.options)
}

const displayedTime = computed(() =>
  new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit' }).format(now.value),
)

const weatherLabel = computed(() => {
  if (!context.value?.location_used) return 'Chưa dùng vị trí'
  if (!context.value?.weather) return 'Chưa lấy được thời tiết'

  const labels = {
    clear: 'Trời quang',
    cloudy: 'Có mây',
    rain: 'Có mưa',
    storm: 'Có giông',
    snow: 'Có tuyết',
    mist: 'Có sương',
  }
  const label = labels[context.value.weather] ?? context.value.weather
  const temperature = context.value.temperature

  return temperature !== null && temperature !== undefined && Number.isFinite(Number(temperature))
    ? `${label}, ${Number(temperature).toFixed(1)}°C`
    : label
})

const strategyLabel = computed(() =>
  meta.value?.fallback ? 'Gợi ý dự phòng' : 'Gợi ý cá nhân hóa',
)

function briefSuggestion(recommendationContext) {
  const hour = Number(recommendationContext?.hour)
  const temperature = recommendationContext?.temperature
  const isHot = temperature !== null && temperature !== undefined && Number(temperature) >= 28
  const isCold = temperature !== null && temperature !== undefined && Number(temperature) <= 20

  if (Number.isFinite(hour) && hour >= 18) {
    return isHot
      ? 'Buổi tối trời nóng, hãy thử nước trái cây mát và ít caffeine để dễ thư giãn.'
      : 'Buổi tối, hãy thử một món nhẹ, ít caffeine để dễ thư giãn.'
  }
  if (isHot) return 'Trời nóng, hãy thử trà trái cây hoặc một món mát lạnh.'
  if (isCold) return 'Trời se lạnh, hãy thử một món nóng để dễ thưởng thức hơn.'
  if (Number.isFinite(hour) && hour < 10) {
    return 'Buổi sáng, hãy thử cà phê hoặc một món thanh nhẹ để bắt đầu ngày mới.'
  }
  return 'Hãy thử một món hợp khẩu vị và thời điểm hiện tại của bạn.'
}

const contextSuggestion = computed(() => briefSuggestion(context.value))

function hasDeclaredPreferences(preference) {
  return Boolean(
    preference?.updated_at ||
      preference?.profile_text ||
      preference?.allergy_notes ||
      (Array.isArray(preference?.taste_tags) && preference.taste_tags.length > 0),
  )
}

async function onRecommendationClick() {
  if (!auth.isAuthenticated) {
    showAuthModal.value = true
    return
  }

  errorMessage.value = ''
  try {
    const { data } = await preferencesApi.show()
    if (!hasDeclaredPreferences(data.data)) {
      showPreferenceModal.value = true
      return
    }
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || 'Chưa thể kiểm tra sở thích của bạn. Vui lòng thử lại.'
    return
  }

  await loadRecommendations()
}

async function loadRecommendations() {
  showPreferenceModal.value = false
  await recStore.requestRecommendations()
}

function goToPreferences() {
  showPreferenceModal.value = false
  router.push({ name: 'preferences' })
}

function goToAuth(routeName) {
  showAuthModal.value = false
  router.push({ name: routeName, query: { redirect: '/' } })
}

onMounted(() => {
  clockTimer = window.setInterval(() => {
    now.value = new Date()
  }, 30_000)

  // 1. Tự động kiểm tra quyền vị trí và cập nhật thời tiết nếu trình duyệt đã cho phép
  void recStore.checkAndAutoLocate()

  // 2. Tải ngữ cảnh ban đầu nếu chưa có trong cache
  if (!context.value) {
    void recStore.loadContext()
  }
})

onBeforeUnmount(() => {
  window.clearInterval(clockTimer)
})
</script>

<template>
  <section class="rounded-3xl border border-primary/20 bg-gradient-to-br from-card via-secondary/30 to-secondary/50 p-5 sm:p-6 shadow-xs text-secondary-foreground">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
      <div class="flex items-start gap-3.5 min-w-0">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary to-primary-hover text-white flex items-center justify-center shrink-0 shadow-md shadow-primary/20">
          <Sparkles :size="20" />
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="font-heading text-lg font-bold text-foreground">Hôm nay uống gì?</h2>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-bold">
              AI Gợi ý cá nhân hóa
            </span>
          </div>
          <p class="text-xs sm:text-sm text-muted-foreground mt-1 leading-relaxed">
            {{ isContextLoading && !context ? 'Đang chuẩn bị gợi ý…' : contextSuggestion }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 shrink-0">
        <div class="rounded-2xl border border-border/80 bg-card/90 backdrop-blur-xs px-4 py-2.5 flex items-center gap-3 min-w-44 shadow-2xs">
          <div class="w-8 h-8 rounded-xl bg-secondary flex items-center justify-center text-primary shrink-0">
            <Clock3 :size="16" />
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Thời gian</p>
            <p class="text-sm font-bold text-foreground">{{ displayedTime }}</p>
          </div>
        </div>
        <div class="rounded-2xl border border-border/80 bg-card/90 backdrop-blur-xs px-4 py-2.5 flex items-center gap-3 min-w-48 shadow-2xs">
          <div class="w-8 h-8 rounded-xl bg-secondary flex items-center justify-center text-primary shrink-0">
            <CloudSun :size="17" />
          </div>
          <div>
            <p class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Thời tiết</p>
            <p class="text-sm font-bold text-foreground truncate">
              {{ isContextLoading ? 'Đang cập nhật…' : weatherLabel }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-5 flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-t border-border/60 pt-4">
      <div class="flex flex-col sm:flex-row gap-2.5">
        <select
          v-if="auth.isAuthenticated"
          v-model="occasion"
          class="h-10 rounded-xl border border-border bg-card px-3 text-xs font-medium text-foreground outline-hidden focus:border-primary focus:ring-2 focus:ring-primary/20 shadow-2xs"
          aria-label="Ngữ cảnh hiện tại"
        >
          <option value="">🎯 Ngữ cảnh bất kỳ</option>
          <option value="học tập hoặc làm việc">📚 Học tập / làm việc</option>
          <option value="sau khi vận động">🏃 Sau khi vận động</option>
          <option value="thư giãn">☕ Thư giãn</option>
          <option value="gặp gỡ bạn bè">👥 Gặp gỡ bạn bè</option>
        </select>
        <button
          type="button"
          class="h-10 rounded-xl border border-border bg-card px-3.5 text-xs font-semibold text-muted-foreground hover:text-primary hover:border-primary/40 transition-colors flex items-center justify-center gap-1.5 disabled:opacity-60 shadow-2xs"
          :disabled="isLocating"
          @click="recStore.locateForWeather()"
        >
          <LoaderCircle v-if="isLocating" :size="14" class="animate-spin" />
          <MapPin v-else :size="14" />
          <span>Cập nhật vị trí thời tiết</span>
        </button>
      </div>

      <button
        type="button"
        class="h-11 rounded-xl bg-gradient-to-r from-primary to-primary-hover px-5 text-sm font-bold text-primary-foreground hover:shadow-md hover:shadow-primary/20 transition-all flex items-center justify-center gap-2 disabled:opacity-60"
        :disabled="isLoading || cooldownSeconds > 0"
        @click="onRecommendationClick"
      >
        <LoaderCircle v-if="isLoading" :size="16" class="animate-spin" />
        <RefreshCw v-else-if="hasRequested" :size="16" />
        <Sparkles v-else :size="16" />
        <span>
          {{
            cooldownSeconds > 0
              ? `Thử lại sau ${cooldownSeconds}s`
              : hasRequested
                ? 'Làm mới gợi ý'
                : 'Nhận gợi ý cho bạn'
          }}
        </span>
      </button>
    </div>

    <p v-if="locationMessage" class="text-[11px] text-muted-foreground mt-3">
      {{ locationMessage }}
    </p>

    <!-- Skeletons when loading recommendations -->
    <div
      v-if="isLoading && recommendations.length === 0"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5 mt-4"
    >
      <div
        v-for="index in 5"
        :key="index"
        class="h-44 rounded-2xl bg-card animate-pulse border border-border"
      ></div>
    </div>

    <div
      v-else-if="errorMessage"
      class="mt-4 rounded-2xl bg-card border border-destructive/20 p-4 flex items-center justify-between gap-3 shadow-2xs"
    >
      <p class="text-xs text-destructive font-medium">{{ errorMessage }}</p>
      <button
        v-if="auth.isAuthenticated && cooldownSeconds === 0"
        type="button"
        class="text-xs font-bold text-primary hover:underline shrink-0"
        @click="onRecommendationClick"
      >
        Thử lại
      </button>
    </div>

    <template v-else-if="recommendations.length > 0">
      <div class="mt-5 rounded-2xl border border-primary/20 bg-card p-4 shadow-2xs">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="text-[10px] font-bold rounded-full bg-secondary border border-accent px-2.5 py-0.5 text-primary">
            {{ strategyLabel }}
          </span>
          <span class="text-xs text-muted-foreground font-medium">Lý do gợi ý</span>
        </div>
        <p class="text-xs sm:text-sm leading-relaxed text-foreground font-medium">{{ meta?.reason_summary }}</p>
      </div>

      <!-- Top 5 Recommendations Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5 mt-4">
        <article
          v-for="(item, itemIndex) in recommendations"
          :key="item.drink.id"
          class="bg-card rounded-2xl border border-border/80 p-3.5 flex flex-col gap-3 min-w-0 transition-all duration-200 hover:-translate-y-1.5 hover:shadow-lg hover:border-primary/50 relative group cursor-pointer focus-visible:outline-2 focus-visible:outline-primary"
          tabindex="0"
          role="button"
          :aria-label="`Xem chi tiết và lý do gợi ý món ${item.drink.name}`"
          @click="openDetail(item)"
          @keydown.enter="openDetail(item)"
        >
          <!-- Ranking Badge -->
          <div class="absolute top-2.5 left-2.5 z-10">
            <span
              class="w-6 h-6 rounded-lg text-white font-heading font-extrabold text-[11px] flex items-center justify-center shadow-xs"
              :class="[
                itemIndex === 0
                  ? 'bg-amber-500'
                  : itemIndex === 1
                    ? 'bg-stone-500'
                    : itemIndex === 2
                      ? 'bg-amber-700'
                      : 'bg-primary/80',
              ]"
            >
              #{{ itemIndex + 1 }}
            </span>
          </div>

          <div class="flex items-center gap-3 pt-5">
            <div class="w-16 h-16 rounded-xl bg-secondary overflow-hidden shrink-0 flex items-center justify-center p-1">
              <img
                v-if="item.drink.image_url"
                :src="item.drink.image_url"
                :alt="item.drink.name"
                class="w-full h-full object-contain group-hover:scale-105 transition-transform"
                loading="lazy"
                decoding="async"
              />
              <span v-else class="text-2xl" aria-hidden="true">
                {{ getCategoryEmoji(item.drink.category) }}
              </span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-bold text-foreground truncate group-hover:text-primary transition-colors">
                {{ item.drink.name }}
              </p>
              <span class="text-xs font-heading font-extrabold text-primary block mt-0.5">
                {{ Number(item.drink.price).toLocaleString('vi-VN') }}đ
              </span>
            </div>
          </div>

          <p class="text-xs text-muted-foreground leading-relaxed flex-1 line-clamp-2">
            {{ item.explanation }}
          </p>

          <button
            type="button"
            class="w-full h-8 rounded-xl bg-secondary text-secondary-foreground text-xs font-bold flex items-center justify-center gap-1.5 hover:bg-primary hover:text-primary-foreground active:scale-95 transition-all duration-200 shadow-2xs cursor-pointer"
            @click.stop="cart.addItem(item.drink)"
          >
            <Plus :size="13" />
            <span>Thêm vào giỏ</span>
          </button>
        </article>
      </div>
    </template>

    <p v-else-if="hasRequested" class="text-xs text-muted-foreground mt-4">
      Chưa có món phù hợp để gợi ý. Bạn vẫn có thể chọn từ menu bên dưới.
    </p>
  </section>

  <!-- Drink Detail & AI Recommendation Explanation Modal -->
  <DrinkDetailModal
    :is-open="isDetailOpen"
    :drink="selectedItem?.drink"
    :recommendation="selectedItem"
    @close="closeDetail"
    @add="handleAddFromDetail"
  />

  <Teleport to="body">
    <div
      v-if="showAuthModal"
      class="fixed inset-0 z-[100] bg-black/40 px-4 flex items-center justify-center"
      role="dialog"
      aria-modal="true"
      aria-labelledby="auth-recommendation-title"
    >
      <div class="w-full max-w-md rounded-2xl bg-card border border-border shadow-xl p-6 relative">
        <button
          type="button"
          class="absolute top-4 right-4 text-muted-foreground hover:text-foreground"
          aria-label="Đóng"
          @click="showAuthModal = false"
        >
          <X :size="18" />
        </button>
        <div class="w-11 h-11 rounded-xl bg-secondary flex items-center justify-center mb-4">
          <LogIn :size="21" class="text-primary" />
        </div>
        <h3 id="auth-recommendation-title" class="font-heading font-bold text-lg text-foreground">
          Đăng nhập để nhận gợi ý của bạn
        </h3>
        <p class="text-sm text-muted-foreground leading-relaxed mt-2">
          Khách vãng lai vẫn có thể xem menu và đặt món. Gợi ý top 5 cần tài khoản để sử dụng sở thích và lịch sử lựa chọn của bạn.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6">
          <button
            type="button"
            class="h-10 rounded-xl border border-border text-sm font-semibold text-foreground hover:bg-secondary"
            @click="goToAuth('register')"
          >
            Tạo tài khoản
          </button>
          <button
            type="button"
            class="h-10 rounded-xl bg-primary text-sm font-semibold text-primary-foreground hover:bg-primary-hover"
            @click="goToAuth('login')"
          >
            Đăng nhập
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="showPreferenceModal"
      class="fixed inset-0 z-[100] bg-black/40 px-4 flex items-center justify-center"
      role="dialog"
      aria-modal="true"
      aria-labelledby="preference-recommendation-title"
    >
      <div class="w-full max-w-md rounded-2xl bg-card border border-border shadow-xl p-6 relative">
        <button
          type="button"
          class="absolute top-4 right-4 text-muted-foreground hover:text-foreground"
          aria-label="Đóng"
          @click="showPreferenceModal = false"
        >
          <X :size="18" />
        </button>
        <div class="w-11 h-11 rounded-xl bg-secondary flex items-center justify-center mb-4">
          <UserRoundCog :size="21" class="text-primary" />
        </div>
        <h3 id="preference-recommendation-title" class="font-heading font-bold text-lg text-foreground">
          Bạn chưa khai báo sở thích
        </h3>
        <p class="text-sm text-muted-foreground leading-relaxed mt-2">
          Thêm khẩu vị, mức đường và mức đá sẽ giúp top 5 sát với bạn hơn. Bạn cũng có thể bỏ qua và nhận gợi ý cơ bản ngay bây giờ.
        </p>
        <div class="flex flex-col gap-3 mt-6">
          <button
            type="button"
            class="h-10 rounded-xl bg-primary text-sm font-semibold text-primary-foreground hover:bg-primary-hover"
            @click="goToPreferences"
          >
            Thêm sở thích trước
          </button>
          <button
            type="button"
            class="h-10 rounded-xl border border-border text-sm font-semibold text-foreground hover:bg-secondary"
            @click="loadRecommendations"
          >
            Bỏ qua, nhận gợi ý cơ bản
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
