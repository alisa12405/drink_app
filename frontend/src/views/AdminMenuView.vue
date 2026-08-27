<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { adminDrinksApi } from '@/services/api'
import { TEMPERATURE_OPTIONS } from '@/constants/drinkOptions'

const router = useRouter()

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

function backToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="admin-page">
    <div class="wrapper">
      <button type="button" class="back" @click="backToHome">← Về trang chủ</button>
      <p class="eyebrow">UC-08 (Admin)</p>
      <h1>Quản lý menu đồ uống</h1>

      <div class="layout">
        <section class="list-panel">
          <div class="list-header">
            <h2>Danh sách món ({{ drinks.length }})</h2>
            <div class="filter">
              <input
                v-model="categoryFilter"
                type="text"
                placeholder="Lọc theo danh mục…"
                @keyup.enter="loadDrinks"
              />
              <button type="button" @click="loadDrinks">Lọc</button>
            </div>
          </div>

          <p v-if="isLoading">Đang tải…</p>
          <p v-else-if="listError" class="error">{{ listError }}</p>
          <p v-else-if="drinks.length === 0" class="empty">Chưa có món nào.</p>

          <ul v-else class="drinks">
            <li v-for="drink in drinks" :key="drink.id" :class="{ active: form.id === drink.id }">
              <div class="drink-main">
                <div>
                  <strong>{{ drink.name }}</strong>
                  <span class="meta">
                    {{ drink.category }} · {{ Number(drink.price).toLocaleString('vi-VN') }}đ ·
                    {{ drink.temperature_type }}
                  </span>
                  <span v-if="drink.tags?.length" class="tags">{{ drink.tags.join(', ') }}</span>
                </div>
                <span class="badge" :class="drink.is_available ? 'badge-on' : 'badge-off'">
                  {{ drink.is_available ? 'Đang bán' : 'Ngừng bán' }}
                </span>
              </div>

              <div class="drink-actions">
                <button type="button" @click="editDrink(drink)">Sửa</button>
                <button
                  type="button"
                  :disabled="togglingId === drink.id"
                  @click="toggleAvailability(drink)"
                >
                  {{ drink.is_available ? 'Ngừng bán' : 'Mở bán lại' }}
                </button>
                <button
                  type="button"
                  class="danger"
                  :disabled="deletingId === drink.id"
                  @click="deleteDrink(drink)"
                >
                  {{ deletingId === drink.id ? 'Đang xoá…' : 'Xoá' }}
                </button>
              </div>
            </li>
          </ul>
        </section>

        <section class="form-panel">
          <h2>{{ isEditing ? `Sửa món #${form.id}` : 'Thêm món mới' }}</h2>

          <form @submit.prevent="onSubmit">
            <label>
              Tên món
              <input v-model="form.name" type="text" required />
              <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name[0] }}</span>
            </label>

            <div class="row">
              <label>
                Danh mục
                <input v-model="form.category" type="text" required />
                <span v-if="fieldErrors.category" class="field-error">{{ fieldErrors.category[0] }}</span>
              </label>

              <label>
                Nhiệt độ
                <select v-model="form.temperature_type">
                  <option v-for="option in TEMPERATURE_OPTIONS" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </label>
            </div>

            <div class="row">
              <label>
                Giá (VNĐ)
                <input v-model="form.price" type="number" min="0" step="1000" required />
                <span v-if="fieldErrors.price" class="field-error">{{ fieldErrors.price[0] }}</span>
              </label>

              <label>
                Calories
                <input v-model="form.calories" type="number" min="0" />
              </label>
            </div>

            <label>
              Mô tả
              <textarea v-model="form.description" rows="2" />
            </label>

            <label>
              Nguyên liệu
              <textarea v-model="form.ingredients" rows="2" />
            </label>

            <label>
              Tags (cách nhau bởi dấu phẩy)
              <input v-model="form.tagsInput" type="text" placeholder="ví dụ: best_seller, ít_ngọt" />
            </label>

            <label>
              Ảnh (URL)
              <input v-model="form.image_url" type="text" placeholder="https://..." />
            </label>

            <label class="checkbox">
              <input v-model="form.is_available" type="checkbox" />
              Đang bán
            </label>

            <p v-if="formError" class="error" role="alert">{{ formError }}</p>
            <p v-if="successMessage" class="success" role="status">{{ successMessage }}</p>

            <div class="form-actions">
              <button type="submit" :disabled="isSaving">
                {{ isSaving ? 'Đang lưu…' : isEditing ? 'Cập nhật món' : 'Thêm món' }}
              </button>
              <button v-if="isEditing" type="button" class="ghost" @click="resetForm">Huỷ sửa</button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page {
  min-height: 100vh;
  padding: 2rem 1.25rem 3rem;
  display: flex;
  justify-content: center;
}

