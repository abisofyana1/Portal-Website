const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Fix fetchMenuNewsImages to match sub-menu titles
const fetchMenuNewsRegex = /const catNews = allNews\.filter\(item => \{[\s\S]*?\}\);/g;
const newFetchMenuNews = `const catNews = allNews.filter(item => {
                const cat = (item.category || '').trim().toLowerCase();
                if (cat === \`menu \${n}\` || cat === \`menu\${n}\`) return true;
                // Also check if the category matches any sub-menu title of this Menu n
                const subMenus = portalSettings.value ? portalSettings.value.filter(s => s.category === menuKey || s.category === \`Menu \${unit} \${n}\`) : [];
                return subMenus.some(sub => (sub.title || '').trim().toLowerCase() === cat);
            });`;
content = content.replace(fetchMenuNewsRegex, newFetchMenuNews);

// 2. Fix the Slide Otomatis template
// Replace the img class in slide mode
content = content.replace(/<img v-if="getMenuItems\('Menu (\d+)'\)\[menuSlideIndices\['Menu \1'\] \|\| 0\]\.image_name\?\.includes\('\.'\)" :src="`\/images\/\$\{getMenuItems\('Menu \1'\)\[menuSlideIndices\['Menu \1'\] \|\| 0\]\.image_name\}`" class="h-12 w-12 object-contain drop-shadow-md">/g,
`<img v-if="getMenuItems('Menu $1')[menuSlideIndices['Menu $1'] || 0].image_name?.includes('.')" :src="\`/images/\${getMenuItems('Menu $1')[menuSlideIndices['Menu $1'] || 0].image_name}\`" class="absolute inset-0 w-full h-full object-cover z-0 opacity-80" style="border-radius: inherit;">`);

content = content.replace(/<h3 class="text-sm font-bold uppercase tracking-widest text-white drop-shadow-sm">/g, '<h3 class="relative z-10 text-sm font-bold uppercase tracking-widest text-white drop-shadow-sm">');
content = content.replace(/<div class="mt-2 text-\[10px\] opacity-70 animate-pulse">Running Text \/ Slide Mode<\/div>/g, '<div class="relative z-10 mt-2 text-[10px] opacity-70 animate-pulse bg-black/30 px-2 py-1 rounded-full">Running Text / Slide Mode</div>');
content = content.replace(/<i v-else :class="\['fas', 'fa-' \+ \(getMenuItems\('Menu (\d+)'\)\[menuSlideIndices\['Menu \1'\] \|\| 0\]\.image_name \|\| 'star'\), 'text-4xl'\]"><\/i>/g, '<i v-else :class="[\'fas\', \'fa-\' + (getMenuItems(\'Menu $1\')[menuSlideIndices[\'Menu $1\'] || 0].image_name || \'star\'), \'text-4xl relative z-10\']"></i>');

fs.writeFileSync(file, content);
console.log('Fixed Home.vue!');
