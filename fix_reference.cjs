const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Replace the invalid images.push line
content = content.replace(/images\.push\(\{ img, title: item\.title, id: item\.id, category: cat \}\);/g, 
"images.push({ img, title: item.title, id: item.id, category: (item.category || '').trim().toLowerCase() });");

fs.writeFileSync(file, content);
console.log('Fixed ReferenceError in Home.vue');
