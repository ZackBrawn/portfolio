<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';

const props = defineProps({
    projectId: {
        type: Number,
        required: true
    }
});

const project = ref(null);
const loading = ref(true);
const submittingComment = ref(false);
const isDark = ref(false);
const selectedImage = ref('');
const showMobileMenu = ref(false);

const authorName = ref('');
const commentContent = ref('');

const toasts = ref([]);
let toastId = 0;

const normalToasts = computed(() => {
    return toasts.value.filter(t => t.type !== 'confirm');
});

const confirmToasts = computed(() => {
    return toasts.value.filter(t => t.type === 'confirm');
});

const hasConfirmToast = computed(() => {
    return confirmToasts.value.length > 0;
});

const showToast = (message, type = 'success', onConfirm = null) => {
    const id = toastId++;
    toasts.value.push({ id, message, type, onConfirm });
    if (type !== 'confirm') {
        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== id);
        }, 4000);
    }
};

const dismissToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        showToast('Dark theme enabled', 'info');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        showToast('Light theme enabled', 'info');
    }
};

const fetchProjectDetails = async () => {
    try {
        const res = await fetch(`/api/projects/${props.projectId}`);
        if (!res.ok) throw new Error();
        project.value = await res.json();

        if (project.value.images_gallery) {
            const gallery = project.value.images_gallery.split(',');
            if (gallery.length > 0) {
                selectedImage.value = gallery[0];
            }
        } else {
            selectedImage.value = project.value.image_url;
        }
    } catch (err) {
        showToast('Failed to load project details', 'error');
    } finally {
        loading.value = false;
    }
};

