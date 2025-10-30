import axios from 'axios';

const $token = await localStorage.getItem('token') ?? '';

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': $token !== '' ? 'Bearer ' + $token : false
    }
});

apiClient.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
        if (error.response?.status === 401 && !originalRequest._retry) {
            try {
                await apiClient.post(import.meta.env.VITE_API_URL + "/api/token/refresh", {
                    refresh_token: localStorage.getItem("refresh_token"),
                }).then((response) => {
                    localStorage.setItem("token", response.token);
                    apiClient.headers.common.Authorization = "Bearer " + response.token;
                    originalRequest.headers["Authorization"] = "Bearer " + response.token;
                    return apiClient(originalRequest);
                });
            } catch (refreshError) {
                console.error("Refresh token failed", refreshError);
            }
        }
        return Promise.reject(error);
    }
);

export default apiClient;
