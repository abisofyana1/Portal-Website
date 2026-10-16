const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// 1. Update onDragEnd
const dragEndRegex = /if \(diff > 0\) \{[\s\S]*?\} else \{[\s\S]*?\/\/ It was a click\.[\s\S]*?wasDragging\.value = false;\s*\/\/ Force view here as well to be safe\s*viewAgentNews\(carouselNews\.value\[currentNewsIndex\.value\]\);\s*\}/;
const newDragEnd = `if (diff > 0) {
             // Next
             currentNewsIndex.value = (currentNewsIndex.value + 1) % carouselNews.value.length;
        } else {
             // Prev
             currentNewsIndex.value = (currentNewsIndex.value - 1 + carouselNews.value.length) % carouselNews.value.length;
        }
    } else {
         // It was a click. Let handleBannerClick process it.
         wasDragging.value = false;
    }`;
content = content.replace(dragEndRegex, newDragEnd);

// 2. Update handleBannerClick
const bannerClickRegex = /const handleBannerClick = \(\) => \{[\s\S]*?viewAgentNews\(carouselNews\.value\[currentNewsIndex\.value\]\);\s*\}/;
const newBannerClick = `const handleBannerClick = (item = null) => {
    // Backup handler
    if (!wasDragging.value) {
        // Debounce slightly to avoid double open if onDragEnd also triggered it
        if (!isViewNewsModalOpen.value) {
             if (item) {
                 viewAgentNews(item);
             } else if (carouselNews.value && carouselNews.value[currentNewsIndex.value]) {
                viewAgentNews(carouselNews.value[currentNewsIndex.value]);
             }`;
content = content.replace(bannerClickRegex, newBannerClick);


// 3. Update Carousel HTML
const oldHtml = `<!-- Layer 1: Full-cover main image (fills banner like background) -->
                             <div class="absolute inset-0 pointer-events-none">
                                 <img v-if="getNewsImage(carouselNews[currentNewsIndex])"
                                      :src="getNewsImage(carouselNews[currentNewsIndex])"
                                      class="w-full h-full object-cover"
                                      :style="'object-position: center top;'"
                                 >
                                 <div v-else class="w-full h-full bg-cover bg-center" style="background-image: url('/images/banner_bg.jpg')"></div>
                             </div>
                             
                             <!-- Layer 2: Subtle dark overlay for text readability -->
                             <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent pointer-events-none"></div>
                             
                             <!-- Layer 4: Content Overlay (bottom strip) -->
                             <div class="absolute bottom-10 left-0 right-0 z-10 px-4 pointer-events-none">
                                 <div class="bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 max-w-lg mx-auto">
                                     <h2 class="text-lg font-black text-white mb-0.5 tracking-tight line-clamp-1 drop-shadow">{{ carouselNews[currentNewsIndex].title }}</h2>
                                     <div class="flex items-center gap-2">
                                         <span class="bg-teal-500 text-white text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">{{ carouselNews[currentNewsIndex].category || 'News' }}</span>
                                         <span class="text-white/70 text-[9px] opacity-0 group-hover:opacity-100 transition font-semibold">- Klik untuk detail</span>
                                     </div>
                                 </div>
                             </div>
                             
                             <!-- Navigation Arrows -->
                             <button 
                                v-if="carouselNews.length > 1"
                                @mousedown.stop
                                @mouseup.stop
                                @click.stop="currentNewsIndex = (currentNewsIndex - 1 + carouselNews.length) % carouselNews.length" 
                                class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/60 text-white w-8 h-8 rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 z-30 pointer-events-auto backdrop-blur-sm shadow-md border border-white/20">
                                 <i class="fas fa-chevron-left text-xs"></i>
                             </button>
                             <button 
                                v-if="carouselNews.length > 1"
                                @mousedown.stop
                                @mouseup.stop
                                @click.stop="currentNewsIndex = (currentNewsIndex + 1) % carouselNews.length" 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/60 text-white w-8 h-8 rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 z-30 pointer-events-auto backdrop-blur-sm shadow-md border border-white/20">
                                 <i class="fas fa-chevron-right text-xs"></i>
                             </button>`;

