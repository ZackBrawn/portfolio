<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';

// state
const projects = ref([]);
const selectedProject = ref(null);
const loading = ref(false);
const submittingComment = ref(false);
const isDark = ref(false);
const activeSection = ref('about');
const activeTab = ref('logs');
let isScrollingProgrammatically = false;

// pagination
const currentPage = ref(1);
const itemsPerPage = 10;

const totalPages = computed(() => {
    return Math.ceil(projects.value.length / itemsPerPage);
});

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return projects.value.slice(start, end);
});

const handlePageChange = (newPage) => {
    currentPage.value = newPage;
    setTimeout(() => {
        scrollTo('projects');
    }, 100);
};

// form
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

window.showToast = showToast;

// tema dark & light
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

// fetch projek
const fetchProjects = async () => {
    try {
        const res = await fetch('/api/projects');
        projects.value = await res.json();
    } catch (err) {
        showToast('Failed to load projects', 'error');
    }
};

const selectedDrawerImage = ref('');

// fetch projek & coment
const selectProject = async (projectItem) => {
    loading.value = true;
    try {
        const res = await fetch(`/api/projects/${projectItem.id}`);
        selectedProject.value = await res.json();

        if (selectedProject.value.images_gallery) {
            const gallery = selectedProject.value.images_gallery.split(',');
            if (gallery.length > 0) {
                selectedDrawerImage.value = gallery[0];
            } else {
                selectedDrawerImage.value = selectedProject.value.image_url;
            }
        } else {
            selectedDrawerImage.value = selectedProject.value.image_url;
        }

        showToast(`Loaded ${projectItem.title}`, 'success');
    } catch (err) {
        showToast('Failed to load project details', 'error');
    } finally {
        loading.value = false;
    }
};

const closeProject = () => {
    selectedProject.value = null;
    selectedDrawerImage.value = '';
    authorName.value = '';
    commentContent.value = '';
};

