const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Add brightness-50 to Slide Otomatis images
content = content.replace(/class="absolute inset-0 w-full h-full object-fill z-0 opacity-100"/g, 'class="absolute inset-0 w-full h-full object-fill z-0 opacity-100 brightness-50"');

// 2. Enhance text shadow for Slide Otomatis text
content = content.replace(/<h3 class="relative z-10 text-sm font-bold uppercase tracking-widest text-white drop-shadow-sm">/g, '<h3 class="relative z-10 text-sm font-bold uppercase tracking-widest text-white [text-shadow:0_2px_4px_rgba(0,0,0,1),_0_0_10px_rgba(0,0,0,0.8)]">');

fs.writeFileSync(file, content);
console.log('Fixed text visibility');
