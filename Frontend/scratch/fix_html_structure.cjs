const fs = require('fs');

let html = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', 'utf8');

// The file currently has:
//             <section class="page simple-page" id="productPanel">
// </main>
// </div>
//
//
//         <div class="panel-header">
// ...
//         <div class="panel-footer">
// ...
//         </div>
//
//     </div>
//
//
//     <div class="toast" id="toast">

// I'll extract everything from `<section class="page simple-page" id="productPanel">`
// up to that `</div>` before `<div class="toast" id="toast">`
// and rewrap it nicely.

// First, fix the malformed section.
html = html.replace(
    /<\/main>\s*<\/div>\s*<div class="panel-header">/,
    `<div class="panel-header">`
);

// Now, the `</main></div>` is missing from the end.
// We have `    </div>\n\n\n    <div class="toast" id="toast">`
// This `</div>` was the end of the `productPanel`. We need to replace it with `</section></main></div>`.
// Wait, my previous replacement might have left it as `</div>`
html = html.replace(
    /<\/div>(\s*)<div class="toast" id="toast">/,
    `</div>\n    </section>\n</main>\n</div>$1<div class="toast" id="toast">`
);

// We need to change the panel-header to page-header and wrap the form in a card
html = html.replace(
    /<div class="panel-header">[\s\S]*?<\/div>/,
    `<div class="page-header">
                <div>
                    <h2>Add New Product</h2>
                    <p>Enter the details of the new product.</p>
                </div>
                <button class="secondary-btn" id="backProductsFromAdd">
                    ← Products
                </button>
            </div>
            <div class="simple-card" style="margin: 0 auto; max-width: 800px; padding: 30px;">`
);

// Replace the panel-footer and close the card
html = html.replace(
    /<div class="panel-footer">([\s\S]*?)<\/div>\s*<\/div>\s*<\/section>/,
    `<div class="panel-footer" style="margin-top: 30px; border-top: 1px solid #d9dee7; padding-top: 20px;">$1</div>
            </div>
        </section>`
);

// Ensure the ID of backProductsFromAdd is what JS expects.
// My previous script replaced closePanel with backProductsFromAdd. Let's make sure `backProductsFromAdd` is defined in JS.
// Check if `const backProductsFromAdd` is there. It is: `const backProductsFromAdd = document.getElementById("backProductsFromAdd");`

fs.writeFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', html, 'utf8');
console.log('Done fixing HTML structure');
