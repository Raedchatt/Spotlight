import { ref } from 'vue';

const isLoginOpen = ref(false);
const isRegisterOpen = ref(false);

export function useAuthModal() {
    const openLogin = () => {
        isLoginOpen.value = true;
        isRegisterOpen.value = false;
    };

    const openRegister = () => {
        isRegisterOpen.value = true;
        isLoginOpen.value = false;
    };

    const closeAll = () => {
        isLoginOpen.value = false;
        isRegisterOpen.value = false;
    };

    return {
        isLoginOpen,
        isRegisterOpen,
        openLogin,
        openRegister,
        closeAll
    };
}
