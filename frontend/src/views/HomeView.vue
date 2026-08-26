<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { drinksApi, ordersApi } from '@/services/api'
import { SUGAR_OPTIONS, ICE_OPTIONS } from '@/constants/drinkOptions'

const router = useRouter()
const auth = useAuthStore()
const drinks = ref([])
const loadError = ref('')

const cart = ref([])
const occasion = ref('')
const isPlacingOrder = ref(false)
const orderError = ref('')
const lastOrder = ref(null)

onMounted(async () => {
  try {
    const { data } = await drinksApi.list()
    drinks.value = data.data ?? []
  } catch {
    loadError.value = 'Không tải được menu. Kiểm tra backend còn chạy không.'
  }
})

async function onLogout() {
  await auth.logout()
  await router.replace({ name: 'login' })
}

function goToPreferences() {
  router.push({ name: 'preferences' })
}

function goToOrderHistory() {
  router.push({ name: 'order-history' })
}

function addToCart(drink) {
  const existing = cart.value.find(
    (item) => item.drink_id === drink.id && item.sugar_level === '100' && item.ice_level === 'normal_ice',
  )
  if (existing) {
    existing.quantity += 1
    return
  }
  cart.value.push({
    drink_id: drink.id,
    name: drink.name,
    price: Number(drink.price),
    quantity: 1,
    sugar_level: '100',
    ice_level: 'normal_ice',
    note: '',
  })
}

function removeFromCart(index) {
  cart.value.splice(index, 1)
}

function changeQuantity(item, delta) {
  item.quantity = Math.max(1, Math.min(20, item.quantity + delta))
}

const cartTotal = computed(() =>
  cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
)

async function placeOrder() {
  if (cart.value.length === 0) return

  orderError.value = ''
  lastOrder.value = null
  isPlacingOrder.value = true

  try {
    const { data } = await ordersApi.create({
      items: cart.value.map((item) => ({
        drink_id: item.drink_id,
        quantity: item.quantity,
        sugar_level: item.sugar_level,
        ice_level: item.ice_level,
        note: item.note || null,
      })),
      occasion: occasion.value || null,
    })
    lastOrder.value = data.data
    cart.value = []
    occasion.value = ''
  } catch (error) {
    orderError.value = error.response?.data?.message || 'Đặt hàng thất bại. Vui lòng thử lại.'
  } finally {
    isPlacingOrder.value = false
  }
}
</script>

<template>
  <div class="home">
    <header>
      <div>
        <p class="eyebrow">Đã đăng nhập</p>
        <h1>{{ auth.user?.name }}</h1>
        <p>{{ auth.user?.email }} · {{ auth.user?.role }}</p>
      </div>
      <div class="actions">
        <button type="button" class="ghost" @click="goToPreferences">Sở thích của tôi</button>
        <button type="button" class="ghost" @click="goToOrderHistory">Lịch sử đơn hàng</button>
        <button type="button" @click="onLogout">Đăng xuất</button>
      </div>
    </header>

    <div class="layout">
      <section class="menu">
        <h2>Menu thử nghiệm</h2>
        <p v-if="loadError" class="error">{{ loadError }}</p>
        <ul v-else>
          <li v-for="drink in drinks" :key="drink.id">
            <div>
              <strong>{{ drink.name }}</strong>
              <span>{{ drink.category }} · {{ Number(drink.price).toLocaleString('vi-VN') }}đ</span>
            </div>
            <button type="button" class="add-btn" @click="addToCart(drink)">+ Thêm</button>
          </li>
        </ul>
      </section>

      <aside class="cart">
        <h2>Giỏ hàng (UC-05)</h2>

        <p v-if="cart.length === 0" class="empty">Chưa có món nào. Bấm "+ Thêm" ở menu bên trái.</p>

        <ul v-else class="cart-items">
          <li v-for="(item, index) in cart" :key="index">
            <div class="cart-item-header">
              <strong>{{ item.name }}</strong>
              <button type="button" class="remove-btn" @click="removeFromCart(index)">✕</button>
            </div>

            <div class="cart-item-row">
              <div class="qty-stepper">
                <button type="button" @click="changeQuantity(item, -1)">−</button>
                <span>{{ item.quantity }}</span>
                <button type="button" @click="changeQuantity(item, 1)">+</button>
              </div>
              <span class="subtotal">{{ (item.price * item.quantity).toLocaleString('vi-VN') }}đ</span>
            </div>

            <div class="cart-item-row">
              <select v-model="item.sugar_level">
                <option v-for="option in SUGAR_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <select v-model="item.ice_level">
                <option v-for="option in ICE_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <input v-model="item.note" type="text" placeholder="Ghi chú (tuỳ chọn)" class="note-input" />
          </li>
        </ul>

        <label class="occasion-label">
          Dịp (tuỳ chọn)
          <input v-model="occasion" type="text" placeholder="ví dụ: sau khi tập gym" />
        </label>

        <div class="cart-footer">
          <span>Tổng cộng</span>
          <strong>{{ cartTotal.toLocaleString('vi-VN') }}đ</strong>
        </div>

        <p v-if="orderError" class="error">{{ orderError }}</p>

        <button
          type="button"
          class="checkout-btn"
          :disabled="cart.length === 0 || isPlacingOrder"
          @click="placeOrder"
        >
          {{ isPlacingOrder ? 'Đang đặt hàng…' : 'Đặt hàng' }}
        </button>

        <div v-if="lastOrder" class="success">
          Đặt hàng thành công! Mã đơn #{{ lastOrder.id }} ·
          {{ Number(lastOrder.total_price).toLocaleString('vi-VN') }}đ.
          <button type="button" class="link-btn" @click="goToOrderHistory">Xem lịch sử đơn hàng</button>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.home {
  max-width: 1080px;
  margin: 0 auto;
  padding: 2rem 1.25rem 3rem;
}

