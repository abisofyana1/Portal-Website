<template>
    <div class="h-full w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col relative">
        <div class="bg-teal-700 p-3 shadow-md flex items-center justify-between border-b border-teal-800 z-10 sticky top-0">
            <h2 class="text-white font-bold text-sm tracking-wide uppercase">EXPORT & IMPORT DATA</h2>
        </div>
        
        <div class="p-4 flex-1 overflow-y-auto bg-gray-50/50">
            <!-- Pilihan Data & Format moved to Export Card -->
            
            <!-- Aksi Export & Import -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Export Action -->
                <div class="bg-white p-4 rounded-xl border border-teal-100 shadow-sm flex flex-col justify-between text-center">
                    <div>
                        <h3 class="text-gray-800 font-bold mb-1 mt-2">Export Data</h3>
                        <p class="text-xs text-gray-500 mb-4">Unduh data modul yang dipilih ke dalam format {{ selectedFormat === 'backup' ? '.backup' : selectedFormat.toUpperCase() }}.</p>
                        
                        <div class="flex gap-4 mb-5 text-left bg-gray-50 p-3 rounded-lg border border-gray-100 divide-x divide-gray-200">
                            <div class="flex-1 pr-2">
                                <label class="block text-xs font-bold text-gray-700 mb-2"><i class="fas fa-database text-teal-600 mr-1"></i> Modul</label>
                                <select v-model="selectedModule" class="w-full rounded border border-gray-300 shadow-sm text-xs p-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
                                    <option value="news">Manage News</option>
                                    <option value="users">Manage User</option>
                                    <option value="units">Manage Unit</option>
                                </select>
                            </div>
                            <div class="flex-1 pl-4">
                                <label class="block text-xs font-bold text-gray-700 mb-2"><i class="fas fa-file-export text-teal-600 mr-1"></i> Format File</label>
                                <div class="flex flex-col gap-2 text-xs text-gray-600">
                                    <label class="flex items-center cursor-pointer"><input type="radio" v-model="selectedFormat" value="csv" class="mr-1.5 text-teal-600 focus:ring-teal-500"> .CSV</label>
                                    <label class="flex items-center cursor-pointer"><input type="radio" v-model="selectedFormat" value="xlsx" class="mr-1.5 text-teal-600 focus:ring-teal-500"> .XLSX</label>
                                    <label class="flex items-center cursor-pointer"><input type="radio" v-model="selectedFormat" value="backup" class="mr-1.5 text-teal-600 focus:ring-teal-500"> .backup</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button @click="handleExport" :disabled="isLoading" class="px-6 py-2.5 bg-teal-600 text-white rounded-lg font-bold text-sm hover:bg-teal-700 transition w-full flex justify-center items-center disabled:opacity-50 mt-auto">
                        <i v-if="isLoading" class="fas fa-spinner fa-spin mr-2"></i>
                        <i v-else class="fas fa-download mr-2"></i> Export Sekarang
                    </button>
                </div>

                <!-- Import Action -->
                <div class="bg-white rounded-xl border border-indigo-100 shadow-sm flex flex-col overflow-hidden">
                    <div class="p-4 flex-1 flex flex-col items-center justify-center text-center">
                        <h3 class="text-gray-800 font-bold mb-1 mt-2">Import Data</h3>
                        <p class="text-xs text-gray-500 mb-3">Unggah file format {{ selectedFormat === 'backup' ? '.backup' : selectedFormat.toUpperCase() }} untuk memperbarui atau menambah data.</p>
                        
                        <button type="button" @click="downloadTemplate" class="px-4 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg font-semibold text-[11px] hover:bg-indigo-100 transition border border-indigo-100 shadow-sm flex items-center justify-center cursor-pointer">
                            <i class="fas fa-file-download mr-1.5"></i> Download Template {{ selectedFormat === 'backup' ? '.backup' : selectedFormat.toUpperCase() }}
                        </button>
                        <div v-if="selectedModule === 'users'" class="text-xs text-gray-500 mt-2 mb-3 leading-relaxed text-left w-full px-4 border-l-2 border-indigo-200">
                            *Khusus Users Di dalam template terdapat kolom :<br>
                            -role_id (1: Admin, 2: Agent, 3: Super Admin),<br>
                            -status_id (1: Aktif, 2: Nonaktif),<br>
                            -unit_id ({{ unitsList }}).
                        </div>
                        <div v-else class="mb-4"></div>
                        
                        <!-- Drag & Drop / File Input Area -->
                        <div class="w-full border-2 border-dashed border-gray-300 rounded-xl p-4 text-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer" @click="$refs.fileInput.click()">
                            <i class="fas fa-cloud-upload-alt text-3xl text-indigo-400 mb-2"></i>
                            <p class="text-sm text-gray-600 font-medium mb-1">Pilih File untuk Diimport</p>
                            <p class="text-xs text-gray-400">Format yang didukung: .{{ selectedFormat }}</p>
                            <input type="file" ref="fileInput" @change="handleFileSelect" class="hidden" multiple :accept="acceptedExtensions">
                        </div>

                        <!-- Selected Files Info -->
                        <div v-if="selectedFiles.length > 0" class="mt-4 w-full bg-indigo-50 rounded-xl p-4 border border-indigo-100 flex flex-col gap-2 text-left">
                            <div v-for="(file, index) in selectedFiles" :key="index" class="flex justify-between items-center text-sm border-b border-indigo-100 pb-2 last:border-0 last:pb-0">
                                <div class="flex items-center text-indigo-800">
                                    <i class="fas fa-file-alt mr-2 text-indigo-500"></i>
                                    <span class="font-medium truncate max-w-[200px]">{{ file.name }}</span>
                                </div>
                                <button @click="removeFile(index)" class="text-rose-500 hover:text-rose-700 transition" title="Hapus file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Import -->
                    <div class="p-4 border-t border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <span class="text-[10px] text-gray-500 max-w-[60%] leading-tight"><i class="fas fa-info-circle mr-1"></i> Data akan diproses (Upsert) berdasarkan ID/Key unik.</span>
                        <button @click="handleImport" :disabled="selectedFiles.length === 0 || isLoading" :class="['px-6 py-2 rounded-lg font-bold text-sm transition shadow', selectedFiles.length > 0 && !isLoading ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-200' : 'bg-gray-200 text-gray-400 cursor-not-allowed']">
                            <i v-if="isLoading" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-upload mr-2"></i> Import
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pengaturan Backup Otomatis -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-gray-800 font-bold flex items-center text-base">
                        <i class="fas fa-clock text-amber-500 mr-2"></i> Penjadwalan Backup Otomatis
                    </h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="schedule.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">{{ schedule.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </label>
                </div>

                <div v-if="!schedule.is_active" class="mt-4 p-5 bg-gray-50 border border-gray-200 rounded-lg text-center animate-scale-up">
                    <i class="fas fa-info-circle text-gray-400 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-600">Saat ini fitur Penjadwalan Backup Otomatis dalam posisi <span class="font-bold text-gray-800">Nonaktif</span>.</p>
                    <p class="text-xs text-gray-500 mt-1">Sistem tidak akan melakukan pencadangan data secara otomatis. Anda tetap dapat melakukan backup secara manual melalui menu Export Data.</p>
                </div>

                <div v-if="schedule.is_active" class="space-y-6 animate-scale-up mt-4">
                    <!-- Tab Menu for Pengaturan / History -->
                    <div class="flex border-b border-gray-200">
                        <button @click="activeTab = 'pengaturan'" :class="['px-4 py-2 font-bold text-sm transition', activeTab === 'pengaturan' ? 'border-b-2 border-amber-500 text-amber-600' : 'text-gray-500 hover:text-gray-700']">Pengaturan Penjadwalan</button>
                        <button @click="activeTab = 'history'" :class="['px-4 py-2 font-bold text-sm transition', activeTab === 'history' ? 'border-b-2 border-amber-500 text-amber-600' : 'text-gray-500 hover:text-gray-700']">History Backup</button>
                    </div>

                    <!-- PENGATURAN TAB -->
                    <div v-if="activeTab === 'pengaturan'" class="space-y-6">
                        <div>
                            <label class="relative inline-flex items-center cursor-pointer mb-2">
                                <input type="checkbox" v-model="schedule.frequency" true-value="custom" false-value="1_month" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                <span class="ml-3 text-sm font-bold text-gray-700">Atur Waktu Spesifik (Custom)</span>
                            </label>
                            <div v-if="schedule.frequency !== 'custom'" class="mt-2 mb-4 p-3 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-800 flex items-start">
                                <i class="fas fa-info-circle mt-0.5 mr-2 text-blue-500"></i>
                                <p>Secara bawaan (default), sistem akan menarik data otomatis setiap <b>Tanggal 1 pada jam 00:00 awal bulan</b>. Aktifkan toggle di atas jika Anda ingin mengatur waktu sendiri.</p>
                            </div>
                        </div>

                        <div v-if="schedule.frequency === 'custom'" class="bg-amber-50/50 p-4 rounded-lg border border-amber-100">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wider">Pengaturan Waktu Spesifik (Pilih di Kalender)</h4>
                                <div :class="['text-xs font-bold bg-white px-2 py-1 rounded shadow-sm border', countdownText === 'Backup Terakhir' || countdownText.includes('Berhasil') ? 'text-emerald-600 border-emerald-100' : 'text-rose-500 border-rose-100']" v-if="countdownText">
                                    <i :class="['fas mr-1', countdownText === 'Backup Terakhir' || countdownText.includes('Berhasil') ? 'fa-check-circle' : 'fa-hourglass-half']"></i> {{ countdownText }}
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-amber-800 mb-2">Tanggal & Waktu (Jam, Menit, Detik)</label>
                                <VueDatePicker v-model="schedule.custom_datetime" 
                                               :is-24="true" 
                                               :enable-seconds="true"
                                               :text-input="true" 
                                               :clearable="false"
                                               auto-apply
                                               model-type="yyyy-MM-dd HH:mm:ss"
                                               format="dd/MM/yyyy, HH:mm:ss"
                                               placeholder="Pilih Tanggal & Waktu" 
                                               class="w-full sm:max-w-[300px]" />
                            </div>
                        </div>
                    </div>

                    </div>
                    <!-- HISTORY TAB -->
                    <div v-if="activeTab === 'history'" class="animate-scale-up">
                        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
                            <div class="overflow-x-auto max-h-[300px]">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="p-3 font-semibold text-gray-700 border-b">Tanggal & Waktu</th>
                                            <th class="p-3 font-semibold text-gray-700 border-b">Keterangan</th>
                                            <th class="p-3 font-semibold text-gray-700 border-b">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="h in histories" :key="h.id" class="hover:bg-gray-50 transition">
                                            <td class="p-3 whitespace-nowrap">{{ formatLocalDate(h.datetime) }}</td>
                                            <td class="p-3 text-gray-600 whitespace-pre-line">{{ h.description }}</td>
                                            <td class="p-3">
                                                <span :class="['px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide', h.status === 'Berhasil' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
                                                    {{ h.status }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="histories.length === 0">
                                            <td colspan="3" class="p-4 text-center text-gray-500">Belum ada riwayat backup.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div> <!-- closes schedule.is_active -->

                <!-- Always visible Simpan Jadwal button -->
                <div class="flex justify-end pt-4 mt-4 border-t border-gray-100">
                    <button @click="saveSchedule" :disabled="isSavingSchedule" class="px-6 py-2 bg-amber-500 text-white rounded-lg font-bold text-sm hover:bg-amber-600 transition shadow flex items-center">
                        <i v-if="isSavingSchedule" class="fas fa-spinner fa-spin mr-2"></i>
                        <i v-else class="fas fa-save mr-2"></i> Simpan Jadwal
                    </button>
                </div>
            </div> <!-- closes Pengaturan Backup Otomatis card -->
        </div> <!-- closes main container -->

        <!-- Custom Centered Popup Modal -->
        <div v-if="popup.show" class="fixed inset-0 z-[12000] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all animate-scale-up">
                <div class="p-6 text-center">
                    <div :class="[
                        'w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4',
                        popup.type === 'success' ? 'bg-emerald-100 text-emerald-500' : '',
                        popup.type === 'error' ? 'bg-rose-100 text-rose-500' : '',
                        popup.type === 'warning' ? 'bg-amber-100 text-amber-500' : ''
                    ]">
                        <i :class="[
                            'fas text-4xl',
                            popup.type === 'success' ? 'fa-check-circle' : '',
                            popup.type === 'error' ? 'fa-times-circle' : '',
                            popup.type === 'warning' ? 'fa-exclamation-triangle' : ''
                        ]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ popup.title }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed font-medium bg-gray-50 p-4 rounded mt-4 border border-gray-200 text-left whitespace-pre-line" v-if="popup.message.includes('\n') || popup.message.includes('-')">{{ popup.message }}</p>
                    <p class="text-sm text-gray-600 leading-relaxed font-medium" v-else>{{ popup.message }}</p>
                </div>
                <div class="p-4 bg-gray-50 flex justify-center border-t border-gray-100">
                    <button @click="popup.show = false" :class="[
                        'px-8 py-2 rounded-xl text-white font-bold text-sm transition-all shadow-md',
                        popup.type === 'success' ? 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200' : '',
                        popup.type === 'error' ? 'bg-rose-500 hover:bg-rose-600 shadow-rose-200' : '',
                        popup.type === 'warning' ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-200' : ''
                    ]">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Overlay for Global Actions -->
        <div v-if="isLoading" class="fixed inset-0 z-[11000] flex flex-col items-center justify-center bg-white/80 backdrop-blur-[2px]">
            <div class="relative w-16 h-16">
                <div class="absolute inset-0 border-4 border-teal-100 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-teal-500 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <div class="text-center mt-4">
                <p class="text-gray-800 font-bold">Memproses Data...</p>
                <p class="text-gray-500 text-xs mt-1">Harap tunggu sebentar</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const selectedModule = ref('news');
