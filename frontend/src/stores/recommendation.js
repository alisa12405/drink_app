import { defineStore } from 'pinia'
import { ref } from 'vue'
import { recommendationsApi } from '@/services/api'

const REC_STORAGE_KEY = 'smart_drink_rec_cache_v1'
const COORD_STORAGE_KEY = 'smart_drink_coords_cache_v1'

function readCachedData() {
  try {
    const raw = sessionStorage.getItem(REC_STORAGE_KEY)
    if (!raw) return null
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function readCachedCoords() {
  try {
    const raw = localStorage.getItem(COORD_STORAGE_KEY)
    if (!raw) return null
    return JSON.parse(raw)
  } catch {
    return null
  }
}

export const useRecommendationStore = defineStore('recommendation', () => {
  const cached = readCachedData()
  const cachedCoords = readCachedCoords()

  const context = ref(cached?.context ?? null)
  const recommendations = ref(cached?.recommendations ?? [])
  const meta = ref(cached?.meta ?? null)
  const occasion = ref(cached?.occasion ?? '')
  const hasRequested = ref(Boolean(cached?.hasRequested))
  const coordinates = ref(cachedCoords ?? null)

  const isContextLoading = ref(false)
  const isLoading = ref(false)
  const isLocating = ref(false)
  const errorMessage = ref('')
  const locationMessage = ref(cachedCoords ? 'Đã lưu vị trí trước đó' : '')
  const cooldownSeconds = ref(0)
  let cooldownTimer = null

  function persist() {
    try {
      sessionStorage.setItem(
        REC_STORAGE_KEY,
        JSON.stringify({
          context: context.value,
          recommendations: recommendations.value,
          meta: meta.value,
          occasion: occasion.value,
          hasRequested: hasRequested.value,
        }),
      )
    } catch {
      // quota or private mode
    }
  }

  function saveCoordinates(coords) {
    coordinates.value = coords
    if (coords) {
      try {
        localStorage.setItem(COORD_STORAGE_KEY, JSON.stringify(coords))
      } catch {
        // quota
      }
    }
  }

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
      persist()
    } catch {
      if (!context.value) {
        context.value = {
          suggestion: 'Bạn có thể xem menu và chọn món phù hợp với khẩu vị ngay lúc này.',
          location_used: false,
        }
      }
    } finally {
      isContextLoading.value = false
    }
  }

  async function checkAndAutoLocate() {
    if (!navigator.geolocation) return

    // If browser supports Permissions API, check if geolocation is already granted
    if (typeof navigator.permissions !== 'undefined' && navigator.permissions?.query) {
      try {
        const permissionStatus = await navigator.permissions.query({ name: 'geolocation' })
        if (permissionStatus.state === 'granted') {
          locateForWeather({ silent: true })
        }
        permissionStatus.onchange = () => {
          if (permissionStatus.state === 'granted') {
            locateForWeather({ silent: true })
          }
        }
        return
      } catch {
        // Fallback for browsers with restricted query API
      }
    }

    // If we have cached coordinates from previously granted permission, refresh silently
    if (coordinates.value) {
      locateForWeather({ silent: true })
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
        saveCoordinates({
          lat: Number(position.coords.latitude.toFixed(5)),
          lon: Number(position.coords.longitude.toFixed(5)),
        })
        locationMessage.value = 'Đã tự động cập nhật thời tiết theo vị trí của bạn.'
        isLocating.value = false
        await loadContext()
      },
      () => {
        if (!silent) {
          locationMessage.value = 'Không lấy được vị trí. Bạn vẫn có thể nhận gợi ý không dùng thời tiết.'
        }
        isLocating.value = false
        if (!context.value) {
          void loadContext()
        }
      },
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
    )
  }

  async function requestRecommendations() {
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
      persist()
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

  function reset() {
    recommendations.value = []
    meta.value = null
    hasRequested.value = false
    errorMessage.value = ''
    try {
      sessionStorage.removeItem(REC_STORAGE_KEY)
    } catch {
      // Bo qua loi quota hoac private mode
    }
  }

  return {
    context,
    recommendations,
    meta,
    occasion,
    hasRequested,
    coordinates,
    isContextLoading,
    isLoading,
    isLocating,
    errorMessage,
    locationMessage,
    cooldownSeconds,
    loadContext,
    checkAndAutoLocate,
    locateForWeather,
    requestRecommendations,
    startCooldown,
    reset,
  }
})
