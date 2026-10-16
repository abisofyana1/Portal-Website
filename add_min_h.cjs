const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let content = fs.readFileSync(file, 'utf8');

// Match the line that defines the root of each Menu card
// We know they all have `:style="[getMenuNewsImageStyle('Menu X'), getMenuBackgroundStyle('Menu X')]"`
// We will look for class="..." in the same div, and add "min-h-0 min-w-0 "

content = content.replace(/(<div[^>]*class=")([^"]*)("[^>]*:style="\[getMenuNewsImageStyle\('Menu \d+'\)[^\]]+\]">)/g, (match, p1, p2, p3) => {
    // Check if it already has min-h-0
    if (p2.includes('min-h-0')) {
        return match;
    }
    return p1 + 'min-h-0 min-w-0 ' + p2 + p3;
});

fs.writeFileSync(file, content);
console.log('Added min-h-0 min-w-0 to all menu cards.');
