
const fs = require("fs");
const content = fs.readFileSync("Home_backup.vue", "utf8");

const startIdx = content.indexOf("<!-- Manage News View -->");
const substring = content.substring(startIdx - 500, startIdx + 500);
console.log(substring);

