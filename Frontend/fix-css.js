import fs from 'fs';
import path from 'path';
import * as cheerio from 'cheerio';

const LEGACY_DIR = 'c:/xampp/htdocs/HBOS/legacy_html';
const VIEWS_DIR = 'c:/xampp/htdocs/HBOS/src/views';

const files = fs.readdirSync(LEGACY_DIR).filter(f => f.endsWith('.html'));

files.forEach(file => {
  const filePath = path.join(LEGACY_DIR, file);
  const htmlContent = fs.readFileSync(filePath, 'utf-8');
  
  const $ = cheerio.load(htmlContent);
  
  // Extract all style blocks from head
  let cssContent = '';
  $('style').each((i, el) => {
    cssContent += $(el).html() + '\n';
  });
  
  // Generate Component Name
  const baseName = file.replace('.html', '').replace(/[^a-zA-Z0-9]/g, '');
  const componentName = baseName + 'View';
  const vueFileName = componentName + '.vue';
  
  const vuePath = path.join(VIEWS_DIR, vueFileName);
  
  if (fs.existsSync(vuePath) && cssContent.trim() !== '') {
    let vueContent = fs.readFileSync(vuePath, 'utf-8');
    
    // Replace <style scoped> block
    // We replace everything between <style scoped> and </style>
    const styleRegex = /<style scoped>[\s\S]*?<\/style>/;
    const newStyleBlock = `<style scoped>\n${cssContent}\n</style>`;
    
    if (vueContent.match(styleRegex)) {
      vueContent = vueContent.replace(styleRegex, newStyleBlock);
    } else {
      // If it doesn't exist, just append it
      vueContent += `\n${newStyleBlock}\n`;
    }
    
    fs.writeFileSync(vuePath, vueContent);
    console.log(`Injected CSS into ${vueFileName}`);
  }
});
