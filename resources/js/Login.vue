<script setup>
import { ref, onMounted } from 'vue';

const email = ref('');
const password = ref('');
const loading = ref(false);
const isDark = ref(false);

const toasts = ref([]);
let toastId = 0;

const showToast = (message, type = 'success') => {
    const id = toastId++;
    toasts.value.push({ id, message, type });
    setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }, 4000);
};

const handleLogin = async () => {
    if (!email.value || !password.value) {
        showToast('Masukkan email dan password', 'error');
        return;
    }
    loading.value = true;
    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                email: email.value,
                password: password.value
            })
        });

        const data = await res.json();

        if (res.ok && data.success) {
            showToast('Selamat datang!', 'success');
            setTimeout(() => {
                window.location.href = '/';
            }, 1000);
        } else {
            const errMsg = data.errors?.email?.[0] || 'Login gagal. Coba lagi.';
            showToast(errMsg, 'error');
        }
    } catch (err) {
        showToast('Koneksi error. Coba lagi.', 'error');
    } finally {
        loading.value = false;
    }
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    const storedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <div
        class="min-h-screen bg-[#94d2ff]/15 dark:bg-[#07090c] text-neutral-900 dark:text-neutral-100 font-sans transition-colors duration-500 flex flex-col justify-center items-center relative overflow-hidden bg-grid-dots selection:bg-brand-moss selection:text-white">

        <div
            class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-brand-moss/12 dark:bg-brand-moss/6 blur-[125px] pointer-events-none z-0 animate-pulse-glow-1">
        </div>
        <div
            class="absolute bottom-[-10%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-brand-steel/12 dark:bg-brand-steel/6 blur-[145px] pointer-events-none z-0 animate-pulse-glow-2">
        </div>

        <div class="absolute top-6 right-6 flex items-center gap-4 z-10">
            <a href="/" class="text-xs font-mono text-neutral-450 hover:text-brand-moss transition-colors">
                Back to Home
            </a>
            <button @click="toggleTheme"
                class="w-8 h-8 flex items-center justify-center border border-neutral-200 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-all text-sm cursor-pointer focus:outline-none bg-white/50 dark:bg-neutral-900/50">
                <span v-if="isDark">☀️</span>
                <span v-else>🌙</span>
            </button>
        </div>

        <div class="w-full max-w-md px-6 relative z-10">
            <div class="bento-card rounded-3xl p-8 sm:p-10 space-y-8">

                <div class="text-center space-y-2">
                    <span
                        class="text-xs font-mono uppercase tracking-widest text-brand-moss bg-brand-moss/10 px-3 py-1 rounded-full border border-brand-moss/20">
                        Owner Panel
                    </span>
                    <h1
                        class="text-2xl sm:text-3xl font-serif font-extrabold tracking-tight mt-2 text-neutral-900 dark:text-neutral-50">
                        Authentication Required
                    </h1>
                    <p class="text-xs font-mono text-neutral-450">
                        Authorized owner workspace only.
                    </p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-4">
                    <div class="space-y-1">
                        <label for="email"
                            class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Email
                            Address</label>
                        <input id="email" v-model="email" type="email" placeholder="owner@example.com" required
                            class="w-full text-sm font-sans p-3 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                    </div>

                    <div class="space-y-1">
                        <label for="password"
                            class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Password</label>
                        <input id="password" v-model="password" type="password" placeholder="••••••••" required
                            class="w-full text-sm font-sans p-3 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full text-sm font-mono py-3 px-4 mt-6 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-brand-moss dark:hover:bg-brand-moss hover:text-white dark:hover:text-white rounded-full transition-colors disabled:opacity-50 cursor-pointer">
                        {{ loading ? 'Authenticating...' : 'Masuk' }}
                    </button>
                </form>

            </div>
        </div>

        <div class="fixed bottom-6 right-6 z-50 space-y-3 max-w-sm pointer-events-none">
            <transition-group name="toast">
                <div v-for="toast in toasts" :key="toast.id"
                    class="p-4 rounded-full shadow-lg flex items-center justify-between border text-sm font-sans pointer-events-auto transition-all duration-300 bg-white/90 dark:bg-[#0c0c0b]/90 backdrop-blur-md border-neutral-200 dark:border-neutral-800">
                    <div class="flex items-center gap-2 px-2">
                        <span v-if="toast.type === 'success'" class="text-brand-moss">✓</span>
                        <span v-else-if="toast.type === 'error'" class="text-red-500">✕</span>
                        <span v-else class="text-brand-steel">ℹ</span>
                        <span class="text-neutral-850 dark:text-neutral-250">{{ toast.message }}</span>
                    </div>
                </div>
            </transition-group>
        </div>

    </div>
</template>

<style>
.toast-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.toast-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
</style>
