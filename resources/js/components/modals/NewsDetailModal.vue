<template>
    <!-- View News Modal (Global) -->
    <div v-if="isOpen" @click.self="closeModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 backdrop-blur-[2px]">
        <div :class="isMaximized ? 'fixed inset-0 w-screen h-screen max-w-none max-h-none rounded-none' : 'w-[900px] max-w-full max-h-[95vh] rounded-xl'" class="bg-white shadow-2xl overflow-hidden flex flex-col relative animate-fade-in-up text-left transition-all duration-300">
             <!-- Header -->
            <div style="background-color: #FCAD49;" class="text-white px-4 py-2 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                <div class="flex items-center space-x-3">
                    <h2 class="text-base md:text-lg font-bold uppercase tracking-wide flex items-center truncate pr-2">
                        <span class="truncate font-black">Lihat Data</span>
                    </h2>
                    <button v-if="currentLayout === 'agent'" @click="$emit('open-news-list')" class="bg-white/20 hover:bg-white/30 text-white text-[10px] px-2 py-1 rounded font-bold shadow-sm flex items-center transition uppercase tracking-wider">
                        <i class="fas fa-list mr-1"></i> Daftar Berita
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="isMaximized = !isMaximized" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer" :title="isMaximized ? 'Perkecil' : 'Layar Penuh (Fullscreen)'">
                        <i :class="isMaximized ? 'fas fa-compress text-xs' : 'fas fa-expand text-xs'"></i>
                    </button>
                    <button @click="closeModal" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-8 overflow-y-auto bg-white flex-1 relative">

                <!-- Title (paling atas) -->
                <h1 class="text-2xl font-bold text-gray-700 text-center mb-1 leading-snug">{{ newsData?.title }}</h1>
                
                <!-- Metadata -->
                <div class="text-center text-sm text-gray-600 mb-4 flex justify-center gap-6 flex-wrap font-medium">
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-user text-base text-teal-500"></i>
                        <span class="font-semibold">By:</span> {{ newsData?.created_by }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-calendar-alt text-base text-teal-500"></i>
                        <span class="font-semibold">Created:</span> {{ newsData?.created_at ? new Date(newsData.created_at).toLocaleString() : '-' }}
                    </div>
                    <div v-if="newsData?.end_time" class="flex items-center gap-1.5 font-bold text-base px-3 py-1 rounded-full" :class="new Date(newsData.end_time) < new Date() ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600'">
                        <i class="fas fa-clock text-base"></i>
                        <span>EndTime:</span> {{ new Date(newsData.end_time).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) }}
                    </div>
                </div>

                <!-- Attachment Image: use file attachment first (if image), then first image from content (base64/URL) -->
                <div v-if="(newsData?.attachment && isImage(newsData.attachment)) || newsData?.firstContentImage" class="mb-6 -mx-8">
                    <img :src="newsData.attachment && isImage(newsData.attachment) ? `/storage/${newsData.attachment}` : newsData.firstContentImage"
                         alt="News Image"
                         class="w-full object-contain shadow-md bg-gray-50 cursor-zoom-in"
                         style="max-height: 600px;"
                         @click="onContentClick"
                         @error="$event.target.style.display='none'"
                    >
                </div>

                <!-- Body Content -->
                <div class="ck-content prose max-w-none text-sm text-gray-800" v-html="newsData?.content" @click="onContentClick"></div>
            </div>

             <!-- Footer -->
             <div class="bg-white px-4 py-2 shrink-0 flex flex-col md:flex-row justify-between items-center border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)] gap-3">
                <div class="text-sm font-bold text-gray-500">
                    <span v-if="currentLayout !== 'agent'" class="hover:text-teal-600 cursor-pointer" @click="$emit('edit-news', newsData)">Ubah | </span> 
                </div>
                <button @click="closeModal" class="px-5 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                    <i class="fas fa-check mr-2"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    newsData: {
        type: Object,
        default: () => ({})
    },
    currentLayout: {
        type: String,
        default: 'agent'
    },
    isImageFile: {
        type: Function,
        default: (filename) => {
            if (!filename || typeof filename !== 'string') return false;
            return /\.(jpg|jpeg|png|webp|gif|svg|avif)$/i.test(filename);
        }
    }
});

const emit = defineEmits(['close', 'open-news-list', 'edit-news', 'content-click']);

const isMaximized = ref(false);

const closeModal = () => {
    isMaximized.value = false;
    emit('close');
};

const isImage = (filename) => {
    return props.isImageFile(filename);
};

const onContentClick = (e) => {
    emit('content-click', e);
};
</script>
