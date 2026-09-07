import axios from 'axios';

const API = axios.create({
    baseURL: '/api/',
    withCredentials: true,
    headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
    }
});

// Request Interceptor to add Authorization token
API.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('__sesion_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response Interceptor to handle 401 Unauthorized globally
API.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            // Unauthorized, redirect to login page using the base URL
            window.location.href = import.meta.env.BASE_URL;
        }
        return Promise.reject(error);
    }
);

export default API;