const fs = require('fs');
let file = fs.readFileSync('src/stores/customers.js', 'utf8');

file = file.replace(
    `return { success: true }`,
    `return { success: true, data: response.data }`
);

fs.writeFileSync('src/stores/customers.js', file);
console.log('customers.js updated');
