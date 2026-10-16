const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

const findLine = "menuNewsCarouselIndices.value[key] = ((menuNewsCarouselIndices.value[key] || 0) + 1) % imgs.length;";
const replaceLine = "menuNewsCarouselIndices.value[key] = (menuNewsCarouselIndices.value[key] || 0) + 1;";
content = content.replace(findLine, replaceLine);

fs.writeFileSync(file, content);
console.log('Fixed modulo in Home.vue');
