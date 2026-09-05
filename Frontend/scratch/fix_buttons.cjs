const fs = require('fs');
let txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// The Save Product button is currently <button class="save-btn" id="saveProduct" type="button">
txt = txt.replace(
    '<button class="save-btn" id="saveProduct" type="button">',
    '<button class="save-btn" id="saveProduct" type="submit">' // or @click="saveProduct"
);

// Actually, if we change type="button" to type="submit", it will trigger the form's @submit.prevent!
// Let's also make sure Cancel works
txt = txt.replace(
    '<button class="cancel-btn" id="cancelBtn" type="button">',
    '<button class="cancel-btn" id="cancelBtn" type="button" @click="closeProductPanel">'
);

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', txt, 'utf8');
console.log("Updated save and cancel buttons.");
