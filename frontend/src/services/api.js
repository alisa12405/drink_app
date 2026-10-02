import axios from 'axios'

// Xem SPEC_smart-drink-recommendation-app.md muc 2.3 (Thiet ke API chinh)
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !error.config?.url?.includes('/auth/login')) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      if (window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    }
    return Promise.reject(error)
  },
)

export const authApi = {
  register: (payload) => apiClient.post('/auth/register', payload),
  login: (payload) => apiClient.post('/auth/login', payload),
  logout: () => apiClient.post('/auth/logout'),
  me: () => apiClient.get('/auth/me'),
}

export const drinksApi = {
  list: (params) => apiClient.get('/drinks', { params }),
  show: (id) => apiClient.get(`/drinks/${id}`),
}

export const recommendationsApi = {
  context: (params) => apiClient.get('/recommendation-context', { params }),
  list: (params) => apiClient.get('/recommendations', { params }),
}

export const notificationsApi = {
  list: () => apiClient.get('/notifications'),
  markRead: (id) => apiClient.patch(`/notifications/${id}/read`),
  markAllRead: () => apiClient.patch('/notifications/read-all'),
}

export const adminDrinksApi = {
  list: (params) => apiClient.get('/admin/drinks', { params }),
  show: (id) => apiClient.get(`/admin/drinks/${id}`),
  create: (payload) => apiClient.post('/admin/drinks', payload),
  update: (id, payload) => apiClient.put(`/admin/drinks/${id}`, payload),
  uploadImage: (id, image) => {
    const formData = new FormData()
    formData.append('image', image)
    return apiClient.post(`/admin/drinks/${id}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  remove: (id) => apiClient.delete(`/admin/drinks/${id}`),
}

export const preferencesApi = {
  show: () => apiClient.get('/preferences'),
  update: (payload) => apiClient.put('/preferences', payload),
}

export const ordersApi = {
  create: (payload) => apiClient.post('/orders', payload),
  history: (params) => apiClient.get('/orders/history', { params }),
  show: (id) => apiClient.get(`/orders/${id}`),
  cancel: (id) => apiClient.patch(`/orders/${id}/cancel`),
}

export const ratingsApi = {
  create: (payload) => apiClient.post('/ratings', payload),
  list: () => apiClient.get('/ratings'),
}

export const adminOrdersApi = {
  list: (params) => apiClient.get('/admin/orders', { params }),
  show: (id) => apiClient.get(`/admin/orders/${id}`),
  updateStatus: (id, status) => apiClient.patch(`/admin/orders/${id}/status`, { status }),
}

export const adminReportsApi = {
  bestSellingDrinks: (params) => apiClient.get('/admin/reports/best-selling-drinks', { params }),
  recommendationEffectiveness: (params) =>
    apiClient.get('/admin/reports/recommendation-effectiveness', { params }),
}

export default apiClient
