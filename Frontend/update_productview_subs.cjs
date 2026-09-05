const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'views', 'ProductView.vue');
let content = fs.readFileSync(filePath, 'utf8');

// Add Subcategory to form and editForm state if not there
content = content.replace(
    /const form = ref\(\{([\s\S]*?)\}\)/,
    (match, inner) => {
        if (!inner.includes('subcategory:')) {
            return `const form = ref({${inner}, subcategory: ''})`;
        }
        return match;
    }
);

content = content.replace(
    /const editForm = ref\(\{([\s\S]*?)\}\)/,
    (match, inner) => {
        if (!inner.includes('subcategory:')) {
            return `const editForm = ref({${inner}, subcategory: ''})`;
        }
        return match;
    }
);


// Replace the category select in ADD form with Category + Subcategory
const addCatReplacement = `
                    <div class="col-md-6">
                      <label class="pv-label">Category</label>
                      <select class="pv-input" v-model="form.category">
                        <option value="">Select Category</option>
                        <option v-for="cat in store.categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">Sub-Category</label>
                      <select class="pv-input" v-model="form.subcategory" :disabled="!form.category">
                        <option value="">Select Sub-Category</option>
                        <option v-for="sub in store.subcategories.filter(s => {
                            const parentCat = store.categories.find(c => c.name === form.category);
                            return parentCat && s.category_id === parentCat.id;
                        })" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-6">
`;
content = content.replace(
    /<div class="col-md-6">\s*<label class="pv-label">Category<\/label>\s*<select class="pv-input" v-model="form\.category">[\s\S]*?<\/select>\s*<\/div>\s*<div class="col-md-6">/m,
    addCatReplacement
);

// Replace the category select in EDIT form with Category + Subcategory
const editCatReplacement = `
                    <div class="col-md-6">
                      <label class="pv-label">Category</label>
                      <select class="pv-input" v-model="editForm.category">
                          <option value="">Select Category</option>
                          <option v-for="cat in store.categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">Sub-Category</label>
                      <select class="pv-input" v-model="editForm.subcategory" :disabled="!editForm.category">
                          <option value="">Select Sub-Category</option>
                          <option v-for="sub in store.subcategories.filter(s => {
                              const parentCat = store.categories.find(c => c.name === editForm.category);
                              return parentCat && s.category_id === parentCat.id;
                          })" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
`;
content = content.replace(
    /<div class="col-md-6">\s*<label class="pv-label">Category<\/label>\s*<select class="pv-input" v-model="editForm\.category">[\s\S]*?<\/select>\s*<\/div>\s*<div class="col-md-6">/m,
    editCatReplacement
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('ProductView.vue updated with subcategory dropdowns');
