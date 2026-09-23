const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
const TOKEN_KEY = 'mei_auth_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  if (token) localStorage.setItem(TOKEN_KEY, token)
  else localStorage.removeItem(TOKEN_KEY)
}

async function request(path, { method = 'GET', body } = {}) {
  const headers = { 'Content-Type': 'application/json' }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`

  const res = await fetch(`${API_URL}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  })

  let data = null
  try {
    data = await res.json()
  } catch {
    // resposta sem corpo (ex: 204)
  }

  if (!res.ok) {
    const message = data?.error || 'Não foi possível completar a solicitação.'
    throw new Error(message)
  }

  return data
}

export const api = {
  register: (payload) => request('/auth/register', { method: 'POST', body: payload }),
  login: (payload) => request('/auth/login', { method: 'POST', body: payload }),
  logout: () => request('/auth/logout', { method: 'POST' }),
  me: () => request('/auth/me'),
  forgotPassword: (email) => request('/auth/forgot-password', { method: 'POST', body: { email } }),
  resetPassword: (token, nova_senha) =>
    request('/auth/reset-password', { method: 'POST', body: { token, nova_senha } }),

  saveCompany: (payload) => request('/companies', { method: 'POST', body: payload }),

  dashboard: (params = {}) => {
    const qs = new URLSearchParams(params).toString()
    return request(`/dashboard${qs ? `?${qs}` : ''}`)
  },

  financial: {
    listCategories: (tipo) => request(`/financial/categories${tipo ? `?tipo=${tipo}` : ''}`),
    createCategory: (payload) => request('/financial/categories', { method: 'POST', body: payload }),
    updateCategory: (id, payload) => request(`/financial/categories?id=${id}`, { method: 'PUT', body: payload }),
    deleteCategory: (id) => request(`/financial/categories?id=${id}`, { method: 'DELETE' }),

    listEntries: (params = {}) => {
      const clean = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v != null))
      const qs = new URLSearchParams(clean).toString()
      return request(`/financial/entries${qs ? `?${qs}` : ''}`)
    },
    createEntry: (payload) => request('/financial/entries', { method: 'POST', body: payload }),
    updateEntry: (id, payload) => request(`/financial/entries?id=${id}`, { method: 'PUT', body: payload }),
    cancelEntry: (id) => request(`/financial/entries?id=${id}`, { method: 'DELETE' }),
  },
}
