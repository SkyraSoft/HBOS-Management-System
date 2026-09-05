const fs = require('fs');

let content = fs.readFileSync('src/stores/retail.js', 'utf8');

const oldAdd = `  const addProduct = async (newProduct) => {
    try {
      const payload = {
        name: newProduct.name,
        category_id: categories.value.find(c => c.name === newProduct.category)?.id,
        sku: newProduct.sku,
        description: newProduct.description,
        cost_price: newProduct.cost !== undefined ? newProduct.cost : newProduct.purchasePrice,
        selling_price: newProduct.price !== undefined ? newProduct.price : newProduct.sellingPrice,
        stock: newProduct.stock,
        min_stock: newProduct.minStock,
        max_stock: newProduct.maxStock,
        unit: newProduct.unit,
        is_active: newProduct.active
      }

      const response = await api.post('/products', payload)
      const p = response.data
      
      products.value.push({
          id: p.id,
          name: p.name,
          sku: p.sku,
          category: p.category?.name || '',
          description: p.description,
          cost: Number(p.cost_price),
          price: Number(p.selling_price),
          stock: Number(p.stock),
          minStock: Number(p.min_stock),
          maxStock: Number(p.max_stock),
          unit: p.unit,
          active: p.is_active !== 0 && p.is_active !== false,
          image: p.image || ''
      })

      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding product' }
    }
  }`;

const newAdd = `  const addProduct = async (newProduct) => {
    try {
      const formData = new FormData()
      formData.append('name', newProduct.name)
      const catId = categories.value.find(c => c.name === newProduct.category)?.id
      if (catId) formData.append('category_id', catId)
      formData.append('sku', newProduct.sku || '')
      formData.append('description', newProduct.description || '')
      formData.append('cost_price', newProduct.cost !== undefined ? newProduct.cost : (newProduct.purchasePrice || 0))
      formData.append('selling_price', newProduct.price !== undefined ? newProduct.price : (newProduct.sellingPrice || 0))
      formData.append('stock', newProduct.stock || 0)
      formData.append('min_stock', newProduct.minStock || 0)
      if (newProduct.maxStock) formData.append('max_stock', newProduct.maxStock)
      formData.append('unit', newProduct.unit || 'pcs')
      formData.append('is_active', newProduct.active ? 1 : 0)

      if (newProduct.imageFile) {
        formData.append('image', newProduct.imageFile)
      }

      const response = await api.post('/products', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      const p = response.data
      
      products.value.push({
          id: p.id,
          name: p.name,
          sku: p.sku,
          category: p.category?.name || '',
          description: p.description,
          cost: Number(p.cost_price),
          price: Number(p.selling_price),
          stock: Number(p.stock),
          minStock: Number(p.min_stock),
          maxStock: Number(p.max_stock),
          unit: p.unit,
          active: p.is_active !== 0 && p.is_active !== false,
          image: p.image || ''
      })

      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding product' }
    }
  }`;

content = content.replace(oldAdd, newAdd);

fs.writeFileSync('src/stores/retail.js', content);
console.log('retail.js updated');
