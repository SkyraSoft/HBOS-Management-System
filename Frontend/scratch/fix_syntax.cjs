const fs = require('fs');

const vueFile = 'c:/xampp/htdocs/HBOS/src/views/CustomerView.vue';
let content = fs.readFileSync(vueFile, 'utf8');

// The file got messed up. Let's just fix it manually using regex to extract the parts and rebuild.

const scriptStart = content.indexOf('<script setup>');
const scriptEnd = content.indexOf('</script>') + 9;
const scriptContent = content.substring(scriptStart, scriptEnd);

const styleStart = content.indexOf('<style scoped>');
const styleEnd = content.indexOf('</style>') + 8;
const styleContent = content.substring(styleStart, styleEnd);

// Extract the section page
const sectionStart = content.indexOf('<section class="page">');
const sectionEndTag = content.indexOf('</section>') + 10;
const sectionContent = content.substring(sectionStart, sectionEndTag);

// Extract the modals
const modalsStart = content.indexOf('<!-- MODALS -->');
const modalsEnd = content.indexOf('<!-- RECORD PAYMENT MODAL -->') > -1 ? content.indexOf('</div>\r\n</template>', modalsStart) || content.indexOf('</div>\n</template>', modalsStart) : -1;

let modalsContent = "";
if (modalsStart > -1) {
    const end = content.indexOf('</template>', modalsStart);
    modalsContent = content.substring(modalsStart, end);
    // remove any dangling </div> that belongs to the app wrapper, but wait, I can just find the end of Record Payment Modal
    const recordPaymentEnd = content.indexOf('</div>\n        </div>\n    </div>', content.indexOf('<!-- RECORD PAYMENT MODAL -->')) + 33;
    if (recordPaymentEnd > 33) {
        modalsContent = content.substring(modalsStart, recordPaymentEnd);
    } else {
        const recordPaymentEnd2 = content.indexOf('</div>\r\n        </div>\r\n    </div>', content.indexOf('<!-- RECORD PAYMENT MODAL -->')) + 36;
        if (recordPaymentEnd2 > 36) modalsContent = content.substring(modalsStart, recordPaymentEnd2);
    }
}

const finalTemplate = `
<template>
<div class="customer-view-container" style="width: 100%; height: 100%; overflow: auto;">
${sectionContent}

${modalsContent}
</div>
</template>

${scriptContent}

${styleContent}
`;

fs.writeFileSync(vueFile, finalTemplate.trim());
console.log("Fixed CustomerView.vue syntax and layout structure.");
