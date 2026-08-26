import axios from 'axios'

// Xem SPEC_smart-drink-recommendation-app.md muc 2.3 (Thiet ke API chinh)
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

export default apiClient
