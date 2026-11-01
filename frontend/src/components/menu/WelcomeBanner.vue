<script setup>
import { Sparkles, Clock, ShieldCheck } from 'lucide-vue-next'

defineProps({
  name: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div
    class="relative rounded-3xl bg-gradient-to-r from-primary via-primary-hover to-[#78350f] text-primary-foreground px-6 py-7 md:px-10 md:py-9 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 overflow-hidden shadow-md shadow-primary/10"
  >
    <!-- Background Decorative Glow Elements -->
    <div
      class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-accent/20 blur-3xl pointer-events-none"
      aria-hidden="true"
    ></div>
    <div
      class="absolute left-1/3 -bottom-16 w-52 h-52 rounded-full bg-white/10 blur-2xl pointer-events-none"
      aria-hidden="true"
    ></div>

    <div class="relative z-10 max-w-xl">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm border border-white/20 text-xs font-semibold mb-3">
        <Sparkles :size="13" class="text-amber-300" />
        <span>Gợi ý thông minh theo sở thích &amp; thời tiết</span>
      </div>

      <h1 class="font-heading font-extrabold text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
        Chào mừng {{ name ? name : 'bạn' }} đến với Smart Drink!
      </h1>

      <p class="mt-2 text-sm sm:text-base text-primary-foreground/90 font-medium leading-relaxed">
        Thưởng thức hơn 50 hương vị trà sữa, cà phê và thức uống tươi ngon được pha chế riêng theo khẩu vị của bạn.
      </p>

      <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-primary-foreground/80 font-medium">
        <span class="inline-flex items-center gap-1.5 bg-black/15 px-2.5 py-1 rounded-lg">
          <Clock :size="13" />
          Pha chế nhanh 5–10 phút
        </span>
        <span class="inline-flex items-center gap-1.5 bg-black/15 px-2.5 py-1 rounded-lg">
          <ShieldCheck :size="13" />
          100% Nguyên liệu chọn lọc
        </span>
      </div>
    </div>

    <!-- Illustrated Drink Badge -->
    <div class="hidden md:flex relative z-10 shrink-0 items-center justify-center">
      <div class="w-32 h-32 lg:w-36 lg:h-36 rounded-3xl bg-white/10 backdrop-blur-md border border-white/25 shadow-xl flex items-center justify-center p-4 transform rotate-2 hover:rotate-0 transition-transform duration-300">
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full drop-shadow-md">
          <defs>
            <linearGradient id="wb-drink-grad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#fef3c7" />
              <stop offset="100%" stop-color="#f59e0b" />
            </linearGradient>
            <linearGradient id="wb-leaf-grad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#86efac" />
              <stop offset="100%" stop-color="#22c55e" />
            </linearGradient>
          </defs>

          <!-- Straw -->
          <path d="M57 18 L67 8" stroke="#fef3c7" stroke-width="5" stroke-linecap="round" />
          <path d="M50 28 L57 18" stroke="#fef3c7" stroke-width="5" stroke-linecap="round" />

          <!-- Cup Rim -->
          <rect x="28" y="26" width="44" height="6" rx="3" fill="#ffffff" />

          <!-- Cup Body -->
          <path d="M32 32 L39 76 Q40 82 46 82 L54 82 Q60 82 61 76 L68 32 Z" fill="#ffffff" fill-opacity="0.3" />

          <!-- Drink Fluid -->
          <path d="M34 46 Q43 43 50 46 T66 46 L61 76 Q60 81 55 81 L45 81 Q40 81 39 76 Z" fill="url(#wb-drink-grad)" />

          <!-- Boba Pearls -->
          <circle cx="43" cy="73" r="3.8" fill="#451a03" />
          <circle cx="53" cy="74" r="3.8" fill="#451a03" />
          <circle cx="48" cy="66" r="3.5" fill="#451a03" />
          <circle cx="57" cy="65" r="3.2" fill="#451a03" />

          <!-- Floating Ice -->
          <rect x="42" y="50" width="9" height="9" rx="2" fill="#ffffff" fill-opacity="0.6" transform="rotate(15 42 50)" />

          <!-- Fresh Leaf -->
          <path d="M70 20 C70 20 80 17 82 25 C82 33 74 33 70 33 C69 28 69 24 70 20 Z" fill="url(#wb-leaf-grad)" />

          <!-- Golden Star Sparkle -->
          <path d="M22 18 L24 22 L28 24 L24 26 L22 30 L20 26 L16 24 L20 22 Z" fill="#fef08a" />
        </svg>
      </div>
    </div>
  </div>
</template>
