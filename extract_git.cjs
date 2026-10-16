
const fs = require("fs");
const path = require("path");
const zlib = require("zlib");

function readGitObject(hash) {
    const dir = hash.substring(0, 2);
    const file = hash.substring(2);
    const objPath = path.join(".git", "objects", dir, file);
    if (!fs.existsSync(objPath)) return null;
    const compressed = fs.readFileSync(objPath);
    return zlib.inflateSync(compressed);
}

const headStr = fs.readFileSync(".git/HEAD", "utf8").trim();
let commitHash = "";
if (headStr.startsWith("ref: ")) {
    const refPath = headStr.substring(5);
    commitHash = fs.readFileSync(path.join(".git", refPath), "utf8").trim();
} else {
    commitHash = headStr;
}

const commitObj = readGitObject(commitHash);
const commitStr = commitObj.toString("utf8");
const treeHash = commitStr.match(/tree ([0-9a-f]{40})/)[1];

function parseTree(hash) {
    const treeObj = readGitObject(hash);
    let entries = [];
    let i = 0;
    while (i < treeObj.length) {
        const nullIdx = treeObj.indexOf(0, i);
        if (nullIdx === -1) break;
        const modeAndName = treeObj.slice(i, nullIdx).toString("utf8").split(" ");
        const mode = modeAndName[0];
        const name = modeAndName[1];
        const sha = treeObj.slice(nullIdx + 1, nullIdx + 21).toString("hex");
        entries.push({mode, name, sha});
        i = nullIdx + 21;
    }
    return entries;
}

function findFileInTree(treeHash, targetPath) {
    const parts = targetPath.split("/");
    let currentTree = treeHash;
    for (let i = 0; i < parts.length; i++) {
        const entries = parseTree(currentTree);
        const entry = entries.find(e => e.name === parts[i]);
        if (!entry) return null;
        if (i === parts.length - 1) return entry.sha;
        currentTree = entry.sha;
    }
    return null;
}

const fileHash = findFileInTree(treeHash, "resources/js/components/Home.vue");
if (fileHash) {
    const fileObj = readGitObject(fileHash);
    const contentIdx = fileObj.indexOf(0) + 1;
    const fileContent = fileObj.slice(contentIdx).toString("utf8");
    fs.writeFileSync("Home_backup.vue", fileContent);
    console.log("Successfully extracted Home.vue from HEAD to Home_backup.vue!");
} else {
    console.log("Could not find file in HEAD.");
}

