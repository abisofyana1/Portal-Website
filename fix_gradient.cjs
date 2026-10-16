const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Remove linear-gradient from getMenuNewsImageStyle
const bgRegex = /backgroundImage: \\\linear-gradient\\(rgba\\(0,0,0,0\\.38\\), rgba\\(0,0,0,0\\.55\\)\\), url\\('\\\\\\$\\{imgToShow\\.img\\}'\\)\\\/g;
const newBg = ackgroundImage: \\\url('\\\\\\')\\\`;
content = content.replace(bgRegex, newBg);

const bgGenericRegex = /backgroundImage: \\\linear-gradient\\(rgba\\(0,0,0,0\\.38\\), rgba\\(0,0,0,0\\.55\\)\\), url\\('\\\\\\$\\{imgs\\[carouselIdx % imgs\\.length\\]\\.img\\}'\\)\\\/g;
const newBgGeneric = ackgroundImage: \\\url('\\\\\\')\\\`;
content = content.replace(bgGenericRegex, newBgGeneric);

fs.writeFileSync(file, content);
console.log('Removed gradient overlay');
