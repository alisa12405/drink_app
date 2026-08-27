import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const occasion = ref('')

  const totalQuantity = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
  const totalPrice = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
  )

  function addItem(drink) {
    const existing = items.value.find(
      (item) =>
        item.drink_id === drink.id && item.sugar_level === '100' && item.ice_level === 'normal_ice',
    )
    if (existing) {
      existing.quantity += 1
      return
    }
    items.value.push({
      drink_id: drink.id,
      name: drink.name,
      category: drink.category,
      price: Number(drink.price),
      quantity: 1,
      sugar_level: '100',
      ice_level: 'normal_ice',
      note: '',
    })
  }

  function increase(index) {
    items.value[index].quantity = Math.min(20, items.value[index].quantity + 1)
  }

  function decrease(index) {
    const item = items.value[index]
    if (item.quantity <= 1) {
      items.value.splice(index, 1)
      return
    }
    item.quantity -= 1
  }

  function remove(index) {
    items.value.splice(index, 1)
  }

  function clear() {
    items.value = []
    occasion.value = ''
  }

  return {
    items,
    occasion,
    totalQuantity,
    totalPrice,
    addItem,
    increase,
    decrease,
    remove,
    clear,
  }
})
