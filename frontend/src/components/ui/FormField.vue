<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  error: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: () => `field-${Math.random().toString(36).slice(2, 9)}`,
  },
  multiline: {
    type: Boolean,
    default: false,
  },
  rows: {
    type: [Number, String],
    default: 3,
  },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="id" class="text-xs font-semibold text-muted-foreground">
      {{ label }}
    </label>
    <textarea
      v-if="multiline"
      :id="id"
      :rows="rows"
      :value="modelValue"
      v-bind="$attrs"
      class="w-full px-3.5 py-2.5 text-sm border rounded-xl bg-input-background outline-none transition placeholder-gray-400 focus:ring-2 focus:ring-primary/30 focus:border-primary/60 resize-y"
      :class="error ? 'border-destructive' : 'border-border'"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <input
      v-else
      :id="id"
      :type="type"
      :value="modelValue"
      v-bind="$attrs"
      class="w-full px-3.5 py-2.5 text-sm border rounded-xl bg-input-background outline-none transition placeholder-gray-400 focus:ring-2 focus:ring-primary/30 focus:border-primary/60"
      :class="error ? 'border-destructive' : 'border-border'"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>
