const fs = require('fs');
let file = fs.readFileSync('src/views/KhataView.vue', 'utf8');

// Update script setup
file = file.replace(
`<script setup>
import { ref, onMounted, computed } from 'vue';
import { useKhataStore } from '@/stores/khata';

const store = useKhataStore();`,
`<script setup>
import { ref, onMounted, computed } from 'vue';
import { useKhataStore } from '@/stores/khata';
import { useCustomersStore } from '@/stores/customers';

const store = useKhataStore();
const customerStore = useCustomersStore();
const customers = computed(() => customerStore.customers);`
);

file = file.replace(
`onMounted(async () => {
  await store.fetchTransactions();
});`,
`onMounted(async () => {
  await store.fetchTransactions();
  await customerStore.fetchCustomers();
});`
);

// Update HTML tbody
const tbodyStart = file.indexOf('<tbody id="customerTableBody">');
const tbodyEnd = file.indexOf('</tbody>', tbodyStart) + 8;

if (tbodyStart !== -1 && tbodyEnd !== -1) {
    const newTbody = `<tbody id="customerTableBody">
                                    <tr class="customer-row" v-for="c in customers" :key="c.id">
                                        <td>
                                            <div class="customer-info">
                                                <div class="customer-avatar">
                                                    {{ c.name ? c.name.charAt(0).toUpperCase() : 'C' }}
                                                </div>
                                                <div>
                                                    <div class="customer-name">
                                                        {{ c.name }}
                                                    </div>
                                                    <div class="customer-phone">
                                                        {{ c.phone }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="currency">PKR {{ (c.balance || 0).toLocaleString() }}</td>
                                        <td class="currency"><span class="status-badge danger">PKR 0</span></td>
                                        <td><span class="status-badge" :class="{'success': (c.balance||0) >= 0, 'danger': (c.balance||0) < 0}">{{ (c.balance||0) >= 0 ? 'Clear' : 'Outstanding' }}</span></td>
                                        <td>
                                            <button class="btn-custom" @click="showPaymentPage">
                                                Payment
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>`;

    file = file.substring(0, tbodyStart) + newTbody + file.substring(tbodyEnd);
    fs.writeFileSync('src/views/KhataView.vue', file);
    console.log('Khata view updated!');
} else {
    console.log('Could not find tbody');
}
