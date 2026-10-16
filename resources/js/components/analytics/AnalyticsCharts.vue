<template>
    <!-- Statistics Grid & Charts -->
    <div v-if="dashboardStats" class="space-y-6">
                            <!-- Stat Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Card 1: Agent Unit -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between transition-all hover:shadow-md group relative overflow-hidden">
                                    <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-teal-50 rounded-full transition-transform group-hover:scale-110 opacity-70"></div>
                                    <div class="flex items-center justify-between w-full relative z-10">
                                        <div class="space-y-2">
                                            <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Agent Unit</div>
                                            <div class="text-3xl font-black text-gray-800 tracking-tight">{{ dashboardStats.agent_stats.total }}</div>
                                            <div class="flex items-center gap-3 text-xs pt-1">
                                                <span class="flex items-center text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-ping"></span>
                                                    {{ dashboardStats.agent_stats.active }} Aktif
                                                </span>
                                                <span class="flex items-center text-gray-500 font-bold bg-gray-50 px-2 py-0.5 rounded border border-gray-150">
                                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span>
                                                    {{ dashboardStats.agent_stats.inactive }} Non-Aktif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="p-4 bg-teal-50 rounded-xl text-teal-600">
                                            <i class="fas fa-users-cog text-2xl"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Super Admin Unit Breakdown -->
                                    <div v-if="dashboardStats.unit_details && dashboardStats.unit_details.length > 0" class="mt-4 pt-3 border-t border-gray-100 space-y-2 relative z-10 w-full">
                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1">Rincian Per Unit:</div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div v-for="ud in dashboardStats.unit_details" :key="ud.unit_name" 
                                                 @click="toggleUnitDetails(ud.unit_name)"
                                                 class="bg-gray-50/50 p-2 rounded-lg border border-gray-100 hover:bg-teal-50/30 transition cursor-pointer relative select-none">
                                                <div class="flex justify-between items-center">
                                                    <div class="font-extrabold text-teal-800 text-[10px] uppercase truncate" :title="ud.unit_name">{{ ud.unit_name }}</div>
                                                    <i class="fas text-[9px] text-teal-600 transition-transform duration-250" 
                                                       :class="expandedUnit === ud.unit_name ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                                                </div>
                                                <div class="flex items-center gap-1 mt-1 text-[9px] font-bold text-gray-500">
                                                    <span class="text-emerald-600">{{ ud.agent_stats.active }} Aktif</span>
                                                    <span class="text-gray-300">|</span>
                                                    <span>{{ ud.agent_stats.inactive }} Non-Aktif</span>
                                                </div>
                                                
                                                <!-- Expanded details showing Admin and Agent count -->
                                                <div v-if="expandedUnit === ud.unit_name" 
                                                     class="mt-2 pt-2 border-t border-teal-100 text-[9px] space-y-1.5 text-gray-600 animate-fadeIn">
                                                    <!-- Admin Unit -->
                                                    <div v-if="ud.unit_name !== 'Super Admin'" class="flex justify-between items-center">
                                                        <span><i class="fas fa-user-shield text-teal-600 mr-1"></i> Admin Unit:</span>
                                                        <span class="font-bold flex items-center gap-1">
                                                            <span class="text-emerald-600">{{ ud.agent_stats.admin_active ?? 0 }} Aktif</span>
                                                            <span class="text-gray-300">|</span>
                                                            <span class="text-gray-500">{{ ud.agent_stats.admin_inactive ?? 0 }} Non-Aktif</span>
                                                        </span>
                                                    </div>
                                                    <!-- Agent -->
                                                    <div v-if="ud.unit_name !== 'Super Admin'" class="flex justify-between items-center">
                                                        <span><i class="fas fa-user text-teal-600 mr-1"></i> Agent:</span>
                                                        <span class="font-bold flex items-center gap-1">
                                                            <span class="text-emerald-600">{{ ud.agent_stats.agent_active ?? 0 }} Aktif</span>
                                                            <span class="text-gray-300">|</span>
                                                            <span class="text-gray-500">{{ ud.agent_stats.agent_inactive ?? 0 }} Non-Aktif</span>
                                                        </span>
                                                    </div>
                                                    <!-- Super Admin -->
                                                    <div v-if="ud.unit_name === 'Super Admin'" class="flex justify-between items-center">
                                                        <span><i class="fas fa-crown text-amber-500 mr-1"></i> Super Admin:</span>
                                                        <span class="font-bold flex items-center gap-1">
                                                            <span class="text-emerald-600">{{ ud.agent_stats.super_admin_active ?? 0 }} Aktif</span>
                                                            <span class="text-gray-300">|</span>
                                                            <span class="text-gray-500">{{ ud.agent_stats.super_admin_inactive ?? 0 }} Non-Aktif</span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
 
                                <!-- Card 2: News Content -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between transition-all hover:shadow-md group relative overflow-hidden">
                                    <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-blue-50 rounded-full transition-transform group-hover:scale-110 opacity-70"></div>
                                    <div class="flex items-center justify-between w-full relative z-10">
                                        <div class="space-y-2">
                                            <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total News & Promo</div>
                                            <div class="text-3xl font-black text-gray-800 tracking-tight">{{ dashboardStats.news_stats.total }}</div>
                                            <div class="flex items-center gap-3 text-xs pt-1">
                                                <span class="flex items-center text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-1.5 animate-pulse"></span>
                                                    {{ dashboardStats.news_stats.active }} Aktif
                                                </span>
                                                <span class="flex items-center text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-100">
                                                    <span class="w-1.5 h-1.5 bg-amber-400 rounded-full mr-1.5"></span>
                                                    {{ dashboardStats.news_stats.expired }} Expired
                                                </span>
                                            </div>
                                        </div>
                                        <div class="p-4 bg-blue-50 rounded-xl text-blue-600">
                                            <i class="fas fa-newspaper text-2xl"></i>
                                        </div>
                                    </div>

                                    <!-- Super Admin Unit Breakdown -->
                                    <div v-if="dashboardStats.unit_details && dashboardStats.unit_details.length > 0" class="mt-4 pt-3 border-t border-gray-100 space-y-2 relative z-10 w-full">
                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1">Rincian Per Unit:</div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div v-for="ud in dashboardStats.unit_details" :key="ud.unit_name" class="bg-gray-50/50 p-2 rounded-lg border border-gray-100 hover:bg-blue-50/30 transition">
                                                <div class="font-extrabold text-blue-800 text-[10px] uppercase truncate" :title="ud.unit_name">{{ ud.unit_name }}</div>
                                                <div class="flex items-center gap-1 mt-1 text-[9px] font-bold text-gray-500">
                                                    <span class="text-blue-600">{{ ud.news_stats.active }} Aktif</span>
                                                    <span class="text-gray-300">|</span>
                                                    <span class="text-amber-600">{{ ud.news_stats.expired }} Expired</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: Rata-Rata Login Daily -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex items-center justify-between transition-all hover:shadow-md group relative overflow-hidden">
                                    <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-indigo-50 rounded-full transition-transform group-hover:scale-110 opacity-70"></div>
                                    <div class="space-y-2 relative z-10">
                                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">Rata-Rata Login Daily</div>
                                        <div class="text-3xl font-black text-gray-800 tracking-tight">{{ dashboardStats.login_stats.average }}</div>
                                        <div class="text-xs text-gray-500 font-semibold pt-1 flex items-center gap-1">
                                            <i class="fas fa-chart-line text-indigo-500"></i>
                                            Total {{ dashboardStats.login_stats.total_30_days }} login (30 hari terakhir)
                                        </div>
                                    </div>
                                    <div class="p-4 bg-indigo-50 rounded-xl text-indigo-600 relative z-10">
                                        <i class="fas fa-sign-in-alt text-2xl"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Statistik Keaktifan Login User -->
                            <div v-if="dashboardStats && dashboardStats.activity_stats" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mt-6">
                                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center border-b border-gray-150 pb-4 mb-4 gap-4">
                                    <div>
                                        <h3 class="text-sm font-extrabold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-users-cog text-teal-600"></i> Statistik Keaktifan Login User
                                        </h3>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Mencatat aktivitas dan frekuensi interaksi user di Portal</p>
                                    </div>
                                    
                                    <!-- Tab Buttons -->
                                    <div class="flex flex-wrap bg-gray-100 p-1 rounded-xl gap-1 text-xs font-bold w-full lg:w-auto">
                                        <button @click="activeLoginTab = 'frequent'" 
                                                :class="activeLoginTab === 'frequent' ? 'bg-white text-teal-600 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                                                class="flex-1 lg:flex-none px-3 py-1.5 rounded-lg transition-all select-none text-center">
                                            🔥 Sering Login
                                        </button>
                                        <button @click="activeLoginTab = 'rare'" 
                                                :class="activeLoginTab === 'rare' ? 'bg-white text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                                                class="flex-1 lg:flex-none px-3 py-1.5 rounded-lg transition-all select-none text-center">
                                            ⏳ Jarang Login
                                        </button>
                                        <button @click="activeLoginTab = 'never'" 
                                                :class="activeLoginTab === 'never' ? 'bg-white text-rose-600 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                                                class="flex-1 lg:flex-none px-3 py-1.5 rounded-lg transition-all select-none text-center">
                                            🚫 Tidak Pernah Login
                                        </button>
                                    </div>
                                </div>

                                <!-- Live Search and Items Per Page Row -->
                                <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-4 mt-2">
                                    <!-- Search Input -->
                                    <div class="relative flex-1 max-w-md">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-search text-gray-400 text-[11px]"></i>
                                        </div>
                                        <input v-model="activitySearchQuery" 
                                               type="text" 
                                               placeholder="Cari NPP, Nama, atau Unit..." 
                                               class="block w-full pl-9 pr-8 py-2 border border-gray-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all placeholder:text-gray-400 text-gray-700" />
                                        <button v-if="activitySearchQuery" 
                                                @click="activitySearchQuery = ''" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition">
                                            <i class="fas fa-times-circle text-xs"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Items Per Page Selection -->
                                    <div class="flex items-center justify-between md:justify-end gap-2 text-xs font-bold text-gray-500">
                                        <span>Tampilkan:</span>
                                        <select v-model="activityPerPage" 
                                                class="bg-gray-50 border border-gray-250 rounded-xl px-3 py-1.5 text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition cursor-pointer select-none">
                                            <option :value="10">10 data</option>
                                            <option :value="25">25 data</option>
                                            <option :value="50">50 data</option>
                                            <option :value="100">100 data</option>
                                            <option :value="500">500 data</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dynamic Tab Content with Unified Premium Table -->
                                <div class="animate-fadeIn">
                                    <div class="overflow-x-auto rounded-xl border border-gray-150">
                                        <table class="w-full text-[11px] text-left text-gray-600">
                                            <thead :class="{
                                                'bg-teal-50/50 text-teal-800 border-teal-100': activeLoginTab === 'frequent',
                                                'bg-amber-50/50 text-amber-800 border-amber-100': activeLoginTab === 'rare',
                                                'bg-rose-50/50 text-rose-800 border-rose-100': activeLoginTab === 'never'
                                            }" class="font-extrabold uppercase text-[9px] tracking-wide border-b">
                                                <tr>
                                                    <th class="p-3">NPP</th>
                                                    <th class="p-3">Employee Name</th>
                                                    <th class="p-3">Unit Name (Unit ID)</th>
                                                    <th class="p-3 text-center">Total Login</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 font-medium">
                                                <tr v-for="user in filteredAndPaginatedActivity.data" :key="user.npp" class="hover:bg-gray-50/50 transition">
                                                    <td class="p-3 font-bold text-gray-800">{{ user.npp }}</td>
                                                    <td class="p-3">{{ user.name }}</td>
                                                    <td class="p-3">
                                                        <span :class="{
                                                            'bg-teal-50 text-teal-700': activeLoginTab === 'frequent',
                                                            'bg-amber-50 text-amber-700': activeLoginTab === 'rare',
                                                            'bg-rose-50 text-rose-700': activeLoginTab === 'never'
                                                        }" class="px-2 py-0.5 rounded-full font-bold text-[9px]">
                                                            {{ user.unit }}
                                                        </span>
                                                    </td>
                                                    <td class="p-3 text-center">
                                                        <span v-if="activeLoginTab === 'never'" class="bg-rose-50 text-rose-600 px-2.5 py-0.5 rounded border border-rose-100 font-bold text-[10px]">
                                                            Belum Pernah
                                                        </span>
                                                        <span v-else :class="{
                                                            'text-teal-600': activeLoginTab === 'frequent',
                                                            'text-amber-600': activeLoginTab === 'rare'
                                                        }" class="font-black text-xs">
                                                            {{ user.count }}x
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr v-if="filteredAndPaginatedActivity.data.length === 0">
                                                    <td colspan="4" class="p-8 text-center text-gray-400 italic font-semibold">
                                                        <i class="fas fa-info-circle mr-1"></i> Tidak ada data ditemukan
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls Footer -->
                                    <div class="flex flex-col sm:flex-row justify-between items-center mt-4 pt-4 border-t border-gray-100 gap-3 text-xs font-bold text-gray-500">
                                        <div>
                                            Menampilkan <span class="text-gray-800">{{ filteredAndPaginatedActivity.data.length }}</span> dari <span class="text-gray-800">{{ filteredAndPaginatedActivity.total }}</span> data
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <!-- Prev Button -->
                                            <button @click="activityCurrentPage--" 
                                                    :disabled="activityCurrentPage <= 1"
                                                    :class="activityCurrentPage <= 1 ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-50 text-gray-700 active:scale-95 border border-gray-200 shadow-sm'"
                                                    class="px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 select-none">
                                                <i class="fas fa-chevron-left text-[9px]"></i> Prev
                                            </button>
                                            
                                            <!-- Page Info Badge -->
                                            <span class="bg-gray-100 text-gray-850 px-3 py-1.5 rounded-lg border border-gray-200/50 font-black">
                                                {{ activityCurrentPage }} / {{ filteredAndPaginatedActivity.totalPages }}
                                            </span>
                                            
                                            <!-- Next Button -->
                                            <button @click="activityCurrentPage++" 
                                                    :disabled="activityCurrentPage >= filteredAndPaginatedActivity.totalPages"
                                                    :class="activityCurrentPage >= filteredAndPaginatedActivity.totalPages ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-50 text-gray-700 active:scale-95 border border-gray-200 shadow-sm'"
                                                    class="px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 select-none">
                                                Next <i class="fas fa-chevron-right text-[9px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Charts Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                
                                <!-- Chart 1: Daily Logins Trend Line Chart -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-7 flex flex-col h-[380px] relative">
                                    <div class="flex justify-between items-center mb-4">
                                        <div>
                                            <h3 class="text-sm font-extrabold text-gray-800 flex items-center gap-2">
                                                <i class="fas fa-history text-teal-600"></i> Tren Aktivitas Login Harian (30 Hari)
                                            </h3>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Menampilkan fluktuasi login setiap hari</p>
                                        </div>
                                        <!-- Active Tooltip Box inline in header to be clear -->
                                        <div v-if="hoveredPoint" class="bg-teal-50 border border-teal-200 text-teal-900 rounded-lg px-2.5 py-1 text-xs font-bold flex items-center gap-1.5 animate-fade-in shadow-sm">
                                            <i class="fas fa-calendar-day text-teal-500"></i> {{ hoveredPoint.label }}:
                                            <span class="text-teal-700">{{ hoveredPoint.count }} login</span>
                                        </div>
                                        <div v-else class="text-xs text-gray-400 font-medium italic">Sorot titik untuk detail</div>
                                    </div>

                                    <!-- SVG Rendered Line/Area Chart -->
                                    <div class="flex-1 w-full overflow-hidden relative select-none">
                                        <svg :viewBox="`0 0 ${loginChartPoints.width} ${loginChartPoints.height}`" class="w-full h-full">
                                            <defs>
                                                <!-- Gradient Under Line -->
                                                <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#0d9488" stop-opacity="0.35"/>
                                                    <stop offset="100%" stop-color="#0d9488" stop-opacity="0.00"/>
                                                </linearGradient>
                                                <!-- Stroke Gradient -->
                                                <linearGradient id="lineGrad" x1="0" y1="0" x2="1" y2="0">
                                                    <stop offset="0%" stop-color="#0d9488"/>
                                                    <stop offset="100%" stop-color="#3b82f6"/>
                                                </linearGradient>
                                                <!-- Drop shadow for the main path line -->
                                                <filter id="shadow" x="-5%" y="-5%" width="110%" height="110%">
                                                    <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#0d9488" flood-opacity="0.15" />
                                                </filter>
                                            </defs>

                                            <!-- Grid Horizontal Lines -->
                                            <line v-for="g in 4" :key="'grid-'+g"
                                                  x1="40" :y1="15 + (g-1) * 47"
                                                  :x2="loginChartPoints.width - 20" :y2="15 + (g-1) * 47"
                                                  stroke="#f1f5f9" stroke-width="1.5" />

                                            <!-- Y Axis Grid Labels -->
                                            <text v-for="g in 4" :key="'lbl-'+g"
                                                  x="30" :y="19 + (g-1) * 47"
                                                  text-anchor="end" fill="#94a3b8" class="text-[9px] font-bold font-mono">
                                                {{ Math.round((loginChartPoints.maxCount * (4-g) / 3)) }}
                                            </text>

                                            <!-- Smooth Area Path Fill -->
                                            <path :d="loginChartPoints.area" fill="url(#areaGrad)" />

                                            <!-- Main Line Path -->
                                            <path :d="loginChartPoints.path" fill="none" stroke="url(#lineGrad)" stroke-width="3" stroke-linecap="round" filter="url(#shadow)" />

                                            <!-- X Axis Labels (every 5th point to prevent overlap) -->
                                            <text v-for="(pt, idx) in loginChartPoints.dots" :key="'xl-'+idx"
                                                  v-show="idx % 5 === 0 || idx === 29"
                                                  :x="pt.x" :y="loginChartPoints.height - 8"
                                                  text-anchor="middle" fill="#94a3b8" class="text-[9px] font-bold">
                                                {{ pt.label }}
                                            </text>

                                            <!-- Active Hover vertical line indicator -->
                                            <line v-if="hoveredPoint"
                                                  :x1="hoveredPoint.x" :y1="15"
                                                  :x2="hoveredPoint.x" :y2="loginChartPoints.height - 25"
                                                  stroke="#0d9488" stroke-width="1.5" stroke-dasharray="3,3" />

                                            <!-- Data point dots (Glowing circles on hover) -->
                                            <circle v-for="(pt, idx) in loginChartPoints.dots" :key="'d-'+idx"
                                                    :cx="pt.x" :cy="pt.y"
                                                    :r="hoveredPoint && hoveredPoint.date === pt.date ? 6 : 3"
                                                    :fill="hoveredPoint && hoveredPoint.date === pt.date ? '#ffffff' : '#0d9488'"
                                                    :stroke="hoveredPoint && hoveredPoint.date === pt.date ? '#0d9488' : '#ffffff'"
                                                    stroke-width="2.5" class="transition-all duration-150" />

                                            <!-- Transparent wider hover areas over each point for interactive mouse-over -->
                                            <rect v-for="(pt, idx) in loginChartPoints.dots" :key="'ha-'+idx"
                                                  :x="pt.x - 9" :y="10"
                                                  width="18" :height="loginChartPoints.height - 35"
                                                  class="fill-transparent cursor-pointer"
                                                  @mouseover="hoveredPoint = pt"
                                                  @mouseleave="hoveredPoint = null" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Chart 2: Menu clicks stats (Progress bars Menu 1 - Menu 15) -->
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-5 flex flex-col h-[380px]">
                                    <div class="mb-4">
                                        <h3 class="text-sm font-extrabold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-mouse-pointer text-teal-600"></i> Statistik Akses Menu 1 - 15
                                        </h3>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Peringkat menu yang paling sering diakses agent</p>
                                    </div>

                                    <!-- Scrollable Two-Column List of Progress Bars -->
                                    <div class="flex-1 overflow-y-auto pr-1 custom-scrollbar space-y-3.5">
                                        <div v-for="menu in dashboardStats.menu_stats" :key="menu.menu" 
                                             class="group/bar flex items-center justify-between gap-4 p-1.5 rounded-lg hover:bg-teal-50/20 transition-all duration-200">
                                            
                                            <!-- Menu label & details -->
                                            <div class="w-20 shrink-0 text-xs font-bold text-gray-700">
                                                {{ menu.menu }}
                                            </div>

                                            <!-- Progress bar and actual click count -->
                                            <div class="flex-1 space-y-1">
                                                <div class="flex justify-between items-center text-[10px]">
                                                    <span class="text-gray-400 font-bold truncate max-w-[120px]" :title="menu.menu_title || menu.menu">
                                                        {{ menu.menu_title || 'Shortcut Menu' }}
                                                    </span>
                                                    <span class="text-teal-700 font-extrabold bg-teal-50 px-2 py-0.5 rounded-full border border-teal-100 group-hover/bar:bg-teal-600 group-hover/bar:text-white transition-colors duration-200">
                                                        {{ menu.clicks }} klik
                                                    </span>
                                                </div>
                                                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden relative">
                                                    <div class="h-full bg-gradient-to-r from-teal-500 to-blue-500 rounded-full transition-all duration-1000 ease-out"
                                                         :style="`width: ${(menu.clicks / maxMenuClicks) * 100}%`" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    dashboardStats: {
        type: Object,
        default: null
    }
});

