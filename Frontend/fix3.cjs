const fs = require('fs');
let txt = fs.readFileSync('c:\\xampp\\htdocs\\HBOS\\src\\views\\ProductView.vue', 'utf8');

txt = txt.replace('.page {\r\n            width: 100%;\r\n            padding: 42px;\r\n        }', '.page {\r\n            width: 100%;\r\n            padding: 42px;\r\n            overflow-x: hidden;\r\n        }');

fs.writeFileSync('c:\\xampp\\htdocs\\HBOS\\src\\views\\ProductView.vue', txt);
