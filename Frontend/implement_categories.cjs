const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'InventryView.vue');
let content = fs.readFileSync(filePath, 'utf8');

const newCategoriesSection = `
        <section class="page simple-page" id="categoriesPage" v-if="activeTab === 'categories'">

            <div v-if="categoryViewMode === 'list'">
                <div class="page-header">
                    <div>
                        <button class="secondary-btn" @click="activeTab = 'products'" style="margin-bottom: 20px; border-color: transparent; padding-left: 0;">
                            &larr; Products
                        </button>
                        <h2>Categories</h2>
                        <p>Manage product categories for your inventory.</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button class="primary-btn" @click="categoryViewMode = 'add'">
                            <i class="bi bi-plus-lg me-1"></i>Add New Category
                        </button>
                    </div>
                </div>

                <div class="simple-card">
                    <h3>Parent Categories</h3>
                    <div class="table-wrapper" style="margin-top: 15px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th style="width: 150px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in categories" :key="category.id">
                                    <td v-if="editingCategoryId === category.id">
                                        <input type="text" v-model="editCategoryName" class="filter-input" @keyup.enter="saveEditCategory" style="width: 100%;" />
                                    </td>
                                    <td v-else><strong>{{ category.name }}</strong></td>
                                    <td style="text-align: right;" v-if="editingCategoryId === category.id">
                                        <button class="primary-btn" @click="saveEditCategory" style="margin-right: 5px; padding: 5px 10px;">Save</button>
                                        <button class="secondary-btn" @click="cancelEditCategory" style="padding: 5px 10px;">Cancel</button>
                                    </td>
                                    <td style="text-align: right;" v-else>
                                        <button class="action-btn" @click="startEditCategory(category)" style="margin-right: 5px; color: var(--blue); border-color: var(--blue-light); background: var(--blue-light);">Edit</button>
                                        <button class="action-btn" @click="handleDeleteCategory(category.id)" style="color: var(--danger); border-color: var(--danger-light); background: var(--danger-light);">Remove</button>
                                    </td>
                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="2" style="text-align: center; color: var(--text-light);">No categories found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 style="margin-top: 40px;">Sub-Categories</h3>
                    <div class="table-wrapper" style="margin-top: 15px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Parent Category</th>
                                    <th>Sub-Category Name</th>
                                    <th style="width: 150px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="sub in subcategories" :key="sub.id">
                                    <td><span style="color: var(--text-light); font-size: 0.9em;">{{ categories.find(c => c.id === sub.category_id)?.name || 'Unknown' }}</span></td>
                                    <td><strong>{{ sub.name }}</strong></td>
                                    <td style="text-align: right;">
                                        <button class="action-btn" @click="handleDeleteSubcategory(sub.id)" style="color: var(--danger); border-color: var(--danger-light); background: var(--danger-light);">Remove</button>
                                    </td>
                                </tr>
                                <tr v-if="subcategories.length === 0">
                                    <td colspan="3" style="text-align: center; color: var(--text-light);">No sub-categories found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="categoryViewMode === 'add'" class="pv-add-category-view">
                <!-- Topbar matching mockup -->
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
                    <div>
                        <div class="pv-breadcrumb-area" style="display: flex; align-items: center; gap: 8px; color: #697386; font-size: 0.85rem; margin-bottom: 8px;">
                            <span>Inventory</span> <span style="font-size: 10px;">&gt;</span> <span style="cursor: pointer;" @click="categoryViewMode = 'list'">Categories</span> <span style="font-size: 10px;">&gt;</span> <span style="color: #171a24; font-weight: 600;">Add New</span>
                        </div>
                        <h1 style="font-size: 1.8rem; font-weight: 700; color: #171a24; margin: 0;">Add New Category</h1>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="secondary-btn" @click="categoryViewMode = 'list'" style="padding: 10px 20px;">Cancel</button>
                        <button class="primary-btn" @click="handleSaveAdvancedCategory" style="padding: 10px 20px;"><i class="bi bi-save me-2"></i> Save Category</button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px;">
                    <!-- LEFT SIDE -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <!-- Basic Info -->
                        <div class="pv-form-card" style="background: #fff; border: 1px solid #d8deea; border-radius: 12px; padding: 24px;">
                            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">Basic Information</h3>
                            <div style="display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Category Name <span style="color: #ef4444;">*</span></label>
                                    <input type="text" class="pv-input" v-model="newCat.name" placeholder="e.g., Beverages" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <label class="pv-label" style="font-size: 0.85rem; font-weight: 600; color: #4b5563; margin: 0;">Category Code</label>
                                        <span style="font-size: 0.75rem; color: #2563eb; cursor: pointer; font-weight: 600;"><i class="bi bi-magic"></i> Auto-generate</span>
                                    </div>
                                    <input type="text" class="pv-input" v-model="newCat.code" placeholder="e.g., BEV-001" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #f8fafc;" />
                                </div>
                            </div>
                        </div>

                        <!-- Hierarchy & Display -->
                        <div class="pv-form-card" style="background: #fff; border: 1px solid #d8deea; border-radius: 12px; padding: 24px;">
                            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">Hierarchy & Display</h3>
                            
                            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div style="flex: 1;">
                                    <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Parent Category</label>
                                    <select class="pv-input" v-model="newCat.parentId" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; background: #fff;">
                                        <option value="">None (Top-Level Category)</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 8px;">Select 'None' to create a main category.</p>
                                </div>
                                <div style="flex: 1;">
                                    <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Sub-categories (Optional)</label>
                                    <input type="text" class="pv-input" v-model="newCat.subcategories" placeholder="e.g., Sodas, Juices, Water" :disabled="!!newCat.parentId" style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px;" />
                                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 8px;">Separate multiple sub-categories with commas.</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="pv-label" style="display: block; font-size: 0.85rem; font-weight: 600; color: #4b5563; margin-bottom: 8px;">Description (Optional)</label>
                                <textarea class="pv-input" v-model="newCat.description" placeholder="Add a brief description of the items in this category..." style="width: 100%; padding: 10px 14px; border: 1px solid #d8deea; border-radius: 8px; min-height: 100px; resize: vertical;"></textarea>
                            </div>
                        </div>

                        <!-- Category Icon / Image -->
                        <div class="pv-form-card" style="background: #fff; border: 1px solid #d8deea; border-radius: 12px; padding: 24px;">
                            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 1px dashed #e2e8f0;">Category Icon / Image</h3>
                            
                            <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: #fff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                    <i class="bi bi-cloud-arrow-up" style="font-size: 1.4rem; color: #2563eb;"></i>
                                </div>
                                <h4 style="font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0 0 4px 0;">Click to upload or drag and drop</h4>
                                <p style="font-size: 0.8rem; color: #64748b; margin: 0;">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
                            </div>
                        </div>
                        
                        <!-- Category Status -->
                        <div class="pv-form-card" style="background: #fff; border: 1px solid #d8deea; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 4px 0;">Category Status</h3>
                                <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Inactive categories will be hidden from POS and storefront.</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="position: relative; display: inline-block; width: 44px; height: 24px; background-color: var(--blue); border-radius: 12px; cursor: pointer;">
                                    <div style="position: absolute; top: 2px; left: 22px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: 0.2s;"></div>
                                </div>
                                <span style="font-size: 0.85rem; font-weight: 700; color: #1e293b;">Active</span>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT SIDEBAR -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        
                        <!-- Preview -->
                        <div class="pv-form-card" style="background: #fff; border: 1px solid #d8deea; border-radius: 12px; padding: 24px;">
                            <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px; color: #1e293b;">
                                <i class="bi bi-eye" style="color: #2563eb;"></i> Preview
                            </h3>
                            
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px 20px; display: flex; flex-direction: column; align-items: center; background: #f8fafc; margin-bottom: 16px;">
                                <div style="width: 64px; height: 64px; background: #f1f5f9; border-radius: 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                    <i class="bi bi-image" style="font-size: 1.5rem; color: #94a3b8;"></i>
                                </div>
                                <div style="width: 100px; height: 10px; background: #e2e8f0; border-radius: 5px; margin-bottom: 10px;"></div>
                                <div style="width: 70px; height: 10px; background: #e2e8f0; border-radius: 5px;"></div>
                            </div>
                            
                            <p style="font-size: 0.8rem; color: #64748b; margin: 0; text-align: center;">Fill out the form to see a live preview.</p>
                        </div>
                        
                        <!-- Organization Tips -->
                        <div class="pv-form-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
                            <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; color: #1e293b;">
                                <i class="bi bi-lightbulb" style="color: #2563eb;"></i> Organization Tips
                            </h3>
                            
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px;">
                                <li style="display: flex; gap: 10px; font-size: 0.8rem; color: #475569; line-height: 1.4;">
                                    <i class="bi bi-check-circle" style="color: #64748b; margin-top: 2px;"></i>
                                    Keep main categories broad (e.g., 'Beverages') and sub-categories specific (e.g., 'Sodas', 'Juices').
                                </li>
                                <li style="display: flex; gap: 10px; font-size: 0.8rem; color: #475569; line-height: 1.4;">
                                    <i class="bi bi-check-circle" style="color: #64748b; margin-top: 2px;"></i>
                                    Use clear, recognizable icons to speed up selection during POS checkout.
                                </li>
                                <li style="display: flex; gap: 10px; font-size: 0.8rem; color: #475569; line-height: 1.4;">
                                    <i class="bi bi-check-circle" style="color: #64748b; margin-top: 2px;"></i>
                                    Logical display ordering ensures high-volume categories appear first.
                                </li>
                            </ul>
                        </div>
                        
                    </div>
                </div>
            </div>
`;

