const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// The regex needs to handle the exact whitespace and structure, or we can just replace specifically for each menu or write a small parser.
// Let's use simple matchers for the horizontal blocks where img is missing:
// Example: <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
// We prepend the img tag.

let modified = false;

// 1. Horizontal Mode Replace (where img is completely missing)
const horizontalRegex = /(<i v-if="!item\.image_name\?\.includes\('\.'\)" :class="\['fas', 'fa-' \+ \(item\.image_name \|\| '([^']+)'\), '([^']+)'\]"><\/i>)/g;
content = content.replace(horizontalRegex, (match, p1, p2, p3) => {
    modified = true;
    return `<img v-if="item.image_name?.includes('.')" :src="\`/images/\${item.image_name}\`" class="w-6 h-6 object-contain mb-0.5">\n                                      ${p1}`;
});

// 2. Slide Mode Replace
// Example: <i :class="['fas', 'fa-' + (getMenuItems('Menu 2')[menuSlideIndices['Menu 2'] || 0].image_name || 'link'), 'text-2xl mb-1']"></i>
const slideRegex = /<i :class="\['fas', 'fa-' \+ \(getMenuItems\('Menu (\d+)'\)\[menuSlideIndices\['Menu \d+'\] \|\| 0\]\.image_name \|\| '([^']+)'\), '([^']+)'\]"><\/i>/g;
content = content.replace(slideRegex, (match, p1, p2, p3) => {
    modified = true;
    return `<img v-if="getMenuItems('Menu ${p1}')[menuSlideIndices['Menu ${p1}'] || 0].image_name?.includes('.')" :src="\`/images/\${getMenuItems('Menu ${p1}')[menuSlideIndices['Menu ${p1}'] || 0].image_name}\`" class="h-8 w-8 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu ${p1}')[menuSlideIndices['Menu ${p1}'] || 0].image_name || '${p2}'), '${p3}']"></i>`;
});

// Also there might be horizontal modes without v-if in front of the <i> tag, like Menu 11:
// <i :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-xl mb-0.5', '[filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))']" ></i>
// But we saw earlier Menu 11 had:
// <img v-if="item.image_name && item.image_name.includes('.')" :src="`/images/${item.image_name}`" class="w-5 h-5 object-contain flex-shrink-0">
// <i v-else ...

fs.writeFileSync(file, content);
console.log('Replaced icons successfully! Modified:', modified);
