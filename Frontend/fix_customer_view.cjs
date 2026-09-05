const fs = require('fs');
const file = 'src/views/CustomerView.vue';
let content = fs.readFileSync(file, 'utf8');

const replacement = `                        <div class="stat-note danger">
                            Requires immediate<br />
                            attention
                        </div>

                    </div>

                </div>

                <!-- =================================================
             CUSTOMER TABLE
        ================================================== -->

                <div class="customer-panel">

                    <!-- TOOLBAR -->
                    <div class="table-toolbar">

                        <div class="customer-search">

                            <i class="bi bi-search"></i>

                            <input type="text" id="customerSearch" v-model="searchQuery"
                                placeholder="Search customer by name or phone number..." />

                        </div>

                        <select class="filter-select" id="customerType" v-model="filterType">

                            <option value="all">
                                Customer Type: All
                            </option>

                            <option value="regular">
                                Regular
                            </option>

                            <option value="wholesale">
                                Wholesale
                            </option>

                            <option value="business">
                                Business
                            </option>

                        </select>

                        <select class="filter-select" id="creditStatus" v-model="filterStatus">

                            <option value="all">
                                Credit Status: All
                            </option>

                            <option value="clear">
                                Clear
                            </option>

                            <option value="outstanding">
                                Outstanding
                            </option>

                            <option value="overdue">
                                Overdue
                            </option>

                        </select>

                        <div class="toolbar-spacer"></div>

                        <button class="icon-button" title="Filter">

                            <i class="bi bi-funnel"></i>

                        </button>

                        <button class="icon-button" title="Download" onclick="downloadCustomers()">

                            <i class="bi bi-download"></i>

                        </button>

                    </div>

                    <!-- TABLE -->
                    <div class="table-wrapper">`;

content = content.replace(/\s*<div class="stat-note danger">\s*Requires immediate<br \/>\s*attention\s*<\/button>\s*<\/div>\s*<!-- TABLE -->\s*<div class="table-wrapper">/m, replacement);

fs.writeFileSync(file, content);
console.log('Fixed');