// post comment
const submitComment = async () => {
    if (!authorName.value.trim() || !commentContent.value.trim()) {
        showToast('Nama dan komentar wajib di isi', 'error');
        return;
    }
    submittingComment.value = true;
    try {
        const res = await fetch(`/api/projects/${selectedProject.value.id}/comments`, {
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
        selectedProject.value.comments.unshift(newComment);
        commentContent.value = '';
        showToast('Berhasil membuat komentar', 'success');
    } catch (err) {
        showToast('Gagal membuat komentar', 'error');
    } finally {
        submittingComment.value = false;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) {
        activeSection.value = id;
        isScrollingProgrammatically = true;
        const headerEl = document.querySelector('.fixed.top-6 > div');
        const headerHeight = headerEl ? headerEl.offsetHeight : 56;
        const yOffset = -(headerHeight + 24 + 32);
        const elementTop = el.getBoundingClientRect().top + window.scrollY;
        window.scrollTo({ top: elementTop + yOffset, behavior: 'smooth' });
        setTimeout(() => {
            isScrollingProgrammatically = false;
        }, 800);
    }
};

// owner auth
const isOwner = ref(false);
const showCreateModal = ref(false);
const submittingProject = ref(false);

const newTitle = ref('');
const newSummary = ref('');
const newDescription = ref('');
const newFocusAreas = ref('');
const newGithub = ref('');
const newLive = ref('');
const newImageFile = ref(null);
const newGalleryFiles = ref([]);
const coverPreviewUrl = ref('');
const galleryPreviewUrls = ref([]);

const isEditing = ref(false);
const editingProjectId = ref(null);

const openEditModal = (projectItem) => {
    isEditing.value = true;
    editingProjectId.value = projectItem.id;
    newTitle.value = projectItem.title;
    newSummary.value = projectItem.summary;
    newDescription.value = projectItem.description;
    newFocusAreas.value = projectItem.focus_areas;
    newGithub.value = projectItem.github_url || '';
    newLive.value = projectItem.live_url || '';

    coverPreviewUrl.value = projectItem.image_url;
    galleryPreviewUrls.value = projectItem.images_gallery ? projectItem.images_gallery.split(',').filter(Boolean) : [];

    showCreateModal.value = true;
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

const closeCreateModal = () => {
    showCreateModal.value = false;
    isEditing.value = false;
    editingProjectId.value = null;
    newTitle.value = '';
    newSummary.value = '';
    newDescription.value = '';
    newFocusAreas.value = '';
    newGithub.value = '';
    newLive.value = '';
    newImageFile.value = null;
    newGalleryFiles.value = [];
    clearPreviews();
};

const handleMainImageChange = (e) => {
    const file = e.target.files[0];
    newImageFile.value = file || null;
    if (coverPreviewUrl.value) {
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

    newGalleryFiles.value = [...newGalleryFiles.value, ...files];

    galleryPreviewUrls.value.forEach(url => {
        if (url.startsWith('blob:')) URL.revokeObjectURL(url);
    });

    const serverUrls = galleryPreviewUrls.value.filter(url => !url.startsWith('blob:'));
    const blobUrls = newGalleryFiles.value.map(file => URL.createObjectURL(file));
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
            newGalleryFiles.value.splice(fileIndex, 1);
        }
        galleryPreviewUrls.value.splice(index, 1);
    } else {
        showToast('Apakah anda yakin ingin menghapus gambar ini dari galeri?', 'confirm', async () => {
            try {
                const res = await fetch(`/api/projects/${editingProjectId.value}/gallery-image`, {
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
                    if (selectedProject.value && selectedProject.value.id === editingProjectId.value) {
                        selectedProject.value.images_gallery = data.images_gallery;
                        if (selectedDrawerImage.value === url) {
                            if (data.images_gallery) {
                                selectedDrawerImage.value = data.images_gallery.split(',')[0];
                            } else {
                                selectedDrawerImage.value = selectedProject.value.image_url;
                            }
                        }
                    }
                    const pIndex = projects.value.findIndex(p => p.id === editingProjectId.value);
                    if (pIndex !== -1) {
                        projects.value[pIndex].images_gallery = data.images_gallery;
                    }
                    galleryPreviewUrls.value = galleryPreviewUrls.value.filter(u => u !== url);
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

const checkAuthStatus = async () => {
    try {
        const res = await fetch('/api/auth-check');
        const data = await res.json();
        isOwner.value = data.logged_in;
    } catch (err) {
        console.error(err);
    }
};

const handleLogout = async () => {
    try {
        const res = await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        if (res.ok) {
            isOwner.value = false;
            showToast('Logout berhasil', 'info');
        }
    } catch (err) {
        showToast('Logout gagal', 'error');
    }
};

const deleteComment = (commentId) => {
    showToast('Apakah anda yakin ingin menghapus komentar ini?', 'confirm', () => proceedDeleteComment(commentId));
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
            if (selectedProject.value && selectedProject.value.comments) {
                selectedProject.value.comments = selectedProject.value.comments.filter(c => c.id !== commentId);
            }
            showToast('Komentar berhasil dihapus', 'success');
        } else {
            showToast('Gagal menghapus komentar', 'error');
        }
    } catch (err) {
        showToast('Koneksi gagal', 'error');
    }
};

const deleteProject = (projectId) => {
    showToast('Apakah anda yakin ingin menghapus program ini? Ini akan menghapus semua log dan komentar yang terkait dengannya.', 'confirm', () => proceedDeleteProject(projectId));
};

const proceedDeleteProject = async (projectId) => {
    try {
        const res = await fetch(`/api/projects/${projectId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        if (res.ok) {
            projects.value = projects.value.filter(p => p.id !== projectId);
            if (currentPage.value > totalPages.value && totalPages.value > 0) {
                currentPage.value = totalPages.value;
            }
            closeProject();
            showToast('Program berhasil dihapus', 'success');
        } else {
            showToast('Gagal menghapus program', 'error');
        }
    } catch (err) {
        showToast('Gagal menghapus program', 'error');
    }
};

const deleteGalleryImage = (projectId, imagePath) => {
    showToast('Apakah anda yakin ingin menghapus gambar ini dari galeri program ini?', 'confirm', () => proceedDeleteGalleryImage(projectId, imagePath));
};

const proceedDeleteGalleryImage = async (projectId, imagePath) => {
    try {
        const res = await fetch(`/api/projects/${projectId}/gallery-image`, {
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
            if (selectedProject.value) {
                selectedProject.value.images_gallery = data.images_gallery;
                if (selectedDrawerImage.value === imagePath) {
                    if (data.images_gallery) {
                        selectedDrawerImage.value = data.images_gallery.split(',')[0];
                    } else {
                        selectedDrawerImage.value = selectedProject.value.image_url;
                    }
                }
            }
            showToast('Gambar berhasil dihapus dari galeri', 'success');
        } else {
            showToast(data.error || 'Gagal menghapus gambar', 'error');
        }
    } catch (err) {
        showToast('Gagal menghapus gambar', 'error');
    }
};

const handleCreateProject = async () => {
    if (!newTitle.value || !newSummary.value || !newDescription.value || !newFocusAreas.value || (!isEditing.value && !newImageFile.value)) {
        showToast('Harap isi semua kolom yang diperlukan dan pilih gambar sampul', 'error');
        return;
    }

    submittingProject.value = true;
    const formData = new FormData();
    formData.append('title', newTitle.value);
    formData.append('summary', newSummary.value);
    formData.append('description', newDescription.value);
    formData.append('focus_areas', newFocusAreas.value);
    if (newGithub.value) formData.append('github_url', newGithub.value);
    if (newLive.value) formData.append('live_url', newLive.value);

    if (newImageFile.value) {
        formData.append('image', newImageFile.value);
        formData.append('cover', newImageFile.value);
    }

    newGalleryFiles.value.forEach((file) => {
        formData.append('gallery[]', file);
    });

    try {
        const url = isEditing.value ? `/api/projects/${editingProjectId.value}/update` : '/api/projects';
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: formData
        });

        if (res.ok) {
            const updatedProj = await res.json();
            showToast(isEditing.value ? 'Berhasil Update!' : 'Berhasil Buat!', 'success');

            if (isEditing.value && selectedProject.value && selectedProject.value.id === editingProjectId.value) {
                selectedProject.value = updatedProj;
                selectedDrawerImage.value = updatedProj.image_url;
            }

            closeCreateModal();
            await fetchProjects();
        } else {
            const data = await res.json();
            showToast(data.message || 'Gagal Submit!', 'error');
        }
    } catch (err) {
        showToast('Gagal Submit!', 'error');
    } finally {
        submittingProject.value = false;
    }
};

const sections = ['about', 'experience', 'projects', 'contact'];

onMounted(() => {
    checkAuthStatus();
    fetchProjects();
    const storedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }

    const observer = new IntersectionObserver((entries) => {
        if (isScrollingProgrammatically) return;
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                activeSection.value = entry.target.id;
            }
        });
    }, { threshold: 0, rootMargin: '-20% 0px -50% 0px' });

    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });
});
</script>

<template>
    <div
        class="min-h-screen bg-[#fafaf9] dark:bg-[#070707] text-neutral-900 dark:text-neutral-100 font-sans transition-colors duration-500 flex flex-col justify-between selection:bg-brand-moss selection:text-white scroll-smooth relative overflow-hidden bg-grid-dots">

        <div
            class="absolute top-[-10%] left-[-10%] w-[55vw] h-[55vw] rounded-full bg-brand-moss/12 dark:bg-brand-moss/6 blur-[125px] pointer-events-none z-0 animate-pulse-glow-1">
        </div>
        <div
            class="absolute top-[35%] right-[-10%] w-[60vw] h-[60vw] rounded-full bg-brand-steel/12 dark:bg-brand-steel/6 blur-[145px] pointer-events-none z-0 animate-pulse-glow-2">
        </div>
        <div
            class="absolute bottom-[-10%] left-[15%] w-[50vw] h-[50vw] rounded-full bg-brand-moss/10 dark:bg-brand-moss/4 blur-[135px] pointer-events-none z-0 animate-pulse-glow-1">
        </div>

        <div class="fixed top-6 left-0 right-0 z-40 px-4 pointer-events-none flex justify-center">
            <div
                class="pointer-events-auto flex items-center justify-between gap-1.5 xs:gap-2 sm:gap-6 px-3 sm:px-6 h-12 sm:h-14 bg-white/80 dark:bg-[#0c0c0b]/85 border border-neutral-200/50 dark:border-neutral-800 rounded-full backdrop-blur-lg shadow-[0_10px_30px_rgba(0,0,0,0.08)] dark:shadow-[0_12px_35px_rgba(0,0,0,0.45)] max-w-xl w-full transition-all duration-300">
                <span @click="scrollTo('about')"
                    class="font-serif text-xs sm:text-base font-extrabold cursor-pointer hover:text-brand-moss transition-colors">ZB</span>

                <nav
                    class="flex items-center gap-1.5 xs:gap-2 sm:gap-4 md:gap-6 text-[9px] sm:text-xs font-mono tracking-wider uppercase shrink-0">
                    <button v-for="section in sections" :key="section" @click="scrollTo(section)"
                        class="capitalize hover:text-brand-moss transition-all cursor-pointer focus:outline-none relative py-1"
                        :class="activeSection === section ? 'text-brand-moss font-bold' : 'text-neutral-400 dark:text-neutral-500'">
                        {{ section }}
                        <span v-if="activeSection === section"
                            class="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-moss rounded-full animate-pulse"></span>
                    </button>
                </nav>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <button @click="toggleTheme"
                        class="w-6.5 h-6.5 sm:w-8 sm:h-8 flex items-center justify-center border border-neutral-200/80 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-all text-xs sm:text-sm cursor-pointer focus:outline-none bg-white/50 dark:bg-neutral-900/50"
                        aria-label="Toggle Theme">
                        <span v-if="isDark">☀️</span>
                        <span v-else>🌙</span>
                    </button>

                    <button v-if="isOwner" @click="handleLogout"
                        class="text-[8.5px] sm:text-[10px] font-mono text-red-500 hover:text-red-600 border border-red-500/20 px-1.5 py-0.5 rounded-full cursor-pointer focus:outline-none"
                        title="Logout Owner">
                        Logout
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-6 pt-36 sm:pt-40 pb-16 w-full flex-grow relative z-10">
            <div class="space-y-36">

                <!-- 1 about-->
                <section id="about" class="scroll-mt-32">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                        <!-- 1 Bio -->
                        <div
                            class="bento-card bento-card-moss rounded-3xl p-8 flex flex-col justify-between min-h-[380px] lg:col-span-2 lg:row-span-2 order-2 md:order-none">
                            <div class="space-y-4">
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1 bg-brand-steel/10 text-brand-steel border border-brand-steel/20 rounded-full text-xs font-mono font-medium shadow-xs">
                                    <span>Campaign & Advocacy Coordinator</span>
                                </div>
                                <h1
                                    class="text-4xl md:text-5xl lg:text-6xl font-serif tracking-tight font-extrabold leading-tight text-neutral-900 dark:text-neutral-50">
                                    Memobilisasi masyarakat untuk kemajuan lingkungan dan sosial.
                                </h1>
                            </div>
                            <p
                                class="text-lg font-sans leading-relaxed text-neutral-600 dark:text-neutral-400 max-w-[55ch] mt-6">
                                Saya Zack Brawn. Saya mengkoordinasikan advokasi, mengorganisir aksi komunitas, dan
                                merancang strategi yang berfokus pada dampak. Mari kita bangun jaringan yang mendorong
                                dampak ekologis dan sosial yang terukur. </p>
                            <div class="pt-8 flex flex-wrap items-center gap-4">
                                <button @click="scrollTo('projects')"
                                    class="text-xs font-mono px-6 py-3 bg-neutral-900 hover:bg-brand-moss text-white dark:bg-neutral-100 dark:hover:bg-brand-moss dark:text-neutral-900 dark:hover:text-white rounded-full transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 cursor-pointer">
                                    Jelajahi Program
                                </button>
                                <button @click="scrollTo('contact')"
                                    class="text-xs font-mono text-neutral-500 hover:text-neutral-800 dark:hover:text-neutral-200 px-4 py-3 rounded-full hover:bg-neutral-100/50 dark:hover:bg-neutral-900/30 transition-all cursor-pointer">
                                    Bergabung
                                </button>
                            </div>
                        </div>

                        <!-- 2 profile -->
                        <div
                            class="bento-card rounded-3xl p-6 flex flex-col justify-between min-h-[380px] lg:row-span-2 order-1 md:order-none">
                            <div class="flex flex-col items-center gap-4 text-center">
                                <div class="relative group">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-brand-moss to-brand-steel rounded-3xl opacity-20 blur-md group-hover:opacity-35 transition-opacity duration-300">
                                    </div>
                                    <img :src="'/avatar.png'" alt="Zack Brawn Portrait"
                                        class="w-full max-w-[280px] aspect-square mx-auto rounded-3xl object-cover border-2 border-neutral-200 dark:border-neutral-800 bg-neutral-100 dark:bg-neutral-900 relative z-10 transition-transform duration-500 group-hover:scale-[1.03]" />
                                </div>
                                <div>
                                    <span
                                        class="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">Zack
                                        Brawn</span>
                                    <span class="text-xs font-mono text-neutral-450">Campaign Coordinator</span>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-neutral-200/50 dark:border-neutral-800/40 space-y-4">
                                <div class="space-y-1">
                                    <span
                                        class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest text-left">Lokasi</span>
                                    <span
                                        class="block font-serif font-bold text-lg text-neutral-800 dark:text-neutral-200 text-left">Semarang,
                                        Jawa Tengah</span>
                                </div>
                                <div class="flex items-center justify-between text-xs font-mono">
                                    <div class="flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-moss opacity-75"></span>
                                            <span
                                                class="relative inline-flex rounded-full h-2 w-2 bg-brand-moss"></span>
                                        </span>
                                        <span class="text-brand-moss font-semibold">Tersedia untuk kontrak</span>
                                    </div>
                                    <span class="text-neutral-400">WIB (UTC+7)</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3 filosofi -->
                        <div
                            class="bento-card rounded-3xl p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 lg:col-span-3 min-h-[160px] order-3 md:order-none">
                            <div class="space-y-3 max-w-2xl text-left">
                                <span
                                    class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Filosofi</span>
                                <h4 class="font-serif font-bold text-2xl text-neutral-900 dark:text-neutral-50">
                                    Transparansi, Aksi, Dampak</h4>
                                <p class="text-sm text-neutral-500 leading-relaxed font-sans">
                                    Kami mengoordinasikan tindakan yang secara langsung mengubah kota dan garis pantai.
                                    Fokus pada pemberdayaan masyarakat akar rumput, metrik publik yang jelas, dan
                                    perubahan ekologis yang berkelanjutan.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2 shrink-0">
                                <span
                                    class="text-xs font-mono bg-neutral-100 dark:bg-neutral-900 border border-neutral-200/50 dark:border-neutral-800/40 px-3 py-1.5 rounded-full text-neutral-600 dark:text-neutral-400">Jangkauan
                                    Akar Rumput</span>
                                <span
                                    class="text-xs font-mono bg-neutral-100 dark:bg-neutral-900 border border-neutral-200/50 dark:border-neutral-800/40 px-3 py-1.5 rounded-full text-neutral-600 dark:text-neutral-400">Keberlanjutan
                                    Ekologis</span>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- 2 pengalaman -->
                <section id="experience" class="scroll-mt-32">
                    <div class="space-y-8">
                        <div class="border-b border-neutral-200 dark:border-neutral-800/40 pb-4">
                            <h2 class="text-sm uppercase tracking-widest text-neutral-400 font-mono font-semibold">Latar
                                Belakang</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- pengalaman -->
                            <div class="bento-card rounded-3xl p-8 space-y-6">
                                <h3
                                    class="font-serif text-xl font-bold border-b border-neutral-200/40 dark:border-neutral-800/20 pb-3 flex items-center gap-2">
                                    Pengalaman
                                </h3>

                                <div
                                    class="space-y-6 relative before:absolute before:left-2 before:top-2 before:bottom-2 before:w-[1px] before:bg-neutral-200 dark:before:bg-neutral-800 pl-6">
                                    <div class="relative group">
                                        <div
                                            class="absolute -left-[30px] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#0c0c0b] bg-brand-moss group-hover:scale-125 transition-transform shadow-xs">
                                        </div>
                                        <span class="text-xs font-mono text-neutral-400 uppercase tracking-widest">2024
                                            — Sekarang</span>
                                        <h4 class="font-serif font-bold text-base mt-1">Koordinator Advokasi Senior</h4>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400 font-mono">Pemuda Hijau
                                            Indonesia · Semarang, Jawa Tengah</p>
                                        <p
                                            class="text-base text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                                            Mengarahkan kampanye lingkungan, koalisi relawan lokal, dan mengelola
                                            program konservasi hutan regional. </p>
                                    </div>

                                    <div class="relative group">
                                        <div
                                            class="absolute -left-[30px] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#0c0c0b] bg-brand-steel group-hover:scale-125 transition-transform shadow-xs">
                                        </div>
                                        <span class="text-xs font-mono text-neutral-400 uppercase tracking-widest">2022
                                            — 2024</span>
                                        <h4 class="font-serif font-bold text-base mt-1">Campaign Organizer</h4>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400 font-mono">Yayasan
                                            Lumbung Pangan · Bandung, Jawa Barat</p>
                                        <p
                                            class="text-base text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                                            Mengoordinasikan pembersihan sampah regional, membangun jaringan mitra, dan
                                            menyelenggarakan penggalangan dana kesadaran publik.
                                        </p>
                                    </div>

                                    <div class="relative group">
                                        <div
                                            class="absolute -left-[30px] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#0c0c0b] bg-brand-steel group-hover:scale-125 transition-transform shadow-xs">
                                        </div>
                                        <span class="text-xs font-mono text-neutral-400 uppercase tracking-widest">2020
                                            — 2022</span>
                                        <h4 class="font-serif font-bold text-base mt-1">Community Engagement Specialist
                                        </h4>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400 font-mono">Asosiasi
                                            Akar Rumput · Jakarta</p>
                                        <p
                                            class="text-base text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                                            Membangun kemitraan komunitas, merancang program pemberdayaan, dan
                                            memfasilitasi dialog publik.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- pendidikan -->
                            <div class="bento-card rounded-3xl p-8 space-y-6">
                                <h3
                                    class="font-serif text-xl font-bold border-b border-neutral-200/40 dark:border-neutral-800/20 pb-3 flex items-center gap-2">
                                    Pendidikan
                                </h3>

                                <div
                                    class="space-y-8 relative before:absolute before:left-2 before:top-2 before:bottom-2 before:w-[1px] before:bg-neutral-200 dark:before:bg-neutral-800 pl-6">
                                    <div class="relative group">
                                        <div
                                            class="absolute -left-[30px] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#0c0c0b] bg-brand-moss group-hover:scale-125 transition-transform shadow-xs">
                                        </div>
                                        <span class="text-xs font-mono text-neutral-400 uppercase tracking-widest">2018
                                            — 2020</span>
                                        <h4 class="font-serif font-bold text-base mt-1">MSc in Public Policy & Social
                                            Impact</h4>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400 font-mono">Stanford
                                            University</p>
                                        <p
                                            class="text-base text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                                            Penelitian akademis yang berfokus pada kebijakan lingkungan, strategi
                                            mobilisasi masyarakat, dan keadilan sosial. </p>
                                    </div>

                                    <div class="relative group">
                                        <div
                                            class="absolute -left-[30px] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#0c0c0b] bg-brand-steel group-hover:scale-125 transition-transform shadow-xs">
                                        </div>
                                        <span class="text-xs font-mono text-neutral-400 uppercase tracking-widest">2015
                                            — 2018</span>
                                        <h4 class="font-serif font-bold text-base mt-1">BA in Sociology & Ecological
                                            Studies</h4>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400 font-mono">MIT</p>
                                        <p
                                            class="text-base text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                                            Studi inti dalam dinamika sosiologi perkotaan, sistem sumber daya
                                            berkelanjutan, dan metode pengorganisasian warga. </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3 project -->
                <section id="projects" class="scroll-mt-32 min-h-screen">
                    <div class="space-y-8">
                        <div
                            class="border-b border-neutral-200 dark:border-neutral-800/40 pb-4 flex justify-between items-center">
                            <h2 class="text-sm uppercase tracking-widest text-neutral-400 font-mono font-semibold">
                                Projek yang dikerjakan</h2>
                            <div class="flex items-center gap-4">
                                <button v-if="isOwner" @click="showCreateModal = true"
                                    class="text-xs font-mono text-brand-moss border border-brand-moss/35 hover:bg-brand-moss hover:text-white px-3 py-1 rounded-full cursor-pointer focus:outline-none transition-colors">
                                    + Tambahkan Projek Baru
                                </button>
                                <span class="text-sm font-mono text-neutral-400">Projek Selesai: {{ projects.length
                                    }}</span>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <article v-if="currentPage === 1 && paginatedProjects.length > 0"
                                class="bento-card bento-card-moss rounded-3xl p-6 lg:p-8 grid grid-cols-1 lg:grid-cols-12 gap-8 relative overflow-hidden">
                                <!-- info details -->
                                <div class="lg:col-span-6 flex flex-col justify-between space-y-6">
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3 text-xs font-mono text-neutral-400">
                                            <span>PROJEK PILIHAN</span>
                                            <span
                                                class="text-xs text-brand-moss bg-brand-moss/10 px-2.5 py-0.5 rounded-full border border-brand-moss/20 font-semibold">
                                                {{ paginatedProjects[0].focus_areas.split(',')[0] }}
                                            </span>
                                        </div>

                                        <h3 class="text-3xl font-serif font-extrabold tracking-tight">
                                            <button @click="selectProject(paginatedProjects[0])"
                                                class="text-left cursor-pointer focus:outline-none hover:text-brand-moss transition-colors duration-200">
                                                {{ paginatedProjects[0].title }}
                                            </button>
                                        </h3>

                                        <p
                                            class="text-base text-neutral-600 dark:text-neutral-400 leading-relaxed font-sans">
                                            {{ paginatedProjects[0].summary }}
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            <span v-for="tech in paginatedProjects[0].focus_areas.split(',')"
                                                :key="tech"
                                                class="text-xs font-mono px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-900 border border-neutral-200/50 dark:border-neutral-800/40 text-neutral-600 dark:text-neutral-400">
                                                {{ tech.trim() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="lg:col-span-6 flex items-center justify-center relative">
                                    <div
                                        class="relative w-full aspect-video rounded-2xl overflow-hidden border border-neutral-200 dark:border-neutral-800/60 bg-neutral-100 dark:bg-neutral-950 shadow-md transition-all duration-500 group-hover:scale-[1.02] group-hover:shadow-lg">
                                        <img v-if="paginatedProjects[0].image_url" :src="paginatedProjects[0].image_url"
                                            alt="Featured mockup" class="w-full h-full object-cover" />
                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none">
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="lg:col-span-12 pt-4 border-t border-neutral-200 dark:border-neutral-800 flex items-center gap-4">
                                    <button @click="selectProject(paginatedProjects[0])"
                                        class="text-sm font-mono px-4 py-2 bg-neutral-900 hover:bg-brand-moss text-white dark:bg-neutral-100 dark:hover:bg-brand-moss dark:text-neutral-900 dark:hover:text-white rounded-full transition-colors cursor-pointer focus:outline-none">
                                        Laporan ({{ paginatedProjects[0].comments_count || 0 }})
                                    </button>

                                    <div class="flex items-center gap-3">
                                        <a :href="'/projects/' + paginatedProjects[0].id"
                                            class="text-xs font-mono text-neutral-400 hover:text-brand-moss font-semibold">
                                            Full Page
                                        </a>
                                        <button v-if="isOwner" @click="deleteProject(paginatedProjects[0].id)"
                                            class="text-xs font-mono text-red-500 hover:text-red-655 font-semibold cursor-pointer focus:outline-none">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <div v-if="(currentPage === 1 && paginatedProjects.length > 1) || (currentPage > 1 && paginatedProjects.length > 0)"
                                class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <article
                                    v-for="project in (currentPage === 1 ? paginatedProjects.slice(1) : paginatedProjects)"
                                    :key="project.id"
                                    class="bento-card rounded-3xl p-5 flex flex-col justify-between min-h-[360px]">
                                    <div class="space-y-4">
                                        <div
                                            class="relative w-full aspect-video rounded-xl overflow-hidden border border-neutral-200/80 dark:border-neutral-800/40 bg-neutral-100 dark:bg-neutral-950 shadow-xs group-hover:scale-[1.01] transition-all">
                                            <img v-if="project.image_url" :src="project.image_url" alt="Project mockup"
                                                class="w-full h-full object-cover" />
                                        </div>

                                        <div
                                            class="flex justify-between items-center text-xs font-mono text-neutral-400">
                                            <span>ARCHIVE PROJEK</span>
                                            <span
                                                class="text-xs text-brand-steel bg-brand-steel/5 px-2 py-0.5 rounded border border-brand-steel/10">
                                                {{ project.focus_areas.split(',')[0] }}
                                            </span>
                                        </div>

                                        <h3
                                            class="text-xl font-serif font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                            <button @click="selectProject(project)"
                                                class="text-left cursor-pointer focus:outline-none hover:text-brand-moss transition-colors duration-200">
                                                {{ project.title }}
                                            </button>
                                        </h3>

                                        <p
                                            class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed font-sans">
                                            {{ project.summary }}
                                        </p>
                                    </div>

                                    <div
                                        class="pt-6 border-t border-neutral-200 dark:border-neutral-800 flex flex-col gap-2.5 mt-6 items-start">
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400 font-mono">
                                            {{ project.focus_areas.split(',').slice(0, 2).join(', ') }}
                                        </span>

                                        <div class="flex items-center gap-4">
                                            <a :href="'/projects/' + project.id"
                                                class="text-xs font-mono text-neutral-400 hover:text-brand-moss font-semibold">
                                                Full Page
                                            </a>
                                            <button v-if="isOwner" @click="deleteProject(project.id)"
                                                class="text-xs font-mono text-red-500 hover:text-red-655 font-semibold cursor-pointer focus:outline-none">
                                                Delete
                                            </button>
                                            <button @click="selectProject(project)"
                                                class="text-sm font-mono text-brand-moss group-hover:underline flex items-center gap-1 cursor-pointer focus:outline-none font-semibold">
                                                Updates ({{ project.comments_count || 0 }})
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <!-- pagination -->
                            <div v-if="totalPages > 1"
                                class="flex justify-center items-center gap-4 pt-8 font-mono text-sm">
                                <button :disabled="currentPage === 1" @click="handlePageChange(currentPage - 1)"
                                    class="px-4 py-2 border border-neutral-200 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 disabled:opacity-40 cursor-pointer focus:outline-none transition-colors">
                                    Previous
                                </button>
                                <span class="text-neutral-500">Page {{ currentPage }} of {{ totalPages }}</span>
                                <button :disabled="currentPage === totalPages"
                                    @click="handlePageChange(currentPage + 1)"
                                    class="px-4 py-2 border border-neutral-200 dark:border-neutral-800 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-900 disabled:opacity-40 cursor-pointer focus:outline-none transition-colors">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4 kontak -->
                <section id="contact" class="scroll-mt-32">
                    <div class="space-y-8">
                        <div class="border-b border-neutral-200 dark:border-neutral-800/40 pb-4">
                            <h2 class="text-sm uppercase tracking-widest text-neutral-400 font-mono font-semibold">
                                Kontak</h2>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-[1.5fr_1fr] gap-6">
                            <div class="bento-card rounded-3xl p-8 flex flex-col justify-between min-h-[220px]">
                                <div class="space-y-4">
                                    <h3 class="font-serif text-3xl font-extrabold leading-tight">Mari kita bangun
                                        sesuatu yang luar biasa.</h3>
                                    <p
                                        class="text-base leading-relaxed text-neutral-600 dark:text-neutral-400 font-serif max-w-[45ch]">
                                        Saya terbuka untuk ruang lingkup konsultasi, audit arsitektur backend, atau
                                        integrasi sistem desain. Kirimkan garis besar proyek Anda dan mari terhubung.
                                    </p>
                                </div>

                                <div
                                    class="space-y-2 pt-4 text-sm font-mono text-neutral-500 border-t border-neutral-200/50 dark:border-neutral-800/40 mt-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-16 uppercase text-xs">Email:</span>
                                        <a href="mailto:zack@example.com"
                                            class="text-brand-moss hover:underline font-semibold">zack@example.com</a>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <a href="https://instagram.com" target="_blank"
                                    class="bento-card rounded-2xl p-5 flex flex-col justify-between hover:border-brand-moss group text-left min-h-[100px]">
                                    <span
                                        class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Source</span>
                                    <span
                                        class="block font-serif font-bold text-base mt-3 group-hover:text-brand-moss transition-colors">Instagram</span>
                                </a>

                                <a href="mailto:zack@example.com"
                                    class="bento-card rounded-2xl p-5 flex flex-col justify-between hover:border-brand-moss group text-left min-h-[100px]">
                                    <span
                                        class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Email</span>
                                    <span
                                        class="block font-serif font-bold text-base mt-3 group-hover:text-brand-moss transition-colors">Email</span>
                                </a>

                                <a href="https://linkedin.com" target="_blank"
                                    class="bento-card rounded-2xl p-5 flex flex-col justify-between hover:border-brand-moss group text-left min-h-[100px]">
                                    <span
                                        class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Professional</span>
                                    <span
                                        class="block font-serif font-bold text-base mt-3 group-hover:text-brand-moss transition-colors">LinkedIn</span>
                                </a>

                                <div class="bento-card rounded-2xl p-5 flex flex-col justify-between min-h-[100px]">
                                    <span
                                        class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Response
                                        time</span>
                                    <span class="block font-serif font-bold text-base mt-3">&lt; 24 Hours</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <footer class="border-t border-neutral-200/50 dark:border-neutral-800 py-8 mt-24 relative z-10">
            <div
                class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center text-sm text-neutral-400 font-mono gap-4">
                <span>&copy; 2026 Zack Brawn.</span>
            </div>
        </footer>

        <div v-if="selectedProject"
            class="fixed inset-0 z-50 flex justify-start bg-black/40 dark:bg-black/70 backdrop-blur-xs transition-opacity duration-300"
            @click.self="closeProject">
            <div
                class="w-full max-w-xl bg-[#fafaf9] dark:bg-[#0c0c0b] h-full overflow-y-auto shadow-2xl flex flex-col border-r border-neutral-200 dark:border-neutral-800 animate-slide-in-left relative">

                <header
                    class="p-6 border-b border-neutral-200 dark:border-neutral-800 flex justify-between items-center sticky top-0 bg-[#fafaf9]/95 dark:bg-[#0c0c0b]/95 backdrop-blur-md z-10">
                    <div>
                        <span class="text-xs font-mono text-brand-steel uppercase tracking-widest">Entry Details</span>
                        <h2 class="text-2xl font-serif font-bold mt-1 text-neutral-900 dark:text-neutral-50">{{
                            selectedProject.title }}</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <a :href="'/projects/' + selectedProject.id"
                            class="text-xs font-mono text-brand-moss hover:underline font-semibold">
                            Full Page
                        </a>
                        <button v-if="isOwner" @click="openEditModal(selectedProject)"
                            class="text-xs font-mono text-brand-steel hover:underline font-semibold cursor-pointer focus:outline-none">
                            Edit
                        </button>
                        <button v-if="isOwner" @click="deleteProject(selectedProject.id)"
                            class="text-xs font-mono text-red-500 hover:underline font-semibold cursor-pointer focus:outline-none">
                            Delete
                        </button>

                        <span class="text-neutral-300 dark:text-neutral-800 font-mono text-xs">|</span>

                        <button @click="closeProject"
                            class="p-2 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 cursor-pointer focus:outline-none transition-colors font-mono text-sm font-semibold"
                            aria-label="Close panel">
                            Close
                        </button>
                    </div>
                </header>

                <div class="p-6 space-y-8 flex-grow">
                    <section
                        class="space-y-4 bg-white dark:bg-[#0c0c0b] border border-neutral-200/50 dark:border-neutral-800 p-5 rounded-2xl">
                        <div class="space-y-4 mb-4">
                            <div
                                class="relative w-full aspect-video rounded-xl overflow-hidden border border-neutral-200/80 dark:border-neutral-800/40 bg-neutral-100 dark:bg-neutral-950">
                                <img v-if="selectedDrawerImage" :src="selectedDrawerImage" alt="Drawer Project mockup"
                                    class="w-full h-full object-cover transition-all duration-300" />
                            </div>

                            <div v-if="selectedProject.images_gallery && selectedProject.images_gallery.split(',').filter(Boolean).length > 0"
                                class="space-y-2">
                                <span
                                    class="block text-[9px] font-mono text-neutral-400 uppercase tracking-widest">Detail
                                    Screenshots</span>
                                <div class="grid grid-cols-4 gap-2">
                                    <div v-for="(img, idx) in selectedProject.images_gallery.split(',').filter(Boolean)"
                                        :key="img" class="relative group">
                                        <button @click="selectedDrawerImage = img"
                                            class="w-full aspect-video rounded-lg overflow-hidden border-2 bg-neutral-100 dark:bg-neutral-950 focus:outline-none transition-all cursor-pointer relative"
                                            :class="selectedDrawerImage === img ? 'border-brand-moss ring-2 ring-brand-moss/10' : 'border-neutral-200 dark:border-neutral-800 hover:border-neutral-450'">
                                            <img :src="img" :alt="`Drawer screenshot detail ${idx + 1}`"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </button>

                                        <button v-if="isOwner" type="button"
                                            @click="deleteGalleryImage(selectedProject.id, img)"
                                            class="absolute -top-1 -right-1 bg-black/85 text-white hover:bg-red-500 rounded-full w-4 h-4 flex items-center justify-center text-[9px] font-bold cursor-pointer focus:outline-none transition-colors shadow-xs"
                                            title="Delete image from gallery">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span v-for="tech in selectedProject.focus_areas.split(',')" :key="tech"
                                class="text-xs font-mono px-2.5 py-0.5 border border-neutral-200 dark:border-neutral-800/60 rounded-full bg-neutral-50 dark:bg-neutral-900">
                                {{ tech.trim() }}
                            </span>
                        </div>

                        <p
                            class="text-base leading-relaxed text-neutral-700 dark:text-neutral-300 font-sans whitespace-pre-line">
                            {{ selectedProject.description }}
                        </p>

                    </section>

                    <section class="space-y-6">
                        <h3 class="text-sm font-mono uppercase text-neutral-500 font-semibold tracking-wider">Komentar
                        </h3>

                        <form @submit.prevent="submitComment"
                            class="space-y-4 bg-white dark:bg-[#0c0c0b] border border-neutral-200/60 dark:border-neutral-800 p-5 rounded-2xl">
                            <div class="space-y-1">
                                <label for="author_name"
                                    class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Name</label>
                                <input id="author_name" v-model="authorName" type="text" placeholder="username..."
                                    required
                                    class="w-full text-sm font-sans p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors" />
                            </div>

                            <div class="space-y-1">
                                <label for="content"
                                    class="block text-xs font-mono text-neutral-400 uppercase tracking-widest">Komentar</label>
                                <textarea id="content" v-model="commentContent" rows="3" placeholder="Tulis pesan..."
                                    required
                                    class="w-full text-sm font-sans p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors"></textarea>
                            </div>

                            <button type="submit" :disabled="submittingComment"
                                class="w-full text-sm font-mono py-2.5 px-4 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-brand-moss dark:hover:bg-brand-moss hover:text-white dark:hover:text-white rounded-full transition-colors disabled:opacity-50 cursor-pointer">
                                {{ submittingComment ? 'Posting...' : 'Kirim Komentar' }}
                            </button>
                        </form>

                        <div class="space-y-4 pt-2">
                            <div v-if="!selectedProject.comments || selectedProject.comments.length === 0"
                                class="text-sm text-neutral-400 font-mono">
                                Belum ada komentar. Jadilah orang pertama yang meninggalkan pesan dukungan. </div>
                            <div v-for="comment in selectedProject.comments" :key="comment.id"
                                class="p-4 border border-neutral-200 dark:border-neutral-800 rounded-2xl bg-white dark:bg-neutral-900/10 text-sm font-sans">
                                <div class="flex justify-between items-baseline mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{
                                            comment.author_name }}</span>
                                        <button v-if="isOwner" @click="deleteComment(comment.id)"
                                            class="text-[10px] font-mono text-red-500 hover:text-red-650 cursor-pointer focus:outline-none">
                                            Hapus
                                        </button>
                                    </div>
                                    <span class="text-xs font-mono text-neutral-400">{{ formatDate(comment.created_at)
                                        }}</span>
                                </div>
                                <p class="text-neutral-600 dark:text-neutral-400 leading-relaxed whitespace-pre-line">{{
                                    comment.content }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 dark:bg-black/75 backdrop-blur-xs p-4"
            @click.self="closeCreateModal">
            <div
                class="bento-card w-full max-w-2xl rounded-3xl p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto animate-scale-up relative">
                <div
                    class="flex justify-between items-center border-b border-neutral-200 dark:border-neutral-800/65 pb-4">
                    <div>
                        <span class="text-xs font-mono text-brand-moss uppercase tracking-widest">{{ isEditing ? 'Edit program' : 'Publish campaign' }}</span>
                        <h2
                            class="text-xl sm:text-2xl font-serif font-extrabold text-neutral-900 dark:text-neutral-50 mt-1">
                            {{ isEditing ? 'Edit Program Details' : 'Tamabahkan Program' }}</h2>
                    </div>
                    <button @click="closeCreateModal"
                        class="p-1 hover:text-neutral-600 dark:hover:text-neutral-200 text-neutral-400 font-mono text-sm cursor-pointer focus:outline-none">
                        Close
                    </button>
                </div>

                <form @submit.prevent="handleCreateProject" class="space-y-4 text-sm font-sans">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="new_title"
                                class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Judul
                                Program *</label>
                            <input id="new_title" v-model="newTitle" type="text" placeholder="Judul Program" required
                                class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                        </div>

                        <div class="space-y-1">
                            <label for="new_tech"
                                class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Fokus
                                Aksi *</label>
                            <input id="new_tech" v-model="newFocusAreas" type="text"
                                placeholder="Contoh: Konservasi, Edukasi" required
                                class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="new_summary"
                            class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Ringkasan
                            *</label>
                        <input id="new_summary" v-model="newSummary" type="text" placeholder="Ringkasan Singkat..."
                            required
                            class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100" />
                    </div>

                    <div class="space-y-1">
                        <label for="new_description"
                            class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Misi Program
                            Lengkap *</label>
                        <textarea id="new_description" v-model="newDescription" rows="4"
                            placeholder="Deskripsi Misi Program" required
                            class="w-full p-2.5 border border-neutral-200 dark:border-neutral-800 rounded bg-neutral-50 dark:bg-neutral-950 focus:outline-none focus:border-brand-moss transition-colors text-neutral-900 dark:text-neutral-100"></textarea>
                    </div>



                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Gambar
                                Sampul {{ isEditing ? '(Optional)' : '(Required) *' }}</label>
                            <input type="file" accept="image/*" :required="!isEditing" @change="handleMainImageChange"
                                class="w-full text-xs text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-mono file:bg-neutral-100 dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-200 dark:hover:file:bg-neutral-800 cursor-pointer" />
                            <!-- Cover preview wrapper -->
                            <div v-if="coverPreviewUrl"
                                class="mt-2 relative aspect-video rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-800 bg-neutral-100 dark:bg-neutral-950">
                                <img :src="coverPreviewUrl" alt="Cover Image Preview"
                                    class="w-full h-full object-cover">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-mono text-neutral-400 uppercase tracking-widest">Gambar
                                Detail Program</label>
                            <input type="file" accept="image/*" multiple @change="handleGalleryImagesChange"
                                class="w-full text-xs text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-mono file:bg-neutral-100 dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-200 dark:hover:file:bg-neutral-800 cursor-pointer" />
                            <!-- Gallery screenshots previews wrapper -->
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
                        {{ submittingProject ? (isEditing ? 'Updating Program...' : 'Publishing Program...') :
                            (isEditing ? 'Update Program' : 'Publish Program') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="fixed top-24 right-6 z-50 space-y-3 w-80 max-w-sm pointer-events-none">
            <transition-group name="toast">
                <div v-for="toast in normalToasts" :key="toast.id"
                    class="p-4 rounded-3xl shadow-lg border text-sm font-sans pointer-events-auto transition-all duration-300 bg-white/95 dark:bg-[#0c0c0b]/95 backdrop-blur-md border-neutral-200 dark:border-neutral-800">
                    <div class="flex items-start gap-2.5 px-1">
                        <span v-if="toast.type === 'success'" class="text-brand-moss font-bold text-base">✓</span>
                        <span v-else-if="toast.type === 'error'" class="text-red-500 font-bold text-base">✕</span>
                        <span v-else class="text-brand-steel font-bold text-base">ℹ</span>
                        <span class="text-neutral-800 dark:text-neutral-200 font-medium leading-tight">{{ toast.message
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
                        Batal
                    </button>
                    <button @click="toast.onConfirm(); dismissToast(toast.id)"
                        class="text-xs font-mono px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-full cursor-pointer focus:outline-none transition-colors font-semibold shadow-sm">
                        Hapus
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<style>
.bento-card {
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(0, 0, 0, 0.06);
    backdrop-filter: blur(20px);
    box-shadow:
        0 10px 25px -5px rgba(0, 0, 0, 0.08),
        0 8px 16px -6px rgba(0, 0, 0, 0.04),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .bento-card {
    background: rgba(18, 18, 17, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow:
        0 15px 30px -5px rgba(0, 0, 0, 0.45),
        0 8px 16px -6px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.bento-card:hover {
    border-color: rgba(0, 0, 0, 0.15);
    box-shadow:
        0 25px 45px -8px rgba(0, 0, 0, 0.12),
        0 15px 25px -10px rgba(0, 0, 0, 0.06);
    transform: translateY(-4px);
}

.dark .bento-card:hover {
    border-color: rgba(255, 255, 255, 0.15);
    box-shadow:
        0 30px 60px -10px rgba(0, 0, 0, 0.6),
        0 20px 30px -12px rgba(0, 0, 0, 0.45);
}

.bento-card-moss {
    background: rgba(240, 243, 238, 0.85);
    border: 1px solid rgba(135, 150, 115, 0.25);
}

.dark .bento-card-moss {
    background: rgba(22, 26, 20, 0.85);
    border: 1px solid rgba(135, 150, 115, 0.2);
}

html {
    scroll-behavior: smooth;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
    }

    to {
        transform: translateX(0);
    }
}

.animate-slide-in {
    animation: slideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
    }

    to {
        transform: translateX(0);
    }
}

.animate-slide-in-left {
    animation: slideInLeft 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.toast-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: #fafaf9;
}

.dark ::-webkit-scrollbar-track {
    background: #070707;
}

::-webkit-scrollbar-thumb {
    background: #d6d6d4;
    border-radius: 9999px;
    border: 3px solid #fafaf9;
}

.dark ::-webkit-scrollbar-thumb {
    background: #2a2a28;
    border: 3px solid #070707;
}

::-webkit-scrollbar-thumb:hover {
    background: #879673;
}
</style>
