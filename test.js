
import fs from "fs";
const content = fs.readFileSync("resources/js/components/Home.vue", "utf8");

const templateStart = content.indexOf("<template>");
const templateEnd = content.lastIndexOf("</template>");
const templateStr = content.substring(templateStart, templateEnd + 11);

let stack = [];
let regex = /<\/?([a-zA-Z0-9\-]+)[^>]*?(?<!\/)>/gs;
let match;
while ((match = regex.exec(templateStr)) !== null) {
    let fullTag = match[0];
    let tagName = match[1].toLowerCase();
    
    if (fullTag.endsWith("/>") || fullTag.endsWith("/ >")) continue;
    
    const voidElements = ["area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"];
    if (voidElements.includes(tagName)) continue;

    if (fullTag.startsWith("</")) {
        if (stack.length > 0 && stack[stack.length - 1].tag === tagName) {
            stack.pop();
        } else {
            console.log("MISMATCH! Expected </" + (stack.length > 0 ? stack[stack.length - 1].tag : "EOF") + "> but found " + fullTag);
            let lineStr = templateStr.substring(0, match.index);
            let lineNum = (lineStr.match(/\n/g) || []).length + 1;
            console.log("Error at line relative to template: " + lineNum);
            break;
        }
    } else {
        stack.push({tag: tagName, index: match.index, full: fullTag});
    }
}
if (stack.length === 0) {
    console.log("Template is perfectly balanced!");
} else {
    console.log("Stack size at end:", stack.length);
}

