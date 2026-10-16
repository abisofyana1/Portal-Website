const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

const styleRegex = /const getMenuNewsImageStyle = \(menuKey\) => \{[\s\S]*?return \{\};\s*\};/;
const newStyle = `const getMenuNewsImageStyle = (menuKey) => {
    if (menuLayoutConfig.value[menuKey] !== 'slide') return {};

    const imgs = menuNewsImages.value[menuKey];
    if (!imgs || imgs.length === 0) return {};

    const activeIdx = menuSlideIndices.value[menuKey] || 0;
    const items = getMenuItems(menuKey);
    const activeItem = items[activeIdx];

    const carouselIdx = menuNewsCarouselIndices.value[menuKey] || 0;

    if (activeItem && activeItem.title) {
        const activeTitle = activeItem.title.trim().toLowerCase();
        
        // Find ALL images matching this Sub Menu title
        const matchedImgs = imgs.filter(i => i.category === activeTitle);
        
        if (matchedImgs.length > 0) {
            // To prevent the carouselIdx from skipping images when synchronized with the Sub Menu sliding,
            // we divide by the total number of Sub Menus. This ensures that every full loop of the Sub Menus
            // advances the background image index by 1.
            const totalItems = items.length > 0 ? items.length : 1;
            const imgIdx = Math.floor(carouselIdx / totalItems) % matchedImgs.length;
            
            const imgToShow = matchedImgs[imgIdx];
            return {
                backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgToShow.img}')\`,
                backgroundSize: 'cover',
                backgroundPosition: 'center top',
                backgroundRepeat: 'no-repeat'
            };
        }
    }

    // FALLBACK
    if (imgs[carouselIdx % imgs.length]?.img) {
        return {
            backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgs[carouselIdx % imgs.length].img}')\`,
            backgroundSize: 'cover',
            backgroundPosition: 'center top',
            backgroundRepeat: 'no-repeat'
        };
    }

    return {};
};`;

content = content.replace(styleRegex, newStyle);

fs.writeFileSync(file, content);
console.log('Fixed math logic for multiple news images in Home.vue');
