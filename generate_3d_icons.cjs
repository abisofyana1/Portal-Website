const fs = require('fs');
const path = require('path');

const targetDir = path.join(__dirname, 'public', 'images', '3d');
if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir, { recursive: true });
}

// Helper to create glowing 3D vector SVG
function create3DSvg({ id, bgGrad, sphereGrad, iconSvg, label, accentColor }) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="100%" height="100%">
  <defs>
    <!-- Background Radial Gradient -->
    <radialGradient id="bgGrad_${id}" cx="50%" cy="30%" r="70%">
      <stop offset="0%" stop-color="${bgGrad[0]}" />
      <stop offset="60%" stop-color="${bgGrad[1]}" />
      <stop offset="100%" stop-color="${bgGrad[2]}" />
    </radialGradient>

    <!-- 3D Sphere / Prism Lighting Gradient -->
    <radialGradient id="sphereGrad_${id}" cx="35%" cy="30%" r="65%">
      <stop offset="0%" stop-color="${sphereGrad[0]}" />
      <stop offset="45%" stop-color="${sphereGrad[1]}" />
      <stop offset="85%" stop-color="${sphereGrad[2]}" />
      <stop offset="100%" stop-color="#050b14" />
    </radialGradient>

    <!-- Metallic Specular Highlight Gradient -->
    <linearGradient id="specular_${id}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.85" />
      <stop offset="30%" stop-color="#ffffff" stop-opacity="0.3" />
      <stop offset="70%" stop-color="${accentColor}" stop-opacity="0.1" />
      <stop offset="100%" stop-color="#000000" stop-opacity="0.6" />
    </linearGradient>

    <!-- Glow Filter -->
    <filter id="glow_${id}" x="-30%" y="-30%" width="160%" height="160%">
      <feGaussianBlur stdDeviation="16" result="blur" />
      <feComposite in="SourceGraphic" in2="blur" operator="over" />
    </filter>

    <!-- Drop Shadow for 3D elements -->
    <filter id="shadow_${id}" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="18" stdDeviation="14" flood-color="#000000" flood-opacity="0.65" />
    </filter>
  </defs>

  <!-- Ambient background glow & rounded card -->
  <rect width="512" height="512" rx="100" fill="url(#bgGrad_${id})" />
  <rect width="508" height="508" x="2" y="2" rx="98" fill="none" stroke="url(#specular_${id})" stroke-width="4" stroke-opacity="0.5" />

  <!-- 3D Pedestal / Floating Platform Shadow -->
  <ellipse cx="256" cy="420" rx="150" ry="32" fill="#000000" opacity="0.45" filter="url(#glow_${id})" />

  <!-- Floating 3D Orb / Main Capsule -->
  <g filter="url(#shadow_${id})">
    <circle cx="256" cy="245" r="140" fill="url(#sphereGrad_${id})" />
    <!-- Glass/Glossy Reflection Arch -->
    <path d="M 150 200 A 130 130 0 0 1 362 200 A 130 90 0 0 0 150 200 Z" fill="#ffffff" opacity="0.25" />
    <!-- Inner Rim Glow -->
    <circle cx="256" cy="245" r="138" fill="none" stroke="${accentColor}" stroke-width="3" opacity="0.6" />
  </g>

  <!-- 3D Geometric Floating Rings / Accents -->
  <ellipse cx="256" cy="245" rx="175" ry="48" fill="none" stroke="${accentColor}" stroke-width="6" opacity="0.75" transform="rotate(-25 256 245)" filter="url(#glow_${id})" />
  <ellipse cx="256" cy="245" rx="175" ry="48" fill="none" stroke="#ffffff" stroke-width="2" opacity="0.4" transform="rotate(-25 256 245)" />

  <!-- Front 3D Icon Graphic -->
  <g filter="url(#shadow_${id})" transform="translate(146, 135) scale(0.44)">
    ${iconSvg}
  </g>

  <!-- Glossy Front Sphere Highlight -->
  <ellipse cx="210" cy="165" rx="42" ry="22" fill="#ffffff" opacity="0.6" transform="rotate(-30 210 165)" />

  <!-- Subtitle Tag / Badge on 3D Card -->
  <g transform="translate(256, 435)">
    <rect x="-100" y="-18" width="200" height="36" rx="18" fill="#0f172a" opacity="0.8" stroke="${accentColor}" stroke-width="1.5" />
    <text x="0" y="6" text-anchor="middle" font-family="'Segoe UI', Roboto, sans-serif" font-weight="800" font-size="14" fill="#ffffff" letter-spacing="2">${label.toUpperCase()}</text>
  </g>
