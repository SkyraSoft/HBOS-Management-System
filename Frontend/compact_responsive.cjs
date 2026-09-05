const fs = require('fs');
let css = fs.readFileSync('src/views/PosSaleView.vue', 'utf8');

css = css.replace(
    `.cart-panel { width: 38%; flex: 0 0 38%; }`,
    `.cart-panel { width: 32%; flex: 0 0 32%; }`
);

css = css.replace(
    `.cart-panel { width: 40%; flex: 0 0 40%; }`,
    `.cart-panel { width: 35%; flex: 0 0 35%; }`
);

fs.writeFileSync('src/views/PosSaleView.vue', css);
console.log('Done compacting responsive cart panel');