const hoveredPoint = ref(null);
const expandedUnit = ref(null);
const activeLoginTab = ref('frequent');
const activitySearchQuery = ref('');
const activityPerPage = ref(10);
const activityCurrentPage = ref(1);

watch(activeLoginTab, () => {
    activityCurrentPage.value = 1;
    activitySearchQuery.value = '';
});

watch([activityPerPage, activitySearchQuery], () => {
    activityCurrentPage.value = 1;
});

const toggleUnitDetails = (unitName) => {
    if (expandedUnit.value === unitName) {
        expandedUnit.value = null;
    } else {
        expandedUnit.value = unitName;
    }
};

const filteredAndPaginatedActivity = computed(() => {
    if (!props.dashboardStats || !props.dashboardStats.activity_stats) {
        return {
            data: [],
            total: 0,
            totalPages: 1,
        };
    }
    
    let rawList = [];
    if (activeLoginTab.value === 'frequent') {
        rawList = props.dashboardStats.activity_stats.frequent || [];
    } else if (activeLoginTab.value === 'rare') {
        rawList = props.dashboardStats.activity_stats.rare || [];
    } else if (activeLoginTab.value === 'never') {
        rawList = props.dashboardStats.activity_stats.never || [];
    }
    
    const query = activitySearchQuery.value.trim().toLowerCase();
    if (query !== '') {
        rawList = rawList.filter(user => {
            const npp = (user.npp || '').toString().toLowerCase();
            const name = (user.name || '').toLowerCase();
            const unit = (user.unit || '').toLowerCase();
            return npp.includes(query) || name.includes(query) || unit.includes(query);
        });
    }
    
    const total = rawList.length;
    const perPage = parseInt(activityPerPage.value) || 10;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    
    if (activityCurrentPage.value > totalPages) {
        activityCurrentPage.value = totalPages;
    }
    if (activityCurrentPage.value < 1) {
        activityCurrentPage.value = 1;
    }
    
    const start = (activityCurrentPage.value - 1) * perPage;
    const paginatedData = rawList.slice(start, start + perPage);
    
    return {
        data: paginatedData,
        total,
        totalPages,
    };
});

