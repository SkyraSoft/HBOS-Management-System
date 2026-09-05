import fs from 'fs';
import path from 'path';

const htmlPath = 'c:/xampp/htdocs/HBOS/legacy_html/Dashboard.html';
const cssPath = 'c:/xampp/htdocs/HBOS/src/assets/main.css';

const htmlContent = fs.readFileSync(htmlPath, 'utf-8');

// Extract everything inside <style> from the HTML
const styleMatch = htmlContent.match(/<style>([\s\S]*?)<\/style>/);

if (styleMatch) {
    let fullCss = styleMatch[1];
    
    // We only want the layout CSS, not the KPI cards or Charts which are already in scoped components
    // However, since it's global layout, it's safer to just append the whole Dashboard CSS to main.css
    // EXCEPT the :root and resets which are already in main.css.
    
    // Let's strip :root and body resets to avoid duplication
    const rootRegex = /:root\s*{[\s\S]*?}/g;
    const resetRegex = /\*\s*{[\s\S]*?}/g;
    const htmlResetRegex = /html\s*{[\s\S]*?}/g;
    const bodyResetRegex = /body\s*{[\s\S]*?}/g;
    const btnResetRegex = /button,\s*input,\s*select\s*{[\s\S]*?}/g;
    
    let cleanCss = fullCss
        .replace(rootRegex, '')
        .replace(resetRegex, '')
        .replace(htmlResetRegex, '')
        .replace(bodyResetRegex, '')
        .replace(btnResetRegex, '');
        
    // Append to main.css
    fs.appendFileSync(cssPath, '\n/* INJECTED GLOBAL LAYOUT CSS */\n' + cleanCss);
    console.log("Global layout CSS injected into main.css successfully!");
} else {
    console.log("No style tag found in Dashboard.html");
}
