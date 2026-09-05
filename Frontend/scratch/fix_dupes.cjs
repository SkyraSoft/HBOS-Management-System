const fs = require('fs');
let html = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', 'utf8');

let lines = html.split('\n');

let firstIdx = -1;
let secondIdx = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('productsNav.addEventListener("click", showProducts);')) {
        if (firstIdx === -1) firstIdx = i;
    }
    if (lines[i].includes('if(invoiceBackBtn) invoiceBackBtn.addEventListener("click", showProducts);')) {
        secondIdx = i;
    }
}

if (firstIdx !== -1 && secondIdx !== -1 && secondIdx > firstIdx) {
    let block = `        productsNav.addEventListener("click", showProducts);
        invoiceNav.addEventListener("click", showInvoice);
        settingsBtn.addEventListener("click", showSettings);
        helpBtn.addEventListener("click", showHelp);

        backProducts.addEventListener("click", showProducts);
        if(invoiceBackBtn) invoiceBackBtn.addEventListener("click", showProducts);`;

    lines.splice(firstIdx, secondIdx - firstIdx + 1, block);
    fs.writeFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', lines.join('\n'), 'utf8');
    console.log("Replaced lines " + firstIdx + " to " + secondIdx);
}
