<script setup>
defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary', // 'primary' | 'secondary' | 'ghost' | 'danger' | 'success'
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  fullWidth: {
    type: Boolean,
    default: true,
  },
})

const variantClass = {
  primary:
    'bg-primary hover:bg-primary-hover text-primary-foreground shadow-sm disabled:bg-muted disabled:text-muted-foreground',
  secondary:
    'bg-secondary hover:bg-accent text-secondary-foreground border border-border disabled:opacity-60',
  ghost: 'bg-transparent hover:bg-secondary text-foreground disabled:opacity-60',
  danger:
    'bg-destructive-bg hover:bg-red-100 text-destructive border border-red-200 disabled:opacity-60',
  success:
    'bg-success-bg hover:bg-green-100 text-success border border-green-200 disabled:opacity-60',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed"
    :class="[fullWidth ? 'w-full' : 'w-auto', variantClass[variant]]"
  >
    <slot />
  </button>
</template>
