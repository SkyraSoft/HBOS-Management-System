const fs = require('fs');

let css = fs.readFileSync('src/views/PosSaleView.vue', 'utf8');

// 1. Grid template columns (make cards narrower) and gap (reduce horizontal space)
css = css.replace(
    `.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; align-items: stretch; }`,
    `.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px 10px; align-items: stretch; }`
);

// 2. Reduce horizontal padding on the panel itself
css = css.replace(
    `.products-panel { flex: 1; min-width: 0; display: flex; flex-direction: column; padding: 16px 15px; overflow-y: auto; overflow-x: hidden; }`,
    `.products-panel { flex: 1; min-width: 0; display: flex; flex-direction: column; padding: 16px 8px; overflow-y: auto; overflow-x: hidden; }`
);

// 3. Keep height same (min-height: 350px) as requested: "Keep the current height". 
// But wait! If the card gets much narrower (180px wide) and is 350px tall, it might look like a very tall rectangle.
// The user said: "Keep the current height, layout, design, and alignment." 
// I will keep the height at 350px.
// But I'll slightly reduce the card's inner padding horizontally so contents don't squeeze too much.
css = css.replace(
    `.product-card { min-width: 0; min-height: 350px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 13px; padding: 14px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`,
    `.product-card { min-width: 0; min-height: 350px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 13px; padding: 14px 12px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }`
);

fs.writeFileSync('src/views/PosSaleView.vue', css);
console.log('Done tweaking product card width and horizontal spacing.');
