const fs = require('fs');
let file = fs.readFileSync('src/views/PosSaleView.vue', 'utf8');

// 1. Make product grid columns fewer (larger cards) and gap slightly larger
file = file.replace(
    `.product-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; align-items: stretch; }`,
    `.product-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; align-items: stretch; }`
);

// 2. Adjust product card height and padding
file = file.replace(
    `.product-card { min-width: 0; height: 356px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 15px; padding: 12px 10px 11px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`,
    `.product-card { min-width: 0; min-height: 380px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 15px; padding: 16px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`
);

// 3. Make product image taller
file = file.replace(
    `.product-image-wrap { position: relative; width: 100%; height: 145px; flex: 0 0 145px; border-radius: 11px; overflow: hidden; background: #e8ebef; margin-bottom: 9px; }`,
    `.product-image-wrap { position: relative; width: 100%; height: 190px; flex: 0 0 190px; border-radius: 11px; overflow: hidden; background: #e8ebef; margin-bottom: 12px; }`
);

// 4. Increase product name font size
file = file.replace(
    `.product-name { font-size: 15px; font-weight: 700; color: #000; margin: 0 0 4px; line-height: 1.35; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }`,
    `.product-name { font-size: 17px; font-weight: 700; color: #000; margin: 0 0 6px; line-height: 1.4; height: 46px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }`
);

file = file.replace(
    `.product-sku { font-size: 12px; color: #787e87; margin-bottom: auto; height: 25px; line-height: 25px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }`,
    `.product-sku { font-size: 13px; color: #787e87; margin-bottom: auto; height: 25px; line-height: 25px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }`
);

file = file.replace(
    `.product-price { font-size: 21px; font-weight: 800; color: #000; margin-bottom: 8px; line-height: 1; }`,
    `.product-price { font-size: 23px; font-weight: 800; color: #000; margin-bottom: 12px; line-height: 1; }`
);

// 5. Shrink cart panel width
file = file.replace(
    `.cart-panel { width: 34%; min-width: 320px; flex: 0 0 34%; background: #fff; border-left: 1px solid #d3d7de; display: flex; flex-direction: column; overflow: hidden; }`,
    `.cart-panel { width: 28%; min-width: 300px; flex: 0 0 28%; background: #fff; border-left: 1px solid #d3d7de; display: flex; flex-direction: column; overflow: hidden; }`
);

// 6. Compact cart panel padding
file = file.replace(
    `.cart-top { padding: 16px 20px 14px; border-bottom: 1px solid #e2e6eb; background: #fff; flex: 0 0 auto; }`,
    `.cart-top { padding: 12px 16px; border-bottom: 1px solid #e2e6eb; background: #fff; flex: 0 0 auto; }`
);

file = file.replace(
    `.cart-item { padding: 15px 20px; border-bottom: 1px solid #eaedf1; }`,
    `.cart-item { padding: 12px 16px; border-bottom: 1px solid #eaedf1; }`
);

file = file.replace(
    `.cart-summary { flex: 0 0 auto; background: #f4f6f9; border-top: 1px solid #e1e5eb; padding: 18px 20px; }`,
    `.cart-summary { flex: 0 0 auto; background: #f4f6f9; border-top: 1px solid #e1e5eb; padding: 14px 16px; }`
);

// Adjust total value text size so it fits well in compact mode
file = file.replace(
    `.total-value { font-size: 30px; font-weight: 900; color: #2447c6; }`,
    `.total-value { font-size: 24px; font-weight: 900; color: #2447c6; }`
);

file = file.replace(
    `.payment-methods { display: flex; gap: 8px; margin-bottom: 18px; }`,
    `.payment-methods { display: flex; gap: 6px; margin-bottom: 14px; }`
);

fs.writeFileSync('src/views/PosSaleView.vue', file);
console.log('PosSaleView CSS updated');
