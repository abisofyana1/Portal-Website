import fs from 'fs';

const html = fs.readFileSync('storage/logs/latest_content.html', 'utf8');
const listMatches = [...html.matchAll(/mso-list:([^;"']+)/gi)].map(m => m[1].trim());
const uniqueLists = [...new Set(listMatches)];
console.log('Lists in saved content:', uniqueLists);

// Search for any bullet symbols or wingdings in content
console.log('Has bullet spans:', html.includes('cxc-bullet'));
