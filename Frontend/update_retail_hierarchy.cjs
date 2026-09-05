const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src', 'stores', 'retail.js');
let content = fs.readFileSync(filePath, 'utf8');

// 1. Add state variables for brands and subcategories
content = content.replace(
  'const categories = ref([])',
  'const categories = ref([])\n  const subcategories = ref([])\n  const brands = ref([])'
);

// 2. Fetch data methods
const fetchCategoryLogic = `
  const fetchCategories = async () => {
    try {
      const response = await api.get('/categories')
      categories.value = response.data
    } catch (error) {
      console.error('Error fetching categories', error)
    }
  }

  const fetchSubcategories = async () => {
    try {
      const response = await api.get('/subcategories')
      subcategories.value = response.data
    } catch (error) {
      console.error('Error fetching subcategories', error)
    }
  }

  const fetchBrands = async () => {
    try {
      const response = await api.get('/brands')
      brands.value = response.data
    } catch (error) {
      console.error('Error fetching brands', error)
    }
  }
`;
content = content.replace(/const fetchCategories = async \(\) => \{[\s\S]*?\}\n  \}/, fetchCategoryLogic.trim());

// 3. Add Brand and Subcategory CRUD methods
const categoryCrud = `
  const addCategory = async (payload) => {
    try {
      const name = typeof payload === 'object' ? payload.name : payload;
      const response = await api.post('/categories', { name })
      categories.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding category' }
    }
  }

  const removeCategory = async (id) => {
    try {
      await api.delete(\`/categories/\${id}\`)
      categories.value = categories.value.filter(c => c.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }

  const updateCategory = (id, newName) => {
    const category = categories.value.find(c => c.id === id)
    if (category) {
      category.name = newName
    }
  }

  const addBrand = async (payload) => {
    try {
      const name = typeof payload === 'object' ? payload.name : payload;
      const response = await api.post('/brands', { name })
      brands.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding brand' }
    }
  }

  const removeBrand = async (id) => {
    try {
      await api.delete(\`/brands/\${id}\`)
      brands.value = brands.value.filter(b => b.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }

  const addSubcategory = async (payload) => {
    try {
      const response = await api.post('/subcategories', payload)
      subcategories.value.unshift(response.data)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding subcategory' }
    }
  }

  const removeSubcategory = async (id) => {
    try {
      await api.delete(\`/subcategories/\${id}\`)
      subcategories.value = subcategories.value.filter(s => s.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }
`;
content = content.replace(/const addCategory = async \([\s\S]*?const updateCategory = \(id, newName\) => \{[\s\S]*?\}\n  \}/, categoryCrud.trim());

// 4. Update fetchProducts
content = content.replace(
  'category: p.category ? p.category.name : \'\',',
  'category: p.category ? p.category.name : \'\',\n        subcategory: p.subcategory ? p.subcategory.name : \'\',\n        brand: p.brand ? p.brand.name : \'\','
);

// 5. Update updateProduct payload mapping
content = content.replace(
  'const payload = {\n        name: updatedData.name,\n        category_id: catId || null,',
  `let brandId = brands.value.find(b => b.name === updatedData.brand)?.id\n      let subcatId = subcategories.value.find(s => s.name === updatedData.subcategory)?.id\n\n      const payload = {\n        name: updatedData.name,\n        category_id: catId || null,\n        brand_id: brandId || null,\n        subcategory_id: subcatId || null,`
);

// 6. Update updateProduct response mapping
content = content.replace(
  'category: p.category?.name || updatedData.category || \'\',',
  'category: p.category?.name || updatedData.category || \'\',\n            subcategory: p.subcategory?.name || updatedData.subcategory || \'\',\n            brand: p.brand?.name || updatedData.brand || \'\','
);

// 7. Update addProduct payload mapping
content = content.replace(
  'formData.append(\'category_id\', catId)\n      } else {\n        formData.append(\'category_id\', \'\')\n      }',
  `formData.append('category_id', catId)\n      } else {\n        formData.append('category_id', '')\n      }\n\n      let brandId = brands.value.find(b => b.name === newProduct.brand)?.id\n      if (brandId) {\n        formData.append('brand_id', brandId)\n      } else {\n        formData.append('brand_id', '')\n      }\n\n      let subcatId = subcategories.value.find(s => s.name === newProduct.subcategory)?.id\n      if (subcatId) {\n        formData.append('subcategory_id', subcatId)\n      } else {\n        formData.append('subcategory_id', '')\n      }`
);

// 8. Update addProduct response mapping
content = content.replace(
  'category: p.category?.name || newProduct.category || \'\',',
  'category: p.category?.name || newProduct.category || \'\',\n          subcategory: p.subcategory?.name || newProduct.subcategory || \'\',\n          brand: p.brand?.name || newProduct.brand || \'\','
);

// 9. Return the new states and methods
content = content.replace(
  'return {\n    products,\n    categories,',
  'return {\n    products,\n    categories,\n    subcategories,\n    brands,'
);

content = content.replace(
  'fetchCategories,\n    fetchSales,\n    addCategory,\n    removeCategory,\n    updateCategory,',
  'fetchCategories,\n    fetchSubcategories,\n    fetchBrands,\n    fetchSales,\n    addCategory,\n    removeCategory,\n    updateCategory,\n    addBrand,\n    removeBrand,\n    addSubcategory,\n    removeSubcategory,'
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('retail.js updated successfully!');
