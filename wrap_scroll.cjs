const fs = require('fs');
const file = 'resources/js/components/Home.vue';
let lines = fs.readFileSync(file, 'utf8').split('\n');

let inTargetBlock = false;
let blockType = ''; // 'LIST' or 'HORIZONTAL'
let divDepth = 0;
let newLines = [];
let targetDepth = 0;
let justSawComment = false;

// Regex to catch the opening div of LIST/HORIZONTAL
const divRegex = /^\s*<div\s+v-(else-if|if)="[^"]+"\s+class="([^"]+)">\s*$/;

for (let i = 0; i < lines.length; i++) {
    let line = lines[i];
    
    // We count depth
    const opens = (line.match(/<div(\s|>)/g) || []).length;
    const closes = (line.match(/<\/div>/g) || []).length;

    if (line.includes('<!-- LIST -->') || line.includes('<!-- HORIZONTAL -->')) {
        justSawComment = true;
        newLines.push(line);
        continue;
    }

    if (justSawComment && line.trim().startsWith('<div v-')) {
        let m = line.match(/^\s*(<div\s+v-(?:else-if|if)="[^"]+")\s+class="([^"]+)">/);
        if (m) {
            inTargetBlock = true;
            targetDepth = divDepth; 
            divDepth += opens - closes;
            
            let vIfPart = m[1];
            let classes = m[2];
            
            // We strip overflow-y-auto to hide standard scrollbar if we use style, but we'll leave it 
            // and add scrollbar hiding style.
            
            newLines.push(`                                  ${vIfPart} class="relative h-full w-full group overflow-hidden flex flex-col justify-center">`);
            newLines.push(`                                      <button @click.stop="scrollMenuContainer($event, -1)" class="absolute top-0 left-1/2 -translate-x-1/2 p-1 text-white/50 hover:text-white bg-black/40 w-full flex justify-center opacity-0 group-hover:opacity-100 z-20 transition"><i class="fas fa-chevron-up text-xs"></i></button>`);
            newLines.push(`                                      <div class="${classes} menu-scroll-container scroll-smooth pb-4 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">`);
            
            justSawComment = false;
            continue;
        }
    }
    
    justSawComment = false;
    
    if (inTargetBlock) {
        divDepth += opens - closes;
        if (divDepth === targetDepth) {
            // We just closed the wrapper div!
            newLines.push(`                                      </div>`); // close the inner div
            newLines.push(`                                      <button @click.stop="scrollMenuContainer($event, 1)" class="absolute bottom-0 left-1/2 -translate-x-1/2 p-1 text-white/50 hover:text-white bg-black/40 w-full flex justify-center opacity-0 group-hover:opacity-100 z-20 transition"><i class="fas fa-chevron-down text-xs"></i></button>`);
            newLines.push(line); // push the original closing div
            inTargetBlock = false;
            continue;
        }
    } else {
        divDepth += opens - closes;
    }

    newLines.push(line);
}

fs.writeFileSync(file, newLines.join('\n'));
console.log('Done wrapping list/horizontal modes!');
