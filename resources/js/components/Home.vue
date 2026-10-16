<script setup>
import { ref, shallowRef, watch, onMounted, onUnmounted, computed, nextTick } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import ExportImport from './ExportImport.vue';
import AgentHeroCarousel from './dashboard/AgentHeroCarousel.vue';
import QuickMenuGrid from './dashboard/QuickMenuGrid.vue';
import AnalyticsCharts from './analytics/AnalyticsCharts.vue';
import NewsDetailModal from './modals/NewsDetailModal.vue';
import GlobalOmnisearchModal from './modals/GlobalOmnisearchModal.vue';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import { setupOfficePasteHandler } from '../utils/officePasteNormalizer';
import CustomFormatPainter from '../utils/ckeditorFormatPainter';
import WordMultilevelList from '../utils/ckeditorMultilevelList';
import {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Subscript,
    Superscript,
    Link,
    List,
    ListProperties,
    TodoList,
    Paragraph,
    Heading,
    Alignment,
    Table,
    TableToolbar,
    TableProperties,
    TableCellProperties,
    TableCaption,
    TableColumnResize,
    BlockQuote,
    Image,
    ImageToolbar,
    ImageCaption,
    ImageStyle,
    ImageResize,
    ImageUpload,
    LinkImage,
    Indent,
    IndentBlock,
    FontColor,
    FontBackgroundColor,
    FontSize,
    FontFamily,
    Highlight,
    HorizontalLine,
    MediaEmbed,
    Code,
    CodeBlock,
    FindAndReplace,
    RemoveFormat,
    SpecialCharacters,
    SpecialCharactersEssentials,
    Undo,
    ImageInsert,
    Autoformat,
    SelectAll,
    Base64UploadAdapter,
    GeneralHtmlSupport,
    HtmlEmbed,
    PageBreak,
    SourceEditing,
    ShowBlocks,
    WordCount
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

// --- Global Download Interceptor ---
// Intercepts any click on an <a> tag with a 'download' attribute or inside attachment table action column
document.addEventListener('click', async (e) => {
    const a = e.target.closest('a');
    if (!a) return;

    const isInsideAttachmentTable = a.closest('table.cxc-attachment-table') !== null;
    
    if (a.hasAttribute('download') || isInsideAttachmentTable) {
        let href = a.getAttribute('href');
        // If it's a local storage link or a blob URL, force it to download
        if (href && (href.includes('/storage/') || href.startsWith('blob:'))) {
            e.preventDefault();
            e.stopPropagation();
            
            if (href.startsWith('blob:')) {
                // For blob URLs, we have to use the JS approach
                const tempA = document.createElement('a');
                tempA.href = href;
                tempA.download = a.getAttribute('download') || 'download';
                document.body.appendChild(tempA);
                tempA.click();
                document.body.removeChild(tempA);
                return;
            }

            // Extract the path after /storage/
            let storagePath = href;
            const storageIndex = href.indexOf('/storage/');
            if (storageIndex !== -1) {
                storagePath = href.substring(storageIndex);
            }
            
            // Redirect to backend download endpoint which sends Content-Disposition: attachment
            const downloadUrl = `/api/news/download-attachment?path=${encodeURIComponent(storagePath)}`;
            window.location.href = downloadUrl;
        }
    }
}, true);

// --- Unit Modal State ---
const unitsData = shallowRef([]);
const selectedUnits = ref([]);
const isLoadingUnits = ref(false);
const unitPagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
const showUnitFilters = ref({ type: false, parent: false, code: false, name: false, shortName: false, address: false, status: false });
const unitFilters = ref({ type: '', parent: '', code: '', name: '', shortName: '', address: '', status: '' });
const isUnitModalOpen = ref(false);
const unitModalMode = ref('add');
const formUnit = ref({ id: null, type: 'DIVISI', parentId: '', code: '', name: '', shortName: '', address: '', isActive: 'Yes' });
// Dummy parent options for dropdown
const parentOptions = ref([{id: 1, name: 'Bank'}, {id: 2, name: 'Credit'}, {id: 3, name: 'Emerald'}]); 

const router = useRouter();
const isAppLoading = ref(true);


const currentUser = ref(null);
const selectedRoleObj = ref(null);

// --- Agent News Carousel Logic (declared early to avoid TDZ) ---
const agentNews = shallowRef([]); // For List Modal / Popups
const agentNewsPagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
const carouselNews = ref([]); // For Slider ONLY
const currentNewsIndex = ref(0); // For Slider Component Index
const selectedPortalCategory = ref('');
let newsCarouselInterval = null;
const searchQuery = ref('');

// --- Portal Settings (declared early to avoid TDZ) ---
const CACHE_KEY = 'portalSettingsCache';
const portalSettings = ref([]);
const getImagePath = (path) => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) return path;
    const clean = path.startsWith('/') ? path.slice(1) : path;
    return `./${clean}`;
};

const backgroundUrl = ref(getImagePath('images/halaman-user.png'));

// --- Menu News Banner (declared early to avoid TDZ) ---
const menuNewsImages = ref({});
const menuNewsCarouselIndices = ref({});
const menuNewsIntervals = {};

// --- Dashboard Statistics Setup ---
const dashboardStats = ref(null);
const isDashboardLoading = ref(false);
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

const filteredAndPaginatedActivity = computed(() => {
    if (!dashboardStats.value || !dashboardStats.value.activity_stats) {
        return {
            data: [],
            total: 0,
            totalPages: 1,
        };
    }
    
    let rawList = [];
    if (activeLoginTab.value === 'frequent') {
        rawList = dashboardStats.value.activity_stats.frequent || [];
    } else if (activeLoginTab.value === 'rare') {
        rawList = dashboardStats.value.activity_stats.rare || [];
    } else if (activeLoginTab.value === 'never') {
        rawList = dashboardStats.value.activity_stats.never || [];
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

const toggleUnitDetails = (unitName) => {
    if (expandedUnit.value === unitName) {
        expandedUnit.value = null;
    } else {
        expandedUnit.value = unitName;
    }
};

const fetchDashboardStats = async (unitName) => {
    if (!unitName) return;
    isDashboardLoading.value = true;
    try {
        const response = await axios.get('/api/dashboard/stats', {
            params: { unit_name: unitName }
        });
        dashboardStats.value = response.data;
    } catch (error) {
        console.error("Failed to fetch dashboard stats", error);
    } finally {
        isDashboardLoading.value = false;
    }
};

const handleSwitchRole = () => {
    selectedRoleObj.value = null;
    selectedRole.value = null;
    localStorage.removeItem('selectedRole');
    dashboardStats.value = null;
};

const loginChartPoints = computed(() => {
    if (!dashboardStats.value || !dashboardStats.value.login_stats || !dashboardStats.value.login_stats.chart) {
        return { path: '', area: '', dots: [], maxCount: 5, padding: { left: 40, right: 20, top: 15, bottom: 25 }, width: 600, height: 180 };
    }
    const chartData = dashboardStats.value.login_stats.chart;
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
    if (!dashboardStats.value || !dashboardStats.value.menu_stats) return 1;
    return Math.max(...dashboardStats.value.menu_stats.map(m => m.clicks), 1);
});

const savedRole = localStorage.getItem('selectedRole');
if (savedRole) {
    try {
        const parsed = JSON.parse(savedRole);
        // Only restore if it's NOT a preview role
        if (parsed && !parsed.isPreview && !String(parsed.id).startsWith('preview-')) {
            selectedRoleObj.value = parsed;
        } else {
            localStorage.removeItem('selectedRole');
        }
    } catch (e) {
        localStorage.removeItem('selectedRole');
    }
}
const currentLayout = ref(null);

// Reactive layout detection
watch(selectedRoleObj, (newRole) => {
    console.log('DEBUG: selectedRoleObj changed:', newRole);
    if (newRole) {
        const roleName = String(newRole.role || '').toLowerCase();
        if (['agent', 'credit', 'banking', 'emerald', 'merchant'].includes(roleName)) {
            currentLayout.value = 'agent';
        } else {
            currentLayout.value = 'admin';
            if (newRole.unit) {
                fetchDashboardStats(newRole.unit);
            }
        }
    } else if (currentUser.value) {
        const roles = [...(currentUser.value.roles || []), ...(currentUser.value.employee?.roles || [])];
        const username = String(currentUser.value.username || '').toLowerCase();
        const employeeName = String(currentUser.value.employee?.name || '').toLowerCase();
        
        const isAdmin = roles.some(r => {
            const name = (typeof r === 'string' ? r : (r.role?.name || r.name || r.rolename || '')).toLowerCase();
            return name.includes('admin');
        }) || username.includes('admin') || employeeName.includes('admin');
        
        if (isAdmin) currentLayout.value = 'admin';
    }
    console.log('DEBUG: currentLayout is now:', currentLayout.value);
}, { immediate: true });

// Also watch currentUser for initial layout determination
watch(currentUser, (user) => {
    if (user && !selectedRoleObj.value) {
        const roles = [...(user.roles || []), ...(user.employee?.roles || [])];
        const isAdmin = roles.some(r => {
            const name = (typeof r === 'string' ? r : (r.role?.name || r.name || '')).toLowerCase();
            return name.includes('admin') || name.includes('super admin');
        });
        if (isAdmin) {
            currentLayout.value = 'admin';
        }
    }
});

const isAdminWithUnit = computed(() => {
    const role = selectedRoleObj.value?.role?.toLowerCase() || '';
    const unit = selectedRoleObj.value?.unit?.toLowerCase() || '';
    if (role.includes('super admin')) return false; // Super Admin is not restricted
    return role.includes('admin') && unit !== '' && unit !== 'super admin';
});

const isSuperAdmin = computed(() => {
    const role = selectedRoleObj.value?.role?.toLowerCase() || '';
    const unit = selectedRoleObj.value?.unit?.toLowerCase() || '';
    return unit === 'super admin' || role.includes('super admin');
});

// Check for preview mode helper
const checkPreviewMode = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const previewUnit = urlParams.get('preview');

    if (previewUnit) {
        // Try to find the actual correctly-cased unit name from parentOptions
        const matchedUnit = parentOptions.value.find(u => u.name.toLowerCase() === previewUnit.toLowerCase());
        const actualUnitName = matchedUnit ? matchedUnit.name : (previewUnit.charAt(0).toUpperCase() + previewUnit.slice(1));

        // Mock the role object for preview
        selectedRoleObj.value = {
            id: 'preview-' + previewUnit,
            role: 'Agent',
            unit: actualUnitName, 
            label: `VIEW ${actualUnitName.toUpperCase()}`,
            isPreview: true // Flag to prevent localStorage contamination
        };
        currentLayout.value = 'agent';
        
        // MOCK USER for template rendering (Bypass Login Screen)
        if (!currentUser.value) {
            currentUser.value = {
                id: 0,
                name: 'Guest Preview',
                employee: {
                    name: 'Guest Preview',
                    npp: 'PREVIEW',
                    roles: []
                }
            };
        }

        // Ensure Carousel/Banner/News are fetched for preview mode
        if (typeof fetchAgentNews === 'function') {
             fetchAgentNews(); 
        }
        if (typeof fetchCarouselNews === 'function') {
             fetchCarouselNews();
        }

        return true;
    }
    return false;
};

const fetchCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;
        console.log('DEBUG: Current User Data:', currentUser.value);
        
        // --- Role & Layout Sync ---
        // (Concurrent login detection handled server-side)

        // Aggressive layout detection for Admin/Super Admin
        const roles = [...(currentUser.value.roles || []), ...(currentUser.value.employee?.roles || [])];
        const username = String(currentUser.value.username || '').toLowerCase();
        const employeeName = String(currentUser.value.employee?.name || '').toLowerCase();
        
        const hasAdminRole = roles.some(r => {
            const name = (typeof r === 'string' ? r : (r.role?.name || r.name || r.rolename || '')).toLowerCase();
            return name.includes('admin');
        }) || username.includes('admin') || employeeName.includes('admin');

        if (hasAdminRole && !currentLayout.value) {
            console.log('DEBUG: Admin status detected via roles/username/name, forcing admin layout');
            currentLayout.value = 'admin';
        }

        await nextTick();
    } catch (e) {
        console.error('Failed to fetch current user', e);
        if (e.response?.status === 401 && !checkPreviewMode()) {
            router.push('/');
        }
    }
};



// Constant for Knowledge Menu Items to allow filtering
const getUnitIcon = (unitName) => {
    const name = unitName.toLowerCase();
    if (name.includes('banking')) return 'book';
    if (name.includes('credit')) return 'credit-card';
    if (name.includes('emerald')) return 'gem';
    if (name.includes('merchant')) return 'store';
    if (name.includes('sat')) return 'satellite';
    return 'book'; // Default icon
};

const allKnowledgeItems = computed(() => {
    return parentOptions.value.map(unit => ({
        name: `Knowledge ${unit.name}`,
        icon: getUnitIcon(unit.name)
    })).sort((a, b) => a.name.localeCompare(b.name));
});

const dynamicTargetUnits = computed(() => {
    const units = parentOptions.value.map(u => u.name).sort((a, b) => a.localeCompare(b));
    return ['All Layanan', ...units];
});

const isTargetUnitDisabled = (opt) => {
    if (!isAdminWithUnit.value) return false;
    const userUnitName = selectedRoleObj.value?.unit?.toLowerCase() || '';
    if (opt === 'All Layanan') return true;
    return opt.toLowerCase() !== userUnitName;
};

const menus = ref([
    { 
        name: 'Home', 
        icon: 'home', 
        isOpen: false,
        children: [] // Will be populated based on roles
    },
    { 
        name: 'Portal', 
        icon: 'globe', 
        isOpen: false,
        children: [
            { name: 'Portal Interface Agent Unit', icon: 'desktop' },
        ]
    },

    { 
        name: 'News', 
        icon: 'newspaper', 
        isOpen: false,
        children: [
            { name: 'Manage News', icon: 'newspaper' },
        ]
    },
    { 
        name: 'My Profile', 
        icon: 'user', 
        isOpen: false,
        active: false, // For parent highlighting if needed
        children: [
            { name: 'My Role', icon: 'id-badge' },
            { name: 'My Profile', icon: 'user-circle' },
        ]
    },
    { 
        name: 'Human Resource', 
        icon: 'users',
        isOpen: false,
        children: [
            { name: 'Manage User', icon: 'user-cog' },
            { name: 'Manage Unit', icon: 'sitemap' },
            { name: 'Activity Log', icon: 'history' },
        ]
    },
    { 
        name: 'Application Tools', 
        icon: 'cog', 
        isOpen: false,
        active: false,
        children: [
            { name: 'Export dan Import', icon: 'file-import' }
        ]
    },
]);

const systemDate = computed(() => {
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date().toLocaleDateString('en-GB', options);
});

const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};

const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        // Format to: 13 May 2026 16:20:00
        const options = { 
            day: '2-digit', 
            month: 'short', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: false 
        };
        return date.toLocaleString('en-GB', options).replace(',', '');
    } catch (e) {
        return dateStr;
    }
};

const toggleMenu = (selectedMenu) => {
    // Find the original menu object to ensure reactivity and state persistence
    const originalMenu = menus.value.find(m => m.name === selectedMenu.name);
    if (!originalMenu) return;

    // Accordion Logic: If about to open, close all others
    if (!originalMenu.isOpen) {
        menus.value.forEach(menu => {
            menu.isOpen = false;
            menu.active = false; // Optional: depending on UX, but current code does this
        });
        
        originalMenu.isOpen = true;
        originalMenu.active = true;
        activeMenuName.value = originalMenu.name;
        activeSubmenuName.value = ''; // Reset submenu when switching main menu
    } else {
        // If clicking an already open menu, just toggle it closed
        originalMenu.isOpen = false;
        originalMenu.active = false;
        activeMenuName.value = '';
        activeSubmenuName.value = '';
    }
};

const handleMiddleClick = (event, menuName, childName = null) => {
    if (event.button === 1) { // Middle click
        event.preventDefault();

        // Special Handling for Knowledge Preview (Home -> Knowledge [Unit])
        if (menuName === 'Home' && childName && childName.startsWith('Knowledge ')) {
            const unitName = childName.replace('Knowledge ', '').trim();
            window.open(`${window.location.origin}/home?preview=${encodeURIComponent(unitName.toLowerCase())}`, '_blank');
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('menu', menuName);
        if (childName) {
            url.searchParams.set('submenu', childName);
        } else {
            url.searchParams.delete('submenu');
        }
        window.open(url.toString(), '_blank');
    }
};

const handleMiddleClickPortal = (event, item) => {
    if (event.button === 1 && item) { // Middle click
        event.preventDefault();
        
        // 1. If it's a real external URL, open that URL in a new tab
        const urlStr = (item.url || '').trim();
        if (urlStr && urlStr !== '#' && (urlStr.startsWith('http') || urlStr.startsWith('www') || urlStr.startsWith('/') || (urlStr.includes('.') && !urlStr.includes(' ')) )) {
            let finalUrl = urlStr;
            if (urlStr.startsWith('www')) finalUrl = 'http://' + urlStr;
            window.open(finalUrl, '_blank');
            return;
        }

        // 2. Otherwise, open the current application with the portalCategory param
        const url = new URL(window.location.href);
        url.searchParams.set('portalCategory', item.title);
        url.searchParams.delete('viewNewsId'); // Clear news view if opening category
        window.open(url.toString(), '_blank');
    }
};

const handleMiddleClickNews = (event, item) => {
    if (event.button === 1 && item) { // Middle click
        event.preventDefault();
        const url = new URL(window.location.href);
        url.searchParams.set('viewNewsId', item.id);
        window.open(url.toString(), '_blank');
    }
};

const handleMiddleClickBanner = (event) => {
    if (event.button === 1 && carouselNews.value.length > 0) {
        event.preventDefault();
        const item = carouselNews.value[currentNewsIndex.value];
        const url = new URL(window.location.href);
        url.searchParams.set('viewNewsId', item.id);
        window.open(url.toString(), '_blank');
    }
};

const handleMiddleClickPromoList = (event) => {
    if (event.button === 1) {
        event.preventDefault();
        const url = new URL(window.location.href);
        url.searchParams.set('portalCategory', 'Promo');
        window.open(url.toString(), '_blank');
    }
};

const selectSubmenu = (selectedChild) => {
    if (selectedChild.name === 'Logout') {
        logout();
        return;
    }

    // Check for Knowledge (Preview) items
    if (selectedChild.name.startsWith('Knowledge ')) {
        const unitName = selectedChild.name.replace('Knowledge ', '').trim();
        window.open(`${window.location.origin}/home?preview=${encodeURIComponent(unitName.toLowerCase())}`, '_blank');
        return;
    }

    // Reset all submenu items
    menus.value.forEach(menu => {
        if (menu.children) {
            menu.children.forEach(child => {
                child.active = false;
            });
        }
    });
    // Activate the selected child
    selectedChild.active = true;
    activeSubmenuName.value = selectedChild.name;

    if (selectedChild.name === 'My Role') {
        if (selectedRoleObj.value && selectedRoleObj.value.unit) {
            fetchDashboardStats(selectedRoleObj.value.unit);
        }
        return;
    }
    
    if (selectedChild.name === 'My Profile') {
         // Just let it activate the view in the main area
        return;
    }
    
    // Auto-fetch data based on submenu
    if (activeSubmenuName.value === 'Manage User') {
        fetchUsers();
    } else if (activeSubmenuName.value === 'Manage News') {
        initializeNewsFilters(); // Ensure filters are reset or ready
        fetchNews();
    } else if (activeSubmenuName.value.startsWith('Portal Interface')) {
        fetchPortalSettings();
    }
};



// Computed options for Role Selection
const roleOptions = computed(() => {
    let roles = currentUser.value?.employee?.roles || currentUser.value?.roles || [];
    
    // Synthesis check: If they have no roles in the database but are an admin, synthesize a Super Admin option
    if (roles.length === 0 && currentUser.value) {
        const username = String(currentUser.value.username || '').toLowerCase();
        const employeeName = String(currentUser.value.employee?.name || '').toLowerCase();
        if (username.includes('admin') || employeeName.includes('admin')) {
            return [{
                id: 0,
                role: 'Super Admin',
                unit: 'Super Admin',
                label: 'SUPER ADMIN'
            }];
        }
    }

    // Map to a usable format
    return roles.map((r, index) => {
        const roleName = typeof r === 'string' ? r : (r.role?.name || r.name || 'Unknown');
        const unitName = typeof r === 'string' ? 'Super Admin' : (r.unit?.name || 'Unknown');
        return {
            id: index, // Simple index as ID for selection
            role: roleName,
            unit: unitName,
            label: `${roleName.toUpperCase()} ${unitName !== 'Unknown' ? 'Unit ' + unitName : ''}`
        };
    });
});

const selectedRole = ref(null); // Will hold the selected ID or object

// Watch for currentUser load to set default selectedRole and auto-submit
watch([currentUser, parentOptions], ([newVal, units]) => {
    // Check for Preview Mode via Query Params
    if (checkPreviewMode()) {
        return; 
    }

    if (newVal && roleOptions.value.length > 0) {
        // Auto-select first role if none selected (e.g. initial login)
        if (!selectedRole.value && !selectedRoleObj.value) {
            selectedRole.value = roleOptions.value[0].id;
            submitRole(); // Auto-apply the role to switch layout
        }
    }
    
    // Ensure loading ends even if no roles are found (e.g. to show error or role selection)
    isAppLoading.value = false;

    
    // Filter Home/Knowledge Menus based on Unit
    if (newVal && newVal.employee && newVal.employee.roles) {
        const homeMenu = menus.value.find(m => m.name === 'Home');
        if (homeMenu) {
            const userRoles = newVal.employee.roles.map(r => r.role?.name?.toLowerCase() || '');
            const isAdmin = userRoles.some(role => role.includes('admin') || role.includes('it support') || role.includes('super user') || role.includes('super admin'));
            const isSuperAdminRole = userRoles.some(role => role.includes('super admin'));
            
            // Find if user has a role that is Admin but restricted by unit
            const restrictedAdminRole = newVal.employee.roles.find(r => {
                const rName = (r.role?.name || '').toLowerCase();
                const uName = (r.unit?.name || '').toLowerCase();
                return rName.includes('admin') && !rName.includes('super') && uName !== '' && uName !== 'super admin';
            });
            const isAdminWithUnitUser = !!restrictedAdminRole;

            if (isSuperAdminRole || (isAdmin && !isAdminWithUnitUser)) {
                // Admin/Super User/Super Admin sees all knowledge items
                homeMenu.children = [...allKnowledgeItems.value];
            } else if (isAdminWithUnitUser) {
                // Admin with specific Unit only sees their unit's knowledge
                const userUnitName = restrictedAdminRole.unit.name.toLowerCase();
                homeMenu.children = allKnowledgeItems.value.filter(item => 
                    item.name.toLowerCase().includes(userUnitName)
                );
            } else {
                // Regular users only see units assigned to them
                const userUnits = newVal.employee.roles.map(r => r.unit?.name?.toLowerCase() || '');
                const allowedItems = allKnowledgeItems.value.filter(item => {
                    const itemUnit = item.name.replace('Knowledge ', '').toLowerCase();
                    return userUnits.some(u => u.includes(itemUnit));
                });
                
                if (allowedItems.length > 0) {
                    homeMenu.children = allowedItems;
                } else {
                    // Fallback to show all if no unit-specific matches found
                    homeMenu.children = [...allKnowledgeItems.value];
                }
            }
        }
    }
}, { immediate: true });

const submitRole = () => {
    if (selectedRole.value === null) return;
    
    // Find selected role object
    const roleObj = roleOptions.value.find(r => r.id === selectedRole.value);
    if (!roleObj) return;

    selectedRoleObj.value = roleObj;

    // Track login in database for daily stats
    if (roleObj.unit && roleObj.unit !== 'Unknown') {
        axios.post('/api/dashboard/track-login', { unit_name: roleObj.unit })
            .catch(err => console.error("Login tracking error:", err));
    }

    // Logic: If Agent -> change layout. If Admin -> stay in default (admin) layout
    const roleName = roleObj.role.toLowerCase();
    // Allow Agent layout for 'agent' or if the role matches specific unit names used as roles
    if (roleName === 'agent' || roleName === 'credit' || roleName === 'banking' || roleName === 'emerald' || roleName === 'merchant') {
        currentLayout.value = 'agent';
        currentLayout.value = 'agent';
        fetchCarouselNews(); // Fetch Slider (Promo only)
        fetchAgentNews(); // Fetch default list (optional)
    } else {
        currentLayout.value = 'admin';
        // Force 'My Role' view as the default dashboard for Admin
        if (!activeSubmenuName.value) {
             const myProfileMenu = menus.value.find(m => m.name === 'My Profile');
             if (myProfileMenu) {
                 myProfileMenu.isOpen = true;
                 myProfileMenu.active = true;
                 activeMenuName.value = 'My Profile';
                 
                 const myRoleChild = myProfileMenu.children.find(c => c.name === 'My Role');
                 if(myRoleChild) {
                     myRoleChild.active = true;
                     activeSubmenuName.value = 'My Role';
                 }
             }
        }
    }

    // Save to localStorage for session persistence
    localStorage.setItem('selectedRole', JSON.stringify(roleObj));
};

// Sync selectedRoleObj with localStorage whenever it changes
watch(selectedRoleObj, (newVal) => {
    if (newVal) {
        // CRITICAL: Only persist to localStorage if it's a REAL role, not a preview
        if (!newVal.isPreview && !String(newVal.id).startsWith('preview-')) {
            localStorage.setItem('selectedRole', JSON.stringify(newVal));
        }
    } else {
        localStorage.removeItem('selectedRole');
    }
}, { deep: true });


const initialUrlParams = new URLSearchParams(window.location.search);
const activeMenuName = ref(initialUrlParams.get('menu') || localStorage.getItem('lastMenu') || '');
const activeSubmenuName = ref(initialUrlParams.get('submenu') || localStorage.getItem('lastSubmenu') || '');

// --- User Modal State ---
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isRoleModalOpen = ref(false);
const isResetPasswordModalOpen = ref(false);
const isAgentDetailModalOpen = ref(false); // Used for Dropdown now




const filteredMenus = computed(() => {

    if (isSuperAdmin.value) return menus.value;
    
    if (isAdminWithUnit.value) {
        return menus.value.map(menu => {
            if (menu.name === 'Application Tools') return null;
            
            const newMenu = { ...menu };
            if (menu.name === 'Human Resource' && menu.children) {
                newMenu.children = menu.children.filter(child => 
                    child.name !== 'Activity Log' && child.name !== 'Manage Unit'
                );
            }
            if (menu.name === 'Portal' && menu.children) {
                newMenu.children = menu.children.filter(child => 
                    child.name !== 'Portal Interface Agent Unit'
                );
            }
            // If the menu children is empty after filtering (and originally had items), hide it
            if (newMenu.children && menu.children.length > 0 && newMenu.children.length === 0) {
                return null;
            }
            return newMenu;
        }).filter(menu => menu !== null);
    }
    
    return menus.value;
});
const agentHeaderTitle = computed(() => {
    if (selectedRoleObj.value && selectedRoleObj.value.unit) {
        return `CXC ${selectedRoleObj.value.unit.toUpperCase()}`;
    }
    return 'CXC KNOWLEDGE';
});

const agentLogoSrc = computed(() => {
    return '/images/Logo Globe (No BG).png';
});

// Update Document Title based on Selected Unit
// Update Document Title and Favicon based on Selected Unit and Layout
watch([selectedRoleObj, currentLayout], ([newRole, newLayout]) => {
    const faviconLink = document.getElementById('app-favicon');
    
    if (newLayout === 'agent' && newRole && newRole.unit) {
        document.title = `Knowledge ${newRole.unit}`;
        
        // Use the new requested logo for all Knowledge pages
        let iconPath = '/images/logo-portalcxc.png';
        if (faviconLink) faviconLink.href = iconPath;
    } else {
        document.title = 'PortalCXC';
        if (faviconLink) faviconLink.href = '/images/logo-portalcxc.png';
    }
}, { immediate: true });

const modalMode = ref('add');
const formUser = ref({
    data: {
        npp: '',
        name: '',
        email: '',
        username: '',
        password: '',
        isActive: 'Yes',
        roles: [],
    }
});



const userInitials = computed(() => {
    try {
        const u = currentUser.value;
        if (!u) return 'AG';
        const name = u.name || (u.employee ? u.employee.name : '');
        if (name && typeof name === 'string') {
            const parts = name.trim().split(/\s+/);
            if (parts.length >= 2) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            } else if (parts.length === 1) {
                 return parts[0].substring(0, 2).toUpperCase();
            }
        }
        if (u.npp) {
            return String(u.npp).substring(0, 2).toUpperCase();
        }
        return 'AG';
    } catch (e) {
        return 'AG';
    }
});

const agentNameDisplay = computed(() => {
    return currentUser.value?.name || currentUser.value?.employee?.name || currentUser.value?.npp || 'Unknown Agent';
});

const agentProfileButton = ref(null);
const toggleAgentDetail = () => {
    isAgentDetailModalOpen.value = !isAgentDetailModalOpen.value;
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (isAgentDetailModalOpen.value && agentProfileButton.value && !agentProfileButton.value.contains(event.target)) {
        // Check if the click is inside the dropdown itself
        const dropdown = document.getElementById('agent-detail-dropdown');
        if (dropdown && !dropdown.contains(event.target)) {
            isAgentDetailModalOpen.value = false;
        }
    }
};

// --- Session Timeout Logic ---
let idleTimer;
const SESSION_TIMEOUT = 8 * 60 * 60 * 1000; // 8 hours (28,800,000 ms)

const resetIdleTimer = () => {
    if (idleTimer) clearTimeout(idleTimer);
    idleTimer = setTimeout(forceLogout, SESSION_TIMEOUT);
};

const forceLogout = async (reason = 'timeout') => {
    try {
        // Record last logout on server before clearing local state
        await axios.post('/api/logout', { 
            reason,
            user_id: currentUser.value?.id 
        });
    } catch (e) {
        console.warn("Server logout recording failed during force logout", e);
    }

    // Set flag for Login page to show specific message
    localStorage.setItem('logoutReason', reason);
    
    // Clear local state thoroughly
    localStorage.removeItem('lastMenu');
    localStorage.removeItem('lastSubmenu');
    localStorage.removeItem('selectedRole');
    localStorage.removeItem('loginTimestamp');
    localStorage.removeItem('portalSettingsCache');
    
    // Force redirect to login
    router.push('/'); 
};

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('mousemove', resetIdleTimer);
    window.removeEventListener('keydown', resetIdleTimer);
    window.removeEventListener('keydown', handleGlobalKeydown);
    if (idleTimer) clearTimeout(idleTimer);
    // Clear per-menu news image carousel intervals
    Object.values(menuNewsIntervals).forEach(id => clearInterval(id));
});

const formRole = ref({
    rolename: 'Admin',
    unitname: 'Banking'
});
const roleErrorMessage = ref(''); // State for Role Modal specific errors

const formErrors = ref({ npp: '', name: '', email: '' }); // State for User form validation errors
const generalFormError = ref(''); // State for generic form errors (e.g. "Mohon lengkapi...")

const newPassword = ref('');
const confirmNewPassword = ref('');
const showNewPassword = ref(false);
const showConfirmNewPassword = ref(false);


// Initialize default active state if any
const initializeActiveState = () => {
    // 0. Check URL Parameters (Priority)
    const urlParams = new URLSearchParams(window.location.search);
    const urlMenu = urlParams.get('menu');
    const urlSubmenu = urlParams.get('submenu');
    const urlPortalCategory = urlParams.get('portalCategory');
    const urlViewNewsId = urlParams.get('viewNewsId');

    if (urlPortalCategory) {
        selectedPortalCategory.value = urlPortalCategory;
        fetchAgentNews(urlPortalCategory);
        isNewsListModalOpen.value = true;
    }
    
    if (urlViewNewsId) {
        axios.get(`/api/news/${urlViewNewsId}`).then(res => {
            viewAgentNews(res.data);
        }).catch(e => console.error("Failed to fetch news from URL", e));
    }

    // 1. Restore Navigation State
    let lastMenu = urlMenu || localStorage.getItem('lastMenu');
    let lastSubmenu = urlSubmenu || localStorage.getItem('lastSubmenu');

    // Security fallback: Hide/Prevent Unit Admin from loading Super Admin only Portal Interface
    if (isAdminWithUnit.value) {
        if (lastMenu === 'Portal') lastMenu = 'My Profile';
        if (lastSubmenu === 'Portal Interface Agent Unit') lastSubmenu = 'My Role';
    }

    if (lastMenu) {
        activeMenuName.value = lastMenu;
        const menuToOpen = menus.value.find(m => m.name === lastMenu);
        if (menuToOpen) {
            menus.value.forEach(m => { m.isOpen = false; m.active = false; }); // Reset others
            menuToOpen.isOpen = true;
            menuToOpen.active = true;
            
            if (lastSubmenu) {
                activeSubmenuName.value = lastSubmenu;
                const childToActivate = menuToOpen.children?.find(c => c.name === lastSubmenu);
                if (childToActivate) {
                    childToActivate.active = true;
                }
            }
        } else if (lastSubmenu) {
            // Even if menu object isn't found yet (e.g. dynamic), set the intent
            activeSubmenuName.value = lastSubmenu;
        }
    } else {
        // Fallback to default behavior if no storage
        // Default to 'My Role'
        const myProfileMenu = menus.value.find(m => m.name === 'My Profile');
        if (myProfileMenu) {
             myProfileMenu.isOpen = true;
             myProfileMenu.active = true;
             activeMenuName.value = 'My Profile';
             
             const myRoleChild = myProfileMenu.children?.find(c => c.name === 'My Role');
             if (myRoleChild) {
                 myRoleChild.active = true;
                 activeSubmenuName.value = 'My Role';
             }
        }
    }

    // 2. Restore User Form State
    const savedUserForm = localStorage.getItem('userFormState');
    if (savedUserForm) {
        try {
            const parsed = JSON.parse(savedUserForm);
            if (parsed.isOpen) {
                isModalOpen.value = true;
                modalMode.value = parsed.mode;
                formUser.value = parsed.data;
            }
        } catch (e) { console.error('Error restoring user form', e); }
    }

    // 3. Restore Unit Form State
    const savedUnitForm = localStorage.getItem('unitFormState');
    if (savedUnitForm) {
        try {
            const parsed = JSON.parse(savedUnitForm);
            if (parsed.isOpen) {
                isUnitModalOpen.value = true;
                unitModalMode.value = parsed.mode;
                formUnit.value = parsed.data;
            }
        } catch (e) { console.error('Error restoring unit form', e); }
    }
};

// --- Persistence Watchers ---

// Save Navigation
watch(activeMenuName, (val) => { if(val) localStorage.setItem('lastMenu', val); });
watch(activeSubmenuName, (val) => { 
    if (val) {
        // DO NOT save "Knowledge" preview menus to localStorage as the last active submenu.
        // This prevents the Admin from getting stuck in Agent view upon refreshing /home.
        if (!val.startsWith('Knowledge ')) {
            localStorage.setItem('lastSubmenu', val); 
        }
    } else {
        localStorage.removeItem('lastSubmenu');
    }

    // Handle "Home -> Knowledge [Unit]" navigation from Admin
    if (val && val.startsWith('Knowledge ')) {
        const unitName = val.replace('Knowledge ', '').trim();
        // Temporarily set the role object to simulate Agent view for this Unit
        selectedRoleObj.value = {
            id: 'preview-' + unitName.toLowerCase(),
            role: 'Agent',
            unit: unitName,
            label: `VIEW ${unitName.toUpperCase()}`,
            isPreview: true
        };
        // Explicitly fetch carousel news for this unit
        fetchCarouselNews();
    }
});

// Save User Form State
watch([formUser, isModalOpen, modalMode], ([newData, isOpen, mode]) => {
    if (isOpen) {
        localStorage.setItem('userFormState', JSON.stringify({ isOpen, mode, data: newData }));
    } else {
        localStorage.removeItem('userFormState');
    }
}, { deep: true });

// Save Unit Form State
watch([formUnit, isUnitModalOpen, unitModalMode], ([newData, isOpen, mode]) => {
    if (isOpen) {
        localStorage.setItem('unitFormState', JSON.stringify({ isOpen, mode, data: newData }));
    } else {
        localStorage.removeItem('unitFormState');
    }
}, { deep: true });


// Manage User State
const usersData = shallowRef([]);
const selectedUsers = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 20
});

const filters = ref({
    npp: '',
    name: '',
    role: '',
    email: '',
    active: '',
    lastLogin: '',
    lastLogout: '',
    wrongPass: '',
});
const showFilters = ref({
    npp: false,
    name: false,
    role: false,
    email: false,
    active: false,
    lastLogin: false,
    lastLogout: false,
    wrongPass: false,
});

const isLoading = ref(false);

const fetchUsers = async (page = 1) => {
    if (activeSubmenuName.value !== 'Manage User') return;
    
    isLoading.value = true;
    try {
        const perPageParam = pagination.value.per_page === 'All' ? 999999 : (pagination.value.per_page || 20);
        const params = {
            page: page,
            per_page: perPageParam,
            ...filters.value
        };
        const response = await axios.get('/api/users', { params });
        usersData.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            per_page: perPageParam === 999999 ? 'All' : response.data.per_page,
        };
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        isLoading.value = false;
    }
};

const toggleFilter = (col) => {
    if (showFilters.value.hasOwnProperty(col)) {
        showFilters.value[col] = !showFilters.value[col];
    }
};

const applyFilter = () => {
    fetchUsers(1);
};

const resetFilters = () => {
    Object.keys(filters.value).forEach(key => filters.value[key] = '');
    fetchUsers(1);
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchUsers(page);
    }
};

const selectAll = () => {
    selectedUsers.value = usersData.value.map(user => user.id);
};

const deselectAll = () => {
    selectedUsers.value = [];
};



// --- News Management State ---
const newsContentEditor = ref(null);
const editorInstance = ref(null);
const isPasteLoading = ref(false);

const onEditorReady = (editor) => {
    editorInstance.value = editor;
    setupOfficePasteHandler(editor, {
        onStartProcessing: () => {
            isPasteLoading.value = true;
        },
        onEndProcessing: () => {
            isPasteLoading.value = false;
        }
    });
    
    // 1. Extend schema to allow tableIndent attribute
    if (editor.model.schema.isRegistered('table')) {
        editor.model.schema.extend('table', { allowAttributes: 'tableIndent' });
    }
    
    // 2. Conversion for tableIndent -> margin-left
    editor.conversion.for('downcast').attributeToAttribute({
        model: 'tableIndent',
        view: modelAttributeValue => {
            return {
                key: 'style',
                value: {
                    'margin-left': (modelAttributeValue * 40) + 'px'
                }
            };
        }
    });

    editor.conversion.for('upcast').attributeToAttribute({
        view: {
            name: 'figure', // CKEditor wraps tables in figure.table
            classes: 'table',
            styles: {
                'margin-left': /.*/
            }
        },
        model: {
            key: 'tableIndent',
            value: viewElement => {
                const margin = viewElement.getStyle('margin-left') || '0px';
                const px = parseInt(margin.replace('px', '')) || 0;
                return Math.floor(px / 40);
            }
        }
    });

    // 3. Intercept Commands for Table Indent
    const overrideIndent = (commandName, isIncrease) => {
        const command = editor.commands.get(commandName);
        if (command) {
            command.on('execute', (evt) => {
                const selection = editor.model.document.selection;
                const blocks = Array.from(selection.getSelectedBlocks());
                const selectedElement = selection.getSelectedElement();
                const table = (selectedElement && selectedElement.is('element', 'table')) 
                              ? selectedElement 
                              : (blocks.length > 0 ? blocks[0].findAncestor('table') : null);
                
                if (table) {
                    evt.stop(); // Prevent default text indentation
                    editor.model.change(writer => {
                        const currentIndent = table.getAttribute('tableIndent') || 0;
                        let newIndent = isIncrease ? currentIndent + 1 : currentIndent - 1;
                        if (newIndent < 0) newIndent = 0;
                        
                        if (newIndent === 0) {
                            writer.removeAttribute('tableIndent', table);
                        } else {
                            writer.setAttribute('tableIndent', newIndent, table);
                        }
                    });
                }
            }, { priority: 'high' });

            // Ensure button is enabled when a table is selected, otherwise retain original state
            const originalRefresh = command.refresh.bind(command);
            command.refresh = function() {
                originalRefresh();
                const selection = editor.model.document.selection;
                const blocks = Array.from(selection.getSelectedBlocks());
                const selectedElement = selection.getSelectedElement();
                const table = (selectedElement && selectedElement.is('element', 'table')) 
                              ? selectedElement 
                              : (blocks.length > 0 ? blocks[0].findAncestor('table') : null);
                
                if (table) {
                    if (commandName === 'outdent') {
                        const currentIndent = table.getAttribute('tableIndent') || 0;
                        this.isEnabled = currentIndent > 0;
                    } else {
                        this.isEnabled = true;
                    }
                }
            };
        }
    };

    overrideIndent('indent', true);
    overrideIndent('outdent', false);
    
    // Set initial content if any
    if (formNews.value.content) {
        editor.setData(formNews.value.content);
    }
};


const editorConfig = computed(() => ({
    plugins: [
        Essentials, Bold, Italic, Underline, Strikethrough, Subscript, Superscript,
        Link, List, ListProperties, TodoList, Paragraph, Heading, Alignment, Table, TableToolbar,
        TableProperties, TableCellProperties, TableCaption, TableColumnResize,
        BlockQuote, Image, ImageToolbar, ImageCaption, ImageStyle, ImageResize,
        ImageUpload, LinkImage, Indent, IndentBlock, FontColor, FontBackgroundColor, FontSize,
        FontFamily, Highlight, HorizontalLine, MediaEmbed, Code, CodeBlock,
        FindAndReplace, RemoveFormat, SpecialCharacters, SpecialCharactersEssentials,
        Undo, ImageInsert, Autoformat, SelectAll, Base64UploadAdapter,
        GeneralHtmlSupport, HtmlEmbed, PageBreak, SourceEditing, ShowBlocks, WordCount,
        CustomFormatPainter, WordMultilevelList
    ],
    heading: {
        options: [
            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
            { model: 'heading1', view: 'h1', title: 'Heading 1 (BAB)', class: 'ck-heading_heading1' },
            { model: 'heading2', view: 'h2', title: 'Heading 2 (SUB BAB)', class: 'ck-heading_heading2' },
            { model: 'heading3', view: 'h3', title: 'Heading 3 (SUB SUB BAB)', class: 'ck-heading_heading3' },
            { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
            { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
            { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
        ]
    },
    list: {
        properties: {
            styles: {
                useAttribute: true,
                listStyleTypes: {
                    bulleted: ['disc', 'circle', 'square'],
                    numbered: [
                        'decimal',
                        'decimal-leading-zero',
                        'lower-roman',
                        'upper-roman',
                        'lower-latin',
                        'upper-latin'
                    ]
                }
            },
            startIndex: true,
            reversed: true
        }
    },
    toolbar: {
        items: [
            'undo', 'redo', '|',
            'sourceEditing', 'showBlocks', '|',
            'findAndReplace', 'selectAll', '|',
            'heading', '|',
            'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'code', 'subscript', 'superscript', 'removeFormat', 'formatPainter', '|',
            'horizontalLine', 'pageBreak', 'specialCharacters', 'link', 'insertImage', 'mediaEmbed', 'insertTable', 'blockQuote', 'codeBlock', 'htmlEmbed', '|',
            'alignment', '|',
            'bulletedList', 'numberedList', 'multiLevelList', 'todoList', '|',
            'outdent', 'indent', '|',
            'alignColon', 'toggleTableBorder'
        ],
        shouldNotGroupWhenFull: true
    },
    table: {
        contentToolbar: [
            'tableColumn', 'tableRow', 'mergeTableCells', 'toggleTableBorder', 'tableProperties', 'tableCellProperties', 'toggleTableCaption'
        ],
        tableProperties: {
            defaultProperties: {
                alignment: 'left'
            }
        },
        tableCellProperties: {
            defaultProperties: {
                horizontalAlignment: 'left',
                verticalAlignment: 'middle'
            }
        }
    },
    link: {
        addTargetToExternalLinks: true,
        defaultProtocol: 'https://',
        decorators: {
            openInNewTab: {
                mode: 'automatic',
                callback: url => !!url,
                attributes: {
                    target: '_blank',
                    rel: 'noopener noreferrer'
                }
            }
        }
    },
    image: {
        resizeUnit: 'px',
        toolbar: [
            'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 
            'imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight', '|',
            'resizeImage', '|',
            'toggleImageCaption', 'imageTextAlternative', 'linkImage'
        ],
        resizeOptions: [
            {
                name: 'resizeImage:original',
                value: null,
                label: 'Original'
            },
            {
                name: 'resizeImage:50',
                value: '50',
                label: '50%'
            },
            {
                name: 'resizeImage:75',
                value: '75',
                label: '75%'
            }
        ]
    },
    placeholder: 'Tulis isi berita di sini...',
    licenseKey: 'GPL',
    fontFamily: {
        options: [
            'default',
            'Aptos, Aptos (Body), sans-serif',
            'Aptos Display, sans-serif',
            'Calibri, Calibri Light, sans-serif',
            'Segoe UI, Segoe UI Semibold, sans-serif',
            'Cambria, Cambria Math, serif',
            'Arial, Helvetica, sans-serif',
            'Arial Black, sans-serif',
            'Times New Roman, Times, serif',
            'Georgia, serif',
            'Garamond, serif',
            'Tahoma, Geneva, sans-serif',
            'Trebuchet MS, Helvetica, sans-serif',
            'Verdana, Geneva, sans-serif',
            'Mistral, cursive',
            'Century Gothic, sans-serif',
            'Book Antiqua, Palatino, serif',
            'Courier New, Courier, monospace',
            'Consolas, Monaco, monospace',
            'Comic Sans MS, cursive, sans-serif',
            'Impact, sans-serif'
        ],
        supportAllValues: true
    },
    fontSize: {
        options: [
            8, 9, 10, 10.5, 11, 11.5, 12, 13, 14, 15, 16, 18, 20, 22, 24, 26, 28, 32, 36, 40, 44, 48, 54, 60, 72
        ],
        supportAllValues: true
    },
    htmlSupport: {
        allow: [
            {
                name: /.*/,
                attributes: true,
                classes: true,
                styles: true
            }
        ]
    },
    htmlEmbed: {
        showPreviews: true,
        sanitizeHtml: (inputHtml) => {
            // No sanitization to allow marquee and other tags requested by user
            return {
                html: inputHtml,
                hasChanged: false
            };
        }
    }
}));
const newsFileInput = ref(null);
const newsData = shallowRef([]);
const selectedNews = ref([]);
const newsPagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
const isLoadingNews = ref(true);
const isNewsTransactionLoading = ref(false);
const showNewsFilters = ref({ title: false, keyword: false, unit: false, category: false, created_name: false, created_time: false, updated_name: false, updated_time: false, status: false });
const newsFilters = ref({ title: '', keyword: '', unit: '', category: '', created_name: '', created_time: '', updated_name: '', updated_time: '', status: '' });
const isNewsModalOpen = ref(false);
const isNewsMaximized = ref(false);
const isViewNewsModalOpen = ref(false);
const isViewNewsMaximized = ref(false);
const isAnnouncementMaximized = ref(false);
const isImageActualSize = ref(false);
const newsModalMode = ref('create'); // 'create', 'edit'
const newsSort = ref({ column: 'created_at', order: 'desc' });

const sortByNews = (column) => {
    if (newsSort.value.column === column) {
        newsSort.value.order = newsSort.value.order === 'asc' ? 'desc' : 'asc';
    } else {
        newsSort.value.column = column;
        newsSort.value.order = 'desc';
    }
    fetchNews(1);
};

const flatNewsCategories = computed(() => {
    const list = [];
    if (availableNewsCategories.value) {
        availableNewsCategories.value.forEach(group => {
            if (group.options) {
                group.options.forEach(opt => {
                    if (!list.includes(opt)) list.push(opt);
                });
            }
        });
    }
    return list.sort();
});

// Initialize/Reset
// Initialize/Reset
const initializeNewsFilters = () => {
   newsFilters.value = { 
       title: '', 
       keyword: '', 
       unit: isAdminWithUnit.value ? (selectedRoleObj.value?.unit || '') : '', 
       category: '', 
       created_name: '', 
       created_time: '', 
       updated_name: '', 
       updated_time: '',
       status: ''
   };
   // Also reset visibility and sort
   showNewsFilters.value = { title: false, keyword: false, unit: false, category: false, created_name: false, created_time: false, updated_name: false, updated_time: false, status: false };
   newsSort.value = { column: 'created_at', order: 'desc' };
};
const formNews = ref({
    title: '',
    category: '',
    startTime: new Date().toISOString().split('T')[0],
    endTime: new Date().toISOString().split('T')[0],
    keyword: '',
    headline: 'No',
    content: '',
    target_unit: [], 
    attachments: [], 
    attachment: null,
    attachmentPath: ''
});
const viewNewsData = ref({});


const globalStatusModal = ref({ show: false, title: '', message: '', type: 'error' });
const showGlobalStatus = (title, message, type = 'error') => {
    globalStatusModal.value = { show: true, title, message, type };
};
// Helper: get EndTime status badge for Manage News table
const getEndTimeStatus = (endTime) => {
    if (!endTime) return { label: '-', cls: 'bg-gray-100 text-gray-400', icon: 'fas fa-minus' };
    const now = new Date();
    const end = new Date(endTime);
    
    const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    const endStr = String(endTime).substring(0, 10);
    
    if (endStr === todayStr) {
        return { label: 'Expired hari ini', cls: 'bg-red-100 text-red-600', icon: 'fas fa-times-circle' };
    }

    const diffMs = end - now;
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    if (diffMs < 0) {
        return { label: 'Expired', cls: 'bg-red-100 text-red-600', icon: 'fas fa-times-circle' };
    } else if (diffDays <= 7) {
        return { label: `${diffDays} hari lagi`, cls: 'bg-orange-100 text-orange-600', icon: 'fas fa-exclamation-triangle' };
    } else if (diffDays <= 30) {
        return { label: `${diffDays} hari lagi`, cls: 'bg-yellow-100 text-yellow-700', icon: 'fas fa-clock' };
    } else {
        return { label: `${diffDays} hari lagi`, cls: 'bg-green-100 text-green-700', icon: 'fas fa-check-circle' };
    }
};

const newsErrors = ref({
    title: '',
    startTime: '',
    keyword: '',
    category: '',
    endTime: '',
    target_unit: '',
    content: ''
});

const fetchNews = async (page = 1) => {
    isLoadingNews.value = true;
    try {
        // Enforce Unit Restriction for restricted Admin
        if (isAdminWithUnit.value && selectedRoleObj.value?.unit) {
            newsFilters.value.unit = selectedRoleObj.value.unit;
        }

        const params = {
            page: page,
            per_page: newsPagination.value.per_page === 'All' ? 999999 : (newsPagination.value.per_page || 20),
            exclude_content: 1,
            ...newsFilters.value,
            sort_column: newsSort.value.column,
            sort_order: newsSort.value.order
        };
        const response = await axios.get('/api/news', { params });
        newsData.value = response.data.data;
        newsPagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            per_page: params.per_page === 999999 ? 'All' : response.data.per_page,
        };
    } catch (error) {
        console.error("Error fetching news:", error);
    } finally {
        isLoadingNews.value = false;
    }
};

const resetNewsForm = () => {
    formNews.value = { 
        title: '', 
        category: '', 
        startTime: new Date().toISOString().split('T')[0], 
        endTime: new Date().toISOString().split('T')[0], 
        keyword: '', 
        headline: 'No', 
        content: '',
        target_unit: [], 
        attachments: [], 
        attachment: null, 
        attachmentPath: '' 
    };
};

const openCreateNewsModal = () => {
    resetNewsForm();
    Object.keys(newsErrors.value).forEach(key => newsErrors.value[key] = '');
    newsModalMode.value = 'create';
    if (isAdminWithUnit.value) {
        formNews.value.target_unit = [selectedRoleObj.value.unit];
    } else if (isSuperAdmin.value) {
        formNews.value.target_unit = ['All Layanan'];
    }

    isNewsModalOpen.value = true;
};

const closeNewsModal = () => {
    isNewsModalOpen.value = false;
    isNewsMaximized.value = false;
    resetNewsForm();
    Object.keys(newsErrors.value).forEach(key => newsErrors.value[key] = '');
};

const openEditNewsModal = async () => {
    if (selectedNews.value.length !== 1) {
        showNotification("Harap pilih satu berita yang ingin diedit", 'error');
        return;
    }
    
    // Fetch full data from API because list is excluding content
    let item;
    try {
        const res = await axios.get(`/api/news/${selectedNews.value[0]}`);
        item = res.data;
    } catch (e) {
        showNotification("Gagal memuat data berita", 'error');
        return;
    }
    
    newsModalMode.value = 'edit';
    let targetUnit = [];
    try {
        if (item.target_unit) {
             targetUnit = JSON.parse(item.target_unit);
             if (!Array.isArray(targetUnit)) targetUnit = [item.target_unit]; // Handle legacy string
        }
    } catch (e) {
        if (item.target_unit) {
            if (item.target_unit.includes(',')) {
                targetUnit = item.target_unit.split(',').map(s => s.trim());
            } else {
                targetUnit = [item.target_unit];
            }
        } else {
            targetUnit = [];
        }
    }

    // Extract attachments from DB or content
    let extractedAttachments = [];
    
    // First priority: Read from the new JSON column 'attachments'
    if (item.attachments) {
        try {
            // It could be an array or string depending on Axios parsing, generally array if casted in Model
            const atts = Array.isArray(item.attachments) ? item.attachments : JSON.parse(item.attachments);
            if (Array.isArray(atts)) {
                extractedAttachments = atts;
            }
        } catch (e) { console.error("Error parsing attachments JSON", e); }
    }

    // Second priority: If no DB column data, parse content (Legacy or sync fallback)
    // AND ALSO: We must process content to auto-upgrade icons for display even if access attachments from DB
    if (item.content) { 
        const parser = new DOMParser();
        const doc = parser.parseFromString(item.content, 'text/html');
        // Try to find the specific table rows
        const rows = doc.querySelectorAll('table.cxc-attachment-table tbody tr');
        
        // Use extractedAttachments only if empty (don't overwrite DB source)
        const populateAttachments = extractedAttachments.length === 0;

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 3) {
                // Name is in 2nd cell (index 1)
                const name = cells[1].innerText.trim();
                // URL/Link is in 3rd cell (index 2)
                const link = cells[2].querySelector('a');
                if (link && populateAttachments) {
                    const url = link.getAttribute('href');
                    if (url && url.includes('/storage/')) {
                        const path = url.split('/storage/')[1];
                         extractedAttachments.push({
                            name: name || path.split('/').pop(),
                            type: '',
                            url: url,
                            path: path
                        });
                    }
                }
            }
        });
        
        // Update content on item object directly for View Mode (Temporary)
        // We'll rely on openViewNewsModal for visual enhancements
        item.content = doc.body.innerHTML; 

        // Fallback: If table not found, look for standalone links (Legacy rich text links)
        if (extractedAttachments.length === 0) {
             const links = doc.querySelectorAll('a[href*="/storage/news_attachments/"]');
             const seenUrls = new Set();
             links.forEach(link => {
                const url = link.getAttribute('href');
                if (!seenUrls.has(url)) {
                    seenUrls.add(url);
                    const path = url.split('/storage/')[1];
                    extractedAttachments.push({
                        name: link.innerText || path.split('/').pop(),
                        type: '',
                        url: url,
                        path: path
                    });
                }
             });
        }
    }
    
    // Ensure the primary attachment (from DB column) is included if not found in content (Data integrity)
    if (item.attachment) {
        const primaryPath = item.attachment;
        const exists = extractedAttachments.some(a => a.path === primaryPath);
        if (!exists) {
             extractedAttachments.unshift({ // Add to beginning
                name: primaryPath.split('/').pop(),
                type: '', 
                url: `${window.location.origin}/storage/${primaryPath}`,
                path: primaryPath
            });
        }
    }

    formNews.value = {
        title: item.title,
        category: item.category,
        startTime: item.start_time,
        endTime: item.end_time,
        keyword: item.keyword,
        headline: item.headline,
        content: item.content,
        target_unit: targetUnit,
        attachments: extractedAttachments,
        attachment: null,
        attachmentPath: item.attachment || ''
    };
    isNewsModalOpen.value = true;
};

const openViewNewsModal = async () => {
    if (selectedNews.value.length !== 1) {
        showNotification("Harap pilih satu berita yang ingin dilihat", 'error');
        return;
    }
    
    // Fetch full data from API because list is excluding content
    let item;
    try {
        const res = await axios.get(`/api/news/${selectedNews.value[0]}`);
        item = res.data;
    } catch (e) {
        showNotification("Gagal memuat data berita", 'error');
        return;
    }

    // Parse and auto-upgrade view content (in-memory only, doesn't affect DB unless saved)
    let processedContent = (item.content || '').replace(/<img[^>]+src=["']file:\/\/\/[^"']*["'][^>]*>/gi, '');
    if (processedContent) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(processedContent, 'text/html');
        
        // 1. Upgrade Download Buttons (Teal Square Icon)
        const actionLinks = doc.querySelectorAll('table.cxc-attachment-table tbody tr td:nth-child(4) a');
        actionLinks.forEach(a => {
             a.innerHTML = `<img src="/storage/portal/icon-download.png" style="width: 32px; height: 32px; display: block; border: none; object-fit: contain;" alt="Download">`;
             
             a.style.display = 'inline-flex';
             a.style.alignItems = 'center';
             a.style.justifyContent = 'center';
             a.style.width = '32px';
             a.style.height = '32px';
             a.style.backgroundColor = 'transparent';
             a.style.color = 'transparent';
             a.style.fontSize = '0';
             a.style.lineHeight = '0';
             a.style.borderRadius = '6px';
             a.style.textDecoration = 'none';
             a.style.border = 'none';
             a.style.outline = 'none';
             a.style.boxShadow = 'none';
             a.setAttribute('title', 'Download File');
             a.setAttribute('target', '_self');
             const href = a.getAttribute('href');
             if (href) {
                 const fileName = href.split('/').pop();
                 a.setAttribute('download', fileName || 'file');
                 
                 let finalHref = href;
                 if (href.startsWith('/')) {
                     finalHref = window.location.origin + href;
                     a.setAttribute('href', finalHref);
                 }
             }
        });

        // 2. Force target="_blank" and absolute URL on attachment links (in the URL column)
        const urlLinks = doc.querySelectorAll('table.cxc-attachment-table tbody tr td:nth-child(3) a');
        urlLinks.forEach(a => {
            a.setAttribute('target', '_blank');
            const href = a.getAttribute('href');
            if (href && href.startsWith('/')) {
                const absoluteUrl = window.location.origin + href;
                a.setAttribute('href', absoluteUrl);
                a.innerText = absoluteUrl;
            }
        });

        processedContent = doc.body.innerHTML;
    }

    const firstContentImage = (!item.attachment || !isImageFile(item.attachment)) ? extractFirstImageFromContent(item.content) : null;
    // If we use the content image as header, remove it from the rendered body to avoid duplicate
    if (firstContentImage) {
        processedContent = removeFirstImageFromContent(processedContent);
    }

    viewNewsData.value = {
        ...item,
        content: processedContent,
        firstContentImage
    };
    isViewNewsModalOpen.value = true;
};

const handleNewsFileUpload = async (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    // Show loading indicator
    isNewsTransactionLoading.value = true;
    
    // Give Vue a tick to render the loading indicator before we block the thread
    await new Promise(resolve => setTimeout(resolve, 50));

    try {
        let newRowsHtml = '';
        const startingIndex = formNews.value.attachments.length;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // 1. Validasi Ukuran File (Maks 10MB)
            if (file.size > 10 * 1024 * 1024) {
                showGlobalStatus(`File Terlalu Besar`, `File "${file.name}" memiliki ukuran ${ (file.size / (1024 * 1024)).toFixed(2) } MB. Maksimum ukuran yang diperbolehkan adalah 10 MB.`, 'error');
                continue;
            }

            // 2. Validasi Ekstensi File
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
            const extension = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(extension)) {
                showGlobalStatus(`Format Tidak Didukung`, `File "${file.name}" memiliki format yang tidak didukung. Harap gunakan: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, atau PNG.`, 'error');
                continue;
            }

            const blobUrl = URL.createObjectURL(file);
            const attachment = {
                name: file.name,
                type: file.type.split('/').pop().toUpperCase(),
                url: blobUrl, // Temporary URL for preview
                path: null,
                isPending: true,
                file: file // Store reference for later upload
            };
            
            formNews.value.attachments.push(attachment);
            
            // Set primary attachment reference if it's the first one
            if (formNews.value.attachments.length === 1) {
                formNews.value.attachment = file; 
            }

            // Build the row HTML for batch insertion later
            const index = startingIndex + i + 1;
            
            // Idempotency check: don't insert if URL already exists in content
            if (!(formNews.value.content && formNews.value.content.includes(attachment.url))) {
                newRowsHtml += `
      <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #4b5563;">${index}</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; color: #4b5563;">${attachment.name}</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; color: #4b5563; word-break: break-all;"><a href="${attachment.url}" download="${attachment.name}" target="_self" style="color: #0f766e; text-decoration: underline;">${attachment.url}</a></td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; white-space: nowrap; line-height: 0;">
          <a href="${attachment.url}" download="${attachment.name}" target="_self" style="display: inline-flex !important; align-items: center; justify-content: center; width: 32px; height: 32px; background-color: transparent; color: transparent !important; text-decoration: none !important; border-radius: 6px; font-size: 0 !important; line-height: 0 !important; overflow: hidden; border: none; outline: none;" title="Download">
              <img src="/storage/portal/icon-download.png" style="width: 32px; height: 32px; display: block; border: none; object-fit: contain;" alt="Download">
          </a>
        </td>
      </tr>`;
            }
        }

        // Insert ALL newly built rows into CKEditor AT ONCE
        if (newRowsHtml) {
            if (editorInstance.value) {
                const currentData = editorInstance.value.getData();
                if (currentData.includes('cxc-attachment-table')) {
                    // Append batch rows to existing table
                    const newData = currentData.replace('</tbody>', newRowsHtml + '</tbody>');
                    editorInstance.value.setData(newData);
                } else {
                    const tableHtml = `
                    <div style="overflow-x: auto; margin-top: 15px;">
                      <table class="cxc-attachment-table" style="width: 100%; border-collapse: collapse; min-width: 600px; font-family: sans-serif; font-size: 14px; border: 1px solid #e5e7eb;">
                        <thead>
                          <tr style="background-color: #f9fafb;">
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; font-weight: 600; color: #374151; width: 50px;">No</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left; font-weight: 600; color: #374151; min-width: 200px;">Nama File</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left; font-weight: 600; color: #374151; min-width: 250px;">URL</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; font-weight: 600; color: #374151; width: 80px;">Action</th>
                          </tr>
                        </thead>
                        <tbody>${newRowsHtml}</tbody>
                      </table>
                    </div>
                    <p>&nbsp;</p>`;
                    editorInstance.value.setData(currentData + tableHtml);
                }
            } else {
                // Fallback for non-editor mode
                if (formNews.value.content && formNews.value.content.includes('cxc-attachment-table')) {
                    formNews.value.content = formNews.value.content.replace('</tbody>', newRowsHtml + '</tbody>');
                } else {
                    const tableHtml = `
                    <div style="overflow-x: auto; margin-top: 15px;">
                      <table class="cxc-attachment-table" style="width: 100%; border-collapse: collapse; min-width: 600px; font-family: sans-serif; font-size: 14px; border: 1px solid #e5e7eb;">
                        <thead>
                          <tr style="background-color: #f9fafb;">
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; font-weight: 600; color: #374151; width: 50px;">No</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left; font-weight: 600; color: #374151; min-width: 200px;">Nama File</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left; font-weight: 600; color: #374151; min-width: 250px;">URL</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; font-weight: 600; color: #374151; width: 80px;">Action</th>
                          </tr>
                        </thead>
                        <tbody>${newRowsHtml}</tbody>
                      </table>
                    </div>
                    <p>&nbsp;</p>`;
                    formNews.value.content = (formNews.value.content || '') + tableHtml;
                }
            }
        }
        
        if (newsFileInput.value) {
            newsFileInput.value.value = '';
        }
    } finally {
        isNewsTransactionLoading.value = false;
    }
};

const insertAttachmentTable = (attachment, index) => {
    // This function is kept for backward compatibility if needed elsewhere,
    // but bulk uploads now use the batched approach inside handleNewsFileUpload.
    const rowHtml = `
      <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #4b5563;">${index}</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; color: #4b5563;">${attachment.name}</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; color: #4b5563; word-break: break-all;"><a href="${attachment.url}" download="${attachment.name}" target="_self" style="color: #0f766e; text-decoration: underline;">${attachment.url}</a></td>
        <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; white-space: nowrap; line-height: 0;">
        <a href="${attachment.url}" download="${attachment.name}" target="_self" style="display: inline-flex !important; align-items: center; justify-content: center; width: 32px; height: 32px; background-color: transparent; color: transparent !important; text-decoration: none !important; border-radius: 6px; font-size: 0 !important; line-height: 0 !important; overflow: hidden; border: none; outline: none;" title="Download">
            <img src="/storage/portal/icon-download.png" style="width: 32px; height: 32px; display: block; border: none; object-fit: contain;" alt="Download">
        </a>
      </td>
    </tr>`;

    // Idempotency check: don't insert if URL already exists in content
    if (formNews.value.content && formNews.value.content.includes(attachment.url)) {
        return;
    }

    if (editorInstance.value) {
        const currentData = editorInstance.value.getData();
        if (currentData.includes('cxc-attachment-table')) {
            // Append row to existing table in editor
            const newData = currentData.replace('</tbody>', rowHtml + '</tbody>');
            editorInstance.value.setData(newData);
        } else {
            // Insert new table
            const tableHtml = `
                <table class="cxc-attachment-table" style="width: 100%; border-collapse: collapse; margin-top: 20px; font-family: sans-serif; border: 1px solid #e5e7eb;">
                    <thead>
                        <tr style="background-color: #f9fafb;">
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">No</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">Nama File</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">URL</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">Action</th>
                        </tr>
                    </thead>
                    <tbody>${rowHtml}</tbody>
                </table><p>&nbsp;</p>`;
            
            const viewFragment = editorInstance.value.data.processor.toView(tableHtml);
            const modelFragment = editorInstance.value.data.toModel(viewFragment);
            editorInstance.value.model.insertContent(modelFragment);
        }
    } else {
        // Fallback for non-editor mode
        if (formNews.value.content && formNews.value.content.includes('cxc-attachment-table')) {
            formNews.value.content = formNews.value.content.replace('</tbody>', rowHtml + '</tbody>');
        } else {
            const tableHtml = `
                <table class="cxc-attachment-table" style="width: 100%; border-collapse: collapse; margin-top: 20px; font-family: sans-serif; border: 1px solid #e5e7eb;">
                    <thead>
                        <tr style="background-color: #f9fafb;">
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">No</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">Nama File</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">URL</th>
                            <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: center; color: #374151; font-weight: 600;">Action</th>
                        </tr>
                    </thead>
                    <tbody>${rowHtml}</tbody>
                </table><p>&nbsp;</p>`;
            formNews.value.content += tableHtml;
        }
    }
};

const removeNewsFile = (index) => {
    openGlobalDelete(
        'Verifikasi Hapus Lampiran',
        'Apakah Anda yakin ingin menghapus lampiran ini secara permanen?',
        async () => {
            if (index === undefined) {
                 // Clear all (legacy)
                 if (formNews.value.attachments && formNews.value.attachments.length > 0) {
                     for (const att of formNews.value.attachments) {
                         if (att.path || (att.url && att.url.includes('/storage/'))) {
                             try {
                                 await axios.post('/api/news/delete-attachment', { path: att.path, url: att.url });
                             } catch (e) {
                                 console.error("Gagal menghapus lampiran dari server", e);
                             }
                         }
                     }
                 }
                 formNews.value.attachments = [];
                 formNews.value.attachment = null;
                 formNews.value.attachmentPath = '';
                 
                 // Remove table from content
                 if (formNews.value.content) {
                     const parser = new DOMParser();
                     const doc = parser.parseFromString(formNews.value.content, 'text/html');
                     doc.querySelectorAll('table.cxc-attachment-table').forEach(table => table.remove());
                     formNews.value.content = doc.body.innerHTML;
                     if (editorInstance.value) editorInstance.value.setData(formNews.value.content);
                 }
            } else {
                const deletedAttachment = formNews.value.attachments[index];
                if (deletedAttachment && (deletedAttachment.path || (deletedAttachment.url && deletedAttachment.url.includes('/storage/')))) {
                    try {
                        await axios.post('/api/news/delete-attachment', { path: deletedAttachment.path, url: deletedAttachment.url });
                    } catch (e) {
                        console.error("Gagal menghapus lampiran dari server", e);
                    }
                }
                
                // Remove from content using DOMParser
                if (deletedAttachment && formNews.value.content) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(formNews.value.content, 'text/html');
                    
                    const rows = doc.querySelectorAll('table.cxc-attachment-table tbody tr');
                    rows.forEach(tr => {
                        const trHtml = tr.innerHTML;
                        const nameCell = tr.querySelector('td:nth-child(2)');
                        const nameMatches = nameCell && nameCell.textContent.trim() === deletedAttachment.name;
                        if (nameMatches || trHtml.includes(deletedAttachment.name) || (deletedAttachment.url && trHtml.includes(deletedAttachment.url))) {
                            tr.remove();
                        }
                    });
                    
                    doc.querySelectorAll('table.cxc-attachment-table').forEach(table => {
                        const tbody = table.querySelector('tbody');
                        if (!tbody || tbody.children.length === 0 || tbody.innerHTML.trim() === '') {
                            table.remove();
                        }
                    });
                    
                    formNews.value.content = doc.body.innerHTML;
                    if (editorInstance.value) editorInstance.value.setData(formNews.value.content);
                }
                
                formNews.value.attachments.splice(index, 1);

                if (formNews.value.attachments.length > 0) {
                    formNews.value.attachmentPath = formNews.value.attachments[0].path;
                } else {
                    formNews.value.attachment = null;
                    formNews.value.attachmentPath = '';
                }
            }

            if (newsFileInput.value) {
                newsFileInput.value.value = '';
            }
        },
        'Hapus'
    );
};

const saveNews = async () => {

    // 1. Process pending uploads first
    let hasUploadError = false;
    let finalContent = formNews.value.content || '';
    const pendingAttachments = formNews.value.attachments.filter(a => a.isPending);
    
    if (pendingAttachments.length > 0) {
        isNewsTransactionLoading.value = true;
        
        // Process uploads in batches to speed up the process while avoiding rate limits
        const batchSize = 10;
        for (let i = 0; i < formNews.value.attachments.length; i += batchSize) {
            const batch = formNews.value.attachments.slice(i, i + batchSize);
            
            await Promise.all(batch.map(async (att, batchIndex) => {
                const actualIndex = i + batchIndex;
                if (att.isPending && att.file) {
                    const formData = new FormData();
                    formData.append('file', att.file);
                    
                    let unitsTag = ['general'];
                    if (formNews.value.target_unit && formNews.value.target_unit.length > 0) {
                        unitsTag = formNews.value.target_unit;
                    } else if (selectedRoleObj.value && selectedRoleObj.value.unit) {
                        unitsTag = [selectedRoleObj.value.unit];
                    }
                    formData.append('units', JSON.stringify(unitsTag));

                    try {
                        const response = await axios.post('/api/news/upload-attachment', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        });

                        if (response.data && response.data.url) {
                            const realUrl = response.data.url;
                            const oldUrl = att.url;
                            
                            // Replace temporary blob URL with server URL in content string
                            finalContent = finalContent.split(oldUrl).join(realUrl);
                            
                            // Update attachment object with server data
                            formNews.value.attachments[actualIndex] = {
                                name: response.data.name || att.name,
                                type: response.data.type || att.type,
                                url: realUrl,
                                path: response.data.path,
                                isPending: false
                            };

                            // Revoke blob URL to free memory
                            URL.revokeObjectURL(oldUrl);
                        }
                    } catch (error) {
                        console.error(`Deferred upload failed for ${att.name}`, error);
                        const errMsg = error.response?.data?.message || error.message;
                        showGlobalStatus(`Gagal Menyimpan Lampiran`, `Gagal mengunggah "${att.name}": ${errMsg}.`, 'error');
                        hasUploadError = true;
                    }
                }
            }));
            
            if (hasUploadError) break;
        }
        
        // DO NOT update formNews.value.content = finalContent here.
        // Updating CKEditor with a massive 240+ row table HTML completely freezes the browser's main thread
        // due to reactivity and CKEditor diffing logic. Instead, we use finalContent for the API request directly.
    }
    
    isNewsTransactionLoading.value = true;

    if (hasUploadError) {
        isNewsTransactionLoading.value = false;
        return;
    }

    // 2. Comprehensive Validation
    // Clear previous errors
    Object.keys(newsErrors.value).forEach(key => newsErrors.value[key] = '');
    
    let isValid = true;
    const hasImage = finalContent && finalContent.toLowerCase().includes('<img');
    const cleanContent = finalContent ? finalContent.replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, '').trim() : '';

    if (!formNews.value.title) { newsErrors.value.title = "Title wajib diisi"; isValid = false; }
    if (!formNews.value.startTime) { newsErrors.value.startTime = "StartTime wajib diisi"; isValid = false; }
    if (!formNews.value.keyword) { newsErrors.value.keyword = "Keyword wajib diisi"; isValid = false; }
    if (!formNews.value.category) { newsErrors.value.category = "Category wajib diisi"; isValid = false; }
    if (!formNews.value.endTime) { newsErrors.value.endTime = "EndTime wajib diisi"; isValid = false; }
    if (!formNews.value.target_unit || formNews.value.target_unit.length === 0) { newsErrors.value.target_unit = "Target Unit wajib dipilih"; isValid = false; }
    if (!cleanContent && !hasImage) { newsErrors.value.content = "Content berita wajib diisi"; isValid = false; }

    // Date Comparison Validation
    if (formNews.value.startTime && formNews.value.endTime) {
        const start = new Date(formNews.value.startTime);
        const end = new Date(formNews.value.endTime);
        if (end <= start) {
            newsErrors.value.endTime = "Tanggal EndTime tidak boleh sama ataupun sebelum StartTime";
            isValid = false;
        }
    }

    if (!isValid) {
        isNewsTransactionLoading.value = false;
        
        // Auto-scroll to the first error field in the modal
        nextTick(() => {
            const modalBody = document.querySelector('.news-modal-body');
            // Match the actual class used in template: text-[10px]
            const firstError = modalBody ? modalBody.querySelector('.text-red-500') : document.querySelector('.text-red-500');
            if (firstError) {
                // Focus the parent or the input if possible
                const container = firstError.closest('.flex.flex-col') || firstError.parentElement;
                if (container) {
                    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const input = container.querySelector('input, select, textarea');
                    if (input) input.focus();
                } else {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        return;
    }

    const formData = new FormData();
    formData.append('title', formNews.value.title);
    formData.append('content', finalContent || '');
    if (formNews.value.keyword) formData.append('keyword', formNews.value.keyword);
    if (formNews.value.category) formData.append('category', formNews.value.category);
    if (formNews.value.startTime) formData.append('start_time', formNews.value.startTime);
    if (formNews.value.endTime) formData.append('end_time', formNews.value.endTime);
    if (formNews.value.headline) formData.append('headline', formNews.value.headline);
    
    // Handle array target_unit
    if (formNews.value.target_unit && formNews.value.target_unit.length > 0) {
        formData.append('target_unit', JSON.stringify(formNews.value.target_unit));
    }

    if (formNews.value.created_by) formData.append('created_by', currentUser.value?.employee?.name || 'System');
    formData.append('updated_by', currentUser.value?.employee?.name || 'System');

    // Handle File
    if (formNews.value.attachment) {
        formData.append('attachment', formNews.value.attachment);
    } else {
        formData.append('attachment_path', formNews.value.attachmentPath || '');
    }

    // Handle Multiple Attachments List
    if (formNews.value.attachments) {
        formData.append('attachments', JSON.stringify(formNews.value.attachments));
    }

    try {
        if (newsModalMode.value === 'create') {
            formData.append('created_by', currentUser.value?.employee?.name || 'System');
            await axios.post('/api/news', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            showGlobalStatus('Berhasil', 'Berhasil Simpan', 'success');
        } else {
             const id = selectedNews.value[0];
             formData.append('_method', 'PUT');
             await axios.post(`/api/news/${id}`, formData, {
                 headers: { 'Content-Type': 'multipart/form-data' }
             });
             showGlobalStatus('Berhasil', 'Berhasil diupdate', 'success');
        }
        
        isNewsModalOpen.value = false;
        selectedNews.value = [];
        resetNewsForm(); // Clear form only on success
        fetchNews(newsPagination.value.current_page); // Refresh list
    } catch (e) {
        console.error("Failed to save news", e);
        let errorMsg = e.response?.data?.message || e.message || 'Terjadi kesalahan pada sistem.';
        // If the error contains HTML or is excessively long, trim or clean it
        if (typeof errorMsg === 'string' && (errorMsg.includes('<html') || errorMsg.includes('<!DOCTYPE') || errorMsg.length > 500)) {
            errorMsg = errorMsg.replace(/<[^>]*>?/gm, ' ').substring(0, 300) + '...';
        }
        showGlobalStatus('Error', 'Gagal menyimpan berita: ' + errorMsg, 'error');
    } finally {
        isNewsTransactionLoading.value = false;
    }
};

const deleteNews = () => {
    if (selectedNews.value.length === 0) {
        showNotification("Harap pilih berita yang ingin dihapus", 'error');
        return;
    }
    
    openGlobalDelete(
        'Hapus Berita',
        `Apakah Anda yakin ingin menghapus ${selectedNews.value.length} berita terpilih?`,
        async () => {
            try {
                await axios.post('/api/news/bulk-delete', { ids: selectedNews.value });
                selectedNews.value = [];
                fetchNews(newsPagination.value.current_page);
                showGlobalStatus('Berhasil', 'Berhasil Dihapus', 'success');
            } catch (e) {
                console.error("Failed to delete news", e);
                showGlobalStatus('Error', 'Gagal menghapus berita', 'error');
                throw e;
            }
        }
    );
};

const execCmd = (command, value = null) => {
    document.execCommand(command, false, value);
};

const copyToClipboard = (text) => {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        alert('URL copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        // Fallback
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        alert('URL copied to clipboard!');
    });
};

// --- Portal Interface Management ---
// (portalSettings, backgroundUrl, CACHE_KEY moved to top of setup to avoid TDZ)

const getCachedSettings = () => {
    try {
        const cached = localStorage.getItem(CACHE_KEY);
        if (cached) {
            const { data } = JSON.parse(cached);
            portalSettings.value = data || [];
            
            // Extract background immediately
            const bg = portalSettings.value.find(s => s.section === 'background');
            if (bg && bg.image_name) {
                backgroundUrl.value = `/images/${bg.image_name}`;
            }
            return data || [];
        }
    } catch (e) { console.error("Cache init error", e); }
    return [];
};

getCachedSettings();

const menuLayoutConfig = ref({}); // { 'Menu 1': 'list', ... }
const menuBackgroundConfig = ref({}); // { 'Menu 1': '#ff0000', ... }
let menuAutoSlideInterval = null;

function parseLayoutConfig() {
    const configs = {};
    const bgConfigs = {};
    portalSettings.value.forEach(i => {
        if (i.category === 'LayoutConfig') {
            configs[i.title] = i.url; 
        } else if (i.category === 'BackgroundConfig') {
            bgConfigs[i.title] = i.url;
        }
    });

    menuLayoutConfig.value = configs;
    menuBackgroundConfig.value = bgConfigs;

    // Sync primary background
    const bg = portalSettings.value.find(s => s.section === 'background');
    if (bg && bg.image_name) {
        backgroundUrl.value = `/images/${bg.image_name}`;
    }
}

// Immediate parse from cache (now safe because function is hoisted)
parseLayoutConfig();

const fetchPortalSettings = async (forceRefresh = false) => {
    try {
        // Always Fetch from Server to ensure real-time accuracy on reload
        const response = await axios.get('/api/portal-settings');
        portalSettings.value = response.data;
        
        // Write to localStorage ONLY to trigger cross-tab 'storage' events for real-time sync
        localStorage.setItem('portalSettingsCache', JSON.stringify({
            data: portalSettings.value,
            timestamp: Date.now()
        }));
        
        parseLayoutConfig();

        // After portal settings are loaded, re-fetch carousel news as it depends on portal titles
        if (currentLayout.value === 'agent') {
            fetchCarouselNews();
        }

    } catch (e) {
        console.error("Failed to fetch portal settings", e);
    }
};

const saveMenuLayoutConfig = async (menuName, mode) => {
    try {
        const existing = portalSettings.value.find(i => i.category === 'LayoutConfig' && i.title === menuName);
        
        const formData = new FormData();
        formData.append('category', 'LayoutConfig');
        formData.append('title', menuName);
        formData.append('url', mode);
        formData.append('section', 'config');
        formData.append('is_active', 1);

        if (existing) {
             await axios.post(`/api/portal-settings/${existing.id}`, formData);
        } else {
             await axios.post(`/api/portal-settings`, formData);
        }
        
        menuLayoutConfig.value[menuName] = mode;
        showNotification(`Layout for ${menuName} updated to ${mode}`, 'success');
        
        // Force refresh to update cache and IDs
        fetchPortalSettings(true); 
    } catch (e) {
        console.error("Failed to save layout config", e);
        showNotification("Failed to update layout", 'error');
    }
};

const saveMenuBackgroundConfig = async (menuName, color) => {
    try {
        const existing = portalSettings.value.find(i => i.category === 'BackgroundConfig' && i.title === menuName);
        
        const formData = new FormData();
        formData.append('category', 'BackgroundConfig');
        formData.append('title', menuName);
        formData.append('url', color);
        formData.append('section', 'config');
        formData.append('is_active', 1);

        if (existing) {
             await axios.post(`/api/portal-settings/${existing.id}`, formData);
        } else {
             await axios.post(`/api/portal-settings`, formData);
        }
        
        menuBackgroundConfig.value[menuName] = color;
        showNotification(`Background for ${menuName} updated`, 'success');
        
        // Force refresh to update cache and IDs
        fetchPortalSettings(true); 
    } catch (e) {
        console.error("Failed to save background config", e);
        showNotification("Failed to update background", 'error');
    }
};

const getMenuBackgroundStyle = (menuName) => {
    const val = menuBackgroundConfig.value[menuName];
    if (!val) return {};
    if ((val.includes('/') || val.includes('.')) && !val.includes('url(') && !val.includes('gradient') && !val.startsWith('#') && !val.startsWith('rgb')) {
         return { background: `url('${val}') center/cover no-repeat` };
    }
    // Use backgroundColor for hex/rgb so it doesn't reset backgroundImage (CSS shorthand conflict)
    if (val.startsWith('#') || val.startsWith('rgb')) {
        return { backgroundColor: val };
    }
    return { background: val };
};

// Returns backgroundImage style from fetched news images. Applied FIRST so admin
// color (backgroundColor) can coexist without resetting it via shorthand conflict.
const getMenuNewsImageStyle = (menuKey) => {
    if (menuLayoutConfig.value[menuKey] !== 'slide') return {};

    const imgs = menuNewsImages.value[menuKey];
    if (!imgs || imgs.length === 0) return {};

    const activeIdx = menuSlideIndices.value[menuKey] || 0;
    const items = getMenuItems(menuKey);
    const activeItem = items[activeIdx];

    const carouselIdx = menuNewsCarouselIndices.value[menuKey] || 0;

    if (activeItem && activeItem.title) {
        const activeTitle = activeItem.title.trim().toLowerCase();
        
        // Find ALL images matching this Sub Menu title
        const matchedImgs = imgs.filter(i => i.category === activeTitle);
        
        if (matchedImgs.length > 0) {
            const totalItems = items.length > 0 ? items.length : 1;
            const imgIdx = Math.floor(carouselIdx / totalItems) % matchedImgs.length;
            const imgToShow = matchedImgs[imgIdx];
            return {
                backgroundImage: `url('${imgToShow.img}')`,
                backgroundSize: '100% 100%',
                backgroundPosition: 'center center',
                backgroundRepeat: 'no-repeat'
            };
        }
        
        // If there's an active sub menu but NO matching news image for it,
        // we DO NOT fall back to showing other sub menus' images.
        // We only fall back to generic Menu-level images (like 'menu 15') if they exist.
        const genericCat1 = menuKey.toLowerCase();
        const genericCat2 = menuKey.replace(' ', '').toLowerCase();
        const genericImgs = imgs.filter(i => i.category === genericCat1 || i.category === genericCat2);
        if (genericImgs.length > 0) {
            const imgToShow = genericImgs[carouselIdx % genericImgs.length];
            return {
                backgroundImage: `url('${imgToShow.img}')`,
                backgroundSize: '100% 100%',
                backgroundPosition: 'center center',
                backgroundRepeat: 'no-repeat'
            };
        }
        
        // Return empty so it uses the normal background color instead of another sub menu's image
        return {};
    }

    // FALLBACK when there is no activeItem (e.g. no sub menus configured)
    if (imgs[carouselIdx % imgs.length]?.img) {
        return {
            backgroundImage: `url('${imgs[carouselIdx % imgs.length].img}')`,
                backgroundSize: '100% 100%',
                backgroundPosition: 'center center',
                backgroundRepeat: 'no-repeat'
        };
    }

    return {};
};

const handleImageError = (item) => {
    // Fallback if image fails to load
    // We modify the item locally to remove image_name so it falls back to icon
    // Note: This mutation is temporary for the current view and won't persist to DB unless saved
    item.image_name = ''; 
};

const savePortalSettings = async () => {
    try {
        await axios.post('/api/portal-settings/bulk', { settings: portalSettings.value });
        showNotification('Settings updated successfully', 'success');
        
        // Force refresh to update cache
        fetchPortalSettings(true);
    } catch (e) {
        console.error("Failed to save portal settings", e);
        showNotification('Failed to save settings', 'error');
    }
};

const isPortalUpdateModalOpen = ref(false);
const portalFormError = ref(''); // Inline error state for portal modal
const portalModalMode = ref('edit'); // 'add', 'edit'
const formPortalUpdate = ref({
    id: null,
    title: '',
    category: '',
    url: '',
    image_name: '',
    image: null,
    is_active: true,
    order_no: 1,
    section: 'main_layout',
    parent_id: null
});

const availableParentMenus = computed(() => {
    if (!formPortalUpdate.value.category) return [];
    
    return (portalSettings.value || []).filter(item => {
        // 1. Must be in the same category
        if (item.category !== formPortalUpdate.value.category) return false;

        // 2. If we are EDITING an existing item (id exists), perform cycle checks
        if (formPortalUpdate.value.id) {
            // Cannot be self
            if (item.id === formPortalUpdate.value.id) return false;
            
            // Cannot be a direct child (prevent simple cycle: A->B->A)
            // If the potential parent (item) thinks I am its parent, it cannot be my parent.
            if (item.parent_id === formPortalUpdate.value.id) return false;
        }
        
        return true;
    }).sort((a, b) => (a.title || '').localeCompare(b.title || ''));
});

const openPortalUpdateModal = (item) => {
    portalModalMode.value = 'edit';

    // Robust parsing for target_unit to ensure it's ALWAYS an array
    let tUnit = [];
    try {
        if (Array.isArray(item.target_unit)) {
            tUnit = item.target_unit;
        } else if (typeof item.target_unit === 'string') {
            // Try parsing JSON first
            try {
                const parsed = JSON.parse(item.target_unit);
                if (Array.isArray(parsed)) tUnit = parsed;
                // If parsing returns a non-array (e.g. single string "Banking" from old data), wrap it
                else if (parsed) tUnit = [parsed]; 
            } catch (e) {
                // If not valid JSON, treat the string itself as a single unit if not empty/null
                if (item.target_unit && item.target_unit !== 'null' && item.target_unit !== '[]') {
                    tUnit = [item.target_unit];
                }
            }
        }
    } catch (e) {
        console.error("Error parsing target_unit", e);
        tUnit = [];
    }
    
    // Final safety check
    if (!Array.isArray(tUnit)) tUnit = [];

    formPortalUpdate.value = {
        id: item.id,
        title: item.title,
        category: item.category,
        url: item.url,
        image_name: item.image_name,
        image: null,
        is_active: item.is_active === 1 || item.is_active === true || item.is_active === 'Yes',
        order_no: item.order_no,
        section: item.section || 'main_layout',
        target_unit: tUnit,
        parent_id: item.parent_id || null
    };
    fetchParentOptions();
    portalFormError.value = '';
    isPortalUpdateModalOpen.value = true;
};

const openPortalAddModal = (category, section = 'main_layout') => {
    portalModalMode.value = 'add';
    formPortalUpdate.value = {
        id: null,
        title: '',
        category: category,
        url: section === 'background' ? '' : '#',
        image_name: '',
        image: null,
        is_active: true,
        order_no: 1,
        section: section,
        target_unit: section === 'background' ? ['All Layanan'] : [],
        parent_id: null
    };
    fetchParentOptions();
    portalFormError.value = '';
    isPortalUpdateModalOpen.value = true;
};

const deletePortalSetting = (id) => {
    openGlobalDelete(
        'Hapus Menu Portal',
        'Apakah Anda yakin ingin menghapus item menu ini? Langkah ini akan menghapus data secara permanen.',
        async () => {
            try {
                await axios.delete(`/api/portal-settings/${id}`);
                showGlobalStatus('Berhasil', 'Item berhasil dihapus', 'success');
                fetchPortalSettings(true); // Force Refresh Cache
            } catch (e) {
                showGlobalStatus('Error', 'Gagal menghapus item', 'error');
                throw e; // Relayed to executeGlobalDelete for loading state
            }
        }
    );
};

const handlePortalImageUpload = (event) => {
    formPortalUpdate.value.image = event.target.files[0];
};

const updateSinglePortalSetting = async () => {
    // 1. Mandatory Target Unit Check
    let tUnits = formPortalUpdate.value.target_unit;
    if (!Array.isArray(tUnits)) tUnits = [];
    
    // Filter out potential empty strings or nulls just in case
    tUnits = tUnits.filter(u => u && u.trim() !== '');

    // Strict check: Ensure selected units are actually valid/available options
    const validUnitNames = parentOptions.value.map(u => u.name); // Using parentOptions source of truth
    const hasGlobal = tUnits.includes('All Layanan');
    
    // Filter to keep only those that are "All Layanan" OR exist in the valid list
    let validSelected = tUnits.filter(u => u === 'All Layanan' || validUnitNames.includes(u));

    // Special handling for Background: allow empty or force All Layanan
    if (formPortalUpdate.value.section === 'background' && validSelected.length === 0) {
        validSelected = ['All Layanan'];
    }

    if (validSelected.length === 0) {
        portalFormError.value = 'Wajib Pilih salah satu "Visible For (Agent Unit)" atau centang "All Layanan".';
        console.log('Validation failed: No valid units selected (Ghosts removed)');
        return;
    }
    
    // Update the form value to the sanitized list to avoid saving ghosts
    formPortalUpdate.value.target_unit = validSelected;


    // 2. Duplicate Check
    const isDuplicate = (portalSettings.value || []).some(s => 
        s.id !== formPortalUpdate.value.id && 
        s.category === formPortalUpdate.value.category && 
        s.title.toLowerCase() === (formPortalUpdate.value.title || '').toLowerCase()
    );

    if (isDuplicate) {
        showNotification('Menu dengan judul ini sudah ada di kategori ini!', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('title', formPortalUpdate.value.title || '');
    formData.append('category', formPortalUpdate.value.category || '');
    formData.append('url', formPortalUpdate.value.url || '');
    formData.append('is_active', formPortalUpdate.value.is_active ? 1 : 0);
    formData.append('order_no', formPortalUpdate.value.order_no || 1);
    formData.append('section', formPortalUpdate.value.section || 'main_layout');
    
    if (formPortalUpdate.value.target_unit && formPortalUpdate.value.target_unit.length > 0) {
        formData.append('target_unit', JSON.stringify(formPortalUpdate.value.target_unit));
    }
    
    if (formPortalUpdate.value.image) {
        formData.append('image', formPortalUpdate.value.image);
    }

    if (formPortalUpdate.value.parent_id) {
        formData.append('parent_id', formPortalUpdate.value.parent_id);
    }

    try {
        if (portalModalMode.value === 'add') {
            await axios.post(`/api/portal-settings`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            await axios.post(`/api/portal-settings/${formPortalUpdate.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }
        // Wait for data to refresh before closing/notifying
        await fetchPortalSettings(true); 
        showNotification(portalModalMode.value === 'add' ? 'Added successfully' : 'Updated successfully', 'success');
        isPortalUpdateModalOpen.value = false;
    } catch (e) {
        console.error("Failed to save setting", e);
        const errorMsg = e.response?.data?.message || 'Failed to save setting';
        showNotification(errorMsg, 'error');
    }
};

const portalSettingsBySection = computed(() => {
    const sections = ['background', 'main_layout', 'corporate_info', 'mini_box', 'promo', 'bottom_center', 'corner'];
    const result = {};
    sections.forEach(s => {
        result[s] = (portalSettings.value || []).filter(item => item.section === s);
    });
    return result;
});

const collapsedMenuGroups = ref(Array.from({length: 15}, (_, i) => `Menu ${i + 1}`));
const toggleMenuGroup = (name) => {
    if (collapsedMenuGroups.value.includes(name)) {
        collapsedMenuGroups.value = collapsedMenuGroups.value.filter(n => n !== name);
    } else {
        collapsedMenuGroups.value.push(name);
    }
};

const globalDeleteModal = ref({
    isOpen: false,
    title: '',
    message: '',
    confirmText: 'Hapus',
    onConfirm: null,
    isLoading: false,
    type: 'danger', // 'danger', 'success', 'warning'
    icon: 'fas fa-trash-alt',
    confirmClass: 'bg-red-600 hover:bg-red-700 shadow-red-200'
});

const openGlobalDelete = (title, message, callback, confirmText = 'Hapus', type = 'danger', icon = null, confirmClass = null) => {
    let defaultIcon = 'fas fa-trash-alt';
    let defaultConfirmClass = 'bg-red-600 hover:bg-red-700 shadow-red-200';
    if (type === 'success') {
        defaultIcon = 'fas fa-check-double';
        defaultConfirmClass = 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200';
    } else if (type === 'warning') {
        defaultIcon = 'fas fa-exclamation-triangle';
        defaultConfirmClass = 'bg-amber-600 hover:bg-amber-700 shadow-amber-200';
    }

    globalDeleteModal.value = {
        isOpen: true,
        title,
        message,
        confirmText,
        onConfirm: callback,
        isLoading: false,
        type,
        icon: icon || defaultIcon,
        confirmClass: confirmClass || defaultConfirmClass
    };
};

const executeGlobalDelete = async () => {
    if (!globalDeleteModal.value.onConfirm) {
        globalDeleteModal.value.isOpen = false;
        return;
    }
    globalDeleteModal.value.isLoading = true;
    try {
        await globalDeleteModal.value.onConfirm();
        globalDeleteModal.value.isOpen = false;
    } catch (error) {
        console.error("Action error in globalDeleteModal:", error);
    } finally {
        globalDeleteModal.value.isLoading = false;
    }
};

// Helper for changing slides manually
const scrollMenuContainer = (event, direction) => {
    const btn = event.currentTarget;
    const container = btn.parentElement.querySelector('.menu-scroll-container');
    if (container) {
        // Find if this is horizontal (flex-row/wrap) or list (flex-col) scrolling.
        // For standard list/horizontal, we scroll vertically as horizontal mode wraps
        const scrollAmount = container.clientHeight * 0.8; 
        container.scrollBy({ top: direction * scrollAmount, behavior: 'smooth' });
    }
};

const moveSlide = (menuName, direction) => {
    const items = getMenuItems(menuName);
    if (!items || items.length === 0) return;
    
    let currentIndex = menuSlideIndices.value[menuName] || 0;
    
    if (direction === 'next') {
        currentIndex = (currentIndex + 1) % items.length;
    } else {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
    }
    
    // Explicitly update
    menuSlideIndices.value[menuName] = currentIndex;
};

// Helper to Safely Get Current Slide Item
const getCurrentSlideItem = (menuName) => {
    const items = getMenuItems(menuName);
    if (!items || items.length === 0) return null;
    const idx = menuSlideIndices.value[menuName] || 0;
    return items[idx];
};

function startAutoSlide() {
    if (menuAutoSlideInterval) clearInterval(menuAutoSlideInterval);
    menuAutoSlideInterval = setInterval(() => {
        if (currentLayout.value !== 'agent') return;
        
        // Loop through all menus 1-15
        for (let i = 1; i <= 15; i++) {
            const menuName = `Menu ${i}`;
            if (menuLayoutConfig.value[menuName] === 'slide') {
                const items = getMenuItems(menuName);
                if (items && items.length > 1) {
                    moveSlide(menuName, 'next');
                }
            }
        }
    }, 5000); // 5 seconds
};

const groupedMenuSettings = computed(() => {
    const items = (portalSettings.value || []).filter(s => s && typeof s.category === 'string' && s.category.startsWith('Menu '));
    const groups = {};
    items.forEach(item => {
        if (!groups[item.category]) groups[item.category] = [];
        groups[item.category].push(item);
    });
    
    // Sort keys and Build Hierarchical List for each group
    return Object.keys(groups).sort((a, b) => {
        const numA = parseInt(a.replace('Menu ', ''));
        const numB = parseInt(b.replace('Menu ', ''));
        return numA - numB;
    }).map(cat => { 
        const catItems = groups[cat];
        const flatList = [];
        const roots = catItems.filter(i => !i.parent_id);

        const traverse = (nodes, depth) => {
            // Sort by order_no then title
            nodes.sort((a, b) => {
                if (a.order_no !== b.order_no) return (a.order_no || 0) - (b.order_no || 0);
                return (a.title || '').localeCompare(b.title || '');
            });

            nodes.forEach(node => {
                node.depth = depth;
                flatList.push(node);
                const children = catItems.filter(i => i.parent_id === node.id);
                if (children.length > 0) traverse(children, depth + 1);
            });
        };

        traverse(roots, 0);

        // Append orphans at root level just in case
        const visitedIds = new Set(flatList.map(i => i.id));
        const orphans = catItems.filter(i => !visitedIds.has(i.id));
        if (orphans.length > 0) {
             orphans.sort((a,b) => (a.title||'').localeCompare(b.title||''));
             orphans.forEach(o => { o.depth = 0; flatList.push(o); });
        }

        return { 
            name: cat, 
            items: flatList 
        };
    });
});

const availableNewsCategories = computed(() => {
    const groups = [];

    // 1. Standard Categories
    groups.push({
        label: 'Standard Categories',
        options: ["Promo & Program", "Announcement"]
    });

    // 2. Portal Menu Categories
    if (portalSettings.value) {
        // Group by 'category' (Menu 1, Menu 2...)
        const menuGroups = {};
        
        portalSettings.value.forEach(item => {
            if (item.category && item.category.startsWith('Menu ') && item.title) {
                if (!menuGroups[item.category]) {
                    menuGroups[item.category] = [];
                }
                if (!menuGroups[item.category].includes(item.title)) {
                    menuGroups[item.category].push(item.title);
                }
            }
        });

        // Sort keys to ensure Menu 1, Menu 2... order
        const sortedKeys = Object.keys(menuGroups).sort((a, b) => {
             const numA = parseInt(a.replace('Menu ', '') || 0);
             const numB = parseInt(b.replace('Menu ', '') || 0);
             return numA - numB;
        });

        sortedKeys.forEach(key => {
            if (menuGroups[key].length > 0) {
                groups.push({
                    label: key,
                    options: menuGroups[key].sort()
                });
            }
        });
    }

    return groups;
});

// Ukuran banner approximate tiap menu di halaman Agent (tinggi berdasarkan Tailwind class)
const MENU_BANNER_SIZES = {
    'Menu 1': '426 × 128 px',  // h-32, col-span-2 of 6
    'Menu 2': '205 × 128 px',  // h-32, col-span-1
    'Menu 3': '205 × 128 px',
    'Menu 4': '205 × 128 px',
    'Menu 5': '205 × 128 px',
    'Menu 6': '426 × 128 px',  // h-32, col-span-2 of right side
    'Menu 7': '640 × 224 px',  // Banner utama, col-span-4
    'Menu 8': '205 × 128 px',  // h-32 dalam sub-grid
    'Menu 9': '205 × 128 px',
    'Menu 10': '426 × 160 px', // h-40, col-span-2
    'Menu 11': '205 × 160 px', // h-40, sub-grid 2×2
    'Menu 12': '205 × 160 px',
    'Menu 13': '205 × 160 px',
    'Menu 14': '205 × 160 px',
    'Menu 15': '426 × 160 px', // h-full ~160px, col-span-2
};

const getMenuItems = (categoryName) => {
    // Determine the active unit name
    let activeUnit = '';
    if (selectedRoleObj.value && selectedRoleObj.value.unit) {
        activeUnit = selectedRoleObj.value.unit.toUpperCase(); // e.g., 'SAT', 'BANKING'
    }

    // Try to find Unit-Specific Category Name first
    let targetCategory = categoryName;

    if (activeUnit && categoryName.startsWith('Menu ')) {
        const menuNumber = categoryName.replace('Menu ', '');
        const unitCategory = `Menu ${activeUnit} ${menuNumber}`;
        const hasUnitSpecific = (portalSettings.value || []).some(s => s.category === unitCategory && s.is_active);
        if (hasUnitSpecific) {
            targetCategory = unitCategory;
        }
    }
    
    const items = (portalSettings.value || []).filter(s => {
        if (s.category !== targetCategory || !s.is_active) return false;
        if (s.parent_id) return false;
        if (!s.target_unit) return true;

        let targets = [];
        try {
            targets = (typeof s.target_unit === 'string') ? JSON.parse(s.target_unit) : s.target_unit;
        } catch (e) {
            targets = [];
        }

        if (!Array.isArray(targets) || targets.length === 0) return true;
        if (targets.includes("All Layanan")) return true;

        const myUnit = selectedRoleObj.value && selectedRoleObj.value.unit ? selectedRoleObj.value.unit : '';
        if (myUnit && targets.some(t => t.toUpperCase() === myUnit.toUpperCase())) return true;

        return false;
    });

    // --- FALLBACK LOGIC FOR NEW UNITS ---
    // If a standard menu is empty, provide a virtual item so news is still accessible
    if (items.length === 0) {
        const standardMappings = {
            'Menu 5': { title: 'News', icon: 'newspaper' },
            'Menu 6': { title: 'Sharing Moment', icon: 'newspaper' },
            'Menu 8': { title: 'Suku Bunga', icon: 'percent' },
            'Menu 10': { title: 'Promo & Program', icon: 'star' }
        };

        if (standardMappings[categoryName]) {
            const map = standardMappings[categoryName];
            return [{
                id: `virtual-${categoryName}`,
                title: map.title,
                image_name: map.icon,
                url: '#',
                is_active: true,
                category: categoryName,
                is_virtual: true
            }];
        }
    }

    return items;
};

// --- Menu Auto-Slide Logic ---
const menuSlideIndices = ref({});
let menuSlideInterval = null;

const startMenuAutoSlide = () => {
    if (menuSlideInterval) clearInterval(menuSlideInterval);
    
    menuSlideInterval = setInterval(() => {
        // Iterate over menus that strictly have 'slide' config
        Object.keys(menuLayoutConfig.value).forEach(menuName => {
            if (menuLayoutConfig.value[menuName] === 'slide') {
                const items = getMenuItems(menuName);
                if (items.length > 1) {
                    const current = menuSlideIndices.value[menuName] || 0;
                    menuSlideIndices.value[menuName] = (current + 1) % items.length;
                }
            }
        });
    }, 5000); // 5 seconds per slide
};

// Restart slide when config changes
watch(menuLayoutConfig, () => {
    startMenuAutoSlide();
}, { deep: true });

// --- Agent News Carousel Logic refs moved to top of setup (to avoid TDZ) ---

// --- Agent Announcement Logic ---
const activeAnnouncements = ref([]);
const currentAnnouncementIndex = ref(0);
const isAnnouncementClosed = ref(false);
let announcementSlideInterval = null;
let announcementAutoPopInterval = null;

const isImageFile = (filename) => {
    if (!filename || typeof filename !== 'string') return false;
    return /\.(jpg|jpeg|png|webp|gif|svg|avif)$/i.test(filename);
};

const isGalleryOpen = ref(false);
const galleryImages = ref([]);
const activeGalleryIndex = ref(0);
const galleryViewMode = ref('feed'); // 'feed' (continuous vertical scroll of all images) or 'slide' (one by one)

const handleContentClick = (e) => {
    if (e.target && e.target.tagName === 'IMG' && e.target.src && !e.target.src.includes('icon-download')) {
        const clickedSrc = e.target.src;
        // Search container for all images in this document
        const container = e.target.closest('.ck-content') || e.target.closest('.announcement-content') || e.target.closest('.overflow-y-auto') || document.body;
        let imagesList = [];
        
        if (container) {
            const domImgs = Array.from(container.querySelectorAll('img')).filter(img => img.src && !img.src.includes('icon-download') && !img.src.includes('icon-'));
            imagesList = domImgs.map((img, idx) => {
                // Try to extract heading / section title preceding this image
                let caption = '';
                let prev = img.closest('figure') || img;
                let cur = prev.previousElementSibling;
                let steps = 0;
                while (cur && steps < 5 && !caption) {
                    const text = cur.innerText?.trim();
                    if (text && text.length < 150) {
                        caption = text.replace(/\n+/g, ' ');
                        break;
                    }
                    cur = cur.previousElementSibling;
                    steps++;
                }
                return {
                    src: img.src,
                    alt: img.alt || `Gambar ${idx + 1}`,
                    caption: caption || `Gambar ${idx + 1}`
                };
            });
        }
        
        if (!imagesList.length) {
            imagesList = [{ src: clickedSrc, alt: 'Gambar', caption: 'Gambar 1' }];
        }
        
        galleryImages.value = imagesList;
        const clickedIdx = imagesList.findIndex(item => item.src === clickedSrc);
        activeGalleryIndex.value = clickedIdx !== -1 ? clickedIdx : 0;
        galleryViewMode.value = 'feed'; // Default to continuous scroll through all images!
        isGalleryOpen.value = true;
        
        // Automatically scroll to the clicked image in the gallery feed
        nextTick(() => {
            const targetEl = document.getElementById(`gallery-img-${activeGalleryIndex.value}`);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
};

const nextGalleryImage = () => {
    if (galleryImages.value.length > 1) {
        activeGalleryIndex.value = (activeGalleryIndex.value + 1) % galleryImages.value.length;
        if (galleryViewMode.value === 'feed') {
            const targetEl = document.getElementById(`gallery-img-${activeGalleryIndex.value}`);
            if (targetEl) targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

const prevGalleryImage = () => {
    if (galleryImages.value.length > 1) {
        activeGalleryIndex.value = (activeGalleryIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length;
        if (galleryViewMode.value === 'feed') {
            const targetEl = document.getElementById(`gallery-img-${activeGalleryIndex.value}`);
            if (targetEl) targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

const scrollToGalleryImage = (index) => {
    activeGalleryIndex.value = index;
    if (galleryViewMode.value === 'feed') {
        const targetEl = document.getElementById(`gallery-img-${index}`);
        if (targetEl) targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const processedAnnouncementContent = computed(() => {
    if (!activeAnnouncements.value.length) return '';
    const item = activeAnnouncements.value[currentAnnouncementIndex.value];
    if (!item || !item.content) return '';
    
    // Strip any invalid file:/// local images copied from Word to prevent browser security errors
    const cleanContent = item.content.replace(/<img[^>]+src=["']file:\/\/\/[^"']*["'][^>]*>/gi, '');
    
    const parser = new DOMParser();
    const doc = parser.parseFromString(cleanContent, 'text/html');
    
    // Style tables and fix downloads
    const tables = doc.querySelectorAll('table.cxc-attachment-table');
    tables.forEach(table => {
        // Apply inline styles to override Tailwind typography prose
        table.style.width = '100%';
        table.style.borderCollapse = 'collapse';
        table.style.marginTop = '1rem';
        table.style.marginBottom = '1rem';
        table.style.fontFamily = 'inherit';
        table.style.border = '1px solid #e5e7eb';
        table.style.borderRadius = '0.5rem';
        table.style.overflow = 'hidden';
        table.style.boxShadow = '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';
        table.style.tableLayout = 'auto';
        
        table.querySelectorAll('th').forEach(th => {
            th.style.backgroundColor = '#f9fafb';
            th.style.padding = '0.75rem 1rem';
            th.style.border = '1px solid #e5e7eb';
            th.style.textAlign = 'center';
            th.style.color = '#374151';
            th.style.fontWeight = '700';
            th.style.fontSize = '0.875rem';
            th.style.whiteSpace = 'nowrap';
        });
        
        table.querySelectorAll('td').forEach(td => {
            td.style.padding = '0.75rem 1rem';
            td.style.border = '1px solid #e5e7eb';
            td.style.color = '#4b5563';
            td.style.fontSize = '0.875rem';
            td.style.verticalAlign = 'middle';
            
            if(td.cellIndex === 0 || td.cellIndex === 3) {
                td.style.textAlign = 'center';
            } else if(td.cellIndex === 2) {
                td.style.wordBreak = 'break-all';
                td.style.color = '#0f766e';
            }
        });
        
        // Upgrade action links
        const actionLinks = table.querySelectorAll('a');
        actionLinks.forEach(a => {
            const isActionCell = a.closest('td')?.cellIndex === 3;
            if (isActionCell || a.innerText.trim().toLowerCase().includes('download') || a.querySelector('img[src*="icon-download"]')) {
                 a.innerHTML = `<img src="/storage/portal/icon-download.png" style="width: 32px; height: 32px; display: block; border: none; object-fit: contain; margin: 0 auto;" alt="Download">`;
                 a.style.display = 'inline-flex';
                 a.style.alignItems = 'center';
                 a.style.justifyContent = 'center';
                 a.style.width = '32px';
                 a.style.height = '32px';
                 a.style.backgroundColor = 'transparent';
                 a.style.color = 'transparent';
                 a.style.fontSize = '0';
                 a.style.lineHeight = '0';
                 a.style.padding = '0';
                 a.style.margin = '0 auto';
                 a.style.borderRadius = '6px';
                 a.style.border = 'none';
                 a.style.textDecoration = 'none';
                 a.setAttribute('title', 'Download File');
                 
                 const href = a.getAttribute('href');
                 if (href) {
                     const fileName = href.split('/').pop();
                     a.setAttribute('download', fileName || 'download');
                 }
            }
        });
    });

    return doc.body.innerHTML;
});

const closeAnnouncement = () => {
    isAnnouncementClosed.value = true;
    localStorage.setItem('lastAnnouncementTimestamp', Date.now().toString());
};

const showHelpAnnouncement = async () => {
    if (activeAnnouncements.value.length === 0) {
        await fetchActiveAnnouncement();
        if (activeAnnouncements.value.length > 0) {
            isAnnouncementClosed.value = false;
        }
    } else {
        isAnnouncementClosed.value = false;
        startAnnouncementSlide();
    }
};

const fetchActiveAnnouncement = async () => {
    try {
        let unit = selectedRoleObj.value?.unit || '';
        const response = await axios.get('/api/news', { params: { category: 'Announcement', unit: unit, per_page: 5 } });
        let news = response.data.data || [];
        
        const now = new Date();
        news = news.filter(item => {
            if (item.end_time) {
                const expiry = new Date(item.end_time);
                if (item.end_time.length === 10) expiry.setHours(23, 59, 59, 999);
                return expiry >= now;
            }
            return true;
        });

        if (news.length > 0) {
            activeAnnouncements.value = news;
            currentAnnouncementIndex.value = 0;
            startAnnouncementSlide();
            
            const lastShown = localStorage.getItem('lastAnnouncementTimestamp');
            if (!lastShown || Date.now() - parseInt(lastShown) >= 2 * 60 * 60 * 1000) {
                isAnnouncementClosed.value = false;
            } else {
                isAnnouncementClosed.value = true;
            }
        } else {
            activeAnnouncements.value = [];
        }
    } catch (e) {
        console.error("Failed to fetch announcement news", e);
    }
};

const startAnnouncementSlide = () => {
    if (announcementSlideInterval) clearInterval(announcementSlideInterval);
    if (activeAnnouncements.value.length > 1) {
        announcementSlideInterval = setInterval(() => {
            nextAnnouncement();
        }, 10000); // 10 seconds per slide
    }
};

const pauseAnnouncementSlide = () => {
    if (announcementSlideInterval) clearInterval(announcementSlideInterval);
};

const nextAnnouncement = () => {
    if (activeAnnouncements.value.length > 1) {
        currentAnnouncementIndex.value = (currentAnnouncementIndex.value + 1) % activeAnnouncements.value.length;
    }
};

const prevAnnouncement = () => {
    if (activeAnnouncements.value.length > 1) {
        currentAnnouncementIndex.value = (currentAnnouncementIndex.value - 1 + activeAnnouncements.value.length) % activeAnnouncements.value.length;
    }
};

watch(currentLayout, (newLayout) => {
    if (newLayout === 'agent') {
        fetchActiveAnnouncement();
        if (announcementAutoPopInterval) clearInterval(announcementAutoPopInterval);
        announcementAutoPopInterval = setInterval(() => {
            if (activeAnnouncements.value.length > 0 && isAnnouncementClosed.value) {
                const lastShown = localStorage.getItem('lastAnnouncementTimestamp');
                if (!lastShown || Date.now() - parseInt(lastShown) >= 2 * 60 * 60 * 1000) {
                    isAnnouncementClosed.value = false;
                }
            }
        }, 60000); // Check every minute
    } else {
        if (announcementAutoPopInterval) {
            clearInterval(announcementAutoPopInterval);
            announcementAutoPopInterval = null;
        }
    }
}, { immediate: true });

// --- Menu News Banner/Carousel (Task 2) ---
// (menuNewsImages, menuNewsCarouselIndices, menuNewsIntervals moved to top of setup to avoid TDZ)

// Fetch news images for all menus (1-15) secara terarah dan instan
async function fetchMenuNewsImages() {
    try {
        let unit = selectedRoleObj.value?.unit || '';

        // Kumpulkan kategori Menu 1 - Menu 15 dan sub-menunya saja agar query terarah
        const targetCategories = [];
        for (let n = 1; n <= 15; n++) {
            const menuKey = `Menu ${n}`;
            targetCategories.push(menuKey);
            targetCategories.push(`Menu${n}`);
            if (portalSettings.value) {
                const subMenus = portalSettings.value.filter(s => s.category === menuKey || s.category === `Menu ${unit} ${n}`);
                subMenus.forEach(s => {
                    if (s.title && !targetCategories.includes(s.title)) {
                        targetCategories.push(s.title);
                    }
                });
            }
        }

        const params = {
            unit,
            categories: JSON.stringify(targetCategories),
            only_first_image: 1,
            per_page: 100
        };

        const response = await axios.get('/api/news', { params });
        let allNews = response.data.data || [];

        // Filter news yang sudah expired
        const now = new Date();
        allNews = allNews.filter(item => {
            if (item.end_time) {
                const expiry = new Date(item.end_time);
                if (item.end_time.length === 10) expiry.setHours(23, 59, 59, 999);
                return expiry >= now;
            }
            return true;
        });

        // Kelompokkan berdasarkan kategori Menu 1 - Menu 15 (case-insensitive)
        const result = {};
        for (let n = 1; n <= 15; n++) {
            const menuKey = `Menu ${n}`;
            const catNews = allNews.filter(item => {
                const cat = (item.category || '').trim().toLowerCase();
                if (cat === `menu ${n}` || cat === `menu${n}`) return true;
                const subMenus = portalSettings.value ? portalSettings.value.filter(s => s.category === menuKey || s.category === `Menu ${unit} ${n}`) : [];
                return subMenus.some(sub => (sub.title || '').trim().toLowerCase() === cat);
            });

            if (catNews.length === 0) continue;

            const images = [];
            for (const item of catNews) {
                let img = null;
                // Prioritaskan file attachment (path storage)
                if (item.attachment) {
                    img = `/storage/${item.attachment}`;
                } else if (item.first_content_image) {
                    img = item.first_content_image;
                } else if (item.content) {
                    img = extractFirstImageFromContent(item.content);
                }

                if (img) {
                    images.push({ img, title: item.title, id: item.id, category: (item.category || '').trim().toLowerCase() });
                }
            }
            if (images.length > 0) result[menuKey] = images;
        }

        menuNewsImages.value = result;

        // Start timer carousel per menu
        Object.keys(result).forEach(key => {
            menuNewsCarouselIndices.value[key] = 0;
            if (result[key].length > 1) {
                if (menuNewsIntervals[key]) clearInterval(menuNewsIntervals[key]);
                menuNewsIntervals[key] = setInterval(() => {
                    const imgs = menuNewsImages.value[key];
                    if (imgs && imgs.length > 1) {
                        menuNewsCarouselIndices.value[key] = (menuNewsCarouselIndices.value[key] || 0) + 1;
                    }
                }, 5000);
            }
        });
    } catch (e) {
        console.error('[MenuNews] Gagal fetch:', e);
    }
};

const isSearchDropdownOpen = ref(false);
const isOmnisearchOpen = ref(false);

const handleGlobalKeydown = (e) => {
    // Open Omnisearch on Ctrl + K or Cmd + K
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
        e.preventDefault();
        isOmnisearchOpen.value = !isOmnisearchOpen.value;
    }
};

const openOmnisearch = () => {
    isOmnisearchOpen.value = true;
};

const submitAgentSearch = () => {
    const query = searchQuery.value.trim();
    if (query) {
        fetchAgentNews(null, query, 1).then(() => {
            isSearchDropdownOpen.value = true;
        });
    } else {
        isSearchDropdownOpen.value = false;
    }
};

const handleAgentSearch = debounce(() => {
    submitAgentSearch();
}, 350);

// Fetch specifically for Carousel (filtered to Promo only)
async function fetchCarouselNews() {
    try {
        let unit = '';
        if (selectedRoleObj.value && selectedRoleObj.value.unit) {
            unit = selectedRoleObj.value.unit;
        }

        // 1. Start with standard and literal categories
        let searchCategories = ['Promo', 'Promo & Program', 'Menu 7'];

        // 2. Add categories from Menu 7 (Carousel) portal settings
        if (portalSettings.value) {
            const menu7Items = portalSettings.value.filter(s => 
                (s.category === 'Menu 7' || (unit && s.category === `Menu ${unit.toUpperCase()} 7`)) && s.is_active
            );
            menu7Items.forEach(item => {
                if (item.title && !searchCategories.includes(item.title)) {
                    searchCategories.push(item.title);
                }
            });
        }
        
        const params = { 
            unit: unit, 
            categories: JSON.stringify(searchCategories), // For enhanced backend
            category: 'Promo', // Fallback
            status: 'Aktif',
            sort_column: 'end_time',
            sort_order: 'asc',
            only_first_image: 1,
            per_page: 20 
        };

        const response = await axios.get('/api/news', { params });
        let news = response.data.data;

        // Apply local PC time filter for EndTime
        const now = new Date();
        carouselNews.value = news.filter(item => {
            // Category Check
            const matchesCategory = searchCategories.includes(item.category) || 
                                    item.category === 'Promo' || 
                                    item.category === 'Menu 7';
            if (!matchesCategory) return false;

            // Expiry Check (EndTime)
            if (item.end_time) {
                const expiry = new Date(item.end_time);
                // If it's just a date (YYYY-MM-DD), make it valid until the end of that day
                if (item.end_time.length === 10) {
                    expiry.setHours(23, 59, 59, 999);
                }
                if (expiry < now) return false;
            }
            return true;
        });
        
        if (carouselNews.value.length > 0) {
            startNewsCarousel();
        }
        // Also fetch news images for all other menus (1-15)
        fetchMenuNewsImages();
    } catch (e) {
        console.error("Failed to fetch carousel news", e);
    }
};

// Fetch for List Modal (Dynamic Category)
async function fetchAgentNews(category = '', search = '', page = 1) {
    const isSearching = !!(search && String(search).trim() !== '');

    if (!isSearching && !category) {
        category = selectedPortalCategory.value;
    }

    // Clear out 'Search Results: ...' from category if it was set previously
    if (typeof category === 'string' && category.startsWith('Search Results:')) {
        category = '';
    }

    try {
        let unit = '';
        if (selectedRoleObj.value && selectedRoleObj.value.unit) {
            unit = selectedRoleObj.value.unit;
        }

        // If searching for Promo, also include Menu 7/10 titles (only when NOT searching)
        const isPromo = !isSearching && (!category || category === 'Promo' || category === 'Promo & Program' || category === 'Promo / Program' || (typeof category === 'string' && category.includes('Promo')) || category === 'Menu 7');
        let searchCategories = null;
        if (!isSearching) {
            searchCategories = [category || 'Promo'];
            if (isPromo) {
                searchCategories = ['Promo', 'Promo & Program', 'Menu 7'];
                // Also include Menu 7 titles
                if (portalSettings.value) {
                    portalSettings.value.filter(s => s.category.includes('7')).forEach(s => searchCategories.push(s.title));
                }
            }
        }
        
        const params = { 
            unit: unit, 
            status: 'Aktif',
            search: isSearching ? String(search).trim() : null,
            page: page,
            per_page: isSearching ? 10 : (agentNewsPagination.value.per_page === 'All' ? 999999 : (agentNewsPagination.value.per_page || 10)),
            exclude_content: 1
        };

        if (!isSearching) {
            params.category = isPromo ? 'Promo' : (category || null);
            if (searchCategories) {
                params.categories = JSON.stringify(searchCategories);
            }
            params.sort_column = isPromo ? 'end_time' : 'created_at';
            params.sort_order = isPromo ? 'asc' : 'desc';
        } else {
            params.sort_column = 'created_at';
            params.sort_order = 'desc';
        }

        const response = await axios.get('/api/news', { params });
        let news = response.data.data;

        // Apply local PC time filter for EndTime
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        news = news.filter(item => {
            if (item.end_time) {
                const expiry = new Date(item.end_time);
                if (item.end_time.length === 10) {
                    expiry.setHours(23, 59, 59, 999);
                }
                return expiry >= now;
            }
            return true;
        });

        // FRONTEND FILTER: Exact match to prevent "Menu 1" matching "Menu 14" (only when NOT searching)
        if (!isSearching && category) {
            // If it's a Promo category, allow any equivalent
            if (isPromo) {
                news = news.filter(item => (searchCategories && searchCategories.includes(item.category)) || (item.category && item.category.includes('Promo')));
            } else {
                // Strict exact match for everything else
                news = news.filter(item => item.category === category);
            }
        }

        agentNews.value = news;
        agentNewsPagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            per_page: params.per_page === 999999 ? 'All' : response.data.per_page,
        };
    } catch (e) {
        console.error("Failed to fetch agent news", e);
    }
};

const handlePortalItemClick = async (item) => {
    if (!item) return;
    
    // 1. If it's a real external URL, open in new tab
    const url = (item.url || '').trim();
    if (url && url !== '#' && (url.startsWith('http') || url.startsWith('www') || url.startsWith('/') || (url.includes('.') && !url.includes(' ')) )) {
        // TRACK MENU CLICK before opening external URL
        const sectionName = item.section || (item.title && item.title.startsWith('Menu ') ? item.title : null);
        if (sectionName && sectionName.startsWith('Menu ')) {
            const curUnit = selectedRoleObj.value?.unit || 'Global';
            axios.post('/api/dashboard/track-click', {
                menu_name: sectionName,
                menu_title: item.title || sectionName,
                unit_name: curUnit
            }).catch(err => console.error("Tracking error:", err));
        }

        let finalUrl = url;
        if (url.startsWith('www')) finalUrl = 'http://' + url;
        window.open(finalUrl, '_blank');
        return;
    }

    // 4. Check for Children (Sub-Menus)
    const children = (portalSettings.value || []).filter(s => s.parent_id === item.id && s.is_active);
    
    // Filter Children by Unit Access as well to prevent unauthorized viewing
    const visibleChildren = children.filter(s => {
         // Re-use logic or strict check? Simple check for now:
         if (!s.target_unit) return true;
         let t = [];
         try { t = (typeof s.target_unit === 'string') ? JSON.parse(s.target_unit) : s.target_unit; } catch(e){ t=[]; }
         if (!Array.isArray(t) || t.length === 0) return true; // Empty = Global
         if (t.includes('All Layanan')) return true;
         if (selectedRoleObj.value?.unit && t.includes(selectedRoleObj.value.unit)) return true;
         return false;
    });

    if (visibleChildren.length > 0) {
        submenuItems.value = visibleChildren.sort((a,b) => (a.order_no||0) - (b.order_no||0));
        submenuParentTitle.value = item.title;
        isSubMenuModalOpen.value = true;
        return;
    }

    // TRACK MENU CLICK for opening news category content list
    const sectionName = item.section || (item.title && item.title.startsWith('Menu ') ? item.title : null);
    if (sectionName && sectionName.startsWith('Menu ')) {
        const curUnit = selectedRoleObj.value?.unit || 'Global';
        axios.post('/api/dashboard/track-click', {
            menu_name: sectionName,
            menu_title: item.title || sectionName,
            unit_name: curUnit
        }).catch(err => console.error("Tracking error:", err));
    }

    // 5. Otherwise, treat as a "News Category" shortcut
    selectedPortalCategory.value = item.title;
    
    // Stop carousel while viewing specific list
    if (newsCarouselInterval) clearInterval(newsCarouselInterval);
    
    await fetchAgentNews(item.title);
    
    // Always open the list modal
    isNewsListModalOpen.value = true;

    // If there's at least one news item, also open the "View Data" popup for the first one
    if (agentNews.value.length > 0) {
        viewAgentNews(agentNews.value[0]);
    } else {
        showNotification(`No content found for category '${item.title}'. Please add News/Data with this category name.`, 'error');
    }
};

// --- Submenu Logic ---
const isSubMenuModalOpen = ref(false);
const submenuItems = ref([]);
const submenuParentTitle = ref('');
const expandedSubmenuIds = ref([]); // Track expanded items inside the modal

const handleSubMenuClick = (sub) => {
     // Check for deeper children
     const deepChildren = (portalSettings.value || []).filter(s => s.parent_id === sub.id && s.is_active);
     
     if (deepChildren.length > 0) {
          // Toggle Accordion Logic
          if (expandedSubmenuIds.value.includes(sub.id)) {
               expandedSubmenuIds.value = expandedSubmenuIds.value.filter(id => id !== sub.id);
          } else {
               expandedSubmenuIds.value.push(sub.id);
          }
     } else {
          // Standard Click Action
          // isSubMenuModalOpen.value = false; // KEEP OPEN
          setTimeout(() => {
              handlePortalItemClick(sub); // Navigate or Open News
          }, 100);
     }
};

const getDeepChildren = (parentId) => {
    const parent = (portalSettings.value || []).find(s => s.id === parentId);
    let children = (portalSettings.value || []).filter(s => s.parent_id === parentId && s.is_active);
    
    if (parent && (parent.title === 'Aplikasi Internal' || parent.title === 'Aplikasi Eksternal')) {
        return children.sort((a, b) => a.title.localeCompare(b.title));
    }
    return children.sort((a,b) => (a.order_no||0) - (b.order_no||0));
};

function startNewsCarousel() {
    if (newsCarouselInterval) clearInterval(newsCarouselInterval);
    currentNewsIndex.value = 0;
    newsCarouselInterval = setInterval(() => {
        if (!isDragging.value && carouselNews.value.length > 0) {
            currentNewsIndex.value = (currentNewsIndex.value + 1) % carouselNews.value.length;
        }
    }, 5000); // 5 seconds
};

// --- Drag Logic ---
const isDragging = ref(false);
const wasDragging = ref(false); // Flag to differentiate click vs drag
const startX = ref(0);

const onDragStart = (e) => {
    isDragging.value = true;
    wasDragging.value = false;
    startX.value = e.pageX || e.touches[0].pageX;
};

const onDragMove = (e) => {
    if (!isDragging.value) return;
    // Just tracking movement 
};

const onDragEnd = (e) => {
    if (!isDragging.value) return;
    const endX = e.pageX || e.changedTouches[0].pageX;
    const diff = startX.value - endX;
    
    // Threshold for drag vs click
    // Threshold for drag vs click
    isDragging.value = false; // Stop dragging state immediately

    if (Math.abs(diff) > 10) { 
        wasDragging.value = true;
        
        if (diff > 0) {
             // Next
             currentNewsIndex.value = (currentNewsIndex.value + 1) % carouselNews.value.length;
        } else {
             // Prev
             currentNewsIndex.value = (currentNewsIndex.value - 1 + carouselNews.value.length) % carouselNews.value.length;
        }
    } else {
         // It was a click. Let handleBannerClick process it.
         wasDragging.value = false;
    }
    
    // Reset after delay
    setTimeout(() => { wasDragging.value = false; }, 200); 
};

const openPromoList = async () => {
    // Explicitly fetch Promo & Program for the list view
    selectedPortalCategory.value = 'Promo / Program';
    
    // Stop carousel temporarily
    if (newsCarouselInterval) clearInterval(newsCarouselInterval);
    
    agentNewsPagination.value.current_page = 1;
    await fetchAgentNews('Promo', '', 1);
    isNewsListModalOpen.value = true;
};

const handleBannerClick = (item = null) => {
    // Backup handler
    if (!wasDragging.value) {
        // Debounce slightly to avoid double open if onDragEnd also triggered it
        if (!isViewNewsModalOpen.value) {
             if (item) {
                 viewAgentNews(item);
             } else if (carouselNews.value && carouselNews.value[currentNewsIndex.value]) {
                viewAgentNews(carouselNews.value[currentNewsIndex.value]);
             }
        }
    }
};

const viewAgentNews = async (item) => {
    if (!item) return;

    // Fetch full data if content was excluded for performance
    if (item.content === undefined || item.content === null) {
        try {
            const res = await axios.get(`/api/news/${item.id}`);
            item = res.data;
        } catch (e) {
            console.error("Failed to load full news data", e);
        }
    }
    
    // Parse and auto-upgrade view content (Same logic as Admin View)
    let processedContent = item.content || '';
    if (processedContent) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(processedContent, 'text/html');
        
        // 1. Upgrade Download Buttons (Teal Square Icon)
        const downloadLinks = doc.querySelectorAll('table.cxc-attachment-table a');
        downloadLinks.forEach(a => {
            const isActionCell = a.closest('td')?.cellIndex === 3;
            if (isActionCell || a.innerText.trim().toLowerCase().includes('download') || a.querySelector('img[src*="icon-download"]')) {
                 a.innerHTML = `<img src="/storage/portal/icon-download.png" style="width: 32px; height: 32px; display: block; border: none; object-fit: contain;" alt="Download">`;
                 a.style.backgroundColor = 'transparent';
                 a.style.color = 'transparent';
                 a.style.fontSize = '0';
                 a.style.lineHeight = '0';
                 a.style.padding = '0';
                 a.style.borderRadius = '6px';
                 a.style.marginRight = '4px';
                 a.style.width = '32px';
                 a.style.height = '32px';
                 a.style.display = 'inline-flex';
                 a.style.alignItems = 'center';
                 a.style.justifyContent = 'center';
                 a.style.textDecoration = 'none';
                 a.style.border = 'none';
                 a.setAttribute('title', 'Download');
                 
                 const href = a.getAttribute('href');
                 if (href) {
                     const fileName = href.split('/').pop();
                     a.setAttribute('download', fileName || 'download');
                     a.setAttribute('target', '_self'); // Force same tab for download trigger
                 }
            }
        });

        // 3. Robust link handling and cleaning for Agents
        const allLinks = doc.querySelectorAll('a');
        allLinks.forEach(a => {
            let href = a.getAttribute('href');
            if (href) {
                // If it's an absolute URL pointing to our storage, make it relative to root
                if (href.includes('/storage/')) {
                    const parts = href.split('/storage/');
                    href = '/storage/' + parts[1];
                    a.setAttribute('href', href);
                }

                const ext = href.split('.').pop().toLowerCase().split(/[#?]/)[0];
                const previewable = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'txt'];
                const isDownloadBtn = a.innerText.trim() === 'Download' || a.querySelector('.fa-download') || a.querySelector('.fas.fa-download');

                // If not previewable OR if it's explicitly a download button, add download attribute
                if (!previewable.includes(ext) || isDownloadBtn) {
                    const fileName = href.split('/').pop();
                    a.setAttribute('download', fileName || 'download');
                } else {
                    // For previewable files, ensure they open in new tab for preview
                    a.setAttribute('target', '_blank');
                    a.setAttribute('rel', 'noopener noreferrer');
                }

                if (href.startsWith('http')) {
                    a.setAttribute('target', '_blank');
                    a.setAttribute('rel', 'noopener noreferrer');
                }
            }
        });

        // 4. Specifically clean up attachment table for Agents
        const removeBtns = doc.querySelectorAll('.cxc-remove-btn');
        removeBtns.forEach(btn => btn.remove());
        
        // Optionally rename 'Action' column to 'Download' for clarity
        const actionHeaders = doc.querySelectorAll('table.cxc-attachment-table th');
        actionHeaders.forEach(th => {
            if (th.innerText === 'Action') th.innerText = 'Download';
        });

        processedContent = doc.body.innerHTML;
    }

    const firstContentImage = !item.attachment ? extractFirstImageFromContent(item.content) : null;
    // If we use the content image as header, remove it from the rendered body to avoid duplicate
    if (firstContentImage) {
        processedContent = removeFirstImageFromContent(processedContent);
    }

    viewNewsData.value = {
        ...item,
        content: processedContent,
        firstContentImage
    };
    isViewNewsModalOpen.value = true;
};

// Helper: extract first <img> src from HTML content (supports base64 and URL)
const extractFirstImageFromContent = (html) => {
    if (!html) return null;
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const img = doc.querySelector('img[src]');
        return img ? img.getAttribute('src') : null;
    } catch (e) {
        return null;
    }
};

// Helper: remove first <img> (and its wrapping <p>/<figure> if empty) from HTML content
const removeFirstImageFromContent = (html) => {
    if (!html) return html;
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const img = doc.querySelector('img[src]');
        if (img) {
            // Remove wrapping figure or paragraph if it becomes empty
            const parent = img.closest('figure, p');
            if (parent) {
                // Remove the img first
                img.remove();
                // If parent is now visually empty, remove it too
                if (!parent.textContent.trim() && !parent.querySelector('img, table, iframe, video')) {
                    parent.remove();
                }
            } else {
                img.remove();
            }
        }
        return doc.body.innerHTML;
    } catch (e) {
        return html;
    }
};

// Helper: get the best thumbnail for a news item (attachment > first content image > null)
const getNewsImage = (item) => {
    if (!item) return null;
    if (item.attachment) return `/storage/${item.attachment}`;
    if (item.first_content_image) return item.first_content_image;
    return extractFirstImageFromContent(item.content);
};

// --- Agent News List Logic ---
const isNewsListModalOpen = ref(false);

const openNewsListModal = async () => {
    isViewNewsModalOpen.value = false;
    
    // Auto-filter list based on the category of the item we were just viewing
    if (viewNewsData.value && viewNewsData.value.category) {
        const cat = viewNewsData.value.category;
        selectedPortalCategory.value = cat;
        await fetchAgentNews(cat);
    }
    
    isNewsListModalOpen.value = true;
};

const selectNewsFromList = (item) => {
    // isNewsListModalOpen.value = false; // Keep list open or close? Usually close if viewing detail
    // User flow: List -> Detail. 
    // Actually, maybe we want to keep list open in background? 
    // The current implementation closes list. Let's stick to closing it.
    isNewsListModalOpen.value = false;
    viewAgentNews(item);
};



const onPasteContent = (event) => {
    const items = (event.clipboardData || event.originalEvent.clipboardData).items;
    for (const item of items) {
        if (item.kind === 'file' && item.type.startsWith('image/')) {
            event.preventDefault();
            const blob = item.getAsFile();
            const reader = new FileReader();
            reader.onload = (e) => {
                document.execCommand('insertImage', false, e.target.result);
            };
            reader.readAsDataURL(blob);
            return; // Handle only the first image found
        }
    }
};

const onDropContent = (event) => {
    event.preventDefault();
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        for (const file of files) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.execCommand('insertImage', false, e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    }
};

const loadingLogout = ref(false);
const isLogoutModalOpen = ref(false);

const logout = () => {
    isLogoutModalOpen.value = true;
};

const reloadPage = () => {
    window.location.reload();
};

const confirmLogout = async () => {
    loadingLogout.value = true;
    try {
        await axios.post('/api/logout', {
            user_id: currentUser.value?.id
        });
        
        // Clear persistence
        localStorage.removeItem('lastMenu');
        localStorage.removeItem('lastSubmenu');
        localStorage.removeItem('selectedRole');
        localStorage.removeItem('userFormState');
        localStorage.removeItem('unitFormState');
        localStorage.removeItem('portalSettingsCache');

        // Reset local state
        selectedRoleObj.value = null;
        selectedRole.value = null;

        router.push('/');
    } catch (error) {
        console.error('Logout failed', error);
        router.push('/'); // Force redirect anyway
    } finally {
        loadingLogout.value = false;
        isLogoutModalOpen.value = false;
    }
};

const isAdminResetPasswordModalOpen = ref(false);
const adminResetData = ref({ userId: null, npp: '', name: '', password: '', confirmPassword: '' });

const openAdminResetModal = (user) => {
    openGlobalDelete(
        'Reset Password User',
        `Apakah Anda yakin ingin mereset password untuk user <strong>${user.npp ? user.npp + ' - ' : ''}${user.name}</strong> ke password default <strong>"1"</strong>?`,
        async () => {
            try {
                await axios.post('/api/users/reset-password', {
                    user_id: user.id,
                    password: '1'
                });
                showNotification('Password berhasil direset menjadi "1"', 'success');
            } catch (e) {
                showNotification('Gagal reset password: ' + (e.response?.data?.message || e.message), 'error');
                throw e;
            }
        },
        'Reset',
        'warning',
        'fas fa-key',
        'bg-amber-600 hover:bg-amber-700 shadow-amber-200'
    );
};

const submitAdminResetPassword = async () => {};

const confirmResetSession = (user) => {
    openGlobalDelete(
        'Reset Session User',
        `Apakah Anda yakin ingin mereset session untuk user <strong>${user.npp ? user.npp + ' - ' : ''}${user.name}</strong>? Ini akan mengeluarkan user jika sedang login.`,
        async () => {
            try {
                await axios.post('/api/users/reset-session', { user_id: user.id });
                showNotification('Session user berhasil direset', 'success');
            } catch (e) {
                showNotification('Gagal reset session: ' + (e.response?.data?.message || e.message), 'error');
                throw e;
            }
        },
        'Reset',
        'danger',
        'fas fa-sign-out-alt',
        'bg-rose-600 hover:bg-rose-700 shadow-rose-200'
    );
};

// Modal Logic (Moved to top)
const notification = ref({ isOpen: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    let translatedMessage = message;
    
    // Melakukan penerjemahan untuk pesan error/sukses bahasa inggris yang umum
    if (typeof translatedMessage === 'string') {
        translatedMessage = translatedMessage
            .replace(/Error saving user:/i, 'Gagal menyimpan user:')
            .replace(/The npp and email have already been taken/i, 'NPP dan Email sudah terdaftar')
            .replace(/The username has already been taken/i, 'NPP sudah terdaftar')
            .replace(/The npp has already been taken \(User exists\)\./i, 'NPP sudah terdaftar')
            .replace(/The npp has already been taken/i, 'NPP sudah terdaftar')
            .replace(/The email has already been taken/i, 'Email sudah terdaftar')
            .replace(/User ID is missing for update/i, 'ID User tidak ditemukan untuk pembaruan')
            .replace(/Passwords do not match/i, 'Konfirmasi password tidak cocok')
            .replace(/Password changed successfully/i, 'Password berhasil diubah')
            .replace(/Failed to delete users/i, 'Gagal menghapus user')
            .replace(/Users deleted successfully/i, 'User berhasil dihapus')
            .replace(/Failed to change password/i, 'Gagal mengubah password')
            .replace(/No content found for category/i, 'Konten tidak ditemukan untuk kategori')
            .replace(/Please add News\/Data with this category name/i, 'Harap tambahkan Berita/Data dengan nama kategori ini')
            .replace(/Added successfully/i, 'Berhasil ditambahkan')
            .replace(/Updated successfully/i, 'Berhasil diperbarui')
            .replace(/Settings updated successfully/i, 'Pengaturan berhasil diperbarui')
            .replace(/Failed to save settings/i, 'Gagal menyimpan pengaturan')
            .replace(/Failed to update layout/i, 'Gagal memperbarui layout')
            .replace(/Failed to update background/i, 'Gagal memperbarui background');
    }

    notification.value = { isOpen: true, message: translatedMessage, type };
    setTimeout(() => {
        notification.value.isOpen = false;
    }, 3000);
};

const resetUserForm = () => {
    formUser.value.data = { npp: '', name: '', email: '', username: '', password: '', isActive: 'Yes', roles: [] };
    formErrors.value = { npp: '', name: '', email: '', roles: '' };
    generalFormError.value = '';
};

const openAddModal = () => {
    modalMode.value = 'add';
    resetUserForm();
    isModalOpen.value = true;
};

// Row Action Helpers
const handleEditUser = (user) => {
    selectedUsers.value = [user.id];
    openUpdateModal();
};

const handleDeleteUser = (user) => {
    selectedUsers.value = [user.id];
    deleteSelected();
};

const handleEditUnit = (unit) => {
    selectedUnits.value = [unit.id];
    openUpdateUnitModal();
};

const handleDeleteUnit = (unit) => {
    selectedUnits.value = [unit.id];
    deleteSelectedUnits();
};

const openUpdateModal = () => {
    formErrors.value = { npp: '', name: '', email: '', roles: '' }; // Reset errors
    generalFormError.value = '';
    if (selectedUsers.value.length !== 1) {
        showNotification('Harap pilih user yang ingin diupdate', 'error');
        return;
    }
    
    // Find the selected user data
    const user = usersData.value.find(u => u.id === selectedUsers.value[0]);
    if (user) {
        modalMode.value = 'edit';
        formUser.value.data = {
            id: user.id, // Include ID for update
            npp: user.npp,
            name: user.name,
            email: user.email, 
            username: user.npp, 
            password: '', 
            isActive: user.active,
            roles: user.raw_roles || [], 
        };
        isModalOpen.value = true;
    }
};

const openViewUserModal = () => {
    formErrors.value = { npp: '', name: '', email: '', roles: '' };
    generalFormError.value = '';
    if (selectedUsers.value.length !== 1) {
        showNotification('Harap pilih satu user yang ingin dilihat', 'error');
        return;
    }
    const user = usersData.value.find(u => u.id === selectedUsers.value[0]);
    if (user) {
        modalMode.value = 'view';
        formUser.value.data = {
            id: user.id,
            npp: user.npp,
            name: user.name,
            email: user.email, 
            username: user.npp, 
            password: '', 
            isActive: user.active,
            roles: user.raw_roles || [], 
        };
        isModalOpen.value = true;
    }
};

const saveRole = () => {
    roleErrorMessage.value = ''; // Reset error
    
    if (!formRole.value.rolename || !formRole.value.unitname) {
        roleErrorMessage.value = 'Please fill all fields';
        return;
    }

    // Check for duplicate Unit ID
    const isDuplicate = formUser.value.data.roles.some(
        role => role.unitName === formRole.value.unitname
    );

    if (isDuplicate) {
        roleErrorMessage.value = 'Unit ID sudah ditambahkan, Silahkan pilih Unit ID yang lain';
        return;
    }

    formUser.value.data.roles.push({
        roleName: formRole.value.rolename,
        unitName: formRole.value.unitname,
        status: formRole.value.unitname
    });
    
    // Clear validation errors for roles and general error once a role is added
    if (formErrors.value) formErrors.value.roles = '';
    generalFormError.value = '';
    
    isRoleModalOpen.value = false;
};

const removeRole = (index) => {
    formUser.value.data.roles.splice(index, 1);
};

const saveUser = async () => {
    console.log('saveUser called');
    try {
        // Reset errors
        formErrors.value = { npp: '', name: '', email: '', roles: '' };
        generalFormError.value = '';
        let hasError = false;

        // Validate mandatory fields individualy
        if (!formUser.value.data.npp) {
            formErrors.value.npp = 'NPP is required';
            hasError = true;
        }
        if (!formUser.value.data.name) {
            formErrors.value.name = 'Name is required';
            hasError = true;
        }
        if (!formUser.value.data.email) {
            formErrors.value.email = 'Email is required';
            hasError = true;
        }
        if (!formUser.value.data.roles || formUser.value.data.roles.length === 0) {
            formErrors.value.roles = 'Set Role minimal pilih 1';
            hasError = true;
        }

        if (hasError) {
            console.log('Validation failed');
            generalFormError.value = 'Mohon lengkapi data yang wajib diisi';
            return;
        }

        // Set username from npp
        formUser.value.data.username = formUser.value.data.npp;

        if (modalMode.value === 'add') {
             // Set default password to 1
             formUser.value.data.password = '1';
             await axios.post('/api/users', formUser.value.data);
             fetchUsers(pagination.value.current_page);
             showGlobalStatus('Berhasil', 'User berhasil disimpan', 'success');
        } else {
             // Official Update via API
             if (formUser.value.data.id) {
                 await axios.put(`/api/users/${formUser.value.data.id}`, formUser.value.data);
                 fetchUsers(pagination.value.current_page); // Refresh list
                 showGlobalStatus('Berhasil', 'Berhasil diupdate', 'success');
             } else {
                 throw new Error("User ID is missing for update.");
             }
        }

        isModalOpen.value = false;
        localStorage.removeItem('userFormState'); // Clear storage on success
        resetUserForm(); // Clear form on success so next add is fresh
    } catch (e) {
        console.error('Error in saveUser:', e);
        showGlobalStatus('Error', 'Gagal menyimpan user: ' + (e.response?.data?.message || e.message), 'error');
    }
};

const deleteSelected = () => {
    if (selectedUsers.value.length === 0) {
        showNotification('Harap pilih user yang ingin dihapus', 'error');
        return;
    }
    
    openGlobalDelete(
        'Hapus User',
        `Apakah Anda yakin ingin menghapus ${selectedUsers.value.length} user terpilih? Data akses mereka akan dicabut.`,
        async () => {
            try {
                await axios.delete('/api/users', { data: { ids: selectedUsers.value } });
                fetchUsers(pagination.value.current_page);
                selectedUsers.value = [];
                showGlobalStatus('Berhasil', 'User berhasil dihapus', 'success');
            } catch (error) {
                showGlobalStatus('Error', 'Gagal menghapus user', 'error');
                throw error;
            }
        }
    );
};

const confirmDelete = async () => {
    try {
        const userIds = selectedUsers.value.length > 0 ? selectedUsers.value : [currentSelectedUserId.value];
        await axios.delete('/api/users', { data: { ids: userIds } });
        fetchUsers(pagination.value.current_page);
        selectedUsers.value = [];
        isDeleteModalOpen.value = false;
        showGlobalStatus('Berhasil', 'User berhasil dihapus', 'success');
    } catch (error) {
        showGlobalStatus('Error', 'Gagal menghapus user', 'error');
    }
};

const saveResetPassword = async () => {
    if (newPassword.value !== confirmNewPassword.value) {
        showNotification('Konfirmasi password tidak cocok', 'error');
        return;
    }
    try {
        await axios.post('/api/change-password', { password: newPassword.value });
        isResetPasswordModalOpen.value = false;
        showNotification('Password berhasil diubah', 'success');
        // Clear query param
        router.replace({ query: null });
    } catch (error) {
        let msg = 'Gagal mengubah password';
        if (error.response && error.response.data) {
             if (error.response.data.message) {
                 msg = error.response.data.message;
             }
             // Laravel validation errors
             if (error.response.data.errors && error.response.data.errors.password) {
                 msg = error.response.data.errors.password[0];
             }
        }
        showNotification(msg, 'error');
    }
};

// Watch for submenu changes to fetch news and other data
watch(activeSubmenuName, (newVal) => {
    if (newVal === 'Manage News' || newVal === 'Knowledge') {
        fetchNews();
        if (unitsData.value.length === 0) fetchUnits();
    } else if (newVal === 'Manage Unit') {
        fetchUnits();
    } else if (newVal === 'Manage User') {
        fetchUsers();
    } else if (newVal && newVal.startsWith('Portal Interface')) {
        fetchPortalSettings();
    } else if (newVal === 'Activity Log') {
        fetchActivityLogs(1);
        fetchErrorLogs(1);
    }
});

// --- Manage Unit Logic (State moved to top) --- 

const fetchUnits = async (page = 1) => {
    if (activeSubmenuName.value !== 'Manage Unit') return;
    isLoadingUnits.value = true;
    try {
        const perPageParam = unitPagination.value.per_page === 'All' ? 999999 : (unitPagination.value.per_page || 20);
        const params = { page, per_page: perPageParam, ...unitFilters.value };
        const response = await axios.get('/api/units', { params });
        const sortedData = [...response.data.data].sort((a, b) => (a.name || '').localeCompare(b.name || ''));
        unitsData.value = sortedData;
        unitPagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            per_page: perPageParam === 999999 ? 'All' : response.data.per_page
        };
    } catch (error) {
        console.error('Error fetching units:', error);
        // Fallback dummy data if API fails
        if (page === 1) {
             unitsData.value = [
                { id: 1, type: 'DIVISI', parent_name: 'Bank', code: 'Bank', name: 'Bank', short_name: 'Bank', address: 'Bank', is_active: 'Yes' },
                { id: 2, type: 'DIVISI', parent_name: 'Credit', code: 'Credit', name: 'Credit', short_name: 'Credit', address: 'Credit', is_active: 'Yes' },
                { id: 3, type: 'DIVISI', parent_name: 'Emerald', code: 'Emerald', name: 'Emerald', short_name: 'Emerald', address: 'Emerald', is_active: 'Yes' }
            ];
            unitPagination.value = { current_page: 1, last_page: 1, total: 3, per_page: 10 };
        }
    } finally {
        isLoadingUnits.value = false;
    }
};

const toggleUnitFilter = (key) => showUnitFilters.value[key] = !showUnitFilters.value[key];
const applyUnitFilter = () => fetchUnits(1);
const resetUnitFilters = () => { Object.keys(unitFilters.value).forEach(k => unitFilters.value[k] = ''); fetchUnits(1); };
const changeUnitPage = (page) => { if (page >= 1 && page <= unitPagination.value.last_page) fetchUnits(page); };
const selectAllUnits = () => { selectedUnits.value = unitsData.value.map(u => u.id); };
const deselectAllUnits = () => { selectedUnits.value = []; };

const openAddUnitModal = () => {
    unitModalMode.value = 'add';
    formUnit.value = { id: null, type: 'DIVISI', parentId: '', code: '', name: '', shortName: '', address: '', isActive: 'Yes' };
    isUnitModalOpen.value = true;
};

const openUpdateUnitModal = () => {
    if (selectedUnits.value.length !== 1) {
        showNotification('Harap pilih satu unit yang ingin diupdate', 'error');
        return;
    }
    const unit = unitsData.value.find(u => u.id === selectedUnits.value[0]);
    if (unit) {
        unitModalMode.value = 'edit';
        formUnit.value = {
            id: unit.id,
            type: unit.type || 'DIVISI',
            parentId: unit.parent_id || '', 
            code: unit.code,
            name: unit.name,
            shortName: unit.short_name,
            address: unit.address,
            isActive: unit.is_active || 'Yes'
        };
        isUnitModalOpen.value = true;
    }
};

const openViewUnitModal = () => {
    if (selectedUnits.value.length !== 1) {
        showNotification('Harap pilih satu unit yang ingin dilihat', 'error');
        return;
    }
    const unit = unitsData.value.find(u => u.id === selectedUnits.value[0]);
    if (unit) {
        unitModalMode.value = 'view';
        formUnit.value = {
            id: unit.id,
            type: unit.type || 'DIVISI',
            parentId: unit.parent_id || '', 
            code: unit.code,
            name: unit.name,
            shortName: unit.short_name,
            address: unit.address,
            isActive: unit.is_active || 'Yes'
        };
        isUnitModalOpen.value = true;
    }
};

const saveUnit = async () => {
    try {
        if (unitModalMode.value === 'add') {
            await axios.post('/api/units', formUnit.value);
            fetchUnits(unitPagination.value.current_page);
            fetchParentOptions(); // Refresh dynamic options
            showGlobalStatus('Berhasil', 'Unit berhasil disimpan', 'success');
        } else {
            await axios.put(`/api/units/${formUnit.value.id}`, formUnit.value);
            fetchUnits(unitPagination.value.current_page);
            fetchParentOptions(); // Refresh dynamic options
            showGlobalStatus('Berhasil', 'Unit berhasil diupdate', 'success');
        }
        isUnitModalOpen.value = false;
        localStorage.removeItem('unitFormState'); // Clear storage on success
    } catch (e) {
        console.error('Save Unit Error:', e);
        showGlobalStatus('Error', 'Gagal menyimpan data: ' + (e.response?.data?.message || e.message), 'error');
        // Do not update local state on error, let the user retry
    }
};

const deleteSelectedUnits = () => {
    if (selectedUnits.value.length === 0) {
        showNotification('Harap pilih unit yang ingin dihapus', 'error');
        return;
    }
    
    openGlobalDelete(
        'Hapus Unit',
        `Apakah Anda yakin ingin menghapus ${selectedUnits.value.length} unit terpilih?`,
        async () => {
            try {
                await axios.delete('/api/units', { data: { ids: selectedUnits.value } });
                fetchUnits(unitPagination.value.current_page);
                fetchParentOptions(); // Refresh dynamic options
                selectedUnits.value = [];
                showGlobalStatus('Berhasil', 'Unit berhasil dihapus', 'success');
            } catch (e) {
                showGlobalStatus('Error', 'Gagal menghapus unit', 'error');
                throw e;
            }
        }
    );
};



const unitTypeMap = { 1: 'DIVISI', 2: 'WILAYAH', 3: 'CABANG' };
const getUnitTypeName = (typeId) => unitTypeMap[typeId] || typeId;

// Load parent options dynamically
const fetchParentOptions = async () => {
    try {
        // Fetch all active units for dropdown (simplified)
        const response = await axios.get('/api/units?per_page=100&status=Yes');
        const rawUnits = Array.isArray(response.data) ? response.data : (response.data?.data || []);
        parentOptions.value = rawUnits.map(u => ({ id: u.id, name: u.name }));
    } catch (e) {
        console.error('Failed to fetch parent options', e);
    }
};

const sortedParentOptions = computed(() => {
    const options = [...parentOptions.value];
    if (!options.some(u => u.name === 'Super Admin')) {
        options.push({ id: 'super-admin-unit', name: 'Super Admin' });
    }
    return options.sort((a, b) => a.name.localeCompare(b.name));
});

const availableRoleOptions = computed(() => {
    if (isAdminWithUnit.value) {
        return ['Admin', 'Agent'];
    }
    return ['Super Admin', 'Admin', 'Agent'];
});

const filteredRoleUnitOptions = computed(() => {
    if (isAdminWithUnit.value) {
        const myUnit = selectedRoleObj.value?.unit || '';
        return sortedParentOptions.value.filter(u => u.name === myUnit);
    }
    return sortedParentOptions.value;
});

watch(() => formRole.value.rolename, (newVal) => {
    if (newVal === 'Super Admin') {
        formRole.value.unitname = 'Super Admin';
    }
});

const openRoleModal = () => {
    fetchParentOptions();
    roleErrorMessage.value = ''; // Clear previous errors
    
    const roles = availableRoleOptions.value;
    const units = filteredRoleUnitOptions.value;
    
    formRole.value = { 
        rolename: roles.includes('Admin') ? 'Admin' : (roles.length > 0 ? roles[0] : ''), 
        unitname: units.length > 0 ? units[0].name : '' 
    };
    isRoleModalOpen.value = true;
};

const debouncedFetchUsers = debounce(() => fetchUsers(1), 500);
const debouncedFetchUnits = debounce(() => fetchUnits(1), 500);
const debouncedFetchNews = debounce(() => { if (typeof fetchNews === 'function') fetchNews(1); }, 500);

const selectAllNews = () => { selectedNews.value = newsData.value.map(item => item.id); };
const deselectAllNews = () => { selectedNews.value = []; };

// Ensure auto-slide doesn't get stuck by resuming when all modals are closed or layout switches
watch([isNewsListModalOpen, isSubMenuModalOpen, isViewNewsModalOpen, currentLayout], ([newList, newSub, newView, newLayout]) => {
    if (newLayout === 'agent') {
        if (!newList && !newSub && !newView) {
            if (typeof startNewsCarousel === 'function') startNewsCarousel();
            if (typeof startAutoSlide === 'function') startAutoSlide();
        }
    } else {
        if (typeof menuAutoSlideInterval !== 'undefined' && menuAutoSlideInterval) clearInterval(menuAutoSlideInterval);
        if (typeof newsCarouselInterval !== 'undefined' && newsCarouselInterval) clearInterval(newsCarouselInterval);
    }
});

onMounted(async () => {
    isAppLoading.value = true;
    
    // Global Listeners
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('mousemove', resetIdleTimer);
    window.addEventListener('keydown', resetIdleTimer);
    window.addEventListener('keydown', handleGlobalKeydown);
    resetIdleTimer();

    // Cross-tab Real-time Sync for Portal Settings (Layouts)
    window.addEventListener('storage', (event) => {
        if (event.key === 'portalSettingsCache' && event.newValue) {
            try {
                const { data } = JSON.parse(event.newValue);
                if (data) {
                    portalSettings.value = data;
                    parseLayoutConfig();
                }
            } catch (e) {
                console.error("Failed to sync layout changes", e);
            }
        }
    });

    // (Live session kick removed as per 'First Login Wins' request)
    // RE-ENABLED: Single session enforcement
    const sessionCheckInterval = setInterval(async () => {
        if (currentUser.value && !selectedRoleObj.value?.isPreview) {
            try {
                await axios.get('/api/user');
            } catch (e) {
                // Interceptor handles 401 and redirect
                if (e.response?.status === 401) {
                    clearInterval(sessionCheckInterval);
                }
            }
        }
    }, 30000);

    onUnmounted(() => {
        clearInterval(sessionCheckInterval);
    });

    try {
        // 1. Critical: Identify User
        await fetchCurrentUser();
        
        // 2. Fetch Unit Options early to ensure preview mode has correct data
        await fetchParentOptions();

        // 3. Restore/Check Mode
        if (checkPreviewMode()) {
            isAppLoading.value = false;
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('reset_password') === 'true') {
            isResetPasswordModalOpen.value = true;
        }
        
        initializeActiveState();
        
        // 4. Parallel Data Fetch
        const initialFetchPromises = [fetchPortalSettings()];
        if (activeSubmenuName.value === 'Manage Unit') {
            initialFetchPromises.push(fetchUnits());
        } else if (activeSubmenuName.value === 'Manage News' || activeSubmenuName.value === 'Knowledge') {
            initialFetchPromises.push(fetchNews());
            if (unitsData.value.length === 0) initialFetchPromises.push(fetchUnits());
        } else if (activeSubmenuName.value === 'Manage User') {
            initialFetchPromises.push(fetchUsers());
        } else if (activeSubmenuName.value === 'Activity Log') {
            initialFetchPromises.push(fetchActivityLogs(1));
            initialFetchPromises.push(fetchErrorLogs(1));
        }
        await Promise.all(initialFetchPromises);

        // 5. If agent layout, ensure menu news images are fetched/refreshed
        if (currentLayout.value === 'agent') {
            fetchMenuNewsImages();
            startAutoSlide();
        }
        
    } catch (error) {
        console.error("Initialization error:", error);
    } finally {
        isAppLoading.value = false;
    }
});

// ==========================================
// ACTIVITY LOG & SYSTEM ERROR LOG LOGIC
// ==========================================
const activeLogTab = ref('activity');
const activityLogs = ref([]);
const errorLogs = ref([]);
const isLoadingActivity = ref(false);
const isLoadingError = ref(false);

const activityPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 20
});

const errorPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 20
});

const activityFilters = ref({
    search: '',
    module: '',
    action: '',
    date_from: '',
    date_to: ''
});

const errorFilters = ref({
    search: '',
    error_class: '',
    date_from: '',
    date_to: ''
});

const selectedErrorLog = ref(null);
const showErrorDetailModal = ref(false);
const showDiagnosticsButtons = ref(false);

// Analytical stats
const logStats = ref({
    totalActivity: 0,
    totalError: 0,
    errorToday: 0,
    mostActiveModule: '-'
});

const fetchLogStats = async () => {
    try {
        const resAct = await axios.get('/api/activity-logs', { params: { per_page: 1 } });
        const resErrStats = await axios.get('/api/error-logs/stats');
        
        logStats.value.totalActivity = resAct.data.total || 0;
        logStats.value.totalError = resErrStats.data.totalError || 0;
        logStats.value.unsolvedError = resErrStats.data.unsolvedError || 0;
        logStats.value.solvedError = resErrStats.data.solvedError || 0;
        logStats.value.errorToday = resErrStats.data.errorToday || 0;
    } catch (e) {
        console.error(e);
    }
};

const fetchActivityLogs = async (page = 1) => {
    if (activeSubmenuName.value !== 'Activity Log') return;
    isLoadingActivity.value = true;
    try {
        const perPageParam = activityPagination.value.per_page === 'All' ? 999999 : (activityPagination.value.per_page || 20);
        const response = await axios.get('/api/activity-logs', {
            params: {
                page,
                per_page: perPageParam,
                search: activityFilters.value.search,
                module: activityFilters.value.module,
                action: activityFilters.value.action,
                date_from: activityFilters.value.date_from,
                date_to: activityFilters.value.date_to
            }
        });
        activityLogs.value = response.data.data || [];
        activityPagination.value = {
            current_page: response.data.current_page || 1,
            last_page: response.data.last_page || 1,
            total: response.data.total || 0,
            per_page: perPageParam === 999999 ? 'All' : (response.data.per_page || 20)
        };
        fetchLogStats();
    } catch (error) {
        showNotification('Gagal memuat log aktivitas', 'error');
    } finally {
        isLoadingActivity.value = false;
    }
};

const fetchErrorLogs = async (page = 1) => {
    if (activeSubmenuName.value !== 'Activity Log') return;
    isLoadingError.value = true;
    try {
        const perPageParam = errorPagination.value.per_page === 'All' ? 999999 : (errorPagination.value.per_page || 20);
        const response = await axios.get('/api/error-logs', {
            params: {
                page,
                per_page: perPageParam,
                search: errorFilters.value.search,
                error_class: errorFilters.value.error_class,
                date_from: errorFilters.value.date_from,
                date_to: errorFilters.value.date_to
            }
        });
        errorLogs.value = response.data.data || [];
        errorPagination.value = {
            current_page: response.data.current_page || 1,
            last_page: response.data.last_page || 1,
            total: response.data.total || 0,
            per_page: perPageParam === 999999 ? 'All' : (response.data.per_page || 20)
        };
        fetchLogStats();
    } catch (error) {
        showNotification('Gagal memuat log error sistem', 'error');
    } finally {
        isLoadingError.value = false;
    }
};

const resetActivityFilters = () => {
    activityFilters.value = { search: '', module: '', action: '', date_from: '', date_to: '' };
    fetchActivityLogs(1);
};

const resetErrorFilters = () => {
    errorFilters.value = { search: '', error_class: '', date_from: '', date_to: '' };
    fetchErrorLogs(1);
};

const clearActivityLogs = async () => {
    if (!confirm('PERINGATAN: Apakah Anda yakin ingin menghapus seluruh log aktivitas pengguna secara permanen? Tindakan ini tidak dapat dibatalkan.')) {
        return;
    }
    try {
        await axios.delete('/api/activity-logs/clear');
        showNotification('Semua log aktivitas pengguna berhasil dibersihkan', 'success');
        fetchActivityLogs(1);
    } catch (error) {
        showNotification('Gagal membersihkan log aktivitas', 'error');
    }
};

const clearErrorLogs = async () => {
    if (!confirm('PERINGATAN: Apakah Anda yakin ingin menghapus seluruh log error sistem secara permanen? Tindakan ini tidak dapat dibatalkan.')) {
        return;
    }
    try {
        await axios.delete('/api/error-logs/clear');
        showNotification('Semua log error sistem berhasil dibersihkan', 'success');
        fetchErrorLogs(1);
    } catch (error) {
        showNotification('Gagal membersihkan log error', 'error');
    }
};

const runSecurityScan = async () => {
    showNotification('Menjalankan pemindaian keamanan...', 'info');
    try {
        const response = await axios.post('/api/security/scan');
        const data = response.data;
        if (data.malware_count > 0 || data.quarantine_count > 0) {
            showNotification(`Pemindaian selesai: Terdeteksi ${data.malware_count} ancaman malware dan ${data.quarantine_count} masalah karantina berkas!`, 'error');
        } else {
            showNotification('Pemindaian selesai: Sistem Anda 100% aman dan bersih dari ancaman!', 'success');
        }
        fetchActivityLogs(1);
        fetchErrorLogs(1);
    } catch (error) {
        showNotification('Gagal menjalankan scan keamanan', 'error');
    }
};

const simulateMalware = async () => {
    try {
        const response = await axios.post('/api/security/simulate-malware');
        showNotification(response.data.message, 'success');
        fetchErrorLogs(1);
    } catch (error) {
        showNotification('Gagal melakukan simulasi malware', 'error');
    }
};

const simulateQuarantine = async () => {
    try {
        const response = await axios.post('/api/security/simulate-quarantine');
        showNotification(response.data.message, 'success');
        fetchActivityLogs(1);
    } catch (error) {
        showNotification('Gagal melakukan simulasi karantina', 'error');
    }
};

const toggleErrorStatus = async (log) => {
    if (!log) return;
    const newStatus = log.status === 'Solved' ? 'Error' : 'Solved';
    try {
        const response = await axios.patch(`/api/error-logs/${log.id}/status`, { status: newStatus });
        log.status = newStatus;
        if (selectedErrorLog.value && selectedErrorLog.value.id === log.id) {
            selectedErrorLog.value.status = newStatus;
        }
        showNotification(response.data.message || 'Status log berhasil diubah.', 'success');
        fetchErrorLogs(errorPagination.value.current_page || 1);
    } catch (error) {
        showNotification('Gagal mengubah status log error', 'error');
    }
};

const solveAllErrors = async () => {
    if (!logStats.value.unsolvedError || logStats.value.unsolvedError === 0) {
        showNotification('Semua error sudah berstatus Solved.', 'info');
        return;
    }
    
    openGlobalDelete(
        'Selesaikan Semua Error',
        `Apakah Anda yakin ingin menandai semua error (${logStats.value.unsolvedError} error) menjadi Solved?`,
        async () => {
            try {
                const response = await axios.post('/api/error-logs/solve-all');
                showNotification(response.data.message || 'Semua error berhasil diselesaikan.', 'success');
                fetchErrorLogs(errorPagination.value.current_page || 1);
            } catch (error) {
                showNotification('Gagal menandai semua error menjadi Solved', 'error');
                throw error;
            }
        },
        'Solved All',
        'success',
        'fas fa-check-double'
    );
};

const performXLSXExport = async (type) => {
    const isActivity = type === 'activity';
    const endpoint = isActivity ? '/api/activity-logs/export' : '/api/error-logs/export';
    const params = isActivity ? {
        search: activityFilters.value.search,
        module: activityFilters.value.module,
        action: activityFilters.value.action,
        date_from: activityFilters.value.date_from,
        date_to: activityFilters.value.date_to
    } : {
        search: errorFilters.value.search,
        error_class: errorFilters.value.error_class,
        date_from: errorFilters.value.date_from,
        date_to: errorFilters.value.date_to
    };

    showNotification('Sedang menyiapkan file Excel...', 'info');

    try {
        const response = await axios.get(endpoint, { params });
        const logs = response.data;
        if (!logs || logs.length === 0) {
            showNotification('Tidak ada data untuk diunduh', 'warning');
            return;
        }

        let data = [];
        let fileName = '';

        if (isActivity) {
            fileName = 'Log_Aktivitas_Pengguna_' + new Date().toISOString().slice(0, 10) + '.xlsx';
            data = logs.map((log, index) => ({
                'No': index + 1,
                'Waktu': formatTime(log.created_at),
                'Username': log.username || 'System',
                'Nama Karyawan': log.user?.employee?.name || '-',
                'Modul': log.module,
                'Aksi': log.action,
                'Deskripsi': log.description,
                'IP Address': log.ip_address || '127.0.0.1',
                'User Agent': log.user_agent || '-'
            }));
        } else {
            fileName = 'Log_Error_Sistem_' + new Date().toISOString().slice(0, 10) + '.xlsx';
            data = logs.map((log, index) => ({
                'No': index + 1,
                'Waktu': formatTime(log.created_at),
                'Username': log.username || 'System',
                'Nama Karyawan': log.user?.employee?.name || '-',
                'Tipe Error': log.error_class || '-',
                'Pesan Error': log.error_message || '-',
                'File': log.file || '-',
                'Baris': log.line || '-',
                'Penjelasan': log.explanation || '-',
                'Saran Solusi': log.suggestion || '-',
                'URL': log.url || '-',
                'Method': log.method || '-',
                'IP Address': log.ip_address || '127.0.0.1'
            }));
        }

        const worksheet = window.XLSX.utils.json_to_sheet(data);
        const workbook = window.XLSX.utils.book_new();
        window.XLSX.utils.book_append_sheet(workbook, worksheet, isActivity ? 'Activity Logs' : 'Error Logs');
        
        // Loop through all cells in the worksheet to apply premium styling
        for (const key in worksheet) {
            if (key[0] === '!') continue; // Skip utility keys like !ref, !cols
            
            const cell = worksheet[key];
            const col = key.match(/[A-Z]+/)[0];
            const row = parseInt(key.match(/\d+/)[0], 10);
            
            // Base style config
            cell.s = {
                font: { name: 'Arial', sz: 10, color: { rgb: "334155" } }, // Default slate-700 font color
                border: {
                    top: { style: 'thin', color: { rgb: "CBD5E1" } },
                    bottom: { style: 'thin', color: { rgb: "CBD5E1" } },
                    left: { style: 'thin', color: { rgb: "CBD5E1" } },
                    right: { style: 'thin', color: { rgb: "CBD5E1" } }
                },
                alignment: { vertical: 'center' }
            };
            
            // Header Row 1 Styling
            if (row === 1) {
                cell.s.fill = {
                    patternType: 'solid',
                    fgColor: { rgb: "1E293B" } // Premium, elegant Slate Dark Navy background
                };
                cell.s.font.bold = true;
                cell.s.font.color = { rgb: "FFFFFF" }; // White text
                cell.s.font.sz = 11;
                cell.s.alignment.horizontal = 'center';
            } else {
                // Column A is "No", center-align both column and its content
                if (col === 'A') {
                    cell.s.alignment.horizontal = 'center';
                } else {
                    // Left align text, right align numbers
                    cell.s.alignment.horizontal = typeof cell.v === 'number' ? 'right' : 'left';
                }
            }
        }

        // Auto-fit column widths including header name length
        const maxLens = {};
        data.forEach(row => {
            Object.keys(row).forEach(key => {
                const val = String(row[key] || '');
                maxLens[key] = Math.max(maxLens[key] || key.length || 10, val.length);
            });
        });
        worksheet['!cols'] = Object.keys(maxLens).map(key => ({
            wch: Math.min(maxLens[key] + 3, 60) // Safe padding and column limit
        }));

        window.XLSX.writeFile(workbook, fileName);
        showNotification('Unduhan Excel berhasil dimulai', 'success');
    } catch (error) {
        console.error('Export failed:', error);
        showNotification('Gagal mengunduh file Excel', 'error');
    }
};

const downloadXLSX = (type) => {
    // Force loading xlsx-js-style if standard window.XLSX is loaded
    if (window.XLSX && window.XLSX.write && String(window.XLSX.write).includes('style')) {
        performXLSXExport(type);
    } else {
        if (window.XLSX) {
            try { delete window.XLSX; } catch(e) { window.XLSX = undefined; }
        }
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js';
        script.onload = () => {
            performXLSXExport(type);
        };
        script.onerror = () => {
            showNotification('Gagal memuat pustaka XLSX', 'error');
        };
        document.head.appendChild(script);
    }
};

const viewErrorDetail = (log) => {
    selectedErrorLog.value = log;
    showErrorDetailModal.value = true;
};

const closeErrorDetailModal = () => {
    showErrorDetailModal.value = false;
    selectedErrorLog.value = null;
};

const isCopied = ref(false);
const copyErrorTrace = () => {
    if (!selectedErrorLog.value || !selectedErrorLog.value.trace) return;
    navigator.clipboard.writeText(selectedErrorLog.value.trace)
        .then(() => {
            isCopied.value = true;
            setTimeout(() => { isCopied.value = false; }, 2000);
        })
        .catch(() => {
            showNotification('Gagal menyalin stack trace', 'error');
        });
};

const formatTime = (timeString) => {
    if (!timeString) return '-';
    const date = new Date(timeString);
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    const ss = String(date.getSeconds()).padStart(2, '0');
    return `${y}-${m}-${d} ${hh}:${mm}:${ss}`;
};

// Client-side JS logging configuration
let clientLoggingErrorActive = false;
window.addEventListener('error', (event) => {
    if (clientLoggingErrorActive) return;
    if (event.filename && (event.filename.includes('error-logs/client') || event.filename.includes('node_modules'))) return;

    clientLoggingErrorActive = true;
    axios.post('/api/error-logs/client', {
        message: event.message || 'Unknown browser runtime error',
        file: event.filename ? event.filename.replace(window.location.origin, '') : 'browser_window',
        line: event.lineno || 0,
        stack: event.error ? event.error.stack : null
    }).catch(() => {})
      .finally(() => {
          clientLoggingErrorActive = false;
      });
});

window.addEventListener('unhandledrejection', (event) => {
    if (clientLoggingErrorActive) return;
    clientLoggingErrorActive = true;
    
    let message = 'Unhandled Promise Rejection';
    let stack = null;
    
    if (event.reason) {
        if (event.reason instanceof Error) {
            message = event.reason.message;
            stack = event.reason.stack;
        } else if (typeof event.reason === 'string') {
            message = event.reason;
        } else {
            message = JSON.stringify(event.reason);
        }
    }

    axios.post('/api/error-logs/client', {
        message: message,
        file: 'browser_promise',
        line: 0,
        stack: stack
    }).catch(() => {})
      .finally(() => {
          clientLoggingErrorActive = false;
      });
});

onUnmounted(() => {
    if (menuAutoSlideInterval) clearInterval(menuAutoSlideInterval);
});

</script>

<template>
    <!-- Background Image (Always present in DOM for instant rendering) -->
    <div v-show="currentLayout === 'agent'" 
         class="fixed inset-0 z-0 bg-slate-900 bg-cover bg-center bg-no-repeat transition-opacity duration-700"
         :style="{ backgroundImage: `url(${backgroundUrl})` }">
    </div>

    <!-- Global Loading State -->
    <div v-if="isAppLoading" class="fixed inset-0 z-[100] flex items-center justify-center bg-white/10 backdrop-blur-[2px] font-sans">
        <div class="flex flex-col items-center">
           <svg class="animate-spin h-10 w-10 text-teal-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-gray-600 font-semibold tracking-wide">Loading Portal...</div>
        </div>
    </div>

    <!-- Agent Layout -->
    <div v-if="currentLayout === 'agent'" class="agent-layout-wrapper min-h-screen font-sans text-white relative overflow-hidden flex flex-col transition-all duration-300">
        
        <!-- Floating Help Button -->
        <button @click="showHelpAnnouncement" class="fixed right-6 bottom-6 z-[90] bg-teal-600 hover:bg-teal-500 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:shadow-2xl transition-all transform hover:-translate-y-1 focus:outline-none flex-col group border border-white/20" title="Help / Announcement">
            <i class="fas fa-question text-xl mb-0.5 group-hover:scale-110 transition-transform"></i>
            <span class="text-[9px] font-bold tracking-wide uppercase">Help</span>
        </button>


        <!-- Header (Removed relative background div from here since moved to top) -->

        <!-- Header -->
        <header class="relative z-30 container mx-auto px-6 mt-6 max-w-7xl">
            <div class="grid grid-cols-6 gap-6 items-center">
                <!-- Left: Brand & Nav (3 Cols for ample breathing room) -->
                <div class="col-span-3 flex items-center justify-between pr-2 lg:pr-4">
                    <!-- Brand Logo -->
                    <div class="flex items-center space-x-1.5 sm:space-x-3 select-none flex-shrink-0">
                        <div class="flex items-center space-x-1 sm:space-x-2 text-white">
                            <h1 class="text-white font-black text-xl sm:text-2xl lg:text-3xl tracking-wider uppercase leading-none drop-shadow-md">CXC</h1>
                            <span class="text-white/40 font-bold text-lg sm:text-xl lg:text-2xl leading-none select-none">-</span>
                            <h2 class="text-sky-300 font-extrabold text-lg sm:text-xl lg:text-2xl tracking-wider uppercase leading-none drop-shadow-sm">{{ agentHeaderTitle.replace('CXC ', '') }}</h2>
                        </div>
                        <img :src="agentLogoSrc" alt="CXC Globe" class="h-10 w-10 sm:h-12 w-12 lg:h-14 lg:w-14 object-contain hover:rotate-12 transition-transform duration-300 drop-shadow-[0_4px_12px_rgba(255,255,255,0.2)] flex-shrink-0" />
                    </div>

                    <!-- Nav Icons -->
                    <div class="flex items-center space-x-1 sm:space-x-2 relative flex-shrink-0">
                         <button @click="reloadPage" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center transition border border-white/20 shadow-md" title="Reload Home">
                            <i class="fas fa-home text-white text-sm"></i>
                        </button>
                        <!-- User Name Button -->
                        <button ref="agentProfileButton" @click="toggleAgentDetail" class="flex items-center bg-white/20 hover:bg-white/30 rounded-full px-3 py-1 border border-white/20 shadow-md ml-1 sm:ml-2 h-8 transition cursor-pointer">
                            <i class="fas fa-user text-white text-xs mr-2"></i>
                            <span class="text-xs text-white font-bold uppercase tracking-wide truncate max-w-[50px] sm:max-w-[80px] lg:max-w-[120px]">{{ currentUser?.name || currentUser?.employee?.name || 'User' }}</span>
                        </button>
                        <!-- Logout Button (Sign Off) -->
                        <button @click="logout" class="w-8 h-8 rounded-full bg-white/20 hover:bg-red-500/80 flex items-center justify-center transition border border-white/20 shadow-md ml-1 sm:ml-2" title="Sign Off">
                            <i class="fas fa-sign-out-alt text-white text-xs"></i>
                        </button>

                        <!-- Agent Detail Dropdown (Positioned relative to this container) -->
                        <transition name="fade-slide">
                            <div v-if="isAgentDetailModalOpen" id="agent-detail-dropdown" class="absolute top-10 right-0 z-[100] w-80 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden text-gray-800">
                                <!-- Header -->
                                <div class="bg-teal-600 px-4 py-3 flex justify-between items-center bg-gradient-to-r from-teal-500 to-teal-700">
                                    <h3 class="text-white font-bold text-sm flex items-center">
                                        <i class="fas fa-id-card mr-2"></i> Agent Profile
                                    </h3>
                                    <button @click="isAgentDetailModalOpen = false" class="text-white/80 hover:text-white transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <!-- Body -->
                                <div class="p-4 bg-gray-50">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <div class="h-12 w-12 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 border border-teal-200 shadow-sm shrink-0">
                                            <span class="text-lg font-bold">{{ userInitials }}</span>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h4 class="font-bold text-gray-800 text-sm truncate" :title="agentNameDisplay">{{ agentNameDisplay }}</h4>
                                            <div class="text-[10px] text-teal-600 font-bold uppercase tracking-wider bg-teal-50 px-1.5 py-0.5 rounded inline-block mt-0.5 border border-teal-100">
                                                {{ currentUser?.username || currentUser?.npp || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white rounded border border-gray-100 shadow-sm text-xs divide-y divide-gray-100">
                                        <div class="flex justify-between items-center p-2">
                                            <span class="text-gray-500 font-bold uppercase">Email</span>
                                            <span class="font-medium text-gray-800 truncate pl-2 max-w-[150px]" :title="currentUser?.employee?.email || currentUser?.email">{{ currentUser?.employee?.email || currentUser?.email || '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center p-2">
                                            <span class="text-gray-500 font-bold uppercase">Role ID</span>
                                            <span class="font-medium text-gray-800">{{ selectedRoleObj?.role || '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center p-2">
                                            <span class="text-gray-500 font-bold uppercase">Unit ID</span>
                                            <span class="font-medium text-gray-800">{{ selectedRoleObj?.unit || '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Center: Search Bar (2 Cols) -->
                 <div class="col-span-2 px-2 sm:px-4 relative z-50">
                    <div class="flex items-center bg-white rounded shadow-inner overflow-hidden h-8 w-full max-w-xl mx-auto border border-white/30 group cursor-text" @click="openOmnisearch">
                        <input type="text" placeholder="Cari... (Ctrl+K)" v-model="searchQuery" @click.stop="openOmnisearch" @input="handleAgentSearch" @keydown.enter.prevent="submitAgentSearch" @focus="searchQuery && (isSearchDropdownOpen = true)" class="flex-1 border-none focus:ring-0 text-gray-700 placeholder-gray-400 text-xs px-3 h-full outline-none">
                        <button type="button" @click.stop="openOmnisearch" class="hidden sm:inline-flex items-center gap-1 mr-1 px-1.5 py-0.5 text-[10px] font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 rounded border border-gray-200 transition shadow-xs" title="Buka Omnisearch (Ctrl + K)">
                            <span>Ctrl</span><span>K</span>
                        </button>
                        <button @click.stop="openOmnisearch" class="bg-blue-400 hover:bg-blue-500 text-white px-3 h-full flex items-center justify-center transition" title="Buka Pencarian Cerdas">
                             <i class="fas fa-search text-xs"></i>
                        </button>
                    </div>

                    <!-- Live Search Dropdown -->
                    <div v-if="isSearchDropdownOpen && searchQuery" class="absolute top-full left-4 right-4 mt-2 bg-white rounded-lg shadow-2xl overflow-hidden max-w-xl mx-auto border border-gray-100 z-[100]">
                         <div v-if="agentNews.length > 0" class="max-h-[400px] overflow-y-auto">
                            <div v-for="item in agentNews" :key="item.id" 
                                 @click="viewAgentNews(item); isSearchDropdownOpen = false; searchQuery = '';" 
                                 @mousedown="handleMiddleClickNews($event, item)"
                                 class="px-4 py-3 border-b border-gray-100 hover:bg-teal-50 cursor-pointer flex justify-between items-start group transition">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800 group-hover:text-teal-700 leading-tight mb-1">{{ item.title }}</h4>
                                    <p class="text-[10px] text-gray-500 line-clamp-2" v-html="(item.content ? item.content.replace(/<[^>]*>?/gm, '').substring(0, 100) + '...' : (item.keyword || ''))"></p>
                                </div>
                                <span class="text-[10px] font-bold text-teal-600 bg-teal-100 px-2 py-0.5 rounded uppercase ml-2 whitespace-nowrap">{{ item.category }}</span>
                            </div>
                        </div>
                        <div v-else class="p-4 text-center text-gray-500 text-sm italic">
                            No results found for "{{ searchQuery }}"
                        </div>
                    </div>
                    <!-- Click outside overlay -->
                    <div v-if="isSearchDropdownOpen" @click="isSearchDropdownOpen = false" class="fixed inset-0 z-[40] cursor-default"></div>
                </div>
                
                <!-- Right: BNI Call Logo (Aligns with Menu 5 - 1 Col) -->
                 <div class="col-span-1 flex justify-end">
                     <img :src="'/images/logo-bni.png'" alt="BNI Call" class="h-8 sm:h-10 lg:h-12 object-contain drop-shadow-lg transform scale-110 origin-right flex-shrink-0">
                 </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 container mx-auto px-6 mt-4 flex-1 max-w-7xl pb-8">
            <QuickMenuGrid
                :get-menu-items="getMenuItems"
                :menu-layout-config="menuLayoutConfig"
                :menu-slide-indices="menuSlideIndices"
                :get-current-slide-item="getCurrentSlideItem"
                :move-slide="moveSlide"
                :get-menu-news-image-style="getMenuNewsImageStyle"
                :get-menu-background-style="getMenuBackgroundStyle"
                @portal-item-click="handlePortalItemClick"
                @middle-click-portal="({ event, item }) => handleMiddleClickPortal(event, item)"
            >
                <template #hero-banner>
                    <AgentHeroCarousel
                        :carousel-news="carouselNews"
                        v-model:current-news-index="currentNewsIndex"
                        :get-news-image="getNewsImage"
                        :get-menu-background-style="getMenuBackgroundStyle"
                        @select-news="handleBannerClick"
                        @open-promo-list="openPromoList"
                        @middle-click-banner="handleMiddleClickBanner"
                        @middle-click-promo-list="handleMiddleClickPromoList"
                    />
                </template>
            </QuickMenuGrid>
        </main>
        
        <!-- Footer -->
        <footer class="text-center text-blue-100/60 text-[10px] py-4 select-none relative z-10">
            CX Center Division<br>
            Copyright by SDI &copy; 2026
        </footer>
    </div>

    <!-- Admin Layout (Sidebar + Default) -->
    <div v-else-if="currentLayout === 'admin'" class="flex h-screen bg-gray-100 font-sans">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col">
            <!-- User Profile Section -->
            <div class="p-4 bg-teal-700 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-white mb-2 flex items-center justify-center text-gray-800 text-2xl font-bold">
                    {{ userInitials }}
                </div>
                <div class="text-sm font-semibold" v-if="currentUser">{{ currentUser.employee?.name }} - {{ currentUser.employee?.npp }}</div>
                <div class="text-sm font-semibold" v-else>Loading...</div>
                <div class="text-xs text-teal-200 uppercase">{{ selectedRoleObj ? selectedRoleObj.label : 'ADMIN Bank' }}</div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-2">
                <ul>
                    <li v-for="menu in filteredMenus" :key="menu.name">
                        <button 
                           @click="toggleMenu(menu)"
                           @mousedown="handleMiddleClick($event, menu.name)"
                           class="w-full flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-800 transition-colors focus:outline-none"
                           :class="{ 'bg-teal-600 border-l-4 border-teal-300': menu.active, 'text-gray-300': !menu.active }">
                            <div class="flex items-center">
                                <!-- Icon rendering -->
                                <i :class="`fas fa-${menu.icon} mr-3 w-5 text-center`"></i>
                                {{ menu.name }}
                            </div>
                            <svg v-if="menu.children" :class="{'rotate-90': menu.isOpen}" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                        
                        <!-- Submenu -->
                        <ul v-if="menu.children && menu.isOpen" class="bg-gray-800">
                            <li v-for="child in menu.children" :key="child.name">
                                <div class="pl-8 pr-2 py-1">
                                    <a href="#" 
                                       @click.prevent="child.children ? child.isOpen = !child.isOpen : selectSubmenu(child)"
                                       @mousedown="handleMiddleClick($event, menu.name, child.name)"
                                       class="block px-4 py-2 text-sm transition-colors rounded-r-md flex items-center justify-between"
                                       :class="(child.active || (child.children && child.children.some(c => c.active))) ? 'bg-teal-600 text-white border-l-4 border-teal-300' : 'text-gray-400 hover:text-white hover:bg-gray-700'">
                                        <div class="flex items-center">
                                            <i :class="`fas fa-${child.icon} mr-2 w-4 text-center text-xs`"></i>
                                            {{ child.name }}
                                        </div>
                                        <svg v-if="child.children" :class="{'rotate-90': child.isOpen}" class="w-3 h-3 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                                
                                <!-- Sub Submenu -->
                                <ul v-if="child.children && child.isOpen" class="bg-gray-900 border-l border-gray-700 ml-12 mr-2">
                                    <li v-for="subchild in child.children" :key="subchild.name">
                                        <a href="#" 
                                           @click.prevent="selectSubmenu(subchild); child.active = true;"
                                           class="block pl-4 pr-2 py-1.5 text-xs transition-colors rounded flex items-center my-1"
                                           :class="subchild.active ? 'text-teal-400 font-bold' : 'text-gray-500 hover:text-teal-300'">
                                            <i :class="`fas fa-${subchild.icon || 'circle'} mr-2 w-3 text-center text-[8px]`"></i>
                                            {{ subchild.name }}
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
            
            <div class="border-t border-gray-800">
                <button @click="logout" class="w-full flex items-center px-4 py-3 text-sm text-gray-400 hover:text-white hover:bg-gray-800 transition-colors focus:outline-none">
                     <i class="fas fa-sign-out-alt mr-3 w-5 text-center text-red-500"></i>
                     Logout
                </button>
            </div>
            
            <div class="p-4 text-xs text-gray-500 text-center border-t border-gray-800">
                Copyright &copy; SDI 2025
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow h-14 flex items-center justify-between px-6 border-b border-gray-200">
                <div class="flex items-center">
                    <!-- Brand/Logo -->
                    <img :src="getImagePath('images/logo-cxc-knowledge.png')" alt="CXC Knowledge Logo" class="h-10 mr-4">
                    
                    <!-- Active Page Title -->
                    <div class="text-gray-700 font-semibold text-lg border-l-2 border-gray-300 pl-4" v-if="activeMenuName">
                        {{ activeMenuName }}
                        <span v-if="activeSubmenuName" class="text-gray-500 font-normal">
                             &rsaquo; {{ activeSubmenuName }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <!-- User info and Logout button removed as requested -->
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                
                <!-- Manage Portal Interface View -->
                <div v-if="activeSubmenuName.startsWith('Portal Interface')" class="bg-[#f4f7f6] min-h-full flex flex-col">
                    <!-- Title Bar -->
                    <div class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
                        <div>
                            <h1 class="text-2xl font-normal text-gray-700">Manage Web Portal</h1>
                             <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest font-bold">
                                YOU ARE HERE: Portal BNI <span class="mx-1">&rsaquo;</span> Manage Web <span class="mx-1">&rsaquo;</span> Manage Web Portal
                            </div>
                        </div>
                        <button @click="savePortalSettings" class="bg-teal-600 text-white px-6 py-2 rounded shadow-md hover:bg-teal-700 transition font-bold text-sm uppercase tracking-wide">
                             <i class="fas fa-save mr-2"></i> Update Settings
                        </button>
                    </div>

                    <div class="p-6 space-y-8 overflow-y-auto">
                        <!-- Section: Change Background Image -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-gray-700 font-bold text-sm flex items-center">
                                    Change Background Image <i class="fas fa-image text-teal-500 ml-2"></i>
                                </h3>
                            </div>
                            <div class="p-4 bg-gray-50/50">
                                <div class="grid grid-cols-12 gap-0 border border-gray-200 rounded overflow-hidden">
                                     <div class="col-span-3 bg-gray-100 p-3 border-r text-xs text-gray-600 font-semibold self-center h-full flex items-center">Background Image</div>
                                     <div class="col-span-6 bg-white p-3 border-r text-xs text-gray-400 flex items-center gap-3">
                                         <div v-if="portalSettingsBySection.background[0]?.image_name" class="w-10 h-10 rounded border overflow-hidden shrink-0 bg-gray-100">
                                              <img :src="`/images/${portalSettingsBySection.background[0].image_name}`" class="w-full h-full object-cover">
                                         </div>
                                         <input type="text" v-if="portalSettingsBySection.background[0]" v-model="portalSettingsBySection.background[0].image_name" class="w-full border-none focus:ring-0 p-0 text-gray-600 font-medium">
                                         <span v-else>Blue Polygon.png</span>
                                     </div>
                                     <div class="col-span-3 bg-white p-2">
                                         <button @click="portalSettingsBySection.background[0] ? openPortalUpdateModal(portalSettingsBySection.background[0]) : openPortalAddModal('Background', 'background')" class="w-full border py-1.5 rounded text-[10px] text-gray-500 hover:bg-gray-50 bg-white">Select files...</button>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Portal Menu Mapping (Menu 1 - Menu 15) -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-teal-50/30">
                                <h3 class="text-gray-700 font-bold text-sm flex items-center">
                                    Portal Menu Mapping (Layout Menu 1 - 15) <i class="fas fa-th-large text-teal-500 ml-2"></i>
                                </h3>
                            </div>
                            <div class="p-0 overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-500 border-b">
                                            <th class="p-3 font-semibold w-32">Layout</th>
                                            <th class="p-3 font-semibold">Title</th>
                                            <th class="p-3 font-semibold">URL</th>
                                            <th class="p-3 font-semibold">Image/Icon</th>
                                            <th class="p-3 font-semibold w-32 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template v-for="group in groupedMenuSettings" :key="group.name">
                                            <!-- Group Header Row -->
                                            <tr class="bg-white hover:bg-teal-50 cursor-pointer transition border-b border-gray-100" @click="toggleMenuGroup(group.name)">
                                                <td class="p-3 font-bold text-gray-700 border-r border-gray-100">
                                                    <div class="flex items-center justify-between w-full whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <i :class="['fas mr-2 text-teal-600 w-4 transition-transform duration-200', collapsedMenuGroups.includes(group.name) ? 'fa-chevron-right' : 'fa-chevron-down text-teal-600']"></i>
                                                            {{ group.name }}
                                                        </div>
                                                        <button @click.stop="openPortalAddModal(group.name)" class="ml-3 text-teal-600 hover:text-teal-800 transition transform hover:scale-110" title="Add new item to this menu">
                                                            <i class="fas fa-plus-circle text-lg"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td colspan="3" class="p-3 text-xs text-gray-500 italic flex items-center gap-4">
                                                    <!-- Layout Control -->
                                                    <div class="flex items-center space-x-2 bg-gray-50 px-2 py-1 rounded border border-gray-200" @click.stop>
                                                        <span class="font-bold text-[10px] uppercase text-gray-400">Layout:</span>
                                                        <select :value="menuLayoutConfig[group.name] || 'list'" @change="saveMenuLayoutConfig(group.name, $event.target.value)" class="text-[10px] border-none bg-transparent font-bold text-teal-600 focus:ring-0 cursor-pointer p-0">
                                                            <option value="list">List (Default)</option>
                                                            <option value="slide">Slide Otomatis</option>
                                                            <option value="horizontal">Horizontal</option>
                                                        </select>
                                                    </div>

                                                    <!-- Background Color Control -->
                                                    <div class="flex items-center space-x-1 bg-gray-50 px-2 py-1 rounded border border-gray-200" @click.stop>
                                                        <span class="font-bold text-[10px] uppercase text-gray-400">BG:</span>
                                                        <input type="text" :value="menuBackgroundConfig[group.name] || ''" @change="saveMenuBackgroundConfig(group.name, $event.target.value)" class="w-16 text-[10px] p-0 border-b border-gray-300 bg-transparent focus:ring-0" placeholder="#Hex/URL">
                                                        <input type="color" :value="menuBackgroundConfig[group.name] || '#ffffff'" @change="saveMenuBackgroundConfig(group.name, $event.target.value)" class="w-4 h-4 p-0 border-0 bg-transparent cursor-pointer" title="Pick Color">
                                                        <button v-if="menuBackgroundConfig[group.name]" @click.stop="saveMenuBackgroundConfig(group.name, '')" class="text-xs text-red-400 hover:text-red-600 ml-1" title="Reset"><i class="fas fa-times"></i></button>
                                                    </div>
                                                    
                                                    <span class="text-[10px] font-bold bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200 flex-shrink-0" title="Ukuran rekomendasi gambar">
                                                        <i class="fas fa-expand-alt mr-1"></i>{{ MENU_BANNER_SIZES[group.name] || '128px' }}
                                                    </span>
                                                    <span v-if="collapsedMenuGroups.includes(group.name)" class="flex items-center gap-2">
                                                        <span class="text-gray-600 italic">{{ group.items.length }} items hidden (click to expand)</span>
                                                    </span>
                                                </td>
                                                <td class="p-3 text-center"></td>
                                            </tr>

                                            <!-- Group Items -->
                                            <template v-if="!collapsedMenuGroups.includes(group.name)">
                                                <tr v-for="(item, idx) in group.items" :key="item.id" class="hover:bg-gray-50 transition bg-white border-b border-gray-50">
                                                    <td class="p-3 border-r border-gray-50">
                                                        <!-- Indentation or empty for layout column -->
                                                    </td>
                                                    <td class="p-3">
                                                        <div class="flex items-center" :style="{ paddingLeft: (item.depth * 20) + 'px' }">
                                                            <i v-if="item.depth > 0" class="fas fa-level-up-alt rotate-90 mr-2 text-teal-400 text-xs"></i>
                                                            <input v-model="item.title" class="w-full border-none focus:ring-0 p-0 text-blue-600 font-semibold bg-transparent" placeholder="Enter title...">
                                                        </div>
                                                    </td>
                                                    <td class="p-3">
                                                        <input v-model="item.url" class="w-full border-none focus:ring-0 p-0 text-gray-500 bg-transparent truncate" placeholder="Enter URL...">
                                                    </td>
                                                    <td class="p-3 text-gray-400">
                                                        <div class="flex items-center space-x-2">
                                                            <i v-if="item.image_name && !item.image_name.includes('.')" :class="['fas', 'fa-' + item.image_name, 'text-teal-500']"></i>
                                                            <span class="truncate max-w-[100px]">{{ item.image_name || '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="p-3 text-center">
                                                        <div class="flex items-center space-x-1 justify-center">
                                                            <button @click="openPortalUpdateModal(item)" class="px-2 py-1 bg-white border border-gray-200 rounded text-[10px] font-bold text-gray-700 hover:bg-teal-500 hover:text-white transition">
                                                                <i class="fas fa-pencil-alt"></i> Update
                                                            </button>
                                                            <button @click="deletePortalSetting(item.id)" class="px-2 py-1 bg-white border border-red-200 rounded text-[10px] font-bold text-red-500 hover:bg-red-500 hover:text-white transition">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr v-if="group.items.length === 0" class="bg-white">
                                                    <td class="p-3 border-r border-gray-50"></td>
                                                    <td colspan="4" class="p-3 text-xs text-gray-400 italic text-center">No items in this menu</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Role View (Default Dashboard) -->
                <div v-if="activeSubmenuName === 'My Role' || activeSubmenuName === ''" class="h-full w-full space-y-6">
                    <!-- 1. Selection Box (If no role has been chosen yet) -->
                    <div v-if="!selectedRoleObj" class="bg-white rounded-xl shadow-xl w-full overflow-hidden p-8 text-center border-t-8 border-teal-600">
                        <div class="inline-flex p-3 bg-teal-50 rounded-full text-teal-600 mb-4 animate-bounce">
                            <i class="fas fa-user-shield text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-extrabold text-teal-800 mb-2">Hi {{ currentUser?.employee?.name || 'User' }}!</h2>
                        <p class="text-gray-500 mb-6">Selamat Datang di aplikasi CXC Knowledge</p>
                        
                        <div class="max-w-md mx-auto bg-gray-50/50 p-6 rounded-xl border border-gray-150">
                            <p class="text-gray-700 mb-4 text-sm font-semibold">
                                Anda mempunyai lebih dari satu level/kewenangan dalam sistem ini.<br>
                                Silahkan pilih role untuk melanjutkan:
                            </p>
                            
                            <div class="flex flex-col sm:flex-row items-center gap-3 justify-center">
                                <select v-model="selectedRole" class="border border-gray-300 rounded-lg px-4 py-2.5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 w-full sm:w-64 shadow-sm bg-white text-sm">
                                    <option v-for="option in roleOptions" :key="option.id" :value="option.id">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <button @click="submitRole" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition-all transform hover:scale-102 flex items-center justify-center shrink-0 w-full sm:w-auto text-sm cursor-pointer border-0">
                                    <i class="fas fa-check-circle mr-2"></i> Submit
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Dashboard Layout (If role has been selected) -->
                    <div v-else class="space-y-6">
                        <!-- Top Info Banner -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col md:flex-row justify-between items-center gap-4 bg-gradient-to-r from-white to-teal-50/20">
                            <div class="flex items-center gap-3">
                                <div class="bg-teal-600 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold shadow-md shrink-0">
                                    {{ userInitials }}
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Level & Kewenangan Aktif</div>
                                    <div class="text-gray-800 font-extrabold text-base flex flex-wrap items-center gap-2">
                                        {{ selectedRoleObj.label }}
                                        <span class="text-xs bg-teal-100 text-teal-800 px-2 py-0.5 rounded-full font-bold border border-teal-200 uppercase">
                                            {{ selectedRoleObj.unit }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button v-if="roleOptions && roleOptions.length > 1" @click="handleSwitchRole" class="border border-teal-200 hover:border-teal-600 hover:bg-teal-50 text-teal-700 font-semibold px-4 py-2 rounded-lg text-xs transition flex items-center gap-2 shadow-sm cursor-pointer bg-white">
                                <i class="fas fa-exchange-alt"></i> Ganti Kewenangan
                            </button>
                        </div>

                        <!-- Spinner while Loading -->
                        <div v-if="isDashboardLoading" class="bg-white rounded-xl p-12 shadow-sm flex flex-col items-center justify-center border border-gray-150 min-h-[300px]">
                            <div class="relative w-16 h-16">
                                <div class="absolute inset-0 rounded-full border-4 border-teal-100 animate-pulse"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-teal-600 border-t-transparent animate-spin"></div>
                            </div>
                            <p class="text-gray-500 font-semibold mt-4 animate-pulse">Memuat dashboard statistik...</p>
                        </div>

                        <!-- Statistics Grid & Charts (Modular Component) -->
                        <AnalyticsCharts :dashboard-stats="dashboardStats" />
                    </div>
                </div>

                <!-- My Profile View -->
                <div v-if="activeSubmenuName === 'My Profile'" class="h-full w-full">
                    <div class="bg-white rounded-lg shadow-xl w-full overflow-hidden">
                        <div class="bg-teal-600 px-6 py-4 flex justify-between items-center bg-gradient-to-r from-teal-500 to-teal-700">
                            <h3 class="text-white font-bold text-lg flex items-center">
                                <i class="fas fa-id-card mr-2"></i> My Profile
                            </h3>
                        </div>
                        
                        <div class="p-6 bg-gray-50">
                             <div class="flex items-center space-x-4 mb-6">
                                <div class="h-16 w-16 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 border border-teal-200 shadow-sm shrink-0">
                                    <span class="text-2xl font-bold">{{ userInitials }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-lg">{{ currentUser?.employee?.name || 'Unknown' }}</h4>
                                    <p class="text-sm text-gray-500 font-mono">{{ currentUser?.employee?.npp || '-' }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                     <span class="text-gray-500 text-sm">Email</span>
                                     <span class="font-medium text-gray-800 text-sm truncate max-w-[250px]">{{ currentUser?.employee?.email || '-' }}</span>
                                </div>
                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                     <span class="text-gray-500 text-sm">Status</span>
                                     <span class="font-medium text-teal-600 text-sm font-bold bg-teal-50 px-2 py-0.5 rounded">Active</span>
                                </div>
                                 <div class="pt-2">
                                     <span class="text-gray-500 text-sm block mb-1">Roles & Units</span>
                                     <div class="flex flex-wrap gap-1">
                                         <span v-for="(r, i) in roleOptions" :key="i" class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded border border-gray-300">
                                             {{ r.label }}
                                         </span>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manage User View -->
                <div v-if="activeSubmenuName === 'Manage User'" class="bg-white rounded shadow-sm border border-gray-200 flex flex-col h-full relative">
                    <!-- Title Bar -->
                    <div class="bg-teal-500 px-4 py-2">
                        <h2 class="text-white font-bold text-sm tracking-wide">MANAGE USER</h2>
                    </div>

                    <!-- Toolbar -->
                    <div class="p-2 border-b border-gray-200 flex items-center space-x-4 text-sm text-gray-700 bg-gray-50">
                        <button @click="resetFilters" class="flex items-center hover:text-teal-600 font-semibold" title="Reset Filters"><i class="fas fa-sync-alt text-lg mr-1"></i> Reset</button>
                        <button @click="openAddModal" class="flex items-center hover:text-teal-600 font-bold"><i class="fas fa-plus mr-1"></i> Add</button>
                        <button @click="openViewUserModal" class="flex items-center hover:text-teal-600"><i class="fas fa-eye mr-1"></i> View</button>
                        <button @click="openUpdateModal" class="flex items-center hover:text-teal-600"><i class="fas fa-pencil-alt mr-1"></i> Update</button>
                        <button @click="deleteSelected" class="flex items-center hover:text-teal-600"><i class="fas fa-trash mr-1"></i> Delete</button>
                        <div class="h-4 w-px bg-gray-300 mx-2"></div>
                        <button @click="selectAll" class="flex items-center hover:text-teal-600"><i class="far fa-check-square mr-1"></i> Select All</button>
                        <button @click="deselectAll" class="flex items-center hover:text-teal-600"><i class="far fa-square mr-1"></i> Deselect All</button>
                    </div>

                    <!-- Table Container -->
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-xs text-left text-gray-600 relative">
                            <thead class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="p-2 border-r w-10 text-center">No</th>
                                    <th class="p-2 border-r min-w-[140px]">
                                        <div class="text-left font-bold text-gray-700">NPP</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="filters.npp" @input="debouncedFetchUsers" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="filters.npp = ''; debouncedFetchUsers()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[180px]">
                                        <div class="text-left font-bold text-gray-700">Employee Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="filters.name" @input="debouncedFetchUsers" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="filters.name = ''; debouncedFetchUsers()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[180px]">
                                        <div class="text-left font-bold text-gray-700">Role(s)</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="filters.role" @change="applyFilter" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2">
                                                <option value="" class="text-center font-normal">---Select Role---</option>
                                                <optgroup label="General Roles" class="text-left font-bold text-gray-800">
                                                    <option value="Admin" class="text-left font-normal text-gray-800">Admin (All Units)</option>
                                                    <option value="Agent" class="text-left font-normal text-gray-800">Agent (All Units)</option>
                                                </optgroup>
                                                <optgroup v-if="sortedParentOptions && sortedParentOptions.length > 0" label="Roles by Unit" class="text-left font-bold text-gray-800">
                                                    <template v-for="unit in sortedParentOptions" :key="unit.id">
                                                        <option :value="`Admin ${unit.name}`" class="text-left font-normal text-gray-800">Admin {{ unit.name }}</option>
                                                        <option :value="`Agent ${unit.name}`" class="text-left font-normal text-gray-800">Agent {{ unit.name }}</option>
                                                    </template>
                                                </optgroup>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[180px]">
                                        <div class="text-left font-bold text-gray-700">Email</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="filters.email" @input="debouncedFetchUsers" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="filters.email = ''; debouncedFetchUsers()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[130px]">
                                        <div class="text-left font-bold text-gray-700">Active?</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="filters.active" @change="applyFilter" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2">
                                                <option value="" class="text-center font-normal">---Select Active---</option>
                                                <option value="Yes" class="text-left font-normal text-gray-800">Yes</option>
                                                <option value="No" class="text-left font-normal text-gray-800">No</option>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[150px]">
                                        <div class="text-left font-bold text-gray-700">Last Login</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="filters.lastLogin" @input="debouncedFetchUsers" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="filters.lastLogin = ''; debouncedFetchUsers()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[150px]">
                                        <div class="text-left font-bold text-gray-700">Last Logout</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="filters.lastLogout" @input="debouncedFetchUsers" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="filters.lastLogout = ''; debouncedFetchUsers()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[140px]">
                                        <div class="text-left font-bold text-gray-700">Wrong Pass</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="filters.wrongPass" @input="debouncedFetchUsers" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="filters.wrongPass = ''; debouncedFetchUsers()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 text-center align-middle">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoading">
                                    <td colspan="10" class="p-4 text-center">Loading...</td>
                                </tr>
                                <tr v-else-if="usersData.length === 0">
                                    <td colspan="10" class="p-4 text-center text-gray-500 italic">Data tidak ditemukan</td>
                                </tr>
                                <tr v-else v-for="(user, index) in usersData" :key="user.id" class="border-b hover:bg-blue-50" @click="selectedUsers.includes(user.id) ? selectedUsers = selectedUsers.filter(id => id !== user.id) : selectedUsers.push(user.id)">
                                    <td class="p-2 border-r text-center align-middle">
                                         <div class="flex items-center justify-center h-full w-full">
                                            <input type="checkbox" :value="user.id" v-model="selectedUsers" @click.stop class="mr-2">
                                            <span>{{ (pagination.current_page - 1) * (pagination.per_page === 'All' ? 0 : pagination.per_page) + index + 1 }}</span>
                                         </div>
                                    </td>
                                    <td class="p-2 border-r font-bold">{{ user.npp }}</td>
                                    <td class="p-2 border-r">{{ user.name }}</td>
                                    <td class="p-2 border-r whitespace-pre-wrap">{{ user.role }}</td>
                                    <td class="p-2 border-r">{{ user.email }}</td>
                                    <td class="p-2 border-r">{{ user.active }}</td>
                                    <td class="p-2 border-r">{{ formatLocalDate(user.lastLogin) }}</td>
                                    <td class="p-2 border-r">{{ formatLocalDate(user.lastLogout) }}</td>
                                    <td class="p-2 border-r">{{ user.wrongPass }}</td>
                                    <td class="p-2 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 justify-center">
                                            <button 
                                                type="button"
                                                @click.stop="openAdminResetModal(user)" 
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-amber-600 bg-amber-50 hover:bg-amber-100 hover:text-amber-700 transition border border-amber-200 shadow-2xs cursor-pointer" 
                                                title="Reset Password"
                                            >
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="7.5" cy="15.5" r="5.5"/>
                                                    <path d="m11.5 11.5 8.5-8.5"/>
                                                    <path d="M16 7h4v4"/>
                                                </svg>
                                            </button>
                                            <button 
                                                type="button"
                                                @click.stop="confirmResetSession(user)" 
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 hover:text-rose-700 transition border border-rose-200 shadow-2xs cursor-pointer" 
                                                title="Reset Session"
                                            >
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                                    <polyline points="16 17 21 12 16 7"/>
                                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                     <div class="p-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-600">
                        <div class="flex items-center space-x-2">
                             <button @click="changePage(1)" :disabled="pagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-backward"></i></button>
                             <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-left"></i></button>
                             
                             <span class="px-2 py-1 bg-teal-500 text-white rounded-full text-xs">{{ pagination.current_page }}</span>
                             
                             <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-right"></i></button>
                             <button @click="changePage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-forward"></i></button>
                        </div>
                        <div class="flex items-center space-x-2 text-xs font-bold text-gray-500">
                            <span>Tampilkan:</span>
                            <select v-model="pagination.per_page" @change="fetchUsers(1)" class="border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-teal-500">
                                <option :value="20">20 data</option>
                                <option :value="25">25 data</option>
                                <option :value="50">50 data</option>
                                <option :value="100">100 data</option>
                                <option :value="500">500 data</option>
                                <option :value="1000">1000 data</option>
                                <option value="All">All data</option>
                            </select>
                            <div class="font-normal text-gray-600 pl-2 border-l border-gray-300 ml-2">
                                {{ (pagination.current_page - 1) * (pagination.per_page === 'All' ? pagination.total : pagination.per_page) + (pagination.total > 0 ? 1 : 0) }} - {{ Math.min(pagination.current_page * (pagination.per_page === 'All' ? pagination.total : pagination.per_page), pagination.total) }} of {{ pagination.total }} items 
                                <i @click="fetchUsers(pagination.current_page)" class="fas fa-sync-alt ml-2 cursor-pointer hover:text-teal-600"></i>
                            </div>
                        </div>
                    </div>
                <!-- Add/Edit/View User Modal -->
                <div v-if="isModalOpen" @click.self="isModalOpen = false" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 backdrop-blur-[2px] p-4">
                    <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-[840px] max-w-full max-h-[92vh] flex flex-col relative animate-fade-in-up text-left border border-gray-200 transition-all duration-300">
                        <!-- Header -->
                        <div style="background-color: #FCAD49;" class="text-white px-5 py-3 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                            <div class="flex items-center space-x-3">
                                <h2 class="text-base md:text-lg font-bold uppercase tracking-wide flex items-center truncate pr-2">
                                    <i :class="modalMode === 'add' ? 'fas fa-user-plus' : (modalMode === 'view' ? 'fas fa-eye' : 'fas fa-user-edit')" class="mr-2 text-white/90"></i>
                                    <span class="truncate font-black">{{ modalMode === 'add' ? 'Tambah Data User' : (modalMode === 'view' ? 'Lihat Data User' : 'Ubah Data User') }}</span>
                                </h2>
                            </div>
                            <button @click="isModalOpen = false" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6 overflow-y-auto space-y-4 bg-white flex-1">
                            <div v-if="modalMode !== 'view'" class="text-xs text-red-500 italic">* Field berbintang wajib diisi</div>
                            <!-- Form Fields -->
                            <div class="space-y-3 text-sm">
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <label class="col-span-3 font-bold text-gray-700">Npp <span v-if="modalMode !== 'view'" class="text-teal-600">*</span></label>
                                    <div class="col-span-9 flex items-center gap-2">
                                        <div class="w-full">
                                            <input v-model="formUser.data.npp" :disabled="modalMode === 'view'" :class="[modalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500', formErrors.npp ? 'border-red-500' : '']" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" placeholder="Masukkan NPP">
                                            <span v-if="formErrors.npp" class="text-red-500 text-xs ml-1 font-semibold">{{ formErrors.npp }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-12 gap-4 items-start">
                                    <label class="col-span-3 font-bold text-gray-700 pt-1">Name <span v-if="modalMode !== 'view'" class="text-teal-600">*</span></label>
                                    <div class="col-span-9 flex items-center gap-2">
                                        <div class="w-full">
                                            <input v-model="formUser.data.name" :disabled="modalMode === 'view'" :class="[modalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500', formErrors.name ? 'border-red-500' : '']" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" placeholder="Masukkan Nama Lengkap">
                                            <span v-if="formErrors.name" class="text-red-500 text-xs ml-1 font-semibold">{{ formErrors.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-12 gap-4 items-start">
                                    <label class="col-span-3 font-bold text-gray-700 pt-1">Email <span v-if="modalMode !== 'view'" class="text-teal-600">*</span></label>
                                    <div class="col-span-9 flex items-center gap-2">
                                        <div class="w-full">
                                            <input v-model="formUser.data.email" type="email" :disabled="modalMode === 'view'" :class="[modalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500', formErrors.email ? 'border-red-500' : '']" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" placeholder="user@domain.com">
                                            <span v-if="formErrors.email" class="text-red-500 text-xs ml-1 font-semibold">{{ formErrors.email }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <label class="col-span-3 font-bold text-gray-700">IsActive</label>
                                    <div class="col-span-9">
                                        <select v-model="formUser.data.isActive" :disabled="modalMode === 'view'" :class="modalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-40 border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition">
                                            <option>Yes</option>
                                            <option>No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Set Role Section -->
                            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/70">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-gray-700 text-sm flex items-center">
                                        <i class="fas fa-shield-alt text-teal-600 mr-2"></i> Set Role <span v-if="modalMode !== 'view'" class="text-red-500 ml-1">*</span>
                                    </h4>
                                    <button v-if="modalMode !== 'view'" type="button" @click="openRoleModal" class="text-xs font-bold flex items-center text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-3 py-1.5 rounded-lg transition shadow-2xs">
                                        <i class="fas fa-plus mr-1.5"></i> Tambah Role
                                    </button>
                                </div>
                                <p v-if="formErrors.roles" class="text-red-500 text-xs italic mb-2 font-semibold">{{ formErrors.roles }}</p>

                                <div class="rounded-lg border border-gray-200 overflow-hidden bg-white shadow-2xs">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="bg-gray-100 text-gray-700 border-b border-gray-200 font-bold">
                                                <th class="p-2.5 border-r border-gray-200 w-12 text-center">No</th>
                                                <th class="p-2.5 border-r border-gray-200 text-left">Role</th>
                                                <th class="p-2.5 border-r border-gray-200 text-left">Unit</th>
                                                <th class="p-2.5 border-r border-gray-200 text-center">Status</th>
                                                <th v-if="modalMode !== 'view'" class="p-2.5 text-center w-20">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(role, idx) in formUser.data.roles" :key="idx" class="border-b border-gray-100 hover:bg-gray-50/80 transition">
                                                <td class="p-2.5 border-r border-gray-100 text-center font-medium">{{ idx + 1 }}</td>
                                                <td class="p-2.5 border-r border-gray-100 font-semibold text-gray-800">{{ role.roleName }}</td>
                                                <td class="p-2.5 border-r border-gray-100 text-gray-600">{{ role.unitName }}</td>
                                                <td class="p-2.5 border-r border-gray-100 text-center">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">{{ role.status || 'Bank' }}</span>
                                                </td>
                                                <td v-if="modalMode !== 'view'" class="p-2.5 text-center">
                                                    <button @click="removeRole(idx)" class="bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 shadow-2xs px-2.5 py-1 text-xs font-semibold rounded-md inline-flex items-center gap-1 transition">
                                                        <i class="fas fa-trash text-[10px]"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!formUser.data.roles || formUser.data.roles.length === 0">
                                                <td :colspan="modalMode !== 'view' ? 5 : 4" class="p-4 text-center text-gray-400 italic">Belum ada role yang ditugaskan</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="bg-white px-6 py-3 shrink-0 flex justify-between items-center border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)]">
                            <div class="flex-1 mr-4">
                                <div v-if="generalFormError" class="bg-red-50 border border-red-200 text-red-600 px-3 py-1.5 rounded-lg text-xs flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2 shrink-0"></i> {{ generalFormError }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button @click="isModalOpen = false" class="px-5 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                    <i class="fas fa-times mr-2 text-gray-500"></i> Tutup
                                </button>
                                <button v-if="modalMode === 'view'" @click="modalMode = 'edit'" class="px-5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                    <i class="fas fa-pencil-alt mr-2"></i> Ubah
                                </button>
                                <button v-if="modalMode !== 'view'" @click="saveUser" class="px-6 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded shadow-md shadow-teal-600/20 transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                    <i class="fas fa-save mr-2"></i> Simpan
                                </button>
                            </div>
                        </div>

                        <!-- Assign Role Modal -->
                        <div v-if="isRoleModalOpen" @click.self="isRoleModalOpen = false" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-[2px] p-4">
                            <div class="bg-white rounded-xl shadow-2xl w-[440px] max-w-full border border-gray-200 overflow-hidden flex flex-col animate-fade-in-up text-left">
                                <div style="background-color: #FCAD49;" class="text-white px-4 py-2.5 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                                    <h3 class="text-sm font-bold uppercase tracking-wide flex items-center">
                                        <i class="fas fa-user-tag mr-2 text-white/90"></i>
                                        <span class="truncate font-black">Tambah Role</span>
                                    </h3>
                                    <button @click="isRoleModalOpen = false" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-12 gap-3 items-center">
                                        <label class="col-span-4 text-xs font-bold text-gray-700">Role_Id <span class="text-teal-600">*</span></label>
                                        <div class="col-span-8">
                                             <select v-model="formRole.rolename" class="w-full border border-gray-300 px-3 py-1.5 rounded-lg text-xs bg-white focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                                                 <option v-for="role in availableRoleOptions" :key="role" :value="role">{{ role }}</option>
                                             </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-12 gap-3 items-center">
                                        <label class="col-span-4 text-xs font-bold text-gray-700">Unit_Id <span class="text-teal-600">*</span></label>
                                        <div class="col-span-8">
                                             <select v-model="formRole.unitname" class="w-full border border-gray-300 px-3 py-1.5 rounded-lg text-xs bg-white focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                                                 <option v-for="unit in filteredRoleUnitOptions" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
                                             </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Error Alert inside Modal -->
                                    <div v-if="roleErrorMessage" class="bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-lg text-xs flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2 shrink-0"></i> {{ roleErrorMessage }}
                                    </div>
                                </div>
                                <div class="bg-white px-5 py-3 border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)] flex justify-end gap-3">
                                     <button @click="isRoleModalOpen = false" class="px-4 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                         <i class="fas fa-times mr-2 text-gray-500"></i> Batal
                                     </button>
                                     <button @click="saveRole" class="px-5 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded shadow-md shadow-teal-600/20 transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                         <i class="fas fa-save mr-2"></i> Simpan
                                     </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Local Toast for Modal -->
                    <div v-if="notification.isOpen" class="absolute inset-0 z-[100] flex items-center justify-center pointer-events-none bg-black/10 backdrop-blur-[0.5px]">
                         <div :class="['flex items-center w-full max-w-md p-4 bg-white rounded-xl shadow-2xl border-l-8 transform transition-all duration-300 hover:scale-105 pointer-events-auto', 
                             notification.type === 'success' ? 'border-teal-500' : 'border-red-500']" role="alert" class="animate-bounce-in">
                              <div :class="['inline-flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full shadow-sm', 
                                 notification.type === 'success' ? 'text-teal-600 bg-teal-100' : 'text-red-600 bg-red-100']">
                                  <i :class="['fas text-xl', notification.type === 'success' ? 'fa-check' : 'fa-exclamation-triangle']"></i>
                             </div>
                             <div class="ml-4 flex-1">
                                 <p :class="['font-bold text-sm', notification.type === 'success' ? 'text-teal-900' : 'text-red-900']">
                                     {{ notification.type === 'success' ? 'Sukses!' : 'Perhatian!' }}
                                 </p>
                                 <p class="text-xs text-gray-600 font-medium mt-0.5">{{ notification.message }}</p>
                             </div>
                             <button type="button" @click="notification.isOpen = false" class="ml-2 -mx-1.5 -my-1.5 bg-transparent text-gray-400 hover:text-gray-900 rounded-lg p-2 hover:bg-gray-100 inline-flex h-8 w-8 justify-center items-center transition">
                                 <span class="sr-only">Close</span>
                                <i class="fas fa-times text-lg"></i>
                             </button>
                         </div>
                    </div>
                </div>
                </div>

                <!-- Manage Unit View -->
                <div v-if="activeSubmenuName === 'Manage Unit'" class="bg-white rounded shadow-sm border border-gray-200 flex flex-col h-full relative">
                    <!-- Title Bar -->
                    <div class="bg-teal-500 px-4 py-2">
                        <h2 class="text-white font-bold text-sm tracking-wide">MANAGE UNIT</h2>
                    </div>

                    <!-- Toolbar -->
                    <div class="p-2 border-b border-gray-200 flex items-center space-x-4 text-sm text-gray-700 bg-gray-50">
                        <button @click="resetUnitFilters" class="flex items-center hover:text-teal-600 font-semibold" title="Reset Filters"><i class="fas fa-sync-alt text-lg mr-1"></i> Reset</button>
                        <button @click="openAddUnitModal" class="flex items-center hover:text-teal-600 font-bold"><i class="fas fa-plus mr-1"></i> Add</button>
                        <button @click="openViewUnitModal" class="flex items-center hover:text-teal-600"><i class="fas fa-eye mr-1"></i> View</button>
                        <button @click="openUpdateUnitModal" class="flex items-center hover:text-teal-600"><i class="fas fa-pencil-alt mr-1"></i> Update</button>
                        <button @click="deleteSelectedUnits" class="flex items-center hover:text-teal-600"><i class="fas fa-trash mr-1"></i> Delete</button>
                        <div class="h-4 w-px bg-gray-300 mx-2"></div>
                        <button @click="selectAllUnits" class="flex items-center hover:text-teal-600"><i class="far fa-check-square mr-1"></i> Select All</button>
                        <button @click="deselectAllUnits" class="flex items-center hover:text-teal-600"><i class="far fa-square mr-1"></i> Deselect All</button>
                    </div>

                    <!-- Table Container -->
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-xs text-left text-gray-600 relative">
                            <thead class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="p-2 border-r w-10 text-center">No</th>
                                    <th class="p-2 border-r min-w-[140px]">
                                        <div class="text-left font-bold text-gray-700">Type_Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="unitFilters.type" @change="applyUnitFilter" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2">
                                                <option value="" class="text-center font-normal">---Select Type---</option>
                                                <option :value="1" class="text-left font-normal text-gray-800">DIVISI</option>
                                                <option :value="2" class="text-left font-normal text-gray-800">WILAYAH</option>
                                                <option :value="3" class="text-left font-normal text-gray-800">CABANG</option>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[150px]">
                                        <div class="text-left font-bold text-gray-700">Parent_Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="unitFilters.parent" @input="applyUnitFilter" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="unitFilters.parent = ''; applyUnitFilter()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[130px]">
                                        <div class="text-left font-bold text-gray-700">Code</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="unitFilters.code" @input="debouncedFetchUnits" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="unitFilters.code = ''; debouncedFetchUnits()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[160px]">
                                        <div class="text-left font-bold text-gray-700">Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="unitFilters.name" @input="debouncedFetchUnits" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="unitFilters.name = ''; debouncedFetchUnits()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[140px]">
                                        <div class="text-left font-bold text-gray-700">Short Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="unitFilters.shortName" @input="debouncedFetchUnits" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="unitFilters.shortName = ''; debouncedFetchUnits()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[180px]">
                                        <div class="text-left font-bold text-gray-700">Address</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="unitFilters.address" @input="debouncedFetchUnits" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="unitFilters.address = ''; debouncedFetchUnits()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[130px]">
                                        <div class="text-left font-bold text-gray-700">Is Active_Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="unitFilters.status" @change="applyUnitFilter" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2">
                                                <option value="" class="text-center font-normal">---Select Status---</option>
                                                <option value="Yes" class="text-left font-normal text-gray-800">Yes</option>
                                                <option value="No" class="text-left font-normal text-gray-800">No</option>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoadingUnits">
                                    <td colspan="8" class="p-4 text-center">Loading...</td>
                                </tr>
                                <tr v-else-if="unitsData.length === 0">
                                    <td colspan="8" class="p-4 text-center text-gray-500 italic">Data tidak ditemukan</td>
                                </tr>
                                <tr v-else v-for="(unit, index) in unitsData" :key="unit.id" class="border-b hover:bg-blue-50 cursor-pointer" @click="selectedUnits.includes(unit.id) ? selectedUnits = selectedUnits.filter(id => id !== unit.id) : selectedUnits.push(unit.id)">
                                    <td class="p-2 border-r text-center align-middle">
                                         <div class="flex items-center justify-center h-full w-full">
                                            <input type="checkbox" :value="unit.id" v-model="selectedUnits" @click.stop class="mr-2">
                                            <span>{{ (unitPagination.current_page - 1) * (unitPagination.per_page === 'All' ? 0 : unitPagination.per_page) + index + 1 }}</span>
                                         </div>
                                    </td>
                                    <td class="p-2 border-r">{{ getUnitTypeName(unit.type) }}</td>
                                    <td class="p-2 border-r">{{ unit.parent_name }}</td>
                                    <td class="p-2 border-r">{{ unit.code }}</td>
                                    <td class="p-2 border-r">{{ unit.name }}</td>
                                    <td class="p-2 border-r">{{ unit.short_name }}</td>
                                    <td class="p-2 border-r">{{ unit.address }}</td>
                                    <td class="p-2 border-r">{{ unit.is_active || unit.is_active_name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Pagination -->
                     <div class="p-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-600">
                        <div class="flex items-center space-x-2">
                             <button @click="changeUnitPage(1)" :disabled="unitPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-backward"></i></button>
                             <button @click="changeUnitPage(unitPagination.current_page - 1)" :disabled="unitPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-left"></i></button>
                             
                             <span class="px-2 py-1 bg-teal-500 text-white rounded-full text-xs">{{ unitPagination.current_page }}</span>
                             
                             <button @click="changeUnitPage(unitPagination.current_page + 1)" :disabled="unitPagination.current_page === unitPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-right"></i></button>
                             <button @click="changeUnitPage(unitPagination.last_page)" :disabled="unitPagination.current_page === unitPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-forward"></i></button>
                        </div>
                        <div class="flex items-center space-x-2 text-xs font-bold text-gray-500">
                            <span>Tampilkan:</span>
                            <select v-model="unitPagination.per_page" @change="fetchUnits(1)" class="border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-teal-500">
                                <option :value="20">20 data</option>
                                <option :value="25">25 data</option>
                                <option :value="50">50 data</option>
                                <option :value="100">100 data</option>
                                <option :value="500">500 data</option>
                                <option :value="1000">1000 data</option>
                                <option value="All">All data</option>
                            </select>
                            <div class="font-normal text-gray-600 pl-2 border-l border-gray-300 ml-2">
                                {{ (unitPagination.current_page - 1) * (unitPagination.per_page === 'All' ? unitPagination.total : unitPagination.per_page) + (unitPagination.total > 0 ? 1 : 0) }} - {{ Math.min(unitPagination.current_page * (unitPagination.per_page === 'All' ? unitPagination.total : unitPagination.per_page), unitPagination.total) }} of {{ unitPagination.total }} items 
                                <i @click="fetchUnits(unitPagination.current_page)" class="fas fa-sync-alt ml-2 cursor-pointer hover:text-teal-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity & Error Log View -->
                <div v-if="activeSubmenuName === 'Activity Log'" class="bg-gray-50 rounded shadow-sm border border-gray-200 flex flex-col h-full relative p-6">
                    <!-- Title & Overview Stats -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">System Monitor & Logs</h2>
                            <p class="text-sm text-gray-500">Pusat pelacakan aktivitas dan analisis kesalahan aplikasi</p>
                        </div>
                        <div class="flex items-center space-x-2 mt-4 md:mt-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                Logger Aktif
                            </span>
                        </div>
                    </div>

                    <!-- Analytical Widgets -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Aktivitas</p>
                                <h3 class="text-2xl font-black text-indigo-600 mt-1">{{ logStats.totalActivity }}</h3>
                            </div>
                            <div class="p-3 bg-indigo-50 text-indigo-500 rounded-lg"><i class="fas fa-history text-xl"></i></div>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Error Log</p>
                                <h3 class="text-2xl font-black text-rose-600 mt-1">{{ logStats.totalError }}</h3>
                                <div class="mt-1 text-[10px] font-bold text-gray-500 flex items-center flex-wrap gap-1.5">
                                     <span class="text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded">Error: {{ logStats.unsolvedError || 0 }}</span>
                                     <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">Solved: {{ logStats.solvedError || 0 }}</span>
                                     <button 
                                         @click="solveAllErrors" 
                                         :disabled="!logStats.unsolvedError || logStats.unsolvedError === 0"
                                         class="inline-flex items-center space-x-1 px-1.5 py-0.5 rounded bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:hover:bg-emerald-600 text-white font-semibold text-[10px] shadow-2xs transition-all cursor-pointer"
                                         title="Selesaikan semua error yang belum solved"
                                     >
                                         <i class="fas fa-check-double text-[9px]"></i>
                                         <span>Solved All</span>
                                     </button>
                                </div>
                            </div>
                            <div class="p-3 bg-rose-50 text-rose-500 rounded-lg"><i class="fas fa-exclamation-circle text-xl"></i></div>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Error Hari Ini</p>
                                <h3 class="text-2xl font-black text-amber-600 mt-1">{{ logStats.errorToday }}</h3>
                            </div>
                            <div class="p-3 bg-amber-50 rounded-lg"><i class="fas fa-bug text-xl text-amber-500"></i></div>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kesehatan Sistem</p>
                                <h3 class="text-lg font-bold text-emerald-600 mt-1" v-if="logStats.unsolvedError === 0">Sangat Baik (100%)</h3>
                                <h3 class="text-lg font-bold text-amber-600 mt-1" v-else-if="logStats.unsolvedError < 5">Butuh Perhatian</h3>
                                <h3 class="text-lg font-bold text-rose-600 mt-1" v-else>Kritis</h3>
                            </div>
                            <div class="p-3 rounded-lg" :class="logStats.unsolvedError === 0 ? 'bg-emerald-50 text-emerald-500' : (logStats.unsolvedError < 5 ? 'bg-amber-50 text-amber-500' : 'bg-rose-50 text-rose-500')">
                                <i class="fas" :class="logStats.unsolvedError === 0 ? 'fa-heartbeat text-xl' : 'fa-heart-broken text-xl'"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Security Diagnostics Section -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div @click="showDiagnosticsButtons = !showDiagnosticsButtons" class="flex items-center space-x-3 cursor-pointer select-none group">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-100 transition-colors">
                                <i class="fas fa-shield-alt text-2xl"></i>
                            </div>
                            <div>
                                <div class="flex items-center">
                                    <h4 class="font-bold text-gray-800 text-sm group-hover:text-indigo-600 transition-colors">Security & Antivirus Diagnostics</h4>
                                    <i class="fas ml-2 text-xs transition-all duration-300" :class="showDiagnosticsButtons ? 'fa-chevron-down text-indigo-600' : 'fa-chevron-right text-gray-400'"></i>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">Pantau ancaman malware, trojan, dan status karantina Crowdstrike secara realtime. <span class="text-indigo-600 font-semibold">(Klik untuk menampilkan opsi diagnostik)</span></p>
                            </div>
                        </div>
                        <div v-if="showDiagnosticsButtons" class="flex flex-wrap gap-2 w-full md:w-auto animate-scale-up">
                            <button @click="runSecurityScan" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-2 text-xs rounded-lg flex items-center space-x-1.5 transition-all shadow-xs cursor-pointer">
                                <i class="fas fa-shield-virus"></i>
                                <span>Scan Keamanan Manual</span>
                            </button>
                            <button @click="simulateMalware" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-2 text-xs rounded-lg flex items-center space-x-1.5 transition-all shadow-xs cursor-pointer">
                                <i class="fas fa-bug"></i>
                                <span>Simulasi Malware/Trojan</span>
                            </button>
                            <button @click="simulateQuarantine" class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-2 text-xs rounded-lg flex items-center space-x-1.5 transition-all shadow-xs cursor-pointer">
                                <i class="fas fa-lock"></i>
                                <span>Simulasi Karantina File</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dual Tab Navigation -->
                    <div class="flex border-b border-gray-200 mb-6 bg-white rounded-t-xl p-2 pb-0">
                        <button 
                            @click="activeLogTab = 'activity'" 
                            class="flex items-center space-x-2 py-3 px-6 text-sm font-semibold border-b-2 transition-all duration-300"
                            :class="activeLogTab === 'activity' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            <i class="fas fa-users-cog"></i>
                            <span>Log Aktivitas Pengguna</span>
                            <span class="ml-1.5 px-2 py-0.5 text-xs font-bold rounded-full" :class="activeLogTab === 'activity' ? 'bg-indigo-100 text-indigo-850' : 'bg-gray-100 text-gray-600'">{{ activityPagination.total }}</span>
                        </button>
                        <button 
                            @click="activeLogTab = 'error'" 
                            class="flex items-center space-x-2 py-3 px-6 text-sm font-semibold border-b-2 transition-all duration-300"
                            :class="activeLogTab === 'error' ? 'border-rose-600 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Log Error Sistem</span>
                            <span class="ml-1.5 px-2 py-0.5 text-xs font-bold rounded-full" :class="activeLogTab === 'error' ? 'bg-rose-100 text-rose-850' : 'bg-gray-100 text-gray-600'">{{ errorPagination.total }}</span>
                        </button>
                    </div>

                    <!-- Tab Content: Activity Log -->
                    <div v-if="activeLogTab === 'activity'" class="flex flex-col flex-1 bg-white p-4 rounded-b-xl border-x border-b border-gray-100 shadow-2xs">
                        <!-- Filters -->
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4 pb-4 border-b border-gray-100">
                            <div class="flex flex-wrap items-center gap-3 flex-1">
                                <!-- Search -->
                                <div class="relative w-64">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input 
                                        v-model="activityFilters.search" 
                                        @input="fetchActivityLogs(1)"
                                        type="text" 
                                        placeholder="Cari deskripsi, npp, nama..." 
                                        class="pl-9 pr-3 py-2 w-full text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <!-- Module Filter -->
                                <select 
                                    v-model="activityFilters.module" 
                                    @change="fetchActivityLogs(1)"
                                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 bg-white"
                                >
                                    <option value="">Semua Modul</option>
                                    <option value="AUTH">AUTH (Keamanan/Sesi)</option>
                                    <option value="USER">USER (Manajemen User)</option>
                                    <option value="UNIT">UNIT (Manajemen Unit)</option>
                                    <option value="NEWS">NEWS (Berita & Informasi)</option>
                                    <option value="PORTAL_SETTING">PORTAL_SETTING (Tata Letak)</option>
                                    <option value="HR">HR (Internal Administrasi)</option>
                                </select>
                                <!-- Action Filter -->
                                <select 
                                    v-model="activityFilters.action" 
                                    @change="fetchActivityLogs(1)"
                                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 bg-white"
                                >
                                    <option value="">Semua Aksi</option>
                                    <option value="LOGIN">LOGIN</option>
                                    <option value="LOGOUT">LOGOUT</option>
                                    <option value="CREATE">CREATE (Tambah)</option>
                                    <option value="UPDATE">UPDATE (Ubah)</option>
                                    <option value="DELETE">DELETE (Hapus)</option>
                                    <option value="RESET_PASSWORD">RESET_PASSWORD</option>
                                    <option value="UPLOAD">UPLOAD</option>
                                </select>
                                <!-- Date Range Filter -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-500 font-medium whitespace-nowrap">Dari</span>
                                    <input
                                        type="date"
                                        v-model="activityFilters.date_from"
                                        @change="fetchActivityLogs(1)"
                                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white cursor-pointer"
                                    />
                                    <span class="text-gray-400 text-sm">&ndash;</span>
                                    <span class="text-xs text-gray-500 font-medium whitespace-nowrap">Sampai</span>
                                    <input
                                        type="date"
                                        v-model="activityFilters.date_to"
                                        @change="fetchActivityLogs(1)"
                                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white cursor-pointer"
                                    />
                                </div>
                                <!-- Reset Filters -->
                                <button 
                                    @click="resetActivityFilters" 
                                    class="text-xs font-semibold text-gray-500 hover:text-indigo-600 transition-colors py-2 px-3"
                                >
                                    Reset Filter
                                </button>
                            </div>
                            <!-- Action Buttons -->
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="downloadXLSX('activity')" 
                                    class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold px-4 py-2 text-xs border border-emerald-200 rounded-lg flex items-center space-x-1.5 transition-all cursor-pointer shadow-2xs"
                                >
                                    <i class="fas fa-file-excel"></i>
                                    <span>Download ke .XLSX</span>
                                </button>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="flex-1 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs font-semibold">
                                    <tr>
                                        <th class="px-4 py-3">Waktu</th>
                                        <th class="px-4 py-3">Pengguna</th>
                                        <th class="px-4 py-3">Modul</th>
                                        <th class="px-4 py-3">Aksi</th>
                                        <th class="px-4 py-3">Deskripsi Aktivitas</th>
                                        <th class="px-4 py-3">Alamat IP</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700 bg-white">
                                    <tr v-if="isLoadingActivity" class="text-center py-8">
                                        <td colspan="6" class="py-10 text-gray-400">
                                            <i class="fas fa-circle-notch animate-spin text-2xl text-indigo-500 mr-2"></i> Memuat log aktivitas...
                                        </td>
                                    </tr>
                                    <tr v-else-if="activityLogs.length === 0" class="text-center py-8">
                                        <td colspan="6" class="py-10 text-gray-400">
                                            <i class="fas fa-folder-open text-2xl block mb-2"></i> Tidak ada log aktivitas yang ditemukan.
                                        </td>
                                    </tr>
                                    <tr v-for="log in activityLogs" :key="'act-' + log.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 font-medium whitespace-nowrap text-gray-500">{{ formatTime(log.created_at) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ log.username || 'System' }}</div>
                                            <div class="text-xs text-gray-400">{{ log.user?.employee?.name || '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                {{ log.module }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span 
                                                class="px-2.5 py-0.5 text-xs font-bold rounded-md border"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 border-emerald-200': log.action === 'CREATE' || log.action === 'LOGIN',
                                                    'bg-amber-50 text-amber-700 border-amber-200': log.action === 'UPDATE' || log.action === 'RESET_PASSWORD' || log.action === 'UPLOAD',
                                                    'bg-rose-50 text-rose-700 border-rose-200': log.action === 'DELETE',
                                                    'bg-slate-50 text-slate-700 border-slate-200': log.action === 'LOGOUT'
                                                }"
                                            >
                                                {{ log.action }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 max-w-md font-medium text-gray-800">{{ log.description }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-500">{{ log.ip_address || '127.0.0.1' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="p-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-600 mt-4 rounded-b">
                            <div class="flex items-center space-x-2">
                                <button @click="fetchActivityLogs(1)" :disabled="activityPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-backward"></i></button>
                                <button @click="fetchActivityLogs(activityPagination.current_page - 1)" :disabled="activityPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-left"></i></button>
                                
                                <span class="px-2 py-1 bg-indigo-600 text-white rounded-full text-xs">{{ activityPagination.current_page }}</span>
                                
                                <button @click="fetchActivityLogs(activityPagination.current_page + 1)" :disabled="activityPagination.current_page === activityPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-right"></i></button>
                                <button @click="fetchActivityLogs(activityPagination.last_page)" :disabled="activityPagination.current_page === activityPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-forward"></i></button>
                            </div>
                            <div class="flex items-center space-x-2 text-xs font-bold text-gray-500">
                                <span>Tampilkan:</span>
                                <select v-model="activityPagination.per_page" @change="fetchActivityLogs(1)" class="border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option :value="20">20 data</option>
                                    <option :value="25">25 data</option>
                                    <option :value="50">50 data</option>
                                    <option :value="100">100 data</option>
                                    <option :value="500">500 data</option>
                                    <option :value="1000">1000 data</option>
                                    <option value="All">All data</option>
                                </select>
                                <div class="font-normal text-gray-600 pl-2 border-l border-gray-300 ml-2">
                                    {{ (activityPagination.current_page - 1) * (activityPagination.per_page === 'All' ? activityPagination.total : activityPagination.per_page) + (activityPagination.total > 0 ? 1 : 0) }} - {{ Math.min(activityPagination.current_page * (activityPagination.per_page === 'All' ? activityPagination.total : activityPagination.per_page), activityPagination.total) }} of {{ activityPagination.total }} items 
                                    <i @click="fetchActivityLogs(activityPagination.current_page)" class="fas fa-sync-alt ml-2 cursor-pointer hover:text-indigo-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: Error Log -->
                    <div v-if="activeLogTab === 'error'" class="flex flex-col flex-1 bg-white p-4 rounded-b-xl border-x border-b border-gray-100 shadow-2xs">
                        <!-- Filters -->
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4 pb-4 border-b border-gray-100">
                            <div class="flex flex-wrap items-center gap-3 flex-1">
                                <!-- Search -->
                                <div class="relative w-64">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input 
                                        v-model="errorFilters.search" 
                                        @input="fetchErrorLogs(1)"
                                        type="text" 
                                        placeholder="Cari pesan error, penjelasan, saran..." 
                                        class="pl-9 pr-3 py-2 w-full text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                                    />
                                </div>
                                <!-- Error Class Filter -->
                                <select 
                                    v-model="errorFilters.error_class" 
                                    @change="fetchErrorLogs(1)"
                                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 bg-white"
                                >
                                    <option value="">Semua Kelas Error</option>
                                    <option value="ClientSideJavaScriptError">ClientSideJavaScriptError (Frontend Browser)</option>
                                    <option value="Illuminate\Database\QueryException">QueryException (Error Database)</option>
                                    <option value="ErrorException">ErrorException (Gagal Eksekusi Skrip)</option>
                                    <option value="Illuminate\Validation\ValidationException">ValidationException (Validasi Form)</option>
                                    <option value="Illuminate\Auth\AuthenticationException">AuthenticationException (Kedaluwarsa Sesi)</option>
                                    <option value="Illuminate\Auth\Access\AuthorizationException">AuthorizationException (Akses Ditolak)</option>
                                    <option value="Illuminate\Session\TokenMismatchException">TokenMismatchException (Sesi Kedaluwarsa CSRF)</option>
                                    <option value="Symfony\Component\HttpKernel\Exception\NotFoundHttpException">NotFoundHttpException (Halaman Tidak Ditemukan)</option>
                                </select>
                                <!-- Date Range Filter -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-500 font-medium whitespace-nowrap">Dari</span>
                                    <input
                                        type="date"
                                        v-model="errorFilters.date_from"
                                        @change="fetchErrorLogs(1)"
                                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 bg-white cursor-pointer"
                                    />
                                    <span class="text-gray-400 text-sm">&ndash;</span>
                                    <span class="text-xs text-gray-500 font-medium whitespace-nowrap">Sampai</span>
                                    <input
                                        type="date"
                                        v-model="errorFilters.date_to"
                                        @change="fetchErrorLogs(1)"
                                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 bg-white cursor-pointer"
                                    />
                                </div>
                                <!-- Reset Filters -->
                                <button 
                                    @click="resetErrorFilters" 
                                    class="text-xs font-semibold text-gray-500 hover:text-rose-600 transition-colors py-2 px-3"
                                >
                                    Reset Filter
                                </button>
                            </div>
                            <!-- Action Buttons -->
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="solveAllErrors" 
                                    :disabled="!logStats.unsolvedError || logStats.unsolvedError === 0"
                                    class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:hover:bg-emerald-600 text-white font-semibold px-3 py-2 text-xs rounded-lg flex items-center space-x-1.5 transition-all cursor-pointer shadow-2xs"
                                    title="Selesaikan semua error yang belum solved"
                                >
                                    <i class="fas fa-check-double"></i>
                                    <span>Solved All</span>
                                </button>
                                <button 
                                    @click="downloadXLSX('error')" 
                                    class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold px-4 py-2 text-xs border border-emerald-200 rounded-lg flex items-center space-x-1.5 transition-all cursor-pointer shadow-2xs"
                                >
                                    <i class="fas fa-file-excel"></i>
                                    <span>Download ke .XLSX</span>
                                </button>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="flex-1 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs font-semibold">
                                    <tr>
                                        <th class="px-4 py-3">Waktu</th>
                                        <th class="px-4 py-3">Pengguna</th>
                                        <th class="px-4 py-3">Kelas Error / Tipe</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3">Pesan Error</th>
                                        <th class="px-4 py-3">File & Baris</th>
                                        <th class="px-4 py-3 text-center">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700 bg-white">
                                    <tr v-if="isLoadingError" class="text-center py-8">
                                        <td colspan="7" class="py-10 text-gray-400">
                                            <i class="fas fa-circle-notch animate-spin text-2xl text-rose-500 mr-2"></i> Menganalisis log error sistem...
                                        </td>
                                    </tr>
                                    <tr v-else-if="errorLogs.length === 0" class="text-center py-8">
                                        <td colspan="7" class="py-10 text-gray-400">
                                            <i class="fas fa-heart text-2xl text-emerald-500 block mb-2 animate-bounce"></i> Sistem bekerja normal. Tidak ada error terdeteksi!
                                        </td>
                                    </tr>
                                    <tr v-for="log in errorLogs" :key="'err-' + log.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 font-medium whitespace-nowrap text-gray-500">{{ formatTime(log.created_at) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="font-semibold text-gray-950">{{ log.username || 'System' }}</div>
                                            <div class="text-xs text-gray-400">{{ log.user?.employee?.name || '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span 
                                                class="px-2 py-0.5 text-xs font-mono font-semibold rounded"
                                                :class="log.error_class === 'ClientSideJavaScriptError' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                            >
                                                {{ log.error_class ? log.error_class.split('\\').pop() : 'Error' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <button 
                                                @click.stop="toggleErrorStatus(log)"
                                                class="px-2.5 py-1 text-xs font-bold rounded-full border inline-flex items-center space-x-1 transition-all cursor-pointer select-none"
                                                :class="log.status === 'Solved' ? 'bg-emerald-50 text-emerald-700 border-emerald-250 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-250 hover:bg-rose-100'"
                                                title="Klik untuk mengubah status"
                                            >
                                                <i class="fas" :class="log.status === 'Solved' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                                <span>{{ log.status === 'Solved' ? 'Solved' : 'Error' }}</span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 max-w-sm truncate text-gray-800 font-semibold" :title="log.error_message">{{ log.error_message }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600 max-w-xs truncate" :title="log.file">
                                            <span class="text-gray-950 font-bold block">{{ log.file ? log.file.split('/').pop() : '-' }}</span>
                                            <span class="text-[10px] text-gray-400 block max-w-xs truncate">{{ log.file || '-' }}</span>
                                            <span class="text-[10px] font-bold text-rose-500" v-if="log.line">Baris: {{ log.line }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <button 
                                                @click="viewErrorDetail(log)" 
                                                class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm hover:shadow transition-all duration-300 flex items-center mx-auto space-x-1"
                                            >
                                                <i class="fas fa-stethoscope"></i>
                                                <span>Analisis & Solusi</span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="p-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-600 mt-4 rounded-b">
                            <div class="flex items-center space-x-2">
                                <button @click="fetchErrorLogs(1)" :disabled="errorPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-backward"></i></button>
                                <button @click="fetchErrorLogs(errorPagination.current_page - 1)" :disabled="errorPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-left"></i></button>
                                
                                <span class="px-2 py-1 bg-rose-600 text-white rounded-full text-xs">{{ errorPagination.current_page }}</span>
                                
                                <button @click="fetchErrorLogs(errorPagination.current_page + 1)" :disabled="errorPagination.current_page === errorPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-right"></i></button>
                                <button @click="fetchErrorLogs(errorPagination.last_page)" :disabled="errorPagination.current_page === errorPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-forward"></i></button>
                            </div>
                            <div class="flex items-center space-x-2 text-xs font-bold text-gray-500">
                                <span>Tampilkan:</span>
                                <select v-model="errorPagination.per_page" @change="fetchErrorLogs(1)" class="border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-rose-500">
                                    <option :value="20">20 data</option>
                                    <option :value="25">25 data</option>
                                    <option :value="50">50 data</option>
                                    <option :value="100">100 data</option>
                                    <option :value="500">500 data</option>
                                    <option :value="1000">1000 data</option>
                                    <option value="All">All data</option>
                                </select>
                                <div class="font-normal text-gray-600 pl-2 border-l border-gray-300 ml-2">
                                    {{ (errorPagination.current_page - 1) * (errorPagination.per_page === 'All' ? errorPagination.total : errorPagination.per_page) + (errorPagination.total > 0 ? 1 : 0) }} - {{ Math.min(errorPagination.current_page * (errorPagination.per_page === 'All' ? errorPagination.total : errorPagination.per_page), errorPagination.total) }} of {{ errorPagination.total }} items 
                                    <i @click="fetchErrorLogs(errorPagination.current_page)" class="fas fa-sync-alt ml-2 cursor-pointer hover:text-rose-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modals moved to end of template -->
                
                <ExportImport v-if="activeSubmenuName === 'Export dan Import'" />
                
                <!-- Fallback View for Unhandled Submenus (Knowledge, etc) if not caught by specific blocks -->
                 <div v-if="activeSubmenuName && !['Home', 'My Role', 'My Profile', 'Manage User', 'Manage Unit', 'Portal Interface', 'Manage News', 'Activity Log', 'Export dan Import'].some(k => activeSubmenuName.includes(k) || activeSubmenuName === k) && activeSubmenuName !== 'Knowledge'" class="bg-white p-8 rounded shadow text-center">
                    <div class="text-6xl text-gray-200 mb-4"><i class="fas fa-tools"></i></div>
                    <h2 class="text-xl font-bold text-gray-600 mb-2">Under Construction</h2>
                    <p class="text-gray-500">The requested page "{{ activeSubmenuName }}" is coming soon.</p>
                </div>



                <!-- Manage News View -->
                <div v-if="activeSubmenuName === 'Manage News' || activeSubmenuName === 'Knowledge'" class="bg-white rounded shadow-sm border border-gray-200 flex flex-col h-full relative">
                    <!-- Title Bar -->
                    <div class="bg-teal-500 px-4 py-2">
                        <h2 class="text-white font-bold text-sm tracking-wide text-transform uppercase">MANAGE {{ activeSubmenuName === 'Knowledge' ? 'KNOWLEDGE' : 'NEWS' }}</h2>
                    </div>

                    <!-- Toolbar -->
                    <div class="p-2 border-b border-gray-200 flex items-center space-x-4 text-sm text-gray-700 bg-gray-50">
                        <button @click="initializeNewsFilters(); fetchNews()" class="flex items-center hover:text-teal-600 font-semibold" title="Reset Filters"><i class="fas fa-sync-alt text-lg mr-1"></i> Reset</button>
                        <button @click="openCreateNewsModal" class="flex items-center hover:text-teal-600 font-bold"><i class="fas fa-plus mr-1"></i> {{ activeSubmenuName === 'Knowledge' ? 'Add Knowledge' : 'Create News' }}</button>
                        <button @click="openViewNewsModal" class="flex items-center hover:text-teal-600"><i class="fas fa-eye mr-1"></i> View</button>
                        <button @click="openEditNewsModal" class="flex items-center hover:text-teal-600"><i class="fas fa-pencil-alt mr-1"></i> Edit</button>
                        <button @click="deleteNews" class="flex items-center hover:text-teal-600"><i class="fas fa-trash mr-1"></i> Delete</button>
                        <div class="h-4 w-px bg-gray-300 mx-2"></div>
                        <button @click="selectAllNews" class="flex items-center hover:text-teal-600"><i class="far fa-check-square mr-1"></i> Select All</button>
                        <button @click="deselectAllNews" class="flex items-center hover:text-teal-600"><i class="far fa-square mr-1"></i> Deselect All</button>
                    </div>

                    <!-- Table Container -->
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-xs text-left text-gray-600 relative border-collapse">
                            <thead class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="p-2 border-r w-10 text-center">No</th>
                                    <th class="p-2 border-r w-64 min-w-[200px]">
                                        <div class="text-left font-bold text-gray-700">Title</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input id="filter_news_title" name="filter_news_title" v-model="newsFilters.title" @input="debouncedFetchNews" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="newsFilters.title = ''; debouncedFetchNews()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[160px]">
                                        <div class="text-left font-bold text-gray-700">Keyword</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input id="filter_news_keyword" name="filter_news_keyword" v-model="newsFilters.keyword" @input="debouncedFetchNews" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="newsFilters.keyword = ''; debouncedFetchNews()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[160px]">
                                        <div class="text-left font-bold text-gray-700">Unit_Id</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="newsFilters.unit" @change="fetchNews(1)" :disabled="isAdminWithUnit" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2" :class="isAdminWithUnit ? 'cursor-not-allowed opacity-75' : ''">
                                                <option v-if="!isAdminWithUnit" value="" class="text-center font-normal">---Select Unit---</option>
                                                <option v-for="opt in dynamicTargetUnits" :key="opt" :value="opt" class="text-left font-normal text-gray-800">{{ opt }}</option>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[170px]">
                                        <div class="text-left font-bold text-gray-700">Category</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="newsFilters.category" @change="fetchNews(1)" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2">
                                                <option value="" class="text-center font-normal">---Select Category---</option>
                                                <option v-for="cat in flatNewsCategories" :key="cat" :value="cat" class="text-left font-normal text-gray-800">{{ cat }}</option>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[150px]">
                                        <div class="text-left font-bold text-gray-700">Created Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="newsFilters.created_name" @input="debouncedFetchNews" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="newsFilters.created_name = ''; debouncedFetchNews()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[140px]">
                                        <div class="flex justify-between items-center h-full">
                                            <span class="font-bold text-gray-700">Created Time</span>
                                            <button type="button" @click="sortByNews('created_at')" class="p-1 rounded hover:bg-gray-200 transition-colors ml-1 inline-flex items-center cursor-pointer" title="Sort by Created Time">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m7 9 5-5 5 5" :stroke="newsSort.column === 'created_at' && newsSort.order === 'asc' ? '#0d9488' : '#9ca3af'"/>
                                                    <path d="m7 15 5 5 5-5" :stroke="newsSort.column === 'created_at' && newsSort.order === 'desc' ? '#0d9488' : '#9ca3af'"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[150px]">
                                        <div class="text-left font-bold text-gray-700">Updated Name</div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <svg class="w-4 h-4 flex-shrink-0 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.3 14.3L19.5 19.5" stroke="#E67E22" stroke-width="3" stroke-linecap="round"/>
                                                <circle cx="10" cy="10" r="6" fill="#D2E4F3" stroke="#2C3E50" stroke-width="1.8"/>
                                                <path d="M7.8 7.8A3.2 3.2 0 0 1 10.2 6.8" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                            <input v-model="newsFilters.updated_name" @input="debouncedFetchNews" class="w-full bg-transparent border-none outline-none focus:outline-none focus:ring-0 text-xs px-1 text-gray-800 font-normal h-full">
                                            <button type="button" @click.stop="newsFilters.updated_name = ''; debouncedFetchNews()" class="flex-shrink-0 text-black hover:text-gray-600 focus:outline-none p-0.5 flex items-center justify-center cursor-pointer transition-colors" title="Clear">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r min-w-[140px]">
                                        <div class="flex justify-between items-center h-full">
                                            <span class="font-bold text-gray-700">Updated Time</span>
                                            <button type="button" @click="sortByNews('updated_at')" class="p-1 rounded hover:bg-gray-200 transition-colors ml-1 inline-flex items-center cursor-pointer" title="Sort by Updated Time">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m7 9 5-5 5 5" :stroke="newsSort.column === 'updated_at' && newsSort.order === 'asc' ? '#0d9488' : '#9ca3af'"/>
                                                    <path d="m7 15 5 5 5-5" :stroke="newsSort.column === 'updated_at' && newsSort.order === 'desc' ? '#0d9488' : '#9ca3af'"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                    <th class="p-2 border-r whitespace-nowrap min-w-[150px]">
                                        <div class="flex items-center text-left font-bold text-gray-700">
                                            <i class="fas fa-clock text-orange-400 mr-1"></i> EndTime
                                        </div>
                                        <div class="relative flex items-center w-full mt-1.5 bg-[#d9d9d9] rounded-lg h-7 px-2 shadow-xs">
                                            <select v-model="newsFilters.status" @change="fetchNews(1)" class="w-full h-full bg-transparent text-gray-800 text-xs font-normal text-center [text-align-last:center] appearance-none cursor-pointer focus:outline-none pr-7 pl-2">
                                                <option value="" class="text-center font-normal">---Select Status---</option>
                                                <option value="Aktif" class="text-left font-normal text-gray-800">Aktif</option>
                                                <option value="Expired" class="text-left font-normal text-gray-800">Expired</option>
                                            </select>
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none flex items-center text-black">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9.5"></circle>
                                                    <polyline points="8.5 10.5 12 14 15.5 10.5"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoadingNews">
                                     <td colspan="10" class="p-4 text-center">Loading News...</td>
                                </tr>
                                <tr v-else-if="newsData.length === 0">
                                     <td colspan="10" class="p-4 text-center text-gray-500 italic">Data tidak ditemukan</td>
                                </tr>
                                <tr v-else v-for="(item, index) in newsData" :key="item.id" class="border-b hover:bg-blue-50 cursor-pointer" :class="{'bg-blue-100': selectedNews.includes(item.id)}" 
                                    @click="selectedNews.includes(item.id) ? selectedNews = selectedNews.filter(id => id !== item.id) : selectedNews.push(item.id)"
                                    @mousedown="handleMiddleClickNews($event, item)">
                                    <td class="p-2 border-r text-center align-middle">
                                        <div class="flex items-center justify-center h-full w-full">
                                            <input type="checkbox" :value="item.id" v-model="selectedNews" @click.stop class="mr-2">
                                            <span>{{ (newsPagination.current_page - 1) * (newsPagination.per_page === 'All' ? 0 : newsPagination.per_page) + index + 1 }}</span>
                                        </div>
                                    </td>
                                    <td class="p-2 border-r font-semibold text-gray-800">{{ item.title }}</td>
                                    <td class="p-2 border-r">{{ item.keyword }}</td>
                                    <td class="p-2 border-r truncate max-w-[150px]" :title="item.target_unit">
                                        {{ (() => {
                                            try {
                                                const units = JSON.parse(item.target_unit);
                                                return Array.isArray(units) ? units.join(', ') : item.target_unit;
                                            } catch(e) { return item.target_unit; }
                                        })() }}
                                    </td>
                                    <td class="p-2 border-r">{{ item.category }}</td>
                                    <td class="p-2 border-r">{{ item.created_by }}</td>
                                    <td class="p-2 border-r whitespace-nowrap">{{ formatLocalDate(item.created_at) }}</td>
                                    <td class="p-2 border-r">{{ item.updated_by }}</td>
                                    <td class="p-2 border-r whitespace-nowrap">{{ item.updated_at ? formatLocalDate(item.updated_at) : '-' }}</td>
                                    <td class="p-2 border-r whitespace-nowrap">
                                        <template v-if="item.end_time">
                                            <span :class="getEndTimeStatus(item.end_time).cls" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold">
                                                <i :class="getEndTimeStatus(item.end_time).icon"></i>
                                                {{ getEndTimeStatus(item.end_time).label }}
                                            </span>
                                            <div class="text-[10px] text-gray-400 mt-0.5">{{ new Date(item.end_time).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) }}</div>
                                        </template>
                                        <span v-else class="text-gray-300 italic text-[10px]">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Pagination -->
                      <div class="p-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-600">
                        <div class="flex items-center space-x-2">
                             <button @click="fetchNews(1)" :disabled="newsPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-backward"></i></button>
                             <button @click="fetchNews(newsPagination.current_page - 1)" :disabled="newsPagination.current_page === 1" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-left"></i></button>
                             
                             <span class="px-2 py-1 bg-teal-500 text-white rounded-full text-xs">{{ newsPagination.current_page }}</span>
                             
                             <button @click="fetchNews(newsPagination.current_page + 1)" :disabled="newsPagination.current_page === newsPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-chevron-right"></i></button>
                             <button @click="fetchNews(newsPagination.last_page)" :disabled="newsPagination.current_page === newsPagination.last_page" class="px-2 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50"><i class="fas fa-step-forward"></i></button>
                        </div>
                        <div class="flex items-center space-x-2 text-xs font-bold text-gray-500">
                            <span>Tampilkan:</span>
                            <select v-model="newsPagination.per_page" @change="fetchNews(1)" class="border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-teal-500">
                                <option :value="20">20 data</option>
                                <option :value="25">25 data</option>
                                <option :value="50">50 data</option>
                                <option :value="100">100 data</option>
                                <option :value="500">500 data</option>
                                <option :value="1000">1000 data</option>
                                <option value="All">All data</option>
                            </select>
                            <div class="font-normal text-gray-600 pl-2 border-l border-gray-300 ml-2">
                                {{ (newsPagination.current_page - 1) * (newsPagination.per_page === 'All' ? newsPagination.total : newsPagination.per_page) + (newsPagination.total > 0 ? 1 : 0) }} - {{ Math.min(newsPagination.current_page * (newsPagination.per_page === 'All' ? newsPagination.total : newsPagination.per_page), newsPagination.total) }} of {{ newsPagination.total }} items 
                                <i @click="fetchNews(newsPagination.current_page)" class="fas fa-sync-alt ml-2 cursor-pointer hover:text-teal-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create/Edit News Modal -->
                <div v-if="isNewsModalOpen" @click.self="closeNewsModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 backdrop-blur-[2px] p-2 md:p-4">
                    <div :class="isNewsMaximized ? 'fixed inset-0 w-screen h-screen max-w-none max-h-none rounded-none' : 'w-[950px] max-w-full max-h-[95vh] rounded-xl'" class="bg-white shadow-2xl overflow-hidden flex flex-col relative animate-fade-in-up text-left transition-all duration-300 border border-gray-200">
                        <!-- Header -->
                        <div style="background-color: #FCAD49;" class="text-white px-4 py-2.5 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                            <div class="flex items-center space-x-3">
                                <h2 class="text-base md:text-lg font-bold uppercase tracking-wide flex items-center truncate pr-2">
                                    <i class="fas fa-newspaper mr-2 text-white/90"></i>
                                    <span class="truncate font-black">{{ newsModalMode === 'create' ? 'Create News' : 'Edit News' }}</span>
                                    <span v-if="activeSubmenuName" class="text-xs font-semibold text-white/85 ml-2">({{ activeSubmenuName.replace('News', '').trim().toUpperCase() }})</span>
                                </h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button @click="isNewsMaximized = !isNewsMaximized" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer" :title="isNewsMaximized ? 'Perkecil' : 'Layar Penuh (Fullscreen)'">
                                    <i :class="isNewsMaximized ? 'fas fa-compress text-xs' : 'fas fa-expand text-xs'"></i>
                                </button>
                                <button @click="closeNewsModal" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer">
                                    <i class="fas fa-times text-sm"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="news-modal-body p-6 overflow-y-auto bg-gray-50 flex-1">
                            <div class="grid grid-cols-2 gap-6 mb-4">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <label class="w-24 text-sm font-bold text-gray-600" for="news_title">Title</label>
                                            <input id="news_title" name="news_title" v-model="formNews.title" 
                                                @input="newsErrors.title = ''"
                                                class="flex-1 border rounded px-2 py-1 text-sm outline-none transition-all duration-200"
                                                :class="newsErrors.title ? 'border-red-500 bg-red-50 focus:border-red-600' : 'border-gray-300 focus:border-teal-500'"
                                                placeholder="Enter news title">
                                        </div>
                                        <p v-if="newsErrors.title" class="ml-24 text-red-500 text-[10px] mt-1 font-semibold">{{ newsErrors.title }}</p>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <label class="w-24 text-sm font-bold text-gray-600" for="news_start_time">StartTime</label>
                                            <div class="flex-1 relative">
                                                <input type="date" id="news_start_time" name="news_start_time" v-model="formNews.startTime" 
                                                    @change="newsErrors.startTime = ''"
                                                    class="w-full border rounded px-2 py-1 text-sm outline-none transition-all duration-200"
                                                    :class="newsErrors.startTime ? 'border-red-500 bg-red-50 focus:border-red-600' : 'border-gray-300 focus:border-teal-500'">
                                            </div>
                                        </div>
                                        <p v-if="newsErrors.startTime" class="ml-24 text-red-500 text-[10px] mt-1 font-semibold">{{ newsErrors.startTime }}</p>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <label class="w-24 text-sm font-bold text-gray-600" for="news_keyword">Keyword</label>
                                            <input id="news_keyword" name="news_keyword" v-model="formNews.keyword" 
                                                @input="newsErrors.keyword = ''"
                                                class="flex-1 border rounded px-2 py-1 text-sm outline-none transition-all duration-200"
                                                :class="newsErrors.keyword ? 'border-red-500 bg-red-50 focus:border-red-600' : 'border-gray-300 focus:border-teal-500'"
                                                placeholder="e.g. promo, banking">
                                        </div>
                                        <p v-if="newsErrors.keyword" class="ml-24 text-red-500 text-[10px] mt-1 font-semibold">{{ newsErrors.keyword }}</p>
                                    </div>
                                </div>
                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <label class="w-24 text-sm font-bold text-gray-600" for="news_category">Category</label>
                                            <select id="news_category" name="news_category" v-model="formNews.category" 
                                                @change="newsErrors.category = ''"
                                                class="flex-1 border rounded px-2 py-1 text-sm outline-none transition-all duration-200 bg-white"
                                                :class="newsErrors.category ? 'border-red-500 bg-red-50 focus:border-red-600' : 'border-gray-300 focus:border-teal-500'">
                                                <option value="" disabled>--Select Category--</option>
                                                <optgroup v-for="group in availableNewsCategories" :key="group.label" :label="group.label">
                                                    <option v-for="opt in group.options" :key="opt" :value="opt">{{ opt }}</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <p v-if="newsErrors.category" class="ml-24 text-red-500 text-[10px] mt-1 font-semibold">{{ newsErrors.category }}</p>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <label class="w-24 text-sm font-bold text-gray-600" for="news_end_time">EndTime</label>
                                            <div class="flex-1 relative">
                                                <input type="date" id="news_end_time" name="news_end_time" v-model="formNews.endTime" 
                                                    @change="newsErrors.endTime = ''"
                                                    class="w-full border rounded px-2 py-1 text-sm outline-none transition-all duration-200"
                                                    :class="newsErrors.endTime ? 'border-red-500 bg-red-50 focus:border-red-600' : 'border-gray-300 focus:border-teal-500'">
                                            </div>
                                        </div>
                                        <p v-if="newsErrors.endTime" class="ml-24 text-red-500 text-[10px] mt-1 font-semibold leading-tight">{{ newsErrors.endTime }}</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="w-24 text-sm font-bold text-gray-600" for="news_headline">Headline?</label>
                                        <select id="news_headline" name="news_headline" v-model="formNews.headline" class="w-24 border border-gray-300 rounded px-2 py-1 text-sm focus:border-teal-500 outline-none bg-white">
                                            <option>No</option>
                                            <option>Yes</option>
                                        </select>
                                    </div>
                                    <!-- PROMO Target Unit (Checkboxes) -->
                                    <div class="flex flex-col">
                                        <div class="flex items-start">
                                            <label class="w-24 text-sm font-bold text-gray-600 mt-1">Target Unit</label>
                                            <div class="flex-1 grid grid-cols-2 gap-2 border p-2 rounded transition-all" :class="newsErrors.target_unit ? 'border-red-500 bg-red-50' : 'border-transparent'">
                                                <label v-for="(opt, idx) in dynamicTargetUnits" :key="opt" class="inline-flex items-center" :class="{ 'opacity-50 cursor-not-allowed': isTargetUnitDisabled(opt) }">
                                                    <input type="checkbox" :id="`news_target_${idx}`" name="news_target_unit[]" v-model="formNews.target_unit" :value="opt" @change="newsErrors.target_unit = ''" class="form-checkbox text-teal-600 h-4 w-4 rounded disabled:bg-gray-200" :disabled="isTargetUnitDisabled(opt)" :class="{ 'cursor-not-allowed': isTargetUnitDisabled(opt) }">
                                                    <span class="ml-2 text-sm text-gray-700" :class="{ 'text-gray-400': isTargetUnitDisabled(opt) }">{{ opt }}</span>
                                                </label>
                                            </div>
                                        </div>
                                        <p v-if="newsErrors.target_unit" class="ml-24 text-red-500 text-[10px] mt-1 font-semibold">{{ newsErrors.target_unit }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Section -->
                            <div class="border border-gray-300 rounded bg-white p-2 mb-4">
                                <div class="flex items-center mb-2 px-2">
                                    <span class="font-bold text-xs text-gray-700">Upload Lampiran</span>
                                    <i class="fas fa-check-circle text-teal-500 ml-1 text-xs"></i>
                                </div>
                                <div class="bg-green-50/50 p-2 text-[10px] text-green-700 border-l-2 border-green-500 mb-2">
                                    * Besar file maksimum : <span class="font-bold">10 MB</span><br>
                                    * Jenis file yang diperbolehkan : PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG
                                </div>
                                <div class="border border-gray-200 p-2">
                                     <input type="file" ref="newsFileInput" class="hidden" multiple @change="handleNewsFileUpload" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
                                     <button @click="$refs.newsFileInput.click()" class="bg-gray-100 border border-gray-300 px-3 py-1 text-xs font-semibold rounded text-gray-600 hover:bg-gray-200">Select files...</button>
                                </div>
                                <table class="w-full text-xs mt-2 border border-gray-200">
                                    <thead class="bg-gray-100 text-gray-600 font-semibold border-b">
                                        <tr>
                                            <td class="p-1 w-10 text-center border-r">No</td>
                                            <td class="p-1 border-r text-center">Nama File</td>
                                            <td class="p-1 border-r text-center">File Type</td>
                                            <td class="p-1 border-r text-center">URL</td>
                                            <td class="p-1 text-center">Action</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="formNews.attachments && formNews.attachments.length > 0" v-for="(file, idx) in formNews.attachments" :key="idx">
                                            <td class="p-1 text-center border-r">{{ idx + 1 }}</td>
                                            <td class="p-1 border-r">{{ file.name }}</td>
                                            <td class="p-1 border-r text-center">{{ file.type || '-' }}</td>
                                            <td class="p-1 border-r relative group">
                                                <div v-if="file.url" class="flex items-center space-x-1 px-1">
                                                    <input type="text" :value="file.url" readonly class="w-full border border-gray-200 text-[10px] px-1 bg-gray-50 text-gray-500 rounded focus:outline-none">
                                                    <button @click="copyToClipboard(file.url)" class="text-teal-500 hover:text-teal-700" title="Copy URL"><i class="fas fa-copy"></i></button>
                                                </div>
                                                <span v-else class="text-xs text-gray-400 italic px-1">Wait...</span>
                                            </td>
                                            <td class="p-1 text-center flex items-center justify-center space-x-2">
                                                <button v-if="file.url" @click="insertAttachmentTable(file, idx + 1)" class="text-blue-500 hover:text-blue-700 p-0.5 rounded border border-blue-200 bg-blue-50 text-[10px] font-bold px-2" title="Insert This File to Content">Insert</button>
                                                <button @click="removeNewsFile(idx)" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                                            </td>
                                        </tr>
                                        <tr v-else>
                                            <td colspan="5" class="p-4 text-center italic text-gray-400">No file selected</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Content Editor -->
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="font-bold text-sm text-gray-700">Content</div>
                                <p v-if="newsErrors.content" class="text-red-500 text-[10px] font-bold italic animate-pulse-fast border-b border-red-200 pb-0.5">* {{ newsErrors.content }}</p>
                            </div>
                            <div class="ck-editor-container transition-all duration-300 rounded-lg overflow-hidden shadow-sm" 
                                :class="newsErrors.content ? 'border-2 border-red-500 animate-error-pulse' : 'border border-gray-300'">
                                <ckeditor 
                                    :editor="ClassicEditor" 
                                    v-model="formNews.content" 
                                    :config="editorConfig"
                                    @ready="onEditorReady"
                                />
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="bg-white px-6 py-3 shrink-0 flex justify-end items-center gap-3 border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)]">
                            <button @click="closeNewsModal" class="px-5 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                <i class="fas fa-times mr-2 text-gray-500"></i> Batal
                            </button>
                            <button @click="saveNews" class="px-6 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded shadow-md shadow-teal-600/20 transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                                <i class="fas fa-save mr-2"></i> {{ newsModalMode === 'create' ? 'Create' : 'Update' }}
                            </button>
                        </div>

                        <!-- Processing & Paste Loading Spinner Overlay -->
                        <div v-if="isPasteLoading" class="absolute inset-0 z-[120] bg-white/75 backdrop-blur-[2px] flex flex-col items-center justify-center animate-fade-in">
                            <div class="relative flex items-center justify-center">
                                <div class="w-16 h-16 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin"></div>
                                <i class="fas fa-paste text-teal-600 text-lg absolute"></i>
                            </div>
                            <p class="mt-4 text-sm font-bold text-gray-800 tracking-wide">Memproses Konten...</p>
                            <p class="text-xs text-gray-500 mt-1">Mengonversi teks dan mengekstrak gambar, mohon tunggu sebentar.</p>
                        </div>
                    </div>
                </div>





            </main>
            
            <!-- Bottom Bar -->
            <footer class="bg-gray-200 px-6 py-2 text-right text-xs text-gray-500 border-t border-gray-300">
                System Date: {{ systemDate }}
            </footer>
        </div>
    </div>
    <!-- Modals (Global, Fixed Position) -->
    
    <!-- Delete Confirmation Modal -->
    <div v-if="isDeleteModalOpen" @click.self="isDeleteModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-[2px]">
            <div class="bg-white rounded shadow-xl w-96 p-6 border border-gray-300">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Konfirmasi Hapus</h3>
            <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus user terpilih?</p>
            <div class="flex justify-end space-x-3">
                <button @click="isDeleteModalOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-sm font-semibold">Batal</button>
                <button @click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-semibold">Hapus</button>
            </div>
        </div>
    </div>

    <!-- Manage Unit Modal -->
    <div v-if="isUnitModalOpen" @click.self="isUnitModalOpen = false" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 backdrop-blur-[2px] p-4">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-[580px] max-w-full max-h-[92vh] flex flex-col relative animate-fade-in-up text-left border border-gray-200 transition-all duration-300">
            <!-- Header -->
            <div style="background-color: #FCAD49;" class="text-white px-5 py-3 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                <div class="flex items-center space-x-3">
                    <h2 class="text-base md:text-lg font-bold uppercase tracking-wide flex items-center truncate pr-2">
                        <i :class="unitModalMode === 'add' ? 'fas fa-plus' : (unitModalMode === 'view' ? 'fas fa-eye' : 'fas fa-pencil-alt')" class="mr-2 text-white/90"></i>
                        <span class="truncate font-black">{{ unitModalMode === 'add' ? 'Tambah Data Unit' : (unitModalMode === 'view' ? 'Lihat Data Unit' : 'Ubah Data Unit') }}</span>
                    </h2>
                </div>
                <button @click="isUnitModalOpen = false" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-4 text-sm bg-white flex-1">
                 <div class="grid grid-cols-12 gap-4 items-center">
                    <label class="col-span-4 font-bold text-gray-700">Type <span v-if="unitModalMode !== 'view'" class="text-teal-600">*</span></label>
                    <div class="col-span-8">
                         <select v-model="formUnit.type" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition">
                            <option :value="1">DIVISI</option>
                            <option :value="2">WILAYAH</option>
                            <option :value="3">CABANG</option>
                        </select>
                    </div>
                </div>
                 <div class="grid grid-cols-12 gap-4 items-center">
                    <label class="col-span-4 font-bold text-gray-700">Parent_Id</label>
                    <div class="col-span-8">
                         <select v-model="formUnit.parentId" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition">
                            <option value="">Pilih Parent</option>
                            <option v-for="opt in parentOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                        </select>
                    </div>
                </div>
                 <div class="grid grid-cols-12 gap-4 items-center">
                    <label class="col-span-4 font-bold text-gray-700">Code</label>
                    <div class="col-span-8">
                        <input v-model="formUnit.code" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" placeholder="Kode unit">
                    </div>
                </div>
                 <div class="grid grid-cols-12 gap-4 items-center">
                    <label class="col-span-4 font-bold text-gray-700">Name <span v-if="unitModalMode !== 'view'" class="text-teal-600">*</span></label>
                    <div class="col-span-8">
                        <input v-model="formUnit.name" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" placeholder="Nama unit">
                    </div>
                </div>
                 <div class="grid grid-cols-12 gap-4 items-center">
                    <label class="col-span-4 font-bold text-gray-700">ShortName <span v-if="unitModalMode !== 'view'" class="text-teal-600">*</span></label>
                    <div class="col-span-8">
                        <input v-model="formUnit.shortName" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" placeholder="Nama singkatan">
                    </div>
                </div>
                 <div class="grid grid-cols-12 gap-4 items-start">
                    <label class="col-span-4 font-bold text-gray-700 pt-1">Address</label>
                    <div class="col-span-8">
                        <textarea v-model="formUnit.address" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-full border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition" rows="3" placeholder="Alamat unit"></textarea>
                    </div>
                </div>
                 <div class="grid grid-cols-12 gap-4 items-center">
                    <label class="col-span-4 font-bold text-gray-700">IsActive <span v-if="unitModalMode !== 'view'" class="text-teal-600">*</span></label>
                    <div class="col-span-8">
                         <select v-model="formUnit.isActive" :disabled="unitModalMode === 'view'" :class="unitModalMode === 'view' ? 'bg-gray-100 text-gray-600 cursor-not-allowed border-gray-200' : 'bg-white border-gray-300 focus:border-teal-500 focus:ring-1 focus:ring-teal-500'" class="w-40 border px-3 py-1.5 rounded-lg focus:outline-none shadow-2xs transition">
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="bg-white px-6 py-3 shrink-0 flex justify-end items-center gap-3 border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)]">
                <button @click="isUnitModalOpen = false" class="px-5 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                    <i class="fas fa-times mr-2 text-gray-500"></i> Tutup
                </button>
                <button v-if="unitModalMode === 'view'" @click="unitModalMode = 'edit'" class="px-5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                    <i class="fas fa-pencil-alt mr-2"></i> Ubah
                </button>
                <button v-if="unitModalMode !== 'view'" @click="saveUnit" class="px-6 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded shadow-md shadow-teal-600/20 transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                    <i class="fas fa-save mr-2"></i> Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Self Change Password Modal -->
    <div v-if="isResetPasswordModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/20 backdrop-blur-sm">
        <div class="bg-white rounded shadow-xl w-[400px] border border-gray-300">
             <div class="bg-teal-500 px-4 py-2 border-b flex justify-between items-center">
                <h3 class="text-white font-semibold">Ganti Password</h3>
            </div>
             <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600 mb-4">Silakan ganti password default Anda untuk melanjutkan.</p>
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Password Baru <span class="text-teal-500">*</span></label>
                    <div class="relative">
                        <input v-model="newPassword" :type="showNewPassword ? 'text' : 'password'" class="w-full border border-gray-300 px-2 py-1 rounded text-sm bg-white focus:outline-none focus:border-teal-500 pr-10">
                        <button type="button" @click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-teal-600 focus:outline-none">
                            <i :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>
                 <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Konfirmasi Password <span class="text-teal-500">*</span></label>
                    <div class="relative">
                        <input v-model="confirmNewPassword" :type="showConfirmNewPassword ? 'text' : 'password'" class="w-full border border-gray-300 px-2 py-1 rounded text-sm bg-white focus:outline-none focus:border-teal-500 pr-10">
                        <button type="button" @click="showConfirmNewPassword = !showConfirmNewPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-teal-600 focus:outline-none">
                            <i :class="showConfirmNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="bg-gray-100 px-4 py-2 border-t flex justify-end gap-2">
                 <button @click="saveResetPassword" class="bg-teal-600 text-white px-4 py-1 rounded text-sm font-semibold hover:bg-teal-700">Ganti Password</button>
            </div>
        </div>
    </div>

    <!-- Agent Announcement Overlay (Global Root Level for True Fullscreen) -->
    <div v-if="activeAnnouncements.length > 0 && !isAnnouncementClosed" 
         :class="isAnnouncementMaximized ? '!p-0 z-[20000]' : 'p-4 md:p-10 z-[100]'" 
         class="fixed inset-0 flex items-center justify-center bg-black/70 backdrop-blur-sm" 
         @mouseenter="pauseAnnouncementSlide" @mouseleave="startAnnouncementSlide">
        <div :class="isAnnouncementMaximized ? 'fixed inset-0 w-screen h-screen max-w-none max-h-none rounded-none' : 'w-[950px] max-w-full max-h-[95vh] rounded-xl'" 
             class="bg-white shadow-2xl overflow-hidden flex flex-col relative animate-fade-in-up text-left transition-all duration-300">
            
            <!-- Header -->
            <div style="background-color: #EF4444;" class="text-white px-5 py-2.5 flex justify-between items-center shrink-0 border-b border-red-600/30">
                <div class="flex items-center space-x-3 truncate pr-2">
                    <span class="bg-white/20 text-white text-[10px] font-black px-2 py-0.5 rounded tracking-wider uppercase flex items-center">
                        <i class="fas fa-bullhorn mr-1.5 animate-pulse"></i> Announcement
                    </span>
                    <h2 class="text-sm md:text-base font-bold truncate">
                        {{ activeAnnouncements[currentAnnouncementIndex]?.title }}
                    </h2>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <span v-if="activeAnnouncements.length > 1" class="text-xs bg-black/20 px-2 py-0.5 rounded-full font-mono font-bold mr-1">
                        {{ currentAnnouncementIndex + 1 }} / {{ activeAnnouncements.length }}
                    </span>
                    <!-- Maximize / Fullscreen Button -->
                    <button @click="isAnnouncementMaximized = !isAnnouncementMaximized" 
                            class="text-white hover:text-white/80 transition bg-black/20 hover:bg-black/40 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer" 
                            :title="isAnnouncementMaximized ? 'Kecilkan Layar' : 'Layar Penuh (Fullscreen)'">
                        <i :class="isAnnouncementMaximized ? 'fas fa-compress text-xs' : 'fas fa-expand text-xs'"></i>
                    </button>
                    <!-- Close Button -->
                    <button @click="closeAnnouncement" 
                            class="text-white hover:text-white/80 transition bg-black/20 hover:bg-black/40 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm cursor-pointer"
                            title="Tutup">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Content Area (Scrollable with continuous flow) -->
            <div class="overflow-y-auto bg-white flex-1 relative custom-scrollbar">
                <div :class="isAnnouncementMaximized ? 'w-full max-w-7xl mx-auto px-6 md:px-12 py-8 fullscreen-reader' : 'p-6 md:p-8'">
                    <!-- Title -->
                    <h1 class="text-xl md:text-2xl font-black text-gray-800 text-center mb-2 leading-snug">
                        {{ activeAnnouncements[currentAnnouncementIndex]?.title }}
                    </h1>

                    <!-- Metadata -->
                    <div class="text-center text-xs md:text-sm text-gray-500 mb-6 flex justify-center gap-4 flex-wrap font-medium pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-user text-red-500"></i>
                            <span class="font-semibold text-gray-600">Oleh:</span> {{ activeAnnouncements[currentAnnouncementIndex]?.created_by || 'Admin' }}
                        </div>
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-calendar-alt text-red-500"></i>
                            <span class="font-semibold text-gray-600">Tanggal:</span> {{ new Date(activeAnnouncements[currentAnnouncementIndex]?.created_at).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) }}
                        </div>
                    </div>

                    <!-- Processed HTML Body -->
                    <div class="announcement-content ck-content prose max-w-none text-sm text-gray-800 leading-relaxed" 
                         v-html="processedAnnouncementContent" 
                         @click="handleContentClick">
                    </div>
                </div>
            </div>

            <!-- Footer / Navigation -->
            <div class="bg-gray-50 px-5 py-2.5 shrink-0 flex justify-between items-center border-t border-gray-200 shadow-sm">
                <!-- Dots Navigation -->
                <div class="flex items-center space-x-2">
                    <button v-if="activeAnnouncements.length > 1" @click="prevAnnouncement" class="text-gray-400 hover:text-gray-700 transition p-1 text-xs">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div v-if="activeAnnouncements.length > 1" class="flex space-x-1.5">
                        <span v-for="(item, idx) in activeAnnouncements" :key="idx" 
                              @click="currentAnnouncementIndex = idx" 
                              class="w-2.5 h-2.5 rounded-full cursor-pointer transition-all"
                              :class="idx === currentAnnouncementIndex ? 'bg-red-500 w-5' : 'bg-gray-300 hover:bg-gray-400'">
                        </span>
                    </div>
                    <button v-if="activeAnnouncements.length > 1" @click="nextAnnouncement" class="text-gray-400 hover:text-gray-700 transition p-1 text-xs">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Tutup Button -->
                <button @click="closeAnnouncement" class="px-5 py-1.5 bg-white hover:bg-gray-100 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition uppercase tracking-wide flex items-center">
                    <i class="fas fa-check mr-2 text-green-600"></i> Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- View News Modal (Modular Component) -->
    <NewsDetailModal
        :is-open="isViewNewsModalOpen"
        :news-data="viewNewsData"
        :current-layout="currentLayout"
        :is-image-file="isImageFile"
        @close="isViewNewsModalOpen = false"
        @open-news-list="openNewsListModal"
        @edit-news="(item) => { isViewNewsModalOpen = false; selectedNews = [item.id]; openEditNewsModal(); }"
        @content-click="handleContentClick"
    />

    <!-- Global Omnisearch / Command Palette Modal (Ctrl + K) -->
    <GlobalOmnisearchModal
        :is-open="isOmnisearchOpen"
        :user-unit="selectedRoleObj?.unit || ''"
        :app-categories="flatNewsCategories"
        @close="isOmnisearchOpen = false"
        @select-news="(item) => { viewAgentNews(item); isOmnisearchOpen = false; }"
    />

    <!-- Portal Setting Update Modal -->
    <div v-if="isPortalUpdateModalOpen" @click.self="isPortalUpdateModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded shadow-xl w-[500px] flex flex-col max-h-[90vh] border border-gray-300 relative">
             <!-- Header -->
            <div class="bg-white px-4 py-2 border-b flex justify-between items-center">
                <h3 class="text-gray-600 font-bold text-sm uppercase">{{ portalModalMode === 'add' ? 'Tambah' : 'Ubah' }} Pengaturan Portal</h3>
                <button @click="isPortalUpdateModalOpen = false" class="text-gray-400 hover:text-red-500 font-bold text-lg">&times;</button>
            </div>
            
            <!-- Body -->
            <div class="news-modal-body p-6 overflow-y-auto space-y-4">
                <div v-if="portalModalMode === 'add'" class="mb-2 text-xs text-orange-600 font-bold bg-orange-50 p-2 rounded border border-orange-200">
                    <i class="fas fa-info-circle mr-1"></i> Anda sedang menambahkan sub-item baru ke <strong>{{ formPortalUpdate.category }}</strong>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Kategori</label>
                    <input v-model="formPortalUpdate.category" readonly class="w-full border border-gray-200 px-2 py-1.5 rounded text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Menu Parent (Opsional)</label>
                    <select v-model="formPortalUpdate.parent_id" class="w-full border border-gray-300 px-2 py-1.5 rounded text-sm focus:outline-none focus:border-teal-500 bg-white shadow-sm">
                        <option :value="null">-- Tanpa Parent (Level Atas) --</option>
                        <option v-for="p in availableParentMenus" :key="p.id" :value="p.id">{{ p.title }}</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Judul <span class="text-teal-500">*</span></label>
                    <input v-model="formPortalUpdate.title" placeholder="Masukkan judul menu..." class="w-full border border-gray-300 px-2 py-1.5 rounded text-sm focus:outline-none focus:border-teal-500 bg-white shadow-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">URL</label>
                    <input v-model="formPortalUpdate.url" placeholder="http://..." class="w-full border border-gray-300 px-2 py-1.5 rounded text-sm focus:outline-none focus:border-teal-500 bg-white shadow-sm">
                </div>
                <!-- ... remains the same ... -->
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Unggah Gambar</label>
                    <div class="flex items-center space-x-2">
                        <input type="file" ref="portalUpdateFileInput" class="hidden" @change="handlePortalImageUpload" accept=".png,.jpg,.jpeg">
                        <button @click="$refs.portalUpdateFileInput.click()" class="bg-gray-100 border border-gray-300 px-3 py-1.5 text-xs font-semibold rounded text-gray-600 hover:bg-gray-200">Pilih file...</button>
                        <span class="text-xs text-gray-500 truncate max-w-[200px]">{{ formPortalUpdate.image ? formPortalUpdate.image.name : (formPortalUpdate.image_name || 'Tidak ada file terpilih') }}</span>
                    </div>
                    <div class="text-[10px] text-gray-400 italic mt-1">* Ukuran maks: 750KB. Format: .png, .jpg, .jpeg</div>
                </div>
                <div class="flex items-center space-x-4 pt-2">
                    <div class="flex items-center">
                        <input type="checkbox" v-model="formPortalUpdate.is_active" id="portal_is_active" class="rounded text-teal-500 focus:ring-teal-500 h-4 w-4 border-gray-300">
                        <label for="portal_is_active" class="ml-2 text-sm text-gray-700 font-medium">Aktif</label>
                    </div>
                </div>

                <!-- Inline Error Alert -->
                <div v-if="portalFormError" class="mb-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded text-xs flex items-center animate-bounce-in">
                    <i class="fas fa-exclamation-circle mr-2 text-lg"></i>
                    <span class="font-bold">{{ portalFormError }}</span>
                </div>

                <!-- Target Unit Checkboxes -->
                <div class="space-y-2 border-t pt-3 mt-2">
                    <label class="text-sm font-bold text-gray-700 block">Terlihat Untuk (Unit Agent)</label>
                    <div class="space-y-1 max-h-32 overflow-y-auto border border-gray-200 p-2 rounded bg-gray-50/50">
                        <!-- Default All Layanan -->
                         <div class="flex items-center">
                            <input type="checkbox" id="unit_all" value="All Layanan" v-model="formPortalUpdate.target_unit" class="rounded text-teal-500 focus:ring-teal-500 h-4 w-4 border-gray-300">
                            <label for="unit_all" class="ml-2 text-xs text-gray-700">Semua Layanan (Global)</label>
                        </div>
                        <!-- Dynamic Units -->
                        <div v-for="unit in sortedParentOptions" :key="unit.id" class="flex items-center">
                            <input type="checkbox" :id="'unit_' + unit.id" :value="unit.name" v-model="formPortalUpdate.target_unit" class="rounded text-teal-500 focus:ring-teal-500 h-4 w-4 border-gray-300">
                            <label :for="'unit_' + unit.id" class="ml-2 text-xs text-gray-700">{{ unit.name }}</label>
                        </div>
                    </div>
                    <!-- Removed misleading text -->
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 border-t flex justify-end gap-2">
                <button @click="isPortalUpdateModalOpen = false" class="px-4 py-1.5 bg-white border border-gray-300 text-gray-700 rounded text-sm font-semibold hover:bg-gray-100">Batal</button>
                <button @click="updateSinglePortalSetting" class="px-4 py-1.5 bg-teal-600 text-white rounded text-sm font-semibold hover:bg-teal-700 shadow-sm transition">
                    {{ portalModalMode === 'add' ? 'Simpan Item Baru' : 'Ubah Pengaturan' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Agent News List Modal (Global) -->
    <div v-if="isNewsListModalOpen" @click.self="isNewsListModalOpen = false" class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-[900px] max-w-full max-h-[95vh] flex flex-col relative animate-fade-in-up text-left">
             <!-- Header -->
            <div style="background-color: #FCAD49;" class="text-white px-4 py-2 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                <h2 class="text-base md:text-lg font-bold uppercase tracking-wide flex items-center truncate pr-2">
                    <span class="truncate font-black">{{ selectedPortalCategory ? selectedPortalCategory : 'Daftar Berita Aktif' }} - {{ agentHeaderTitle.replace('CXC ', '') }}</span>
                </h2>
                <button @click="isNewsListModalOpen = false; selectedPortalCategory = ''; fetchAgentNews();" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-0 overflow-y-auto bg-white flex-1 border-t border-gray-300">
                 <table class="w-full text-xs text-left text-gray-600 border-collapse border border-gray-300">
                    <thead class="bg-gray-100 text-gray-700 font-bold border-b border-gray-300 sticky top-0">
                        <tr>
                            <th class="p-3 w-12 text-center border-r border-gray-300">No</th>
                            <th class="p-3 border-r border-gray-300">Judul</th>
                            <th class="p-3 w-32 border-r border-gray-300 text-center">Kategori</th>
                            <th class="p-3 w-32 border-r border-gray-300 text-center">Tanggal Publish</th>
                            <th class="p-3 w-32 border-r border-gray-300 text-center">EndTime</th>
                            <th class="p-3 w-24 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="">
                        <tr v-for="(item, index) in agentNews" :key="item.id" 
                            class="hover:bg-teal-50 transition cursor-pointer group border-b border-gray-300" 
                            @click="selectNewsFromList(item)"
                            @mousedown="handleMiddleClickNews($event, item)">
                            <td class="p-3 text-center border-r border-gray-300">{{ agentNewsPagination.per_page === 'All' ? index + 1 : (agentNewsPagination.current_page - 1) * agentNewsPagination.per_page + index + 1 }}</td>
                            <td class="p-3 font-semibold text-gray-800 group-hover:text-teal-700 border-r border-gray-300">{{ item.title }}</td>
                            <td class="p-3 border-r border-gray-300 text-center font-bold items-center justify-center">
                                <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px] border border-gray-200 uppercase">{{ item.category }}</span>
                            </td>
                            <td class="p-3 text-[10px] border-r border-gray-300 text-center">{{ new Date(item.created_at).toLocaleDateString() }}</td>
                            <td class="p-3 text-[10px] border-r border-gray-300 text-center">{{ item.end_time ? new Date(item.end_time).toLocaleDateString() : '-' }}</td>
                            <td class="p-3 text-center flex justify-center items-center">
                                <button class="text-teal-600 hover:text-teal-800 bg-teal-100 hover:bg-teal-200 p-1.5 rounded-full w-8 h-8 flex items-center justify-center transition mx-auto shadow-sm"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <tr v-if="(!agentNews || agentNews.length === 0)">
                             <td colspan="6" class="p-8 text-center text-gray-400 italic bg-gray-50 uppercase font-black">
                                <i class="fas fa-newspaper text-2xl mb-2 opacity-50"></i><br>
                                Tidak ada berita aktif untuk unit ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

             <!-- Footer -->
             <div class="bg-white px-4 py-2 shrink-0 flex flex-col md:flex-row justify-between items-center border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)] gap-3">
                 <div class="flex items-center space-x-4">
                     <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500 font-bold">Tampilkan:</span>
                        <select v-model="agentNewsPagination.per_page" @change="fetchAgentNews('', '', 1)" class="border border-gray-300 rounded px-2 py-1 text-xs font-semibold focus:outline-none focus:border-teal-500">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="All">All data</option>
                        </select>
                     </div>
                     <div class="flex items-center space-x-1">
                        <button @click="fetchAgentNews('', '', agentNewsPagination.current_page - 1)" :disabled="agentNewsPagination.current_page <= 1" class="px-3 py-1 bg-white hover:bg-gray-100 border border-gray-300 rounded text-xs font-bold disabled:opacity-50 transition"><i class="fas fa-chevron-left mr-1"></i> Prev</button>
                        <span class="text-xs font-bold text-gray-600 px-2">{{ agentNewsPagination.current_page }} of {{ agentNewsPagination.last_page }}</span>
                        <button @click="fetchAgentNews('', '', agentNewsPagination.current_page + 1)" :disabled="agentNewsPagination.current_page >= agentNewsPagination.last_page" class="px-3 py-1 bg-white hover:bg-gray-100 border border-gray-300 rounded text-xs font-bold disabled:opacity-50 transition">Next <i class="fas fa-chevron-right ml-1"></i></button>
                     </div>
                 </div>
                 <button @click="isNewsListModalOpen = false" class="px-5 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                    <i class="fas fa-check mr-2"></i> Tutup
                 </button>
             </div>
        </div>
    </div>

    <!-- Sub Menu List Modal -->
    <div v-if="isSubMenuModalOpen" @click.self="isSubMenuModalOpen = false" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-[2px]">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-[500px] max-w-full max-h-[95vh] flex flex-col relative animate-fade-in-up text-left">
             <div style="background-color: #FCAD49;" class="text-white px-4 py-2 flex justify-between items-center shrink-0 border-b border-[#FCAD49]/30">
                <h2 class="text-base md:text-lg font-bold uppercase tracking-wide flex items-center truncate pr-2">
                    <i class="fas fa-layer-group mr-2 text-base md:text-lg text-white/90"></i>
                    <span class="truncate font-black">{{ submenuParentTitle }}</span>
                </h2>
                <button @click="isSubMenuModalOpen = false" class="text-white hover:text-white/80 transition bg-black/10 hover:bg-black/30 rounded-full w-7 h-7 flex items-center justify-center focus:outline-none shrink-0 shadow-sm">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto bg-gray-50 flex-1 space-y-2">
                 <div v-if="submenuItems.length === 0" class="text-center text-gray-500 italic p-4">Tidak ada sub-item ditemukan.</div>
                 
                 <!-- Recursive-like rendering (Manual for 1 level deep + Accordion) -->
                 <template v-for="sub in submenuItems" :key="sub.id">
                     <div @click="handleSubMenuClick(sub)"
                          @mousedown="handleMiddleClickPortal($event, sub)"
                          class="bg-white p-3 rounded border border-gray-200 shadow-sm hover:shadow-md hover:border-teal-300 cursor-pointer flex items-center transition group relative">
                          
                          <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 mr-4 group-hover:bg-teal-600 group-hover:text-white transition">
                               <img v-if="sub.image_name && sub.image_name.includes('.')" :src="`/images/${sub.image_name}`" class="w-6 h-6 object-contain">
                               <i v-else :class="['fas', 'fa-' + (sub.image_name || 'link'), 'text-lg']"></i>
                          </div>
                          
                          <div class="flex-1">
                               <h4 class="font-bold text-gray-700 text-sm group-hover:text-teal-700">{{ sub.title }}</h4>
                               <p class="text-[10px] text-gray-500 truncate">{{ sub.url || '#' }}</p>
                          </div>
                          
                          <!-- Chevron for children -->
                          <div v-if="getDeepChildren(sub.id).length > 0" class="ml-2">
                               <i :class="['fas text-gray-400 transition-transform duration-200', expandedSubmenuIds.includes(sub.id) ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                          </div>
                          <div v-else class="ml-2">
                               <i class="fas fa-external-link-alt text-gray-300 group-hover:text-teal-500 text-xs"></i>
                          </div>
                     </div>
                     
                     <!-- Render Children if Expanded -->
                     <transition name="fade-slide">
                        <div v-if="expandedSubmenuIds.includes(sub.id)" class="ml-8 pl-4 border-l-2 border-teal-200 space-y-1 mb-2">
                            <div v-for="child in getDeepChildren(sub.id)" :key="child.id" 
                                 @click.stop="handlePortalItemClick(child)"
                                 @mousedown="handleMiddleClickPortal($event, child)"
                                 class="p-2 hover:bg-teal-50 cursor-pointer rounded text-sm text-gray-600 flex items-center group-child">
                                 <i class="fas fa-caret-right text-teal-400 mr-2"></i>
                                 <span class="group-child-hover:text-teal-700 font-medium">{{ child.title }}</span>
                            </div>
                        </div>
                     </transition>
                 </template>
            </div>
            <div class="bg-white px-4 py-2 shrink-0 flex flex-col md:flex-row justify-end items-center border-t border-gray-200 shadow-[0_-2px_8px_-3px_rgba(0,0,0,0.05)] gap-3">
                <button @click="isSubMenuModalOpen = false" class="px-5 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-bold rounded shadow-sm transition duration-200 uppercase tracking-wide focus:outline-none flex items-center justify-center transform hover:-translate-y-0.5">
                    <i class="fas fa-check mr-2"></i> Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div v-if="isLogoutModalOpen" @click.self="isLogoutModalOpen = false" class="fixed inset-0 z-[60] flex items-center justify-center backdrop-blur-[2px]">
        <div class="bg-white rounded-lg shadow-2xl w-96 p-6 border border-gray-200 transform transition-all scale-100">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Konfirmasi Keluar</h3>
                <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin mengakhiri sesi Anda?</p>
            </div>
            <div class="flex justify-center space-x-3">
                <button @click="isLogoutModalOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Batal</button>
                <button @click="confirmLogout" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Keluar</button>
            </div>
        </div>
    </div>



    <!-- Global Confirmation / Action Modal -->
    <div v-if="globalDeleteModal.isOpen" @click.self="globalDeleteModal.isOpen = false" class="fixed inset-0 z-[20000] flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-[400px] overflow-hidden transform transition-all animate-scale-up">
            <div class="p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-6 group transition-all"
                    :class="globalDeleteModal.type === 'success' ? 'bg-emerald-100' : (globalDeleteModal.type === 'warning' ? 'bg-amber-100' : 'bg-red-100')">
                    <i :class="[globalDeleteModal.icon || 'fas fa-trash-alt', 'text-3xl group-hover:scale-110 transition-transform', 
                        globalDeleteModal.type === 'success' ? 'text-emerald-600' : (globalDeleteModal.type === 'warning' ? 'text-amber-600' : 'text-red-500')]"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ globalDeleteModal.title || 'Konfirmasi Tindakan' }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed px-4" v-html="globalDeleteModal.message || 'Apakah Anda yakin ingin memproses tindakan ini?'"></p>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-center gap-3">
                <button @click="globalDeleteModal.isOpen = false" class="px-6 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-100 transition shadow-sm cursor-pointer">Batal</button>
                <button @click="executeGlobalDelete" 
                    :disabled="globalDeleteModal.isLoading"
                    :class="['px-8 py-2 text-white rounded-xl font-bold text-sm transition shadow-lg disabled:opacity-50 flex items-center cursor-pointer', 
                        globalDeleteModal.confirmClass || 'bg-red-600 hover:bg-red-700 shadow-red-200']">
                    <i v-if="globalDeleteModal.isLoading" class="fas fa-spinner fa-spin mr-2"></i>
                    {{ globalDeleteModal.isLoading ? 'Memproses...' : (globalDeleteModal.confirmText || 'Ya, Lanjutkan') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast (Enhanced) -->
    <div v-if="notification.isOpen && !isModalOpen" class="fixed top-12 inset-x-0 z-[10000] flex justify-center pointer-events-none">
        <div :class="['flex items-center w-full max-w-md p-4 bg-white rounded-xl shadow-2xl border-l-8 transform transition-all duration-300 hover:scale-105 pointer-events-auto', 
            notification.type === 'success' ? 'border-teal-500' : 'border-red-500']" role="alert" class="animate-bounce-in">
             <div :class="['inline-flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full shadow-sm', 
                notification.type === 'success' ? 'text-teal-600 bg-teal-100' : 'text-red-600 bg-red-100']">
                 <i :class="['fas text-xl', notification.type === 'success' ? 'fa-check' : 'fa-exclamation-triangle']"></i>
            </div>
            <div class="ml-4 flex-1">
                <p :class="['font-bold text-sm', notification.type === 'success' ? 'text-teal-900' : 'text-red-900']">
                    {{ notification.type === 'success' ? 'Sukses!' : 'Perhatian!' }}
                </p>
                <p class="text-xs text-gray-600 font-medium mt-0.5">{{ notification.message }}</p>
            </div>
            <button type="button" @click="notification.isOpen = false" class="ml-2 -mx-1.5 -my-1.5 bg-transparent text-gray-400 hover:text-gray-900 rounded-lg p-2 hover:bg-gray-100 inline-flex h-8 w-8 justify-center items-center transition">
                <span class="sr-only">Close</span>
               <i class="fas fa-times text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Beautiful Status Modal (Success/Error) -->
    <div v-if="globalStatusModal.show" class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/40 backdrop-blur-[2px]">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col overflow-hidden animate-scale-up border" :class="globalStatusModal.type === 'success' ? 'border-green-100' : 'border-red-100'">
            <div class="px-5 py-3 flex items-center border-b flex-shrink-0" :class="globalStatusModal.type === 'success' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100'">
                <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 flex-shrink-0" :class="globalStatusModal.type === 'success' ? 'bg-green-100' : 'bg-red-100'">
                    <i class="fas text-sm" :class="[globalStatusModal.type === 'success' ? 'fa-check text-green-600' : 'fa-exclamation-triangle text-red-600']"></i>
                </div>
                <h3 class="font-bold text-base truncate" :class="globalStatusModal.type === 'success' ? 'text-green-800' : 'text-red-800'">{{ globalStatusModal.title }}</h3>
            </div>
            <div class="p-5 overflow-y-auto flex-1 custom-scrollbar">
                <p class="text-gray-600 leading-relaxed text-sm break-words whitespace-pre-wrap">{{ globalStatusModal.message }}</p>
                <div class="mt-5 flex justify-end">
                    <button @click="globalStatusModal.show = false" 
                        class="px-4 py-1.5 text-sm text-white font-semibold rounded-lg shadow-md transition-all"
                        :class="globalStatusModal.type === 'success' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'">
                        {{ globalStatusModal.type === 'success' ? 'Tutup' : 'Mengerti' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Error Analysis Modal -->
    <div v-if="showErrorDetailModal && selectedErrorLog" class="fixed inset-0 z-[11000] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden transform transition-all animate-scale-up text-slate-100 my-8">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-rose-950 text-rose-400">
                        <i class="fas fa-bug text-lg"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-black tracking-tight text-white">Analisis Kesalahan & Solusi Perbaikan</h3>
                        <p class="text-xs text-slate-400 font-medium">Sistem Monitoring Terpadu PortalCXC</p>
                    </div>
                </div>
                <button @click="closeErrorDetailModal" class="h-8 w-8 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6 custom-scrollbar text-sm">
                <!-- Meta Info Banner -->
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Tipe Error / Kelas</div>
                        <div class="font-mono text-rose-400 font-bold text-base">{{ selectedErrorLog.error_class }}</div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="space-y-1 text-center">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Status</div>
                            <div>
                                <button 
                                    @click="toggleErrorStatus(selectedErrorLog)" 
                                    class="px-3 py-1 text-xs font-bold rounded-lg border flex items-center space-x-1.5 transition-all cursor-pointer"
                                    :class="selectedErrorLog.status === 'Solved' ? 'bg-emerald-950/85 text-emerald-400 border-emerald-500/30 hover:bg-emerald-900/60' : 'bg-rose-950/85 text-rose-400 border-rose-500/30 hover:bg-rose-900/60'"
                                    title="Klik untuk mengubah status"
                                >
                                    <i class="fas" :class="selectedErrorLog.status === 'Solved' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-circle text-rose-400'"></i>
                                    <span>{{ selectedErrorLog.status === 'Solved' ? 'SOLVED' : 'ERROR' }}</span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1 md:text-right">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Waktu Terjadi</div>
                            <div class="font-mono text-indigo-300 font-bold">{{ formatTime(selectedErrorLog.created_at) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Error Message Alert Box -->
                <div class="bg-rose-950/20 border-l-4 border-rose-500 p-4 rounded-r-xl">
                    <div class="text-xs font-bold text-rose-400 uppercase tracking-wider mb-1">Pesan Error Mentah (Raw Message)</div>
                    <p class="font-mono text-sm text-rose-100 font-bold break-words">{{ selectedErrorLog.error_message }}</p>
                    <div class="mt-2 text-xs text-rose-300/80 font-mono" v-if="selectedErrorLog.file">
                        Lokasi: <span class="text-rose-300 font-bold">{{ selectedErrorLog.file }}</span> 
                        <span v-if="selectedErrorLog.line"> pada baris <span class="bg-rose-900/50 px-1.5 py-0.5 rounded text-white font-bold">{{ selectedErrorLog.line }}</span></span>
                    </div>
                </div>

                <!-- Explanation & Suggestion (Client Requirement) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Explanation Box (Amber/Kuning) -->
                    <div class="bg-amber-950/20 border border-amber-500/40 rounded-xl p-5 shadow-sm space-y-3">
                        <div class="flex items-center space-x-2 text-amber-400">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                            <h4 class="font-bold text-base text-amber-300">Mengapa Ini Terjadi? (Penyebab)</h4>
                        </div>
                        <p class="text-amber-100/90 leading-relaxed font-semibold">
                            {{ selectedErrorLog.explanation || 'Analisis penyebab tidak tersedia untuk tipe error ini.' }}
                        </p>
                    </div>

                    <!-- Suggestion Box (Emerald/Hijau) -->
                    <div class="bg-emerald-950/20 border border-emerald-500/40 rounded-xl p-5 shadow-sm space-y-3">
                        <div class="flex items-center space-x-2 text-emerald-400">
                            <i class="fas fa-check-circle text-xl"></i>
                            <h4 class="font-bold text-base text-emerald-300">Langkah Perbaikan (Solusi)</h4>
                        </div>
                        <div class="text-emerald-100/90 leading-relaxed font-semibold whitespace-pre-line">
                            {{ selectedErrorLog.suggestion || 'Saran perbaikan tidak tersedia untuk tipe error ini.' }}
                        </div>
                    </div>
                </div>

                <!-- Technical Stack Trace -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center">
                            <i class="fas fa-code mr-1.5 text-indigo-400"></i> Technical Stack Trace
                        </h4>
                        <button 
                            @click="copyErrorTrace" 
                            class="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center space-x-1 px-2.5 py-1 bg-slate-950 border border-slate-800 rounded-lg transition-all"
                        >
                            <i class="fas" :class="isCopied ? 'fa-check text-emerald-400' : 'fa-copy'"></i>
                            <span>{{ isCopied ? 'Tersalin!' : 'Salin Trace' }}</span>
                        </button>
                    </div>
                    <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 overflow-x-auto max-h-60 overflow-y-auto">
                        <pre class="font-mono text-xs text-slate-300 leading-relaxed whitespace-pre">{{ selectedErrorLog.trace || 'Tidak ada data stack trace.' }}</pre>
                    </div>
                </div>

                <!-- Client context details -->
                <div class="bg-slate-950/50 p-4 rounded-xl border border-slate-800/80 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono text-slate-400">
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">HTTP METHOD</div>
                        <div class="font-bold text-slate-200">{{ selectedErrorLog.method || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">USER AGENT</div>
                        <div class="font-bold text-slate-200 truncate max-w-xs" :title="selectedErrorLog.user_agent || 'Unknown'">{{ selectedErrorLog.method === 'CLIENT' ? 'Browser Client' : 'Server Backend' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">IP ADDRESS</div>
                        <div class="font-bold text-slate-200">{{ selectedErrorLog.ip_address || '127.0.0.1' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">PENGGUNA AKTIF</div>
                        <div class="font-bold text-rose-300">{{ selectedErrorLog.username || 'System (Guest)' }}</div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-950 border-t border-slate-800 flex justify-end">
                <button 
                    @click="closeErrorDetailModal" 
                    class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-bold rounded-lg transition"
                >
                    Tutup Modal
                </button>
            </div>
        </div>
    </div>

    <!-- Transaction Loading Overlay -->
    <div v-if="isNewsTransactionLoading" class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-black/40 backdrop-blur-[1px]">
        <div class="bg-white p-6 rounded-xl shadow-2xl flex flex-col items-center space-y-4 animate-scale-up">
            <div class="relative w-16 h-16">
                <div class="absolute inset-0 border-4 border-teal-100 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-teal-500 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <div class="text-center">
                <p class="text-gray-800 font-bold">Memproses Data...</p>
                <p class="text-gray-500 text-xs mt-1">Harap tunggu sebentar</p>
            </div>
        </div>
    </div>

    <!-- Image & Content Gallery Modal (Continuous Scroll through all images & sections) -->
    <div v-if="isGalleryOpen" @click="isGalleryOpen = false" class="fixed inset-0 z-[30000] flex flex-col items-center bg-black/92 backdrop-blur-md animate-fade-in select-none">
        <!-- Top Sticky Header Controls -->
        <div class="w-full max-w-6xl flex justify-between items-center px-4 py-3 shrink-0 z-20 bg-black/40 border-b border-white/10 backdrop-blur-md" @click.stop>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-white">
                    <i class="fas fa-images text-teal-400 text-lg"></i>
                    <span class="font-bold text-sm sm:text-base tracking-wide">Galeri Konten</span>
                    <span class="text-xs bg-teal-500/20 text-teal-300 px-2.5 py-0.5 rounded-full font-semibold border border-teal-500/30">
                        {{ galleryImages.length }} Gambar
                    </span>
                </div>
                
                <!-- View Mode Switcher -->
                <div class="flex items-center bg-white/10 rounded-lg p-0.5 border border-white/10">
                    <button @click="galleryViewMode = 'feed'" 
                            :class="galleryViewMode === 'feed' ? 'bg-teal-600 text-white shadow' : 'text-gray-300 hover:text-white'" 
                            class="px-3 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 cursor-pointer" 
                            title="Scroll semua gambar dari atas ke bawah">
                        <i class="fas fa-stream"></i> <span class="hidden sm:inline">Scroll Semua</span>
                    </button>
                    <button @click="galleryViewMode = 'slide'" 
                            :class="galleryViewMode === 'slide' ? 'bg-teal-600 text-white shadow' : 'text-gray-300 hover:text-white'" 
                            class="px-3 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 cursor-pointer" 
                            title="Tampilkan satu per satu">
                        <i class="fas fa-image"></i> <span class="hidden sm:inline">Satu per Satu</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Navigation in Slide Mode -->
                <div v-if="galleryViewMode === 'slide' && galleryImages.length > 1" class="flex items-center gap-1.5 mr-2">
                    <button @click="prevGalleryImage" class="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs font-bold transition cursor-pointer" title="Sebelumnya">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="text-xs text-white/80 font-semibold px-1">
                        {{ activeGalleryIndex + 1 }} / {{ galleryImages.length }}
                    </span>
                    <button @click="nextGalleryImage" class="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs font-bold transition cursor-pointer" title="Selanjutnya">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <button @click="isGalleryOpen = false" class="text-white hover:text-red-400 bg-white/10 hover:bg-white/25 rounded-full w-8 h-8 flex items-center justify-center text-lg font-bold transition shadow cursor-pointer focus:outline-none border border-white/10" title="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Quick Jump Thumbnail / Index Bar (if more than 1 image) -->
        <div v-if="galleryImages.length > 1" class="w-full max-w-6xl px-4 py-2 flex items-center gap-2 overflow-x-auto shrink-0 bg-black/30 border-b border-white/5 custom-scrollbar" @click.stop>
            <span class="text-[11px] text-gray-400 font-semibold uppercase shrink-0">Lompat ke:</span>
            <button v-for="(img, idx) in galleryImages" :key="idx" 
                    @click="scrollToGalleryImage(idx)" 
                    :class="activeGalleryIndex === idx ? 'bg-teal-500 text-white font-bold border-teal-400 shadow-md' : 'bg-white/10 text-gray-300 hover:bg-white/20 border-white/10'" 
                    class="px-3 py-1 rounded-full text-xs transition whitespace-nowrap shrink-0 border flex items-center gap-1.5 cursor-pointer">
                <span class="w-4 h-4 rounded-full bg-black/30 text-[10px] flex items-center justify-center font-bold">{{ idx + 1 }}</span>
                <span class="truncate max-w-[140px]">{{ img.caption }}</span>
            </button>
        </div>

        <!-- Mode Feed: Continuous Vertical Scroll of All Images -->
        <div v-if="galleryViewMode === 'feed'" class="w-full max-w-5xl flex-1 overflow-y-auto py-8 px-4 flex flex-col items-center gap-12 custom-scrollbar" @click.self="isGalleryOpen = false">
            <div v-for="(img, idx) in galleryImages" :key="idx" :id="'gallery-img-' + idx" class="w-full flex flex-col items-center gap-3 transition-all scroll-mt-6" @click.stop>
                <!-- Section Caption / Header for this image -->
                <div class="bg-gray-900/90 text-teal-300 border border-teal-500/40 px-5 py-2 rounded-xl text-sm font-bold shadow-xl flex items-center justify-between w-full max-w-4xl backdrop-blur-md">
                    <div class="flex items-center gap-2.5 truncate">
                        <span class="w-6 h-6 rounded-full bg-teal-500 text-gray-900 font-black text-xs flex items-center justify-center shrink-0">{{ idx + 1 }}</span>
                        <span class="truncate">{{ img.caption }}</span>
                    </div>
                    <span class="text-xs text-gray-400 font-medium shrink-0 ml-3">Gambar {{ idx + 1 }} dari {{ galleryImages.length }}</span>
                </div>
                <!-- The Large High-Res Image -->
                <img :src="img.src" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-white/20 bg-gray-950/80" :alt="img.alt" />
            </div>

            <div class="text-center py-6 text-gray-400 text-xs flex flex-col items-center gap-1">
                <i class="fas fa-check-circle text-teal-400 text-base mb-1"></i>
                <span class="font-bold">Akhir dari Galeri Dokumen Ini</span>
                <span>Seluruh gambar panduan telah ditampilkan.</span>
            </div>
        </div>

        <!-- Mode Slide: One Image Focus with Navigation -->
        <div v-else class="w-full max-w-6xl flex-1 flex items-center justify-center relative p-4" @click.self="isGalleryOpen = false">
            <button v-if="galleryImages.length > 1" @click.stop="prevGalleryImage" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/15 hover:bg-white/30 text-white rounded-full w-12 h-12 flex items-center justify-center transition shadow-2xl backdrop-blur-sm cursor-pointer z-10 border border-white/20">
                <i class="fas fa-chevron-left text-xl"></i>
            </button>

            <div class="flex flex-col items-center gap-3 max-w-full max-h-[85vh]" @click.stop>
                <div class="bg-gray-900/90 text-teal-300 border border-teal-500/40 px-5 py-2 rounded-xl text-sm font-bold shadow-xl flex items-center gap-2 backdrop-blur-md">
                    <span class="w-6 h-6 rounded-full bg-teal-500 text-gray-900 font-black text-xs flex items-center justify-center shrink-0">{{ activeGalleryIndex + 1 }}</span>
                    <span>{{ galleryImages[activeGalleryIndex]?.caption }}</span>
                </div>
                <img :src="galleryImages[activeGalleryIndex]?.src" class="max-w-full max-h-[75vh] object-contain rounded-2xl shadow-2xl border border-white/20 bg-gray-950/80" :alt="galleryImages[activeGalleryIndex]?.alt" />
            </div>

            <button v-if="galleryImages.length > 1" @click.stop="nextGalleryImage" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/15 hover:bg-white/30 text-white rounded-full w-12 h-12 flex items-center justify-center transition shadow-2xl backdrop-blur-sm cursor-pointer z-10 border border-white/20">
                <i class="fas fa-chevron-right text-xl"></i>
            </button>
        </div>

        <div class="py-2 text-[11px] text-white/50 font-medium shrink-0 flex items-center gap-2">
            <span>Gunakan scroll mouse untuk melihat semua gambar</span>
            <span>•</span>
            <span>Tekan Esc atau klik silang untuk menutup</span>
        </div>
    </div>
</template>

<style scoped>
/* CKEditor Custom Styles */
:deep(.ck-editor__editable) {
    min-height: 450px;
    max-height: 70vh;
    border-radius: 0 0 8px 8px !important;
}
:deep(.ck-editor__editable.format-painter-active) {
    cursor: copy !important;
}
:deep(.ck-toolbar) {
    border-radius: 8px 8px 0 0 !important;
    border-color: #e5e7eb !important;
    background-color: #f9fafb !important;
}
:deep(.ck-content) {
    font-size: 0.875rem;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}
:deep(.ck-content) ul,
:deep(.announcement-content) ul {
    list-style-type: disc !important;
    padding-left: 2rem !important;
    margin-top: 0.75rem !important;
    margin-bottom: 0.75rem !important;
    display: block !important;
}
:deep(.ck-content) ol,
:deep(.announcement-content) ol {
    list-style-type: decimal !important;
    padding-left: 2rem !important;
    margin-top: 0.75rem !important;
    margin-bottom: 0.75rem !important;
    display: block !important;
}
:deep(.ck-content) li,
:deep(.announcement-content) li {
    display: list-item !important;
    margin-bottom: 0.35rem !important;
}
:deep(.ck-content) ul ul,
:deep(.announcement-content) ul ul {
    list-style-type: circle !important;
    margin-top: 0.25rem !important;
    margin-bottom: 0.25rem !important;
}
:deep(.ck-content) ul ul ul,
:deep(.announcement-content) ul ul ul {
    list-style-type: square !important;
}
:deep(.ck-content) ol ol,
:deep(.announcement-content) ol ol {
    list-style-type: lower-alpha !important;
    margin-top: 0.25rem !important;
    margin-bottom: 0.25rem !important;
}
:deep(.ck-content) ol ol ol,
:deep(.announcement-content) ol ol ol {
    list-style-type: lower-roman !important;
}
:deep(.ck-content) table {
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
}
:deep(.ck-content) table td, 
:deep(.ck-content) table th {
    border: 1px solid #cbd5e1;
    padding: 6px 8px;
    min-width: 0.40cm !important;
}
:deep(.ck-content) figure.table[style*="border-style:none"] table,
:deep(.ck-content) figure.table[style*="border-color:transparent"] table,
:deep(.ck-content) table.table-borderless,
:deep(.ck-content) table.cxc-colon-table,
:deep(.ck-content) table[style*="border:none"],
:deep(.ck-content) table[style*="border: 0"],
:deep(.ck-content) table[style*="border-style:none"],
:deep(.ck-content) table[style*="border-color:transparent"],
:deep(.ck-widget.table) table[style*="border:none"],
:deep(.ck-widget.table) table[style*="border-style:none"],
:deep(.ck-widget.table) table[style*="border:0"],
:deep(.ck-widget.table) table[style*="border-width:0"] {
    border: none !important;
    outline: none !important;
}
:deep(.ck-content) figure.table[style*="border-style:none"] td,
:deep(.ck-content) figure.table[style*="border-style:none"] th,
:deep(.ck-content) figure.table[style*="border-color:transparent"] td,
:deep(.ck-content) figure.table[style*="border-color:transparent"] th,
:deep(.ck-content) table.table-borderless td,
:deep(.ck-content) table.table-borderless th,
:deep(.ck-content) table.cxc-colon-table td,
:deep(.ck-content) table.cxc-colon-table th,
:deep(.ck-content) table[style*="border:none"] td,
:deep(.ck-content) table[style*="border:none"] th,
:deep(.ck-content) table[style*="border: 0"] td,
:deep(.ck-content) table[style*="border: 0"] th,
:deep(.ck-content) table[style*="border-style:none"] td,
:deep(.ck-content) table[style*="border-style:none"] th,
:deep(.ck-content) table[style*="border-color:transparent"] td,
:deep(.ck-content) table[style*="border-color:transparent"] th,
:deep(.ck-content) table td[style*="border-style:none"],
:deep(.ck-content) table th[style*="border-style:none"],
:deep(.ck-content) table td[style*="border-color:transparent"],
:deep(.ck-content) table th[style*="border-color:transparent"],
:deep(.ck-content) table td[style*="border:none"],
:deep(.ck-content) table th[style*="border:none"] {
    border: none !important;
    outline: none !important;
}
:deep(.ck-content) table col,
:deep(.announcement-content) table col {
    min-width: 0.40cm !important;
}
:deep(.ck-content) table col[style*="width: 0.4"],
:deep(.ck-content) table col[style*="width:0.4"],
:deep(.ck-content) table td[style*="width: 0.4"],
:deep(.ck-content) table td[style*="width:0.4"],
:deep(.ck-content) table th[style*="width: 0.4"],
:deep(.ck-content) table th[style*="width:0.4"] {
    width: 0.40cm !important;
    max-width: 0.40cm !important;
    min-width: 0.40cm !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
    text-align: center;
}
:deep(.ck-content) table th {
    background-color: transparent !important;
}
:deep(.ck-content) blockquote {
    border-left: 4px solid #d1d5db !important;
    padding-left: 1.5rem !important;
    font-style: italic !important;
    color: #4b5563 !important;
    margin: 1.5rem 0 !important;
}
:deep(.ck-content) a {
    color: #2563eb !important;
    font-weight: bold !important;
    text-decoration: underline !important;
}
/* Hide SR text and ensure icons show in editor */
:deep(.ck-content) .sr-only {
    display: none !important;
}
:deep(.ck-content) i.fas {
    font-family: "Font Awesome 5 Free" !important;
    font-weight: 900 !important;
    display: inline-block !important;
}
/* Defensive styling for HTML Embed */
:deep(.ck-content) .raw-html-embed {
    margin: 1.5rem 0 !important;
    max-width: 100% !important;
    overflow-x: auto !important;
    background: transparent !important;
}
:deep(.ck-content) {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    max-width: 100% !important;
}
:deep(.ck-content) marquee {
    display: block !important;
    width: 100% !important;
}

/* CKEditor Image Alignment Styles */
:deep(.ck-content) .image {
    display: table;
    clear: both;
    text-align: center;
    margin: 0.9em auto;
}
:deep(.ck-content) .image img,
:deep(.announcement-content) .image img,
:deep(.ck-content) img,
:deep(.announcement-content) img {
    display: block;
    margin: 0.75rem auto;
    max-width: 100%;
    height: auto;
    image-rendering: auto !important;
    border-radius: 6px;
    cursor: zoom-in;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
:deep(.ck-content) .image img:hover,
:deep(.announcement-content) .image img:hover,
:deep(.ck-content) img:hover,
:deep(.announcement-content) img:hover {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}
:deep(.ck-content) .image-style-block-align-left,
:deep(.ck-content) .image-style-align-left {
    float: left;
    margin-right: 1.5em;
    clear: none;
}
:deep(.ck-content) .image-style-block-align-right,
:deep(.ck-content) .image-style-align-right {
    float: right;
    margin-left: 1.5em;
    clear: none;
}
:deep(.ck-content) .image-style-align-center {
    margin-left: auto;
    margin-right: auto;
}
:deep(.ck-content) .image.text-align-center {
    margin-left: auto;
    margin-right: auto;
}

/* Optimized Fullscreen Reader Styles for crisp, large imagery */
:deep(.fullscreen-reader) figure.image {
    width: 100% !important;
    max-width: 100% !important;
    display: block !important;
    text-align: center !important;
    margin: 1.5rem auto !important;
}
:deep(.fullscreen-reader) figure.image img,
:deep(.fullscreen-reader) img {
    max-width: 100% !important;
    width: auto !important;
    max-height: 85vh !important;
    display: block !important;
    margin: 0 auto !important;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}
:deep(.fullscreen-reader) p {
    font-size: 1rem !important;
    line-height: 1.8 !important;
}
:deep(.ck.ck-editor__main>.ck-editor__editable:not(.ck-focused)) {
    border-color: #e5e7eb !important;
}
@keyframes error-pulse {
    0%, 100% { border-color: #dc2626; box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1); }
    50% { border-color: #b91c1c; box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.2); }
}
.animate-error-pulse {
    animation: error-pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes content-blink {
    0%, 100% { background-color: #fef2f2; }
    50% { background-color: #fee2e2; }
}
.animate-content-blink {
    animation: content-blink 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-fast {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.05); }
}
.animate-pulse-fast {
    animation: pulse-fast 0.6s ease-in-out infinite;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}
.custom-scrollbar-thin::-webkit-scrollbar {
    width: 2px;
}
@keyframes bounceIn {
    0% { opacity: 0; transform: translateY(-20px); }
    60% { opacity: 1; transform: translateY(10px); }
    80% { transform: translateY(-5px); }
    100% { transform: translateY(0); }
}
.animate-bounce-in {
    animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-scale-up {
    animation: scaleUp 0.3s ease-out forwards;
}
.custom-scrollbar-thin::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
}
.custom-scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 1px;
}

/* Responsive Zoom for smaller laptop screens (e.g. 1366x768) to prevent vertical scrolling */
@media screen and (max-height: 768px) and (min-width: 1024px) {
    .agent-layout-wrapper {
        zoom: 0.75;
        height: calc(100vh / 0.75) !important;
        max-height: calc(100vh / 0.75) !important;
        overflow: hidden !important;
    }
}
@media screen and (max-height: 850px) and (min-width: 1024px) and (min-height: 769px) {
    .agent-layout-wrapper {
        zoom: 0.85;
        height: calc(100vh / 0.85) !important;
        max-height: calc(100vh / 0.85) !important;
        overflow: hidden !important;
    }
}

/* Office Paste Symbol & List Styling to prevent symbol clipping */
.ck-content .cxc-bullet-prefix,
.ck-content .cxc-bullet-symbol {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    margin-right: 6px !important;
    min-width: 14px !important;
    line-height: 1 !important;
    vertical-align: middle !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    font-weight: bold !important;
    font-size: 1em !important;
    user-select: none !important;
}

.ck-content .cxc-bullet-line {
    margin-top: 0.25rem !important;
    margin-bottom: 0.25rem !important;
    line-height: 1.5 !important;
    overflow: visible !important;
    text-indent: 0 !important;
}

.ck-content ul {
    padding-left: 2rem !important;
    margin-left: 0 !important;
    list-style-position: outside !important;
}

.ck-content ul[style*="list-style-type: none"],
.ck-content ul[style*="list-style-type:none"],
.ck-content li[style*="list-style-type: none"],
.ck-content li[style*="list-style-type:none"] {
    list-style-type: none !important;
}

.ck-content li {
    padding-left: 0 !important;
    margin-bottom: 0.25rem !important;
}

/* Word-like List Properties Dropdown (Bullet Library & Numbering Library styling) */
.ck.ck-list-styles-list {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 8px !important;
    padding: 10px !important;
    background: #f8fafc !important;
    border-radius: 6px !important;
}

.ck.ck-list-styles-list .ck-button {
    border: 1px solid #cbd5e1 !important;
    border-radius: 4px !important;
    padding: 8px !important;
    background: #ffffff !important;
    transition: all 0.15s ease-in-out !important;
    min-width: 54px !important;
    min-height: 48px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.ck.ck-list-styles-list .ck-button:hover {
    border-color: #0284c7 !important;
    background: #e0f2fe !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.ck.ck-list-styles-list .ck-button.ck-on {
    border-color: #0284c7 !important;
    background: #bae6fd !important;
}

</style>
