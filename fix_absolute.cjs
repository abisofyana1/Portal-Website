const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Replace the scroll container class for LIST mode
content = content.replace(/class="min-h-0 flex flex-col h-full overflow-y-auto (custom-scrollbar-thin space-y-[^"]+w-full pt-1 menu-scroll-container scroll-smooth pb-4 pt-4)"/g, 
    'class="absolute inset-0 flex flex-col overflow-y-auto $1"');

// Replace the scroll container class for HORIZONTAL mode
content = content.replace(/class="min-h-0 flex flex-wrap gap-1 h-full overflow-y-auto (content-start w-full menu-scroll-container scroll-smooth pb-4 pt-4)"/g, 
    'class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto $1"');

fs.writeFileSync(file, content);
console.log('Applied absolute positioning to scroll containers.');
