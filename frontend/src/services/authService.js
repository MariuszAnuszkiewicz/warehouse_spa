import { ref, reactive } from 'vue';
import axios from 'axios';

const authService = () => {

    const loginStatus = ref(null);
    const switchLink = ref(true);
    const token = ref('');
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
            await axios.post(import.meta.env.VITE_API_URL + '/api/login', $forms.loginForm)
           .then((response) => {
               token.value = response.data.token;
            });

            localStorage.setItem('token', token.value);
            resetLoginForm();
            return true;
        } catch (error) {
            console.warn('error: ', error);
        }
    };

    const handleRegister = async () => {
        try {
            await axios.post(import.meta.env.VITE_API_URL + '/api/register', $forms.registerForm)
            resetRegisterForm();
            return true;
        } catch (error) {
            errors.value = error.response.data.errors;
            console.warn('error: ', error.response.data.errors);
        }
    };

    const logout = () => {
        localStorage.removeItem('token');
        redirectTo('/login');
    };

    const redirectTo = ($url) => {
        window.location.href = $url;
    };

    const detect401 = (status = 401) => {
        if (status === 401) {
            window.location.href = '/login';
            return true;
        }
    }

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
        detect401,
        errors
    }
};

export default authService();