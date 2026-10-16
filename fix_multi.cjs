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
            // Cycle through them using the carousel index
            const imgToShow = matchedImgs[carouselIdx % matchedImgs.length];
            return {
                backgroundImage: \`linear-gradient(rgba(0,0,0,0.38), rgba(0,0,0,0.55)), url('\${imgToShow.img}')\`,
                backgroundSize: 'cover',
                backgroundPosition: 'center top',
                backgroundRepeat: 'no-repeat'
            };
        }
    }

    // FALLBACK: If the active Sub Menu has no specific News, 
    // OR if there are no Sub Menus, we just cycle through all available News images for this Menu.
    // (e.g. News uploaded directly to 'Menu 15')
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
console.log('Fixed multiple news images logic in Home.vue');
