<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ChevronLeft, ChevronRight, Pencil, Search, Users } from 'lucide-vue-next'
import { adminUsersApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import FormField from '@/components/ui/FormField.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'

const auth = useAuthStore()
const users = ref([])
const isLoading = ref(true)
const listError = ref('')
const formError = ref('')
const successMessage = ref('')
const fieldErrors = ref({})
const isSaving = ref(false)
const editingId = ref(null)
const filters = reactive({ search: '', role: '', status: '' })
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const form = reactive({ name: '', email: '', role: 'customer', is_active: true })

async function loadUsers(page = 1) {
  isLoading.value = true
  listError.value = ''

  try {
    const params = {
      page,
      ...(filters.search.trim() ? { search: filters.search.trim() } : {}),
      ...(filters.role ? { role: filters.role } : {}),
      ...(filters.status ? { status: filters.status } : {}),
    }
    const { data } = await adminUsersApi.list(params)
    users.value = data.data ?? []
    Object.assign(meta, {
      current_page: data.meta?.current_page ?? 1,
      last_page: data.meta?.last_page ?? 1,
      total: data.meta?.total ?? users.value.length,
    })
  } catch (error) {
    listError.value = error.response?.data?.message || 'Không tải được danh sách tài khoản.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => loadUsers())

function applyFilters() {
  loadUsers(1)
}

function startEditing(user) {
  editingId.value = user.id
  Object.assign(form, {
    name: user.name,
    email: user.email,
    role: user.role,
    is_active: user.is_active,
  })
  formError.value = ''
  fieldErrors.value = {}
  successMessage.value = ''
}

function cancelEditing() {
  editingId.value = null
  formError.value = ''
  fieldErrors.value = {}
}

function fieldError(field) {
  return fieldErrors.value[field]?.[0] ?? ''
}

function roleLabel(role) {
  return role === 'admin' ? 'Quản trị viên' : 'Khách hàng'
}

function statusLabel(isActive) {
  return isActive ? 'Đang hoạt động' : 'Đã vô hiệu hóa'
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('vi-VN') : '—'
}

async function saveUser() {
  if (editingId.value === null) return

  isSaving.value = true
  formError.value = ''
  fieldErrors.value = {}
  successMessage.value = ''

  try {
    const payload = {
      name: form.name,
      email: form.email,
      role: form.role,
      is_active: form.is_active,
    }
    const { data } = await adminUsersApi.update(editingId.value, payload)
    const updatedUser = data.data

    if (auth.user?.id === updatedUser.id) {
      auth.user = { ...auth.user, ...updatedUser }
      localStorage.setItem('auth_user', JSON.stringify(auth.user))
    }

    editingId.value = null
    successMessage.value = 'Đã cập nhật tài khoản.'
    await loadUsers(meta.current_page)
  } catch (error) {
    fieldErrors.value = error.response?.data?.errors ?? {}
    formError.value = error.response?.data?.message || 'Không thể cập nhật tài khoản.'
  } finally {
    isSaving.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > meta.last_page) return
  loadUsers(page)
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <AppNavbar :cart-count="0" :show-cart="false" />

    <main class="mx-auto flex max-w-[1100px] flex-col gap-6 px-4 py-8 md:px-6">
      <header>
        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Quản trị</p>
        <h1 class="mt-1 font-heading text-2xl font-bold text-foreground">Quản lý tài khoản</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Tìm kiếm tài khoản, cập nhật thông tin và quyền truy cập. Tài khoản bị vô hiệu hóa vẫn giữ nguyên lịch sử đơn hàng.
        </p>
      </header>

      <BaseCard padding="sm" class="flex items-end gap-3 flex-wrap">
        <FormField
          v-model="filters.search"
          label="Tìm theo tên hoặc email"
          placeholder="Nhập tên hoặc email"
          class="min-w-[220px] flex-1"
          @keyup.enter="applyFilters"
        />
        <label class="flex flex-col gap-1.5 text-xs font-semibold text-muted-foreground">
          Vai trò
          <select v-model="filters.role" class="rounded-xl border border-border bg-input-background px-3.5 py-2.5 text-sm text-foreground">
            <option value="">Tất cả</option>
            <option value="customer">Khách hàng</option>
            <option value="admin">Quản trị viên</option>
          </select>
        </label>
        <label class="flex flex-col gap-1.5 text-xs font-semibold text-muted-foreground">
          Trạng thái
          <select v-model="filters.status" class="rounded-xl border border-border bg-input-background px-3.5 py-2.5 text-sm text-foreground">
            <option value="">Tất cả</option>
            <option value="active">Đang hoạt động</option>
            <option value="inactive">Đã vô hiệu hóa</option>
          </select>
        </label>
        <BaseButton :full-width="false" @click="applyFilters">
          <Search :size="16" /> Tìm kiếm
        </BaseButton>
      </BaseCard>

      <p v-if="successMessage" class="rounded-xl border border-success/20 bg-success-bg px-3.5 py-2.5 text-sm text-success">
        {{ successMessage }}
      </p>

      <BaseCard v-if="editingId !== null" padding="lg" class="flex flex-col gap-4">
        <SectionHeader title="Chỉnh sửa tài khoản" />
        <p v-if="formError" class="rounded-xl bg-destructive-bg px-3.5 py-2.5 text-sm text-destructive">
          {{ formError }}
        </p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField v-model="form.name" label="Họ và tên" :error="fieldError('name')" required />
          <FormField v-model="form.email" label="Email" type="email" :error="fieldError('email')" required />
          <label class="flex flex-col gap-1.5 text-xs font-semibold text-muted-foreground">
            Vai trò
            <select
              v-model="form.role"
              :disabled="editingId === auth.user?.id"
              class="rounded-xl border border-border bg-input-background px-3.5 py-2.5 text-sm text-foreground disabled:cursor-not-allowed disabled:opacity-60"
            >
              <option value="customer">Khách hàng</option>
              <option value="admin">Quản trị viên</option>
            </select>
          </label>
          <label class="flex items-center gap-2.5 self-end rounded-xl border border-border px-3.5 py-3 text-sm text-foreground">
            <input
              v-model="form.is_active"
              type="checkbox"
              :disabled="editingId === auth.user?.id"
              class="size-4 accent-primary disabled:cursor-not-allowed"
            />
            Tài khoản đang hoạt động
          </label>
        </div>
        <p v-if="editingId === auth.user?.id" class="text-xs text-muted-foreground">
          Bạn không thể tự hạ quyền hoặc vô hiệu hóa tài khoản quản trị hiện tại.
        </p>
        <div class="flex flex-wrap justify-end gap-2">
          <BaseButton variant="secondary" :full-width="false" :disabled="isSaving" @click="cancelEditing">Hủy</BaseButton>
          <BaseButton :full-width="false" :disabled="isSaving" @click="saveUser">
            {{ isSaving ? 'Đang lưu…' : 'Lưu thay đổi' }}
          </BaseButton>
        </div>
      </BaseCard>

      <BaseCard padding="lg" class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <SectionHeader title="Danh sách tài khoản" />
          <span class="text-sm text-muted-foreground">Tổng: <strong class="text-foreground">{{ meta.total }}</strong></span>
        </div>

        <p v-if="isLoading" class="text-sm text-muted-foreground">Đang tải tài khoản…</p>
        <p v-else-if="listError" class="rounded-xl bg-destructive-bg px-3.5 py-2.5 text-sm text-destructive">{{ listError }}</p>
        <div v-else-if="users.length === 0" class="flex flex-col items-center gap-2 py-10 text-center">
          <Users :size="36" class="text-border" />
          <p class="font-semibold text-foreground">Không tìm thấy tài khoản</p>
          <p class="text-sm text-muted-foreground">Thử thay đổi từ khóa hoặc bộ lọc.</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-[760px] w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-border">
                <th class="px-2 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tài khoản</th>
                <th class="px-2 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Vai trò</th>
                <th class="px-2 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Trạng thái</th>
                <th class="px-2 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Đơn hàng</th>
                <th class="px-2 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Ngày tạo</th>
                <th class="px-2 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="border-b border-border/60">
                <td class="px-2 py-3 text-foreground">
                  <p class="font-semibold">{{ user.name }}</p>
                  <p class="mt-0.5 text-xs text-muted-foreground">{{ user.email }}</p>
                </td>
                <td class="px-2 py-3 text-foreground">{{ roleLabel(user.role) }}</td>
                <td class="px-2 py-3">
                  <span
                    class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="user.is_active ? 'bg-success-bg text-success' : 'bg-muted text-muted-foreground'"
                  >
                    {{ statusLabel(user.is_active) }}
                  </span>
                </td>
                <td class="px-2 py-3 text-right text-foreground">{{ user.orders_count ?? 0 }}</td>
                <td class="px-2 py-3 text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                <td class="px-2 py-3 text-right">
                  <BaseButton variant="secondary" :full-width="false" @click="startEditing(user)">
                    <Pencil :size="15" /> Sửa
                  </BaseButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!isLoading && !listError && meta.last_page > 1" class="flex items-center justify-between border-t border-border pt-4">
          <span class="text-xs text-muted-foreground">Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
          <div class="flex gap-2">
            <BaseButton variant="secondary" :full-width="false" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">
              <ChevronLeft :size="16" /> Trước
            </BaseButton>
            <BaseButton variant="secondary" :full-width="false" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">
              Tiếp <ChevronRight :size="16" />
            </BaseButton>
          </div>
        </div>
      </BaseCard>
    </main>
  </div>
</template>