const selectedFormat = ref('csv');
const selectedFiles = ref([]);
const fileInput = ref(null);
const isLoading = ref(false);
const isSavingSchedule = ref(false);
const activeTab = ref('pengaturan');
const histories = ref([]);
const unitsList = ref('');
const countdownText = ref('');
let countdownInterval = null;

const schedule = ref({
    is_active: false,
    frequency: '1_month',
    custom_datetime: ''
});

const originalSchedule = ref(null);

const popup = ref({
    show: false,
    title: '',
    message: '',
    type: 'success'
});

const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        const options = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        return date.toLocaleString('en-GB', options).replace(',', '');
    } catch (e) {
        return dateStr;
    }
};

let hasFetchedAfterCountdown = false;

const updateCountdown = () => {
    if (schedule.value.is_active && schedule.value.frequency === 'custom' && schedule.value.custom_datetime) {
        const target = new Date(schedule.value.custom_datetime).getTime();
        const now = new Date().getTime();
        const diff = target - now;
        
        if (diff > 0) {
            hasFetchedAfterCountdown = false;
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);
            
            let text = [];
            if(d > 0) text.push(`${d} Hari`);
            if(h > 0 || d > 0) text.push(`${h} Jam`);
            text.push(`${m} Menit`);
            text.push(`${s} Detik`);
            countdownText.value = text.join(' ');
        } else {
            if (!hasFetchedAfterCountdown && diff > -120000) {
                countdownText.value = 'Memproses Backup...';
                if (Math.abs(diff) % 5000 < 1500) {
                    fetchHistory();
                }
                if (histories.value.length > 0) {
                    const latest = new Date(histories.value[0].datetime).getTime();
                    if (Math.abs(latest - target) < 120000) {
                        countdownText.value = histories.value[0].status === 'Berhasil' ? 'Selesai (Berhasil disimpan)' : 'Selesai (Gagal)';
                        hasFetchedAfterCountdown = true;
                    }
                }
            } else if (!hasFetchedAfterCountdown) {
                countdownText.value = 'Backup Terakhir';
            }
        }
    } else {
        countdownText.value = '';
    }
};

