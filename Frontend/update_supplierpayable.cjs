const fs = require('fs');
let file = fs.readFileSync('src/views/SupplierpayableView.vue', 'utf8');

// Update script setup
file = file.replace(
`<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();`,
`<script setup>
import { onMounted, ref, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useSuppliersStore } from '@/stores/suppliers';

const store = useSuppliersStore();
const suppliers = computed(() => store.suppliers);

const route = useRoute();`
);

file = file.replace(
`onMounted(() => {
  console.log('SupplierpayableView mounted');
});`,
`onMounted(async () => {
  console.log('SupplierpayableView mounted');
  await store.fetchSuppliers();
});`
);

// Replace tbody
const tbodyStart = file.indexOf('<tbody id="supplierTable">');
const tbodyEnd = file.indexOf('</tbody>', tbodyStart) + 8;

const newTbody = `<tbody id="supplierTable">
                                    <tr v-for="s in suppliers" :key="s.id">
                                        <td>{{ s.name }}</td>
                                        <td>{{ s.phone || 'N/A' }}</td>
                                        <td>0</td>
                                        <td class="currency">PKR {{ (s.total_purchases || 0).toLocaleString() }}</td>
                                        <td class="outstanding">
                                            PKR {{ (s.balance || 0).toLocaleString() }}
                                        </td>
                                        <td>
                                            <span class="status active">
                                                Active
                                            </span>
                                        </td>
                                        <td>
                                            <button class="view-btn">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>`;

file = file.substring(0, tbodyStart) + newTbody + file.substring(tbodyEnd);
fs.writeFileSync('src/views/SupplierpayableView.vue', file);
console.log('SupplierpayableView updated!');
