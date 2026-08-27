<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { adminReportsApi } from '@/services/api'

const router = useRouter()

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

function backToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="reports-page">
    <div class="wrapper">
      <button type="button" class="back" @click="backToHome">← Về trang chủ</button>
      <p class="eyebrow">UC-10 (Admin)</p>
      <h1>Báo cáo thống kê</h1>

      <div class="toolbar">
        <label>
          Từ ngày
          <input v-model="fromDate" type="date" />
        </label>
        <label>
          Đến ngày
          <input v-model="toDate" type="date" />
        </label>
        <button type="button" @click="loadAll">Áp dụng</button>
      </div>

      <section class="panel">
        <h2>Món bán chạy nhất</h2>
        <p class="hint">Chỉ tính các đơn đã ở trạng thái "Hoàn tất".</p>

        <p v-if="bestSellersLoading">Đang tải…</p>
        <p v-else-if="bestSellersError" class="error">{{ bestSellersError }}</p>
        <p v-else-if="bestSellers.length === 0" class="empty">Chưa có đơn hàng hoàn tất nào trong khoảng thời gian này.</p>

        <table v-else class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Món</th>
              <th>Danh mục</th>
              <th>Số lượng bán</th>
              <th>Doanh thu</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in bestSellers" :key="row.drink_id">
              <td>{{ index + 1 }}</td>
              <td>{{ row.drink_name }}</td>
              <td>{{ row.drink_category }}</td>
              <td>{{ row.total_quantity }}</td>
              <td>{{ Number(row.total_revenue).toLocaleString('vi-VN') }}đ</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="panel">
        <h2>Hiệu quả gợi ý</h2>
        <p class="hint">
          Đối chiếu danh sách gợi ý (UC-04) với đơn hàng thực tế của cùng khách hàng trong 24h sau đó.
        </p>

        <p v-if="effectivenessLoading">Đang tải…</p>
        <p v-else-if="effectivenessError" class="error">{{ effectivenessError }}</p>
        <template v-else>
          <p v-if="effectiveness.total_recommendations === 0" class="empty">
            Chưa có dữ liệu gợi ý nào được ghi nhận (tính năng gợi ý — UC-04 — chưa phát sinh log).
          </p>

          <div class="stats">
            <div class="stat-card">
              <span class="stat-value">{{ effectiveness.total_recommendations }}</span>
              <span class="stat-label">Lượt gợi ý</span>
            </div>
            <div class="stat-card">
              <span class="stat-value">{{ effectiveness.converted_recommendations }}</span>
              <span class="stat-label">Lượt dẫn đến mua hàng</span>
            </div>
            <div class="stat-card">
              <span class="stat-value">{{ effectiveness.conversion_rate }}%</span>
              <span class="stat-label">Tỉ lệ chuyển đổi</span>
            </div>
          </div>

          <div v-if="Object.keys(effectiveness.conversion_by_position).length > 0" class="positions">
            <h3>Phân bố theo vị trí gợi ý được chọn mua</h3>
            <ul>
              <li v-for="(count, position) in effectiveness.conversion_by_position" :key="position">
                {{ positionLabel(position) }}: <strong>{{ count }}</strong> lượt
              </li>
            </ul>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<style scoped>
.reports-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: 2rem 1.25rem 3rem;
}

.wrapper {
  width: min(100%, 900px);
  display: flex;
  flex-direction: column;
  gap: 1rem;
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
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 12px;
  padding: 0.85rem 1rem;
}

.toolbar label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.85rem;
  color: #4a3728;
}

.toolbar input {
  border: 1px solid #e0d3c2;
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font: inherit;
  background: #fffdf8;
}

.toolbar button {
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #9a3412;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  cursor: pointer;
  font: inherit;
}

.panel {
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 12px 40px rgba(74, 44, 23, 0.06);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.panel h2 {
  font-size: 1.1rem;
  color: #3f2a1d;
}

.hint {
  color: #6b5848;
  font-size: 0.82rem;
  margin-top: -0.4rem;
}

.empty {
  color: #6b5848;
  font-size: 0.9rem;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.table th,
.table td {
  text-align: left;
  padding: 0.55rem 0.6rem;
  border-bottom: 1px solid #f1e7d8;
}

.table th {
  color: #9a3412;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.table td {
  color: #3f2a1d;
}

.stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.9rem;
}

@media (max-width: 640px) {
  .stats {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 12px;
  padding: 0.9rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #9a3412;
}

.stat-label {
  font-size: 0.8rem;
  color: #6b5848;
  text-align: center;
}

.positions {
  border-top: 1px dashed #eadfce;
  padding-top: 0.75rem;
}

.positions h3 {
  font-size: 0.88rem;
  color: #4a3728;
  margin-bottom: 0.4rem;
}

.positions ul {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 0.25rem;
  font-size: 0.88rem;
  color: #4a3728;
}

.error {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
}
</style>
