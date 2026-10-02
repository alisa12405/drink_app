<script setup>
import { onMounted, reactive, ref } from 'vue'
import { BarChart3, Sparkles, Target, TrendingUp } from 'lucide-vue-next'
import { adminReportsApi } from '@/services/api'
import { getCategoryEmoji } from '@/constants/drinkOptions'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseButton from '@/components/ui/BaseButton.vue'


const fromDate = ref('')
const toDate = ref('')

const bestSellers = ref([])
const bestSellersLoading = ref(true)
const bestSellersError = ref('')

const effectiveness = reactive({
  total_recommendations: 0,
  converted_recommendations: 0,
  conversion_rate: 0,
  conversion_by_position: {},
})
const effectivenessLoading = ref(true)
const effectivenessError = ref('')

const RANK_MEDALS = ['🥇', '🥈', '🥉']

function dateParams() {
  const params = {}
  if (fromDate.value) params.from = fromDate.value
  if (toDate.value) params.to = toDate.value
  return params
}

async function loadBestSellers() {
  bestSellersLoading.value = true
  bestSellersError.value = ''
  try {
    const { data } = await adminReportsApi.bestSellingDrinks(dateParams())
    bestSellers.value = data.data ?? []
  } catch {
    bestSellersError.value = 'Không tải được báo cáo món bán chạy.'
  } finally {
    bestSellersLoading.value = false
  }
}

async function loadEffectiveness() {
  effectivenessLoading.value = true
  effectivenessError.value = ''
  try {
    const { data } = await adminReportsApi.recommendationEffectiveness(dateParams())
    Object.assign(effectiveness, data.data)
  } catch {
    effectivenessError.value = 'Không tải được báo cáo hiệu quả gợi ý.'
  } finally {
    effectivenessLoading.value = false
  }
}

function loadAll() {
  loadBestSellers()
  loadEffectiveness()
}

onMounted(loadAll)

function positionLabel(position) {
  return `Vị trí #${Number(position) + 1}`
}

function formatAverageRating(value) {
  if (value === null || value === undefined) return '—'

  return `${Number(value).toLocaleString('vi-VN', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} ★`
}

</script>

