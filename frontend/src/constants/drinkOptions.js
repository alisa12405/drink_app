export const SUGAR_OPTIONS = [
  { value: '0', label: '0% (không đường)' },
  { value: '30', label: '30% (ít đường)' },
  { value: '50', label: '50% (vừa đường)' },
  { value: '70', label: '70% (ngọt)' },
  { value: '100', label: '100% (ngọt đậm)' },
]

export const ICE_OPTIONS = [
  { value: 'no_ice', label: 'Không đá' },
  { value: 'less_ice', label: 'Ít đá' },
  { value: 'normal_ice', label: 'Đá bình thường' },
  { value: 'extra_ice', label: 'Nhiều đá' },
]

export const ORDER_STATUS_LABELS = {
  pending: 'Chờ xác nhận',
  confirmed: 'Đã xác nhận',
  done: 'Hoàn tất',
  cancelled: 'Đã huỷ',
}

export const ORDER_STATUS_BADGE_CLASSES = {
  pending: 'bg-accent text-accent-foreground',
  confirmed: 'bg-blue-50 text-blue-700',
  done: 'bg-success-bg text-success',
  cancelled: 'bg-destructive-bg text-destructive',
}

export const ORDER_TYPE_OPTIONS = [
  { value: 'dine_in', label: 'Dùng tại quán', icon: '🏠' },
  { value: 'takeaway', label: 'Mang đi', icon: '🥤' },
]

export function getOrderTypeInfo(orderType) {
  return ORDER_TYPE_OPTIONS.find((opt) => opt.value === orderType) ?? null
}

export const TEMPERATURE_OPTIONS = [
  { value: 'hot', label: 'Nóng', icon: '🔥' },
  { value: 'cold', label: 'Lạnh', icon: '🧊' },
  { value: 'both', label: 'Nóng & lạnh', icon: '🔥🧊' },
]

export const TASTE_TAG_PRESETS = [
  { value: 'ngọt', label: 'Ngọt' },
  { value: 'ít_ngọt', label: 'Ít ngọt' },
  { value: 'có_caffeine', label: 'Có caffeine' },
  { value: 'không_caffeine', label: 'Không caffeine' },
  { value: 'trái_cây', label: 'Trái cây' },
  { value: 'thanh_mát', label: 'Thanh mát' },
  { value: 'truyền_thống', label: 'Truyền thống' },
  { value: 'thơm', label: 'Thơm' },
  { value: 'matcha', label: 'Matcha' },
  { value: 'giải_khát', label: 'Giải khát' },
]

export function getTasteTagLabel(tag) {
  return TASTE_TAG_PRESETS.find((preset) => preset.value === tag)?.label ?? tag
}

const CATEGORY_EMOJI = {
  'trà sữa': '🧋',
  'trà trái cây': '🍹',
  'cà phê': '☕',
  'nước ép': '🍊',
}

export function getCategoryEmoji(category) {
  return CATEGORY_EMOJI[category?.toLowerCase()] ?? '🥤'
}
