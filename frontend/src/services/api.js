import axios from 'axios'

// Xem SPEC_smart-drink-recommendation-app.md muc 2.3 (Thiet ke API chinh)
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api',
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

export const adminDrinksApi = {
  list: (params) => apiClient.get('/admin/drinks', { params }),
  show: (id) => apiClient.get(`/admin/drinks/${id}`),
  create: (payload) => apiClient.post('/admin/drinks', payload),
  update: (id, payload) => apiClient.put(`/admin/drinks/${id}`, payload),
  remove: (id) => apiClient.delete(`/admin/drinks/${id}`),
}

export default apiClient
