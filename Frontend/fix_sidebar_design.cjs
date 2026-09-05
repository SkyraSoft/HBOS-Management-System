const fs = require('fs');
let layout = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', 'utf8');

// Add gap to the nav item div that wraps icon and text
layout = layout.replace(/<div style="display: flex; align-items: center;">/g, '<div style="display: flex; align-items: center; gap: 12px;">');

// Add gap to the bottom items as well (they are just placed directly inside the <a>, which is flex)
// Wait, bottomNavItems structure is:
/*
          <a 
            href="#" 
            class="nav-link" 
            :class="{ active: isItemActive(item) }"
            @click.prevent="handleNavClick(item.path)"
          >
            <i class="bi" :class="item.icon"></i>
            <span>{{ item.name }}</span>
          </a>
*/
// The nav-link class handles flex, but I can add gap or margin to the icon
layout = layout.replace(/<i class="bi" :class="item.icon"><\/i>\s*<span>{{ item\.name }}<\/span>/g, '<i class="bi" :class="item.icon" style="margin-right: 12px;"></i>\n            <span>{{ item.name }}</span>');

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', layout);
