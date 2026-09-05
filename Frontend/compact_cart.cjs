const fs = require('fs');

let css = fs.readFileSync('src/views/PosSaleView.vue', 'utf8');

// 1. cart panel width
css = css.replace(
    `.cart-panel { width: 28%; min-width: 300px; flex: 0 0 28%; background: #fff; border-left: 1px solid #d3d7de; display: flex; flex-direction: column; overflow: hidden; }`,
    `.cart-panel { width: 25%; min-width: 280px; flex: 0 0 25%; background: #fff; border-left: 1px solid #d3d7de; display: flex; flex-direction: column; overflow: hidden; }`
);

// 2. padding
css = css.replace(
    `.cart-top { padding: 12px 16px; border-bottom: 1px solid #e2e6eb; background: #fff; flex: 0 0 auto; }`,
    `.cart-top { padding: 10px 12px; border-bottom: 1px solid #e2e6eb; background: #fff; flex: 0 0 auto; }`
);
css = css.replace(
    `.cart-item { padding: 12px 16px; border-bottom: 1px solid #eaedf1; }`,
    `.cart-item { padding: 10px 12px; border-bottom: 1px solid #eaedf1; }`
);
css = css.replace(
    `.cart-summary { flex: 0 0 auto; background: #f4f6f9; border-top: 1px solid #e1e5eb; padding: 14px 16px; }`,
    `.cart-summary { flex: 0 0 auto; background: #f4f6f9; border-top: 1px solid #e1e5eb; padding: 12px 12px; }`
);

// 3. customer select
css = css.replace(
    `.customer-select { width: 100%; height: 48px; border: 2px solid #e1e4e9; border-radius: 9px; padding: 0 15px; font-size: 15px; color: #171a24; font-weight: 600; background-color: #fff; cursor: pointer; appearance: none;`,
    `.customer-select { width: 100%; height: 42px; border: 2px solid #e1e4e9; border-radius: 9px; padding: 0 12px; font-size: 14px; color: #171a24; font-weight: 600; background-color: #fff; cursor: pointer; appearance: none;`
);

// 4. item text
css = css.replace(
    `.cart-item-name { font-size: 15px; font-weight: 700; color: #080808; margin-bottom: 4px; }`,
    `.cart-item-name { font-size: 14px; font-weight: 700; color: #080808; margin-bottom: 2px; }`
);
css = css.replace(
    `.cart-item-price { font-size: 16px; font-weight: 700; color: #080808; }`,
    `.cart-item-price { font-size: 14px; font-weight: 700; color: #080808; }`
);

// 5. quantity control
css = css.replace(
    `.quantity-control { display: inline-flex; align-items: center; border: 1px solid #d6dae0; border-radius: 7px; height: 38px; overflow: hidden; }`,
    `.quantity-control { display: inline-flex; align-items: center; border: 1px solid #d6dae0; border-radius: 6px; height: 32px; overflow: hidden; }`
);
css = css.replace(
    `.qty-value { width: 44px; text-align: center; font-size: 15px; font-weight: 600; color: #000; border-left: 1px solid #d6dae0; border-right: 1px solid #d6dae0; height: 100%; display: flex; align-items: center; justify-content: center; background: #fff; }`,
    `.qty-value { width: 36px; text-align: center; font-size: 14px; font-weight: 600; color: #000; border-left: 1px solid #d6dae0; border-right: 1px solid #d6dae0; height: 100%; display: flex; align-items: center; justify-content: center; background: #fff; }`
);

// 6. Summary row
css = css.replace(
    `.summary-row { display: flex; justify-content: space-between; font-size: 15px; color: #444a53; font-weight: 600; margin-bottom: 12px; }`,
    `.summary-row { display: flex; justify-content: space-between; font-size: 14px; color: #444a53; font-weight: 600; margin-bottom: 8px; }`
);
css = css.replace(
    `.discount-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #c4c9d1; }`,
    `.discount-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #c4c9d1; }`
);
css = css.replace(
    `.discount-input { width: 130px; height: 36px; border: 1px solid #c8ccd3; border-radius: 6px; padding: 0 10px; font-size: 14px; background: #fff; }`,
    `.discount-input { width: 110px; height: 32px; border: 1px solid #c8ccd3; border-radius: 6px; padding: 0 8px; font-size: 13px; background: #fff; }`
);
css = css.replace(
    `.total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }`,
    `.total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }`
);
css = css.replace(
    `.total-label { font-size: 20px; font-weight: 800; color: #080808; }`,
    `.total-label { font-size: 18px; font-weight: 800; color: #080808; }`
);
css = css.replace(
    `.total-value { font-size: 24px; font-weight: 900; color: #2447c6; }`,
    `.total-value { font-size: 22px; font-weight: 900; color: #2447c6; }`
);

// 7. Payment methods
css = css.replace(
    `.payment-btn { flex: 1; height: 65px; border: 2px solid #dce0e6; border-radius: 10px; background: #fff; color: #444a53; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; transition: all .15s ease; }`,
    `.payment-btn { flex: 1; height: 55px; border: 2px solid #dce0e6; border-radius: 8px; background: #fff; color: #444a53; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; transition: all .15s ease; }`
);
css = css.replace(
    `.payment-btn i { font-size: 20px; }`,
    `.payment-btn i { font-size: 18px; }`
);
css = css.replace(
    `.complete-sale { width: 100%; height: 62px; border: none; border-radius: 12px; background: #2447c6; color: #fff; font-size: 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s ease; }`,
    `.complete-sale { width: 100%; height: 54px; border: none; border-radius: 10px; background: #2447c6; color: #fff; font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s ease; }`
);

// product cards a bit larger by reducing minimum from 3 to 2 columns on very large screens or relying on auto-fit? 
// 3 columns is already quite large given the new 25% cart panel. Let's make it minmax(240px, 1fr) with auto-fill so it always optimizes size.
css = css.replace(
    `.product-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; align-items: stretch; }`,
    `.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; align-items: stretch; }`
);
css = css.replace(
    `.product-card { min-width: 0; min-height: 380px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 15px; padding: 16px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`,
    `.product-card { min-width: 0; min-height: 400px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 15px; padding: 18px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`
);
css = css.replace(
    `.product-image-wrap { position: relative; width: 100%; height: 190px; flex: 0 0 190px; border-radius: 11px; overflow: hidden; background: #e8ebef; margin-bottom: 12px; }`,
    `.product-image-wrap { position: relative; width: 100%; height: 210px; flex: 0 0 210px; border-radius: 12px; overflow: hidden; background: #e8ebef; margin-bottom: 14px; }`
);

fs.writeFileSync('src/views/PosSaleView.vue', css);
console.log('Done compacting cart and enlarging products.');
