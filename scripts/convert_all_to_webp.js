const puppeteer = require('../Frontend/node_modules/puppeteer');
const fs = require('fs');
const path = require('path');

const brandsDir = path.join(__dirname, '../Frontend/public/demo/brands');
const catsDir = path.join(__dirname, '../Frontend/public/demo/categories');
const prodsDir = path.join(__dirname, '../Frontend/public/demo/products');

async function convertAll() {
  console.log('Launching Puppeteer for WebP conversion...');
  const browser = await puppeteer.launch({ headless: 'new' });
  const page = await browser.newPage();

  const convertFolder = async (folder, width, height) => {
    const files = fs.readdirSync(folder).filter(f => f.endsWith('.svg'));
    console.log(`Converting ${files.length} SVGs in ${path.basename(folder)}...`);
    await page.setViewport({ width, height, deviceScaleFactor: 1 });

    for (const file of files) {
      const svgPath = path.join(folder, file);
      const webpPath = path.join(folder, file.replace('.svg', '.webp'));
      const svgContent = fs.readFileSync(svgPath, 'utf8');

      await page.setContent(`<!DOCTYPE html><html><body style="margin:0;padding:0;display:flex;align-items:center;justify-content:center;width:${width}px;height:${height}px;background:transparent;overflow:hidden;">${svgContent}</body></html>`);

      const buffer = await page.screenshot({
        type: 'webp',
        quality: 90,
        omitBackground: true
      });

      fs.writeFileSync(webpPath, buffer);
      const kb = (buffer.length / 1024).toFixed(2);
      if (buffer.length > 100 * 1024) {
        console.warn(`⚠️ Warning: ${path.basename(webpPath)} is ${kb} KB (> 100KB)!`);
      } else {
        console.log(`  ✓ ${path.basename(webpPath)}: ${kb} KB`);
      }
    }
  };

  // Convert Brands (520x400)
  await convertFolder(brandsDir, 520, 400);

  // Convert Categories (320x320)
  await convertFolder(catsDir, 320, 320);

  // Convert Products (480x480)
  await convertFolder(prodsDir, 480, 480);

  await browser.close();
  console.log('\nAll 56 visuals converted to optimized WebP format successfully!');
}

convertAll().catch(err => {
  console.error('Conversion failed:', err);
  process.exit(1);
});

