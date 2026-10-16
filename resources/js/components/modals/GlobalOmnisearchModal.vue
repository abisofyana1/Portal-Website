<template>
    <!-- Omnisearch Modal / Command Palette Backdrop -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9999] flex items-start justify-center pt-14 sm:pt-20 px-4 bg-slate-950/70 backdrop-blur-sm"
                @click.self="close"
            >
                <!-- Modal Dialog -->
                <div
                    class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden flex flex-col max-h-[82vh] animate-in fade-in zoom-in-95 duration-150"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Pencarian Cerdas Omnisearch PortalCXC"
                >
                    <!-- Search Header Input Bar -->
                    <div class="relative flex items-center px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-teal-50/30">
                        <i class="fas fa-search text-teal-600 text-base mr-3 shrink-0"></i>
                        <input
                            ref="searchInputRef"
                            v-model="query"
                            type="text"
                            placeholder="Cari promo, pengumuman, berkas... (Ctrl+K)"
                            class="w-full bg-transparent border-none text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-0 font-medium"
                            @keydown.down.prevent="navigateResults(1)"
                            @keydown.up.prevent="navigateResults(-1)"
                            @keydown.enter.prevent="selectActiveItem"
                            @keydown.esc.prevent="close"
                        />
                        <!-- Clear or Spinner Icon -->
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <span v-if="isLoading" class="w-4 h-4 border-2 border-teal-500 border-t-transparent rounded-full animate-spin"></span>
                            <button
                                v-else-if="query"
                                @click="query = ''; searchResults = [];"
                                class="text-slate-400 hover:text-slate-600 p-1 rounded-full text-xs"
                                title="Hapus teks"
                            >
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                            <span class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 bg-slate-200/80 rounded border border-slate-300">ESC</span>
                        </div>
                    </div>

                    <!-- Search Results Body -->
                    <div class="flex-1 overflow-y-auto p-3 space-y-1.5 bg-slate-50/30 custom-scrollbar">
                        <!-- Loading State -->
                        <div v-if="isLoading && searchResults.length === 0" class="py-10 text-center text-slate-400">
                            <div class="inline-block w-7 h-7 border-2 border-teal-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                            <p class="text-xs font-medium">Mencari cepat di database...</p>
                        </div>

                        <!-- Empty Search Query / Prompt State -->
                        <div v-else-if="!query && searchResults.length === 0" class="py-10 px-6 text-center text-slate-400">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-700 mb-1">Pencarian Cerdas Omnisearch PortalCXC</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                Ketik kata kunci promo, pengumuman atau nama berkas untuk mencari secara instan di seluruh unit.
                            </p>
                            <div class="mt-4 flex flex-wrap justify-center gap-2 text-[11px] text-slate-500">
                                <span class="bg-white border border-slate-200 px-2.5 py-1 rounded-lg">💡 Tekan <kbd class="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-700">Ctrl + K</kbd> kapan saja</span>
                                <span class="bg-white border border-slate-200 px-2.5 py-1 rounded-lg">⚡ Hasil super cepat</span>
                            </div>
                        </div>

                        <!-- No Results Found State -->
                        <div v-else-if="searchResults.length === 0 && !isLoading" class="py-12 text-center text-slate-400">
                            <i class="fas fa-search-minus text-3xl mb-2 text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-600">Tidak ada hasil ditemukan</p>
                            <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci promo, pengumuman, atau nama berkas lainnya.</p>
                        </div>

                        <!-- Results List -->
                        <div
                            v-for="(item, index) in searchResults"
                            :key="item.id"
                            @click="selectItem(item)"
                            @mouseenter="activeIndex = index"
                            :class="[
                                'p-3 rounded-xl cursor-pointer transition-all duration-150 flex items-start gap-3 border',
                                activeIndex === index
                                    ? 'bg-teal-50/70 border-teal-200 shadow-sm translate-x-0.5'
                                    : 'bg-white border-slate-100 hover:border-slate-200 hover:bg-slate-50/60'
                            ]"
                        >
                            <!-- Category Badge Icon -->
                            <div
                                :class="[
                                    'w-9 h-9 rounded-xl flex items-center justify-center text-sm shrink-0 shadow-xs font-bold',
                                    getCategoryIconBg(item.category)
                                ]"
                            >
                                <i :class="getCategoryIconClass(item.category)"></i>
                            </div>

                            <!-- Content Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <h4
                                        class="text-xs sm:text-sm font-bold text-slate-800 truncate"
                                        :class="{ 'text-teal-800': activeIndex === index }"
                                        v-html="highlightMatch(item.title, query)"
                                    ></h4>
                                    <span
                                        :class="[
                                            'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider shrink-0 border',
                                            getCategoryBadgeStyle(item.category)
                                        ]"
                                    >
                                        {{ item.category || 'Berita' }}
                                    </span>
                                </div>

                                <!-- Keyword or Snippet Preview -->
                                <p
                                    v-if="item.keyword || item.content_preview || item.content"
                                    class="text-[11px] text-slate-500 line-clamp-1 mb-1.5"
                                    v-html="getSnippetText(item)"
                                ></p>

                                <!-- Meta: Target Unit & Dates & Attachment badge -->
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-400 font-medium">
                                    <span v-if="item.target_unit" class="flex items-center gap-1 text-slate-600">
                                        <i class="fas fa-building text-[9px] text-teal-600"></i>
                                        {{ formatTargetUnit(item.target_unit) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-calendar-alt text-[9px]"></i>
                                        {{ formatDate(item.created_at) }}
                                    </span>
                                    <span v-if="item.end_time" class="flex items-center gap-1 font-semibold text-amber-600">
                                        <i class="fas fa-clock text-[9px]"></i>
                                        Berlaku s/d {{ formatDate(item.end_time) }}
                                    </span>
                                    <span
                                        v-if="hasAttachment(item)"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-blue-50 text-blue-600 border border-blue-100 font-semibold"
                                    >
                                        <i class="fas fa-paperclip text-[8px]"></i>
                                        Lampiran Dokumen
                                    </span>
                                </div>
                            </div>

                            <!-- Enter Action Hint -->
                            <div v-if="activeIndex === index" class="hidden sm:flex items-center shrink-0 text-teal-600 text-xs font-semibold pl-1">
                                <span class="text-[10px] bg-teal-100 px-1.5 py-0.5 rounded">Buka ↵</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Controls -->
                    <div class="px-4 py-2.5 bg-slate-100/80 border-t border-slate-200 text-slate-500 text-[11px] flex flex-wrap items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="font-medium">
                                Ditemukan: <strong class="text-slate-800 font-bold">{{ searchResults.length }}</strong> hasil
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-400">
                            <span><kbd class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-300 text-slate-700 shadow-2xs">↑</kbd> <kbd class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-300 text-slate-700 shadow-2xs">↓</kbd> Navigasi</span>
                            <span><kbd class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-300 text-slate-700 shadow-2xs">↵</kbd> Buka</span>
                            <span><kbd class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-300 text-slate-700 shadow-2xs">ESC</kbd> Tutup</span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    userUnit: {
        type: String,
        default: ''
    },
    appCategories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'select-news']);

