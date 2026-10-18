/**
 * Mock Data & Handler for Standalone Demo Mode (GitHub Pages / No Backend)
 */

export const MOCK_USERS_DATA = [
    {
        id: 1,
        username: 'superadmin_demo',
        password: 'password123',
        attempt_count: 0,
        last_login: new Date().toISOString(),
        employee: {
            id: 1,
            npp: 'DEMO001',
            name: 'Super Administrator Demo',
            email: 'superadmin@demo.com',
            is_active: 1,
            roles: [
                {
                    id: 1,
                    role_id: 3,
                    unit_id: 13,
                    status_id: 1,
                    role: { id: 3, name: 'Super Admin', type: 'role', value: 3 },
                    unit: { id: 13, name: 'Super Admin', code: 'Super Admin', short_name: 'Super Admin' }
                }
            ]
        }
    },
    {
        id: 2,
        username: 'admin_banking',
        password: 'password123',
        attempt_count: 0,
        last_login: new Date().toISOString(),
        employee: {
            id: 2,
            npp: 'DEMO002',
            name: 'Admin Banking Demo',
            email: 'admin_banking@demo.com',
            is_active: 1,
            roles: [
                {
                    id: 2,
                    role_id: 1,
                    unit_id: 2,
                    status_id: 1,
                    role: { id: 1, name: 'Admin', type: 'role', value: 1 },
                    unit: { id: 2, name: 'Banking', code: 'Banking', short_name: 'Banking' }
                }
            ]
        }
    },
    {
        id: 3,
        username: 'agent_banking',
        password: 'password123',
        attempt_count: 0,
        last_login: new Date().toISOString(),
        employee: {
            id: 3,
            npp: 'DEMO003',
            name: 'Agent Banking Demo',
            email: 'agent_banking@demo.com',
            is_active: 1,
            roles: [
                {
                    id: 3,
                    role_id: 2,
                    unit_id: 2,
                    status_id: 1,
                    role: { id: 2, name: 'Agent', type: 'role', value: 2 },
                    unit: { id: 2, name: 'Banking', code: 'Banking', short_name: 'Banking' }
                }
            ]
        }
    }
];

export const MOCK_UNITS = [
    { id: 2, name: 'Banking', code: 'Banking', short_name: 'Banking', is_active: 1, type: 1, address: 'Jakarta' },
    { id: 1, name: 'Credit', code: 'Credit', short_name: 'Credit', is_active: 1, type: 1, address: 'Jakarta' },
    { id: 3, name: 'Emerald', code: 'Emerald', short_name: 'Emerald', is_active: 1, type: 1, address: 'Jakarta' },
    { id: 4, name: 'Merchant', code: 'Merchant', short_name: 'Merchant', is_active: 1, type: 1, address: 'Jakarta' },
    { id: 5, name: 'SAT', code: 'SAT', short_name: 'SAT', is_active: 1, type: 1, address: 'Jakarta' },
    { id: 8, name: 'TnR', code: 'TnR', short_name: 'TnR', is_active: 1, type: 1, address: 'Jakarta' },
    { id: 13, name: 'Super Admin', code: 'Super Admin', short_name: 'Super Admin', is_active: 1, type: 1, address: 'Jakarta' }
];

export const MOCK_NEWS = [
    {
        id: 1,
        title: 'Panduan Operasional Layanan Contact Center Terpadu 2026',
        category: 'Panduan',
        keyword: 'Panduan, SOP, Operasional',
        content: '<p>Berikut ini adalah panduan komprehensif alur eskalasi tiket nasabah serta standar penanganan inquiry operasional di lingkungan Contact Center.</p>',
        created_name: 'Super Administrator Demo',
        created_at: new Date().toISOString(),
        updated_name: 'Super Administrator Demo',
        updated_at: new Date().toISOString(),
        target_unit: ['Banking', 'Credit', 'Emerald'],
        is_active: 1,
        headline: 'Yes',
        flag: 1,
        view_count: 142
    },
    {
        id: 2,
        title: 'Sosialisasi Program Loyalty Nasabah Prioritas Unit Banking',
        category: 'Promo',
        keyword: 'Loyalty, Promo, Prioritas',
        content: '<p>Program rewards dan cashback saldo khusus nasabah terpilih unit Banking yang bertransaksi aktif menggunakan mobile banking.</p>',
        created_name: 'Admin Banking Demo',
        created_at: new Date().toISOString(),
        updated_name: 'Admin Banking Demo',
        updated_at: new Date().toISOString(),
        target_unit: ['Banking'],
        is_active: 1,
        headline: 'Yes',
        flag: 1,
        view_count: 89
    },
    {
        id: 3,
        title: 'Maintenance Terjadwal Sistem Verifikasi OTP',
        category: 'Pengumuman',
        keyword: 'Maintenance, OTP, Security',
        content: '<p>Pemberitahuan pemeliharaan server gateway autentikasi pada akhir pekan. Agent diharapkan menginformasikan alternatif validasi transaksi nasabah.</p>',
        created_name: 'Super Administrator Demo',
        created_at: new Date().toISOString(),
        updated_name: 'Super Administrator Demo',
        updated_at: new Date().toISOString(),
        target_unit: ['Banking', 'Credit', 'Merchant'],
        is_active: 1,
        headline: 'No',
        flag: 1,
        view_count: 215
    }
];

