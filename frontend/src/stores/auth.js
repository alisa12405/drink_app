import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '@/services/api'

const TOKEN_KEY = 'auth_token'
const USER_KEY = 'auth_user'

function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY) || 'null')
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || '')
  const user = ref(readStoredUser())

  const isAuthenticated = computed(() => Boolean(token.value))

  function persist(nextToken, nextUser) {
    token.value = nextToken
    user.value = nextUser
    localStorage.setItem(TOKEN_KEY, nextToken)
    localStorage.setItem(USER_KEY, JSON.stringify(nextUser))
  }

  function clear() {
    token.value = ''
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  async function login(email, password) {
    const { data } = await authApi.login({ email, password })
    persist(data.token, data.data)
    return user.value
  }

  async function register(payload) {
    const { data } = await authApi.register(payload)
    persist(data.token, data.data)
    return user.value
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // Token có thể đã hết hạn — vẫn xoá phiên local.
    }
    clear()
  }

  return { token, user, isAuthenticated, login, register, logout, clear }
})