const searchInputRef = ref(null);
const query = ref('');
const searchResults = ref([]);
const isLoading = ref(false);
const activeIndex = ref(0);

// Cache search results in memory for instant retrieval
const searchCache = new Map();
let debounceTimer = null;
let currentAbortController = null;

// Watch isOpen to autofocus input or reset state on close
watch(
    () => props.isOpen,
    (val) => {
        if (val) {
            activeIndex.value = 0;
            nextTick(() => {
                if (searchInputRef.value) {
                    searchInputRef.value.focus();
                    searchInputRef.value.select();
                }
            });
            if (query.value && query.value.trim()) {
                performSearch(query.value.trim());
            }
        } else {
            if (debounceTimer) clearTimeout(debounceTimer);
            if (currentAbortController) {
                currentAbortController.abort();
                currentAbortController = null;
            }
            query.value = '';
            searchResults.value = [];
            isLoading.value = false;
        }
    }
);

// Watch query for fast debounced search (150ms debounce for rapid response)
watch(query, (newVal) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    const trimmed = (newVal || '').trim();
    if (!trimmed) {
        if (currentAbortController) {
            currentAbortController.abort();
            currentAbortController = null;
        }
        searchResults.value = [];
        isLoading.value = false;
        return;
    }

    // Check instant cache
    const cacheKey = `${trimmed}_${props.userUnit || ''}`;
    if (searchCache.has(cacheKey)) {
        if (currentAbortController) {
            currentAbortController.abort();
            currentAbortController = null;
        }
        searchResults.value = searchCache.get(cacheKey);
        isLoading.value = false;
        activeIndex.value = 0;
        return;
    }

    // Clear stale results immediately so results from a previous query don't linger
    searchResults.value = [];
    isLoading.value = true;
    debounceTimer = setTimeout(() => {
        performSearch(trimmed);
    }, 150);
});

