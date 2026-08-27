<script setup>
defineProps({
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
  },
})

const iconSizeClass = {
  sm: 'w-8 h-8 text-base',
  md: 'w-9 h-9 text-lg',
  lg: 'w-12 h-12 text-2xl',
}

const nameSizeClass = {
  sm: 'text-sm',
  md: 'text-lg',
  lg: 'text-2xl',
}
</script>

<template>
  <div class="flex items-center gap-2.5">
    <div
      class="shrink-0 rounded-full bg-primary text-primary-foreground flex items-center justify-center"
      :class="iconSizeClass[size]"
    >
      🧋
    </div>
    <div>
      <span class="font-heading font-bold text-foreground leading-none" :class="nameSizeClass[size]">
        Smart Drink
      </span>
      <p class="text-[10px] text-primary font-semibold tracking-widest uppercase leading-none mt-0.5">
        Trà sữa &amp; Cà phê
      </p>
    </div>
  </div>
</template>