const submitComment = async () => {
    if (!authorName.value.trim() || !commentContent.value.trim()) {
        showToast('Nama dan komentar harus diisi', 'error');
        return;
    }
    submittingComment.value = true;
    try {
        const res = await fetch(`/api/projects/${props.projectId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                author_name: authorName.value,
                content: commentContent.value
            })
        });

        if (!res.ok) throw new Error();

        const newComment = await res.json();
        project.value.comments.unshift(newComment);
        commentContent.value = '';
        showToast('Komentar berhasil', 'success');
    } catch (err) {
        showToast('Komentar gagal', 'error');
    } finally {
        submittingComment.value = false;
    }
};

const isOwner = ref(false);

const showEditModal = ref(false);
const submittingProject = ref(false);

const editTitle = ref('');
const editSummary = ref('');
const editDescription = ref('');
const editFocusAreas = ref('');
const editGithub = ref('');
const editLive = ref('');
const editImageFile = ref(null);
const editGalleryFiles = ref([]);

const coverPreviewUrl = ref('');
const galleryPreviewUrls = ref([]);

const openEditModal = () => {
    editTitle.value = project.value.title;
    editSummary.value = project.value.summary;
    editDescription.value = project.value.description;
    editFocusAreas.value = project.value.focus_areas;
    editGithub.value = project.value.github_url || '';
    editLive.value = project.value.live_url || '';

    coverPreviewUrl.value = project.value.image_url;
    galleryPreviewUrls.value = project.value.images_gallery ? project.value.images_gallery.split(',').filter(Boolean) : [];

    showEditModal.value = true;
};

const clearPreviews = () => {
    if (coverPreviewUrl.value && coverPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }
    coverPreviewUrl.value = '';

    galleryPreviewUrls.value.forEach(url => {
        if (url.startsWith('blob:')) {
            URL.revokeObjectURL(url);
        }
    });
    galleryPreviewUrls.value = [];
};

const closeEditModal = () => {
    showEditModal.value = false;
    editTitle.value = '';
    editSummary.value = '';
    editDescription.value = '';
    editFocusAreas.value = '';
    editGithub.value = '';
    editLive.value = '';
    editImageFile.value = null;
    editGalleryFiles.value = [];
    clearPreviews();
};

const handleMainImageChange = (e) => {
    const file = e.target.files[0];
    editImageFile.value = file || null;
    if (coverPreviewUrl.value && coverPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(coverPreviewUrl.value);
    }
    if (file) {
        coverPreviewUrl.value = URL.createObjectURL(file);
    } else {
        coverPreviewUrl.value = '';
    }
};

const handleGalleryImagesChange = (e) => {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;

    editGalleryFiles.value = [...editGalleryFiles.value, ...files];

    galleryPreviewUrls.value.forEach(url => {
        if (url.startsWith('blob:')) URL.revokeObjectURL(url);
    });

    const serverUrls = galleryPreviewUrls.value.filter(url => !url.startsWith('blob:'));
    const blobUrls = editGalleryFiles.value.map(file => URL.createObjectURL(file));
    galleryPreviewUrls.value = [...serverUrls, ...blobUrls];

    e.target.value = '';
};

const removeSelectedGalleryImage = (index) => {
    const url = galleryPreviewUrls.value[index];
    if (url.startsWith('blob:')) {
        URL.revokeObjectURL(url);
        const serverUrlsCount = galleryPreviewUrls.value.filter(u => !u.startsWith('blob:')).length;
        const fileIndex = index - serverUrlsCount;
        if (fileIndex >= 0) {
            editGalleryFiles.value.splice(fileIndex, 1);
        }
        galleryPreviewUrls.value.splice(index, 1);
    } else {
        showToast('Apakah anda yakin ingin menghapus gambar ini dari galeri?', 'confirm', async () => {
            try {
                const res = await fetch(`/api/projects/${props.projectId}/gallery-image`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ image_path: url })
                });
                const data = await res.json();
                if (res.ok) {
                    project.value.images_gallery = data.images_gallery;
                    galleryPreviewUrls.value = galleryPreviewUrls.value.filter(u => u !== url);
                    if (selectedImage.value === url) {
                        if (data.images_gallery) {
                            selectedImage.value = data.images_gallery.split(',')[0];
                        } else {
                            selectedImage.value = project.value.image_url;
                        }
                    }
                    showToast('Gambar berhasil dihapus dari galeri', 'success');
                } else {
                    showToast(data.error || 'Gagal menghapus gambar', 'error');
                }
            } catch (err) {
                showToast('Gagal menghapus gambar', 'error');
            }
        });
    }
};

const handleUpdateProject = async () => {
    if (!editTitle.value || !editSummary.value || !editDescription.value || !editFocusAreas.value) {
        showToast('Please fill all required fields', 'error');
        return;
    }

    submittingProject.value = true;
    const formData = new FormData();
    formData.append('title', editTitle.value);
    formData.append('summary', editSummary.value);
    formData.append('description', editDescription.value);
    formData.append('focus_areas', editFocusAreas.value);
    if (editGithub.value) formData.append('github_url', editGithub.value);
    if (editLive.value) formData.append('live_url', editLive.value);

    if (editImageFile.value) {
        formData.append('cover', editImageFile.value);
    }

    editGalleryFiles.value.forEach(file => {
        formData.append('gallery[]', file);
    });

    try {
        const res = await fetch(`/api/projects/${props.projectId}/update`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: formData
        });
        if (res.ok) {
            project.value = await res.json();
            selectedImage.value = project.value.image_url;
            showToast('Campaign updated successfully!', 'success');
            closeEditModal();
        } else {
            const data = await res.json();
            showToast(data.message || 'Failed to update campaign', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    } finally {
        submittingProject.value = false;
    }
};

const checkAuthStatus = async () => {
    try {
        const res = await fetch('/api/auth-check');
        const data = await res.json();
        isOwner.value = data.logged_in;
    } catch (err) {
        console.error(err);
    }
};

const deleteComment = (commentId) => {
    showToast('Yakin ingin menghapus komentar ini?', 'confirm', () => proceedDeleteComment(commentId));
};

const proceedDeleteComment = async (commentId) => {
    try {
        const res = await fetch(`/api/comments/${commentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        if (res.ok) {
            project.value.comments = project.value.comments.filter(c => c.id !== commentId);
            showToast('Komentar berhasil dihapus', 'success');
        } else {
            showToast('Gagal menghapus komentar', 'error');
        }
    } catch (err) {
        showToast('Koneksi error', 'error');
    }
};

const deleteProject = () => {
    showToast('Yakin ingin menghapus proyek ini?', 'confirm', () => proceedDeleteProject());
};

const proceedDeleteProject = async () => {
    try {
        const res = await fetch(`/api/projects/${props.projectId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        if (res.ok) {
            showToast('Project berhasil dihapus', 'success');
            setTimeout(() => {
                window.location.href = '/';
            }, 1000);
        } else {
            showToast('Gagal menghapus project', 'error');
        }
    } catch (err) {
        showToast('Koneksi error', 'error');
    }
};

const deleteGalleryImage = (imagePath) => {
    showToast('Yakin ingin menghapus gambar ini dari galeri?', 'confirm', () => proceedDeleteGalleryImage(imagePath));
};

const proceedDeleteGalleryImage = async (imagePath) => {
    try {
        const res = await fetch(`/api/projects/${props.projectId}/gallery-image`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ image_path: imagePath })
        });
        const data = await res.json();
        if (res.ok) {
            project.value.images_gallery = data.images_gallery;
            if (selectedImage.value === imagePath) {
                if (data.images_gallery) {
                    selectedImage.value = data.images_gallery.split(',')[0];
                } else {
                    selectedImage.value = project.value.image_url;
                }
            }
            showToast('Gambar berhasil dihapus dari galeri', 'success');
        } else {
            showToast(data.error || 'Gagal menghapus gambar', 'error');
        }
    } catch (err) {
        showToast('Koneksi error', 'error');
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

onMounted(() => {
    checkAuthStatus();
    fetchProjectDetails();

    const storedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }

    window.addEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
    const buttonEl = document.getElementById('mobile-options-button');
    const menuEl = document.getElementById('mobile-options-dropdown');
    if (showMobileMenu.value && buttonEl && menuEl) {
        if (!buttonEl.contains(event.target) && !menuEl.contains(event.target)) {
            showMobileMenu.value = false;
        }
    }
};

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div
        class="min-h-screen bg-[#94d2ff]/15 dark:bg-[#07090c] text-neutral-900 dark:text-neutral-100 font-sans transition-colors duration-500 flex flex-col justify-between selection:bg-brand-moss selection:text-white scroll-smooth relative overflow-hidden bg-grid-dots">

        <div
            class="absolute top-[-5%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-brand-moss/12 dark:bg-brand-moss/6 blur-[125px] pointer-events-none z-0 animate-pulse-glow-1">
        </div>
        <div
            class="absolute top-[15%] right-[-10%] w-[55vw] h-[55vw] rounded-full bg-brand-sky/25 dark:bg-brand-sky/12 blur-[150px] pointer-events-none z-0 animate-pulse-glow-2">
        </div>
        <div
            class="absolute top-[45%] left-[-15%] w-[60vw] h-[60vw] rounded-full bg-brand-sky/22 dark:bg-brand-sky/10 blur-[150px] pointer-events-none z-0 animate-pulse-glow-1">
        </div>
        <div
            class="absolute bottom-[2%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-brand-moss/10 dark:bg-brand-moss/5 blur-[135px] pointer-events-none z-0 animate-pulse-glow-2">
        </div>

        <!-- Desktop Centered Capsule Header (Hidden on Mobile) -->
        <div class="fixed top-6 left-0 right-0 z-40 px-4 pointer-events-none hidden sm:flex justify-center">
            <div
                class="pointer-events-auto flex items-center justify-between gap-3 sm:gap-6 px-4 sm:px-6 h-12 sm:h-14 bg-white/80 dark:bg-[#0c0c0b]/85 border border-neutral-200/50 dark:border-neutral-800 rounded-full backdrop-blur-lg shadow-[0_10px_30px_rgba(0,0,0,0.08)] dark:shadow-[0_12px_35_rgba(0,0,0,0.45)] max-w-xl w-full transition-all duration-300">
                <a href="/"
                    class="font-serif text-sm sm:text-base font-extrabold hover:text-brand-moss transition-colors">ZB</a>

                <span class="text-xs font-sans uppercase tracking-widest text-neutral-450 dark:text-neutral-400 font-semibold">Detail Project</span>

                <div class="flex items-center gap-3">
                    <button v-if="isOwner" @click="openEditModal"
                        class="text-xs font-sans text-brand-steel hover:underline font-semibold cursor-pointer focus:outline-none transition-colors"
                        title="Edit Project">
                        Edit
                    </button>
                    <button v-if="isOwner" @click="deleteProject"
                        class="text-xs font-sans text-red-500 hover:text-red-600 cursor-pointer focus:outline-none transition-colors"
                        title="Hapus Project">
                        Hapus
                    </button>
                    <a href="/#projects"
                        class="text-xs font-sans hover:text-brand-moss transition-colors flex items-center gap-1 font-semibold">
                        Kembali
                    </a>

                    <button @click="toggleTheme"
                        class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center border border-neutral-200/80 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-all text-xs sm:text-sm cursor-pointer focus:outline-none bg-white/50 dark:bg-neutral-900/50"
                        aria-label="Toggle Theme">
                        <span v-if="isDark">☀️</span>
                        <span v-else>🌙</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Floating Options Button & Dropdown (Visible only on Mobile) -->
        <div class="fixed top-6 left-6 z-40 sm:hidden pointer-events-none">
            <button id="mobile-options-button" @click="showMobileMenu = !showMobileMenu"
                class="pointer-events-auto flex items-center gap-2 border border-neutral-200 dark:border-neutral-800 px-3 py-1.5 rounded-full cursor-pointer focus:outline-none bg-white/80 dark:bg-[#0c0c0b]/80 backdrop-blur-lg shadow-md transition-all select-none animate-fade-in"
                :class="{ 'border-brand-moss text-brand-moss bg-brand-moss/5': showMobileMenu }">
                <span class="font-serif text-sm font-extrabold">ZB</span>
                <svg v-if="!showMobileMenu" class="w-3.5 h-3.5 text-neutral-500 dark:text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else class="w-3.5 h-3.5 text-neutral-500 dark:text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Mobile Dropdown Menu -->
            <transition name="slide-fade">
                <div id="mobile-options-dropdown" v-if="showMobileMenu" 
                    class="pointer-events-auto mt-2 p-3 bg-white/95 dark:bg-[#0c0c0b]/95 border border-neutral-200/50 dark:border-neutral-800 rounded-2xl backdrop-blur-lg shadow-xl flex flex-col gap-2.5 w-[200px] select-none text-left">
                    
                    <div class="flex items-center justify-between pb-2 border-b border-neutral-200/50 dark:border-neutral-800/40">
                        <a href="/" class="font-serif text-sm font-extrabold hover:text-brand-moss">ZB</a>
                        <button @click="toggleTheme"
                            class="w-6.5 h-6.5 flex items-center justify-center border border-neutral-200/80 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-all text-xs cursor-pointer focus:outline-none bg-white/50 dark:bg-neutral-900/50"
                            aria-label="Toggle Theme">
                            <span v-if="isDark">☀️</span>
                            <span v-else>🌙</span>
                        </button>
                    </div>

                    <a href="/#projects"
                        class="w-full text-left py-1 px-2 hover:bg-neutral-50 dark:hover:bg-neutral-900 rounded-lg text-xs font-sans font-semibold text-neutral-500 dark:text-neutral-400 transition-colors">
                        Kembali
                    </a>

                    <div v-if="isOwner" class="pt-2 border-t border-neutral-200/50 dark:border-neutral-800/40 flex flex-col gap-2">
                        <button @click="openEditModal(); showMobileMenu = false"
                            class="w-full text-left text-xs font-sans text-brand-steel hover:underline py-1 px-2 hover:bg-neutral-50 dark:hover:bg-neutral-900 rounded-lg cursor-pointer focus:outline-none font-semibold">
                            Edit
                        </button>
                        <button @click="deleteProject(); showMobileMenu = false"
                            class="w-full text-left text-xs font-sans text-red-500 hover:underline py-1 px-2 hover:bg-neutral-50 dark:hover:bg-neutral-900 rounded-lg cursor-pointer focus:outline-none font-semibold">
                            Hapus
                        </button>
                    </div>
                </div>
            </transition>
        </div>

        <div class="max-w-5xl mx-auto px-6 pt-20 sm:pt-40 pb-16 w-full flex-grow relative z-10">
            <div v-if="loading" class="flex flex-col justify-center items-center py-32 space-y-4">
                <div class="w-10 h-10 border-4 border-brand-moss border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-mono text-neutral-400">Memuat project...</span>
            </div>

            <div v-else-if="project" class="space-y-12">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                    <div class="lg:col-span-2 space-y-6">
                        <article class="bento-card rounded-3xl p-6 sm:p-8 space-y-6">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 text-xs font-sans text-neutral-400 font-semibold">
                                    <span class="uppercase tracking-wider">DATA PROJECT</span>
                                    <span
                                        class="text-xs text-brand-moss bg-brand-moss/10 px-2.5 h-5 rounded-full border border-brand-moss/20 font-semibold whitespace-nowrap inline-flex items-center justify-center leading-none">
                                        {{ project.focus_areas.split(',')[0] }}
                                    </span>
                                </div>
                                <h1
                                    class="text-2xl sm:text-4xl font-serif font-extrabold tracking-tight leading-tight text-neutral-900 dark:text-neutral-50">
                                    {{ project.title }}
                                </h1>
                            </div>

                            <div class="space-y-4">
                                <div
                                    class="relative w-full aspect-video rounded-2xl overflow-hidden border border-neutral-200 dark:border-neutral-800/60 bg-neutral-100 dark:bg-neutral-950 shadow-md">
                                    <img :src="selectedImage" :alt="project.title + ' Showcase'"
                                        class="w-full h-full object-cover transition-all duration-500">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none">
                                    </div>
                                </div>

                                <div v-if="project.images_gallery && project.images_gallery.split(',').filter(Boolean).length > 0"
                                    class="space-y-2">
                                    <span
                                        class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Galeri
                                        foto (Klik untuk melihat)</span>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                                        <div v-for="(img, idx) in project.images_gallery.split(',').filter(Boolean)"
                                            :key="img" class="relative group">
                                            <button @click="selectedImage = img"
                                                class="w-full aspect-video rounded-xl overflow-hidden border-2 bg-neutral-100 dark:bg-neutral-950 focus:outline-none transition-all cursor-pointer relative"
                                                :class="selectedImage === img ? 'border-brand-moss ring-2 ring-brand-moss/20 scale-[1.02]' : 'border-neutral-200 dark:border-neutral-800 hover:border-neutral-450'">
                                                <img :src="img" :alt="`Foto project ${idx + 1}`"
                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                            </button>

                                            <button v-if="isOwner" type="button" @click="deleteGalleryImage(img)"
                                                class="absolute -top-1 -right-1 bg-black/85 text-white hover:bg-red-500 rounded-full w-4.5 h-4.5 flex items-center justify-center text-[9px] font-bold cursor-pointer focus:outline-none transition-colors shadow-xs"
                                                title="Hapus foto">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p
                                class="text-base leading-relaxed text-neutral-700 dark:text-neutral-300 font-sans whitespace-pre-line">
                                {{ project.description }}
                            </p>


                        </article>
                    </div>

                    <div class="space-y-6">
                        <div class="bento-card rounded-3xl p-6 space-y-4">
                            <span class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Detail
                                Project</span>
                            <div class="space-y-3 divide-y divide-neutral-200 dark:divide-neutral-850">
                                <div class="py-2 flex justify-between text-xs font-mono">
                                    <span class="text-neutral-400">TANGGAL PROSES</span>
                                    <span class="text-neutral-600 dark:text-neutral-350 font-semibold uppercase">{{
                                        formatDate(project.created_at) }}</span>
                                </div>
                                <div class="py-2 flex justify-between text-xs font-mono">
                                    <span class="text-neutral-400">KOMENTAR</span>
                                    <span>{{ project.comments ? project.comments.length : 0 }}</span>
                                </div>
                                <div class="py-2 flex justify-between text-xs font-mono">
                                    <span class="text-neutral-400">Fokus Bidang</span>
                                    <span class="text-neutral-600 dark:text-neutral-300 text-right">{{
                                        project.focus_areas }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bento-card rounded-3xl p-6 space-y-4">
                            <h3 class="font-serif font-bold text-lg">Tinggalkan Komentar</h3>

                            <form @submit.prevent="submitComment" class="space-y-4">
                                <div class="space-y-1">
                                    <label for="author_name"
                                        class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Nama</label>
                                    <input id="author_name" v-model="authorName" type="text" placeholder="username..."
                                        required
                                        class="w-full text-sm font-sans p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                                </div>

                                <div class="space-y-1">
                                    <label for="content"
                                        class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Pesan</label>
                                    <textarea id="content" v-model="commentContent" rows="4"
                                        placeholder="Tuliskan pesan..." required
                                        class="w-full text-sm font-sans p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100"></textarea>
                                </div>

                                <button type="submit" :disabled="submittingComment"
                                    class="w-full text-sm font-mono py-2.5 px-4 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-brand-moss dark:hover:bg-brand-moss hover:text-white dark:hover:text-white rounded-full transition-colors disabled:opacity-50 cursor-pointer">
                                    {{ submittingComment ? 'Posting...' : 'Kirim Komentar' }}
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <section class="space-y-6">
                    <div class="border-b border-neutral-200 dark:border-neutral-800/40 pb-4">
                        <h2
                            class="text-sm uppercase tracking-widest text-neutral-450 dark:text-neutral-400 font-mono font-semibold">
                            Komentar</h2>
                    </div>

                    <div class="space-y-4 max-w-3xl">
                        <div v-if="!project.comments || project.comments.length === 0"
                            class="text-sm text-neutral-400 font-mono py-8 text-center bg-white/20 dark:bg-neutral-900/10 border border-neutral-200/50 dark:border-neutral-800/40 rounded-3xl">
                            Belum ada komentar. Jadilah orang pertama yang meninggalkan pesan dukungan.
                        </div>
                        <div v-else v-for="comment in project.comments" :key="comment.id"
                            class="p-5 border border-neutral-200 dark:border-neutral-800 rounded-2xl bg-white/80 dark:bg-[#0c0c0b]/80 backdrop-blur-md shadow-xs text-sm font-sans">
                            <div class="flex justify-between items-baseline mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{
                                        comment.author_name }}</span>
                                    <button v-if="isOwner" @click="deleteComment(comment.id)"
                                        class="text-[10px] font-mono text-red-500 hover:text-red-650 cursor-pointer focus:outline-none">
                                        Delete
                                    </button>
                                </div>
                                <span class="text-xs font-mono text-neutral-400">{{ formatDate(comment.created_at)
                                    }}</span>
                            </div>
                            <p class="text-neutral-600 dark:text-neutral-450 leading-relaxed whitespace-pre-line">{{
                                comment.content }}</p>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <footer class="border-t border-neutral-200/50 dark:border-neutral-800 py-8 mt-24 relative z-10">
            <div
                class="max-w-5xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center text-sm text-neutral-400 font-mono gap-4">
                <span>&copy; 2026 Zack Brawn.</span>
            </div>
        </footer>

        <div v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 dark:bg-black/75 backdrop-blur-xs p-4"
            @click.self="closeEditModal">
            <div
                class="bento-card w-full max-w-2xl rounded-3xl p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto animate-scale-up relative text-left">
                <div
                    class="flex justify-between items-center border-b border-neutral-200 dark:border-neutral-800/65 pb-4">
                    <div>
                        <span class="text-xs font-mono text-brand-moss uppercase tracking-widest">Edit</span>
                        <h2
                            class="text-xl sm:text-2xl font-serif font-extrabold text-neutral-900 dark:text-neutral-50 mt-1">
                            Edit Details</h2>
                    </div>
                    <button @click="closeEditModal"
                        class="p-1 hover:text-neutral-600 dark:hover:text-neutral-200 text-neutral-400 font-mono text-sm cursor-pointer focus:outline-none">
                        Close
                    </button>
                </div>

                <form @submit.prevent="handleUpdateProject" class="space-y-4 text-sm font-sans">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="edit_title"
                                class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Judul
                                *</label>
                            <input id="edit_title" v-model="editTitle" type="text" required
                                class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                        </div>

                        <div class="space-y-1">
                            <label for="edit_tech"
                                class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Fokus
                                Aksi *</label>
                            <input id="edit_tech" v-model="editFocusAreas" type="text" required
                                class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="edit_summary"
                            class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Ringkasan
                            Singkat *</label>
                        <input id="edit_summary" v-model="editSummary" type="text" required
                            class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                    </div>

                    <div class="space-y-1">
                        <label for="edit_description"
                            class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Deskripsi</label>
                        <textarea id="edit_description" v-model="editDescription" rows="4" required
                            class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100"></textarea>
                    </div>



                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Gambar
                                Sampul</label>
                            <input type="file" accept="image/*" @change="handleMainImageChange"
                                class="w-full text-xs text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-mono file:bg-neutral-100 dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-200 dark:hover:file:bg-neutral-800 cursor-pointer" />
                            <div v-if="coverPreviewUrl"
                                class="mt-2 relative aspect-video rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-800 bg-neutral-100 dark:bg-neutral-950">
                                <img :src="coverPreviewUrl" alt="Cover Image Preview"
                                    class="w-full h-full object-cover">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Galeri
                                Foto</label>
                            <input type="file" accept="image/*" multiple @change="handleGalleryImagesChange"
                                class="w-full text-xs text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-mono file:bg-neutral-100 dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-200 dark:hover:file:bg-neutral-800 cursor-pointer" />
                            <div v-if="galleryPreviewUrls.length > 0" class="mt-2 grid grid-cols-3 gap-2">
                                <div v-for="(url, index) in galleryPreviewUrls" :key="url"
                                    class="aspect-video rounded-lg overflow-hidden border border-neutral-200 dark:border-neutral-800 bg-neutral-100 dark:bg-neutral-950 relative group">
                                    <img :src="url" alt="Detail Gallery Preview" class="w-full h-full object-cover">
                                    <button type="button" @click="removeSelectedGalleryImage(index)"
                                        class="absolute top-1.5 right-1.5 bg-black/75 text-white hover:bg-red-500 rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold cursor-pointer focus:outline-none transition-colors shadow-xs"
                                        title="Remove image">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="submittingProject"
                        class="w-full text-sm font-mono py-3 px-4 mt-4 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-brand-moss dark:hover:bg-brand-moss hover:text-white dark:hover:text-white rounded-full transition-colors disabled:opacity-50 cursor-pointer">
                        {{ submittingProject ? 'Updating...' : 'Update' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="fixed top-24 right-6 z-50 space-y-3 w-80 max-w-sm pointer-events-none">
            <transition-group name="toast">
                <div v-for="toast in normalToasts" :key="toast.id"
                    class="p-4 rounded-3xl shadow-lg border text-sm font-sans pointer-events-auto transition-all duration-300 bg-white/95 dark:bg-[#0c0c0b]/95 backdrop-blur-md border-neutral-200 dark:border-neutral-800">
                    <div class="flex items-start gap-2.5 px-1">
                        <span v-if="toast.type === 'success'" class="text-brand-moss font-bold text-base"></span>
                        <span v-else-if="toast.type === 'error'" class="text-red-500 font-bold text-base"></span>
                        <span v-else class="text-brand-steel font-bold text-base"></span>
                        <span class="text-neutral-850 dark:text-neutral-250 font-medium leading-tight">{{ toast.message
                            }}</span>
                    </div>
                </div>
            </transition-group>
        </div>

        <div v-if="hasConfirmToast"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/25 dark:bg-black/55 backdrop-blur-xs p-4">
            <div v-for="toast in confirmToasts" :key="toast.id"
                class="bento-card w-full max-w-sm rounded-3xl p-6 space-y-4 shadow-xl border border-neutral-200 dark:border-neutral-800 bg-white/95 dark:bg-[#0c0c0b]/95 animate-scale-up text-left">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="text-amber-500 text-xl">⚠️</span>
                        <h4 class="font-serif font-bold text-lg text-neutral-900 dark:text-neutral-50">Konfirmasi</h4>
                    </div>
                    <p class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400 font-sans">
                        {{ toast.message }}
                    </p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="dismissToast(toast.id)"
                        class="text-xs font-mono px-4 py-2 border border-neutral-200 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 cursor-pointer focus:outline-none transition-colors text-neutral-600 dark:text-neutral-400 font-semibold">
                        Cancel
                    </button>
                    <button @click="toast.onConfirm(); dismissToast(toast.id)"
                        class="text-xs font-mono px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-full cursor-pointer focus:outline-none transition-colors font-semibold shadow-sm">
                        Confirm
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<style>
html {
    scroll-behavior: smooth;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.toast-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

/* Modified Premium Scrollbar */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: #eff8ff;
}

.dark ::-webkit-scrollbar-track {
    background: #07090c;
}

::-webkit-scrollbar-thumb {
    background: #d6d6d4;
    border-radius: 9999px;
    border: 3px solid #eff8ff;
}

.dark ::-webkit-scrollbar-thumb {
    background: #2a2a28;
    border: 3px solid #07090c;
}

::-webkit-scrollbar-thumb:hover {
    background: #3182ce;
}
</style>
