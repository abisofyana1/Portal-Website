const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(/4000\); \/\/ 4 seconds per slide/g, '5000); // 5 seconds per slide');
content = content.replace(/4000\);/g, '5000);');

fs.writeFileSync(file, content);
console.log('Fixed interval time to 5 seconds in Home.vue');
