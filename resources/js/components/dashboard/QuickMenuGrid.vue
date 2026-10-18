<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 sm:gap-6 h-full">
                
                <!-- Menu 1-15 Dynamic Mapping -->
                <!-- ROW 1 -->
                
                <!-- Menu 1: Col Span 2 (full width on 2-col mobile, 2 cols on desktop) -->
                <div class="col-span-2 md:col-span-2">
                    <div class="min-h-0 min-w-0 bg-orange-500 rounded-xl overflow-hidden shadow-lg border-t-4 border-orange-300 relative h-32 group transition text-white" :style="[getMenuNewsImageStyle('Menu 1'), getMenuBackgroundStyle('Menu 1')]">
                        <!-- Ambient 3D Graphic Watermark -->
                        <img :src="getMenuIllustration('Menu 1')" class="absolute -right-2 -bottom-2 w-28 h-28 opacity-30 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-40 transition-all duration-300 drop-shadow-md z-0" alt="">

                        <div class="p-3.5 h-full flex flex-col justify-start overflow-y-auto custom-scrollbar relative z-10">
                             <!-- Case 1: Multiple Items -->
                             <div v-if="getMenuItems('Menu 1').length >= 1" class="h-full">
                                 <!-- Header -->
                                 <h3 v-if="(menuLayoutConfig['Menu 1'] || 'list') === 'list'" 
                                      @click="getMenuItems('Menu 1').length > 0 ? handlePortalItemClick(getMenuItems('Menu 1')[0]) : handlePortalItemClick({ title: 'Menu 1' })"
                                      class="font-bold text-xs uppercase opacity-90 mb-2 border-b border-white/20 pb-1 px-2 pt-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.7)] cursor-pointer hover:text-white/80 flex items-center justify-between">
                                      <span>Menu 1</span>
                                      <i class="fas fa-chevron-right text-[10px] opacity-60"></i>
                                 </h3>
                                 
                                 <!-- MODE: LIST (Default) -->
                                 <div v-if="(menuLayoutConfig['Menu 1'] || 'list') === 'list'" class="space-y-1.5 px-1 pb-2">
                                      <div v-for="item in getMenuItems('Menu 1')" :key="item.id" 
                                          @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                          class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/25 rounded cursor-pointer transition bg-black/30 backdrop-blur-[1px] border border-white/10 shadow-sm">
                                         <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-7 h-7 object-contain flex-shrink-0 drop-shadow">
                                         <i v-else :class="['fas', 'fa-' + (item.image_name || 'chevron-right'), 'text-xs', '[filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))']"></i>
                                         <span class="text-xs font-bold uppercase truncate [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ item.title }}</span>
                                     </div>
                                 </div>

                                 <!-- MODE: HORIZONTAL -->
                                 <div v-else-if="menuLayoutConfig['Menu 1'] === 'horizontal'" class="flex flex-wrap gap-2 p-2 h-full overflow-y-auto content-start">
                                     <div v-for="item in getMenuItems('Menu 1')" :key="item.id" 
                                          @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                          class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-2 cursor-pointer w-[80px] h-[80px] text-center border border-white/20">
                                         <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-8 h-8 object-contain mb-1">
                                         <i v-else :class="['fas', 'fa-' + (item.image_name || 'chevron-right'), 'text-xl mb-1', '[filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))']"></i>
                                         <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                     </div>
                                 </div>

                                 <!-- MODE: SLIDE (Carousel) -->
                                 <div v-else-if="menuLayoutConfig['Menu 1'] === 'slide'" class="relative h-full flex items-center justify-center p-4">
                                     <!-- Arrows -->
                                    <button @click.stop="moveSlide('Menu 1', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                    <button @click.stop="moveSlide('Menu 1', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                     <!-- Simple CSS Animation for Slide -->
                                     <div class="text-center w-full h-full"> 
                                        <div class="h-full w-full flex flex-col items-center justify-center cursor-pointer" 
                                             @click="getCurrentSlideItem('Menu 1') ? handlePortalItemClick(getCurrentSlideItem('Menu 1')) : null" @mousedown="getCurrentSlideItem('Menu 1') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 1')) : null">
                                            <div class="animate-fade-in-out" :key="menuSlideIndices['Menu 1']">
                                                 <div class="flex justify-center mb-2">
                                                     <img v-if="getMenuItems('Menu 1')[menuSlideIndices['Menu 1'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 1')[menuSlideIndices['Menu 1'] || 0].image_name)" class="absolute inset-0 w-full h-full object-fill z-0 opacity-100 brightness-50" style="border-radius: inherit;">
                                                     <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 1')[menuSlideIndices['Menu 1'] || 0].image_name || 'star'), 'text-4xl relative z-10']"></i>
                                                 </div>
                                                 <h3 class="relative z-10 text-sm font-bold uppercase tracking-widest text-white [text-shadow:0_2px_4px_rgba(0,0,0,1),_0_0_10px_rgba(0,0,0,0.8)]">{{ getMenuItems('Menu 1')[menuSlideIndices['Menu 1'] || 0].title }}</h3>
                                                 <div class="relative z-10 mt-2 text-[10px] opacity-70 animate-pulse bg-black/30 px-2 py-1 rounded-full">Running Text / Slide Mode</div>
                                            </div>
                                        </div>

                                     </div>
                                 </div>
                             </div>
                             <!-- Case 2: Single Item or fallback -->
                             <div v-else @click="handlePortalItemClick(getMenuItems('Menu 1')[0] || { title: 'Menu 1' })" @mousedown="getMenuItems('Menu 1')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 1')[0]) : null"
                                  class="h-full flex items-center justify-between px-2 cursor-pointer">
                                 <div>
                                     <h3 class="font-bold text-base leading-tight mb-1 uppercase drop-shadow">{{ getMenuItems('Menu 1')[0]?.title || 'TENTANG KAMI' }}</h3>
                                     <span class="text-[10px] font-bold tracking-wider uppercase text-white/80 flex items-center">
                                         <i class="fas fa-info-circle mr-1 opacity-70"></i> Menu 1
                                     </span>
                                 </div>
                                 <img :src="getMenuIllustration('Menu 1')" class="w-16 h-16 object-contain drop-shadow-lg" alt="">
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Menu 2: Col Span 1 -->
                <div class="col-span-1">
                    <div class="min-h-0 min-w-0 bg-teal-500 rounded-2xl shadow-lg flex flex-col p-2 transition border-b-[6px] border-teal-700 relative group h-32 text-center text-white overflow-hidden" :style="[getMenuNewsImageStyle('Menu 2'), getMenuBackgroundStyle('Menu 2')]">
                         <!-- Ambient 3D Graphic Watermark -->
                         <img :src="getMenuIllustration('Menu 2')" class="absolute -right-3 -bottom-3 w-20 h-20 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">

                         <div v-if="getMenuItems('Menu 2').length >= 1" class="h-full w-full relative z-10">
                             <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 2'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                 <div v-for="item in getMenuItems('Menu 2')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/25 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)] border border-white/5">
                                     <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                      <i v-else :class="['fas', 'fa-' + (item.image_name || 'external-link-alt'), 'text-xs flex-shrink-0 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))]']"></i>
                                     <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 2'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-1 p-1 h-full overflow-y-auto justify-center content-center text-center menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                  <div v-for="item in getMenuItems('Menu 2')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1 cursor-pointer w-[56px] h-[56px] m-0.5 border border-white/20">
                                     <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                      <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
                                      <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 w-full [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- SLIDE -->
                             <div v-else-if="menuLayoutConfig['Menu 2'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative" 
                                  @click="getCurrentSlideItem('Menu 2') ? handlePortalItemClick(getCurrentSlideItem('Menu 2')) : null" @mousedown="getCurrentSlideItem('Menu 2') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 2')) : null">
                                  
                                  <!-- Arrows -->
                                  <button @click.stop="moveSlide('Menu 2', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                  <button @click.stop="moveSlide('Menu 2', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                 <div class="flex flex-col items-center animate-fade-in-out" :key="menuSlideIndices['Menu 2']">
                                      <img v-if="getMenuItems('Menu 2')[menuSlideIndices['Menu 2'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 2')[menuSlideIndices['Menu 2'] || 0].image_name)" class="h-10 w-10 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 2')[menuSlideIndices['Menu 2'] || 0].image_name || 'link'), 'text-2xl mb-1']"></i>
                                      <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 2')[menuSlideIndices['Menu 2'] || 0].title }}</span>
                                 </div>
                             </div>
                         </div>
                         <div v-else @click="getMenuItems('Menu 2')[0] ? handlePortalItemClick(getMenuItems('Menu 2')[0]) : null" @mousedown="getMenuItems('Menu 2')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 2')[0]) : null"
                              class="flex flex-col items-center justify-center h-full cursor-pointer hover:bg-teal-400 transition rounded-xl relative z-10 p-1">
                             <div class="w-14 h-14 flex items-center justify-center mb-1">
                                <img :src="getMenuIllustration('Menu 2')" class="w-full h-full object-contain drop-shadow-md">
                             </div>
                             <span class="font-bold text-[10px] tracking-wider uppercase text-teal-50 drop-shadow">{{ getMenuItems('Menu 2')[0]?.title || 'App List' }}</span>
                         </div>
                    </div>
                </div>

                <!-- Menu 3: Col Span 1 -->
                <div class="col-span-1">
                    <div class="min-h-0 min-w-0 bg-teal-500 rounded-2xl shadow-lg flex flex-col p-2 transition border-b-[6px] border-teal-700 relative group h-32 text-center text-white overflow-hidden" :style="[getMenuNewsImageStyle('Menu 3'), getMenuBackgroundStyle('Menu 3')]">
                         <!-- Ambient 3D Graphic Watermark -->
                         <img :src="getMenuIllustration('Menu 3')" class="absolute -right-3 -bottom-3 w-20 h-20 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">

                         <div v-if="getMenuItems('Menu 3').length >= 1" class="h-full w-full relative z-10">
                             <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 3'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                 <div v-for="item in getMenuItems('Menu 3')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/25 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)] border border-white/5">
                                     <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                      <i v-else :class="['fas', 'fa-' + (item.image_name || 'external-link-alt'), 'text-xs flex-shrink-0 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))]']"></i>
                                     <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 3'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-1 p-1 h-full overflow-y-auto justify-center content-center text-center menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                  <div v-for="item in getMenuItems('Menu 3')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1 cursor-pointer w-[56px] h-[56px] m-0.5 border border-white/20">
                                     <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                      <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
                                      <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 w-full [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- SLIDE -->
                             <div v-else-if="menuLayoutConfig['Menu 3'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                  @click="getCurrentSlideItem('Menu 3') ? handlePortalItemClick(getCurrentSlideItem('Menu 3')) : null" @mousedown="getCurrentSlideItem('Menu 3') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 3')) : null">
                                  
                                  <!-- Arrows -->
                                  <button @click.stop="moveSlide('Menu 3', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                  <button @click.stop="moveSlide('Menu 3', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                 <div class="flex flex-col items-center animate-fade-in-out" :key="menuSlideIndices['Menu 3']">
                                      <img v-if="getMenuItems('Menu 3')[menuSlideIndices['Menu 3'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 3')[menuSlideIndices['Menu 3'] || 0].image_name)" class="h-10 w-10 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 3')[menuSlideIndices['Menu 3'] || 0].image_name || 'layer-group'), 'text-2xl mb-1']"></i>
                                      <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 3')[menuSlideIndices['Menu 3'] || 0].title }}</span>
                                 </div>
                             </div>
                         </div>
                         <div v-else @click="getMenuItems('Menu 3')[0] ? handlePortalItemClick(getMenuItems('Menu 3')[0]) : null" @mousedown="getMenuItems('Menu 3')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 3')[0]) : null"
                              class="flex flex-col items-center justify-center h-full cursor-pointer hover:bg-teal-400 transition rounded-xl relative z-10 p-1">
                             <div class="w-14 h-14 flex items-center justify-center mb-1">
                                <img :src="getMenuIllustration('Menu 3')" class="w-full h-full object-contain drop-shadow-md">
                             </div>
                             <span class="font-bold text-[10px] tracking-wider uppercase text-teal-50 drop-shadow">{{ getMenuItems('Menu 3')[0]?.title || 'Portal Menu' }}</span>
                         </div>
                    </div>
                </div>

                <!-- Menu 4: Col Span 1 -->
                <div class="col-span-1">
                    <div class="min-h-0 min-w-0 bg-orange-500 rounded-2xl shadow-lg flex flex-col p-2 transition border-b-[6px] border-orange-700 relative group h-32 text-center text-white overflow-hidden" :style="[getMenuNewsImageStyle('Menu 4'), getMenuBackgroundStyle('Menu 4')]">
                         <!-- Ambient 3D Graphic Watermark -->
                         <img :src="getMenuIllustration('Menu 4')" class="absolute -right-3 -bottom-3 w-20 h-20 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">

                         <div v-if="getMenuItems('Menu 4').length >= 1" class="h-full w-full relative z-10">
                             <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 4'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                 <div v-for="item in getMenuItems('Menu 4')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/25 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)] border border-white/5">
                                     <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                      <i v-else :class="['fas', 'fa-' + (item.image_name || 'external-link-alt'), 'text-xs flex-shrink-0 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))]']"></i>
                                     <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 4'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-1 p-1 h-full overflow-y-auto justify-center content-center text-center menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                  <div v-for="item in getMenuItems('Menu 4')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1 cursor-pointer w-[56px] h-[56px] m-0.5 border border-white/20">
                                            <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                      <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'list'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
                                      <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 w-full [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- SLIDE -->
                             <div v-else-if="menuLayoutConfig['Menu 4'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                  @click="getCurrentSlideItem('Menu 4') ? handlePortalItemClick(getCurrentSlideItem('Menu 4')) : null" @mousedown="getCurrentSlideItem('Menu 4') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 4')) : null">
                                  
                                  <!-- Arrows -->
                                  <button @click.stop="moveSlide('Menu 4', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                  <button @click.stop="moveSlide('Menu 4', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                 <div class="flex flex-col items-center animate-fade-in-out" :key="menuSlideIndices['Menu 4']">
                                      <img v-if="getMenuItems('Menu 4')[menuSlideIndices['Menu 4'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 4')[menuSlideIndices['Menu 4'] || 0].image_name)" class="h-10 w-10 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 4')[menuSlideIndices['Menu 4'] || 0].image_name || 'list'), 'text-2xl mb-1']"></i>
                                      <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 4')[menuSlideIndices['Menu 4'] || 0].title }}</span>
                                 </div>
                             </div>
                         </div>
                         <div v-else @click="getMenuItems('Menu 4')[0] ? handlePortalItemClick(getMenuItems('Menu 4')[0]) : null" @mousedown="getMenuItems('Menu 4')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 4')[0]) : null"
                              class="flex flex-col items-center justify-center h-full cursor-pointer hover:bg-orange-400 transition rounded-xl relative z-10 p-1">
                             <div class="w-14 h-14 flex items-center justify-center mb-1">
                                <img :src="getMenuIllustration('Menu 4')" class="w-full h-full object-contain drop-shadow-md">
                             </div>
                             <span class="font-bold text-[10px] tracking-wider uppercase text-orange-50 drop-shadow">{{ getMenuItems('Menu 4')[0]?.title || 'Workflows' }}</span>
                         </div>
                    </div>
                </div>

                <!-- Menu 5: Col Span 1 -->
                <div class="col-span-1">
                    <div class="min-h-0 min-w-0 bg-teal-500 rounded-2xl shadow-lg flex flex-col p-2 transition border-b-[6px] border-teal-700 relative group h-32 text-center text-white overflow-hidden" :style="[getMenuNewsImageStyle('Menu 5'), getMenuBackgroundStyle('Menu 5')]">
                         <!-- Ambient 3D Graphic Watermark -->
                         <img :src="getMenuIllustration('Menu 5')" class="absolute -right-3 -bottom-3 w-20 h-20 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">

                         <div v-if="getMenuItems('Menu 5').length >= 1" class="h-full w-full relative z-10">
                             <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 5'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                 <div v-for="item in getMenuItems('Menu 5')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/25 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)] border border-white/5">
                                     <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                      <i v-else :class="['fas', 'fa-' + (item.image_name || 'external-link-alt'), 'text-xs flex-shrink-0 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))]']"></i>
                                     <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 5'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-1 p-1 h-full overflow-y-auto justify-center content-center text-center menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                  <div v-for="item in getMenuItems('Menu 5')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1 cursor-pointer w-[56px] h-[56px] m-0.5 border border-white/20">
                                            <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                      <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'newspaper'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
                                      <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 w-full [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>
                             <!-- SLIDE -->
                             <div v-else-if="menuLayoutConfig['Menu 5'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                  @click="getCurrentSlideItem('Menu 5') ? handlePortalItemClick(getCurrentSlideItem('Menu 5')) : null" @mousedown="getCurrentSlideItem('Menu 5') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 5')) : null">
                                  
                                  <!-- Arrows -->
                                  <button @click.stop="moveSlide('Menu 5', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                  <button @click.stop="moveSlide('Menu 5', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                 <div class="flex flex-col items-center animate-fade-in-out" :key="menuSlideIndices['Menu 5']">
                                      <img v-if="getMenuItems('Menu 5')[menuSlideIndices['Menu 5'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 5')[menuSlideIndices['Menu 5'] || 0].image_name)" class="h-10 w-10 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 5')[menuSlideIndices['Menu 5'] || 0].image_name || 'newspaper'), 'text-2xl mb-1']"></i>
                                      <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 5')[menuSlideIndices['Menu 5'] || 0].title }}</span>
                                 </div>
                             </div>
                         </div>
                         <div v-else @click="getMenuItems('Menu 5')[0] ? handlePortalItemClick(getMenuItems('Menu 5')[0]) : null" @mousedown="getMenuItems('Menu 5')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 5')[0]) : null"
                              class="flex flex-col items-center justify-center h-full cursor-pointer hover:bg-teal-400 transition rounded-xl relative z-10 p-1">
                             <div class="w-14 h-14 flex items-center justify-center mb-1">
                                <img :src="getMenuIllustration('Menu 5')" class="w-full h-full object-contain drop-shadow-md">
                             </div>
                             <span class="font-bold text-[10px] tracking-wider uppercase text-teal-50 drop-shadow">{{ getMenuItems('Menu 5')[0]?.title || 'News' }}</span>
                         </div>
                    </div>
                </div>


                <!-- ROW 2 & 3 -->
                <div class="col-span-2 md:col-span-2 flex flex-col gap-4 sm:gap-6">
                     <!-- Menu 6: Sharing Moment -->

                     <div class="min-h-0 min-w-0 bg-teal-600 rounded-xl overflow-hidden shadow-lg border-t-4 border-teal-300 relative group h-32 transition text-white" :style="[getMenuNewsImageStyle('Menu 6'), getMenuBackgroundStyle('Menu 6')]">
                         <!-- Ambient 3D Graphic Watermark -->
                         <img :src="getMenuIllustration('Menu 6')" class="absolute -right-2 -bottom-2 w-28 h-28 opacity-30 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-40 transition-all duration-300 drop-shadow-md z-0" alt="">

                         <div class="p-3.5 h-full flex flex-col justify-start overflow-y-auto custom-scrollbar relative z-10">
                              <!-- Case 1: Multiple Items -->
                              <div v-if="getMenuItems('Menu 6').length >= 1" class="h-full">
                                  <!-- Header -->
                                  <h3 v-if="(menuLayoutConfig['Menu 6'] || 'list') === 'list'" class="font-bold text-xs uppercase opacity-90 mb-2 border-b border-white/20 pb-1 px-2 pt-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.7)] flex items-center justify-between">
                                      <span>Menu 6</span>
                                      <i class="fas fa-chevron-right text-[10px] opacity-60"></i>
                                  </h3>
                                  
                                  <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 6'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 space-y-1.5 px-1 pb-2 menu-scroll-container scroll-smooth" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 6')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/25 rounded cursor-pointer transition bg-black/30 backdrop-blur-[1px] border border-white/10 shadow-sm">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-7 h-7 object-contain flex-shrink-0 drop-shadow">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'newspaper'), 'text-xs']"></i>
                                          <span class="text-xs font-bold uppercase truncate [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>              

                                  <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 6'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-2 p-2 h-full overflow-y-auto content-start menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                     <div v-for="item in getMenuItems('Menu 6')" :key="item.id" 
                                          @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                          class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-2 cursor-pointer w-[80px] h-[80px] text-center border border-white/20">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-12 h-12 object-contain mb-1 flex-shrink-0 [filter:drop-shadow(0_2px_4px_rgba(0,0,0,0.5))]" @error="handleImageError(item)">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'newspaper'), 'text-3xl mb-1 [filter:drop-shadow(0_1px_3px_rgba(0,0,0,0.9))]']"></i>
                                         <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                     </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>

                                  <!-- SLIDE -->
                                  <div v-else-if="menuLayoutConfig['Menu 6'] === 'slide'" class="relative h-full flex items-center justify-center p-4">
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 6', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 6', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                      <div class="h-full w-full flex flex-col items-center justify-center cursor-pointer" 
                                           @click="getCurrentSlideItem('Menu 6') ? handlePortalItemClick(getCurrentSlideItem('Menu 6')) : null" @mousedown="getCurrentSlideItem('Menu 6') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 6')) : null">
                                          <div class="animate-fade-in-out text-center" :key="menuSlideIndices['Menu 6']">
                                               <div class="flex justify-center mb-2">
                                                   <img v-if="getMenuItems('Menu 6')[menuSlideIndices['Menu 6'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 6')[menuSlideIndices['Menu 6'] || 0].image_name)" class="absolute inset-0 w-full h-full object-fill z-0 opacity-100 brightness-50" style="border-radius: inherit;">
                                                   <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 6')[menuSlideIndices['Menu 6'] || 0].image_name || 'star'), 'text-4xl relative z-10']"></i>
                                               </div>
                                               <h3 class="relative z-10 text-sm font-bold uppercase tracking-widest text-white [text-shadow:0_2px_4px_rgba(0,0,0,1),_0_0_10px_rgba(0,0,0,0.8)]">{{ getMenuItems('Menu 6')[menuSlideIndices['Menu 6'] || 0].title }}</h3>
                                               <div class="relative z-10 mt-2 text-[10px] opacity-70 animate-pulse bg-black/30 px-2 py-1 rounded-full">Running Text / Slide Mode</div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                              <!-- Case 2: Single Item -->
                              <div v-else @click="getMenuItems('Menu 6')[0] ? handlePortalItemClick(getMenuItems('Menu 6')[0]) : null" @mousedown="getMenuItems('Menu 6')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 6')[0]) : null"
                                   class="h-full flex items-center justify-between px-2 cursor-pointer">
                                  <div>
                                      <h3 class="font-bold text-base leading-tight mb-1 uppercase drop-shadow">{{ getMenuItems('Menu 6')[0]?.title || 'SHARING MOMENT' }}</h3>
                                      <span class="text-[10px] font-bold tracking-wider uppercase text-white/80 flex items-center">
                                          <i class="fas fa-newspaper mr-1 opacity-70"></i> Menu 6
                                      </span>
                                  </div>
                                  <img :src="getMenuIllustration('Menu 6')" class="w-16 h-16 object-contain drop-shadow-lg" alt="">
                              </div>
                         </div>
                     </div>

                     <!-- Row for Menu 8 & 9 -->
                     <div class="grid grid-cols-2 gap-4 h-32">
                         <!-- Menu 8 -->
                          <div class="min-h-0 min-w-0 bg-cyan-800/80 rounded-xl flex flex-col p-2 text-center shadow-lg border border-white/10 group h-full text-white overflow-hidden relative" :style="[getMenuNewsImageStyle('Menu 8'), getMenuBackgroundStyle('Menu 8')]">
                               <!-- Ambient 3D Graphic Watermark -->
                               <img :src="getMenuIllustration('Menu 8')" class="absolute -right-3 -bottom-3 w-16 h-16 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">

                               <div v-if="getMenuItems('Menu 8').length >= 1" class="h-full w-full relative z-10">
                                   <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 8'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto custom-scrollbar-thin space-y-1 menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                       <div v-for="item in getMenuItems('Menu 8')" :key="item.id" 
                                            @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                            class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/25 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)] border border-white/5">
                                           <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                           <i v-else :class="['fas', 'fa-' + (item.image_name || 'percent'), 'text-xs flex-shrink-0 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))]']"></i>
                                           <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                       </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                   </div>
                                   <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 8'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-1 p-1 h-full overflow-y-auto justify-center content-center text-center menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                        <div v-for="item in getMenuItems('Menu 8')" :key="item.id" 
                                             @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                             class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1 cursor-pointer w-[56px] h-[56px] m-0.5 border border-white/20">
                                            <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                      <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'percent'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
                                             <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 w-full [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                        </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                   </div>
                                   <!-- SLIDE -->
                                   <div v-else-if="menuLayoutConfig['Menu 8'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                        @click="getCurrentSlideItem('Menu 8') ? handlePortalItemClick(getCurrentSlideItem('Menu 8')) : null" @mousedown="getCurrentSlideItem('Menu 8') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 8')) : null">
                                        
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 8', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 8', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                       <div class="flex flex-col items-center animate-fade-in-out" :key="menuSlideIndices['Menu 8']">
                                            <img v-if="getMenuItems('Menu 8')[menuSlideIndices['Menu 8'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 8')[menuSlideIndices['Menu 8'] || 0].image_name)" class="h-10 w-10 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 8')[menuSlideIndices['Menu 8'] || 0].image_name || 'percent'), 'text-2xl mb-1']"></i>
                                            <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 8')[menuSlideIndices['Menu 8'] || 0].title }}</span>
                                       </div>
                                   </div>
                               </div>
                               <div v-else @click="getMenuItems('Menu 8')[0] ? handlePortalItemClick(getMenuItems('Menu 8')[0]) : null" @mousedown="getMenuItems('Menu 8')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 8')[0]) : null"
                                    class="flex flex-col items-center justify-center h-full cursor-pointer hover:bg-cyan-700 transition rounded-xl relative z-10 p-1">
                                   <div class="w-12 h-12 flex items-center justify-center mb-1">
                                      <img :src="getMenuIllustration('Menu 8')" class="w-full h-full object-contain drop-shadow-md">
                                   </div>
                                   <span class="text-[9px] font-bold leading-tight uppercase tracking-wide drop-shadow">{{ getMenuItems('Menu 8')[0]?.title || 'Suku Bunga' }}</span>
                               </div>
                          </div>
                         <!-- Menu 9 -->
                          <div class="min-h-0 min-w-0 bg-gray-900 rounded-xl flex flex-col p-2 text-center shadow-lg border border-white/20 group h-full text-white overflow-hidden relative" :style="[getMenuNewsImageStyle('Menu 9'), getMenuBackgroundStyle('Menu 9')]">
                               <!-- Ambient 3D Graphic Watermark -->
                               <img :src="getMenuIllustration('Menu 9')" class="absolute -right-3 -bottom-3 w-16 h-16 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">

                               <div v-if="getMenuItems('Menu 9').length >= 1" class="h-full w-full relative z-10">
                                   <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 9'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto custom-scrollbar-thin space-y-1 menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                       <div v-for="item in getMenuItems('Menu 9')" :key="item.id" 
                                            @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                            class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/25 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)] border border-white/5">
                                           <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                           <i v-else :class="['fas', 'fa-' + (item.image_name || 'building'), 'text-xs flex-shrink-0 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))]']"></i>
                                           <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                       </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                   </div>
                                   <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 9'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 flex flex-wrap gap-1 p-1 h-full overflow-y-auto justify-center content-center text-center menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                         <div v-for="item in getMenuItems('Menu 9')" :key="item.id" 
                                              @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                              class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1 cursor-pointer w-[56px] h-[56px] m-0.5 border border-white/20">
                                             <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                       <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'building'), 'text-lg mb-0.5 [filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.9))]']"></i>
                                              <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 w-full [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                         </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                   </div>
                                   <!-- SLIDE -->
                                   <div v-else-if="menuLayoutConfig['Menu 9'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                        @click="getCurrentSlideItem('Menu 9') ? handlePortalItemClick(getCurrentSlideItem('Menu 9')) : null" @mousedown="getCurrentSlideItem('Menu 9') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 9')) : null">
                                        
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 9', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 9', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                       <div class="flex flex-col items-center animate-fade-in-out" :key="menuSlideIndices['Menu 9']">
                                            <img v-if="getMenuItems('Menu 9')[menuSlideIndices['Menu 9'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 9')[menuSlideIndices['Menu 9'] || 0].image_name)" class="h-10 w-10 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 9')[menuSlideIndices['Menu 9'] || 0].image_name || 'building'), 'text-2xl mb-1']"></i>
                                            <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 9')[menuSlideIndices['Menu 9'] || 0].title }}</span>
                                       </div>
                                   </div>
                               </div>
                               <div v-else @click="getMenuItems('Menu 9')[0] ? handlePortalItemClick(getMenuItems('Menu 9')[0]) : null" @mousedown="getMenuItems('Menu 9')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 9')[0]) : null"
                                    class="flex flex-col items-center justify-center h-full cursor-pointer hover:bg-black transition rounded-xl relative z-10 p-1">
                                   <div class="w-12 h-12 flex items-center justify-center mb-1">
                                      <img :src="getMenuIllustration('Menu 9')" class="w-full h-full object-contain drop-shadow-md">
                                   </div>
                                   <span class="text-[9px] font-bold leading-tight uppercase tracking-wide text-gray-100 drop-shadow">{{ getMenuItems('Menu 9')[0]?.title || 'Giro Perusahaan' }}</span>
                               </div>
                          </div>
                     </div>
                </div>

                <!-- Right Column (Hero Banner) - Col Span 4 -->
                <!-- Spans the height of Menu 6 + gap + Menu 8/9 approx -->
                <!-- Menu 7: Hero Banner / News Carousel Slot -->
                <slot name="hero-banner"></slot>


                <!-- ROW 4 -->

                <!-- Menu 10: Promo - Col Span 2 (full width on 2-col mobile, 2 cols on desktop) -->
                <div class="col-span-2 md:col-span-2">
                     <div class="min-h-0 min-w-0 bg-yellow-600 rounded-xl overflow-hidden shadow-lg relative h-48 group transition text-white" :style="[getMenuNewsImageStyle('Menu 10'), getMenuBackgroundStyle('Menu 10')]">
                        <!-- Ambient 3D Graphic Watermark -->
                        <img :src="getMenuIllustration('Menu 10')" class="absolute -right-3 -bottom-3 w-36 h-36 opacity-30 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-45 transition-all duration-300 drop-shadow-md z-0" alt="">

                        <!-- Case 1: Multiple Items -->
                        <div v-if="getMenuItems('Menu 10').length >= 1" class="h-full w-full relative z-10">
                            
                             <!-- LIST (Original) -->
                             <div v-if="(menuLayoutConfig['Menu 10'] || 'list') === 'list'" class="absolute inset-0 p-4 overflow-y-auto custom-scrollbar bg-black/40 backdrop-blur-[2px] z-10 flex flex-col space-y-1.5">
                                 <h3 class="font-bold text-xs uppercase opacity-90 mb-2 border-b border-white/20 pb-1 flex items-center justify-between">
                                     <span>Menu 10</span>
                                     <i class="fas fa-chevron-right text-[10px] opacity-60"></i>
                                 </h3>
                                 <div v-for="item in getMenuItems('Menu 10')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer transition bg-black/30 border border-white/10 shadow-sm">
                                     <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-7 h-7 object-contain flex-shrink-0 drop-shadow">
                                     <i v-else :class="['fas', 'fa-' + (item.image_name || 'star'), 'text-sm']"></i>
                                     <span class="text-xs font-bold uppercase truncate [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ item.title }}</span>
                                 </div>
                             </div>

                             <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 10'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 absolute inset-0 p-4 overflow-y-auto bg-black/40 backdrop-blur-[2px] z-10 flex flex-wrap content-start gap-2 menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                <div v-for="item in getMenuItems('Menu 10')" :key="item.id" 
                                      @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                      class="flex flex-col items-center justify-center bg-white/20 hover:bg-white/30 rounded p-2 cursor-pointer w-[80px] h-[80px] text-center border border-white/10 transition">
                                     <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-8 h-8 object-contain mb-1 shadow-sm">
                                     <i v-else :class="['fas', 'fa-' + (item.image_name || 'star'), 'text-xl mb-1']"></i>
                                     <span class="text-[9px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                 </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                             </div>

                             <!-- SLIDE -->
                             <div v-else-if="menuLayoutConfig['Menu 10'] === 'slide'" 
                                  class="h-full w-full relative cursor-pointer flex items-end"
                                  @click="getCurrentSlideItem('Menu 10') ? handlePortalItemClick(getCurrentSlideItem('Menu 10')) : null" @mousedown="getCurrentSlideItem('Menu 10') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 10')) : null">
                                 
                                     <!-- Arrows -->
                                    <button @click.stop="moveSlide('Menu 10', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                    <button @click.stop="moveSlide('Menu 10', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                 <!-- Dynamic Background for current slide -->
                                 <div class="absolute inset-0 bg-cover bg-center transition-all duration-1000 transform" 
                                      :style="`background-image: url('${getMenuItems('Menu 10')[menuSlideIndices['Menu 10'] || 0].image_name?.includes('.') ? getImagePath('images/')+getMenuItems('Menu 10')[menuSlideIndices['Menu 10'] || 0].image_name : ''}')`">
                                     <!-- Solid fallback hanya jika tidak ada portal item image DAN tidak ada news image -->
                                     <div v-if="!getMenuItems('Menu 10')[menuSlideIndices['Menu 10'] || 0].image_name?.includes('.') && !menuNewsImages['Menu 10']?.length" class="absolute inset-0 bg-yellow-600"></div>
                                 </div>
                                 
                                 <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 8px 8px;"></div>
                                 
                                 <div class="w-full bg-black/60 backdrop-blur-sm px-4 py-2 flex justify-between items-center z-10 transition animate-fade-in-out" :key="menuSlideIndices['Menu 10']">
                                      <div class="flex items-center space-x-2">
                                           <i v-if="!getMenuItems('Menu 10')[menuSlideIndices['Menu 10'] || 0].image_name?.includes('.')" :class="['fas', 'fa-' + (getMenuItems('Menu 10')[menuSlideIndices['Menu 10'] || 0].image_name || 'star'), 'text-sm']"></i>
                                           <span class="font-bold text-sm uppercase truncate max-w-[150px]">{{ getMenuItems('Menu 10')[menuSlideIndices['Menu 10'] || 0].title }}</span>
                                      </div>
                                      <span class="text-[10px] text-white/80 font-medium tracking-widest bg-yellow-500/20 px-1 rounded animate-pulse">Slide Mode</span>
                                 </div>
                             </div>

                        </div>
                        <div v-else @click="getMenuItems('Menu 10')[0] ? handlePortalItemClick(getMenuItems('Menu 10')[0]) : null" @mousedown="getMenuItems('Menu 10')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 10')[0]) : null"
                             class="h-full w-full relative cursor-pointer flex items-end">
                            <div class="absolute inset-0 flex items-center justify-center p-4">
                                <img :src="getMenuIllustration('Menu 10')" class="h-28 w-28 object-contain drop-shadow-xl" alt="">
                            </div>
                            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 8px 8px;"></div>
                            <div class="w-full bg-black/50 backdrop-blur-sm px-4 py-2 flex justify-between items-center z-10 transition group-hover:bg-black/70">
                                 <span class="font-bold text-sm uppercase">{{ getMenuItems('Menu 10')[0]?.title || 'PROMO' }}</span>
                                 <span class="text-[10px] text-white/80 font-medium tracking-widest">Menu 10</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bottom Right Area - Col Span 4 (desktop: 4 cols, mobile: full 2 cols, h-auto or h-48) -->
                <div class="col-span-2 md:col-span-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 min-h-[12rem] h-auto md:h-48">
                    
                    <!-- Sub-Grid for Menu 11, 12, 13, 14 - Col Span 2 -->
                    <div class="col-span-1 sm:col-span-2 md:col-span-2 grid grid-cols-2 grid-rows-2 gap-3 sm:gap-4 h-48 md:h-full">
                         <!-- Menu 11 -->
                         <div class="min-h-0 min-w-0 bg-red-600 rounded-lg shadow-md flex transition border-b-4 border-red-800 text-white p-2 h-full group relative overflow-hidden" :style="[getMenuNewsImageStyle('Menu 11'), getMenuBackgroundStyle('Menu 11')]">
                              <!-- Ambient 3D Graphic Watermark -->
                              <img :src="getMenuIllustration('Menu 11')" class="absolute -right-3 -bottom-3 w-16 h-16 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">
                              <div v-if="getMenuItems('Menu 11').length >= 1" class="h-full w-full relative z-10">
                                  <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 11'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 11')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/30 border border-white/10 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'chevron-right'), 'text-sm flex-shrink-0']"></i>
                                          <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 11'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto content-start justify-center menu-scroll-container scroll-smooth px-1 pt-2 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 11')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1.5 cursor-pointer w-[68px] h-[68px] text-center border border-white/20">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-8 h-8 object-contain mb-0.5">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-xl mb-0.5', '[filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))']" ></i>
                                          <span class="text-[10px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- SLIDE -->
                                  <div v-else-if="menuLayoutConfig['Menu 11'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                       @click="getCurrentSlideItem('Menu 11') ? handlePortalItemClick(getCurrentSlideItem('Menu 11')) : null" @mousedown="getCurrentSlideItem('Menu 11') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 11')) : null">
                                        
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 11', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 11', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                      <div class="flex flex-col items-center animate-fade-in-out text-center" :key="menuSlideIndices['Menu 11']">
                                           <img v-if="getMenuItems('Menu 11')[menuSlideIndices['Menu 11'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 11')[menuSlideIndices['Menu 11'] || 0].image_name)" class="h-8 w-8 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 11')[menuSlideIndices['Menu 11'] || 0].image_name || 'link'), 'text-2xl mb-1']"></i>
                                           <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 11')[menuSlideIndices['Menu 11'] || 0].title }}</span>
                                      </div>
                                  </div>
                              </div>
                              <div v-else @click="getMenuItems('Menu 11')[0] ? handlePortalItemClick(getMenuItems('Menu 11')[0]) : null" @mousedown="getMenuItems('Menu 11')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 11')[0]) : null"
                                   class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-red-500/80 rounded transition relative z-10 p-2 text-center">
                                 <div class="w-12 h-12 flex items-center justify-center mb-1">
                                     <img :src="getMenuIllustration('Menu 11')" class="w-full h-full object-contain drop-shadow-md" alt="">
                                 </div>
                                 <span class="font-bold text-xs tracking-wide uppercase line-clamp-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ getMenuItems('Menu 11')[0]?.title || 'Menu 11' }}</span>
                              </div>
                         </div>
                        
                        <!-- Menu 13 -->
                         <div class="min-h-0 min-w-0 bg-teal-500 rounded-lg shadow-md flex transition border-b-4 border-teal-700 text-white p-2 h-full group relative overflow-hidden" :style="[getMenuNewsImageStyle('Menu 13'), getMenuBackgroundStyle('Menu 13')]">
                              <!-- Ambient 3D Graphic Watermark -->
                              <img :src="getMenuIllustration('Menu 13')" class="absolute -right-3 -bottom-3 w-16 h-16 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">
                              <div v-if="getMenuItems('Menu 13').length >= 1" class="h-full w-full relative z-10">
                                  <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 13'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 13')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/30 border border-white/10 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'chevron-right'), 'text-sm flex-shrink-0']"></i>
                                          <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 13'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto content-start justify-center menu-scroll-container scroll-smooth px-1 pt-2 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 13')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1.5 cursor-pointer w-[68px] h-[68px] text-center border border-white/20">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-8 h-8 object-contain mb-0.5">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-xl mb-0.5', '[filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))']" ></i>
                                          <span class="text-[10px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- SLIDE -->
                                  <div v-else-if="menuLayoutConfig['Menu 13'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                       @click="getCurrentSlideItem('Menu 13') ? handlePortalItemClick(getCurrentSlideItem('Menu 13')) : null" @mousedown="getCurrentSlideItem('Menu 13') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 13')) : null">
                                       
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 13', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 13', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                      <div class="flex flex-col items-center animate-fade-in-out text-center" :key="menuSlideIndices['Menu 13']">
                                           <img v-if="getMenuItems('Menu 13')[menuSlideIndices['Menu 13'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 13')[menuSlideIndices['Menu 13'] || 0].image_name)" class="h-8 w-8 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 13')[menuSlideIndices['Menu 13'] || 0].image_name || 'link'), 'text-2xl mb-1']"></i>
                                           <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 13')[menuSlideIndices['Menu 13'] || 0].title }}</span>
                                      </div>
                                  </div>
                              </div>
                              <div v-else @click="getMenuItems('Menu 13')[0] ? handlePortalItemClick(getMenuItems('Menu 13')[0]) : null" @mousedown="getMenuItems('Menu 13')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 13')[0]) : null"
                                   class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-teal-400/80 rounded transition relative z-10 p-2 text-center">
                                 <div class="w-12 h-12 flex items-center justify-center mb-1">
                                     <img :src="getMenuIllustration('Menu 13')" class="w-full h-full object-contain drop-shadow-md" alt="">
                                 </div>
                                 <span class="font-bold text-xs tracking-wide uppercase line-clamp-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ getMenuItems('Menu 13')[0]?.title || 'Menu 13' }}</span>
                              </div>
                         </div>

                        <!-- Menu 12 -->
                         <div class="min-h-0 min-w-0 bg-teal-500 rounded-lg shadow-md flex transition border-b-4 border-teal-700 text-white p-2 h-full group relative overflow-hidden" :style="[getMenuNewsImageStyle('Menu 12'), getMenuBackgroundStyle('Menu 12')]">
                              <!-- Ambient 3D Graphic Watermark -->
                              <img :src="getMenuIllustration('Menu 12')" class="absolute -right-3 -bottom-3 w-16 h-16 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">
                              <div v-if="getMenuItems('Menu 12').length >= 1" class="h-full w-full relative z-10">
                                  <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 12'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 12')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/30 border border-white/10 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'chevron-right'), 'text-sm flex-shrink-0']"></i>
                                          <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 12'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto content-start justify-center menu-scroll-container scroll-smooth px-1 pt-2 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 12')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1.5 cursor-pointer w-[68px] h-[68px] text-center border border-white/20">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-8 h-8 object-contain mb-0.5">
                                          <i v-else :class="['fas', 'fa-' + (item.image_name || 'link'), 'text-xl mb-0.5', '[filter:drop-shadow(0_1px_2px_rgba(0,0,0,0.8))']" ></i>
                                          <span class="text-[10px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- SLIDE -->
                                  <div v-else-if="menuLayoutConfig['Menu 12'] === 'slide'" class="h-full flex items-center justify-center cursor-pointer relative"
                                       @click="getCurrentSlideItem('Menu 12') ? handlePortalItemClick(getCurrentSlideItem('Menu 12')) : null" @mousedown="getCurrentSlideItem('Menu 12') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 12')) : null">
                                       
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 12', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 12', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                      <div class="flex flex-col items-center animate-fade-in-out text-center" :key="menuSlideIndices['Menu 12']">
                                           <img v-if="getMenuItems('Menu 12')[menuSlideIndices['Menu 12'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 12')[menuSlideIndices['Menu 12'] || 0].image_name)" class="h-8 w-8 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 12')[menuSlideIndices['Menu 12'] || 0].image_name || 'link'), 'text-2xl mb-1']"></i>
                                           <span class="text-[9px] font-bold uppercase [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ getMenuItems('Menu 12')[menuSlideIndices['Menu 12'] || 0].title }}</span>
                                      </div>
                                  </div>
                              </div>
                              <div v-else @click="getMenuItems('Menu 12')[0] ? handlePortalItemClick(getMenuItems('Menu 12')[0]) : null" @mousedown="getMenuItems('Menu 12')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 12')[0]) : null"
                                   class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-teal-400/80 rounded transition relative z-10 p-2 text-center">
                                 <div class="w-12 h-12 flex items-center justify-center mb-1">
                                     <img :src="getMenuIllustration('Menu 12')" class="w-full h-full object-contain drop-shadow-md" alt="">
                                 </div>
                                 <span class="font-bold text-[10px] leading-tight tracking-wide uppercase line-clamp-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ getMenuItems('Menu 12')[0]?.title || 'Menu 12' }}</span>
                              </div>
                         </div>

                        <!-- Menu 14 -->
                         <div class="min-h-0 min-w-0 bg-amber-700 rounded-lg shadow-md flex transition border-b-4 border-amber-900 text-white p-2 h-full overflow-hidden group relative" :style="[getMenuNewsImageStyle('Menu 14'), getMenuBackgroundStyle('Menu 14')]">
                              <!-- Ambient 3D Graphic Watermark -->
                              <img :src="getMenuIllustration('Menu 14')" class="absolute -right-3 -bottom-3 w-16 h-16 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">
                              <!-- Case 1: Multiple Items -->
                              <div v-if="getMenuItems('Menu 14').length >= 1" class="h-full w-full relative z-10">
                                  
                                  <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 14'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-col overflow-y-auto menu-scroll-container scroll-smooth pt-2 pb-2 px-1 space-y-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 14')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex items-center space-x-2 py-1.5 px-2 hover:bg-white/20 rounded cursor-pointer bg-black/30 border border-white/10 text-xs font-bold uppercase leading-tight [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">
                                          <span class="truncate text-xs [text-shadow:0_1px_3px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>

                                  <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 14'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="absolute inset-0 flex flex-wrap gap-1 overflow-y-auto content-start justify-center menu-scroll-container scroll-smooth px-1 pt-2 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                                      <div v-for="item in getMenuItems('Menu 14')" :key="item.id" 
                                           @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                           class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-1.5 cursor-pointer w-[68px] h-[68px] text-center border border-white/20">
                                          <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                          <span class="text-[10px] font-bold uppercase leading-tight line-clamp-2 [text-shadow:0_1px_2px_rgba(0,0,0,0.9)]">{{ item.title }}</span>
                                      </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>

                                  <!-- SLIDE -->
                                  <div v-else-if="menuLayoutConfig['Menu 14'] === 'slide'" class="relative h-full flex items-center justify-center pointer-events-none-children-except-arrows">
                                      <div class="h-full w-full flex flex-col items-center justify-center cursor-pointer relative" 
                                           @click="getCurrentSlideItem('Menu 14') ? handlePortalItemClick(getCurrentSlideItem('Menu 14')) : null" @mousedown="getCurrentSlideItem('Menu 14') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 14')) : null">
                                           
                                           <!-- Arrows -->
                                            <button @click.stop="moveSlide('Menu 14', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                            <button @click.stop="moveSlide('Menu 14', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                           <!-- Background Image Faded -->
                                           <img v-if="getMenuItems('Menu 14')[menuSlideIndices['Menu 14'] || 0].image_name?.includes('.')" 
                                                :src="getImagePath('images/' + getMenuItems('Menu 14')[menuSlideIndices['Menu 14'] || 0].image_name)" 
                                                class="absolute inset-0 w-full h-full object-cover opacity-20 pointer-events-none">
                                           
                                           <div class="animate-fade-in-out text-center relative z-10" :key="menuSlideIndices['Menu 14']">
                                                <div class="flex justify-center mb-1">
                                                     <img v-if="getMenuItems('Menu 14')[menuSlideIndices['Menu 14'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 14')[menuSlideIndices['Menu 14'] || 0].image_name)" class="h-8 w-8 object-contain drop-shadow-md">
                                                </div>
                                                <h3 class="text-[10px] font-bold uppercase tracking-widest text-white drop-shadow-sm px-1 line-clamp-2">{{ getMenuItems('Menu 14')[menuSlideIndices['Menu 14'] || 0].title }}</h3>
                                           </div>
                                      </div>
                                  </div>

                              </div>
                              
                              <!-- Case 2: Single Item -->
                              <div v-else @click="getMenuItems('Menu 14')[0] ? handlePortalItemClick(getMenuItems('Menu 14')[0]) : null" @mousedown="getMenuItems('Menu 14')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 14')[0]) : null"
                                   class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-amber-600 rounded transition relative z-10 p-2 text-center">
                                 <div class="w-12 h-12 flex items-center justify-center mb-1">
                                     <img :src="getMenuIllustration('Menu 14')" class="w-full h-full object-contain drop-shadow-md" alt="">
                                 </div>
                                 <span class="font-bold text-xs tracking-wide uppercase line-clamp-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ getMenuItems('Menu 14')[0]?.title || 'Menu 14' }}</span>
                              </div>
                         </div>
                    </div>

                    <!-- Menu 15: Corner Large -->
                    <div class="col-span-1 sm:col-span-2 md:col-span-2 h-48 md:h-full">
                        <div class="min-h-0 min-w-0 bg-teal-700/80 rounded-xl shadow-lg border border-teal-500/50 relative overflow-hidden h-full group transition text-white" :style="[getMenuNewsImageStyle('Menu 15'), getMenuBackgroundStyle('Menu 15')]">
                             <!-- Ambient 3D Graphic Watermark -->
                             <img :src="getMenuIllustration('Menu 15')" class="absolute -right-4 -bottom-4 w-32 h-32 opacity-25 object-contain pointer-events-none group-hover:scale-105 group-hover:opacity-35 transition-all duration-300 drop-shadow-md z-0" alt="">
                             <div v-if="getMenuItems('Menu 15').length >= 1" class="absolute inset-0 z-10">
                                  <!-- LIST -->
                                  <div v-if="(menuLayoutConfig['Menu 15'] || 'list') === 'list'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 h-full flex flex-col p-4 overflow-y-auto custom-scrollbar bg-black/20 space-y-2 menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                     <h3 class="font-bold text-xs uppercase opacity-80 mb-1 border-b border-white/20 pb-1 flex items-center justify-between">
                                         <span>Menu 15</span>
                                         <i class="fas fa-globe text-xs"></i>
                                     </h3>
                                     <div v-for="item in getMenuItems('Menu 15')" :key="item.id" 
                                          @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                          class="flex items-center space-x-3 py-1.5 px-3 hover:bg-white/20 rounded cursor-pointer transition bg-black/30 border border-white/10">
                                         <img v-if="item.image_name && item.image_name.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain flex-shrink-0 drop-shadow">    
                                         <i v-else :class="['fas', 'fa-' + (item.image_name || 'external-link-alt'), 'text-sm text-teal-300']"></i>
                                         <span class="text-[11px] font-bold uppercase truncate [text-shadow:0_1px_3px_rgba(0,0,0,0.8)]">{{ item.title }}</span>
                                     </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- HORIZONTAL -->
                                  <div v-else-if="menuLayoutConfig['Menu 15'] === 'horizontal'" class="relative h-full w-full group overflow-hidden flex flex-col justify-center min-h-0">
                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mt-0.5"><i class="fas fa-chevron-up text-xs"></i></button>
                                      <div class="min-h-0 h-full p-4 overflow-y-auto bg-black/20 flex flex-wrap content-start gap-2 menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                                     <div v-for="item in getMenuItems('Menu 15')" :key="item.id" 
                                          @click="handlePortalItemClick(item)" @mousedown="handleMiddleClickPortal($event, item)"
                                          class="flex flex-col items-center justify-center bg-black/30 hover:bg-black/50 rounded p-2 cursor-pointer w-[80px] h-[80px] text-center border border-white/20">
                                         <img v-if="item.image_name?.includes('.')" :src="getImagePath('images/' + item.image_name)" class="w-6 h-6 object-contain mb-0.5">
                                      <i v-if="!item.image_name?.includes('.')" :class="['fas', 'fa-' + (item.image_name || 'external-link-alt'), 'text-xl mb-1 text-teal-300']"></i>
                                         <span class="text-[10px] font-bold uppercase leading-tight line-clamp-2">{{ item.title }}</span>
                                     </div>
                                      </div>
                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/70 hover:text-white opacity-0 group-hover:opacity-100 z-20 transition drop-shadow-md mb-0.5"><i class="fas fa-chevron-down text-xs"></i></button>
                                  </div>
                                  <!-- SLIDE -->
                                  <div v-else-if="menuLayoutConfig['Menu 15'] === 'slide'" class="h-full w-full flex items-center justify-center cursor-pointer bg-black/20 relative"
                                       @click="getCurrentSlideItem('Menu 15') ? handlePortalItemClick(getCurrentSlideItem('Menu 15')) : null" @mousedown="getCurrentSlideItem('Menu 15') ? handleMiddleClickPortal($event, getCurrentSlideItem('Menu 15')) : null">
                                       
                                        <!-- Arrows -->
                                        <button @click.stop="moveSlide('Menu 15', 'prev')" class="absolute left-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-left text-xs"></i></button>
                                        <button @click.stop="moveSlide('Menu 15', 'next')" class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-white/50 hover:text-white transition opacity-0 group-hover:opacity-100 z-20"><i class="fas fa-chevron-right text-xs"></i></button>

                                      <div class="flex flex-col items-center animate-fade-in-out text-center px-6" :key="menuSlideIndices['Menu 15']">
                                           <div class="mb-2 p-2 bg-white/10 rounded-full">
                                                <img v-if="getMenuItems('Menu 15')[menuSlideIndices['Menu 15'] || 0].image_name?.includes('.')" :src="getImagePath('images/' + getMenuItems('Menu 15')[menuSlideIndices['Menu 15'] || 0].image_name)" class="h-8 w-8 object-contain mb-1 drop-shadow-md">
                                           <i v-else :class="['fas', 'fa-' + (getMenuItems('Menu 15')[menuSlideIndices['Menu 15'] || 0].image_name || 'external-link-alt'), 'text-3xl text-teal-300']"></i>
                                           </div>
                                           <span class="text-xs font-bold uppercase tracking-wide">{{ getMenuItems('Menu 15')[menuSlideIndices['Menu 15'] || 0].title }}</span>
                                      </div>
                                  </div>
                              </div>
                             <div v-else @click="getMenuItems('Menu 15')[0] ? handlePortalItemClick(getMenuItems('Menu 15')[0]) : null" @mousedown="getMenuItems('Menu 15')[0] ? handleMiddleClickPortal($event, getMenuItems('Menu 15')[0]) : null"
                                  class="p-4 h-full flex flex-col justify-center relative cursor-pointer hover:bg-teal-600 transition z-10">
                                <div class="flex items-center space-x-3 mb-2 relative z-10">
                                    <div class="w-14 h-14 flex items-center justify-center flex-shrink-0">
                                        <img :src="getMenuIllustration('Menu 15')" class="w-full h-full object-contain drop-shadow-lg" alt="">
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm uppercase tracking-wider">{{ getMenuItems('Menu 15')[0]?.title || 'Menu 15' }}</h3>
                                        <p class="text-[10px] text-white/70 leading-relaxed mt-0.5">Access external resources and tools.</p>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>

        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    getMenuItems: {
        type: Function,
        required: true
    },
    menuLayoutConfig: {
        type: Object,
        default: () => ({})
    },
    menuSlideIndices: {
        type: Object,
        default: () => ({})
    },
    getCurrentSlideItem: {
        type: Function,
        required: true
    },
    moveSlide: {
        type: Function,
        required: true
    },
    getMenuNewsImageStyle: {
        type: Function,
        required: true
    },
    getMenuBackgroundStyle: {
        type: Function,
        required: true
    }
});

