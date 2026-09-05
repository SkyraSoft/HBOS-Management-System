const fs = require('fs');
const txt = fs.readFileSync('c:\\xampp\\htdocs\\HBOS\\legacy_html\\Inventry.html', 'utf8');

const mainStart = txt.indexOf('<div class="main">');
const scriptStart = txt.indexOf('<script>');

const html = txt.substring(mainStart, scriptStart);
fs.writeFileSync('c:\\xampp\\htdocs\\HBOS\\scratch_inventry_html.html', html);
console.log('HTML extracted successfully.');
