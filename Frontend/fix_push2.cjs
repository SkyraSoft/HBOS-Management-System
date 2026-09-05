const fs = require('fs');

let content = fs.readFileSync('src/stores/retail.js', 'utf8');

const oldPush = `            maxStock: Number(p.max_stock),
            unit: p.unit,
            active: Boolean(p.is_active !== false)
        })`;

const newPush = `            maxStock: Number(p.max_stock),
            unit: p.unit,
            active: Boolean(p.is_active !== false),
            image: p.image || ''
        })`;

content = content.replace(oldPush, newPush);

fs.writeFileSync('src/stores/retail.js', content);
console.log('retail.js push updated');