const showPopup = (title, message, type = 'success') => {
    popup.value = { show: true, title, message, type };
};

const acceptedExtensions = computed(() => {
    return `.${selectedFormat.value}`;
});

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    if (files.length > 0) {
        // Validate extensions
        const invalid = files.filter(f => !f.name.toLowerCase().endsWith(`.${selectedFormat.value}`));
        if (invalid.length > 0) {
            showPopup('Format Tidak Sesuai', `Beberapa file tidak sesuai format .${selectedFormat.value}`, 'error');
            return;
        }
        selectedFiles.value = [...selectedFiles.value, ...files];
    }
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    if (selectedFiles.value.length === 0 && fileInput.value) {
        fileInput.value.value = '';
    }
};

const clearFile = () => {
    selectedFiles.value = [];
    if (fileInput.value) fileInput.value.value = '';
};

const triggerDownload = (url) => {
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', '');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadTemplate = () => {
    const url = `/api/export-import/template/${selectedModule.value}/${selectedFormat.value}`;
    triggerDownload(url);
};

const handleExport = () => {
    isLoading.value = true;
    try {
        const url = `/api/export-import/export/${selectedModule.value}/${selectedFormat.value}`;
        triggerDownload(url);
        
        showPopup('Berhasil', `Data ${selectedModule.value} berhasil di-export dalam format ${selectedFormat.value === 'backup' ? '.BACKUP' : '.' + selectedFormat.value.toUpperCase()}.`, 'success');
    } catch (error) {
        showPopup('Gagal', 'Terjadi kesalahan saat meng-export data.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const handleImport = async () => {
    if (selectedFiles.value.length === 0) return;
    
    isLoading.value = true;
    const formData = new FormData();
    selectedFiles.value.forEach(file => {
        formData.append('files[]', file);
    });
    
    try {
        const response = await axios.post(`/api/export-import/import/${selectedModule.value}/${selectedFormat.value}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        const res = response.data;
        if (res.status === 'success') {
            showPopup('Import Berhasil', res.message, 'success');
            clearFile();
        } else if (res.status === 'warning') {
             showPopup('Import dengan Catatan', res.message, 'warning');
             clearFile();
        } else {
             showPopup('Import Gagal', res.message || 'Terjadi kesalahan.', 'error');
        }
    } catch (error) {
        let msg = 'Terjadi kesalahan saat meng-import data.';
        if (error.response?.data?.message) {
            msg = error.response.data.message;
        }
        showPopup('Gagal', msg, 'error');
    } finally {
        isLoading.value = false;
    }
};

const fetchSchedule = async () => {
    try {
        const response = await axios.get('/api/export-import/schedule');
        if (response.data) {
            schedule.value = {
                is_active: response.data.is_active == 1,
                frequency: response.data.frequency || '1_month',
                custom_datetime: response.data.custom_datetime || ''
            };
            originalSchedule.value = JSON.parse(JSON.stringify(schedule.value));
            updateCountdown();
            if (!countdownInterval) {
                countdownInterval = setInterval(updateCountdown, 1000);
            }
        }
    } catch (e) {
        // Ignored
    }
};

const fetchHistory = async () => {
    try {
        const response = await axios.get('/api/export-import/history');
        histories.value = response.data || [];
    } catch (e) {
        // Ignored
    }
};

const fetchUnitsInfo = async () => {
    try {
        const response = await axios.get('/api/units?all=true');
        if (response.data) {
            const dataList = response.data.data || response.data;
            if (Array.isArray(dataList)) {
                unitsList.value = [...dataList]
                    .sort((a, b) => a.id - b.id)
                    .map(u => `${u.id}: ${u.short_name || u.name}`)
                    .join(', ');
            }
        }
    } catch (e) {
        // Ignored
    }
};

watch(selectedModule, (newMod) => {
    if (newMod === 'users') {
        fetchUnitsInfo();
    }
});

const saveSchedule = async () => {
    if (originalSchedule.value) {
        const isUnchanged = originalSchedule.value.is_active === schedule.value.is_active &&
                            originalSchedule.value.frequency === schedule.value.frequency &&
                            originalSchedule.value.custom_datetime === schedule.value.custom_datetime;
        if (isUnchanged) {
            showPopup('Peringatan', 'Tidak Bisa Menyimpan Jadwal Jika Tidak Ada Perubahan Waktu', 'warning');
            return;
        }
    }

    isSavingSchedule.value = true;
    try {
        await axios.post('/api/export-import/schedule', schedule.value);
        originalSchedule.value = JSON.parse(JSON.stringify(schedule.value));
        showPopup('Tersimpan', 'Jadwal backup otomatis berhasil diperbarui.', 'success');
    } catch (error) {
        showPopup('Gagal', 'Terjadi kesalahan saat menyimpan jadwal.', 'error');
    } finally {
        isSavingSchedule.value = false;
    }
};

onMounted(() => {
    fetchSchedule();
    fetchHistory();
    fetchUnitsInfo();
});
</script>

<style scoped>
@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-scale-up {
    animation: scaleUp 0.3s ease-out forwards;
}

/* VueDatePicker Custom Styles to match theme */
:deep(.dp__theme_light) {
    --dp-primary-color: #f59e0b; /* amber-500 */
    --dp-primary-text-color: #fff;
    --dp-border-color: #fde68a; /* amber-200 */
    --dp-border-color-hover: #f59e0b;
    --dp-border-radius: 0.5rem;
    --dp-input-padding: 0.75rem 1rem 0.75rem 2.5rem;
}
:deep(.dp__input) {
    border-width: 2px !important;
    font-size: 1rem !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
</style>
