const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Change backgroundSize: 'cover' to 'contain' in getMenuNewsImageStyle
content = content.replace(/backgroundSize: 'cover'/g, "backgroundSize: 'contain'");
content = content.replace(/backgroundPosition: 'center top'/g, "backgroundPosition: 'center center'");

// 2. Change object-cover to object-contain for all Slide Otomatis img tags (for Sub Menu images)
// We look for class="absolute inset-0 w-full h-full object-cover z-0 opacity-80"
content = content.replace(/class="absolute inset-0 w-full h-full object-cover z-0 opacity-80"/g, 'class="absolute inset-0 w-full h-full object-contain z-0 opacity-80"');

// 3. Move the size tag outside of the v-if for collapsed menus
const oldTag = `<span v-if="collapsedMenuGroups.includes(group.name)" class="flex items-center gap-2">
                                                        <span class="text-gray-600 italic">{{ group.items.length }} items hidden (click to expand)</span>
                                                        <span class="text-[10px] font-bold bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200" title="Ukuran banner di halaman Agent">
                                                            <i class="fas fa-expand-alt mr-1"></i>{{ MENU_BANNER_SIZES[group.name] || '128px' }}
                                                        </span>
                                                    </span>`;
                                                    
const newTag = `<span class="text-[10px] font-bold bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200 flex-shrink-0" title="Ukuran rekomendasi gambar">
                                                        <i class="fas fa-expand-alt mr-1"></i>{{ MENU_BANNER_SIZES[group.name] || '128px' }}
                                                    </span>
                                                    <span v-if="collapsedMenuGroups.includes(group.name)" class="flex items-center gap-2">
                                                        <span class="text-gray-600 italic">{{ group.items.length }} items hidden (click to expand)</span>
                                                    </span>`;
                                                    
content = content.replace(oldTag, newTag);

fs.writeFileSync(file, content);
console.log('Fixed UI in Home.vue');
