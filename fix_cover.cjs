const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Change backgroundSize: 'contain' back to 'cover' in getMenuNewsImageStyle
content = content.replace(/backgroundSize: 'contain'/g, "backgroundSize: 'cover'");
content = content.replace(/backgroundPosition: 'center center'/g, "backgroundPosition: 'center center'"); // We can keep center center for better cropping

// 2. Change object-contain back to object-cover for all Slide Otomatis img tags
content = content.replace(/class="absolute inset-0 w-full h-full object-contain z-0 opacity-80"/g, 'class="absolute inset-0 w-full h-full object-cover z-0 opacity-80"');

fs.writeFileSync(file, content);
console.log('Reverted contain back to cover in Home.vue');
