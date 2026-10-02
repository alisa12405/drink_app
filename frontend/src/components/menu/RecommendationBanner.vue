<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
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
import { preferencesApi, recommendationsApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { getCategoryEmoji } from '@/constants/drinkOptions'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()

const context = ref(null)
const recommendations = ref([])
const meta = ref(null)
const occasion = ref('')
const coordinates = ref(null)
const now = ref(new Date())
const isContextLoading = ref(false)
const isLoading = ref(false)
const isLocating = ref(false)
const hasRequested = ref(false)
const errorMessage = ref('')
const locationMessage = ref('')
const showAuthModal = ref(false)
const showPreferenceModal = ref(false)
const cooldownSeconds = ref(0)
let clockTimer
let cooldownTimer

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

function startCooldown(seconds = 60) {
  cooldownSeconds.value = Math.max(1, Number(seconds) || 60)
  window.clearInterval(cooldownTimer)
  cooldownTimer = window.setInterval(() => {
    cooldownSeconds.value = Math.max(0, cooldownSeconds.value - 1)
    if (cooldownSeconds.value === 0) window.clearInterval(cooldownTimer)
  }, 1000)
}

async function loadContext() {
  isContextLoading.value = true
  try {
    const params = coordinates.value
      ? { lat: coordinates.value.lat, lon: coordinates.value.lon }
      : {}
    const { data } = await recommendationsApi.context(params)
    context.value = data.data ?? null
  } catch {
    context.value = {
      suggestion: 'Bạn có thể xem menu và chọn món phù hợp với khẩu vị ngay lúc này.',
      location_used: false,
    }
  } finally {
    isContextLoading.value = false
  }
}

function locateForWeather({ silent = false } = {}) {
  if (!navigator.geolocation) {
    locationMessage.value = 'Trình duyệt không hỗ trợ định vị; gợi ý chung vẫn sẵn sàng.'
    return
  }

  isLocating.value = true
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      coordinates.value = {
        lat: Number(position.coords.latitude.toFixed(5)),
        lon: Number(position.coords.longitude.toFixed(5)),
      }
      locationMessage.value = 'Đã cập nhật thời tiết theo vị trí hiện tại.'
      isLocating.value = false
      await loadContext()
    },
    () => {
      coordinates.value = null
      locationMessage.value = silent
        ? 'Bạn chưa chia sẻ vị trí; đang hiển thị gợi ý chung theo thời gian.'
        : 'Không lấy được vị trí. Bạn vẫn có thể nhận gợi ý không dùng thời tiết.'
      isLocating.value = false
      void loadContext()
    },
    { enableHighAccuracy: false, timeout: 7000, maximumAge: 0 },
  )
}

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
  isLoading.value = true
  hasRequested.value = true
  errorMessage.value = ''

  try {
    const params = {}
    if (occasion.value) params.occasion = occasion.value
    if (coordinates.value) {
      params.lat = coordinates.value.lat
      params.lon = coordinates.value.lon
    }

    const { data } = await recommendationsApi.list(params)
    recommendations.value = data.data ?? []
    meta.value = data.meta ?? null
  } catch (error) {
    if (error.response?.status === 429) {
      startCooldown(error.response.headers?.['retry-after'])
    }
    errorMessage.value =
      error.response?.data?.message ||
      'Chưa thể tải gợi ý lúc này. Menu bên dưới vẫn hoạt động bình thường.'
  } finally {
    isLoading.value = false
  }
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
  void loadContext()
  locateForWeather({ silent: true })
})

onBeforeUnmount(() => {
  window.clearInterval(clockTimer)
  window.clearInterval(cooldownTimer)
})
</script>