header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.actions {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.eyebrow {
  color: #b45309;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

h1,
h2 {
  color: #3f2a1d;
}

header p {
  color: #6b5848;
}

button {
  border: 0;
  border-radius: 10px;
  padding: 0.6rem 0.9rem;
  background: #fff7ed;
  color: #9a3412;
  border: 1px solid #fed7aa;
  font: inherit;
  cursor: pointer;
}

button.ghost {
  background: #fff;
}

.layout {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 800px) {
  .layout {
    grid-template-columns: 1fr;
  }
}

ul {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 0.6rem;
}

.menu li {
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 12px;
  padding: 0.85rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.menu li div {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.menu li span {
  color: #6b5848;
  font-size: 0.9rem;
}

.add-btn {
  white-space: nowrap;
}

.cart {
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 16px;
  padding: 1.25rem;
  position: sticky;
  top: 1.5rem;
}

.empty {
  color: #6b5848;
  font-size: 0.9rem;
}

.cart-items {
  gap: 0.9rem;
}

.cart-items > li {
  border-bottom: 1px dashed #eadfce;
  padding-bottom: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.cart-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.remove-btn {
  background: none;
  border: none;
  color: #b91c1c;
  padding: 0 0.25rem;
}

.cart-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
}

.qty-stepper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-stepper button {
  padding: 0.2rem 0.6rem;
}

.subtotal {
  font-weight: 600;
  color: #3f2a1d;
}

select,
.note-input,
.occasion-label input {
  border: 1px solid #e0d3c2;
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font: inherit;
  font-size: 0.85rem;
  color: #3f2a1d;
  background: #fffdf8;
  flex: 1;
}

.occasion-label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.85rem;
  color: #4a3728;
  margin-top: 0.9rem;
}

.cart-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
  padding-top: 0.9rem;
  border-top: 1px solid #eadfce;
  font-size: 1.05rem;
  color: #3f2a1d;
}

.checkout-btn {
  width: 100%;
  margin-top: 0.9rem;
  background: #b45309;
  color: #fff;
  font-weight: 600;
  padding: 0.75rem 1rem;
}

.checkout-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.link-btn {
  background: none;
  border: none;
  color: #15803d;
  text-decoration: underline;
  padding: 0;
  margin-left: 0.4rem;
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
  margin-top: 0.9rem;
}

.success {
  background: #f0fdf4;
  color: #15803d;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
  margin-top: 0.9rem;
}
</style>
