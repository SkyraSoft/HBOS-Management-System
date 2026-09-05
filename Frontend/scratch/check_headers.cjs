const fs = require('fs');
let txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// I need to find the `products` page breadcrumb and the `invoice` page breadcrumb.
// Wait, the products page had:
// <div class="top-left">
//     <div class="breadcrumb">
//         <span>Inventory</span> <span class="sep">&gt;</span> <span>Products</span>
//     </div>
//     <h2>Products</h2>
//     <p>Manage your products, pricing, categories and stock levels.</p>
// </div>

// If my previous regex replaced the products page one with the Invoice one, I need to fix it!
// Let's dump the HTML for both headers.

console.log("=== HEADER 1 ===");
let h1 = txt.indexOf('class="page products-page"');
console.log(txt.substring(h1, h1 + 500));

console.log("=== HEADER 2 ===");
let h2 = txt.indexOf('class="page invoice-page"');
console.log(txt.substring(h2, h2 + 500));
