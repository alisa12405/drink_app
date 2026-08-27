<script setup>
import { CheckCircle2, Lock, ShoppingCart } from 'lucide-vue-next'

defineProps({
  currentStep: {
    type: Number,
    default: 0, // 0 = Giỏ hàng, 1 = Thanh toán, 2 = Hoàn tất
  },
})

const steps = [
  { label: 'Giỏ hàng', icon: ShoppingCart },
  { label: 'Thanh toán', icon: Lock },
  { label: 'Hoàn tất', icon: CheckCircle2 },
]
</script>

<template>
  <div class="flex items-center">
    <template v-for="(step, index) in steps" :key="step.label">
      <div class="flex items-center">
        <div class="flex flex-col items-center gap-1">
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all"
            :class="
              index <= currentStep
                ? 'bg-primary border-primary text-primary-foreground'
                : 'bg-card border-border text-muted-foreground'
            "
          >
            <component :is="step.icon" :size="14" />
          </div>
          <span
            class="text-[10px] font-semibold whitespace-nowrap"
            :class="index <= currentStep ? 'text-primary' : 'text-muted-foreground'"
          >
            {{ step.label }}
          </span>
        </div>
        <div
          v-if="index < steps.length - 1"
          class="w-10 sm:w-16 h-0.5 mb-4 mx-1"
          :class="index < currentStep ? 'bg-primary/60' : 'bg-border'"
        />
      </div>
    </template>
  </div>
</template>