// Replace existing section with new one
content = content.replace(
  /<section class="page simple-page" id="categoriesPage" v-if="activeTab === 'categories'">[\s\S]*?<\/section>/,
  newCategoriesSection + '\n        </section>'
);

// Inject variables into script
const variablesScript = `
const categoryViewMode = ref('list')
const newCat = ref({
    name: '',
    code: '',
    parentId: '',
    subcategories: '',
    description: '',
    status: true,
    image: null
})

const handleSaveAdvancedCategory = async () => {
    if (!newCat.value.name.trim()) {
        toastMsg.value = 'Category Name is required.'
        showToast()
        return
    }
    
    try {
        if (!newCat.value.parentId) {
            // Main Category
            await retailStore.addCategory(newCat.value.name.trim())
            // Need to get ID to add subcategories if provided
            const created = categories.value.find(c => c.name === newCat.value.name.trim())
            if (created && newCat.value.subcategories) {
                const subs = newCat.value.subcategories.split(',').map(s => s.trim()).filter(Boolean)
                for (const sub of subs) {
                    await retailStore.addSubcategory({ name: sub, category_id: created.id })
                }
            }
        } else {
            // Sub-category
            await retailStore.addSubcategory({ name: newCat.value.name.trim(), category_id: newCat.value.parentId })
        }
        
        toastMsg.value = 'Category saved successfully!'
        showToast()
        categoryViewMode.value = 'list'
        newCat.value = { name: '', code: '', parentId: '', subcategories: '', description: '', status: true, image: null }
    } catch (e) {
        console.error(e)
        toastMsg.value = 'Error saving category.'
        showToast()
    }
}

const handleDeleteSubcategory = async (id) => {
    if (confirm('Delete this sub-category?')) {
        await retailStore.removeSubcategory(id)
        toastMsg.value = 'Sub-category removed!'
        showToast()
    }
}

`;

content = content.replace(
  /const newCategoryName = ref\(''\)/,
  variablesScript + '\nconst newCategoryName = ref(\'\')'
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('Categories Management page implemented');