</svg>`;
}

// 15 Menu Configs
const menus = [
  {
    file: 'menu-1-knowledge.svg',
    id: 'm1',
    bgGrad: ['#38bdf8', '#0284c7', '#082f49'],
    sphereGrad: ['#67e8f9', '#0ea5e9', '#0369a1'],
    accentColor: '#38bdf8',
    label: 'Knowledge Base',
    // 3D Open Book / Brain knowledge icon
    iconSvg: `
      <path d="M256 120 C 340 70, 440 80, 480 90 L 480 390 C 440 380, 340 370, 256 420 C 172 370, 72 380, 32 390 L 32 90 C 72 80, 172 70, 256 120 Z" fill="#ffffff" fill-opacity="0.95" />
      <path d="M256 120 L 256 420" stroke="#0284c7" stroke-width="20" stroke-linecap="round" />
      <path d="M100 170 C 150 160, 200 165, 230 185 M 100 230 C 150 220, 200 225, 230 245 M 100 290 C 150 280, 200 285, 230 305" stroke="#38bdf8" stroke-width="14" stroke-linecap="round" />
      <path d="M412 170 C 362 160, 312 165, 282 185 M 412 230 C 362 220, 312 225, 282 245 M 412 290 C 362 280, 312 285, 282 305" stroke="#38bdf8" stroke-width="14" stroke-linecap="round" />
    `
  },
  {
    file: 'menu-2-apps.svg',
    id: 'm2',
    bgGrad: ['#34d399', '#059669', '#064e3b'],
    sphereGrad: ['#6ee7b7', '#10b981', '#047857'],
    accentColor: '#34d399',
    label: 'App Directory',
    // 3D Grid / Cubes apps icon
    iconSvg: `
      <rect x="70" y="70" width="150" height="150" rx="36" fill="#ffffff" />
      <rect x="290" y="70" width="150" height="150" rx="36" fill="#a7f3d0" />
      <rect x="70" y="290" width="150" height="150" rx="36" fill="#a7f3d0" />
      <rect x="290" y="290" width="150" height="150" rx="36" fill="#ffffff" />
      <circle cx="145" cy="145" r="30" fill="#059669" />
      <path d="M340 145 L 390 145 M 365 120 L 365 170" stroke="#059669" stroke-width="16" stroke-linecap="round" />
      <path d="M120 365 L 170 365" stroke="#059669" stroke-width="16" stroke-linecap="round" />
      <circle cx="365" cy="365" r="30" fill="#059669" />
    `
  },
  {
    file: 'menu-3-portal.svg',
    id: 'm3',
    bgGrad: ['#818cf8', '#4f46e5', '#1e1b4b'],
    sphereGrad: ['#a5b4fc', '#6366f1', '#4338ca'],
    accentColor: '#818cf8',
    label: 'Portal Menu',
    // 3D Layered Isometric Stacks
    iconSvg: `
      <path d="M256 60 L 450 160 L 256 260 L 62 160 Z" fill="#ffffff" />
      <path d="M62 210 L 256 310 L 450 210 L 450 250 L 256 350 L 62 250 Z" fill="#c7d2fe" />
      <path d="M62 300 L 256 400 L 450 300 L 450 340 L 256 440 L 62 340 Z" fill="#818cf8" />
      <ellipse cx="256" cy="160" rx="40" ry="20" fill="#4f46e5" />
    `
  },
  {
    file: 'menu-4-options.svg',
    id: 'm4',
    bgGrad: ['#fb923c', '#ea580c', '#7c2d12'],
    sphereGrad: ['#fdba74', '#f97316', '#c2410c'],
    accentColor: '#fdba74',
    label: 'Menu Menu',
    // 3D Floating Interactive Checklist / Cards
    iconSvg: `
      <rect x="80" y="80" width="350" height="90" rx="28" fill="#ffffff" />
      <rect x="80" y="210" width="350" height="90" rx="28" fill="#ffedd5" />
      <rect x="80" y="340" width="350" height="90" rx="28" fill="#ffffff" />
      <circle cx="130" cy="125" r="24" fill="#f97316" />
      <path d="M120 125 L 126 132 L 140 118" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
      <line x1="180" y1="125" x2="380" y2="125" stroke="#ea580c" stroke-width="16" stroke-linecap="round" />
      <circle cx="130" cy="255" r="24" fill="#fdba74" />
      <line x1="180" y1="255" x2="350" y2="255" stroke="#f97316" stroke-width="16" stroke-linecap="round" />
      <circle cx="130" cy="385" r="24" fill="#f97316" />
      <path d="M120 385 L 126 392 L 140 378" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
      <line x1="180" y1="385" x2="370" y2="385" stroke="#ea580c" stroke-width="16" stroke-linecap="round" />
    `
  },
  {
    file: 'menu-5-news.svg',
    id: 'm5',
    bgGrad: ['#2dd4bf', '#0d9488', '#134e4a'],
    sphereGrad: ['#5eead4', '#14b8a6', '#0f766e'],
    accentColor: '#5eead4',
    label: 'News Bulletin',
    // 3D Newspaper & Megaphone
    iconSvg: `
      <rect x="90" y="70" width="330" height="370" rx="28" fill="#ffffff" />
      <rect x="130" y="110" width="130" height="110" rx="16" fill="#0d9488" />
      <line x1="290" y1="125" x2="380" y2="125" stroke="#14b8a6" stroke-width="16" stroke-linecap="round" />
      <line x1="290" y1="165" x2="380" y2="165" stroke="#14b8a6" stroke-width="16" stroke-linecap="round" />
      <line x1="290" y1="205" x2="350" y2="205" stroke="#14b8a6" stroke-width="16" stroke-linecap="round" />
      <line x1="130" y1="260" x2="380" y2="260" stroke="#99f6e4" stroke-width="14" stroke-linecap="round" />
      <line x1="130" y1="300" x2="380" y2="300" stroke="#99f6e4" stroke-width="14" stroke-linecap="round" />
      <line x1="130" y1="340" x2="380" y2="340" stroke="#99f6e4" stroke-width="14" stroke-linecap="round" />
      <line x1="130" y1="380" x2="260" y2="380" stroke="#99f6e4" stroke-width="14" stroke-linecap="round" />
    `
  },
  {
    file: 'menu-6-sharing.svg',
    id: 'm6',
    bgGrad: ['#06b6d4', '#0891b2', '#164e63'],
    sphereGrad: ['#67e8f9', '#06b6d4', '#0e7490'],
    accentColor: '#67e8f9',
    label: 'Sharing Moment',
    // 3D Community / Chat / Ideas bubble
    iconSvg: `
      <path d="M120 100 C 60 100, 40 150, 40 210 C 40 260, 80 300, 130 310 L 110 380 L 190 320 C 330 340, 360 250, 360 210 C 360 140, 290 100, 120 100 Z" fill="#ffffff" />
      <circle cx="140" cy="210" r="20" fill="#0891b2" />
      <circle cx="210" cy="210" r="20" fill="#0891b2" />
      <circle cx="280" cy="210" r="20" fill="#0891b2" />
      <path d="M340 240 C 430 250, 470 320, 470 360 C 470 395, 435 425, 400 430 L 415 470 L 360 440 C 270 445, 240 400, 240 370" fill="#67e8f9" opacity="0.85" />
    `
  },
  {
    file: 'menu-7-banner.svg',
    id: 'm7',
    bgGrad: ['#3b82f6', '#1d4ed8', '#172554'],
    sphereGrad: ['#93c5fd', '#3b82f6', '#1e40af'],
    accentColor: '#93c5fd',
    label: 'Hero Highlights',
    // 3D Media Screen / Broadcast
    iconSvg: `
      <rect x="60" y="100" width="392" height="260" rx="30" fill="#ffffff" />
      <rect x="80" y="120" width="352" height="220" rx="20" fill="#1e40af" />
      <polygon points="230,170 330,230 230,290" fill="#38bdf8" />
      <path d="M180 400 L 332 400 M 256 360 L 256 420" stroke="#ffffff" stroke-width="24" stroke-linecap="round" />
    `
  },
  {
    file: 'menu-8-rates.svg',
    id: 'm8',
    bgGrad: ['#0284c7', '#0369a1', '#082f49'],
    sphereGrad: ['#38bdf8', '#0284c7', '#075985'],
    accentColor: '#38bdf8',
    label: 'Suku Bunga & Kurs',
    // 3D Percentage & Coin / Growth chart
    iconSvg: `
      <path d="M80 400 L 180 290 L 280 340 L 420 160" stroke="#ffffff" stroke-width="36" stroke-linecap="round" stroke-linejoin="round" />
      <polygon points="420,110 440,180 370,160" fill="#ffffff" />
      <circle cx="160" cy="160" r="45" fill="#38bdf8" />
      <circle cx="160" cy="160" r="25" fill="#ffffff" />
      <line x1="120" y1="360" x2="360" y2="120" stroke="#a5f3fc" stroke-width="20" stroke-linecap="round" opacity="0.6" />
      <circle cx="340" cy="340" r="45" fill="#38bdf8" />
      <circle cx="340" cy="340" r="25" fill="#ffffff" />
    `
  },
  {
    file: 'menu-9-giro.svg',
    id: 'm9',
    bgGrad: ['#475569', '#1e293b', '#020617'],
    sphereGrad: ['#94a3b8', '#475569', '#0f172a'],
    accentColor: '#38bdf8',
    label: 'Giro & Korporasi',
    // 3D Bank / Corporate Building
    iconSvg: `
      <polygon points="256,60 70,160 442,160" fill="#ffffff" />
      <rect x="90" y="160" width="332" height="30" fill="#cbd5e1" />
      <rect x="110" y="190" width="40" height="190" rx="8" fill="#ffffff" />
      <rect x="190" y="190" width="40" height="190" rx="8" fill="#ffffff" />
      <rect x="282" y="190" width="40" height="190" rx="8" fill="#ffffff" />
      <rect x="362" y="190" width="40" height="190" rx="8" fill="#ffffff" />
      <rect x="70" y="380" width="372" height="50" rx="10" fill="#cbd5e1" />
    `
  },
  {
    file: 'menu-10-promo.svg',
    id: 'm10',
    bgGrad: ['#eab308', '#ca8a04', '#713f12'],
    sphereGrad: ['#fde047', '#eab308', '#a16207'],
    accentColor: '#fef08a',
    label: 'Promo & Program',
    // 3D Gift Box / Star Reward
    iconSvg: `
      <path d="M256 60 L 305 175 L 430 185 L 335 270 L 365 395 L 256 330 L 147 395 L 177 270 L 82 185 L 207 175 Z" fill="#ffffff" />
      <circle cx="256" cy="230" r="50" fill="#eab308" />
      <path d="M256 195 L 256 265 M 220 230 L 292 230" stroke="#ffffff" stroke-width="14" stroke-linecap="round" />
    `
  },
  {
    file: 'menu-11-alert.svg',
    id: 'm11',
    bgGrad: ['#f43f5e', '#e11d48', '#881337'],
    sphereGrad: ['#fda4af', '#f43f5e', '#be123c'],
    accentColor: '#fecdd3',
    label: 'Alert & Notice',
    // 3D Glowing Bell / Broadcast Alert
    iconSvg: `
      <path d="M256 80 C 180 80, 160 140, 160 240 L 120 340 L 392 340 L 352 240 C 352 140, 332 80, 256 80 Z" fill="#ffffff" />
      <path d="M206 360 C 206 395, 230 420, 256 420 C 282 420, 306 395, 306 360" fill="#ffffff" />
      <ellipse cx="256" cy="70" rx="30" ry="20" fill="#ffffff" />
      <line x1="256" y1="150" x2="256" y2="250" stroke="#e11d48" stroke-width="20" stroke-linecap="round" />
      <circle cx="256" cy="290" r="14" fill="#e11d48" />
    `
  },
  {
    file: 'menu-12-links.svg',
    id: 'm12',
    bgGrad: ['#14b8a6', '#0d9488', '#042f2e'],
    sphereGrad: ['#5eead4', '#14b8a6', '#0f766e'],
    accentColor: '#99f6e4',
    label: 'Quick Links',
    // 3D Chain Link / Interconnected node
    iconSvg: `
      <circle cx="160" cy="256" r="80" fill="none" stroke="#ffffff" stroke-width="40" />
      <circle cx="352" cy="256" r="80" fill="none" stroke="#a7f3d0" stroke-width="40" />
      <rect x="210" y="236" width="92" height="40" rx="10" fill="#ffffff" />
    `
  },
  {
    file: 'menu-13-calculator.svg',
    id: 'm13',
    bgGrad: ['#06b6d4', '#0891b2', '#155e75'],
    sphereGrad: ['#67e8f9', '#06b6d4', '#0e7490'],
    accentColor: '#a5f3fc',
    label: 'Simulasi Hitung',
    // 3D Calculator / Finance Tool
    iconSvg: `
      <rect x="100" y="70" width="312" height="372" rx="36" fill="#ffffff" />
      <rect x="135" y="110" width="242" height="80" rx="16" fill="#0891b2" />
      <text x="350" y="165" font-family="monospace" font-weight="900" font-size="44" fill="#ffffff" text-anchor="end">0.00</text>
      <!-- Buttons -->
      <circle cx="165" cy="240" r="22" fill="#06b6d4" />
      <circle cx="256" cy="240" r="22" fill="#06b6d4" />
      <circle cx="347" cy="240" r="22" fill="#0891b2" />
      <circle cx="165" cy="310" r="22" fill="#06b6d4" />
      <circle cx="256" cy="310" r="22" fill="#06b6d4" />
      <circle cx="347" cy="310" r="22" fill="#0891b2" />
      <circle cx="165" cy="380" r="22" fill="#06b6d4" />
      <circle cx="256" cy="380" r="22" fill="#06b6d4" />
      <circle cx="347" cy="380" r="22" fill="#0e7490" />
    `
  },
  {
    file: 'menu-14-guide.svg',
    id: 'm14',
    bgGrad: ['#f97316', '#c2410c', '#431407'],
    sphereGrad: ['#fdba74', '#ea580c', '#9a3412'],
    accentColor: '#fed7aa',
    label: 'User Manual & SOP',
    // 3D Shield / Verified Guide Badge
    iconSvg: `
      <path d="M256 60 C 370 60, 420 120, 420 220 C 420 340, 330 420, 256 460 C 182 420, 92 340, 92 220 C 92 120, 142 60, 256 60 Z" fill="#ffffff" />
      <path d="M200 240 L 240 280 L 320 190" fill="none" stroke="#ea580c" stroke-width="34" stroke-linecap="round" stroke-linejoin="round" />
    `
  },
  {
    file: 'menu-15-external.svg',
    id: 'm15',
    bgGrad: ['#0f766e', '#115e59', '#042f2e'],
    sphereGrad: ['#2dd4bf', '#0d9488', '#134e4a'],
    accentColor: '#5eead4',
    label: 'External Tools',
    // 3D Rocket / Global Outbound Connection
    iconSvg: `
      <rect x="90" y="140" width="280" height="280" rx="36" fill="#ffffff" />
      <path d="M260 90 L 420 90 L 420 250" fill="none" stroke="#2dd4bf" stroke-width="44" stroke-linecap="round" stroke-linejoin="round" />
      <line x1="400" y1="110" x2="230" y2="280" stroke="#2dd4bf" stroke-width="44" stroke-linecap="round" />
      <rect x="140" y="190" width="180" height="180" rx="20" fill="#0f766e" />
    `
  }
];

menus.forEach(m => {
  const content = create3DSvg(m);
  const outPath = path.join(targetDir, m.file);
  fs.writeFileSync(outPath, content, 'utf8');
  console.log(`Generated: ${m.file}`);
});
console.log('All 15 3D menu SVG illustrations generated successfully!');
