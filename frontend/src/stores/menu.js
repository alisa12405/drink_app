import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { drinksApi } from '@/services/api'

const MENU_CACHE_KEY = 'smart_drink_menu_v1'
const MENU_CACHE_TTL_MS = 5 * 60 * 1000

function readCachedMenu() {
  try {
    const cached = JSON.parse(localStorage.getItem(MENU_CACHE_KEY) || 'null')
    if (!cached || !Array.isArray(cached.drinks) || !Number.isFinite(cached.updatedAt)) {
      return null
    }
    return cached
  } catch {
    localStorage.removeItem(MENU_CACHE_KEY)
    return null
  }
}

export const useMenuStore = defineStore('menu', () => {
  const cached = readCachedMenu()
  const drinks = ref(cached?.drinks ?? [])
  const updatedAt = ref(cached?.updatedAt ?? 0)
  const isLoading = ref(false)
  const isRefreshing = ref(false)
  const loadError = ref('')
  let pendingRequest = null

  const hasDrinks = computed(() => drinks.value.length > 0)

  function isFresh() {
    return hasDrinks.value && Date.now() - updatedAt.value < MENU_CACHE_TTL_MS
  }

  function persist() {
    try {
      localStorage.setItem(
        MENU_CACHE_KEY,
        JSON.stringify({ drinks: drinks.value, updatedAt: updatedAt.value }),
      )
    } catch {
      // Menu van hoat dong trong bo nho neu trinh duyet chan/quota localStorage.
    }
  }

  async function load({ force = false } = {}) {
    if (!force && isFresh()) return drinks.value
    if (pendingRequest) return pendingRequest

    const isInitialLoad = !hasDrinks.value
    isLoading.value = isInitialLoad
    isRefreshing.value = !isInitialLoad
    loadError.value = ''

    pendingRequest = (async () => {
      try {
        const { data } = await drinksApi.list()
        drinks.value = data.data ?? []
        updatedAt.value = Date.now()
        persist()
      } catch {
        loadError.value = hasDrinks.value
          ? 'Không thể cập nhật menu mới nhất. Đang hiển thị dữ liệu đã lưu.'
          : 'Không tải được menu. Vui lòng thử lại sau.'
      } finally {
        isLoading.value = false
        isRefreshing.value = false
        pendingRequest = null
      }

      return drinks.value
    })()

    return pendingRequest
  }

  function prefetch() {
    return load()
  }

  function refresh() {
    return load({ force: true })
  }

  function invalidate() {
    updatedAt.value = 0
    localStorage.removeItem(MENU_CACHE_KEY)
  }

  return {
    drinks,
    hasDrinks,
    isLoading,
    isRefreshing,
    loadError,
    load,
    prefetch,
    refresh,
    invalidate,
  }
})
