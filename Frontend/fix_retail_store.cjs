const fs = require('fs');

let content = fs.readFileSync('src/stores/retail.js', 'utf8');

const oldFetchProducts = `  const fetchProducts = async () => {
    try {
      const response = await api.get('/products')
      products.value = response.data
    } catch (error) {
      console.error('Error fetching products', error)
    }
  }`;

const newFetchProducts = `  const fetchProducts = async () => {
    try {
      const response = await api.get('/products')
      products.value = response.data.map(p => ({
        id: p.id,
        name: p.name,
        category: p.category ? p.category.name : '',
        sku: p.sku,
        barcode: p.barcode,
        cost: p.cost_price ? Number(p.cost_price) : 0,
        price: p.selling_price ? Number(p.selling_price) : 0,
        stock: p.stock ? Number(p.stock) : 0,
        minStock: p.min_stock ? Number(p.min_stock) : 0,
        unit: p.unit || 'pcs',
        description: p.description || '',
        active: p.is_active !== 0 && p.is_active !== false,
        image: p.image || ''
      }))
    } catch (error) {
      console.error('Error fetching products', error)
    }
  }`;

content = content.replace(oldFetchProducts, newFetchProducts);
fs.writeFileSync('src/stores/retail.js', content);
console.log('retail.js updated');
