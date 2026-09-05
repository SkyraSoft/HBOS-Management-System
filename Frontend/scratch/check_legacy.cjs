const fs = require('fs');
const txt = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Inventry.html', 'utf8');
console.log('App end:', txt.indexOf('</div>', txt.indexOf('</main>')));
console.log('Side panel start:', txt.indexOf('<div class="side-panel"'));
