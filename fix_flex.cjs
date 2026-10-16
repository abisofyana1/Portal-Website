const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// The scroll container currently has:
// "flex flex-col h-full overflow-y-auto custom-scrollbar-thin space-y-0.5 w-full pt-1 menu-scroll-container scroll-smooth pb-4 pt-4"
// OR
// "flex flex-wrap gap-1 h-full overflow-y-auto content-start w-full menu-scroll-container scroll-smooth pb-4 pt-4"
// We will add "min-h-0 " to all instances of "menu-scroll-container"

content = content.replace(/class="([^"]*menu-scroll-container[^"]*)"/g, (match, p1) => {
    if (!p1.includes('min-h-0')) {
        return `class="min-h-0 ${p1}"`;
    }
    return match;
});

// Also make sure the wrapper flex-col has min-h-0 just in case
content = content.replace(/(class="relative h-full w-full group overflow-hidden flex flex-col justify-center")/g, (match) => {
    return 'class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0"';
});

fs.writeFileSync(file, content);
console.log('Added min-h-0 to scroll containers.');
