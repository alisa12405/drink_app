<script setup>
defineProps({
  label: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    default: '',
  },
  selected: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['select'])
</script>

<template>
  <button
    type="button"
    class="flex items-center gap-3 px-5 py-3 rounded-xl border-2 text-sm font-semibold transition-all min-w-[150px]"
    :class="
      selected
        ? 'border-primary bg-secondary text-primary'
        : 'border-border text-muted-foreground hover:border-accent bg-card'
    "
    @click="$emit('select')"
  >
    <span
      class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
      :class="selected ? 'border-primary' : 'border-border'"
    >
      <span v-if="selected" class="w-2 h-2 rounded-full bg-primary" />
    </span>
    <span v-if="icon" class="text-lg">{{ icon }}</span>
    {{ label }}
  </button>
</template>
