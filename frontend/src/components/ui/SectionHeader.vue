<script setup>
defineProps({
  number: {
    type: [Number, String],
    default: null,
  },
  title: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <div class="flex items-center gap-3 mb-4">
    <div
      v-if="number !== null"
      class="w-6 h-6 shrink-0 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold"
    >
      {{ number }}
    </div>
    <h3 class="font-heading font-bold text-foreground text-base">{{ title }}</h3>
  </div>
</template>
