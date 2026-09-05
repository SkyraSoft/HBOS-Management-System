const fs = require('fs');

let content = fs.readFileSync('src/stores/retail.js', 'utf8');

const oldCall = `      const response = await api.post('/products', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })`;

const newCall = `      const response = await api.post('/products', formData)`;

content = content.replace(oldCall, newCall);

fs.writeFileSync('src/stores/retail.js', content);
console.log('retail.js updated again');
