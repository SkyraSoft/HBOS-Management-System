const fs = require('fs');
const file = 'c:/xampp/htdocs/HBOS/src/views/InventryView.vue';
let txt = fs.readFileSync(file, 'utf8');

// 1. Update invoice HTML to use invoiceData
txt = txt.replace(
    /<div class="receipt-header">[\s\S]*?<\/div>/,
    `<div class="receipt-header">
                                    <div class="invoice-number" id="receiptInvoiceNumber">
                                        {{ invoiceData.number }}
                                    </div>
                                    <div class="invoice-date" id="receiptDate">
                                        {{ invoiceData.date }}
                                    </div>
                                </div>`
);

txt = txt.replace(
    /<td id="receiptProduct">\s*Product\s*<\/td>/,
    '<td id="receiptProduct">\n                                            {{ invoiceData.product }} <br> <small>{{ invoiceData.sku }}</small>\n                                        </td>'
);

txt = txt.replace(
    /<td id="receiptQty">\s*1\s*<\/td>/,
    '<td id="receiptQty">\n                                            {{ invoiceData.quantity }}\n                                        </td>'
);

txt = txt.replace(
    /<td id="receiptAmount">\s*0\s*<\/td>/,
    '<td id="receiptAmount">\n                                            PKR {{ invoiceData.total }}\n                                        </td>'
);

txt = txt.replace(
    /<span id="receiptPurchase">\s*PKR 0\s*<\/span>/,
    '<span id="receiptPurchase">\n                                        PKR {{ invoiceData.purchasePrice }}\n                                    </span>'
);

txt = txt.replace(
    /<span id="receiptQuantity">\s*1\s*<\/span>/,
    '<span id="receiptQuantity">\n                                        {{ invoiceData.quantity }}\n                                    </span>'
);

txt = txt.replace(
    /<span id="receiptTotal">\s*PKR 0\s*<\/span>/,
    '<span id="receiptTotal">\n                                        PKR {{ invoiceData.total }}\n                                    </span>'
);


// 2. Add WhatsApp button and fix "Add New Product" button
txt = txt.replace(
    /<button class="black" id="newProductBtn" @click="openProductPanelFromInvoice">[\s\S]*?<\/button>/,
    `<button class="primary-btn" id="newProductBtn" @click="openProductPanelFromInvoice" style="margin-top: 20px; width: auto; padding: 10px 20px;">
                                ＋ Add New Product
                            </button>`
);

txt = txt.replace(
    /<div class="receipt-buttons">/,
    `<div class="receipt-buttons">

                            <button id="whatsappInvoice" @click="whatsappInvoice" style="background: #25d366; color: white; border-color: #25d366;">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </button>`
);

// 3. Add whatsappInvoice function
const whatsappFunc = `
const whatsappInvoice = () => {
    const text = \`HBOS Inventory Invoice
Invoice: #\${invoiceData.value.number}
Product: \${invoiceData.value.product}
SKU: \${invoiceData.value.sku}
Quantity: \${invoiceData.value.quantity}
Total: PKR \${invoiceData.value.total}\`;
    
    const url = \`https://wa.me/?text=\${encodeURIComponent(text)}\`;
    window.open(url, '_blank');
}
`;

txt = txt.replace(
    "const printInvoice =",
    whatsappFunc + "\nconst printInvoice ="
);


fs.writeFileSync(file, txt);
console.log("Invoice connected and updated");
