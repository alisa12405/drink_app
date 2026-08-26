<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ordersApi, ratingsApi } from '@/services/api'
import { ORDER_STATUS_LABELS } from '@/constants/drinkOptions'

const router = useRouter()
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

function backToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="history-page">
    <div class="card">
      <button type="button" class="back" @click="backToHome">← Về trang chủ</button>
      <p class="eyebrow">UC-06</p>
      <h1>Lịch sử đơn hàng</h1>

      <p v-if="isLoading">Đang tải…</p>
      <p v-else-if="errorMessage" class="error">{{ errorMessage }}</p>
      <p v-else-if="orders.length === 0" class="empty">Bạn chưa có đơn hàng nào.</p>

      <ul v-else class="orders">
        <li v-for="order in orders" :key="order.id">
          <div class="order-header">
            <div>
              <strong>Đơn #{{ order.id }}</strong>
              <span class="date">{{ new Date(order.created_at).toLocaleString('vi-VN') }}</span>
            </div>
            <span class="status" :class="`status-${order.status}`">{{ statusLabel(order.status) }}</span>
          </div>

          <ul class="order-items">
            <li v-for="item in order.items" :key="item.id" class="order-item">
              <div class="order-item-line">
                <span>
                  {{ item.quantity }}× {{ item.drink_name }}
                  <span class="item-options">({{ item.sugar_level }}% đường, {{ item.ice_level }})</span>
                </span>
                <span>{{ Number(item.subtotal).toLocaleString('vi-VN') }}đ</span>
              </div>

              <div v-if="order.status === 'done'" class="rating-box">
                <div class="stars">
                  <button
                    v-for="star in 5"
                    :key="star"
                    type="button"
                    class="star"
                    :class="{ filled: star <= ratingForm(order.id, item.drink_id).rating }"
                    @click="setStars(order.id, item.drink_id, star)"
                  >
                    ★
                  </button>
                  <span v-if="ratingsByKey[`${order.id}:${item.drink_id}`]" class="rated-badge">
                    Đã đánh giá
                  </span>
                </div>

                <input
                  v-model="ratingForm(order.id, item.drink_id).comment"
                  type="text"
                  class="comment-input"
                  placeholder="Nhận xét (tuỳ chọn)"
                />

                <button
                  type="button"
                  class="rate-btn"
                  :disabled="ratingForm(order.id, item.drink_id).isSubmitting"
                  @click="submitRating(order.id, item.drink_id)"
                >
                  {{
                    ratingForm(order.id, item.drink_id).isSubmitting
                      ? 'Đang gửi…'
                      : ratingsByKey[`${order.id}:${item.drink_id}`]
                        ? 'Cập nhật đánh giá'
                        : 'Gửi đánh giá'
                  }}
                </button>

                <p v-if="ratingForm(order.id, item.drink_id).error" class="rating-error">
                  {{ ratingForm(order.id, item.drink_id).error }}
                </p>
                <p v-if="ratingForm(order.id, item.drink_id).success" class="rating-success">
                  Cảm ơn bạn đã đánh giá!
                </p>
              </div>
            </li>
          </ul>

          <div class="order-footer">
            <strong>Tổng: {{ Number(order.total_price).toLocaleString('vi-VN') }}đ</strong>
            <button
              v-if="order.status === 'pending'"
              type="button"
              class="cancel-btn"
              :disabled="cancellingId === order.id"
              @click="cancelOrder(order)"
            >
              {{ cancellingId === order.id ? 'Đang huỷ…' : 'Huỷ đơn' }}
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.history-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: 2rem 1.25rem 3rem;
}

.card {
  width: min(100%, 680px);
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 16px;
  padding: 1.75rem;
  box-shadow: 0 12px 40px rgba(74, 44, 23, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.back {
  align-self: flex-start;
  background: none;
  border: 0;
  color: #9a3412;
  padding: 0;
  font: inherit;
  cursor: pointer;
}

.eyebrow {
  color: #b45309;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

h1 {
  font-size: 1.6rem;
  color: #3f2a1d;
}

.empty {
  color: #6b5848;
}

.orders {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 1rem;
}

.orders > li {
  border: 1px solid #eadfce;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.order-header div {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.date {
  color: #6b5848;
  font-size: 0.8rem;
}

.status {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: #fff7ed;
  color: #9a3412;
}

.status-done {
  background: #f0fdf4;
  color: #15803d;
}

.status-cancelled {
  background: #fef2f2;
  color: #b91c1c;
}

.status-confirmed {
  background: #eff6ff;
  color: #1d4ed8;
}

.order-items {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 0.6rem;
  font-size: 0.9rem;
  color: #4a3728;
}

.order-item {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.order-item-line {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

.item-options {
  color: #6b5848;
  font-size: 0.8rem;
}

.rating-box {
  background: #fffaf3;
  border: 1px dashed #eadfce;
  border-radius: 10px;
  padding: 0.6rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.stars {
  display: flex;
  align-items: center;
  gap: 0.15rem;
}

.star {
  background: none;
  border: none;
  font-size: 1.3rem;
  color: #d6d3d1;
  cursor: pointer;
  padding: 0 0.05rem;
  line-height: 1;
}

.star.filled {
  color: #f59e0b;
}

.rated-badge {
  margin-left: 0.5rem;
  font-size: 0.75rem;
  color: #15803d;
  font-weight: 600;
}

.comment-input {
  border: 1px solid #e0d3c2;
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font: inherit;
  font-size: 0.85rem;
  color: #3f2a1d;
  background: #fffdf8;
}

.rate-btn {
  align-self: flex-start;
  background: #fff7ed;
  color: #9a3412;
  border: 1px solid #fed7aa;
  border-radius: 8px;
  padding: 0.4rem 0.8rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.rate-btn:disabled {
  opacity: 0.6;
  cursor: wait;
}

.rating-error {
  color: #b91c1c;
  font-size: 0.8rem;
}

.rating-success {
  color: #15803d;
  font-size: 0.8rem;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px dashed #eadfce;
  padding-top: 0.6rem;
}

.cancel-btn {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.4rem 0.7rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.cancel-btn:disabled {
  opacity: 0.6;
  cursor: wait;
}

.error {
  color: #b91c1c;
}
</style>
