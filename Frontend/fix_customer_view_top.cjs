const fs = require('fs');
const file = 'src/views/CustomerView.vue';
let content = fs.readFileSync(file, 'utf8');
const lines = content.split('\n');

const replacement = `                        <button class="btn-custom btn-primary-custom" @click="openAddModal">
                            <i class="bi bi-person-plus"></i>
                            Add Customer
                        </button>
                    </div>
                </div>

                <!-- =================================================
             STATISTICS
        ================================================== -->
                <div class="stats-grid">
                    <!-- TOTAL CUSTOMERS -->
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-label">
                            Total<br />
                            Customers
                        </div>`;

let startIdx = lines.findIndex(l => l.includes('btn-primary-custom" @click="openAddModal"'));
let endIdx = lines.findIndex(l => l.includes('<div class="stat-value">') && lines[lines.indexOf(l) + 1].includes('842'));

if (startIdx === -1) {
    // maybe it was deleted entirely, let's find where to insert
    startIdx = lines.findIndex(l => l.includes('Record Payment'));
    while(startIdx < lines.length && !lines[startIdx].includes('</button>')) startIdx++;
    startIdx++; // the line after </button>
}
console.log(startIdx, endIdx);

lines.splice(startIdx, endIdx - startIdx, replacement);

fs.writeFileSync(file, lines.join('\n'));
console.log('Fixed buttons and stats card');