<template>
  <div class="min-h-screen bg-background">
    <AppNavbar :cart-count="0" :show-cart="false" />

    <div class="max-w-[1000px] mx-auto px-4 md:px-6 py-8 flex flex-col gap-6">
      <div>
        <p class="text-xs font-semibold text-primary uppercase tracking-wide">Quản trị</p>
        <h1 class="font-heading font-bold text-2xl text-foreground mt-1">Báo cáo thống kê</h1>
        <p class="text-sm text-muted-foreground mt-1">Theo dõi món bán chạy và hiệu quả gợi ý.</p>
      </div>

      <BaseCard padding="sm" class="flex items-end gap-3 flex-wrap">
        <FormField v-model="fromDate" label="Từ ngày" type="date" class="w-auto" />
        <FormField v-model="toDate" label="Đến ngày" type="date" class="w-auto" />
        <BaseButton :full-width="false" @click="loadAll">Áp dụng</BaseButton>
      </BaseCard>

      <BaseCard padding="lg" class="flex flex-col gap-4">
        <SectionHeader title="Món bán chạy nhất" />
        <p class="text-sm text-muted-foreground -mt-3">Chỉ tính các đơn đã ở trạng thái "Hoàn tất".</p>

        <p v-if="bestSellersLoading" class="text-sm text-muted-foreground">Đang tải…</p>
        <p v-else-if="bestSellersError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
          {{ bestSellersError }}
        </p>
        <p v-else-if="bestSellers.length === 0" class="text-sm text-muted-foreground">
          Chưa có đơn hàng hoàn tất nào trong khoảng thời gian này.
        </p>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="border-b border-border">
                <th class="text-left py-2.5 px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">#</th>
                <th class="text-left py-2.5 px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Món</th>
                <th class="text-left py-2.5 px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Danh mục</th>
                <th class="text-right py-2.5 px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">SL bán</th>
                <th class="text-right py-2.5 px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Đánh giá</th>
                <th class="text-right py-2.5 px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Doanh thu</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in bestSellers"
                :key="row.drink_id"
                class="border-b border-border/60 hover:bg-secondary/30 transition-colors"
              >
                <td class="py-2.5 px-2 text-foreground font-semibold">{{ RANK_MEDALS[index] ?? index + 1 }}</td>
                <td class="py-2.5 px-2 text-foreground">
                  <span class="mr-1.5">{{ getCategoryEmoji(row.drink_category) }}</span>{{ row.drink_name }}
                </td>
                <td class="py-2.5 px-2 text-muted-foreground">{{ row.drink_category }}</td>
                <td class="py-2.5 px-2 text-right text-foreground">{{ row.total_quantity }}</td>
                <td class="py-2.5 px-2 text-right text-foreground">{{ formatAverageRating(row.average_rating) }}</td>
                <td class="py-2.5 px-2 text-right font-bold text-primary">
                  {{ Number(row.total_revenue).toLocaleString('vi-VN') }}đ
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>

      <BaseCard padding="lg" class="flex flex-col gap-4">
        <SectionHeader title="Hiệu quả gợi ý" />
        <p class="text-sm text-muted-foreground -mt-3">
          Đối chiếu danh sách gợi ý với đơn hàng thực tế của cùng khách hàng trong 24h sau đó.
        </p>

        <p v-if="effectivenessLoading" class="text-sm text-muted-foreground">Đang tải…</p>
        <p v-else-if="effectivenessError" class="text-sm text-destructive bg-destructive-bg rounded-xl px-3.5 py-2.5">
          {{ effectivenessError }}
        </p>
        <template v-else>
          <p v-if="effectiveness.total_recommendations === 0" class="text-sm text-muted-foreground">
            Chưa có dữ liệu gợi ý nào được ghi nhận.
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-secondary/40 border border-border rounded-2xl p-5 flex flex-col items-center gap-1.5 text-center">
              <Sparkles :size="20" class="text-primary" />
              <span class="font-heading font-bold text-3xl text-foreground">{{ effectiveness.total_recommendations }}</span>
              <span class="text-xs text-muted-foreground uppercase tracking-wide">Lượt gợi ý</span>
            </div>
            <div class="bg-secondary/40 border border-border rounded-2xl p-5 flex flex-col items-center gap-1.5 text-center">
              <TrendingUp :size="20" class="text-primary" />
              <span class="font-heading font-bold text-3xl text-foreground">{{ effectiveness.converted_recommendations }}</span>
              <span class="text-xs text-muted-foreground uppercase tracking-wide">Lượt dẫn đến mua hàng</span>
            </div>
            <div class="bg-secondary/40 border border-border rounded-2xl p-5 flex flex-col items-center gap-1.5 text-center">
              <Target :size="20" class="text-primary" />
              <span class="font-heading font-bold text-3xl text-foreground">{{ effectiveness.conversion_rate }}%</span>
              <span class="text-xs text-muted-foreground uppercase tracking-wide">Tỉ lệ chuyển đổi</span>
            </div>
          </div>

          <div v-if="Object.keys(effectiveness.conversion_by_position).length > 0" class="border-t border-dashed border-border pt-4 flex flex-col gap-2.5">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-foreground">
              <BarChart3 :size="16" class="text-primary" /> Phân bố theo vị trí gợi ý được chọn mua
            </h3>
            <ul class="flex flex-col gap-1.5">
              <li
                v-for="(count, position) in effectiveness.conversion_by_position"
                :key="position"
                class="flex items-center justify-between text-sm text-foreground bg-muted/60 rounded-lg px-3.5 py-2"
              >
                <span class="text-muted-foreground">{{ positionLabel(position) }}</span>
                <strong>{{ count }} lượt</strong>
              </li>
            </ul>
          </div>
        </template>
      </BaseCard>
    </div>
  </div>
</template>