const newHtml = `<!-- Layout: 2 columns side by side -->
                             <div class="w-full h-full flex overflow-hidden">
                                 <!-- Item 1 -->
                                 <div class="flex-1 relative border-r border-gray-100/10 group/item cursor-pointer" @click.stop="handleBannerClick(carouselNews[currentNewsIndex])" @mousedown.stop="handleMiddleClickBanner($event, carouselNews[currentNewsIndex])">
                                     <img v-if="getNewsImage(carouselNews[currentNewsIndex])"
                                          :src="getNewsImage(carouselNews[currentNewsIndex])"
                                          class="w-full h-full object-contain bg-white"
                                     >
                                     <div v-else class="w-full h-full bg-cover bg-center" style="background-image: url('/images/banner_bg.jpg')"></div>
                                     <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                                     <div class="absolute bottom-10 left-0 right-0 z-10 px-4 pointer-events-none">
                                         <div class="bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 max-w-sm mx-auto flex items-center justify-between">
                                             <div class="flex-1">
                                                 <h2 class="text-xs font-black text-white mb-0.5 tracking-tight line-clamp-2 drop-shadow">{{ carouselNews[currentNewsIndex].title }}</h2>
                                                 <span class="text-white/70 text-[9px] opacity-0 group-hover/item:opacity-100 transition font-semibold"> Klik untuk detail</span>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 
                                 <!-- Item 2 -->
                                 <div v-if="carouselNews.length > 1" class="flex-1 relative group/item cursor-pointer" @click.stop="handleBannerClick(carouselNews[(currentNewsIndex + 1) % carouselNews.length])" @mousedown.stop="handleMiddleClickBanner($event, carouselNews[(currentNewsIndex + 1) % carouselNews.length])">
                                     <img v-if="getNewsImage(carouselNews[(currentNewsIndex + 1) % carouselNews.length])"
                                          :src="getNewsImage(carouselNews[(currentNewsIndex + 1) % carouselNews.length])"
                                          class="w-full h-full object-contain bg-white"
                                     >
                                     <div v-else class="w-full h-full bg-cover bg-center" style="background-image: url('/images/banner_bg.jpg')"></div>
                                     <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                                     <div class="absolute bottom-10 left-0 right-0 z-10 px-4 pointer-events-none">
                                         <div class="bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 max-w-sm mx-auto flex items-center justify-between">
                                             <div class="flex-1">
                                                 <h2 class="text-xs font-black text-white mb-0.5 tracking-tight line-clamp-2 drop-shadow">{{ carouselNews[(currentNewsIndex + 1) % carouselNews.length].title }}</h2>
                                                 <span class="text-white/70 text-[9px] opacity-0 group-hover/item:opacity-100 transition font-semibold"> Klik untuk detail</span>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                             
                             <!-- Navigation Arrows -->
                             <button 
                                v-if="carouselNews.length > 1"
                                @mousedown.stop
                                @mouseup.stop
                                @click.stop="currentNewsIndex = (currentNewsIndex - 1 + carouselNews.length) % carouselNews.length" 
                                class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/60 text-white w-8 h-8 rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 z-30 pointer-events-auto backdrop-blur-sm shadow-md border border-white/20">
                                 <i class="fas fa-chevron-left text-xs"></i>
                             </button>
                             <button 
                                v-if="carouselNews.length > 1"
                                @mousedown.stop
                                @mouseup.stop
                                @click.stop="currentNewsIndex = (currentNewsIndex + 2) % carouselNews.length" 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/60 text-white w-8 h-8 rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 z-30 pointer-events-auto backdrop-blur-sm shadow-md border border-white/20">
                                 <i class="fas fa-chevron-right text-xs"></i>
                             </button>`;

content = content.replace(oldHtml, newHtml);

// 4. Update the container to have @click.stop="handleBannerClick()" without args as fallback
content = content.replace(/@click\.stop="handleBannerClick"/g, '@click.stop="handleBannerClick()"');
// and replace the parent wrapper class from flex items-center justify-center to w-full h-full
content = content.replace(/class="absolute inset-0 flex items-center justify-center"/g, 'class="absolute inset-0 w-full h-full"');

fs.writeFileSync(file, content);
console.log('Fixed carousel layout.');
