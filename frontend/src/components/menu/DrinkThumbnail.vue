<script setup>
import { computed, ref, watch } from 'vue'
import { getCategoryEmoji } from '@/constants/drinkOptions'

const props = defineProps({
  imageUrl: {
    type: String,
    default: '',
  },
  category: {
    type: String,
    default: '',
  },
  name: {
    type: String,
    default: 'Đồ uống',
  },
  size: {
    type: String,
    default: 'sm',
    validator: (value) => ['sm', 'md'].includes(value),
  },
})

const imageFailed = ref(false)
const sizeClasses = computed(() => (props.size === 'md' ? 'w-11 h-11 text-xl' : 'w-10 h-10 text-lg'))

watch(
  () => props.imageUrl,
  () => {
    imageFailed.value = false
  },
)
</script>

<template>
  <div
    class="rounded-xl bg-secondary flex items-center justify-center overflow-hidden shrink-0"
    :class="sizeClasses"
  >
    <img
      v-if="imageUrl && !imageFailed"
      :src="imageUrl"
      :alt="name"
      class="w-full h-full object-contain p-1"
      loading="lazy"
      decoding="async"
      @error="imageFailed = true"
    />
    <span v-else aria-hidden="true">{{ getCategoryEmoji(category) }}</span>
  </div>
</template>
