<script setup>
defineProps({
  compactOnMobile: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
  },
})

const iconSizeClass = {
  sm: 'w-8 h-8',
  md: 'w-10 h-10',
  lg: 'w-14 h-14',
}

const nameSizeClass = {
  sm: 'text-base',
  md: 'text-xl',
  lg: 'text-2xl',
}
</script>

<template>
  <div class="flex items-center gap-2.5 group">
    <!-- Modern SVG Logo Icon -->
    <div
      class="shrink-0 relative flex items-center justify-center rounded-2xl overflow-hidden shadow-sm transition-transform duration-200 group-hover:scale-105"
      :class="iconSizeClass[size]"
    >
      <svg
        viewBox="0 0 100 100"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full h-full"
        aria-hidden="true"
      >
        <defs>
          <linearGradient id="sd-logo-bg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#b45309" />
            <stop offset="50%" stop-color="#d97706" />
            <stop offset="100%" stop-color="#92400e" />
          </linearGradient>
          <linearGradient id="sd-logo-drink" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#fed7aa" />
            <stop offset="100%" stop-color="#f59e0b" />
          </linearGradient>
          <linearGradient id="sd-logo-leaf" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#4ade80" />
            <stop offset="100%" stop-color="#16a34a" />
          </linearGradient>
        </defs>

        <!-- Background Squircle -->
        <rect width="100" height="100" rx="26" fill="url(#sd-logo-bg)" />
        <rect
          x="3"
          y="3"
          width="94"
          height="94"
          rx="23"
          stroke="#ffffff"
          stroke-opacity="0.25"
          stroke-width="2"
          fill="none"
        />

        <!-- Straw -->
        <path d="M57 20 L66 10" stroke="#fef3c7" stroke-width="4.5" stroke-linecap="round" />
        <path d="M50 29 L57 20" stroke="#fef3c7" stroke-width="4.5" stroke-linecap="round" />

        <!-- Cup Rim -->
        <rect x="29" y="27" width="42" height="5" rx="2.5" fill="#ffffff" />

        <!-- Cup Body -->
        <path
          d="M33 32 L39 74 Q40 80 46 80 L54 80 Q60 80 61 74 L67 32 Z"
          fill="#ffffff"
          fill-opacity="0.22"
        />

        <!-- Drink Liquid Wave -->
        <path
          d="M35 46 Q43 43 50 46 T65 46 L61 74 Q60 79 55 79 L45 79 Q40 79 39 74 Z"
          fill="url(#sd-logo-drink)"
        />

        <!-- Boba Pearls -->
        <circle cx="44" cy="71" r="3.5" fill="#78350f" />
        <circle cx="53" cy="72" r="3.5" fill="#78350f" />
        <circle cx="48" cy="65" r="3" fill="#78350f" />
        <circle cx="56" cy="64" r="2.8" fill="#78350f" />

        <!-- Ice Cube -->
        <rect
          x="42"
          y="50"
          width="8"
          height="8"
          rx="2"
          fill="#ffffff"
          fill-opacity="0.45"
          transform="rotate(12 42 50)"
        />

        <!-- Tea Leaf Accent -->
        <path
          d="M68 22 C68 22 76 19 78 26 C78 33 71 33 68 33 C67 29 67 25 68 22 Z"
          fill="url(#sd-logo-leaf)"
        />

        <!-- Smart Sparkle (AI) -->
        <path
          d="M26 21 L27.5 24.5 L31 26 L27.5 27.5 L26 31 L24.5 27.5 L21 26 L24.5 24.5 Z"
          fill="#fef08a"
        />
      </svg>
    </div>

    <!-- Brand Text -->
    <div :class="compactOnMobile ? 'hidden min-[400px]:block' : ''">
      <div class="flex items-center gap-1.5">
        <span class="font-heading font-extrabold text-foreground tracking-tight leading-none" :class="nameSizeClass[size]">
          Smart Drink
        </span>
        <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
      </div>
      <p class="text-[10px] text-primary/90 font-bold tracking-widest uppercase leading-none mt-1">
        Trà sữa &amp; Cà phê AI
      </p>
    </div>
  </div>
</template>
