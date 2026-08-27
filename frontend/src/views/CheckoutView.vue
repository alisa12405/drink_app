<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { CheckCircle2, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { ordersApi } from '@/services/api'
import { SUGAR_OPTIONS, ICE_OPTIONS, ORDER_TYPE_OPTIONS, getCategoryEmoji } from '@/constants/drinkOptions'
import AppLogo from '@/components/ui/AppLogo.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormField from '@/components/ui/FormField.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import ProgressSteps from '@/components/ui/ProgressSteps.vue'
import RadioCard from '@/components/ui/RadioCard.vue'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()

const PAYMENT_OPTIONS = [
  { value: 'cash', label: 'Tiền mặt', icon: '💵' },
  { value: 'qr', label: 'Chuyển khoản QR', icon: '📱' },
]

const step = ref('form') // 'form' | 'confirmed'
const orderType = ref('dine_in')
const paymentMethod = ref('cash')
const note = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const lastOrder = ref(null)

onMounted(() => {
  if (cart.items.length === 0) {
    router.replace({ name: 'home' })
  }
})

watch(
  () => cart.items.length,
  (length) => {
    if (length === 0 && step.value === 'form') {
      router.replace({ name: 'home' })
    }
  },
)

async function confirmOrder() {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const { data } = await ordersApi.create({
      items: cart.items.map((item) => ({
        drink_id: item.drink_id,
        quantity: item.quantity,
        sugar_level: item.sugar_level,
        ice_level: item.ice_level,
        note: item.note || null,
      })),
      order_type: orderType.value,
      occasion: note.value.trim() || null,
    })
    lastOrder.value = data.data
    cart.clear()
    step.value = 'confirmed'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Đặt hàng thất bại. Vui lòng thử lại.'
  } finally {
    isSubmitting.value = false
  }
}

function goHome() {
  router.push({ name: 'home' })
}

function goToOrderHistory() {
  router.push({ name: 'order-history' })
}
</script>

