<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('test@example.com')
const password = ref('password')
const errorMessage = ref('')
const isSubmitting = ref(false)

const demoAccounts = [
  { label: 'Khách hàng', email: 'test@example.com', password: 'password' },
  { label: 'Admin', email: 'admin@example.com', password: 'password' },
]

function fillDemo(account) {
  email.value = account.email
  password.value = account.password
  errorMessage.value = ''
}

async function onSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await auth.login(email.value.trim(), password.value)
    await router.replace({ name: 'home' })
  } catch (error) {
    const apiMessage = error.response?.data?.errors?.email?.[0] || error.response?.data?.message
    errorMessage.value = apiMessage || 'Đăng nhập thất bại. Kiểm tra email, mật khẩu và API backend.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <form class="card" @submit.prevent="onSubmit">
      <p class="eyebrow">Smart Drink</p>
      <h1>Đăng nhập</h1>
      <p class="subtitle">Dùng tài khoản seed để thử API Auth.</p>

      <label>
        Email
        <input v-model="email" type="email" autocomplete="username" required />
      </label>

      <label>
        Mật khẩu
        <input v-model="password" type="password" autocomplete="current-password" required />
      </label>

      <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>

      <button type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? 'Đang đăng nhập…' : 'Đăng nhập' }}
      </button>

      <div class="demo">
        <span>Điền nhanh:</span>
        <button
          v-for="account in demoAccounts"
          :key="account.email"
          type="button"
          class="ghost"
          @click="fillDemo(account)"
        >
          {{ account.label }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.card {
  width: min(100%, 420px);
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 16px;
  padding: 2rem 1.75rem;
  box-shadow: 0 12px 40px rgba(74, 44, 23, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.eyebrow {
  color: #b45309;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

h1 {
  font-size: 1.7rem;
  color: #3f2a1d;
}

.subtitle,
.demo {
  color: #6b5848;
  font-size: 0.95rem;
}

label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.9rem;
  color: #4a3728;
}

input {
  border: 1px solid #e0d3c2;
  border-radius: 10px;
  padding: 0.7rem 0.8rem;
  font: inherit;
  color: #3f2a1d;
  background: #fffdf8;
}

input:focus {
  outline: 2px solid #d97706;
  border-color: #d97706;
}

button {
  margin-top: 0.4rem;
  border: 0;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  background: #b45309;
  color: #fff;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

button:disabled {
  opacity: 0.7;
  cursor: wait;
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
}

.demo {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.ghost {
  margin: 0;
  background: #fff7ed;
  color: #9a3412;
  border: 1px solid #fed7aa;
  padding: 0.35rem 0.7rem;
  font-weight: 500;
}
</style>
