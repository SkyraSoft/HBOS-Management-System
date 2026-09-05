const fs = require('fs');
let content = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

const startMarker = "<!-- ====================================================\r\n             CATEGORIES PAGE";
let startIdx = content.indexOf(startMarker);
if (startIdx === -1) {
    startIdx = content.indexOf("<!-- ====================================================\n             CATEGORIES PAGE");
}

let endIdx = content.indexOf('</section>', startIdx);
if (endIdx !== -1) {
    endIdx += '</section>'.length; // Include the closing tag
} else {
    console.log("Could not find closing section tag.");
    process.exit(1);
}

const newCategoriesUI = `<!-- ====================================================
             CATEGORIES PAGE
        ===================================================== -->
        <section v-if="currentPage === 'categories'" class="pv-section" style="padding-top: 20px;">

            <div v-if="categoryViewMode === 'list'">
                <div class="page-header">
                    <div>
                        <h2>Categories Management</h2>
                        <p>Manage product categories, brands, and pricing details.</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="primary-btn" @click="categoryViewMode = 'add'">
                            <i class="bi bi-plus-lg me-1"></i>Add Category / Subcategory
                        </button>
                    </div>
                </div>

                <div class="simple-card" style="padding: 0;">
                    <div class="table-wrapper">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Product</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Brand</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Category</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Parent Category</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Subcategory</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">SKU</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Purchase Price</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Selling Price</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Stock</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569;">Status</th>
                                    <th style="padding: 12px 16px; font-weight: 600; color: #475569; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in products" :key="product.id" style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px 16px; color: #1e293b; font-weight: 500;">{{ product.name }}</td>
                                    <td style="padding: 12px 16px; color: #64748b;">{{ product.brand || '-' }}</td>
                                    <td style="padding: 12px 16px; color: #2563eb;">{{ product.category || '-' }}</td>
                                    <td style="padding: 12px 16px; color: #64748b;">{{ getParentCategory(product.category) || '-' }}</td>
                                    <td style="padding: 12px 16px; color: #64748b;">{{ getSubcategory(product.category) || '-' }}</td>
                                    <td style="padding: 12px 16px; color: #64748b; font-family: monospace;">{{ product.sku }}</td>
                                    <td style="padding: 12px 16px; color: #64748b;">PKR {{ product.purchasePrice || 0 }}</td>
                                    <td style="padding: 12px 16px; color: #1e293b; font-weight: 600;">PKR {{ product.sellingPrice || 0 }}</td>
                                    <td style="padding: 12px 16px;">
                                        <span :style="{ color: product.stock > 0 ? '#16a34a' : '#ef4444', fontWeight: '600' }">{{ product.stock || 0 }}</span>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <span class="badge" :class="product.stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'" style="padding: 5px 10px; border-radius: 20px;">
                                            {{ product.stock > 0 ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button style="border: none; background: #f1f5f9; color: #475569; border-radius: 6px; padding: 6px; cursor: pointer;"><i class="bi bi-eye"></i></button>
                                            <button style="border: none; background: #eff6ff; color: #2563eb; border-radius: 6px; padding: 6px; cursor: pointer;" @click="editProduct(product)"><i class="bi bi-pencil"></i></button>
                                            <button style="border: none; background: #fef2f2; color: #ef4444; border-radius: 6px; padding: 6px; cursor: pointer;" @click="deleteProduct(product.id)"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="products.length === 0">
                                    <td colspan="11" style="text-align: center; padding: 30px; color: #94a3b8;">No records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="categoryViewMode === 'add'" class="pv-add-category-view">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
                    <div>
                        <div class="pv-breadcrumb-area" style="display: flex; align-items: center; gap: 8px; color: #697386; font-size: 0.85rem; margin-bottom: 8px;">
                            <span>Product</span> <span style="font-size: 10px;">&gt;</span> <span style="cursor: pointer;" @click="categoryViewMode = 'list'">Categories</span> <span style="font-size: 10px;">&gt;</span> <span style="color: #171a24; font-weight: 600;">Add Category</span>
                        </div>
                        <h1 style="font-size: 1.8rem; font-weight: 700; color: #171a24; margin: 0;">Add Category / Subcategory</h1>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="secondary-btn" @click="categoryViewMode = 'list'" style="padding: 10px 20px;">Cancel</button>
                        <button class="primary-btn" @click="handleSaveAdvancedCategory" style="padding: 10px 20px;"><i class="bi bi-save me-2"></i> Save Details</button>
                    </div>
                </div>

                <div style="background: #fff; border: 1px solid #d8deea; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">Detailed Information</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Product Name</label>
                            <input type="text" class="pv-input" v-model="newCat.productName" placeholder="e.g., Premium Office Chair" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Brand</label>
                            <select class="pv-input" v-model="newCat.brand" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #fff;">
                                <option value="">Select Brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.name">{{ brand.name }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Category</label>
                            <select class="pv-input" v-model="newCat.category" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #fff;">
                                <option value="">Select Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Parent Category</label>
                            <select class="pv-input" v-model="newCat.parentId" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #fff;">
                                <option value="">None (Top-Level)</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Subcategory</label>
                            <select class="pv-input" v-model="newCat.subcategoryId" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #fff;">
                                <option value="">Select Subcategory</option>
                                <option v-for="sub in subcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">SKU</label>
                            <input type="text" class="pv-input" v-model="newCat.sku" placeholder="e.g., CHAIR-001" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Purchase Price</label>
                            <input type="number" class="pv-input" v-model="newCat.purchasePrice" placeholder="0.00" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Selling Price</label>
                            <input type="number" class="pv-input" v-model="newCat.sellingPrice" placeholder="0.00" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Stock</label>
                            <input type="number" class="pv-input" v-model="newCat.stock" placeholder="0" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                        </div>
                        
                        <div>
                            <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Status</label>
                            <select class="pv-input" v-model="newCat.status" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #fff;">
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </section>`;

let newContent = content.substring(0, startIdx) + newCategoriesUI + content.substring(endIdx);
fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', newContent);
console.log('Categories UI updated successfully.');
