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
  <div class="min-h-screen bg-gradient-to-br from-secondary/40 via-background to-secondary/20 flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <!-- Ambient Glow Circles -->
    <div
      class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-primary/10 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-accent/20 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>

    <div class="w-full max-w-[440px] flex flex-col items-center gap-7 relative z-10">
      <AppLogo size="lg" />

      <BaseCard class="w-full shadow-lg border-border/80" padding="lg">
        <form class="flex flex-col gap-5" @submit.prevent="onSubmit">
          <div>
            <h1 class="font-heading font-extrabold text-2xl text-foreground">Đăng nhập</h1>
            <p class="text-sm text-muted-foreground mt-1.5 leading-relaxed">
              Chào mừng bạn quay lại! Đăng nhập để nhận gợi ý món cá nhân hóa.
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

          <p v-if="errorMessage" class="text-sm text-destructive bg-destructive-bg border border-destructive/20 rounded-xl px-3.5 py-2.5" role="alert">
            {{ errorMessage }}
          </p>

          <BaseButton type="submit" :disabled="isSubmitting" class="shadow-sm shadow-primary/20">
            {{ isSubmitting ? 'Đang đăng nhập…' : 'Đăng nhập' }}
          </BaseButton>

          <div class="flex flex-col gap-2 pt-2 border-t border-border/60">
            <p class="text-xs text-muted-foreground text-center">
              Chưa có tài khoản?
              <RouterLink :to="{ name: 'register' }" class="text-primary font-bold hover:underline ml-1">
                Đăng ký ngay
              </RouterLink>
            </p>
            <RouterLink :to="{ name: 'home' }" class="text-xs text-muted-foreground text-center hover:text-primary transition-colors font-medium">
              ← Tiếp tục xem menu không cần đăng nhập
            </RouterLink>
          </div>
        </form>
      </BaseCard>
    </div>
  </div>
</template>
