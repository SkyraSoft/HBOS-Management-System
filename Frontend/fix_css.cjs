const fs = require('fs');
let txt = fs.readFileSync('c:\\xampp\\htdocs\\HBOS\\src\\assets\\main.css', 'utf8');

const regex = /\.nav-link\.active \{[\s\S]*?justify-content: space-between;/m;

const replacement = `.nav-link.active {
            background: #f1f5fc;
            color: #52657f;

            font-weight: 600;
        }

        .nav-link.active::after {
            content: "";

            position: absolute;

            top: 0;
            right: 0;

            width: 4px;
            height: 100%;

            background: #52647d;

            border-radius: 0 5px 5px 0;
        }


        /* =========================================================
           MAIN
        ========================================================== */

        .main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .main-content {
            max-width: 1080px;

            margin: 0 auto;

            padding: 48px 24px 40px;
        }


        /* =========================================================
           TOP BAR
        ========================================================== */

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;`;

txt = txt.replace(regex, replacement);
fs.writeFileSync('c:\\xampp\\htdocs\\HBOS\\src\\assets\\main.css', txt);
