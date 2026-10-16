const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Replace object-cover and max-height for the header image in Lihat Data modal
content = content.replace(
    /class="w-full object-cover shadow-md"[\s\n]*style="max-height: 420px; object-position: center top;"/g,
    'class="w-full object-contain shadow-md bg-gray-50"\n                         style="max-height: 600px;"'
);

fs.writeFileSync(file, content);
console.log('Fixed image cropping in Lihat Data modal.');
