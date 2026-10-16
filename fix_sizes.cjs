const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Reverse the sizes to Width x Height
const replacements = {
    "'128 × 426px'": "'426 × 128 px'",
    "'128 × 205px'": "'205 × 128 px'",
    "'224 × 640px'": "'640 × 224 px'",
    "'160 × 426px'": "'426 × 160 px'",
    "'160 × 205px'": "'205 × 160 px'"
};

for (const [oldSize, newSize] of Object.entries(replacements)) {
    content = content.replace(new RegExp(oldSize, 'g'), newSize);
}

fs.writeFileSync(file, content);
console.log('Fixed sizes in Home.vue');
