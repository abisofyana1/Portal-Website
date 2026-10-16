const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Replace for List mode (any remaining)
content = content.replace(/class="min-h-0 flex flex-col h-full overflow-y-auto /g, 'class="absolute inset-0 flex flex-col overflow-y-auto ');

// Replace for Horizontal mode (any remaining)
content = content.replace(/class="min-h-0 flex flex-wrap gap-1 h-full overflow-y-auto /g, 'class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto ');

// Also convert fetchMenuNewsImages to a function declaration to fix TDZ
content = content.replace(/const\s+fetchMenuNewsImages\s*=\s*async\s*\(\)\s*=>\s*\{/g, 'async function fetchMenuNewsImages() {');

// Same for startNewsCarousel if it's an arrow function
content = content.replace(/const\s+startNewsCarousel\s*=\s*\(\)\s*=>\s*\{/g, 'function startNewsCarousel() {');
content = content.replace(/const\s+startAutoSlide\s*=\s*\(\)\s*=>\s*\{/g, 'function startAutoSlide() {');

fs.writeFileSync(file, content);
console.log('Fixed absolute positioning and TDZ functions.');
