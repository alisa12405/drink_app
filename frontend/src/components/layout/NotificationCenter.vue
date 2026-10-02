<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, CheckCheck, PackageCheck, ShoppingBag, X } from 'lucide-vue-next'
import { notificationsApi } from '@/services/api'

const router = useRouter()
const notifications = ref([])
const unreadCount = ref(0)
const isOpen = ref(false)
const isLoading = ref(false)
const toasts = ref([])
const knownIds = new Set()
let initialized = false
let pollTimer

function iconFor(kind) {
  return kind === 'new_order' ? ShoppingBag : PackageCheck
}

function relativeTime(value) {
  if (!value) return ''
  const diff = Math.max(0, Date.now() - new Date(value).getTime())
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'Vừa xong'
  if (minutes < 60) return `${minutes} phút trước`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} giờ trước`
  return new Date(value).toLocaleDateString('vi-VN')
}

function dismissToast(id) {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

function showToast(notification) {
  toasts.value = [notification, ...toasts.value].slice(0, 3)
  window.setTimeout(() => dismissToast(notification.id), 6500)
}

async function loadNotifications({ showNewToasts = true } = {}) {
  if (!initialized) isLoading.value = true
  try {
    const { data } = await notificationsApi.list()
    const incoming = data.data ?? []

    if (initialized && showNewToasts) {
      incoming
        .filter((item) => !item.read_at && !knownIds.has(item.id))
        .reverse()
        .forEach(showToast)
    }

    incoming.forEach((item) => knownIds.add(item.id))
    notifications.value = incoming
    unreadCount.value = data.meta?.unread_count ?? 0
    initialized = true
  } catch {
    // Polling failure must not disturb the current page.
  } finally {
    isLoading.value = false
  }
}

async function openNotification(notification) {
  if (!notification.read_at) {
    try {
      await notificationsApi.markRead(notification.id)
      notification.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch {
      return
    }
  }

  isOpen.value = false
  dismissToast(notification.id)
  if (notification.url) await router.push(notification.url)
}

async function markAllAsRead() {
  if (unreadCount.value === 0) return
  await notificationsApi.markAllRead()
  const readAt = new Date().toISOString()
  notifications.value.forEach((notification) => {
    notification.read_at ||= readAt
  })
  unreadCount.value = 0
}

onMounted(() => {
  void loadNotifications({ showNewToasts: false })
  pollTimer = window.setInterval(() => void loadNotifications(), 10_000)
})

onBeforeUnmount(() => window.clearInterval(pollTimer))
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="relative p-2 text-muted-foreground hover:text-primary transition-colors"
      aria-label="Thông báo"
      @click="isOpen = !isOpen"
    >
      <Bell :size="21" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-5 h-5 px-1 bg-primary text-primary-foreground rounded-full text-[10px] font-bold flex items-center justify-center"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-2 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 top-11 z-[70] w-[min(23rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-border bg-card shadow-xl"
      >
        <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
          <div>
            <p class="font-heading font-bold text-foreground">Thông báo</p>
            <p class="text-[11px] text-muted-foreground">{{ unreadCount }} thông báo chưa đọc</p>
          </div>
          <button
            type="button"
            class="text-xs font-semibold text-primary disabled:text-muted-foreground"
            :disabled="unreadCount === 0"
            @click="markAllAsRead"
          >
            <CheckCheck :size="15" class="inline mr-1" /> Đọc tất cả
          </button>
        </div>

        <div class="max-h-[26rem] overflow-y-auto">
          <p v-if="isLoading" class="px-4 py-8 text-center text-sm text-muted-foreground">Đang tải…</p>
          <p v-else-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
            Chưa có thông báo nào.
          </p>
          <template v-else>
            <button
              v-for="notification in notifications"
              :key="notification.id"
              type="button"
              class="w-full border-b border-border/70 px-4 py-3 text-left transition-colors hover:bg-secondary/50 last:border-0"
              :class="notification.read_at ? 'bg-card' : 'bg-secondary/30'"
              @click="openNotification(notification)"
            >
              <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-secondary text-primary">
                  <component :is="iconFor(notification.kind)" :size="17" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-foreground">{{ notification.title }}</span>
                    <span v-if="!notification.read_at" class="h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                  </span>
                  <span class="mt-0.5 block text-xs leading-relaxed text-muted-foreground">{{ notification.message }}</span>
                  <span class="mt-1 block text-[10px] text-muted-foreground">{{ relativeTime(notification.created_at) }}</span>
                </span>
              </div>
            </button>
          </template>
        </div>
      </div>
    </Transition>
  </div>

  <Teleport to="body">
    <div class="pointer-events-none fixed bottom-5 right-5 z-[100] flex w-[min(23rem,calc(100vw-2.5rem))] flex-col gap-3">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto rounded-2xl border border-primary/20 bg-card p-4 shadow-2xl"
        >
          <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary text-primary">
              <component :is="iconFor(toast.kind)" :size="19" />
            </span>
            <button type="button" class="min-w-0 flex-1 text-left" @click="openNotification(toast)">
              <span class="block text-sm font-bold text-foreground">{{ toast.title }}</span>
              <span class="mt-1 block text-xs leading-relaxed text-muted-foreground">{{ toast.message }}</span>
            </button>
            <button type="button" class="text-muted-foreground hover:text-foreground" @click="dismissToast(toast.id)">
              <X :size="16" />
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
