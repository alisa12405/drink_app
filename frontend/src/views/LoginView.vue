<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMenuStore } from '@/stores/menu'
import AppLogo from '@/components/ui/AppLogo.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const auth = useAuthStore()
const menu = useMenuStore()

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

onMounted(() => menu.prefetch())

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
  <div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-[420px] flex flex-col items-center gap-6">
      <AppLogo size="lg" />

      <BaseCard class="w-full" padding="lg">
        <form class="flex flex-col gap-5" @submit.prevent="onSubmit">
          <div>
            <h1 class="font-heading font-bold text-2xl text-foreground">Đăng nhập</h1>
            <p class="text-sm text-muted-foreground mt-1">
              Chào mừng quay lại! Đăng nhập để đặt món yêu thích của bạn.
            </p>
          </div>

          <FormField
            v-model="email"
            label="Email"
            type="email"
            autocomplete="username"
            placeholder="ban@example.com"
            required
          />

          <FormField
            v-model="password"
            label="Mật khẩu"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
          />

          <p v-if="errorMessage" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5" role="alert">
            {{ errorMessage }}
          </p>

          <BaseButton type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Đang đăng nhập…' : 'Đăng nhập' }}
          </BaseButton>

          <p class="text-sm text-muted-foreground text-center">
            Chưa có tài khoản?
            <RouterLink :to="{ name: 'register' }" class="text-primary font-semibold hover:underline">
              Đăng ký ngay
            </RouterLink>
          </p>
          <RouterLink :to="{ name: 'home' }" class="text-sm text-muted-foreground text-center hover:text-primary hover:underline">
            Tiếp tục xem menu không cần đăng nhập
          </RouterLink>
        </form>
      </BaseCard>
    </div>
  </div>
</template>
