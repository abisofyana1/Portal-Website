const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Change backgroundSize from 'cover' to '100% 100%'
const bgRegex = /backgroundImage: \`linear-gradient\(rgba\(0,0,0,0\.38\), rgba\(0,0,0,0\.55\)\), url\('\$\{imgToShow\.img\}'\)\`,\s*backgroundSize: 'cover',\s*backgroundPosition: 'center center',\s*backgroundRepeat: 'no-repeat'/g;

const newBg = `backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgToShow.img}')\`,
                backgroundSize: '100% 100%',
                backgroundPosition: 'center center',
                backgroundRepeat: 'no-repeat'`;
content = content.replace(bgRegex, newBg);

const bgGenericRegex = /backgroundImage: \`linear-gradient\(rgba\(0,0,0,0\.38\), rgba\(0,0,0,0\.55\)\), url\('\$\{imgs\[carouselIdx % imgs\.length\]\.img\}'\)\`,\s*backgroundSize: 'cover',\s*backgroundPosition: 'center center',\s*backgroundRepeat: 'no-repeat'/g;

const newBgGeneric = `backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgs[carouselIdx % imgs.length].img}')\`,
                backgroundSize: '100% 100%',
                backgroundPosition: 'center center',
                backgroundRepeat: 'no-repeat'`;
content = content.replace(bgGenericRegex, newBgGeneric);

// 2. Change object-cover to object-fill
content = content.replace(/class="absolute inset-0 w-full h-full object-cover z-0 opacity-100"/g, 'class="absolute inset-0 w-full h-full object-fill z-0 opacity-100"');

fs.writeFileSync(file, content);
console.log('Reverted to 100% 100% stretch');
