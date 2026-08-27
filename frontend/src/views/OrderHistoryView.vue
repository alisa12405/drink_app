<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { PackageOpen, Star } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { ordersApi, ratingsApi } from '@/services/api'
import {
  ORDER_STATUS_LABELS,
  ORDER_STATUS_BADGE_CLASSES,
  getCategoryEmoji,
  getOrderTypeInfo,
} from '@/constants/drinkOptions'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const auth = useAuthStore()

const orders = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const cancellingId = ref(null)

// key = `${order_id}:${drink_id}` -> { id, rating, comment }
const ratingsByKey = reactive({})
// key = `${order_id}:${drink_id}` -> { rating, comment, isSubmitting, error, success }
const ratingForms = reactive({})

function ratingKey(orderId, drinkId) {
  return `${orderId}:${drinkId}`
}

async function loadOrders() {
  isLoading.value = true
  try {
    const [ordersRes, ratingsRes] = await Promise.all([ordersApi.history(), ratingsApi.list()])
    orders.value = ordersRes.data.data ?? []

    for (const key of Object.keys(ratingsByKey)) delete ratingsByKey[key]
    for (const rating of ratingsRes.data.data ?? []) {
      ratingsByKey[ratingKey(rating.order_id, rating.drink_id)] = rating
    }
  } catch {
    errorMessage.value = 'Không tải được lịch sử đơn hàng.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadOrders)

function statusLabel(status) {
  return ORDER_STATUS_LABELS[status] ?? status
}

function statusBadgeClass(status) {
  return ORDER_STATUS_BADGE_CLASSES[status] ?? 'bg-secondary text-secondary-foreground'
}

function orderTypeLabel(order) {
  const info = getOrderTypeInfo(order.context_snapshot?.order_type)
  return info ? `${info.icon} ${info.label}` : null
}

async function cancelOrder(order) {
  cancellingId.value = order.id
  try {
    await ordersApi.cancel(order.id)
    await loadOrders()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Không thể huỷ đơn hàng.'
  } finally {
    cancellingId.value = null
  }
}

function ratingForm(orderId, drinkId) {
  const key = ratingKey(orderId, drinkId)
  if (!ratingForms[key]) {
    const existing = ratingsByKey[key]
    ratingForms[key] = {
      rating: existing?.rating ?? 0,
      comment: existing?.comment ?? '',
      isSubmitting: false,
      error: '',
      success: false,
    }
  }
  return ratingForms[key]
}

function setStars(orderId, drinkId, value) {
  ratingForm(orderId, drinkId).rating = value
}

async function submitRating(orderId, drinkId) {
  const form = ratingForm(orderId, drinkId)
  if (form.rating < 1) {
    form.error = 'Vui lòng chọn số sao.'
    return
  }

  form.isSubmitting = true
  form.error = ''
  form.success = false

  try {
    const { data } = await ratingsApi.create({
      order_id: orderId,
      drink_id: drinkId,
      rating: form.rating,
      comment: form.comment || null,
    })
    ratingsByKey[ratingKey(orderId, drinkId)] = data.data
    form.success = true
  } catch (error) {
    form.error = error.response?.data?.message || 'Gửi đánh giá thất bại.'
  } finally {
    form.isSubmitting = false
  }
}

async function onLogout() {
  await auth.logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <AppNavbar :cart-count="0" :show-cart="false">
      <RouterLink :to="{ name: 'home' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Menu
      </RouterLink>
      <RouterLink :to="{ name: 'preferences' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Sở thích của tôi
      </RouterLink>
      <RouterLink :to="{ name: 'order-history' }" class="text-sm font-semibold text-primary">
        Lịch sử đơn hàng
      </RouterLink>
      <template v-if="auth.user?.role === 'admin'">
        <RouterLink :to="{ name: 'admin-menu' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
          Quản lý menu
        </RouterLink>
        <RouterLink :to="{ name: 'admin-orders' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
          Quản lý đơn hàng
        </RouterLink>
        <RouterLink :to="{ name: 'admin-reports' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
          Báo cáo
        </RouterLink>
      </template>

      <template #actions>
        <div class="hidden md:block text-right leading-tight">
          <p class="text-xs font-semibold text-foreground">{{ auth.user?.name }}</p>
          <p class="text-[10px] text-muted-foreground uppercase tracking-wide">{{ auth.user?.role }}</p>
        </div>
        <button
          type="button"
          class="text-xs font-semibold text-muted-foreground hover:text-destructive border border-border rounded-lg px-3 py-1.5 transition-colors"
          @click="onLogout"
        >
          Đăng xuất
        </button>
      </template>
    </AppNavbar>

    <div class="max-w-[860px] mx-auto px-4 md:px-6 py-8 flex flex-col gap-6">
      <div>
        <h1 class="font-heading font-bold text-2xl text-foreground">Lịch sử đơn hàng</h1>
        <p class="text-sm text-muted-foreground mt-1">Theo dõi trạng thái đơn và đánh giá món đã đặt.</p>
      </div>

      <p v-if="isLoading" class="text-sm text-muted-foreground">Đang tải…</p>

      <p v-else-if="errorMessage" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
        {{ errorMessage }}
      </p>

      <BaseCard v-else-if="orders.length === 0" padding="lg" class="flex flex-col items-center text-center py-14">
        <PackageOpen :size="40" class="text-border mb-3" />
        <p class="text-foreground font-semibold">Bạn chưa có đơn hàng nào</p>
        <p class="text-sm text-muted-foreground mt-1 mb-5">Ghé menu và chọn thức uống yêu thích của bạn nhé.</p>
        <BaseButton :full-width="false" @click="router.push({ name: 'home' })">Đặt đồ ngay</BaseButton>
      </BaseCard>

      <div v-else class="flex flex-col gap-4">
        <BaseCard v-for="order in orders" :key="order.id" padding="lg">
          <div class="flex items-start justify-between gap-3 pb-4 border-b border-border">
            <div class="flex flex-col gap-1">
              <span class="font-heading font-bold text-foreground">Đơn #{{ order.id }}</span>
              <div class="flex items-center gap-2 flex-wrap text-xs text-muted-foreground">
                <span v-if="orderTypeLabel(order)" class="font-semibold text-foreground/80">{{ orderTypeLabel(order) }}</span>
                <span v-if="orderTypeLabel(order)">·</span>
                <span>{{ new Date(order.created_at).toLocaleString('vi-VN') }}</span>
              </div>
            </div>
            <span
              class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
              :class="statusBadgeClass(order.status)"
            >
              {{ statusLabel(order.status) }}
            </span>
          </div>

          <ul class="flex flex-col gap-4 py-4">
            <li v-for="item in order.items" :key="item.id" class="flex flex-col gap-2">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-secondary flex items-center justify-center text-base shrink-0">
                  {{ getCategoryEmoji(item.drink_category) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-foreground truncate">{{ item.quantity }}× {{ item.drink_name }}</p>
                  <p class="text-xs text-muted-foreground">{{ item.sugar_level }}% đường · {{ item.ice_level }}</p>
                </div>
                <span class="text-sm font-bold text-primary shrink-0">
                  {{ Number(item.subtotal).toLocaleString('vi-VN') }}đ
                </span>
              </div>

              <div v-if="order.status === 'done'" class="ml-12 bg-muted/60 border border-dashed border-border rounded-xl px-3.5 py-3 flex flex-col gap-2.5">
                <div class="flex items-center gap-1">
                  <button
                    v-for="star in 5"
                    :key="star"
                    type="button"
                    class="transition-colors"
                    :class="star <= ratingForm(order.id, item.drink_id).rating ? 'text-amber-500' : 'text-muted-foreground/30 hover:text-amber-300'"
                    @click="setStars(order.id, item.drink_id, star)"
                  >
                    <Star :size="18" :fill="star <= ratingForm(order.id, item.drink_id).rating ? 'currentColor' : 'none'" />
                  </button>
                  <span v-if="ratingsByKey[ratingKey(order.id, item.drink_id)]" class="ml-2 text-xs font-semibold text-success">
                    Đã đánh giá
                  </span>
                </div>

                <input
                  v-model="ratingForm(order.id, item.drink_id).comment"
                  type="text"
                  placeholder="Nhận xét (tuỳ chọn)"
                  class="text-xs border border-border rounded-lg px-3 py-2 bg-input-background text-foreground outline-none focus:ring-2 focus:ring-primary/30 placeholder-gray-400"
                />

                <BaseButton
                  :full-width="false"
                  variant="secondary"
                  :disabled="ratingForm(order.id, item.drink_id).isSubmitting"
                  @click="submitRating(order.id, item.drink_id)"
                >
                  {{
                    ratingForm(order.id, item.drink_id).isSubmitting
                      ? 'Đang gửi…'
                      : ratingsByKey[ratingKey(order.id, item.drink_id)]
                        ? 'Cập nhật đánh giá'
                        : 'Gửi đánh giá'
                  }}
                </BaseButton>

                <p v-if="ratingForm(order.id, item.drink_id).error" class="text-xs text-destructive">
                  {{ ratingForm(order.id, item.drink_id).error }}
                </p>
                <p v-if="ratingForm(order.id, item.drink_id).success" class="text-xs text-success">
                  Cảm ơn bạn đã đánh giá!
                </p>
              </div>
            </li>
          </ul>

          <div class="flex items-center justify-between gap-3 pt-4 border-t border-border">
            <span class="font-heading font-bold text-foreground">
              Tổng: <span class="text-primary">{{ Number(order.total_price).toLocaleString('vi-VN') }}đ</span>
            </span>
            <BaseButton
              v-if="order.status === 'pending'"
              :full-width="false"
              variant="danger"
              :disabled="cancellingId === order.id"
              @click="cancelOrder(order)"
            >
              {{ cancellingId === order.id ? 'Đang huỷ…' : 'Huỷ đơn' }}
            </BaseButton>
          </div>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
