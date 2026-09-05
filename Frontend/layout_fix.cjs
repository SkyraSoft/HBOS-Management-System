const fs = require('fs');
let layout = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', 'utf8');

// Update handleNavClick
const navClickPattern = /const handleNavClick = \(item\) => {[\s\S]*?isSidebarOpen\.value = false;\n    }\n  };/;
const newNavClick = `const handleNavClick = (item) => {
    if (item.children) {
      toggleMenu(item.path);
    } else {
      if (route.path === (typeof item === 'string' ? item : item.path)) {
        window.location.reload();
      } else {
        router.push(typeof item === 'string' ? item : item.path);
      }
      isSidebarOpen.value = false;
    }
  };`;
layout = layout.replace(navClickPattern, newNavClick);

// Update handleChildClick
const childClickPattern = /const handleChildClick = \(childPath\) => {\n    router\.push\(childPath\);\n    isSidebarOpen\.value = false;\n  };/;
const newChildClick = `const handleChildClick = (childPath) => {
    if (route.path === childPath || route.path + route.hash === childPath) {
        window.location.reload();
    } else {
        router.push(childPath);
    }
    isSidebarOpen.value = false;
  };`;
layout = layout.replace(childClickPattern, newChildClick);

// Update goToDashboard
const goPattern = /const goToDashboard = \(\) => {\n    router\.push\('\/'\)\n  }/;
const newGo = `const goToDashboard = () => {
    if (route.path === '/') {
        window.location.reload();
    } else {
        router.push('/')
    }
  }`;
layout = layout.replace(goPattern, newGo);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/layouts/MainLayout.vue', layout);
