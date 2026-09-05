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
  
  // Find the actual main content area
  let contentHtml = '';
  const mainContent = $('.main-content');
  const sectionPage = $('section.page');
  const loginWrapper = $('.login-wrapper');
  
  if (loginWrapper.length > 0) {
    contentHtml = loginWrapper.parent().html();
  } else if (mainContent.length > 0) {
    // some files have .main-content
    contentHtml = mainContent.html();
  } else if (sectionPage.length > 0) {
    // Khata, Expenses, Reports, etc. use <section class="page">
    contentHtml = $.html(sectionPage);
  } else {
    // fallback
    const mainTag = $('main');
    if (mainTag.length > 0) {
        // Strip header and sidebar if they exist inside main (which they shouldn't)
        mainTag.find('header.topbar').remove();
        mainTag.find('aside.sidebar').remove();
        contentHtml = mainTag.html();
    } else {
        contentHtml = '<div>Unable to extract content</div>';
    }
  }
  
  const baseName = file.replace('.html', '').replace(/[^a-zA-Z0-9]/g, '');
  const componentName = baseName + 'View';
  const vueFileName = componentName + '.vue';
  const vuePath = path.join(VIEWS_DIR, vueFileName);
  
  if (fs.existsSync(vuePath) && contentHtml) {
    let vueContent = fs.readFileSync(vuePath, 'utf-8');
    
    // Replace everything inside <template> ... </template>
    // Note: Since vue tags can span multiple lines, we use regex
    const templateRegex = /<template>[\s\S]*?<\/template>/;
    const newTemplate = `<template>\n<div class="${baseName.toLowerCase()}-page-wrapper">\n${contentHtml}\n</div>\n</template>`;
    
    if (vueContent.match(templateRegex)) {
        vueContent = vueContent.replace(templateRegex, newTemplate);
        fs.writeFileSync(vuePath, vueContent);
        console.log(`Cleaned up template for ${vueFileName}`);
    }
  }
});
