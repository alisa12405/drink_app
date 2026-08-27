<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { adminOrdersApi } from '@/services/api'
import { ORDER_STATUS_LABELS } from '@/constants/drinkOptions'

const router = useRouter()

const STATUS_FILTERS = [
  { value: '', label: 'Tất cả' },
  { value: 'pending', label: ORDER_STATUS_LABELS.pending },
  { value: 'confirmed', label: ORDER_STATUS_LABELS.confirmed },
  { value: 'done', label: ORDER_STATUS_LABELS.done },
  { value: 'cancelled', label: ORDER_STATUS_LABELS.cancelled },
]

const NEXT_ACTIONS = {
  pending: [
    { status: 'confirmed', label: 'Xác nhận', className: 'confirm' },
    { status: 'cancelled', label: 'Huỷ đơn', className: 'danger' },
  ],
  confirmed: [
    { status: 'done', label: 'Hoàn tất', className: 'confirm' },
    { status: 'cancelled', label: 'Huỷ đơn', className: 'danger' },
  ],
  done: [],
  cancelled: [],
}

const orders = ref([])
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const statusFilter = ref('')
const isLoading = ref(true)
const listError = ref('')
const updatingKey = ref('')

function statusLabel(status) {
  return ORDER_STATUS_LABELS[status] ?? status
}

function nextActionsFor(order) {
  return NEXT_ACTIONS[order.status] ?? []
}

async function loadOrders(page = 1) {
  isLoading.value = true
  listError.value = ''
  try {
    const { data } = await adminOrdersApi.list({
      page,
      ...(statusFilter.value ? { status: statusFilter.value } : {}),
    })
    orders.value = data.data ?? []
    Object.assign(meta, {
      current_page: data.meta?.current_page ?? 1,
      last_page: data.meta?.last_page ?? 1,
      total: data.meta?.total ?? orders.value.length,
    })
  } catch {
    listError.value = 'Không tải được danh sách đơn hàng.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => loadOrders())

function onFilterChange() {
  loadOrders(1)
}

function goToPage(page) {
  if (page < 1 || page > meta.last_page) return
  loadOrders(page)
}

const canGoPrev = computed(() => meta.current_page > 1)
const canGoNext = computed(() => meta.current_page < meta.last_page)

async function changeStatus(order, status) {
  const key = `${order.id}:${status}`
  updatingKey.value = key
  listError.value = ''
  try {
    await adminOrdersApi.updateStatus(order.id, status)
    await loadOrders(meta.current_page)
  } catch (error) {
    listError.value = error.response?.data?.message || 'Không thể cập nhật trạng thái đơn hàng.'
  } finally {
    updatingKey.value = ''
  }
}

function backToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="admin-orders-page">
    <div class="wrapper">
      <button type="button" class="back" @click="backToHome">← Về trang chủ</button>
      <p class="eyebrow">UC-09 (Admin)</p>
      <h1>Quản lý đơn hàng</h1>

      <div class="toolbar">
        <label>
          Lọc theo trạng thái
          <select v-model="statusFilter" @change="onFilterChange">
            <option v-for="option in STATUS_FILTERS" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </label>
        <span class="total">Tổng: {{ meta.total }} đơn</span>
      </div>

      <p v-if="isLoading">Đang tải…</p>
      <p v-else-if="listError" class="error">{{ listError }}</p>
      <p v-else-if="orders.length === 0" class="empty">Không có đơn hàng nào.</p>

      <ul v-else class="orders">
        <li v-for="order in orders" :key="order.id">
          <div class="order-header">
            <div>
              <strong>Đơn #{{ order.id }}</strong>
              <span class="customer">{{ order.user?.name }} · {{ order.user?.email }}</span>
              <span class="date">{{ new Date(order.created_at).toLocaleString('vi-VN') }}</span>
            </div>
            <span class="status" :class="`status-${order.status}`">{{ statusLabel(order.status) }}</span>
          </div>

          <ul class="order-items">
            <li v-for="item in order.items" :key="item.id">
              {{ item.quantity }}× {{ item.drink_name }}
              <span class="item-options">({{ item.sugar_level }}% đường, {{ item.ice_level }})</span>
              — {{ Number(item.subtotal).toLocaleString('vi-VN') }}đ
            </li>
          </ul>

          <div class="order-footer">
            <strong>Tổng: {{ Number(order.total_price).toLocaleString('vi-VN') }}đ</strong>

            <div class="actions">
              <span v-if="nextActionsFor(order).length === 0" class="no-action">Không còn thao tác</span>
              <button
                v-for="action in nextActionsFor(order)"
                :key="action.status"
                type="button"
                :class="action.className"
                :disabled="updatingKey === `${order.id}:${action.status}`"
                @click="changeStatus(order, action.status)"
              >
                {{
                  updatingKey === `${order.id}:${action.status}` ? 'Đang xử lý…' : action.label
                }}
              </button>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="!isLoading && orders.length > 0" class="pagination">
        <button type="button" :disabled="!canGoPrev" @click="goToPage(meta.current_page - 1)">← Trước</button>
        <span>Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button type="button" :disabled="!canGoNext" @click="goToPage(meta.current_page + 1)">Sau →</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-orders-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: 2rem 1.25rem 3rem;
}

.wrapper {
  width: min(100%, 800px);
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

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.6rem;
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 12px;
  padding: 0.85rem 1rem;
}

.toolbar label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.88rem;
  color: #4a3728;
}

.toolbar select {
  border: 1px solid #e0d3c2;
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font: inherit;
  background: #fffdf8;
}

.total {
  color: #6b5848;
  font-size: 0.85rem;
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
  background: #fff;
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
  align-items: flex-start;
  gap: 0.6rem;
}

.order-header div {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.customer {
  color: #4a3728;
  font-size: 0.85rem;
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
  white-space: nowrap;
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
  gap: 0.3rem;
  font-size: 0.9rem;
  color: #4a3728;
}

.item-options {
  color: #6b5848;
  font-size: 0.8rem;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.6rem;
  border-top: 1px dashed #eadfce;
  padding-top: 0.6rem;
}

.actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.no-action {
  color: #6b5848;
  font-size: 0.82rem;
}

.actions button {
  border: 1px solid #e0d3c2;
  background: #fffdf8;
  color: #3f2a1d;
  border-radius: 8px;
  padding: 0.4rem 0.75rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.actions button.confirm {
  border-color: #bbf7d0;
  background: #f0fdf4;
  color: #15803d;
}

.actions button.danger {
  border-color: #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.actions button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  color: #4a3728;
  font-size: 0.88rem;
}

.pagination button {
  border: 1px solid #e0d3c2;
  background: #fff;
  color: #3f2a1d;
  border-radius: 8px;
  padding: 0.4rem 0.8rem;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
}
</style>