export const MOCK_SETTINGS = [
    { id: 1, section: 'background', category: 'General', image_name: '1779789895_1778942904_19.png', title: 'Portal Background' },
    { id: 2, section: 'logo', category: 'General', image_name: 'logo.png', title: 'PortalCXC' },
    { id: 3, section: 'Menu 1', category: 'Menu 1', title: 'Knowledge Base', url: '#/news', image_name: '3d/menu-1-knowledge.svg' },
    { id: 4, section: 'Menu 2', category: 'Menu 2', title: 'App Directory', url: '#', image_name: '3d/menu-2-apps.svg' },
    { id: 5, section: 'Menu 3', category: 'Menu 3', title: 'Portal Menu', url: '#', image_name: '3d/menu-3-portal.svg' },
    { id: 6, section: 'Menu 4', category: 'Menu 4', title: 'Workflows & Forms', url: '#', image_name: '3d/menu-4-options.svg' },
    { id: 7, section: 'Menu 5', category: 'Menu 5', title: 'News Bulletin', url: '#/news', image_name: '3d/menu-5-news.svg' },
    { id: 8, section: 'Menu 6', category: 'Menu 6', title: 'Sharing Moment', url: '#', image_name: '3d/menu-6-sharing.svg' },
    { id: 9, section: 'Menu 7', category: 'Menu 7', title: 'Hero Highlights', url: '#', image_name: '3d/menu-7-banner.svg' },
    { id: 10, section: 'Menu 8', category: 'Menu 8', title: 'Suku Bunga & Kurs', url: '#', image_name: '3d/menu-8-rates.svg' },
    { id: 11, section: 'Menu 9', category: 'Menu 9', title: 'Giro Korporasi', url: '#', image_name: '3d/menu-9-giro.svg' },
    { id: 12, section: 'Menu 10', category: 'Menu 10', title: 'Promo & Rewards', url: '#', image_name: '3d/menu-10-promo.svg' },
    { id: 13, section: 'Menu 11', category: 'Menu 11', title: 'Alerts & Notice', url: '#', image_name: '3d/menu-11-alert.svg' },
    { id: 14, section: 'Menu 12', category: 'Menu 12', title: 'Quick Links', url: '#', image_name: '3d/menu-12-links.svg' },
    { id: 15, section: 'Menu 13', category: 'Menu 13', title: 'Simulasi Hitung', url: '#', image_name: '3d/menu-13-calculator.svg' },
    { id: 16, section: 'Menu 14', category: 'Menu 14', title: 'SOP & Guides', url: '#', image_name: '3d/menu-14-guide.svg' },
    { id: 17, section: 'Menu 15', category: 'Menu 15', title: 'External Tools', url: '#', image_name: '3d/menu-15-external.svg' }
];

export const MOCK_DASHBOARD_STATS = {
    total_users: 3,
    active_users: 3,
    total_news: 3,
    units: ['Banking', 'Credit', 'Emerald', 'Merchant', 'SAT'],
    login_stats: {
        chart: Array.from({ length: 30 }, (_, i) => ({
            date: `2026-10-${String(i + 1).padStart(2, '0')}`,
            count: Math.floor(Math.random() * 20) + 5,
            label: `${i + 1} Oct`
        }))
    },
    frequent_users: [
        { npp: 'DEMO001', name: 'Super Administrator Demo', unit: 'Super Admin', login_count: 32 },
        { npp: 'DEMO002', name: 'Admin Banking Demo', unit: 'Banking', login_count: 28 },
        { npp: 'DEMO003', name: 'Agent Banking Demo', unit: 'Banking', login_count: 45 }
    ]
};
