const puppeteer = require('../Frontend/node_modules/puppeteer');
const fs = require('fs');
const path = require('path');

async function testWebp() {
  const browser = await puppeteer.launch({ headless: 'new' });
  const page = await browser.newPage();
  await page.setViewport({ width: 400, height: 400, deviceScaleFactor: 1 });
  
  const svgContent = fs.readFileSync(path.join(__dirname, '../Frontend/public/demo/products/prod_01.svg'), 'utf8');
  await page.setContent(`<!DOCTYPE html><html><body style="margin:0;padding:0;display:flex;align-items:center;justify-content:center;width:400px;height:400px;background:#ffffff;">${svgContent}</body></html>`);
  
  const buffer = await page.screenshot({ type: 'webp', quality: 85 });
  fs.writeFileSync(path.join(__dirname, '../scratch_test_prod_01.webp'), buffer);
  console.log('WebP successfully created! Size:', buffer.length, 'bytes (', (buffer.length / 1024).toFixed(2), 'KB)');
  await browser.close();
}

testWebp().catch(console.error);

