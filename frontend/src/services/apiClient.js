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
//console.log('XYZ+')
apiClient.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
         console.log('xyz+');
        if (error.response?.status === 401 && !originalRequest._retry) {
            console.log('xyz++');
           // originalRequest._retry = true;
            console.log('xyz+++');
            try {
                console.log('bzz');
                await apiClient.post(import.meta.env.VITE_API_URL + "/api/token/refresh", {
                    refresh_token: localStorage.getItem("refresh_token"),
                }).then((response) => {
                    console.log('response: ', response);
                    localStorage.setItem("token", response.token);
                    apiClient.headers.common.Authorization = "Bearer " + response.token;
                    originalRequest.headers["Authorization"] = "Bearer " + response.token;
                    return apiClient(originalRequest);
                    //localStorage.setItem('refresh_token', response.data.refresh_token);
                });

                // localStorage.setItem("token", response.token);
                // apiClient.defaults.headers.common["Authorization"] = "Bearer " + response.token;
                // originalRequest.headers["Authorization"] = "Bearer " + response.token;
                // return apiClient(originalRequest);
            } catch (refreshError) {
                console.error("Refresh token failed", refreshError);
                // np. redirect do login
            }
        }
        return Promise.reject(error);
    }
);


export default apiClient;