.wrapper {
  width: min(100%, 1080px);
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
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
  margin-bottom: 0.5rem;
}

h2 {
  font-size: 1.05rem;
  color: #3f2a1d;
}

.layout {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 1.25rem;
  align-items: start;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }
}

.list-panel,
.form-panel {
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 12px 40px rgba(74, 44, 23, 0.06);
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-bottom: 0.75rem;
}

.filter {
  display: flex;
  gap: 0.4rem;
}

.filter input {
  border: 1px solid #e0d3c2;
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font: inherit;
  font-size: 0.85rem;
}

.filter button {
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #9a3412;
  border-radius: 8px;
  padding: 0.4rem 0.7rem;
  cursor: pointer;
}

.empty {
  color: #6b5848;
}

.drinks {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 0.6rem;
  max-height: 640px;
  overflow-y: auto;
}

.drinks > li {
  border: 1px solid #eadfce;
  border-radius: 12px;
  padding: 0.75rem 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.drinks > li.active {
  border-color: #d97706;
  background: #fffaf3;
}

.drink-main {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.6rem;
}

.drink-main div {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.meta {
  color: #6b5848;
  font-size: 0.85rem;
}

.tags {
  color: #9a3412;
  font-size: 0.78rem;
}

.badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  white-space: nowrap;
}

.badge-on {
  background: #f0fdf4;
  color: #15803d;
}

.badge-off {
  background: #fef2f2;
  color: #b91c1c;
}

.drink-actions {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.drink-actions button {
  border: 1px solid #e0d3c2;
  background: #fffdf8;
  color: #3f2a1d;
  border-radius: 8px;
  padding: 0.35rem 0.65rem;
  font-size: 0.8rem;
  cursor: pointer;
}

.drink-actions button.danger {
  border-color: #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.drink-actions button:disabled {
  opacity: 0.6;
  cursor: wait;
}

form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.88rem;
  color: #4a3728;
}

label.checkbox {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
}

input,
select,
textarea {
  border: 1px solid #e0d3c2;
  border-radius: 10px;
  padding: 0.6rem 0.7rem;
  font: inherit;
  color: #3f2a1d;
  background: #fffdf8;
  resize: vertical;
}

input:focus,
select:focus,
textarea:focus {
  outline: 2px solid #d97706;
  border-color: #d97706;
}

.field-error {
  color: #b91c1c;
  font-size: 0.78rem;
}

.form-actions {
  display: flex;
  gap: 0.6rem;
}

.form-actions button[type='submit'] {
  border: 0;
  border-radius: 10px;
  padding: 0.7rem 1rem;
  background: #b45309;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

.form-actions button[type='submit']:disabled {
  opacity: 0.7;
  cursor: wait;
}

.form-actions button.ghost {
  border: 1px solid #e0d3c2;
  background: #fff;
  color: #4a3728;
  border-radius: 10px;
  padding: 0.7rem 1rem;
  cursor: pointer;
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.85rem;
}

.success {
  background: #f0fdf4;
  color: #15803d;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.85rem;
}
</style>
