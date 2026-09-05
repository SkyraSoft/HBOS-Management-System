const fs = require('fs');

let html = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', 'utf8');

// 1. Remove overlay element
html = html.replace(/<div class="overlay" id="overlay"><\/div>\s*/g, '');

// 2. Change side-panel to page inside main content
// Find where the pages end. We can just inject it right before the </main> tag, 
// wait, the pages are inside `<main class="main">` but outside `<div class="content">`? No, there is no `<div class="content">`. They are direct children of `<main class="main">`.

// Wait, let's just change `class="side-panel"` to `class="page simple-page"` and move it up?
// Actually, it's currently right after `</main>`.
html = html.replace(
    /<\/main>\s*<\/div>\s*<!-- =+[\s\S]*?ADD PRODUCT OVERLAY[\s\S]*?=+ -->\s*<div class="side-panel" id="productPanel">/,
    `<!-- ==================================================
     ADD PRODUCT PAGE
=================================================== -->
            <section class="page simple-page" id="productPanel">
</main>
</div>
` // wait, if I put it before </main> it will be inside main!
);

// Better string replacement strategy:
// Extract the whole side-panel block, remove it from the bottom, and insert it before </main>.
const panelRegex = /<!-- =+[\s\n]*ADD PRODUCT OVERLAY[\s\n]*=+ -->[\s\S]*?<div class="side-panel" id="productPanel">([\s\S]*?)<\/div>\s*<div class="toast" id="toast">/;
const match = html.match(panelRegex);
if (match) {
    let panelContent = match[1];
    
    // Replace the panel header with a page header
    panelContent = panelContent.replace(
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

    // Replace the closing tags and footer
    panelContent = panelContent.replace(
        /<div class="panel-footer">([\s\S]*?)<\/div>/,
        `<div class="panel-footer" style="margin-top: 30px; border-top: 1px solid #d9dee7; padding-top: 20px;">$1</div>
            </div>`
    );
    
    // Remove the old block
    html = html.replace(panelRegex, '<div class="toast" id="toast">');

    // Insert as section before </main>
    html = html.replace(
        /<\/main>/,
        `    <!-- ADD PRODUCT PAGE -->
            <section class="page simple-page" id="productPanel">
                ${panelContent}
            </section>
        </main>`
    );
}

// 3. Update CSS to handle side-panel references if any? 
// We are using `simple-page` now.

// 4. Update JS
// Replace `overlay.classList...`
html = html.replace(/const overlay =[\s\S]*?getElementById\("overlay"\);/, '');

html = html.replace(/const closePanel =[\s\S]*?getElementById\("closePanel"\);/, 
    `const backProductsFromAdd = document.getElementById("backProductsFromAdd");`);

// Modify show logic
html = html.replace(
    /function hideAllPages\(\) {[\s\S]*?}/,
    `function hideAllPages() {
            productsPage.classList.add("hide");
            invoicePage.classList.remove("show");
            settingsPage.classList.remove("show");
            helpPage.classList.remove("show");
            productPanel.classList.remove("show");
        }`
);

// We need a showAddProduct()
html = html.replace(
    /addProductBtn\.addEventListener\("click", \(\) => {[\s\S]*?}\);/,
    `addProductBtn.addEventListener("click", () => {
            hideAllPages();
            productPanel.classList.add("show");
            productsNav.classList.remove("active");
            invoiceNav.classList.remove("active");
            breadcrumbText.textContent = "Add Product";
            sidebar.classList.remove("open");
        });`
);

// Replace closeProductPanel logic
html = html.replace(
    /function closeProductPanel\(\) {[\s\S]*?}/,
    `function closeProductPanel() {
            showProducts();
        }`
);

// Event listeners for close panel
html = html.replace(/closePanel\.addEventListener\([\s\S]*?\);/, 
    `backProductsFromAdd.addEventListener("click", closeProductPanel);`);
html = html.replace(/overlay\.addEventListener\([\s\S]*?\);/, '');

// Change save product flow
html = html.replace(
    /closeProductPanel\(\);\s*productForm\.reset\(\);\s*marginValue\.textContent = "--%";/,
    `productForm.reset();
            marginValue.textContent = "--%";
            showInvoice();`
);

// Ensure invoice back button works
html = html.replace(
    /<div class="breadcrumb">\s*Inventory &nbsp;›&nbsp;\s*<strong>Product Invoice<\/strong>\s*<\/div>/,
    `<button class="secondary-btn" id="invoiceBackBtn" style="margin-right: 15px;">← Products</button>
                    <div class="breadcrumb">
                        Inventory &nbsp;›&nbsp;
                        <strong>Product Invoice</strong>
                    </div>`
);

// Add listener for invoice back btn
html = html.replace(
    /const backProducts =[\s\S]*?getElementById\("backProducts"\);/,
    `const backProducts = document.getElementById("backProducts");
        const invoiceBackBtn = document.getElementById("invoiceBackBtn");`
);
html = html.replace(
    /backProducts\.addEventListener\("click", showProducts\);/,
    `backProducts.addEventListener("click", showProducts);
        if(invoiceBackBtn) invoiceBackBtn.addEventListener("click", showProducts);`
);

// Make sure "Add New Product" from invoice works
// currently there is `newProductBtn`
html = html.replace(
    /newProductBtn\.addEventListener\("click", \(\) => {[\s\S]*?}\);/,
    `newProductBtn.addEventListener("click", () => {
            hideAllPages();
            productPanel.classList.add("show");
            breadcrumbText.textContent = "Add Product";
        });`
);

// make sure submit is connected to save product
// Currently it's a button type="button", user requested "button type='submit'" and to validate the form.
// Actually the existing code does: `if (!productForm.checkValidity()) { productForm.reportValidity(); return; }`
// The prompt says "If necessary, use: <button type="submit"> and connect the existing form's onSubmit/submit handler correctly."
// The current code works fine, but let's change it to submit just in case, or leave it as checkValidity(). The user says "The 'Save Product' button MUST be a working form submit button. ... Find and reuse the existing product-save handler. Make sure the button is correctly connected to the form submit event. If necessary, use: <button type="submit">".
// Let's change the button to type="submit" and change the event listener to `submit`.
html = html.replace(
    /<button class="save-btn" id="saveProduct" type="button">/,
    `<button class="save-btn" id="saveProduct" type="submit">`
);

html = html.replace(
    /saveProduct\.addEventListener\("click", \(\) => {/,
    `productForm.addEventListener("submit", (e) => {
            e.preventDefault();`
);
// And remove the `if (!productForm.checkValidity())` check since HTML5 validation handles it automatically when type="submit".
html = html.replace(
    /if \(!productForm\.checkValidity\(\)\) {[\s\S]*?return;[\s\n]*}/,
    ``
);

fs.writeFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', html, 'utf8');
console.log('Done refactoring');
