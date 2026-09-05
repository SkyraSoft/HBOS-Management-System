import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useRetailStore = defineStore('retail', () => {
  // --- STATE ---
  
  // Shared Products List
  const products = ref([])
  const categories = ref([])
  const subcategories = ref([])
  const brands = ref([])

  // Active POS Cart
  const cart = ref([])
  const sales = ref([])
  const cartDiscount = ref(0)
  
  // --- GETTERS ---
  const cartSubtotal = computed(() => {
    return cart.value.reduce((total, item) => total + (item.price * item.quantity), 0)
  })

  const cartTotal = computed(() => {
    return cartSubtotal.value - cartDiscount.value
  })

  // --- ACTIONS (API Integrated) ---
  
  // Fetch initial data
  const fetchProducts = async () => {
    try {
      const response = await api.get('/products')
      products.value = response.data.map((p, index) => {
        let catName = '';
        if (p.category && typeof p.category === 'object') {
          catName = p.category.name || '';
        } else if (typeof p.category === 'string') {
          catName = p.category;
        } else if (p.category_name) {
          catName = p.category_name;
        }

        const catId = p.category_id !== undefined && p.category_id !== null 
          ? p.category_id 
          : (p.category && typeof p.category === 'object' ? p.category.id : null);

        return {
          id: p.id !== undefined && p.id !== null ? p.id : (index + 1),
          name: p.name || 'Unnamed Product',
          categoryId: catId,
          category: catName,
          subcategory: p.subcategory ? (typeof p.subcategory === 'object' ? p.subcategory.name : p.subcategory) : '',
          brand: p.brand ? (typeof p.brand === 'object' ? p.brand.name : p.brand) : '',
          sku: p.sku || '',
          barcode: p.barcode || '',
          cost: p.cost_price ? Number(p.cost_price) : (p.cost ? Number(p.cost) : 0),
          price: p.selling_price ? Number(p.selling_price) : (p.price ? Number(p.price) : 0),
          stock: p.stock !== undefined && p.stock !== null ? Number(p.stock) : 0,
          minStock: p.min_stock ? Number(p.min_stock) : (p.minStock ? Number(p.minStock) : 0),
          unit: p.unit || 'pcs',
          description: p.description || '',
          active: p.is_active !== 0 && p.is_active !== false,
          image: p.image || '',
          hasVariations: Boolean(p.has_variations || (p.attributes && p.attributes.length > 0)),
          attributes: p.attributes ? (typeof p.attributes === 'string' ? JSON.parse(p.attributes) : p.attributes) : [],
          variations: p.variations ? (typeof p.variations === 'string' ? JSON.parse(p.variations) : p.variations) : []
        };
      })
    } catch (error) {
      console.error('Error fetching products', error)
    }
  }

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

  const fetchSales = async () => {
    try {
      const response = await api.get('/sales')
      sales.value = response.data
    } catch (error) {
      console.error('Error fetching sales', error)
    }
  }

  const addCategory = async (payload) => {
    try {
      const data = typeof payload === 'object' ? payload : { name: payload }
      const response = await api.post('/categories', data)
      categories.value.unshift(response.data)
      return { success: true, data: response.data }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding category' }
    }
  }

  const removeCategory = async (id) => {
    try {
      await api.delete(`/categories/${id}`)
      categories.value = categories.value.filter(c => c.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }

  const updateCategory = async (id, payload) => {
    try {
      const data = typeof payload === 'object' ? payload : { name: payload }
      const response = await api.put(`/categories/${id}`, data)
      const index = categories.value.findIndex(c => c.id === id)
      if (index !== -1) {
        categories.value[index] = { ...categories.value[index], ...response.data }
      }
      return { success: true, data: response.data }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error updating category' }
    }
  }

  const updateSubcategory = async (id, payload) => {
    try {
      const data = typeof payload === 'object' ? payload : { name: payload }
      const response = await api.put(`/subcategories/${id}`, data)
      const index = subcategories.value.findIndex(s => s.id === id)
      if (index !== -1) {
        subcategories.value[index] = { ...subcategories.value[index], ...response.data }
      }
      return { success: true, data: response.data }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error updating subcategory' }
    }
  }

  const addBrand = async (payload) => {
    try {
      const data = typeof payload === 'object' ? payload : { name: payload }
      const response = await api.post('/brands', data)
      brands.value.unshift(response.data)
      return { success: true, data: response.data }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding brand' }
    }
  }

  const removeBrand = async (id) => {
    try {
      await api.delete(`/brands/${id}`)
      brands.value = brands.value.filter(b => b.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }

  const addSubcategory = async (payload) => {
    try {
      const data = typeof payload === 'object' ? payload : { name: payload }
      const response = await api.post('/subcategories', data)
      subcategories.value.unshift(response.data)
      return { success: true, data: response.data }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error adding subcategory' }
    }
  }

  const removeSubcategory = async (id) => {
    try {
      await api.delete(`/subcategories/${id}`)
      subcategories.value = subcategories.value.filter(s => s.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }

  const getSubcategoriesByCategoryId = (catIdOrName) => {
    if (!catIdOrName) return subcategories.value
    return subcategories.value.filter(s => {
      if (s.category_id && String(s.category_id) === String(catIdOrName)) return true
      if (s.category && (String(s.category.id) === String(catIdOrName) || s.category.name === catIdOrName)) return true
      const cat = categories.value.find(c => c.name === catIdOrName || String(c.id) === String(catIdOrName))
      if (cat && s.category_id && String(s.category_id) === String(cat.id)) return true
      return false
    })
  }

  const deleteProduct = async (id) => {
    try {
      await api.delete(`/products/${id}`)
      products.value = products.value.filter(p => p.id !== id)
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false }
    }
  }

  const updateProduct = async (id, updatedData) => {
    try {
      let catId = categories.value.find(c => c.name === updatedData.category)?.id

      // Map frontend fields to backend fields expected by ProductController
      let brandId = brands.value.find(b => b.name === updatedData.brand)?.id
      let subcatId = subcategories.value.find(s => s.name === updatedData.subcategory)?.id

      const formData = new FormData()
      formData.append('_method', 'PUT')
      formData.append('name', updatedData.name)
      if (catId) formData.append('category_id', catId)
      if (brandId) formData.append('brand_id', brandId)
      if (subcatId) formData.append('subcategory_id', subcatId)
      if (updatedData.brand !== undefined) formData.append('brand', updatedData.brand || '')
      
      formData.append('sku', updatedData.sku || '')
      formData.append('description', updatedData.description || '')
      
      const cost = updatedData.cost !== undefined ? updatedData.cost : updatedData.purchasePrice
      formData.append('cost_price', cost)
      
      const price = updatedData.price !== undefined ? updatedData.price : updatedData.sellingPrice
      formData.append('selling_price', price)
      
      formData.append('stock', updatedData.stock || 0)
      formData.append('min_stock', updatedData.minStock || 0)
      formData.append('max_stock', updatedData.maxStock || 0)
      formData.append('unit', updatedData.unit || 'pcs')
      formData.append('is_active', updatedData.active ? 1 : 0)

      if (updatedData.imageFile && updatedData.imageFile instanceof File) {
        formData.append('image', updatedData.imageFile)
      } else if (updatedData.image_url && typeof updatedData.image_url === 'string' && updatedData.image_url.startsWith('data:')) {
        formData.append('image_base64', updatedData.image_url)
      } else if (updatedData.image_url || updatedData.imagePreview || updatedData.image) {
        formData.append('image_url', updatedData.image_url || updatedData.imagePreview || updatedData.image)
      }
      
      const response = await api.post(`/products/${id}`, formData)
      
      const index = products.value.findIndex(p => p.id === id)
      if (index !== -1) {
        // We remap the backend response back to our frontend schema
        const p = response.data
        products.value[index] = {
            id: p.id,
            name: p.name,
            sku: p.sku,
            category: p.category?.name || updatedData.category || '',
            subcategory: p.subcategory?.name || updatedData.subcategory || '',
            brand: p.brand?.name || updatedData.brand || '',
            description: p.description,
            cost: Number(p.cost_price),
            price: Number(p.selling_price),
            stock: Number(p.stock),
            minStock: Number(p.min_stock),
            maxStock: Number(p.max_stock),
            unit: p.unit,
            active: Boolean(p.is_active !== false),
            image: p.image || '',
            hasVariations: Boolean(updatedData.hasVariations),
            attributes: updatedData.attributes || [],
            variations: updatedData.variations || []
        }
      }
      return { success: true }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error updating product' }
    }
  }

  const addProduct = async (newProduct) => {
    try {
      const formData = new FormData()
      formData.append('name', newProduct.name)
      
      let catId = categories.value.find(c => c.name === newProduct.category)?.id
      if (catId) formData.append('category_id', catId)
      if (newProduct.category) formData.append('category', newProduct.category)

      let brandId = brands.value.find(b => b.name === newProduct.brand)?.id
      if (brandId) formData.append('brand_id', brandId)
      if (newProduct.brand) formData.append('brand', newProduct.brand)

      let subcatId = subcategories.value.find(s => s.name === newProduct.subcategory)?.id
      if (subcatId) formData.append('subcategory_id', subcatId)
      if (newProduct.subcategory) formData.append('subcategory', newProduct.subcategory)
      
      formData.append('sku', newProduct.sku || '')
      formData.append('description', newProduct.description || '')
      formData.append('cost_price', Number(newProduct.cost) || 0)
      formData.append('selling_price', Number(newProduct.price) || 0)
      formData.append('stock', Number(newProduct.stock) || 0)
      formData.append('min_stock', newProduct.minStock || 0)
      if (newProduct.maxStock) formData.append('max_stock', newProduct.maxStock)
      formData.append('unit', newProduct.unit || 'pcs')
      formData.append('is_active', newProduct.active ? 1 : 0)

      if (newProduct.imageFile && newProduct.imageFile instanceof File) {
        formData.append('image', newProduct.imageFile)
      } else if (newProduct.image_url && typeof newProduct.image_url === 'string' && newProduct.image_url.startsWith('data:')) {
        formData.append('image_base64', newProduct.image_url)
      } else if (newProduct.image_url || newProduct.imagePreview || newProduct.image) {
        formData.append('image_url', newProduct.image_url || newProduct.imagePreview || newProduct.image)
      }

      const response = await api.post('/products', formData)
      const p = response.data
      
      products.value.push({
          id: p.id,
          name: p.name,
          sku: p.sku,
          category: p.category?.name || newProduct.category || '',
          subcategory: p.subcategory?.name || newProduct.subcategory || '',
          brand: p.brand?.name || newProduct.brand || '',
          description: p.description,
          cost: Number(p.cost_price),
          price: Number(p.selling_price),
          stock: Number(p.stock),
          minStock: Number(p.min_stock),
          maxStock: Number(p.max_stock),
          unit: p.unit,
          active: Boolean(p.is_active !== false),
          image: p.image || '',
          hasVariations: Boolean(newProduct.hasVariations),
          attributes: newProduct.attributes || [],
          variations: newProduct.variations || []
      })
      return { success: true }
    } catch (error) {
      console.error(error)
      let errMsg = error.response?.data?.message || 'Error adding product'
      if (error.response?.data?.errors) {
        errMsg = Object.values(error.response.data.errors).flat().join(' ')
      }
      return { success: false, message: errMsg }
    }
  }

  const addToCart = (product, packType = 'Single Pack', qty = 1, customPrice = null) => {
    const pId = String(product.id);
    const itemPack = packType || 'Single Pack';
    const cartItemId = `${pId}_${itemPack.replace(/\s+/g, '_')}`;
    
    // Check if item with same cartItemId or same product ID exists
    const existingIndex = cart.value.findIndex(item => String(item.cartItemId || item.id) === cartItemId);
    const sameProductIndex = cart.value.findIndex(item => String(item.productId || item.id).split('_')[0] === pId);
    
    const maxStock = product.stock !== undefined && product.stock !== null ? Number(product.stock) : 999999;
    let packPrice = customPrice !== null && customPrice !== undefined ? Number(customPrice) : Number(product.price || 0);
    const addQuantity = Math.max(1, Number(qty) || 1);

    if (existingIndex !== -1) {
      const existingItem = cart.value[existingIndex];
      existingItem.price = packPrice;
      existingItem.packType = itemPack;
      if (maxStock <= 0 || (existingItem.quantity + addQuantity) <= maxStock) {
        existingItem.quantity += addQuantity;
      } else {
        return { success: false, message: `Only ${maxStock} units available.` };
      }
    } else if (sameProductIndex !== -1) {
      // If the product was in cart under a different pack type, update it to the newly selected pack type & price!
      const item = cart.value[sameProductIndex];
      item.cartItemId = cartItemId;
      item.packType = itemPack;
      item.price = packPrice;
      item.quantity = addQuantity;
    } else {
      if (maxStock > 0 || product.stock === undefined) {
        cart.value.push({
          cartItemId: cartItemId,
          id: cartItemId,
          productId: pId,
          name: product.name,
          sku: product.sku || '',
          barcode: product.barcode || '',
          price: packPrice,
          unitPrice: Number(product.price) || 0,
          cost: Number(product.cost) || 0,
          stock: maxStock,
          packType: itemPack,
          unit: product.unit || 'pcs',
          image: product.image || '',
          quantity: addQuantity
        });
      } else {
        return { success: false, message: 'Out of stock.' };
      }
    }
    return { success: true };
  }

  const updateCartQuantity = (cartItemId, newQuantity) => {
    const itemIndex = cart.value.findIndex(i => String(i.cartItemId || i.id) === String(cartItemId));
    if (itemIndex !== -1) {
      const item = cart.value[itemIndex]
      const product = products.value.find(p => String(p.id) === String(item.productId || item.id));
      const maxStock = product ? (product.stock !== undefined ? Number(product.stock) : 999999) : (item.stock || 999999);
      
      if (newQuantity <= 0) {
        removeFromCart(cartItemId)
      } else if (newQuantity <= maxStock) {
        item.quantity = newQuantity
      } else {
        return { success: false, message: `Only ${maxStock} units available.` }
      }
    }
    return { success: true }
  }

  const removeFromCart = (cartItemId) => {
    cart.value = cart.value.filter(item => String(item.cartItemId || item.id) !== String(cartItemId));
  }

  const completeSale = async (paymentMethod, customerId = null, customerName = 'Walk-in Customer') => {
    if (cart.value.length === 0) return { success: false, message: 'Cart is empty.' };

    try {
        const subtotal = Number(cartSubtotal.value) || 0;
        const discount = Number(cartDiscount.value) || 0;
        const tax = Math.round(subtotal * 0.1);
        const total = Math.max(0, subtotal - discount + tax);

        const itemsPayload = cart.value.map(item => {
            const rawId = String(item.productId || item.id);
            const cleanId = rawId.includes('_') ? rawId.split('_')[0] : rawId;
            const numericProductId = parseInt(cleanId, 10);
            const itemQty = Math.max(1, parseInt(item.quantity, 10) || 1);
            const unitPrice = Number(item.price) || 0;
            return {
                product_id: numericProductId,
                quantity: itemQty,
                unit_price: unitPrice,
                discount: 0,
                total: unitPrice * itemQty
            };
        });

        const nowIso = new Date().toISOString();
        const invoiceNo = `INV-${String(Date.now()).slice(-8)}`;
        const payload = {
            customer_id: customerId ? parseInt(customerId, 10) : null,
            invoice_number: invoiceNo,
            date: nowIso.split('T')[0],
            subtotal: subtotal,
            discount: discount,
            tax: tax,
            total: total,
            payment_method: paymentMethod || 'Cash',
            items: itemsPayload
        };

        const currentCartItems = [...cart.value];
        const response = await api.post('/sales', payload);
        
        // Refresh products to get updated stock
        await fetchProducts();
        
        // Clear Cart
        cart.value = [];
        cartDiscount.value = 0;

        return { 
          success: true, 
          invoice: {
            invoiceNumber: response.data?.invoice_number || invoiceNo,
            customer: customerName || 'Walk-in Customer',
            date: response.data?.created_at || response.data?.date || nowIso,
            paymentMethod: payload.payment_method,
            subtotal: subtotal,
            discount: discount,
            tax: tax,
            total: total,
            items: currentCartItems
          } 
        };
    } catch (error) {
        console.error('Error completing sale:', error);
        let msg = error.response?.data?.error || error.response?.data?.message || 'Error completing sale';
        if (error.response?.data?.errors) {
            msg = Object.values(error.response.data.errors).flat().join(' ');
        }
        return { success: false, message: msg };
    }
  }

  return {
    products,
    categories,
    subcategories,
    brands,
    cart,
    sales,
    cartSubtotal,
    cartDiscount,
    cartTotal,
    fetchProducts,
    fetchCategories,
    fetchSales,
    addProduct,
    addToCart,
    updateProduct,
    deleteProduct,
    updateCartQuantity,
    removeFromCart,
    completeSale,
    addCategory,
    removeCategory,
    deleteCategory: removeCategory,
    updateCategory,
    fetchSubcategories,
    fetchBrands,
    addSubcategory,
    updateSubcategory,
    removeSubcategory,
    addBrand,
    removeBrand,
    getSubcategoriesByCategoryId
  }
})
