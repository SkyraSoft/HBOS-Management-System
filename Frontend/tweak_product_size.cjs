const fs = require('fs');

let css = fs.readFileSync('src/views/PosSaleView.vue', 'utf8');

// 1. Grid
css = css.replace(
    `.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; align-items: stretch; }`,
    `.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; align-items: stretch; }`
);

// 2. Card
css = css.replace(
    `.product-card { min-width: 0; min-height: 400px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 15px; padding: 18px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`,
    `.product-card { min-width: 0; min-height: 350px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 13px; padding: 14px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`
);

// 3. Image Wrap
css = css.replace(
    `.product-image-wrap { position: relative; width: 100%; height: 210px; flex: 0 0 210px; border-radius: 12px; overflow: hidden; background: #e8ebef; margin-bottom: 14px; }`,
    `.product-image-wrap { position: relative; width: 100%; height: 160px; flex: 0 0 160px; border-radius: 10px; overflow: hidden; background: #e8ebef; margin-bottom: 12px; }`
);

fs.writeFileSync('src/views/PosSaleView.vue', css);
console.log('Done tweaking product card size.');
