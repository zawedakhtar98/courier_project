import { defineStore } from "pinia";
import { ref } from "vue";
import { login as loginService } from '@/services/auth';
import { logout as logoutService } from '@/services/auth';
import API from '@/services/ApiService';
import { useToast } from "vue-toastification";
const toast = useToast();

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isLogin = ref(false);
    const isReady = ref(false); // Flag to check if we verified initial session

    const checkAuth = async () => {
        try {
            // Adjust this endpoint if your backend uses a different route to fetch the authenticated user
            // debugger
            const resp = await API.get('login-user');
            // debugger
            if (resp.status === 200) {
                user.value = resp.data;
                isLogin.value = true;
            }
        } catch (err) {
            user.value = null;
            isLogin.value = false;
        } finally {
            isReady.value = true;
        }
    };

    const login = async (data) => {
        try {
            // debugger
            const resp = await loginService(data);
            // debugger
            if (resp?.status === 'success' || resp?.data?.user) {
                user.value = resp.data?.user;
                isLogin.value = true;
                if (resp.data?.token) {
                    localStorage.setItem('__session_token', resp.data.token);
                    toast.success("Successfully logged in!");
                }
                return true;
            }
            return false;
        } catch (err) {
            console.log(err);
        }
    }

    const logout = async () => {
        try {
            await logoutService();
            user.value = null;
            isLogin.value = false;
            localStorage.removeItem('__session_token');
            toast.info("Successfully logged out!");
        } catch (err) {
            console.log(err);
        }
    }

    return { user, isLogin, isReady, checkAuth, login, logout };
})