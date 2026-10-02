<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { PackageOpen } from 'lucide-vue-next'
import { adminOrdersApi } from '@/services/api'
import {
  ORDER_STATUS_LABELS,
  ORDER_STATUS_BADGE_CLASSES,
  getCategoryEmoji,
  getOrderTypeInfo,
} from '@/constants/drinkOptions'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'


const STATUS_FILTERS = [
  { value: '', label: 'Tất cả' },
  { value: 'pending', label: ORDER_STATUS_LABELS.pending },
  { value: 'confirmed', label: ORDER_STATUS_LABELS.confirmed },
  { value: 'done', label: ORDER_STATUS_LABELS.done },
  { value: 'cancelled', label: ORDER_STATUS_LABELS.cancelled },
]

const NEXT_ACTIONS = {
  pending: [
    { status: 'confirmed', label: 'Xác nhận', variant: 'success' },
    { status: 'cancelled', label: 'Huỷ đơn', variant: 'danger' },
  ],
  confirmed: [
    { status: 'done', label: 'Hoàn tất', variant: 'success' },
    { status: 'cancelled', label: 'Huỷ đơn', variant: 'danger' },
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

function statusBadgeClass(status) {
  return ORDER_STATUS_BADGE_CLASSES[status] ?? 'bg-secondary text-secondary-foreground'
}

function orderTypeLabel(order) {
  const info = getOrderTypeInfo(order.context_snapshot?.order_type)
  return info ? `${info.icon} ${info.label}` : null
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

</script>

<template>
  <div class="min-h-screen bg-background">
    <AppNavbar :cart-count="0" :show-cart="false" />

    <div class="max-w-[900px] mx-auto px-4 md:px-6 py-8 flex flex-col gap-6">
      <div>
        <p class="text-xs font-semibold text-primary uppercase tracking-wide">Quản trị</p>
        <h1 class="font-heading font-bold text-2xl text-foreground mt-1">Quản lý đơn hàng</h1>
        <p class="text-sm text-muted-foreground mt-1">Theo dõi và cập nhật trạng thái các đơn hàng của khách.</p>
      </div>

      <BaseCard padding="sm" class="flex items-center justify-between gap-3 flex-wrap">
        <label class="flex items-center gap-2.5 text-sm text-foreground">
          <span class="font-medium text-muted-foreground">Lọc theo trạng thái</span>
          <select
            v-model="statusFilter"
            class="text-sm border border-border rounded-xl bg-input-background px-3 py-2 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/60"
            @change="onFilterChange"
          >
            <option v-for="option in STATUS_FILTERS" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </label>
        <span class="text-sm text-muted-foreground">Tổng: <strong class="text-foreground">{{ meta.total }}</strong> đơn</span>
      </BaseCard>

      <p v-if="isLoading" class="text-sm text-muted-foreground">Đang tải…</p>
      <p v-else-if="listError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
        {{ listError }}
      </p>

      <BaseCard v-else-if="orders.length === 0" padding="lg" class="flex flex-col items-center text-center py-14">
        <PackageOpen :size="40" class="text-border mb-3" />
        <p class="text-foreground font-semibold">Không có đơn hàng nào</p>
        <p class="text-sm text-muted-foreground mt-1">Chưa có đơn nào khớp với bộ lọc hiện tại.</p>
      </BaseCard>

      <div v-else class="flex flex-col gap-4">
        <BaseCard v-for="order in orders" :key="order.id" padding="lg">
          <div class="flex items-start justify-between gap-3 pb-4 border-b border-border">
            <div class="flex flex-col gap-1 min-w-0">
              <span class="font-heading font-bold text-foreground">Đơn #{{ order.id }}</span>
              <span class="text-xs text-muted-foreground truncate">
                <template v-if="order.user">{{ order.user.name }} · {{ order.user.email }}</template>
                <template v-else>Khách vãng lai · {{ order.customer_name }}</template>
              </span>
              <span class="text-xs text-muted-foreground">{{ new Date(order.created_at).toLocaleString('vi-VN') }}</span>
            </div>
            <div class="flex flex-col items-end gap-1.5 shrink-0">
              <span
                v-if="orderTypeLabel(order)"
                class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap bg-secondary text-secondary-foreground"
              >
                {{ orderTypeLabel(order) }}
              </span>
              <span
                class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                :class="statusBadgeClass(order.status)"
              >
                {{ statusLabel(order.status) }}
              </span>
            </div>
          </div>

          <p v-if="order.context_snapshot?.occasion" class="text-sm text-muted-foreground bg-muted/60 border border-dashed border-border rounded-xl px-3.5 py-2.5 mt-4">
            📝 {{ order.context_snapshot.occasion }}
          </p>

          <ul class="flex flex-col gap-2.5 py-4">
            <li v-for="item in order.items" :key="item.id" class="flex items-center gap-3">
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
            </li>
          </ul>

          <div class="flex items-center justify-between gap-3 pt-4 border-t border-border flex-wrap">
            <span class="font-heading font-bold text-foreground">
              Tổng: <span class="text-primary">{{ Number(order.total_price).toLocaleString('vi-VN') }}đ</span>
            </span>

            <div class="flex gap-2 flex-wrap">
              <span v-if="nextActionsFor(order).length === 0" class="text-xs text-muted-foreground">Không còn thao tác</span>
              <BaseButton
                v-for="action in nextActionsFor(order)"
                :key="action.status"
                :full-width="false"
                :variant="action.variant"
                :disabled="updatingKey === `${order.id}:${action.status}`"
                @click="changeStatus(order, action.status)"
              >
                {{ updatingKey === `${order.id}:${action.status}` ? 'Đang xử lý…' : action.label }}
              </BaseButton>
            </div>
          </div>
        </BaseCard>
      </div>

      <div v-if="!isLoading && orders.length > 0" class="flex items-center justify-center gap-4 text-sm text-foreground">
        <BaseButton :full-width="false" variant="ghost" class="border border-border" :disabled="!canGoPrev" @click="goToPage(meta.current_page - 1)">
          ← Trước
        </BaseButton>
        <span class="text-muted-foreground">Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
        <BaseButton :full-width="false" variant="ghost" class="border border-border" :disabled="!canGoNext" @click="goToPage(meta.current_page + 1)">
          Sau →
        </BaseButton>
      </div>
    </div>
  </div>
</template>
