const fs = require('fs');
let dView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');

// Undo bad replace if it exists
dView = dView.replace(/<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="pointer-events: auto;">\s*<div class="modal-dialog/g, `<div class="modal-dialog`);

// Do a proper replace for each modal to add styles without duplicating the inner div
dView = dView.replace(/<div v-if="activeModal === 'product'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/, `<div v-if="activeModal === 'product'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;">`);
dView = dView.replace(/<div v-if="activeModal === 'expense'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/, `<div v-if="activeModal === 'expense'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;">`);
dView = dView.replace(/<div v-if="activeModal === 'purchase'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/, `<div v-if="activeModal === 'purchase'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;">`);
dView = dView.replace(/<div v-if="activeModal === 'returndebt'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/, `<div v-if="activeModal === 'returndebt'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;">`);

// Make inner dialog clickable
dView = dView.replace(/<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">/g, `<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="pointer-events: auto;">`);

// Save
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', dView);