<template>
  <div class="min-h-screen bg-background flex flex-col">
    <nav class="bg-card border-b border-border shadow-sm sticky top-0 z-50">
      <div class="max-w-[1200px] mx-auto px-4 md:px-6 h-16 flex items-center justify-between gap-4">
        <RouterLink :to="{ name: 'home' }">
          <AppLogo size="sm" />
        </RouterLink>
        <p class="hidden sm:block text-sm text-muted-foreground font-medium">Đặt hàng an toàn &amp; đơn giản</p>
        <ProgressSteps :current-step="step === 'confirmed' ? 2 : 1" />
      </div>
    </nav>

    <div v-if="step === 'confirmed'" class="flex-1 flex items-center justify-center px-4 py-16">
      <BaseCard padding="lg" class="max-w-md w-full flex flex-col items-center text-center">
        <div class="w-20 h-20 bg-success-bg rounded-full flex items-center justify-center mb-6">
          <CheckCircle2 :size="40" class="text-success" />
        </div>
        <h2 class="font-heading font-bold text-2xl text-foreground mb-2">Đặt hàng thành công!</h2>
        <p class="text-sm text-muted-foreground leading-relaxed mb-3">
          Đơn hàng của bạn đã được ghi nhận và đang chờ quán xác nhận.
        </p>
        <p class="text-sm text-muted-foreground mb-1">
          Mã đơn <span class="font-semibold text-foreground">#{{ lastOrder?.id }}</span>
        </p>
        <p class="text-lg font-bold text-primary mb-8">
          {{ Number(lastOrder?.total_price ?? 0).toLocaleString('vi-VN') }}đ
        </p>
        <div class="w-full flex flex-col gap-3">
          <BaseButton @click="goHome">Về trang chủ</BaseButton>
          <BaseButton variant="secondary" @click="goToOrderHistory">Xem lịch sử đơn hàng</BaseButton>
        </div>
      </BaseCard>
    </div>

    <div v-else class="max-w-[1200px] w-full mx-auto px-4 md:px-6 py-8 flex flex-col md:flex-row gap-6">
      <div class="flex-1 w-full flex flex-col gap-5">
        <BaseCard>
          <SectionHeader :number="1" title="Món đã chọn" />
          <div class="flex flex-col gap-4">
            <div
              v-for="(item, index) in cart.items"
              :key="index"
              class="flex flex-col gap-3 pb-4 border-b border-border last:border-0 last:pb-0"
            >
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-secondary flex items-center justify-center text-xl shrink-0">
                  {{ getCategoryEmoji(item.category) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-foreground truncate">{{ item.name }}</p>
                  <p class="text-sm text-primary font-bold mt-0.5">
                    {{ (item.price * item.quantity).toLocaleString('vi-VN') }}đ
                  </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <button
                    type="button"
                    class="w-6 h-6 rounded-full border border-border flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors"
                    @click="cart.decrease(index)"
                  >
                    <Minus :size="12" />
                  </button>
                  <span class="text-sm font-semibold text-foreground w-4 text-center">{{ item.quantity }}</span>
                  <button
                    type="button"
                    class="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-primary-foreground hover:bg-primary-hover transition-colors"
                    @click="cart.increase(index)"
                  >
                    <Plus :size="12" />
                  </button>
                </div>
                <button
                  type="button"
                  class="text-muted-foreground/60 hover:text-destructive transition-colors shrink-0"
                  @click="cart.remove(index)"
                >
                  <Trash2 :size="16" />
                </button>
              </div>

              <div class="flex items-center gap-2 pl-14">
                <select
                  v-model="item.sugar_level"
                  class="flex-1 text-xs border border-border rounded-lg px-2.5 py-2 bg-input-background text-foreground outline-none focus:ring-2 focus:ring-primary/30"
                >
                  <option v-for="option in SUGAR_OPTIONS" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
                <select
                  v-model="item.ice_level"
                  class="flex-1 text-xs border border-border rounded-lg px-2.5 py-2 bg-input-background text-foreground outline-none focus:ring-2 focus:ring-primary/30"
                >
                  <option v-for="option in ICE_OPTIONS" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <input
                v-model="item.note"
                type="text"
                placeholder="Ghi chú riêng cho món (tuỳ chọn)"
                class="ml-14 text-xs border border-border rounded-lg px-3 py-2 bg-input-background text-foreground outline-none focus:ring-2 focus:ring-primary/30 placeholder-gray-400"
              />
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <SectionHeader :number="2" title="Thông tin khách hàng" />
          <div class="text-sm">
            <p class="font-semibold text-foreground">{{ auth.user?.name }}</p>
            <p class="text-muted-foreground">{{ auth.user?.email }}</p>
          </div>
        </BaseCard>

        <BaseCard>
          <SectionHeader :number="3" title="Hình thức nhận đồ" />
          <div class="flex gap-4 flex-wrap">
            <RadioCard
              v-for="opt in ORDER_TYPE_OPTIONS"
              :key="opt.value"
              :label="opt.label"
              :icon="opt.icon"
              :selected="orderType === opt.value"
              @select="orderType = opt.value"
            />
          </div>
        </BaseCard>

        <BaseCard>
          <SectionHeader :number="4" title="Ghi chú thêm" />
          <FormField v-model="note" label="Dịp / ghi chú (tuỳ chọn)" placeholder="vd: sau khi tập gym" />
        </BaseCard>

        <BaseCard>
          <SectionHeader :number="5" title="Phương thức thanh toán (giả lập)" />
          <div class="flex gap-3 flex-wrap">
            <RadioCard
              v-for="opt in PAYMENT_OPTIONS"
              :key="opt.value"
              :label="opt.label"
              :icon="opt.icon"
              :selected="paymentMethod === opt.value"
              @select="paymentMethod = opt.value"
            />
          </div>
          <p class="text-xs text-muted-foreground mt-4">
            * Đây là bước thanh toán giả lập cho mục đích demo, chưa tích hợp cổng thanh toán thật.
          </p>
        </BaseCard>

        <RouterLink
          :to="{ name: 'home' }"
          class="flex items-center gap-1 text-sm text-muted-foreground hover:text-primary transition-colors w-fit"
        >
          ← Quay lại menu
        </RouterLink>
      </div>

      <aside class="w-full md:w-80 shrink-0">
        <BaseCard padding="none" class="overflow-hidden md:sticky md:top-24">
          <div class="px-6 py-4 border-b border-border">
            <h2 class="font-heading font-bold text-foreground">Đơn hàng của bạn</h2>
          </div>

          <div class="px-6 py-4 flex flex-col gap-4 max-h-[280px] overflow-y-auto">
            <div v-for="(item, index) in cart.items" :key="index" class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-secondary flex items-center justify-center text-lg shrink-0">
                {{ getCategoryEmoji(item.category) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-foreground truncate">{{ item.name }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">x{{ item.quantity }}</p>
              </div>
              <span class="text-sm font-bold text-primary shrink-0">
                {{ (item.price * item.quantity).toLocaleString('vi-VN') }}đ
              </span>
            </div>
          </div>

          <div class="px-6 py-4 border-t border-border bg-muted/40">
            <div class="flex justify-between text-base font-bold text-foreground">
              <span>Tổng cộng</span>
              <span class="text-primary text-xl">{{ cart.totalPrice.toLocaleString('vi-VN') }}đ</span>
            </div>
          </div>

          <div class="px-6 py-5 flex flex-col gap-3">
            <p v-if="errorMessage" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
              {{ errorMessage }}
            </p>
            <BaseButton :disabled="isSubmitting" @click="confirmOrder">
              <ShoppingBag :size="16" />
              {{ isSubmitting ? 'Đang xử lý…' : 'Xác nhận đặt hàng' }}
            </BaseButton>
          </div>
        </BaseCard>
      </aside>
    </div>
  </div>
</template>
