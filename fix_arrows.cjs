const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Remove bg-black/40 and w-full from up/down arrows
content = content.replace(/class="absolute top-0 left-1\/2 -translate-x-1\/2 p-1 text-white\/50 hover:text-white bg-black\/40 w-full flex justify-center opacity-0 group-hover:opacity-100 z-20 transition"/g,
    'class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"');

content = content.replace(/class="absolute bottom-0 left-1\/2 -translate-x-1\/2 p-1 text-white\/50 hover:text-white bg-black\/40 w-full flex justify-center opacity-0 group-hover:opacity-100 z-20 transition"/g,
    'class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"');

// 2. Adjust padding in scroll containers so they don't push content down too much
content = content.replace(/pt-6 pb-6/g, 'pt-2 pb-2');

fs.writeFileSync(file, content);
console.log('Fixed arrow styling and scroll container padding.');