<template>
  <section class="rounded-2xl border border-accent bg-secondary/40 px-5 py-5 text-secondary-foreground">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
      <div class="flex items-start gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-card flex items-center justify-center shrink-0 shadow-sm">
          <Sparkles :size="20" class="text-primary" />
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="font-heading text-base font-semibold">Hôm nay uống gì?</h2>
          </div>
          <p class="text-sm text-muted-foreground mt-1 leading-relaxed">
            {{ isContextLoading && !context ? 'Đang chuẩn bị gợi ý…' : contextSuggestion }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 shrink-0">
        <div class="rounded-xl border border-border bg-card px-3.5 py-2.5 flex items-center gap-2.5 min-w-40">
          <Clock3 :size="17" class="text-primary" />
          <div>
            <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Thời gian</p>
            <p class="text-sm font-semibold text-foreground">{{ displayedTime }}</p>
          </div>
        </div>
        <div class="rounded-xl border border-border bg-card px-3.5 py-2.5 flex items-center gap-2.5 min-w-44">
          <CloudSun :size="18" class="text-primary" />
          <div>
            <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Thời tiết</p>
            <p class="text-sm font-semibold text-foreground">
              {{ isContextLoading ? 'Đang cập nhật…' : weatherLabel }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-t border-border/70 pt-4">
      <div class="flex flex-col sm:flex-row gap-2">
        <select
          v-if="auth.isAuthenticated"
          v-model="occasion"
          class="h-10 rounded-xl border border-border bg-card px-3 text-xs text-foreground outline-none focus:ring-2 focus:ring-primary/30"
          aria-label="Ngữ cảnh hiện tại"
        >
          <option value="">Ngữ cảnh bất kỳ</option>
          <option value="học tập hoặc làm việc">Học tập / làm việc</option>
          <option value="sau khi vận động">Sau khi vận động</option>
          <option value="thư giãn">Thư giãn</option>
          <option value="gặp gỡ bạn bè">Gặp gỡ bạn bè</option>
        </select>
        <button
          type="button"
          class="h-10 rounded-xl border border-border bg-card px-3 text-xs font-semibold text-muted-foreground hover:text-primary hover:border-primary/40 transition-colors flex items-center justify-center gap-1.5 disabled:opacity-60"
          :disabled="isLocating"
          @click="locateForWeather()"
        >
          <LoaderCircle v-if="isLocating" :size="14" class="animate-spin" />
          <MapPin v-else :size="14" />
          Cập nhật thời tiết theo vị trí
        </button>
      </div>

      <button
        type="button"
        class="h-10 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary-hover transition-colors flex items-center justify-center gap-2 disabled:opacity-60"
        :disabled="isLoading || cooldownSeconds > 0"
        @click="onRecommendationClick"
      >
        <LoaderCircle v-if="isLoading" :size="16" class="animate-spin" />
        <RefreshCw v-else-if="hasRequested" :size="16" />
        <Sparkles v-else :size="16" />
        {{
          cooldownSeconds > 0
            ? `Thử lại sau ${cooldownSeconds}s`
            : hasRequested
              ? 'Cập nhật top 5'
              : 'Nhận gợi ý của bạn'
        }}
      </button>
    </div>

    <p v-if="locationMessage" class="text-[11px] text-muted-foreground mt-3">
      {{ locationMessage }}
    </p>

    <div
      v-if="isLoading && recommendations.length === 0"
      class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mt-4"
    >
      <div
        v-for="index in 5"
        :key="index"
        class="h-36 rounded-xl bg-card animate-pulse border border-border"
      ></div>
    </div>

    <div
      v-else-if="errorMessage"
      class="mt-4 rounded-xl bg-card border border-destructive/20 px-4 py-3 flex items-center justify-between gap-3"
    >
      <p class="text-xs text-destructive">{{ errorMessage }}</p>
      <button
        v-if="auth.isAuthenticated && cooldownSeconds === 0"
        type="button"
        class="text-xs font-semibold text-primary hover:underline shrink-0"
        @click="onRecommendationClick"
      >
        Thử lại
      </button>
    </div>

    <template v-else-if="recommendations.length > 0">
      <div class="mt-4 rounded-xl border border-primary/15 bg-card px-4 py-3">
        <span class="text-[10px] font-semibold rounded-full border border-border px-2 py-0.5 text-primary">
          {{ strategyLabel }}
        </span>
        <p class="mt-2 text-sm leading-relaxed text-foreground">{{ meta?.reason_summary }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mt-3">
        <article
          v-for="item in recommendations"
          :key="item.drink.id"
          class="bg-card rounded-xl border border-border p-3 flex flex-col gap-3 min-w-0 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="flex items-center gap-3">
            <div class="w-16 h-16 rounded-lg bg-secondary overflow-hidden shrink-0 flex items-center justify-center">
              <img
                v-if="item.drink.image_url"
                :src="item.drink.image_url"
                :alt="item.drink.name"
                class="w-full h-full object-contain p-1"
                loading="lazy"
                decoding="async"
              />
              <span v-else class="text-2xl" aria-hidden="true">
                {{ getCategoryEmoji(item.drink.category) }}
              </span>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-foreground">{{ item.drink.name }}</p>
              <span class="text-xs font-bold text-primary">
                {{ Number(item.drink.price).toLocaleString('vi-VN') }}đ
              </span>
            </div>
          </div>
          <p class="text-xs text-muted-foreground leading-relaxed flex-1">{{ item.explanation }}</p>
          <button
            type="button"
            class="w-full h-8 rounded-lg bg-secondary text-secondary-foreground text-xs font-semibold flex items-center justify-center gap-1.5 hover:bg-accent transition-colors"
            @click="cart.addItem(item.drink)"
          >
            <Plus :size="13" />
            Thêm vào giỏ
          </button>
        </article>
      </div>
    </template>

    <p v-else-if="hasRequested" class="text-xs text-muted-foreground mt-4">
      Chưa có món phù hợp để gợi ý. Bạn vẫn có thể chọn từ menu bên dưới.
    </p>
  </section>

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
