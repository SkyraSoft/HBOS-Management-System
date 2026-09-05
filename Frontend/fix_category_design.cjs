const fs = require('fs');

let pv = fs.readFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', 'utf8');

// 1. Add state variable
pv = pv.replace("const categoryViewMode = ref('list')", "const categoryViewMode = ref('list')\nconst categoryAddType = ref('category')");

// 2. Add handleCreateSubcategory
const handleAddSubPattern = `const handleAddSubcategory = async () => {
    if (!newSubcategoryName.value || !newSubcategoryCatId.value) return
    await store.addSubcategory({ name: newSubcategoryName.value, category_id: newSubcategoryCatId.value })
    newSubcategoryName.value = ''
    newSubcategoryCatId.value = ''
}`;

const handleAddSubReplacement = `const handleAddSubcategory = async () => {
    if (!newSubcategoryName.value || !newSubcategoryCatId.value) return
    await store.addSubcategory({ name: newSubcategoryName.value, category_id: newSubcategoryCatId.value })
    newSubcategoryName.value = ''
    newSubcategoryCatId.value = ''
}
const handleCreateSubcategory = async () => {
    if (!newSubcategoryName.value.trim() || !newSubcategoryCatId.value) {
        showToast('Please fill in all subcategory fields.');
        return;
    }
    const res = await store.addSubcategory({ name: newSubcategoryName.value.trim(), category_id: newSubcategoryCatId.value })
    if (res.success) {
        newSubcategoryName.value = ''
        newSubcategoryCatId.value = ''
        showToast('Subcategory added successfully.');
    } else {
        showToast(res.message || 'Failed to add subcategory.');
    }
}`;

pv = pv.replace(handleAddSubPattern, handleAddSubReplacement);

// 3. Replace template block
const startTemplateIndex = pv.indexOf('<div v-if="categoryViewMode === \'add\'" class="pv-add-category-view">');
// Find closing </div> for pv-add-category-view
// We can find the next </section> because it's right before it
const endSectionIndex = pv.indexOf('</section>', startTemplateIndex);
// Wait, we want to replace from startTemplateIndex to the matching closing div right before </section>
// Let's find the closing div of pv-add-category-view.
// Since the original was:
/*
            <div v-if="categoryViewMode === 'add'" class="pv-add-category-view">
                ...
                </div>
            </div>
        </section>
*/
// Let's search for the exact block using regex
const viewPattern = /<div v-if="categoryViewMode === 'add'" class="pv-add-category-view">[\s\S]*?<\/div>\s*<\/div>\s*<\/section>/;

const newTemplateBlock = `<div v-if="categoryViewMode === 'add'" class="pv-add-category-view" style="animation: fadeIn 0.3s ease;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; margin-bottom: 24px;">
                    <div>
                        <div class="pv-breadcrumb-area" style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.85rem; margin-bottom: 8px;">
                            <span>Product</span> <span style="font-size: 10px;">&gt;</span> <span style="cursor: pointer; color: #2563eb; font-weight: 500;" @click="categoryViewMode = 'list'">Categories</span> <span style="font-size: 10px;">&gt;</span> <span style="color: #1e293b; font-weight: 600;">Add New</span>
                        </div>
                        <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.025em;">Create Category / Subcategory</h1>
                    </div>
                    <div>
                        <button class="secondary-btn" @click="categoryViewMode = 'list'" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            Back to List
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: row; gap: 24px; margin-top: 24px; flex-wrap: wrap;">
                    <!-- Left Tab: Create Parent Category -->
                    <div style="flex: 1; min-width: 320px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02); transition: all 0.3s; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; min-height: 380px;" :style="categoryAddType === 'category' ? 'border-color: #2563eb; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.06);' : 'opacity: 0.85;'">
                        <div v-if="categoryAddType === 'category'" style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #2563eb, #3b82f6);"></div>
                        
                        <div>
                            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; cursor: pointer;" @click="categoryAddType = 'category'">
                                <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" :style="categoryAddType === 'category' ? 'background: #eff6ff; color: #2563eb;' : 'background: #f8fafc; color: #64748b;'">
                                    <i class="bi bi-folder-plus" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #1e293b;">Parent Category</h3>
                                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Create a top-level product category</p>
                                </div>
                            </div>

                            <div style="margin-top: 32px;">
                                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px;">Category Name *</label>
                                <input type="text" class="pv-input" v-model="newCategoryName" placeholder="e.g., Electronics, Grocery, Apparel" style="width: 100%; padding: 12px 16px; border: 1px solid #d8deea; border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; background: #fff;" @focus="categoryAddType = 'category'" />
                            </div>
                        </div>

                        <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                            <button class="primary-btn" @click="addCategory" style="width: 100%; padding: 12px 24px; border-radius: 10px; background: #2563eb; color: #fff; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);">
                                <i class="bi bi-plus-lg"></i> Create Category
                            </button>
                        </div>
                    </div>

                    <!-- Right Tab: Create Subcategory -->
                    <div style="flex: 1; min-width: 320px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02); transition: all 0.3s; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; min-height: 380px;" :style="categoryAddType === 'subcategory' ? 'border-color: #2563eb; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.06);' : 'opacity: 0.85;'">
                        <div v-if="categoryAddType === 'subcategory'" style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #2563eb, #3b82f6);"></div>
                        
                        <div>
                            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; cursor: pointer;" @click="categoryAddType = 'subcategory'">
                                <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" :style="categoryAddType === 'subcategory' ? 'background: #eff6ff; color: #2563eb;' : 'background: #f8fafc; color: #64748b;'">
                                    <i class="bi bi-diagram-3" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #1e293b;">Subcategory</h3>
                                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Create a subcategory under a parent category</p>
                                </div>
                            </div>

                            <div style="margin-top: 32px;">
                                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px;">Parent Category *</label>
                                <select class="pv-input" v-model="newSubcategoryCatId" style="width: 100%; padding: 12px 16px; border: 1px solid #d8deea; border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; background: #fff;" @focus="categoryAddType = 'subcategory'">
                                    <option value="">Select Parent Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>

                            <div style="margin-top: 20px;">
                                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px;">Subcategory Name *</label>
                                <input type="text" class="pv-input" v-model="newSubcategoryName" placeholder="e.g., Laptops, Soft Drinks, Shirts" style="width: 100%; padding: 12px 16px; border: 1px solid #d8deea; border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; background: #fff;" @focus="categoryAddType = 'subcategory'" />
                            </div>
                        </div>

                        <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                            <button class="primary-btn" @click="handleCreateSubcategory" style="width: 100%; padding: 12px 24px; border-radius: 10px; background: #2563eb; color: #fff; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);">
                                <i class="bi bi-plus-lg"></i> Create Subcategory
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>`;

pv = pv.replace(viewPattern, newTemplateBlock);

fs.writeFileSync('c:/xampp/htdocs/HBOS/Frontend/src/views/ProductView.vue', pv);
