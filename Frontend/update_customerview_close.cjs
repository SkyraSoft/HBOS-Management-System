const fs = require('fs');

let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', 'utf8');

// Emit 'close' when closeModal is called, if isModal is true
const closeModalPattern = /closeModal\(modalId\) \{[\s\S]*?\}/;
const newCloseModal = `closeModal(modalId) {
    if (typeof window !== 'undefined' && window.bootstrap) {
      const modalEl = document.getElementById(modalId)
      if (modalEl) {
        const modal = window.bootstrap.Modal.getInstance(modalEl)
        if (modal) modal.hide()
      }
    }
    if (this?.isModal || typeof props !== 'undefined' && props.isModal) {
      emit('close');
    }
  }`;

// Actually wait, closeModal is an arrow function or normal function in script setup?
content = content.replace(/const closeModal = \(modalId\) => \{[\s\S]*?\n\}/, `const closeModal = (modalId) => {
    if (typeof window !== 'undefined' && window.bootstrap) {
        const modalEl = document.getElementById(modalId)
        if (modalEl) {
            const modal = window.bootstrap.Modal.getInstance(modalEl)
            if (modal) {
                modal.hide()
                // Wait to remove backdrop
                setTimeout(() => {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    document.body.classList.remove('modal-open');
                    document.body.style = '';
                }, 150);
            }
        }
    }
    if (props.isModal) {
        emit('close');
    }
}`);

// Wait, the emit is declared as: const emit = defineEmits(['success', 'close'])
content = content.replace(/const emit = defineEmits\(\['success'\]\)/, `const emit = defineEmits(['success', 'close'])`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', content);
console.log('CustomerView updated to emit close event.');
