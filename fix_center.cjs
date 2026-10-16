const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Fix 1: For Horizontal scroll containers - change from content-start to place-content-center
// so icons are centered in the card when there are few items
content = content.replace(
    /class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto content-start w-full menu-scroll-container scroll-smooth pb-4 pt-4"/g,
    'class="absolute inset-0 flex flex-wrap gap-2 overflow-y-auto place-content-center justify-center menu-scroll-container scroll-smooth p-2"'
);

// Fix 2: For List scroll containers - add padding so items aren't hidden under up/down buttons
content = content.replace(
    /class="absolute inset-0 flex flex-col overflow-y-auto custom-scrollbar-thin space-y-0\.5 w-full pt-1 menu-scroll-container scroll-smooth pb-4 pt-4"/g,
    'class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-6 pb-6 px-1 space-y-0.5"'
);
content = content.replace(
    /class="absolute inset-0 flex flex-col overflow-y-auto custom-scrollbar-thin space-y-1 w-full menu-scroll-container scroll-smooth pb-4 pt-4"/g,
    'class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-6 pb-6 px-1 space-y-1"'
);
content = content.replace(
    /class="absolute inset-0 flex flex-col overflow-y-auto custom-scrollbar-thin space-y-1 py-1 menu-scroll-container scroll-smooth pb-4 pt-4"/g,
    'class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-6 pb-6 px-1 space-y-1"'
);

fs.writeFileSync(file, content);
console.log('Fixed centering and padding for menu scroll containers.');
