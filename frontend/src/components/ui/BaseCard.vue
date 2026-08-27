<script setup>
defineProps({
  padding: {
    type: String,
    default: 'md', // 'none' | 'sm' | 'md' | 'lg'
  },
})

const paddingClass = {
  none: '',
  sm: 'p-4',
  md: 'p-6',
  lg: 'p-8',
}
</script>

<template>
  <div class="bg-card border border-border rounded-2xl shadow-sm" :class="paddingClass[padding]">
    <slot />
  </div>
</template>
