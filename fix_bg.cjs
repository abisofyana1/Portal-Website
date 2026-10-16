const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Fix getMenuNewsImageStyle for matchedImgs
const bgRegex = /backgroundImage: \`linear-gradient\(rgba\(0,0,0,0\.38\), rgba\(0,0,0,0\.55\)\), url\('\$\{imgToShow\.img\}'\)\`,\s*backgroundSize: 'cover',\s*backgroundPosition: 'center center',\s*backgroundRepeat: 'no-repeat'/g;
const newBg = `backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgToShow.img}'), url('\${imgToShow.img}')\`,
                backgroundSize: 'cover, contain, cover',
                backgroundPosition: 'center center, center center, center center',
                backgroundRepeat: 'no-repeat, no-repeat, no-repeat'`;
content = content.replace(bgRegex, newBg);

// Fix generic fallback in getMenuNewsImageStyle (imgs array)
const bgGenericRegex = /backgroundImage: \`linear-gradient\(rgba\(0,0,0,0\.38\), rgba\(0,0,0,0\.55\)\), url\('\$\{imgs\[carouselIdx % imgs\.length\]\.img\}'\)\`,\s*backgroundSize: 'cover',\s*backgroundPosition: 'center center',\s*backgroundRepeat: 'no-repeat'/g;
const newBgGeneric = `backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgs[carouselIdx % imgs.length].img}'), url('\${imgs[carouselIdx % imgs.length].img}')\`,
            backgroundSize: 'cover, contain, cover',
            backgroundPosition: 'center center, center center, center center',
            backgroundRepeat: 'no-repeat, no-repeat, no-repeat'`;
content = content.replace(bgGenericRegex, newBgGeneric);

// 2. Fix Slide Otomatis Sub Menu images
// We want to add a backdrop layer image right before the main image.
// Main image has class="absolute inset-0 w-full h-full object-cover z-0 opacity-80"
// Let's replace the whole <img> tag with two <img> tags
for (let i = 1; i <= 15; i++) {
    const searchImg = `<img v-if="getMenuItems('Menu ${i}')[menuSlideIndices['Menu ${i}'] || 0].image_name?.includes('.')" :src="\`/images/\${getMenuItems('Menu ${i}')[menuSlideIndices['Menu ${i}'] || 0].image_name}\`" class="absolute inset-0 w-full h-full object-cover z-0 opacity-80" style="border-radius: inherit;">`;
    const replaceImg = `<img v-if="getMenuItems('Menu ${i}')[menuSlideIndices['Menu ${i}'] || 0].image_name?.includes('.')" :src="\`/images/\${getMenuItems('Menu ${i}')[menuSlideIndices['Menu ${i}'] || 0].image_name}\`" class="absolute inset-0 w-full h-full object-cover z-0 opacity-40 blur-md scale-110" style="border-radius: inherit;">
                                 <img v-if="getMenuItems('Menu ${i}')[menuSlideIndices['Menu ${i}'] || 0].image_name?.includes('.')" :src="\`/images/\${getMenuItems('Menu ${i}')[menuSlideIndices['Menu ${i}'] || 0].image_name}\`" class="absolute inset-0 w-full h-full object-contain z-0 opacity-100" style="border-radius: inherit;">`;
    content = content.replace(searchImg, replaceImg);
}

fs.writeFileSync(file, content);
console.log('Fixed background fill in Home.vue');
