const fs = require('fs');
let txt = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// Fix Issue 1: Change :root to .app so CSS variables work properly inside scoped CSS
txt = txt.replace(':root {', '.app {');

// Fix Issue 2: The Save Product button is OUTSIDE the </form> tag.
// So type="submit" doesn't work. We need to explicitly bind @click="saveProduct".
txt = txt.replace(
    '<button class="save-btn" id="saveProduct" type="submit">',
    '<button class="save-btn" id="saveProduct" type="button" @click="saveProduct">'
);

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', txt, 'utf8');
console.log("Fixed CSS variables scope and Save Product click handler.");
