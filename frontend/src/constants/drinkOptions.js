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
