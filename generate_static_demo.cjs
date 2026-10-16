const fs = require('fs');
const path = require('path');

function generateHtml() {
    const manifestPath = path.join(__dirname, 'public', 'build', 'manifest.json');
    if (!fs.existsSync(manifestPath)) {
        console.error('manifest.json not found!');
        return;
    }

    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    const entry = manifest['resources/js/app.js'];
    const cssList = entry.css || [];
    const jsFile = entry.file;

    const cssLinks = cssList.map(css => `    <link rel="stylesheet" href="./build/${css}">`).join('\n');

    const htmlContent = `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PortalCXC - Live Demo</title>
    <link rel="icon" id="app-favicon" href="./favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
${cssLinks}
</head>
<body class="bg-gray-100">
    <div id="app"></div>
    <script type="module" src="./build/${jsFile}"></script>
</body>
</html>
`;

    // Fix absolute /build/assets/ font paths inside built CSS files so fonts resolve on GitHub Pages subpaths
    const buildAssetsDir = path.join(__dirname, 'public', 'build', 'assets');
    if (fs.existsSync(buildAssetsDir)) {
        const files = fs.readdirSync(buildAssetsDir);
        files.filter(f => f.endsWith('.css')).forEach(cssFile => {
            const fullPath = path.join(buildAssetsDir, cssFile);
            let cssContent = fs.readFileSync(fullPath, 'utf8');
            if (cssContent.includes('/build/assets/')) {
                cssContent = cssContent.replaceAll('/build/assets/', './');
                fs.writeFileSync(fullPath, cssContent, 'utf8');
                console.log(`✓ Patched font paths in ${cssFile}`);
            }
        });
    }

    const targetPath = path.join(__dirname, 'public', 'index.html');
    fs.writeFileSync(targetPath, htmlContent, 'utf8');
    console.log('✓ Successfully generated public/index.html for GitHub Pages demo!');
}

generateHtml();