const emit = defineEmits([
    'portal-item-click',
    'middle-click-portal'
]);

const handlePortalItemClick = (item) => {
    emit('portal-item-click', item);
};

const handleMiddleClickPortal = (event, item) => {
    emit('middle-click-portal', { event, item });
};

const getImagePath = (path) => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) return path;
    const clean = path.startsWith('/') ? path.slice(1) : path;
    return './' + clean;
};

// Fallback 3D Illustration Mapping for each Menu (Menu 1 - Menu 15)
const menuIllustrations = {
    'Menu 1': '3d/menu-1-knowledge.svg',
    'Menu 2': '3d/menu-2-apps.svg',
    'Menu 3': '3d/menu-3-portal.svg',
    'Menu 4': '3d/menu-4-options.svg',
    'Menu 5': '3d/menu-5-news.svg',
    'Menu 6': '3d/menu-6-sharing.svg',
    'Menu 7': '3d/menu-7-banner.svg',
    'Menu 8': '3d/menu-8-rates.svg',
    'Menu 9': '3d/menu-9-giro.svg',
    'Menu 10': '3d/menu-10-promo.svg',
    'Menu 11': '3d/menu-11-alert.svg',
    'Menu 12': '3d/menu-12-links.svg',
    'Menu 13': '3d/menu-13-calculator.svg',
    'Menu 14': '3d/menu-14-guide.svg',
    'Menu 15': '3d/menu-15-external.svg'
};

const getMenuIllustration = (menuKey) => {
    const items = props.getMenuItems(menuKey);
    if (items && items.length > 0 && items[0].image_name && items[0].image_name.includes('.')) {
        return getImagePath('images/' + items[0].image_name);
    }
    const def = menuIllustrations[menuKey];
    return def ? getImagePath('images/' + def) : '';
};
</script>
