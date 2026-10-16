/**
 * Microsoft 365 (Word, Outlook Classic, PowerPoint, Excel) Paste Normalizer for CKEditor 5
 *
 * Ensures:
 * 1. Embedded images (from file:/// or RTF hex bytes) are extracted and converted to Base64 data URLs.
 * 2. BAB, SUB BAB, SUB SUB BAB, and custom outline numbering (BAB I, 1.1, 1.1.1, A., 1.) are preserved intact without being stripped or degraded into generic lists.
 * 3. Wingdings, Wingdings 2, Wingdings 3, Symbol, and PUA characters are converted to standard universal Unicode symbols (bullets, checkmarks, arrows, checkboxes).
 * 4. Punctuation marks (smart quotes, dashes, ellipses, non-breaking spaces) are preserved.
 * 5. Formatting, font families (Aptos, Calibri, Segoe UI, Mistral, etc.), font sizes, colors, and Excel/Word tables are fully preserved.
 * 6. Outlook/Word @list definitions from style headers are parsed so custom bullets (✔, ➢, ▪, ❖, •) and sub-list levels are cleanly rendered.
 */

// Wingdings character map (PUA 0xF000 + charCode or raw charCode)
export const WINGDINGS_MAP = {
    0x21: '✏', // pencil
    0x22: '✂', // scissors
    0x23: '✂',
    0x24: '👓',
    0x28: '☎', // phone
    0x29: '☏',
    0x2A: '✉', // envelope
    0x2B: '📬',
    0x33: '📁',
    0x34: '📂',
    0x3C: '📄',
    0x3D: '📃',
    0x40: '🔔',
    0x4A: '☺', // smiley
    0x4B: '😐', // neutral
    0x4C: '☹', // sad
    0x54: '⌛',
    0x55: '🖮',
    0x6C: '●', // large round bullet
    0x6E: '■', // black square
    0x75: '◆', // black diamond
    0x76: '❖', // diamond floral / four-diamond
    0x77: '✆',
    0x78: '✉',
    0x79: '☏',
    0x7A: '☎',
    0xA1: '§',
    0xA4: '❖',
    0xA7: '▪', // black small square bullet
    0xA8: '☐', // empty ballot box
    0xA9: '☉',
    0xAA: '★', // black star
    0xAB: '☆', // white star
    0xB0: '°',
    0xB1: '±',
    0xB2: '²',
    0xB3: '³',
    0xB4: '×',
    0xB7: '•', // standard bullet
    0xB8: '÷',
    0xBC: '¼',
    0xBD: '½',
    0xBE: '¾',
    0xC6: 'Ø',
    0xD8: '➢', // arrow bullet
    0xDC: '▲', // up triangle
    0xDD: '▼', // down triangle
    0xDE: '😊',
    0xDF: '😐',
    0xE0: '✉',
    0xE1: '➔', // right arrow
    0xE8: '👍',
    0xE9: '👎',
    0xEE: '✓', // checkmark
    0xEF: '✔', // heavy checkmark
    0xF0: '✕', // multiplication X
    0xF1: '✗', // ballot X
    0xF2: '✘', // heavy ballot X
    0xF8: '★',
    0xFB: '✗',
    0xFC: '✔', // checkmark
    0xFD: '✕', // x
    0xFE: '☑', // check in box
    0xFF: '☒'  // x in box
};

// Symbol font character map
export const SYMBOL_MAP = {
    0x20: ' ',
    0x21: '!',
    0x23: '#',
    0x25: '%',
    0x26: '&',
    0x2B: '+',
    0x2C: ',',
    0x2D: '-',
    0x2E: '.',
    0x2F: '/',
    0x3A: ':',
    0x3B: ';',
    0x3C: '<',
    0x3D: '=',
    0x3E: '>',
    0x3F: '?',
    0x40: '≅',
    0x41: 'Α',
    0x42: 'Β',
    0x43: 'Χ',
    0x44: 'Δ',
    0x45: 'Ε',
    0x46: 'Φ',
    0x47: 'Γ',
    0x48: 'Η',
    0x49: 'Ι',
    0x4B: 'Κ',
    0x4C: 'Λ',
    0x4D: 'Μ',
    0x4E: 'Ν',
    0x4F: 'Ο',
    0x50: 'Π',
    0x51: 'Θ',
    0x52: 'Ρ',
    0x53: 'Σ',
    0x54: 'Τ',
    0x55: 'Υ',
    0x56: 'ς',
    0x57: 'Ω',
    0x58: 'Ξ',
    0x59: 'Ψ',
    0x5A: 'Ζ',
    0x61: 'α',
    0x62: 'β',
    0x63: 'χ',
    0x64: 'δ',
    0x65: 'ε',
    0x66: 'φ',
    0x67: 'γ',
    0x68: 'η',
    0x69: 'ι',
    0x6B: 'κ',
    0x6C: 'λ',
    0x6D: 'μ',
    0x6E: 'ν',
    0x6F: 'ο',
    0x70: 'π',
    0x71: 'θ',
    0x72: 'ρ',
    0x73: 'σ',
    0x74: 'τ',
    0x75: 'υ',
    0x76: 'ϖ',
    0x77: 'ω',
    0x78: 'ξ',
    0x79: 'ψ',
    0x7A: 'ζ',
    0xA3: '≤',
    0xB1: '±',
    0xB2: '″',
    0xB3: '≥',
    0xB4: '×',
    0xB5: '∝',
    0xB7: '•', // standard round bullet
    0xB8: '÷',
    0xB9: '≠',
    0xBA: '≡',
    0xBB: '≈',
    0xBC: '…',
    0xD3: '©',
    0xD4: '™',
    0xDE: '⇒',
    0xDF: '⇔',
    0xE0: '⇐',
    0xE1: '⇑',
    0xE2: '⇒',
    0xE3: '⇓',
    0xE5: '∑',
    0xF2: '∫'
};

