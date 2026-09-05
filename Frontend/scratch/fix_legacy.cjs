const fs = require('fs');

let html = fs.readFileSync('legacy_html/Expenses.html', 'utf8');

const toggleFunc = `
        function toggleSubmenu(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.toggle('show');
                const arrow = document.getElementById(id.replace('Submenu', 'Arrow'));
                if (arrow) arrow.classList.toggle('open');
            }
        }

        function switchPage(page) {`;

html = html.replace('function switchPage(page) {', toggleFunc);

const oldActiveLogic = "document.querySelectorAll('.sidebar .nav-item').forEach(btn => btn.classList.remove('active'));";
const newActiveLogic = `
            document.querySelectorAll('.submenu button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.bottom-link').forEach(btn => btn.classList.remove('active'));
            const btn = document.querySelector(\`button[data-page="\${page}"]\`);
            if (btn) btn.classList.add('active');
`;
html = html.replace(oldActiveLogic, newActiveLogic);

fs.writeFileSync('legacy_html/Expenses.html', html);
console.log('Fixed legacy');
