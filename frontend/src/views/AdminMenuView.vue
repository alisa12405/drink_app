<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Pencil, Search, Trash2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { adminDrinksApi } from '@/services/api'
import { TEMPERATURE_OPTIONS, getCategoryEmoji } from '@/constants/drinkOptions'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import FormField from '@/components/ui/FormField.vue'
import RadioCard from '@/components/ui/RadioCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const auth = useAuthStore()

const drinks = ref([])
const isLoading = ref(true)
const listError = ref('')
const categoryFilter = ref('')

const deletingId = ref(null)
const togglingId = ref(null)

const isSaving = ref(false)
const formError = ref('')
const fieldErrors = ref({})
const successMessage = ref('')

function emptyForm() {
  return {
    id: null,
    name: '',
    description: '',
    ingredients: '',
    category: '',
    price: '',
    calories: '',
    temperature_type: 'cold',
    tagsInput: '',
    image_url: '',
    is_available: true,
  }
}

const form = reactive(emptyForm())
const isEditing = computed(() => form.id !== null)

async function loadDrinks() {
  isLoading.value = true
  listError.value = ''
  try {
    const { data } = await adminDrinksApi.list(
      categoryFilter.value ? { category: categoryFilter.value } : undefined,
    )
    drinks.value = data.data ?? []
  } catch {
    listError.value = 'Không tải được danh sách món.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDrinks)

function resetForm() {
  Object.assign(form, emptyForm())
  formError.value = ''
  fieldErrors.value = {}
}

function editDrink(drink) {
  form.id = drink.id
  form.name = drink.name
  form.description = drink.description ?? ''
  form.ingredients = drink.ingredients ?? ''
  form.category = drink.category
  form.price = Number(drink.price)
  form.calories = drink.calories ?? ''
  form.temperature_type = drink.temperature_type
  form.tagsInput = (drink.tags ?? []).join(', ')
  form.image_url = drink.image_url ?? ''
  form.is_available = drink.is_available
  formError.value = ''
  fieldErrors.value = {}
  successMessage.value = ''
}

function buildPayload() {
  return {
    name: form.name,
    description: form.description || null,
    ingredients: form.ingredients || null,
    category: form.category,
    price: Number(form.price),
    calories: form.calories === '' ? null : Number(form.calories),
    temperature_type: form.temperature_type,
    tags: form.tagsInput
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean),
    image_url: form.image_url || null,
    is_available: form.is_available,
  }
}

async function onSubmit() {
  isSaving.value = true
  formError.value = ''
  fieldErrors.value = {}
  successMessage.value = ''

  try {
    if (isEditing.value) {
      await adminDrinksApi.update(form.id, buildPayload())
      successMessage.value = 'Đã cập nhật món.'
    } else {
      await adminDrinksApi.create(buildPayload())
      successMessage.value = 'Đã thêm món mới.'
    }
    await loadDrinks()
    resetForm()
  } catch (error) {
    if (error.response?.status === 422) {
      fieldErrors.value = error.response.data.errors || {}
    }
    formError.value = error.response?.data?.message || 'Lưu món thất bại. Vui lòng thử lại.'
  } finally {
    isSaving.value = false
  }
}

async function toggleAvailability(drink) {
  togglingId.value = drink.id
  try {
    await adminDrinksApi.update(drink.id, { is_available: !drink.is_available })
    await loadDrinks()
  } catch {
    listError.value = 'Không thể cập nhật trạng thái món.'
  } finally {
    togglingId.value = null
  }
}

async function deleteDrink(drink) {
  if (!window.confirm(`Xoá món "${drink.name}"?`)) return

  deletingId.value = drink.id
  try {
    await adminDrinksApi.remove(drink.id)
    if (form.id === drink.id) resetForm()
    await loadDrinks()
  } catch {
    listError.value = 'Không thể xoá món này.'
  } finally {
    deletingId.value = null
  }
}

function temperatureLabel(value) {
  return TEMPERATURE_OPTIONS.find((opt) => opt.value === value)?.label ?? value
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
      <RouterLink :to="{ name: 'order-history' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Lịch sử đơn hàng
      </RouterLink>
      <RouterLink :to="{ name: 'admin-menu' }" class="text-sm font-semibold text-primary">
        Quản lý menu
      </RouterLink>
      <RouterLink :to="{ name: 'admin-orders' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Quản lý đơn hàng
      </RouterLink>
      <RouterLink :to="{ name: 'admin-reports' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
        Báo cáo
      </RouterLink>

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

    <div class="max-w-[1200px] mx-auto px-4 md:px-6 py-8 flex flex-col gap-6">
      <div>
        <p class="text-xs font-semibold text-primary uppercase tracking-wide">UC-08 · Admin</p>
        <h1 class="font-heading font-bold text-2xl text-foreground mt-1">Quản lý menu đồ uống</h1>
        <p class="text-sm text-muted-foreground mt-1">Thêm, chỉnh sửa, ẩn/hiện hoặc xoá món trong thực đơn.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-6">
        <BaseCard padding="lg" class="flex flex-col gap-4 min-w-0">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <h2 class="font-heading font-bold text-foreground">Danh sách món ({{ drinks.length }})</h2>
            <div class="flex items-center gap-2">
              <div class="relative">
                <Search :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  v-model="categoryFilter"
                  type="text"
                  placeholder="Lọc theo danh mục…"
                  class="pl-9 pr-3.5 py-2 text-sm border border-border rounded-xl bg-input-background outline-none transition placeholder-gray-400 focus:ring-2 focus:ring-primary/30 focus:border-primary/60"
                  @keyup.enter="loadDrinks"
                />
              </div>
              <BaseButton :full-width="false" variant="secondary" @click="loadDrinks">Lọc</BaseButton>
            </div>
          </div>

          <p v-if="isLoading" class="text-sm text-muted-foreground">Đang tải…</p>
          <p v-else-if="listError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
            {{ listError }}
          </p>
          <p v-else-if="drinks.length === 0" class="text-sm text-muted-foreground">Chưa có món nào.</p>

          <ul v-else class="flex flex-col gap-3 max-h-[640px] overflow-y-auto pr-1">
            <li
              v-for="drink in drinks"
              :key="drink.id"
              class="rounded-xl border p-4 flex flex-col gap-3 transition-colors"
              :class="form.id === drink.id ? 'border-primary bg-secondary/40' : 'border-border'"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl bg-secondary flex items-center justify-center text-lg shrink-0">
                    {{ getCategoryEmoji(drink.category) }}
                  </div>
                  <div class="min-w-0 flex flex-col gap-1">
                    <p class="text-sm font-semibold text-foreground truncate">{{ drink.name }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ drink.category }} · {{ Number(drink.price).toLocaleString('vi-VN') }}đ ·
                      {{ temperatureLabel(drink.temperature_type) }}
                    </p>
                    <p v-if="drink.tags?.length" class="text-xs text-secondary-foreground">
                      {{ drink.tags.join(', ') }}
                    </p>
                  </div>
                </div>
                <span
                  class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                  :class="drink.is_available ? 'bg-success-bg text-success' : 'bg-destructive-bg text-destructive'"
                >
                  {{ drink.is_available ? 'Đang bán' : 'Ngừng bán' }}
                </span>
              </div>

              <div class="flex gap-2 flex-wrap">
                <BaseButton :full-width="false" variant="secondary" @click="editDrink(drink)">
                  <Pencil :size="14" /> Sửa
                </BaseButton>
                <BaseButton
                  :full-width="false"
                  variant="ghost"
                  class="border border-border"
                  :disabled="togglingId === drink.id"
                  @click="toggleAvailability(drink)"
                >
                  {{ drink.is_available ? 'Ngừng bán' : 'Mở bán lại' }}
                </BaseButton>
                <BaseButton
                  :full-width="false"
                  variant="danger"
                  :disabled="deletingId === drink.id"
                  @click="deleteDrink(drink)"
                >
                  <Trash2 :size="14" /> {{ deletingId === drink.id ? 'Đang xoá…' : 'Xoá' }}
                </BaseButton>
              </div>
            </li>
          </ul>
        </BaseCard>

        <BaseCard padding="lg" class="flex flex-col gap-4 h-fit">
          <SectionHeader :title="isEditing ? `Sửa món #${form.id}` : 'Thêm món mới'" />

          <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
            <FormField v-model="form.name" label="Tên món" required :error="fieldErrors.name?.[0]" />

            <FormField v-model="form.category" label="Danh mục" required :error="fieldErrors.category?.[0]" />

            <div class="flex flex-col gap-1.5">
              <p class="text-xs font-semibold text-muted-foreground">Nhiệt độ phục vụ</p>
              <div class="flex gap-2 flex-wrap">
                <RadioCard
                  v-for="option in TEMPERATURE_OPTIONS"
                  :key="option.value"
                  :label="option.label"
                  :icon="option.icon"
                  :selected="form.temperature_type === option.value"
                  @select="form.temperature_type = option.value"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <FormField
                v-model="form.price"
                label="Giá (VNĐ)"
                type="number"
                min="0"
                step="1000"
                required
                :error="fieldErrors.price?.[0]"
              />
              <FormField v-model="form.calories" label="Calories" type="number" min="0" />
            </div>

            <FormField v-model="form.description" label="Mô tả" multiline :rows="2" />

            <FormField v-model="form.ingredients" label="Nguyên liệu" multiline :rows="2" />

            <FormField
              v-model="form.tagsInput"
              label="Tags (cách nhau bởi dấu phẩy)"
              placeholder="vd: best_seller, ít_ngọt"
            />

            <FormField v-model="form.image_url" label="Ảnh (URL)" placeholder="https://..." />

            <label class="flex items-center gap-2.5 text-sm text-foreground cursor-pointer select-none">
              <input v-model="form.is_available" type="checkbox" class="w-4 h-4 rounded accent-current text-primary cursor-pointer" />
              Đang bán
            </label>

            <p v-if="formError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5" role="alert">
              {{ formError }}
            </p>
            <p v-if="successMessage" class="text-sm text-success bg-success-bg rounded-xl px-3.5 py-2.5" role="status">
              {{ successMessage }}
            </p>

            <div class="flex gap-3">
              <BaseButton type="submit" :full-width="false" :disabled="isSaving">
                {{ isSaving ? 'Đang lưu…' : isEditing ? 'Cập nhật món' : 'Thêm món' }}
              </BaseButton>
              <BaseButton v-if="isEditing" type="button" :full-width="false" variant="ghost" @click="resetForm">
                Huỷ sửa
              </BaseButton>
            </div>
          </form>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
