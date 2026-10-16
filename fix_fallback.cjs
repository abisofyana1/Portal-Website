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
        
        // If there's an active sub menu but NO matching news image for it,
        // we DO NOT fall back to showing other sub menus' images.
        // We only fall back to generic Menu-level images (like 'menu 15') if they exist.
        const genericCat1 = menuKey.toLowerCase();
        const genericCat2 = menuKey.replace(' ', '').toLowerCase();
        const genericImgs = imgs.filter(i => i.category === genericCat1 || i.category === genericCat2);
        if (genericImgs.length > 0) {
            const imgToShow = genericImgs[carouselIdx % genericImgs.length];
            return {
                backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgToShow.img}')\`,
                backgroundSize: 'cover',
                backgroundPosition: 'center top',
                backgroundRepeat: 'no-repeat'
            };
        }
        
        // Return empty so it uses the normal background color instead of another sub menu's image
        return {};
    }

    // FALLBACK when there is no activeItem (e.g. no sub menus configured)
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
console.log('Fixed fallback logic in Home.vue');
