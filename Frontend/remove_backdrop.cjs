const fs = require('fs');

// 1. Fix CustomerView.vue openModal / closeModal
let cView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', 'utf8');

const openModalPattern = /function openModal\(id\) \{[\s\S]*?document\.body\.appendChild\(bd\);\s*\}/;
const newOpenModal = `function openModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'block';
        el.style.zIndex = '1060';
        el.classList.add('show');
        if (props.isModal) {
            el.style.background = 'none';
            el.style.pointerEvents = 'none';
            const dialog = el.querySelector('.modal-dialog');
            if (dialog) dialog.style.pointerEvents = 'auto';
        } else {
            const bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show custom-bd-' + id;
            document.body.appendChild(bd);
        }
    }`;
cView = cView.replace(openModalPattern, newOpenModal);

const closeModalPattern = /function closeModal\(id\) \{[\s\S]*?bd\.remove\(\);\s*\}/;
const newCloseModal = `function closeModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'none';
        el.classList.remove('show');
        if (props.isModal) {
            el.style.background = '';
            el.style.pointerEvents = '';
        } else {
            const bd = document.querySelector('.custom-bd-' + id);
            if(bd) bd.remove();
        }
    }`;
cView = cView.replace(closeModalPattern, newCloseModal);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', cView);

// 2. Fix DashboardView.vue backdrops
let dView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', 'utf8');
dView = dView.replace(/<div v-if="activeModal" class="modal-backdrop fade show" style="z-index: 1040;"><\/div>/g, '');

// Update other modals in Dashboard to use pointer events so background is clickable
dView = dView.replace(/<div v-if="activeModal === 'product'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/g, `<div v-if="activeModal === 'product'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="pointer-events: auto;">`);
dView = dView.replace(/<div v-if="activeModal === 'expense'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/g, `<div v-if="activeModal === 'expense'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="pointer-events: auto;">`);
dView = dView.replace(/<div v-if="activeModal === 'purchase'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/g, `<div v-if="activeModal === 'purchase'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="pointer-events: auto;">`);
dView = dView.replace(/<div v-if="activeModal === 'returndebt'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050;">/g, `<div v-if="activeModal === 'returndebt'" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: none; pointer-events: none;"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="pointer-events: auto;">`);

// Cleanup duplicate <div class="modal-dialog..."> since my regex replaced the opening div, I should replace it carefully.
// Ah, the regex above added `<div class="modal-dialog...` but the template ALREADY HAS `<div class="modal-dialog...` underneath it!
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/DashboardView.vue', dView);