const loginChartPoints = computed(() => {
    if (!props.dashboardStats || !props.dashboardStats.login_stats || !props.dashboardStats.login_stats.chart) {
        return { path: '', area: '', dots: [], maxCount: 5, padding: { left: 40, right: 20, top: 15, bottom: 25 }, width: 600, height: 180 };
    }
    const chartData = props.dashboardStats.login_stats.chart;
    const width = 600;
    const height = 180;
    const padding = { left: 40, right: 20, top: 15, bottom: 25 };

    const maxCount = Math.max(...chartData.map(d => d.count), 5);
    
    const xScale = (width - padding.left - padding.right) / 29;
    const yScale = (height - padding.top - padding.bottom) / maxCount;

    const points = chartData.map((d, i) => {
        const x = padding.left + i * xScale;
        const y = height - padding.bottom - d.count * yScale;
        return { x, y, count: d.count, label: d.label, date: d.date };
    });

    let path = '';
    if (points.length > 0) {
        path = `M ${points[0].x} ${points[0].y}`;
        for (let i = 1; i < points.length; i++) {
            path += ` L ${points[i].x} ${points[i].y}`;
        }
    }

    let area = '';
    if (points.length > 0) {
        area = `${path} L ${points[points.length - 1].x} ${height - padding.bottom} L ${points[0].x} ${height - padding.bottom} Z`;
    }

    return { path, area, dots: points, maxCount, padding, width, height };
});

const maxMenuClicks = computed(() => {
    if (!props.dashboardStats || !props.dashboardStats.menu_stats) return 1;
    return Math.max(...props.dashboardStats.menu_stats.map(m => m.clicks), 1);
});
</script>
