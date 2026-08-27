<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Sparkles, X } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { preferencesApi } from '@/services/api'
import { SUGAR_OPTIONS, ICE_OPTIONS, TASTE_TAG_PRESETS, getTasteTagLabel } from '@/constants/drinkOptions'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormField from '@/components/ui/FormField.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import RadioCard from '@/components/ui/RadioCard.vue'

const router = useRouter()
const auth = useAuthStore()

const isLoading = ref(true)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const selectedTags = ref([])
const customTagInput = ref('')
const sugarLevelDefault = ref('100')
const iceLevelDefault = ref('normal_ice')
const allergyNotes = ref('')
const profileText = ref('')

const customTags = computed(() =>
  selectedTags.value.filter((tag) => !TASTE_TAG_PRESETS.some((preset) => preset.value === tag)),
)

function applyPreference(preference) {
  selectedTags.value = [...(preference.taste_tags ?? [])]
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

function toggleTag(tag) {
  if (selectedTags.value.includes(tag)) {
    selectedTags.value = selectedTags.value.filter((t) => t !== tag)
  } else {
    selectedTags.value = [...selectedTags.value, tag]
  }
}

function removeTag(tag) {
  selectedTags.value = selectedTags.value.filter((t) => t !== tag)
}

function addCustomTag() {
  const tag = customTagInput.value.trim().toLowerCase().replace(/\s+/g, '_')
  if (tag && !selectedTags.value.includes(tag)) {
    selectedTags.value = [...selectedTags.value, tag]
  }
  customTagInput.value = ''
}

async function onSubmit() {
  errorMessage.value = ''
  successMessage.value = ''
  isSaving.value = true

  try {
    const { data } = await preferencesApi.update({
      taste_tags: selectedTags.value,
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
      <RouterLink :to="{ name: 'preferences' }" class="text-sm font-semibold text-primary">
        Sở thích của tôi
      </RouterLink>
      <RouterLink :to="{ name: 'order-history' }" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
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

    <div class="max-w-[680px] mx-auto px-4 md:px-6 py-8 flex flex-col gap-5">
      <div>
        <h1 class="font-heading font-bold text-2xl text-foreground">Sở thích đồ uống</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Khai báo sở thích để hệ thống gợi ý đồ uống phù hợp hơn với bạn.
        </p>
      </div>

      <p v-if="isLoading" class="text-sm text-muted-foreground">Đang tải…</p>

      <form v-else class="flex flex-col gap-5" @submit.prevent="onSubmit">
        <BaseCard>
          <SectionHeader :number="1" title="Sở thích vị giác" />
          <div class="flex flex-wrap gap-2">
            <button
              v-for="preset in TASTE_TAG_PRESETS"
              :key="preset.value"
              type="button"
              class="px-3.5 py-1.5 rounded-full text-sm font-medium border-2 transition-colors"
              :class="
                selectedTags.includes(preset.value)
                  ? 'bg-secondary border-primary text-primary'
                  : 'bg-card border-border text-muted-foreground hover:border-accent'
              "
              @click="toggleTag(preset.value)"
            >
              {{ preset.label }}
            </button>
          </div>

          <div v-if="customTags.length" class="flex flex-wrap gap-2 mt-3">
            <span
              v-for="tag in customTags"
              :key="tag"
              class="inline-flex items-center gap-1.5 pl-3.5 pr-2 py-1.5 rounded-full text-sm font-medium bg-secondary border-2 border-primary text-primary"
            >
              {{ getTasteTagLabel(tag) }}
              <button type="button" class="hover:text-destructive transition-colors" @click="removeTag(tag)">
                <X :size="14" />
              </button>
            </span>
          </div>

          <div class="flex gap-2 mt-4">
            <input
              v-model="customTagInput"
              type="text"
              placeholder="Thêm sở thích khác (vd: sữa_yến_mạch)"
              class="flex-1 px-3.5 py-2.5 text-sm border border-border rounded-xl bg-input-background outline-none transition placeholder-gray-400 focus:ring-2 focus:ring-primary/30 focus:border-primary/60"
              @keydown.enter.prevent="addCustomTag"
            />
            <BaseButton type="button" variant="secondary" :full-width="false" @click="addCustomTag">Thêm</BaseButton>
          </div>
        </BaseCard>

        <BaseCard>
          <SectionHeader :number="2" title="Mức đường & đá mặc định" />
          <div class="flex flex-col gap-4">
            <div>
              <p class="text-xs font-semibold text-muted-foreground mb-2">Mức đường</p>
              <div class="flex gap-3 flex-wrap">
                <RadioCard
                  v-for="option in SUGAR_OPTIONS"
                  :key="option.value"
                  :label="option.label"
                  :selected="sugarLevelDefault === option.value"
                  @select="sugarLevelDefault = option.value"
                />
              </div>
            </div>
            <div>
              <p class="text-xs font-semibold text-muted-foreground mb-2">Mức đá</p>
              <div class="flex gap-3 flex-wrap">
                <RadioCard
                  v-for="option in ICE_OPTIONS"
                  :key="option.value"
                  :label="option.label"
                  :selected="iceLevelDefault === option.value"
                  @select="iceLevelDefault = option.value"
                />
              </div>
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <SectionHeader :number="3" title="Ghi chú dị ứng" />
          <FormField
            v-model="allergyNotes"
            multiline
            :rows="3"
            placeholder="Ví dụ: dị ứng đậu phộng, không dùng được sữa bò..."
          />
        </BaseCard>

        <p v-if="errorMessage" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5" role="alert">
          {{ errorMessage }}
        </p>
        <p v-if="successMessage" class="text-sm text-success bg-success-bg rounded-xl px-3.5 py-2.5" role="status">
          {{ successMessage }}
        </p>

        <BaseButton type="submit" :disabled="isSaving">
          {{ isSaving ? 'Đang lưu…' : 'Lưu sở thích' }}
        </BaseButton>
      </form>

      <div v-if="profileText" class="bg-muted/40 border border-dashed border-border rounded-2xl p-6">
        <div class="flex items-center gap-2 mb-2">
          <Sparkles :size="16" class="text-primary" />
          <h2 class="text-xs font-bold text-primary uppercase tracking-wide">Hồ sơ tổng hợp dùng để gợi ý</h2>
        </div>
        <p class="text-sm text-muted-foreground leading-relaxed">{{ profileText }}</p>
      </div>
    </div>
  </div>
</template>
