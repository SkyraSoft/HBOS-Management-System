const fs = require('fs');

function fixLegacy() {
    let html = fs.readFileSync('legacy_html/Expenses.html', 'utf8');
    
    // Replace sidebar HTML
    const sidebarRegex = /<aside class="sidebar" id="sidebar">[\s\S]*?<\/aside>/;
    const newSidebar = `<aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-logo">H</div>
                <div class="brand-text">
                    <h1>HBOS</h1>
                    <p>Management System</p>
                </div>
            </div>
            
            <div class="sidebar-content">
                <div class="nav-label">Management</div>
                <button class="nav-item" onclick="window.location.href='/'" style="margin-bottom: 10px;">
                    <span class="nav-icon">←</span>
                    <span>Dashboard</span>
                </button>

                <button class="nav-item active" onclick="toggleSubmenu('expensesSubmenu')">
                    <span class="nav-icon">▣</span>
                    <span>Expenses</span>
                    <span class="nav-arrow open" id="expensesArrow">⌄</span>
                </button>
                
                <div class="submenu show" id="expensesSubmenu">
                    <button class="active" data-page="overview" onclick="switchPage('overview')">Expenses</button>
                    <button data-page="add" onclick="switchPage('add')">Add Expense</button>
                    <button data-page="analytics" onclick="switchPage('analytics')">Expense Analytics</button>
                    <button data-page="recurring" onclick="switchPage('recurring')">Recurring Expenses</button>
                </div>
            </div>

            <!-- ONLY SETTINGS + HELP -->
            <div class="sidebar-bottom">
                <button class="bottom-link" data-page="settings" onclick="switchPage('settings')">
                    <span class="nav-icon">⚙</span>
                    <span>Settings</span>
                </button>
                <button class="bottom-link" data-page="help" onclick="switchPage('help')">
                    <span class="nav-icon">?</span>
                    <span>Help</span>
                </button>
            </div>
        </aside>`;
    
    html = html.replace(sidebarRegex, newSidebar);

    // Update JS toggleSubmenu if needed
    if (!html.includes('function toggleSubmenu')) {
        const toggleFunc = `
        function toggleSubmenu(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.toggle('show');
                const arrow = document.getElementById(id.replace('Submenu', 'Arrow'));
                if (arrow) arrow.classList.toggle('open');
            }
        }
        function switchPage(pageId) {`;
        html = html.replace("function switchPage(pageId) {", toggleFunc);
    }
    
    // Switch page logic update for active state
    const oldSwitchPageBody = `document.querySelectorAll('.sidebar .nav-item').forEach(btn => btn.classList.remove('active'));`;
    const newSwitchPageBody = `
            document.querySelectorAll('.submenu button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.bottom-link').forEach(btn => btn.classList.remove('active'));
            const btn = document.querySelector(\`button[data-page="\${pageId}"]\`);
            if (btn) btn.classList.add('active');`;
            
    html = html.replace(oldSwitchPageBody, newSwitchPageBody);

    // CSS Updates: Replace the new flat sidebar css with the InventryView sidebar css
    const cssToReplace = /\.brand \{[\s\S]*?\.sidebar-bottom \{/g;
    const inventrySidebarCSS = `
        .brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-logo {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 8px;
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 18px;
        }
        .brand-text h1 {
            font-size: 20px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.5px;
            color: #111827;
        }
        .brand-text p {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 600;
            margin-top: 2px;
        }
        .sidebar-content {
            flex: 1;
            padding: 20px 12px;
            overflow-y: auto;
        }
        .nav-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 12px 12px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            height: 40px;
            padding: 0 13px;
            border: 0;
            background: transparent;
            border-radius: 8px;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: .2s;
        }
        .nav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .nav-item.active {
            background: #eff6ff;
            color: var(--primary);
            font-weight: 600;
        }
        .nav-icon {
            font-size: 18px;
            display: grid;
            place-items: center;
            color: inherit;
        }
        .nav-arrow {
            margin-left: auto;
            font-size: 16px;
            transition: .2s;
        }
        .nav-arrow.open {
            transform: rotate(180deg);
        }
        .submenu {
            display: none;
            flex-direction: column;
            gap: 4px;
            margin: 4px 0 4px 34px;
            border-left: 1px solid #e2e8f0;
            padding-left: 12px;
        }
        .submenu.show {
            display: flex;
        }
        .submenu button {
            text-align: left;
            border: 0;
            background: transparent;
            padding: 8px 12px;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            border-radius: 6px;
            transition: .2s;
        }
        .submenu button:hover {
            color: #0f172a;
            background: #f8fafc;
        }
        .submenu button.active {
            color: var(--primary);
            font-weight: 600;
            background: #eff6ff;
        }
        .sidebar-bottom {`;
    
    html = html.replace(cssToReplace, inventrySidebarCSS);
    
    const bottomLinkCSS = `
        .sidebar-bottom {
            padding: 24px 16px 24px 0;
            border-top: 1px solid #e2e8f0;
        }
        .bottom-link {
            display: flex;
            align-items: center;
            gap: 12px;
            width: calc(100% - 16px);
            height: 40px;
            padding: 0 13px;
            margin-left: 16px;
            border: 0;
            background: transparent;
            border-radius: 8px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: .2s;
        }
        .bottom-link:hover, .bottom-link.active {
            color: #0f172a;
            background: #f1f5f9;
        }
    `;
    
    html = html.replace(/\.sidebar-bottom \{[\s\S]*?\/\* =========================\s*MAIN\s*=========================\s*\*\//m, bottomLinkCSS + "\n        /* =========================\n   MAIN\n========================= */");

    html = html.replace(/margin-left: var\(--sidebar\);/g, "margin-left: 256px;");

    fs.writeFileSync('legacy_html/Expenses.html', html);
    console.log("legacy_html/Expenses.html updated.");
}

function fixVue() {
    let vue = fs.readFileSync('src/views/ExpensesView.vue', 'utf8');

    const sidebarRegex = /<aside class="sidebar" id="sidebar">[\s\S]*?<\/aside>/;
    const newSidebar = `<aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-logo">H</div>
                <div class="brand-text">
                    <h1>HBOS</h1>
                    <p>Management System</p>
                </div>
            </div>
            
            <div class="sidebar-content">
                <div class="nav-label">Management</div>
                <button class="nav-item" @click="$router.push('/')" style="margin-bottom: 10px;">
                    <span class="nav-icon">←</span>
                    <span>Dashboard</span>
                </button>

                <!-- EXPENSES -->
                <button class="nav-item active" @click="isSubmenuOpen = !isSubmenuOpen">
                    <span class="nav-icon">▣</span>
                    <span>Expenses</span>
                    <span class="nav-arrow" :class="{ open: isSubmenuOpen }">⌄</span>
                </button>
                
                <div class="submenu" :class="{ show: isSubmenuOpen }">
                    <button :class="{ active: activePage === 'overview' }" @click="activePage = 'overview'">Expenses</button>
                    <button :class="{ active: activePage === 'add' }" @click="activePage = 'add'">Add Expense</button>
                    <button :class="{ active: activePage === 'analytics' }" @click="activePage = 'analytics'">Expense Analytics</button>
                    <button :class="{ active: activePage === 'recurring' }" @click="activePage = 'recurring'">Recurring Expenses</button>
                </div>
            </div>

            <!-- ONLY SETTINGS + HELP -->
            <div class="sidebar-bottom">
                <button class="bottom-link" :class="{ active: activePage === 'settings' }" @click="activePage = 'settings'">
                    <span class="nav-icon">⚙</span>
                    <span>Settings</span>
                </button>
                <button class="bottom-link" :class="{ active: activePage === 'help' }" @click="activePage = 'help'">
                    <span class="nav-icon">?</span>
                    <span>Help</span>
                </button>
            </div>
        </aside>`;
    
    vue = vue.replace(sidebarRegex, newSidebar);

    vue = vue.replace('<div class="expenses-layout">', '<div class="app">');
    vue = vue.replace('</main> <!-- /.expenses-content-area -->', '</main>');
    vue = vue.replace('</div> <!-- /.expenses-layout -->', '</div>');
    
    if (!vue.includes('isSubmenuOpen')) {
        vue = vue.replace("const activePage = ref('overview')", "const activePage = ref('overview')\nconst isSubmenuOpen = ref(true)");
    }
    
    vue = vue.replace(/\.expenses-layout \{[\s\S]*?\}\s*\.expenses-sidebar \{[\s\S]*?\}\s*\.expenses-content-area \{[\s\S]*?\}/, `
        .app {
            min-height: 100vh;
            display: flex;
        }
        
        .main {
            margin-left: 256px;
            width: calc(100% - 256px);
            min-height: 100vh;
            background: var(--bg);
        }
    `);

    const cssToReplace = /\.brand \{[\s\S]*?\.sidebar-bottom \{/g;
    const inventrySidebarCSS = `
        .brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-logo {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 8px;
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 18px;
        }
        .brand-text h1 {
            font-size: 20px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.5px;
            color: #111827;
        }
        .brand-text p {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 600;
            margin-top: 2px;
        }
        .sidebar-content {
            flex: 1;
            padding: 20px 12px;
            overflow-y: auto;
        }
        .nav-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 12px 12px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            height: 40px;
            padding: 0 13px;
            border: 0;
            background: transparent;
            border-radius: 8px;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: .2s;
        }
        .nav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .nav-item.active {
            background: #eff6ff;
            color: var(--primary);
            font-weight: 600;
        }
        .nav-icon {
            font-size: 18px;
            display: grid;
            place-items: center;
            color: inherit;
        }
        .nav-arrow {
            margin-left: auto;
            font-size: 16px;
            transition: .2s;
        }
        .nav-arrow.open {
            transform: rotate(180deg);
        }
        .submenu {
            display: none;
            flex-direction: column;
            gap: 4px;
            margin: 4px 0 4px 34px;
            border-left: 1px solid #e2e8f0;
            padding-left: 12px;
        }
        .submenu.show {
            display: flex;
        }
        .submenu button {
            text-align: left;
            border: 0;
            background: transparent;
            padding: 8px 12px;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            border-radius: 6px;
            transition: .2s;
        }
        .submenu button:hover {
            color: #0f172a;
            background: #f8fafc;
        }
        .submenu button.active {
            color: var(--primary);
            font-weight: 600;
            background: #eff6ff;
        }
        .sidebar-bottom {`;
    
    vue = vue.replace(cssToReplace, inventrySidebarCSS);
    
    const bottomLinkCSS = `
        .sidebar-bottom {
            padding: 24px 16px 24px 0;
            border-top: 1px solid #e2e8f0;
        }
        .bottom-link {
            display: flex;
            align-items: center;
            gap: 12px;
            width: calc(100% - 16px);
            height: 40px;
            padding: 0 13px;
            margin-left: 16px;
            border: 0;
            background: transparent;
            border-radius: 8px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: .2s;
        }
        .bottom-link:hover, .bottom-link.active {
            color: #0f172a;
            background: #f1f5f9;
        }
    `;
    
    vue = vue.replace(/\.sidebar-bottom \{[\s\S]*?\/\* =========================\s*MAIN\s*=========================\s*\*\//m, bottomLinkCSS + "\n        /* =========================\n   MAIN\n========================= */");

    fs.writeFileSync('src/views/ExpensesView.vue', vue);
    console.log("src/views/ExpensesView.vue updated.");
}

fixLegacy();
fixVue();
