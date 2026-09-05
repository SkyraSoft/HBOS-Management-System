const fs = require('fs');
let f = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8'); 
f = f.replace(/v-if="!props.isModal"/g, 'v-if="!isModal"'); 
f = f.replace(/v-show="isPaymentPage \|\| props.isModal"/g, 'v-show="isPaymentPage || isModal"'); 
f = f.replace(/:style="props.isModal \? /g, ':style="isModal ? '); 
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', f);
