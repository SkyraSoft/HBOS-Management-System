const fs = require('fs');

let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// Replace invoice details
vue = vue.replace(
    /<strong id="invoiceNumber">[^<]+<\/strong>/,
    '<strong id="invoiceNumber">{{ invoiceData.number }}</strong>'
);
vue = vue.replace(
    /<strong id="invoiceProduct">[^<]+<\/strong>/,
    '<strong id="invoiceProduct">{{ invoiceData.product }}</strong>'
);
vue = vue.replace(
    /<strong id="invoiceSku">[^<]+<\/strong>/,
    '<strong id="invoiceSku">{{ invoiceData.sku }}</strong>'
);
vue = vue.replace(
    /<strong id="invoiceCategory">[^<]+<\/strong>/,
    '<strong id="invoiceCategory">{{ invoiceData.category }}</strong>'
);
vue = vue.replace(
    /<strong id="invoiceQty">[^<]+<\/strong>/,
    '<strong id="invoiceQty">{{ invoiceData.quantity }}</strong>'
);
vue = vue.replace(
    /<strong id="invoiceTotal">\s*PKR [^<]+\s*<\/strong>/m,
    '<strong id="invoiceTotal">\n                                    PKR {{ formatMoney(invoiceData.total) }}\n                                </strong>'
);

// Receipt side
vue = vue.replace(
    /<span id="receiptInvoice">[^<]+<\/span>/,
    '<span id="receiptInvoice">{{ invoiceData.number }}</span>'
);
vue = vue.replace(
    /<span id="receiptDate">[^<]+<\/span>/,
    '<span id="receiptDate">{{ invoiceData.date }}</span>'
);
vue = vue.replace(
    /<span class="item-name" id="receiptProduct">[^<]+<\/span>/,
    '<span class="item-name" id="receiptProduct">{{ invoiceData.product }}</span>'
);
vue = vue.replace(
    /<span id="receiptQty">[^<]+<\/span>/,
    '<span id="receiptQty">{{ invoiceData.quantity }}</span>'
);
vue = vue.replace(
    /<span id="receiptAmount">[^<]+<\/span>/,
    '<span id="receiptAmount">{{ formatMoney(invoiceData.sellingPrice || (invoiceData.total / (invoiceData.quantity || 1))) }}</span>'
);
vue = vue.replace(
    /<span id="receiptSubtotal">[^<]+<\/span>/,
    '<span id="receiptSubtotal">{{ formatMoney(invoiceData.total) }}</span>'
);
vue = vue.replace(
    /<strong id="receiptGrand">[^<]+<\/strong>/,
    '<strong id="receiptGrand">{{ formatMoney(invoiceData.total) }}</strong>'
);

// Buttons
vue = vue.replace(
    /<button class="secondary-btn" id="backProducts">/,
    '<button class="secondary-btn" id="backProducts" @click="activeTab = \'products\'">'
);

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', vue, 'utf8');
console.log("Invoice binds added.");
