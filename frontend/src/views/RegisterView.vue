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

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const fieldErrors = ref({})
const errorMessage = ref('')
const isSubmitting = ref(false)

onMounted(() => menu.prefetch())

async function onSubmit() {
  errorMessage.value = ''
  fieldErrors.value = {}
  isSubmitting.value = true

  try {
    await auth.register({
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    await router.replace({ name: 'home' })
  } catch (error) {
    const errors = error.response?.data?.errors
    if (errors) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, messages]) => [key, messages[0]]),
      )
    }
    errorMessage.value = error.response?.data?.message || 'Đăng ký thất bại. Vui lòng kiểm tra lại thông tin.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-secondary/40 via-background to-secondary/20 flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <!-- Ambient Glow Circles -->
    <div
      class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-primary/10 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-accent/20 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>

    <div class="w-full max-w-[440px] flex flex-col items-center gap-7 relative z-10">
      <AppLogo size="lg" />

      <BaseCard class="w-full shadow-lg border-border/80" padding="lg">
        <form class="flex flex-col gap-5" @submit.prevent="onSubmit">
          <div>
            <h1 class="font-heading font-extrabold text-2xl text-foreground">Tạo tài khoản</h1>
            <p class="text-sm text-muted-foreground mt-1.5 leading-relaxed">
              Đăng ký để khai báo sở thích và nhận gợi ý đồ uống phù hợp với bạn.
            </p>
          </div>

          <FormField
            v-model="name"
            label="Họ tên"
            type="text"
            autocomplete="name"
            placeholder="Nguyễn Văn A"
            :error="fieldErrors.name"
            required
          />

          <FormField
            v-model="email"
            label="Email"
            type="email"
            autocomplete="username"
            placeholder="ban@example.com"
            :error="fieldErrors.email"
            required
          />

          <FormField
            v-model="password"
            label="Mật khẩu"
            type="password"
            autocomplete="new-password"
            placeholder="Tối thiểu 8 ký tự"
            :error="fieldErrors.password"
            required
          />

          <FormField
            v-model="passwordConfirmation"
            label="Xác nhận mật khẩu"
            type="password"
            autocomplete="new-password"
            placeholder="Nhập lại mật khẩu"
            required
          />

          <p v-if="errorMessage" class="text-sm text-destructive bg-destructive-bg border border-destructive/20 rounded-xl px-3.5 py-2.5" role="alert">
            {{ errorMessage }}
          </p>

          <BaseButton type="submit" :disabled="isSubmitting" class="shadow-sm shadow-primary/20">
            {{ isSubmitting ? 'Đang tạo tài khoản…' : 'Đăng ký' }}
          </BaseButton>

          <div class="flex flex-col gap-2 pt-2 border-t border-border/60">
            <p class="text-xs text-muted-foreground text-center">
              Đã có tài khoản?
              <RouterLink :to="{ name: 'login' }" class="text-primary font-bold hover:underline ml-1">
                Đăng nhập
              </RouterLink>
            </p>
            <RouterLink :to="{ name: 'home' }" class="text-xs text-muted-foreground text-center hover:text-primary transition-colors font-medium">
              ← Tiếp tục xem menu không cần tài khoản
            </RouterLink>
          </div>
        </form>
      </BaseCard>
    </div>
  </div>
</template>
