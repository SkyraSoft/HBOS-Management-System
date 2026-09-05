const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'reports1View.vue');
let content = fs.readFileSync(filePath, 'utf8');

// The current structure:
//              <!-- =================================================
//                   FILTERS
//              ================================================== -->
//
//              <div class="filter-bar">
//              ...
//              </div>
//
//              <!-- =================================================
//                   KPI CARDS
//              ================================================== -->
//            <div v-if="activeTab === 'Overview'">

// We want to change it to:
//            <div v-if="activeTab === 'Overview'">
//              <!-- =================================================
//                   FILTERS
//              ================================================== -->
//
//              <div class="filter-bar">
//              ...
//              </div>
//
//              <!-- =================================================
//                   KPI CARDS
//              ================================================== -->

const oldString = `              <!-- =================================================
                   FILTERS
              ================================================== -->`;

const newString = `            <div v-if="activeTab === 'Overview'">
              <!-- =================================================
                   FILTERS
              ================================================== -->`;

content = content.replace(oldString, newString);

// And we need to remove the current `<div v-if="activeTab === 'Overview'">`
const currentVif = `              <!-- =================================================
                   KPI CARDS
              ================================================== -->
            <div v-if="activeTab === 'Overview'">`;

const fixedVif = `              <!-- =================================================
                   KPI CARDS
              ================================================== -->`;

content = content.replace(currentVif, fixedVif);

fs.writeFileSync(filePath, content, 'utf8');
console.log('Moved v-if to include filters');
