import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
});

// Add a response interceptor to handle 401 Unauthorized globally
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      // Potentially clear token or trigger logout context here
      // For now, let the components handle the redirect
    }
    return Promise.reject(error);
  }
);

export default api;
