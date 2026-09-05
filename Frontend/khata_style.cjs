const fs = require('fs');
let khView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', 'utf8');

// Update the inline style for .payment-page when isModal is true
const oldStyle = `:style="props.isModal ? 'position: fixed; inset: 0; z-index: 1060; pointer-events: auto; background: var(--bg); overflow-y: auto;' : ''"`;
const newStyle = `:style="props.isModal ? 'position: fixed; top: 10vh; left: 50%; transform: translateX(-50%); z-index: 1060; pointer-events: auto; background: var(--bg); overflow-y: auto; border: 1px solid #ddd; box-shadow: 0 15px 40px rgba(0,0,0,0.15); border-radius: 16px; padding: 24px; max-width: 95vw; width: 1200px; max-height: 80vh;' : ''"`;

khView = khView.replace(oldStyle, newStyle);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/KhataView.vue', khView);
