<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { drinksApi } from '@/services/api'

const router = useRouter()
const auth = useAuthStore()
const drinks = ref([])
const loadError = ref('')

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
</script>

<template>
  <div class="home">
    <header>
      <div>
        <p class="eyebrow">Đã đăng nhập</p>
        <h1>{{ auth.user?.name }}</h1>
        <p>{{ auth.user?.email }} · {{ auth.user?.role }}</p>
      </div>
      <button type="button" @click="onLogout">Đăng xuất</button>
    </header>

    <section>
      <h2>Menu thử nghiệm</h2>
      <p v-if="loadError" class="error">{{ loadError }}</p>
      <ul v-else>
        <li v-for="drink in drinks" :key="drink.id">
          <strong>{{ drink.name }}</strong>
          <span>{{ drink.category }} · {{ Number(drink.price).toLocaleString('vi-VN') }}đ</span>
        </li>
      </ul>
    </section>
  </div>
</template>

<style scoped>
.home {
  max-width: 720px;
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

ul {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 0.6rem;
}

li {
  background: #fff;
  border: 1px solid #eadfce;
  border-radius: 12px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

li span {
  color: #6b5848;
  font-size: 0.9rem;
}

.error {
  color: #b91c1c;
}
</style>
