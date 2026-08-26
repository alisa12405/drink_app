<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { preferencesApi } from '@/services/api'
import { SUGAR_OPTIONS, ICE_OPTIONS } from '@/constants/drinkOptions'

const router = useRouter()

const isLoading = ref(true)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const tasteTagsInput = ref('')
const sugarLevelDefault = ref('100')
const iceLevelDefault = ref('normal_ice')
const allergyNotes = ref('')
const profileText = ref('')

function applyPreference(preference) {
  tasteTagsInput.value = (preference.taste_tags ?? []).join(', ')
  sugarLevelDefault.value = preference.sugar_level_default
  iceLevelDefault.value = preference.ice_level_default
  allergyNotes.value = preference.allergy_notes ?? ''
  profileText.value = preference.profile_text ?? ''
}

onMounted(async () => {
  try {
    const { data } = await preferencesApi.show()
    applyPreference(data.data)
  } catch {
    errorMessage.value = 'Không tải được hồ sơ sở thích.'
  } finally {
    isLoading.value = false
  }
})

async function onSubmit() {
  errorMessage.value = ''
  successMessage.value = ''
  isSaving.value = true

  const tasteTags = tasteTagsInput.value
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean)

  try {
    const { data } = await preferencesApi.update({
      taste_tags: tasteTags,
      sugar_level_default: sugarLevelDefault.value,
      ice_level_default: iceLevelDefault.value,
      allergy_notes: allergyNotes.value || null,
    })
    applyPreference(data.data)
    successMessage.value = 'Đã lưu sở thích của bạn.'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Lưu thất bại. Vui lòng thử lại.'
  } finally {
    isSaving.value = false
  }
}

function backToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="preferences-page">
    <div class="card">
      <button type="button" class="back" @click="backToHome">← Về trang chủ</button>
      <p class="eyebrow">UC-02</p>
      <h1>Sở thích đồ uống</h1>
      <p class="subtitle">Khai báo sở thích để hệ thống gợi ý đồ uống phù hợp hơn.</p>

      <p v-if="isLoading">Đang tải…</p>

      <form v-else @submit.prevent="onSubmit">
        <label>
          Sở thích (cách nhau bởi dấu phẩy)
          <input
            v-model="tasteTagsInput"
            type="text"
            placeholder="ví dụ: ngọt, có_caffeine, trái_cây"
          />
        </label>

        <div class="row">
          <label>
            Mức đường mặc định
            <select v-model="sugarLevelDefault">
              <option v-for="option in SUGAR_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label>
            Mức đá mặc định
            <select v-model="iceLevelDefault">
              <option v-for="option in ICE_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>
        </div>

        <label>
          Ghi chú dị ứng
          <textarea v-model="allergyNotes" rows="3" placeholder="ví dụ: dị ứng đậu phộng" />
        </label>

        <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="success" role="status">{{ successMessage }}</p>

        <button type="submit" :disabled="isSaving">
          {{ isSaving ? 'Đang lưu…' : 'Lưu sở thích' }}
        </button>
      </form>

      <div v-if="profileText" class="profile-text">
        <h2>Hồ sơ tổng hợp (dùng để tính gợi ý)</h2>
        <p>{{ profileText }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.preferences-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: 2rem 1.25rem 3rem;
}

.card {
  width: min(100%, 560px);
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

.subtitle {
  color: #6b5848;
  font-size: 0.95rem;
}

form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.9rem;
  color: #4a3728;
}

input,
select,
textarea {
  border: 1px solid #e0d3c2;
  border-radius: 10px;
  padding: 0.65rem 0.75rem;
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

button[type='submit'] {
  border: 0;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  background: #b45309;
  color: #fff;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

button[type='submit']:disabled {
  opacity: 0.7;
  cursor: wait;
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
}

.success {
  background: #f0fdf4;
  color: #15803d;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
}

.profile-text {
  border-top: 1px dashed #eadfce;
  padding-top: 0.9rem;
  color: #6b5848;
  font-size: 0.85rem;
}

.profile-text h2 {
  font-size: 0.85rem;
  color: #9a3412;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.3rem;
}
</style>