/**
 * Checks if the clipboard content is originating from Microsoft Office
 */
export function isMicrosoftOfficeContent(html, rtf, text) {
    if (html && (
        /mso-|MsoNormal|MsoList|MsoHeading|urn:schemas-microsoft-com:office|class=["'][^"']*Mso/i.test(html) ||
        /<meta[^>]*content=["']Word\./i.test(html) ||
        /<meta[^>]*content=["']Excel\./i.test(html) ||
        /<meta[^>]*content=["']PowerPoint\./i.test(html) ||
        /ProgId=["'](Word|Excel|PowerPoint)\./i.test(html) ||
        /<!--\[if\s+!supportLists\]/i.test(html) ||
        /v:shapes=/i.test(html) ||
        /xmlns:w="urn:schemas-microsoft-com:office:word"/i.test(html)
    )) {
        return true;
    }
    if (rtf && (
        /\\generator\s+Microsoft/i.test(rtf) ||
        /\\rtf1/i.test(rtf)
    )) {
        return true;
    }
    return false;
}

/**
 * Converts a hex string into Base64 format safely without call stack overflow
 */
export function hexToBase64(hexString) {
    const match = hexString.match(/.{1,2}/g);
    if (!match) return '';
    const bytes = new Uint8Array(match.map(b => parseInt(b, 16)));
    let binary = '';
    const chunkSize = 8192;
    for (let i = 0; i < bytes.length; i += chunkSize) {
        const chunk = bytes.subarray(i, i + chunkSize);
        binary += String.fromCharCode.apply(null, chunk);
    }
    return btoa(binary);
}

/**
 * Extracts images from RTF data including Word, Outlook, PowerPoint, and Excel picture headers
 */
export function extractImagesFromRtf(rtf) {
    if (!rtf || typeof rtf !== 'string') return [];
    const images = [];

    // Find pict blocks iteratively without large catastrophic backtracking regex
    let searchPos = 0;
    while ((searchPos = rtf.indexOf('\\pict', searchPos)) !== -1) {
        // Look for the start of the block containing \pict
        const openBrace = rtf.lastIndexOf('{', searchPos);
        if (openBrace === -1) {
            searchPos += 5;
            continue;
        }

        // Find the matching closing brace '}' for this group safely
        let depth = 1;
        let closeBrace = -1;
        for (let i = openBrace + 1; i < rtf.length; i++) {
            const ch = rtf[i];
            if (ch === '{') depth++;
            else if (ch === '}') {
                depth--;
                if (depth === 0) {
                    closeBrace = i;
                    break;
                }
            }
        }

        if (closeBrace === -1) {
            searchPos += 5;
            continue;
        }

        const pictBlock = rtf.substring(openBrace, closeBrace + 1);
        searchPos = closeBrace + 1;

        // Detect MIME type
        let mimeType = 'image/png';
        const typeMatch = pictBlock.match(/\\(pngblip|jpegblip|wmetafile\d*|emfblip)/i);
        if (typeMatch) {
            const typeTag = typeMatch[1].toLowerCase();
            if (typeTag.includes('jpeg') || typeTag.includes('jpg')) {
                mimeType = 'image/jpeg';
            } else {
                mimeType = 'image/png';
            }
        }

        // The hex data starts after the last \controlword and space in \pict
        // In RTF, the header has properties like \picw1000\pich1000\picwgoal5000\jpegblip followed by a space/newline and the hex sequence.
        // We find the last backslash in the pict block:
        const lastBackslash = pictBlock.lastIndexOf('\\');
        let hexCandidate = '';
        if (lastBackslash !== -1) {
            // Find the whitespace or newline that separates the control word and the hex payload
            const rest = pictBlock.slice(lastBackslash);
            const firstSpace = rest.search(/[\s\r\n]/);
            if (firstSpace !== -1) {
                hexCandidate = rest.slice(firstSpace);
            } else {
                hexCandidate = rest;
            }
        } else {
            hexCandidate = pictBlock;
        }

        // Remove closing curly brace if present in candidate
        hexCandidate = hexCandidate.replace(/\}[\s\r\n]*$/, '');
        const rawHex = hexCandidate.replace(/[^0-9a-fA-F]/g, '');
        if (rawHex.length >= 40) {
            try {
                const base64 = hexToBase64(rawHex);
                if (base64) {
                    images.push({
                        mimeType,
                        dataUrl: `data:${mimeType};base64,${base64}`
                    });
                }
            } catch (e) {
                console.warn('[OfficePaste] Failed to convert hex image to base64:', e);
            }
        }
    }
    return images;
}

/**
 * Replaces local / file:/// image URLs with base64 Data URLs extracted from RTF
 */
export function replaceLocalImagesWithRtfImages(html, rtfImages) {
    if (!html || !rtfImages || rtfImages.length === 0) return html;
    let imgIndex = 0;

    // 1. Replace VML <v:imagedata src="file:///...">
    html = html.replace(/<v:imagedata[^>]*src=["']([^"']*)["'][^>]*>/gi, (match, src) => {
        if (imgIndex < rtfImages.length) {
            const dataUrl = rtfImages[imgIndex++].dataUrl;
            return `<img src="${dataUrl}" class="cxc-office-img" style="max-width:100%;height:auto;display:inline-block;" />`;
        }
        return match;
    });

    // 2. Replace <img> tags with local or temp file:/// sources
    html = html.replace(/<img([^>]*)src=["']([^"']*)["']([^>]*)>/gi, (fullTag, before, src, after) => {
        if (/^(file:\/\/|blob:|x-apple|cid:|mso-file)/i.test(src) || src.includes('/AppData/Local/Temp/') || src.includes('\\AppData\\Local\\Temp\\') || !src) {
            if (imgIndex < rtfImages.length) {
                const dataUrl = rtfImages[imgIndex++].dataUrl;
                return `<img${before}src="${dataUrl}"${after}>`;
            }
        }
        return fullTag;
    });

    return html;
}

/**
 * Parses @list CSS definitions from Microsoft Office / Outlook style blocks.
 * Maps list id + level (e.g. "l5_1", "l5_2") to the resolved Unicode bullet symbol and font.
 */
export function parseOfficeListStyles(rawHtml) {
    const listMap = {};
    if (!rawHtml) return listMap;

    const listRegex = /@list\s+([a-zA-Z0-9]+)(?::level(\d+))?\s*\{([^}]+)\}/gi;
    let match;
    while ((match = listRegex.exec(rawHtml)) !== null) {
        const listId = match[1].toLowerCase();
        const level = match[2] ? parseInt(match[2], 10) : 1;
        const body = match[3];

        const textMatch = body.match(/mso-level-text:\s*([^;]+);/i);
        const fontMatch = body.match(/font-family:\s*([^;]+);/i);

        let symbolText = '';
        const font = fontMatch ? fontMatch[1].replace(/["']/g, '').trim() : '';

        if (textMatch) {
            let rawText = textMatch[1].trim();
            rawText = rawText.replace(/^"|"$/g, '').replace(/^'|'$/g, '');

            const hexEscapeMatch = rawText.match(/^\\([0-9a-fA-F]{2,4})$/);
            if (hexEscapeMatch) {
                const hexVal = parseInt(hexEscapeMatch[1], 16);
                const charCode = hexVal & 0xFF;
                if (/symbol/i.test(font) && SYMBOL_MAP[charCode]) {
                    symbolText = SYMBOL_MAP[charCode];
                } else if (WINGDINGS_MAP[charCode]) {
                    symbolText = WINGDINGS_MAP[charCode];
                } else {
                    symbolText = '•';
                }
            } else {
                symbolText = rawText;
            }
        }

        const key = `${listId}_${level}`;
        listMap[key] = {
            symbol: symbolText || '•',
            font
        };
    }
    return listMap;
}

/**
 * Translates Wingdings, Symbol, and Private Use Area (PUA) characters to standard Unicode
 */
export function convertPuaAndSymbolCharacters(text, fontFamily = '') {
    if (!text) return '';
    const isSymbol = /symbol/i.test(fontFamily);
    const isWingdings = /wingdings|webdings/i.test(fontFamily);

    let res = text.replace(/[\uF000-\uF0FF]/g, (char) => {
        const code = char.charCodeAt(0) & 0xFF;
        if (isSymbol && SYMBOL_MAP[code]) return SYMBOL_MAP[code];
        if (isWingdings && WINGDINGS_MAP[code]) return WINGDINGS_MAP[code];
        if (WINGDINGS_MAP[code]) return WINGDINGS_MAP[code];
        if (SYMBOL_MAP[code]) return SYMBOL_MAP[code];
        if (code === 0xB7) return '•';
        if (code === 0xA7) return '▪';
        if (code === 0xFC) return '✔';
        if (code === 0xFE) return '☑';
        if (code === 0xA8) return '☐';
        if (code === 0xD8) return '➢';
        if (code === 0x76) return '❖';
        return '•';
    });

    // If font is Wingdings, convert raw single-byte characters that Outlook/Word outputs in span
    if (isWingdings) {
        res = res.replace(/[\u0020-\u00FF]/g, (char) => {
            const code = char.charCodeAt(0);
            if (code === 0x20 || code === 0xA0) return ' ';
            if (WINGDINGS_MAP[code]) return WINGDINGS_MAP[code];
            if (code === 0xD8) return '➢'; // Ø in ansi -> ➢ in wingdings
            if (code === 0x76) return '❖'; // v in ansi -> ❖ in wingdings
            if (code === 0xFC) return '✔'; // ü in ansi -> ✔ in wingdings
            if (code === 0xA7) return '▪'; // § in ansi -> ▪ in wingdings
            return char;
        });
    }

    return res;
}

/**
 * Normalizes symbols and punctuation in the DOM
 */
export function normalizeSymbolsAndPunctuation(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
    let node;
    const textNodes = [];
    while ((node = walker.nextNode())) {
        textNodes.push(node);
    }

    textNodes.forEach(tNode => {
        const parent = tNode.parentElement;
        const font = parent ? (parent.style.fontFamily || parent.getAttribute('face') || '') : '';
        let val = tNode.nodeValue;

        // Convert PUA codes
        if (/[\uF000-\uF0FF]/.test(val)) {
            val = convertPuaAndSymbolCharacters(val, font);
        }

        // Clean common Office punctuation oddities
        val = val
            .replace(/\u2011/g, '-') // non-breaking hyphen to hyphen
            .replace(/\u00AD/g, '')  // soft hyphen
            .replace(/\u200B/g, ''); // zero-width space

        tNode.nodeValue = val;

        // If parent has Wingdings/Symbol font, replace font-family with inherit/sans-serif
        if (parent && /wingdings|webdings|symbol/i.test(font)) {
            parent.style.fontFamily = 'inherit';
        }
    });
}

/**
 * Normalizes Outlook / Word list items according to parsed @list styles.
 * Injects the exact visual bullet symbol (✔, ➢, ▪, ❖, etc.) and adjusts nested indents.
 */
export function normalizeOfficeLists(rootNode, listDefinitions = {}) {
    if (!rootNode) return;
    const doc = rootNode.ownerDocument || (rootNode.createElement ? rootNode : document);
    const listItems = Array.from(rootNode.querySelectorAll('li, p.MsoListParagraph, div.MsoListParagraph, p[style*="mso-list"], li[style*="mso-list"]'));
    
    listItems.forEach(item => {
        const style = item.getAttribute('style') || '';
        const match = style.match(/mso-list:\s*([a-zA-Z0-9]+)\s+level(\d+)/i);
        
        let targetSymbol = '';
        let level = 1;
        let listDef = null;

        if (match) {
            const listId = match[1].toLowerCase();
            level = parseInt(match[2], 10) || 1;
            const key = `${listId}_${level}`;
            listDef = listDefinitions[key] || listDefinitions[`${listId}_1`];
        }

        // Check explicit margin-left (e.g. margin-left: 36.0pt, 72.0pt, 108.0pt, etc.)
        // Outlook often sets level1 with increasing margin-left for sub-bullets
        const marginMatch = style.match(/margin-left:\s*(-?[\d\.]+)(pt|px|in|cm)/i);
        if (marginMatch) {
            const num = parseFloat(marginMatch[1]);
            const unit = marginMatch[2].toLowerCase();
            let ptVal = num;
            if (unit === 'px') ptVal = num * 0.75;
            else if (unit === 'in') ptVal = num * 72;
            else if (unit === 'cm') ptVal = num * 28.3465;

            if (ptVal > 15) {
                const calculatedLevel = Math.max(1, Math.round(ptVal / 36));
                level = Math.max(level, calculatedLevel);
            }
        }

        // 1. Check if unhidden marker exists from preserveBabAndOutlineStructure
        const marker = item.querySelector('.cxc-bullet-marker');
        if (marker) {
            const markerVal = marker.textContent.trim();
            if (markerVal) {
                targetSymbol = markerVal;
            }
            marker.remove();
        }

        // 2. Check if marker exists inside mso-list:Ignore spans before deleting them
        const ignoreSpans = item.querySelectorAll('span[style*="mso-list:Ignore"], span[style*="mso-list: Ignore"]');
        if (!targetSymbol || targetSymbol === '•') {
            for (let i = 0; i < ignoreSpans.length; i++) {
                const iSpan = ignoreSpans[i];
                const iFont = iSpan.style.fontFamily || (iSpan.getAttribute('face') || '');
                let sText = (iSpan.textContent || '').trim();

                if (/wingdings|symbol|webdings/i.test(iFont) || /[\uF000-\uF0FF]/.test(sText)) {
                    sText = convertPuaAndSymbolCharacters(sText, iFont).trim();
                }

                if (sText && (/^[a-zA-Z0-9]+[\.\)]/.test(sText) || /^\([a-zA-Z0-9]+\)/.test(sText) || /^[\u2022\u25AA\u27A4\u2714\u25CF\-\*\+➢▪❖✔☑☐•]/.test(sText))) {
                    targetSymbol = sText;
                    break;
                }
            }
        }

        // Clean out any leftover Office list-ignore spans or empty bullet spans inside the item
        ignoreSpans.forEach(s => s.remove());

        // 3. Fall back to listDefinitions symbol if marker not found
        if (!targetSymbol && listDef && listDef.symbol) {
            targetSymbol = listDef.symbol;
        }

        // 4. Check if text begins with numbering or letter prefix (e.g. "a. ", "b. ", "1. ")
        if (!targetSymbol || targetSymbol === '•') {
            const trimmedItemText = (item.textContent || '').trim();
            const textMarkerMatch = trimmedItemText.match(/^([a-zA-Z0-9]+[\.\)]|\([a-zA-Z0-9]+\))\s+/);
            if (textMarkerMatch) {
                targetSymbol = textMarkerMatch[1];
                // Remove the prefix from the first text node so it's not duplicated
                const walker = doc.createTreeWalker(item, NodeFilter.SHOW_TEXT, null, false);
                const firstTextNode = walker.nextNode();
                if (firstTextNode && firstTextNode.nodeValue) {
                    firstTextNode.nodeValue = firstTextNode.nodeValue.replace(/^([a-zA-Z0-9]+[\.\)]|\([a-zA-Z0-9]+\))\s*/, '');
                }
            }
        }

        // Clean out any duplicate leading bullet characters (e.g. "• ", "➢ ", "▪ ", "❖ ", "-", etc.)
        // in the remaining text nodes so the bullet symbol isn't shown twice!
        const walker = doc.createTreeWalker(item, NodeFilter.SHOW_TEXT, null, false);
        let firstNode = walker.nextNode();
        while (firstNode && !firstNode.nodeValue.trim()) {
            firstNode = walker.nextNode();
        }
        if (firstNode && firstNode.nodeValue) {
            // Remove leading bullet/symbol duplicates
            firstNode.nodeValue = firstNode.nodeValue.replace(/^[\s\u00A0]*[•▪➢❖✔☑☐●■◆★☆\-\*Øvü§·þ¨]+\s*/, '');
        }

        if (!targetSymbol) {
            targetSymbol = '•';
        }

        // Calculate left padding according to list nesting level
        // Level 1: 18px, Level 2: 36px, Level 3: 54px, Level 4: 72px
        const indentPx = (level - 1) * 22;
        const existingColor = item.style.color || (item.closest('[style*="color"]') ? item.closest('[style*="color"]').style.color : '');

        // Remove any existing bullet symbol span if re-normalizing
        const existingSymbolSpan = item.querySelector('.cxc-bullet-symbol');
        if (existingSymbolSpan) {
            existingSymbolSpan.remove();
        }

        const bulletSpan = doc.createElement('span');
        bulletSpan.className = 'cxc-bullet-symbol';
        bulletSpan.setAttribute('style', 'display:inline-flex;align-items:center;justify-content:center;min-width:14px;margin-right:6px;font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;font-weight:bold;line-height:1;user-select:none;');
        bulletSpan.textContent = targetSymbol;
        item.insertBefore(bulletSpan, item.firstChild);

        // Convert to a clean indented <p> so CKEditor ListEditing does not strip the custom symbol
        const p = doc.createElement('p');
        p.className = 'cxc-bullet-line MsoNormal';
        
        // Preserve any inline styles while applying indentation
        let inlineStyle = item.getAttribute('style') || '';
        inlineStyle = inlineStyle
            .replace(/mso-list:[^;]+;?/gi, '')
            .replace(/margin-left:[^;]+;?/gi, '')
            .replace(/text-indent:[^;]+;?/gi, '')
            .replace(/list-style-type:[^;]+;?/gi, '');

        // Clean paragraph margins and indentation without negative text-indent!
        p.setAttribute('style', `margin-left:${indentPx}px;padding-left:4px;text-indent:0;margin-top:0.3em;margin-bottom:0.3em;line-height:1.5;${existingColor ? `color:${existingColor};` : ''}${inlineStyle}`);
        
        // Move children
        while (item.firstChild) {
            p.appendChild(item.firstChild);
        }

        item.replaceWith(p);
    });

    // Unwrap any <ul> or <ol> that now contain our custom bullet paragraphs or other elements
    const uls = Array.from(rootNode.querySelectorAll('ul, ol'));
    uls.forEach(ul => {
        const parent = ul.parentNode;
        if (parent) {
            while (ul.firstChild) {
                parent.insertBefore(ul.firstChild, ul);
            }
            ul.remove();
        }
    });
}

/**
 * Preserves BAB, SUB BAB, SUB SUB BAB and outline numbering hierarchies
 * Prevents CKEditor from stripping them or turning them into generic lists
 */
export function preserveBabAndOutlineStructure(html) {
    if (!html) return html;

    const legalHeadingRegex = /^(BAB\s+[0-9IVXLCDM]+|SUB\s*BAB|PASAL\s+[0-9IVXLCDM]+|BAGIAN\s+[0-9IVXLCDM]+|LAMPIRAN[\s\S]*?)/i;

    // 1. Un-hide conditional comments that hold BAB / outline markers or bullet symbols before DOMParser drops them
    html = html.replace(/<!--\[if\s+!supportLists\]>([\s\S]*?)<!\[endif\]-->/gi, (match, inner) => {
        const text = inner.replace(/<[^>]*>/g, '').trim();
        // If it's a Wingdings / Symbol bullet symbol marker
        if (/font-family:[^;]*(?:wingdings|symbol|webdings)/i.test(inner)) {
            let symbol = '•';
            // Check character code or characters
            for (let i = 0; i < text.length; i++) {
                const code = text.charCodeAt(i) & 0xFF;
                if (code === 0xD8 || /[Ø\u00D8\uF0D8]/.test(text[i])) {
                    symbol = '➢';
                    break;
                } else if (code === 0x76 || /[v\u0076\uF076]/.test(text[i])) {
                    symbol = '❖';
                    break;
                } else if (code === 0xFC || /[ü\u00FC\uF0FC]/.test(text[i])) {
                    symbol = '✔';
                    break;
                } else if (code === 0xA7 || /[§\u00A7\uF0A7]/.test(text[i])) {
                    symbol = '▪';
                    break;
                } else if (code === 0xFE || /[þ\u00FE\uF0FE]/.test(text[i])) {
                    symbol = '☑';
                    break;
                } else if (code === 0xA8 || /[¨\u00A8\uF0A8]/.test(text[i])) {
                    symbol = '☐';
                    break;
                } else if (code === 0xB7 || /[·\u00B7\uF0B7]/.test(text[i])) {
                    symbol = '•';
                    break;
                }
            }
            return `<span class="cxc-bullet-marker" style="margin-right:8px;">${symbol}</span>`;
        }

        // Legal headers like BAB I, PASAL 1, LAMPIRAN
        if (legalHeadingRegex.test(text)) {
            return `<span class="cxc-outline-preserve" style="font-weight:bold;margin-right:8px;">${inner}</span>`;
        }

        // Multilevel list marker (e.g. "a.", "b.", "1.", "1.1", "(a)", "(1)", "•", "-", etc.)
        if (text) {
            return `<span class="cxc-bullet-marker" style="margin-right:8px;">${text}</span>`;
        }

        return match;
    });

    // 2. Parse HTML into DOM to inspect elements
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // 3. Process headings and outline paragraphs
    const blocks = doc.querySelectorAll('p, h1, h2, h3, h4, h5, h6, div.MsoNormal');
    blocks.forEach(block => {
        const className = block.className || '';
        const style = block.getAttribute('style') || '';

        const isHeading = /MsoHeading|MsoTitle|MsoSubtitle/i.test(className) || /^h[1-6]$/i.test(block.tagName);

        const markerSpan = block.querySelector('.cxc-outline-preserve');
        let markerText = '';

        if (markerSpan) {
            markerText = markerSpan.textContent.replace(/\s+/g, ' ').trim();
        } else {
            const firstText = (block.textContent || '').trim();
            const firstMatch = firstText.match(legalHeadingRegex);
            if (firstMatch && firstText.indexOf(firstMatch[0]) === 0) {
                markerText = firstMatch[0];
            }
        }

        const isBabOrOutline = legalHeadingRegex.test(markerText);

        if (isBabOrOutline || isHeading) {
            if (style) {
                const cleanedStyle = style
                    .replace(/mso-list:[^;]+;?/gi, '')
                    .replace(/text-indent:[^;]+;?/gi, '');
                block.setAttribute('style', cleanedStyle);
            }

            block.classList.remove('MsoListParagraph', 'MsoListParagraphCxSpFirst', 'MsoListParagraphCxSpMiddle', 'MsoListParagraphCxSpLast');

            if (markerSpan) {
                const cleanMarker = markerText + (markerText.endsWith('.') ? ' ' : '. ');
                const strong = doc.createElement('strong');
                strong.className = 'cxc-outline-prefix';
                strong.style.fontWeight = 'bold';
                strong.textContent = cleanMarker;
                markerSpan.replaceWith(strong);
            }

            if (/MsoHeading1|MsoTitle/i.test(className) || /^BAB\s+/i.test(markerText)) {
                const h2 = doc.createElement('h2');
                h2.innerHTML = block.innerHTML;
                h2.setAttribute('style', 'font-weight:bold;margin-top:1.2em;margin-bottom:0.6em;color:#1e293b;' + (block.getAttribute('style') || ''));
                block.replaceWith(h2);
            } else if (/MsoHeading2|MsoSubtitle/i.test(className) || /^\d+\.\d+\b/.test(markerText) || /^SUB\s*BAB/i.test(markerText)) {
                const h3 = doc.createElement('h3');
                h3.innerHTML = block.innerHTML;
                h3.setAttribute('style', 'font-weight:bold;margin-top:1em;margin-bottom:0.5em;color:#334155;' + (block.getAttribute('style') || ''));
                block.replaceWith(h3);
            } else if (/MsoHeading3/i.test(className) || /^\d+\.\d+\.\d+\b/.test(markerText) || /^SUB\s*SUB\s*BAB/i.test(markerText)) {
                const h4 = doc.createElement('h4');
                h4.innerHTML = block.innerHTML;
                h4.setAttribute('style', 'font-weight:bold;margin-top:0.8em;margin-bottom:0.4em;color:#475569;' + (block.getAttribute('style') || ''));
                block.replaceWith(h4);
            }
        }
    });

    return doc.body.innerHTML;
}

/**
 * Normalizes Excel and Word tables so borders, backgrounds, and cell styling survive paste
 */
export function normalizeOfficeTables(doc) {
    const tables = doc.querySelectorAll('table');
    tables.forEach(table => {
        table.classList.add('table');
        if (!table.style.borderCollapse) {
            table.style.borderCollapse = 'collapse';
        }
        if (!table.style.width) {
            table.style.width = '100%';
        }

        const cells = table.querySelectorAll('td, th');
        cells.forEach(cell => {
            if (!cell.style.padding) {
                cell.style.padding = '6px 10px';
            }
            if (!cell.style.border) {
                cell.style.border = '1px solid #cbd5e1';
            }
            if (cell.getAttribute('bgcolor') && !cell.style.backgroundColor) {
                cell.style.backgroundColor = cell.getAttribute('bgcolor');
            }
        });
    });
}

/**
 * Normalizes fonts, font-sizes, and paragraph margins from Office
 */
export function normalizeOfficeTypography(doc) {
    const styledElements = doc.querySelectorAll('[style]');
    styledElements.forEach(el => {
        let style = el.getAttribute('style') || '';

        // Convert Mso pt font sizes to clean px or standard values
        style = style.replace(/font-size:\s*([\d\.]+)pt/gi, (m, pt) => {
            const px = Math.round(parseFloat(pt) * 1.3333);
            return `font-size:${px}px`;
        });

        // Convert Mso pt margins and indents to px
        // Note: We ignore negative margins on list paragraphs to prevent items jumping offscreen
        style = style.replace(/(margin-left|margin-right|text-indent):\s*(-?[\d\.]+)pt/gi, (m, prop, pt) => {
            const val = parseFloat(pt);
            if (val <= 0 && prop === 'margin-left') {
                return ''; // remove negative margin-left on list paragraphs
            }
            const px = Math.round(val * 1.3333);
            return `${prop}:${px}px`;
        });

        // Clean out noisy mso-* properties that aren't valid CSS
        style = style.replace(/mso-[^:]+:[^;]+;?/gi, '');

        el.setAttribute('style', style);
    });

    // Replace mso spacerun spans (&nbsp; series)
    const spaceruns = doc.querySelectorAll('span[style*="mso-spacerun"]');
    spaceruns.forEach(span => {
        span.replaceWith(...span.childNodes);
    });

    // Remove empty <o:p></o:p> elements
    const ops = doc.querySelectorAll('o\\:p, op');
    ops.forEach(op => {
        if (!op.textContent.trim()) {
            op.remove();
        } else {
            op.replaceWith(...op.childNodes);
        }
    });
}

/**
 * Full master normalization pipeline for pasted Office content
 */
export function normalizeOfficeHtml(html, rtf = '', text = '') {
    if (!html) return html;

    // 1. Extract embedded RTF images and replace local file:/// URLs
    if (rtf) {
        try {
            const rtfImages = extractImagesFromRtf(rtf);
            if (rtfImages.length > 0) {
                html = replaceLocalImagesWithRtfImages(html, rtfImages);
            }
        } catch (e) {
            console.warn('[OfficePaste] Error extracting RTF images:', e);
        }
    }

    // 2. Parse @list definitions from <style> BEFORE style tags are stripped
    const listDefinitions = parseOfficeListStyles(html);

    // 3. Preserve BAB, SUB BAB, SUB SUB BAB and outline numbering
    html = preserveBabAndOutlineStructure(html);

    // 4. Parse into DOM for detailed structure normalization
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // Remove XML / Mso head blocks that interfere with rendering
    const msoStyles = doc.querySelectorAll('style, xml, link[rel="Edit-Time-Data"], o\\:smarttagtype');
    msoStyles.forEach(s => s.remove());

    // 5. Apply list definitions to list items so custom bullets (✔, ➢, ▪, ❖, •) are preserved
    normalizeOfficeLists(doc.body, listDefinitions);

    // 6. Normalize Wingdings, Symbol, and PUA characters
    normalizeSymbolsAndPunctuation(doc.body);

    // 7. Normalize Excel and Word Tables
    normalizeOfficeTables(doc.body);

    // 8. Normalize typography, font sizes, colors, and styles
    normalizeOfficeTypography(doc.body);

    return doc.body.innerHTML;
}

/**
 * Hooks the Office Paste Normalizer directly into CKEditor 5
 */
export function setupOfficePasteHandler(editor, { onStartProcessing = null, onEndProcessing = null } = {}) {
    if (!editor || !editor.editing || !editor.editing.view) return;

    const viewDocument = editor.editing.view.document;

    // 1. Hook into CKEditor's clipboardInput with HIGH priority
    viewDocument.on('clipboardInput', (evt, data) => {
        const dataTransfer = data.dataTransfer;
        const html = dataTransfer.getData('text/html');
        const rtf = dataTransfer.getData('text/rtf');
        const text = dataTransfer.getData('text/plain');

        if (isMicrosoftOfficeContent(html, rtf, text) || (rtf && rtf.includes('\\pict')) || (html && /file:\/\//.test(html))) {
            const isHeavy = (html && html.length > 30000) || (rtf && rtf.length > 50000) || (rtf && rtf.includes('\\pict'));
            if (isHeavy && typeof onStartProcessing === 'function') {
                onStartProcessing();
            }

            try {
                // If heavy, execute in next animation frame/timeout to let spinner render smoothly
                const runProcessing = () => {
                    try {
                        const cleanedHtml = normalizeOfficeHtml(html, rtf, text);
                        if (cleanedHtml) {
                            const processor = editor.data?.htmlProcessor || editor.data?.processor;
                            if (processor && typeof processor.toView === 'function') {
                                const viewFragment = processor.toView(cleanedHtml);
                                const modelFragment = editor.data.toModel(viewFragment);
                                editor.model.change(writer => {
                                    editor.model.insertContent(modelFragment, editor.model.document.selection);
                                });
                            }
                        }
                    } catch (err) {
                        console.error('[OfficePaste] Error in clipboardInput processing:', err);
                    } finally {
                        if (typeof onEndProcessing === 'function') {
                            onEndProcessing();
                        }
                    }
                };

                if (isHeavy) {
                    setTimeout(runProcessing, 30);
                    evt.stop();
                } else {
                    runProcessing();
                    evt.stop();
                }
            } catch (err) {
                console.error('[OfficePaste] Error initiating clipboardInput processing:', err);
                if (typeof onEndProcessing === 'function') {
                    onEndProcessing();
                }
            }
        }
    }, { priority: 'high' });

    // 2. DOM paste event: If clipboard contains binary image files (e.g. from Word, Excel, or screenshot)
    const editingRoot = editor.editing.view.getDomRoot();
    if (editingRoot) {
        editingRoot.addEventListener('paste', (event) => {
            const clipboardData = event.clipboardData;
            if (!clipboardData) return;

            const items = clipboardData.items;
            const html = clipboardData.getData('text/html');

            if (items && items.length > 0) {
                const imageItem = Array.from(items).find(it => it.kind === 'file' && it.type.startsWith('image/'));
                if (imageItem && (!html || /<img[^>]*src=["'](?:file:\/\/|cid:|mso-file)/i.test(html))) {
                    const file = imageItem.getAsFile();
                    if (file) {
                        if (typeof onStartProcessing === 'function') {
                            onStartProcessing();
                        }
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            try {
                                const base64Url = e.target.result;
                                editor.model.change(writer => {
                                    const imageElement = writer.createElement('imageBlock', {
                                        src: base64Url
                                    });
                                    editor.model.insertContent(imageElement, editor.model.document.selection);
                                });
                            } finally {
                                if (typeof onEndProcessing === 'function') {
                                    setTimeout(onEndProcessing, 50);
                                }
                            }
                        };
                        reader.onerror = () => {
                            if (typeof onEndProcessing === 'function') {
                                onEndProcessing();
                            }
                        };
                        reader.readAsDataURL(file);
                        if (!html || html.replace(/<[^>]*>/g, '').trim().length === 0) {
                            event.preventDefault();
                        }
                    }
                }
            }
        }, { capture: true });
    }
}
