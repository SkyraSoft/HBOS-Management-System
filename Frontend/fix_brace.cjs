const fs = require('fs');

let cView = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', 'utf8');

cView = cView.replace(/function closeModal\(id\) \{\s*const el = document\.getElementById\(id\);\s*if\(el\) \{\s*el\.style\.display = 'none';\s*el\.classList\.remove\('show'\);\s*if \(props\.isModal\) \{\s*el\.style\.background = '';\s*el\.style\.pointerEvents = '';\s*\} else \{\s*const bd = document\.querySelector\('\.custom-bd-' \+ id\);\s*if\(bd\) bd\.remove\(\);\s*\}\s*\}/, `function closeModal(id) {
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
    }
}`);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/CustomerView.vue', cView);
