<template>
    <!-- Right Column (Hero Banner) - Col Span 2 on mobile, 4 on desktop -->
    <!-- Spans the height of Menu 6 + gap + Menu 8/9 approx -->
    <div class="col-span-2 md:col-span-4">
        <!-- Menu 7: Banner/News Carousel -->
        <div class="bg-gray-900 rounded-2xl shadow-2xl overflow-hidden relative border-4 border-white/90 group h-[17.5rem] select-none cursor-pointer"
             @mousedown="(e) => { onDragStart(e); handleMiddleClickBanner(e); }"
             @mouseup="onDragEnd"
             @mouseleave="isDragging = false"
             @touchstart="onDragStart"
             @touchend="onDragEnd"
             @click.stop="handleBannerClick()"
             :style="getMenuBackgroundStyle ? getMenuBackgroundStyle('Menu 7') : {}"
        > 
            <div v-if="carouselNews && carouselNews.length > 0" class="absolute inset-0 w-full h-full bg-white flex overflow-hidden">
                <!-- Item 1 -->
                <div class="flex-1 relative border-r border-gray-200 group/item cursor-pointer" @click.stop="handleBannerClick(carouselNews[currentNewsIndex])">
                    <img v-if="getNewsImage(carouselNews[currentNewsIndex])"
                         :src="getNewsImage(carouselNews[currentNewsIndex])"
                         class="w-full h-full object-contain"
                    >
                    <div v-else class="w-full h-full bg-cover bg-center" style="background-image: url('/images/banner_bg.jpg')"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                    <div class="absolute bottom-10 left-0 right-0 z-10 px-4 pointer-events-none">
                        <div class="bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 max-w-sm mx-auto flex items-center justify-between">
                            <div class="flex-1">
                                <h2 class="text-xs font-black text-white mb-0.5 tracking-tight line-clamp-2 drop-shadow">{{ carouselNews[currentNewsIndex]?.title }}</h2>
                                <span class="text-white/70 text-[9px] opacity-0 group-hover/item:opacity-100 transition font-semibold">Klik untuk detail</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Item 2 -->
                <div v-if="carouselNews.length > 1" class="flex-1 relative group/item cursor-pointer" @click.stop="handleBannerClick(carouselNews[(currentNewsIndex + 1) % carouselNews.length])">
                    <img v-if="getNewsImage(carouselNews[(currentNewsIndex + 1) % carouselNews.length])"
                         :src="getNewsImage(carouselNews[(currentNewsIndex + 1) % carouselNews.length])"
                         class="w-full h-full object-contain"
                    >
                    <div v-else class="w-full h-full bg-cover bg-center" style="background-image: url('/images/banner_bg.jpg')"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                    <div class="absolute bottom-10 left-0 right-0 z-10 px-4 pointer-events-none">
                        <div class="bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 max-w-sm mx-auto flex items-center justify-between">
                            <div class="flex-1">
                                <h2 class="text-xs font-black text-white mb-0.5 tracking-tight line-clamp-2 drop-shadow">{{ carouselNews[(currentNewsIndex + 1) % carouselNews.length]?.title }}</h2>
                                <span class="text-white/70 text-[9px] opacity-0 group-hover/item:opacity-100 transition font-semibold">Klik untuk detail</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Arrows -->
                <button 
                   v-if="carouselNews.length > 1"
                   @mousedown.stop
                   @mouseup.stop
                   @click.stop="prevSlide" 
                   class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/60 text-white w-8 h-8 rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 z-30 pointer-events-auto backdrop-blur-sm shadow-md border border-white/20">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button 
                   v-if="carouselNews.length > 1"
                   @mousedown.stop
                   @mouseup.stop
                   @click.stop="nextSlide" 
                   class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/60 text-white w-8 h-8 rounded-full flex items-center justify-center transition opacity-0 group-hover:opacity-100 z-30 pointer-events-auto backdrop-blur-sm shadow-md border border-white/20">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
            <div v-else class="absolute inset-0 bg-gray-100 flex items-center justify-center bg-[url('/images/banner_bg.jpg')] bg-cover bg-center">
                <div class="text-center p-8 bg-white/80 backdrop-blur-sm rounded-xl border border-gray-200 shadow-sm">
                    <h2 class="text-5xl font-black text-orange-600 mb-2 tracking-tighter filter drop-shadow-sm">PORTAL</h2>
                    <h3 class="text-2xl font-bold text-gray-700 uppercase tracking-wide">Welcome</h3>
                    <p class="text-xs text-gray-500 mt-2 font-mono">No News Available</p>
                </div>
            </div>
            
            <!-- Update Info footer -->
            <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white px-6 py-2 text-xs flex justify-between items-center backdrop-blur-md z-20">
                <div class="flex items-center space-x-4">
                    <span class="font-medium tracking-wide">News: {{ (carouselNews?.length > 0) ? (currentNewsIndex + 1) : 0 }} / {{ carouselNews?.length || 0 }}</span>
                    <button @click.stop="$emit('open-promo-list')" @mousedown="handleMiddleClickPromoList($event)" class="bg-teal-600 hover:bg-teal-500 text-white px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider transition shadow flex items-center">
                        <i class="fas fa-list-ul mr-1"></i> List News
                    </button>
                </div>
                <span class="bg-red-600 text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider shadow">Slide to changes</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    carouselNews: {
        type: Array,
        default: () => []
    },
    currentNewsIndex: {
        type: Number,
        default: 0
    },
    getNewsImage: {
        type: Function,
        required: true
    },
    getMenuBackgroundStyle: {
        type: Function,
        default: () => ({})
    }
});

const emit = defineEmits([
    'update:currentNewsIndex',
    'select-news',
    'open-promo-list',
    'middle-click-banner',
    'middle-click-promo-list'
]);

// Drag Logic
const isDragging = ref(false);
const wasDragging = ref(false);
const startX = ref(0);

const onDragStart = (e) => {
    isDragging.value = true;
    wasDragging.value = false;
    startX.value = e.pageX || (e.touches && e.touches[0] ? e.touches[0].pageX : 0);
};

const onDragEnd = (e) => {
    if (!isDragging.value) return;
    const endX = e.pageX || (e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].pageX : 0);
    const diff = startX.value - endX;
    
    isDragging.value = false;

    if (Math.abs(diff) > 10 && props.carouselNews && props.carouselNews.length > 0) { 
        wasDragging.value = true;
        if (diff > 0) {
            nextSlide();
        } else {
            prevSlide();
        }
    } else {
        wasDragging.value = false;
    }
    
    setTimeout(() => { wasDragging.value = false; }, 200); 
};

const prevSlide = () => {
    if (!props.carouselNews || props.carouselNews.length === 0) return;
    const newIdx = (props.currentNewsIndex - 1 + props.carouselNews.length) % props.carouselNews.length;
    emit('update:currentNewsIndex', newIdx);
};

const nextSlide = () => {
    if (!props.carouselNews || props.carouselNews.length === 0) return;
    const newIdx = (props.currentNewsIndex + 1) % props.carouselNews.length;
    emit('update:currentNewsIndex', newIdx);
};

const handleBannerClick = (item = null) => {
    if (!wasDragging.value) {
        const target = item || (props.carouselNews && props.carouselNews[props.currentNewsIndex]);
        if (target) {
            emit('select-news', target);
        }
    }
};

const handleMiddleClickBanner = (e) => {
    emit('middle-click-banner', e);
};

const handleMiddleClickPromoList = (e) => {
    emit('middle-click-promo-list', e);
};
</script>
