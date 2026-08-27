<script setup>
defineProps({
  name: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div
    class="rounded-2xl bg-gradient-to-r from-primary to-primary-hover text-primary-foreground px-6 py-6 md:px-8 md:py-8 flex items-center justify-between gap-6 overflow-hidden"
  >
    <div>
      <p class="text-xs uppercase tracking-widest font-semibold opacity-80">Xin chào</p>
      <h1 class="font-heading font-bold text-2xl md:text-3xl mt-1">{{ name }} 👋</h1>
      <p class="mt-2 text-sm md:text-base opacity-90">Hôm nay bạn muốn thưởng thức món gì?</p>
    </div>
    <div class="hidden md:block text-6xl shrink-0 opacity-90">🧋</div>
  </div>
</template>
