const fs = require('fs');

let content = fs.readFileSync('src/stores/retail.js', 'utf8');

const oldPush = `        products.value.push({
            id: p.id,
            name: p.name,
            sku: p.sku,
            category: p.category?.name || '',
            description: p.description,
            cost: Number(p.cost_price),
            price: Number(p.selling_price),
            stock: Number(p.stock),
            minStock: Number(p.min_stock),
            maxStock: Number(p.max_stock),
            unit: p.unit,
            active: Boolean(p.is_active !== false)
        })`;

const newPush = `        products.value.push({
            id: p.id,
            name: p.name,
            sku: p.sku,
            category: p.category?.name || '',
            description: p.description,
            cost: Number(p.cost_price),
            price: Number(p.selling_price),
            stock: Number(p.stock),
            minStock: Number(p.min_stock),
            maxStock: Number(p.max_stock),
            unit: p.unit,
            active: Boolean(p.is_active !== false),
            image: p.image || ''
        })`;

content = content.replace(oldPush, newPush);

fs.writeFileSync('src/stores/retail.js', content);
console.log('retail.js push updated');
