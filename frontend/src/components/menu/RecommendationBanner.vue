<script setup>
import { Sparkles } from 'lucide-vue-next'
</script>

<template>
  <div
    class="rounded-2xl border-2 border-dashed border-accent bg-secondary/50 px-5 py-4 flex items-center gap-3 text-secondary-foreground"
  >
    <Sparkles :size="20" class="shrink-0 text-primary" />
    <div class="text-sm">
      <p class="font-semibold">Gợi ý cho bạn</p>
      <p class="text-muted-foreground">
        Tính năng gợi ý cá nhân hoá theo sở thích &amp; ngữ cảnh (UC-04) sẽ hiển thị ở đây khi hoàn thiện.
      </p>
    </div>
  </div>
</template>
