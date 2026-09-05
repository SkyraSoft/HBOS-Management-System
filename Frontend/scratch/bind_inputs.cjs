const fs = require('fs');

let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

vue = vue.replace(
    /<input type="text" id="productName"([^>]+)>/g,
    '<input type="text" id="productName" v-model="newProduct.name"$1>'
);
vue = vue.replace(
    /<input type="text" id="sku"([^>]+)>/g,
    '<input type="text" id="sku" v-model="newProduct.sku"$1>'
);
vue = vue.replace(
    /<select id="category"([^>]+)>/g,
    '<select id="category" v-model="newProduct.category"$1>'
);
vue = vue.replace(
    /<input type="number" id="purchasePrice"([^>]+)>/g,
    '<input type="number" id="purchasePrice" v-model.number="newProduct.purchasePrice" @input="calculateMargin"$1>'
);
vue = vue.replace(
    /<input type="number" id="sellingPrice"([^>]+)>/g,
    '<input type="number" id="sellingPrice" v-model.number="newProduct.sellingPrice" @input="calculateMargin"$1>'
);
vue = vue.replace(
    /<input type="number" id="stock"([^>]+)>/g,
    '<input type="number" id="stock" v-model.number="newProduct.stock"$1>'
);
vue = vue.replace(
    /<select id="unit">/g,
    '<select id="unit" v-model="newProduct.unit">'
);
vue = vue.replace(
    /<input type="number" id="minStock"([^>]+)>/g,
    '<input type="number" id="minStock" v-model.number="newProduct.minStock"$1>'
);
vue = vue.replace(
    /<textarea id="notes"([^>]+)><\/textarea>/g,
    '<textarea id="notes" v-model="newProduct.notes"$1></textarea>'
);

// Also margin display
vue = vue.replace(
    /<span id="marginValue">\s*--%\s*<\/span>/,
    '<span id="marginValue">{{ marginDisplay }}%</span>'
);

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', vue, 'utf8');
console.log("Inputs bound.");
