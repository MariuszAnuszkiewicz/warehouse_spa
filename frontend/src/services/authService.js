import { ref } from 'vue';
import axios from 'axios';
import router from '@/router';

const authService = () => {

    const loginStatus = ref(null);
    const switchLink = ref(true);
    const email = ref('');
    const password = ref('');
    const name = ref('');
    const errors = ref([]);

    const $forms = {
        loginForm: {
            email: email.value,
            password: password.value
        },
        registerForm: {
            email: email.value,
            password: password.value,
            name: name.value
        }
    }

    const resetLoginForm = () => {
        $forms.loginForm.email = "";
        $forms.loginForm.password = "";
    }

    const resetRegisterForm = () => {
        $forms.registerForm.email = "";
        $forms.registerForm.password = "";
        $forms.registerForm.name = "";
    }

    const checkLoginStatus = () => {
        if (localStorage.getItem('token') !== null) {
            loginStatus.value = true;
        } else {
            loginStatus.value = false;
        }
    };

    const checkIsLoginUrl = () => {
        if (window.location.href.indexOf('login') !== -1) {
            switchLink.value = true;
        } else {
            switchLink.value = false;
        }
    };

    const handleLogin = async () => {
        try {
            const response = await axios.post(`${import.meta.env.VITE_API_URL}/api/login`, $forms.loginForm);

            localStorage.setItem('token', response.data.token);
            localStorage.setItem('refresh_token', response.data.refresh_token);
            resetLoginForm();
            return true;
        } catch (error) {
            console.warn('error: ', error);
        }
    };

    const handleRegister = async () => {
        try {
            await axios.post(`${import.meta.env.VITE_API_URL}/api/register`, $forms.registerForm)
            resetRegisterForm();
            return true;
        } catch (error) {
            errors.value = error.response.data.errors;
            console.warn('error: ', error.response.data.errors);
        }
    };

    const logout = () => {
        localStorage.removeItem('token');
        localStorage.removeItem('refresh_token');
        redirectTo('/login');
    };

    const redirectTo = (url) => {
        router.push(url);
    };

    return {
        loginStatus,
        switchLink,
        $forms,
        checkLoginStatus,
        checkIsLoginUrl,
        logout,
        handleRegister,
        handleLogin,
        redirectTo,
        errors
    }
};

export default authService();