async function performSearch(searchTerm) {
    if (!searchTerm) {
        searchResults.value = [];
        isLoading.value = false;
        return;
    }

    const cacheKey = `${searchTerm}_${props.userUnit || ''}`;
    if (searchCache.has(cacheKey)) {
        searchResults.value = searchCache.get(cacheKey);
        isLoading.value = false;
        activeIndex.value = 0;
        return;
    }

    // Cancel pending request if any
    if (currentAbortController) {
        currentAbortController.abort();
    }
    const abortController = new AbortController();
    currentAbortController = abortController;

    try {
        isLoading.value = true;
        const params = {
            search: searchTerm,
            unit: props.userUnit || '',
            per_page: 30,
            sort_column: 'created_at',
            sort_order: 'desc',
            exclude_content: 1 // Crucial for ultra fast transfer (< 10ms instead of 150ms)
        };

        const response = await axios.get('/api/news', { 
            params,
            signal: abortController.signal
        });

        // Only update if this request is still the active one
        if (currentAbortController === abortController) {
            const items = response.data?.data || response.data || [];
            const finalResults = Array.isArray(items) ? items : [];
            
            searchCache.set(cacheKey, finalResults);
            searchResults.value = finalResults;
            activeIndex.value = 0;
        }
    } catch (err) {
        if (axios.isCancel(err) || err.name === 'CanceledError' || err.code === 'ERR_CANCELED') {
            // Request was canceled by newer search, ignore
            return;
        }
        console.error('Omnisearch failed:', err);
        if (currentAbortController === abortController) {
            searchResults.value = [];
        }
    } finally {
        if (currentAbortController === abortController) {
            isLoading.value = false;
        }
    }
}

function hasAttachment(item) {
    if (!item) return false;
    if (item.attachment && typeof item.attachment === 'string' && item.attachment.trim() !== '') return true;
    if (item.attachments && Array.isArray(item.attachments) && item.attachments.length > 0) return true;
    if (typeof item.attachments === 'string' && item.attachments.length > 2) return true;
    return false;
}

function navigateResults(direction) {
    if (searchResults.value.length === 0) return;
    const len = searchResults.value.length;
    activeIndex.value = (activeIndex.value + direction + len) % len;
}

function selectActiveItem() {
    if (searchResults.value.length > 0 && activeIndex.value >= 0 && activeIndex.value < searchResults.value.length) {
        selectItem(searchResults.value[activeIndex.value]);
    }
}

function selectItem(item) {
    emit('select-news', item);
    close();
}

function close() {
    emit('close');
}

function highlightMatch(text, q) {
    if (!text) return '';
    if (!q || !q.trim()) return escapeHtml(text);
    const escaped = escapeRegex(q.trim());
    const regex = new RegExp(`(${escaped})`, 'gi');
    return escapeHtml(text).replace(regex, '<mark class="bg-amber-200 text-amber-900 rounded px-0.5 font-black">$1</mark>');
}

function getSnippetText(item) {
    if (item.keyword) {
        return `<strong>Kata Kunci:</strong> ${escapeHtml(item.keyword)}`;
    }
    if (item.content_preview) {
        return escapeHtml(item.content_preview);
    }
    if (item.content) {
        const plain = item.content.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
        return plain.length > 120 ? plain.substring(0, 120) + '...' : plain;
    }
    return '';
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function formatTargetUnit(unit) {
    if (!unit) return 'Semua Unit';
    if (unit.startsWith('[') && unit.endsWith(']')) {
        try {
            const parsed = JSON.parse(unit);
            return parsed.join(', ');
        } catch {
            return unit;
        }
    }
    return unit;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch {
        return dateStr;
    }
}

function getCategoryIconClass(category) {
    const c = (category || '').toLowerCase();
    if (c.includes('promo') || c.includes('program')) return 'fas fa-gift';
    if (c.includes('announcement') || c.includes('pengumuman')) return 'fas fa-bullhorn';
    return 'fas fa-newspaper';
}

function getCategoryIconBg(category) {
    const c = (category || '').toLowerCase();
    if (c.includes('promo') || c.includes('program')) return 'bg-amber-100 text-amber-700';
    if (c.includes('announcement') || c.includes('pengumuman')) return 'bg-rose-100 text-rose-700';
    return 'bg-teal-100 text-teal-700';
}

function getCategoryBadgeStyle(category) {
    const c = (category || '').toLowerCase();
    if (c.includes('promo') || c.includes('program')) return 'bg-amber-50 text-amber-700 border-amber-200';
    if (c.includes('announcement') || c.includes('pengumuman')) return 'bg-rose-50 text-rose-700 border-rose-200';
    return 'bg-teal-50 text-teal-700 border-teal-200';
}
</script>
