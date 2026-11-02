import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'
import { cashRegisterService } from '@/services/cashRegisterService'
import { getPendingOutboxQueue, createOfflineSale } from '@/services/offlinePosEngine'
import { syncCoordinator } from '@/services/syncCoordinator'
import { putStoreItem, bulkPutStoreItems, getStoreItems } from '@/services/localDb'

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

  // Cash Register Day-Open / Day-Close Session (REQ-0024, REQ-0025, SRT-GAP-017, SRT-GAP-018)
  const activeRegisterSession = ref(null)
  const isRegisterSessionLoading = ref(false)
  const hasActiveRegisterSession = computed(() => !!activeRegisterSession.value && activeRegisterSession.value.status === 'open')

  // Phase-8 Sync & Offline Outbox State (REQ-0030, SRT-GAP-025)
  const syncState = ref(syncCoordinator.getState())
  syncCoordinator.subscribe((s) => {
    syncState.value = s
  })
  const syncStatus = computed(() => syncState.value.status)
  const pendingOutboxCount = computed(() => syncState.value.pendingCount)
  
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
      const mapped = response.data.map((p, index) => {
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
          alternateBarcodes: Array.isArray(p.alternate_barcodes) ? p.alternate_barcodes : (p.barcodes ? p.barcodes.filter(b => b.barcode_type === 'alternate').map(b => b.barcode) : []),
          allowFractional: Boolean(p.allow_fractional),
          saleUnit: p.sale_unit || null,
          conversionFactor: p.conversion_factor ? Number(p.conversion_factor) : 1.0,
          wholesalePrice: p.wholesale_price !== null && p.wholesale_price !== undefined ? Number(p.wholesale_price) : null,
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
      products.value = mapped
      // Persist to IndexedDB for offline catalog persistence across reload
      try {
        await bulkPutStoreItems('products', mapped)
      } catch (dbErr) {
        console.warn('Failed to cache products to IndexedDB:', dbErr)
      }
    } catch (error) {
      console.error('Error fetching products from network, attempting offline IndexedDB fallback:', error)
      try {
        const cached = await getStoreItems('products')
        if (cached && cached.length > 0) {
          products.value = cached
        }
      } catch (dbErr) {
        console.error('Failed to load products from IndexedDB:', dbErr)
      }
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

  const updateBrand = async (id, payload) => {
    try {
      const data = typeof payload === 'object' ? payload : { name: payload }
      const response = await api.put(`/brands/${id}`, data)
      const updated = response.data?.data || response.data
      const idx = brands.value.findIndex(b => String(b.id) === String(id))
      if (idx !== -1) {
        brands.value[idx] = { ...brands.value[idx], ...updated }
      }
      return { success: true, data: updated }
    } catch (error) {
      console.error(error)
      return { success: false, message: error.response?.data?.message || 'Error updating brand' }
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
      if (updatedData.wholesalePrice !== undefined && updatedData.wholesalePrice !== null && updatedData.wholesalePrice !== '') {
        formData.append('wholesale_price', updatedData.wholesalePrice)
      } else if (updatedData.wholesale_price !== undefined) {
        formData.append('wholesale_price', updatedData.wholesale_price)
      }
      formData.append('allow_fractional', updatedData.allowFractional || updatedData.allow_fractional ? 1 : 0)
      if (updatedData.saleUnit || updatedData.sale_unit) {
        formData.append('sale_unit', updatedData.saleUnit || updatedData.sale_unit)
      }
      if (updatedData.conversionFactor || updatedData.conversion_factor) {
        formData.append('conversion_factor', updatedData.conversionFactor || updatedData.conversion_factor)
      }
      if (updatedData.alternateBarcodes || updatedData.alternate_barcodes) {
        const alts = updatedData.alternateBarcodes || updatedData.alternate_barcodes
        formData.append('alternate_barcodes', typeof alts === 'string' ? alts : JSON.stringify(alts))
      }
      
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
            allowFractional: Boolean(p.allow_fractional),
            saleUnit: p.sale_unit || null,
            conversionFactor: p.conversion_factor ? Number(p.conversion_factor) : 1.0,
            wholesalePrice: p.wholesale_price !== null && p.wholesale_price !== undefined ? Number(p.wholesale_price) : null,
            alternateBarcodes: Array.isArray(p.alternate_barcodes) ? p.alternate_barcodes : (p.barcodes ? p.barcodes.filter(b => b.barcode_type === 'alternate').map(b => b.barcode) : []),
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
      if (newProduct.wholesalePrice !== undefined && newProduct.wholesalePrice !== null && newProduct.wholesalePrice !== '') {
        formData.append('wholesale_price', newProduct.wholesalePrice)
      } else if (newProduct.wholesale_price !== undefined) {
        formData.append('wholesale_price', newProduct.wholesale_price)
      }
      formData.append('allow_fractional', newProduct.allowFractional || newProduct.allow_fractional ? 1 : 0)
      if (newProduct.saleUnit || newProduct.sale_unit) {
        formData.append('sale_unit', newProduct.saleUnit || newProduct.sale_unit)
      }
      if (newProduct.conversionFactor || newProduct.conversion_factor) {
        formData.append('conversion_factor', newProduct.conversionFactor || newProduct.conversion_factor)
      }
      if (newProduct.alternateBarcodes || newProduct.alternate_barcodes) {
        const alts = newProduct.alternateBarcodes || newProduct.alternate_barcodes
        formData.append('alternate_barcodes', typeof alts === 'string' ? alts : JSON.stringify(alts))
      }

      formData.append('stock', Number(newProduct.stock) || 0)
      formData.append('min_stock', newProduct.minStock || 0)
      if (newProduct.maxStock) formData.append('max_stock', newProduct.maxStock)
      formData.append('unit', newProduct.unit || 'pcs')
      const isActive = (newProduct.active !== false && newProduct.is_active !== false && newProduct.active !== 0 && newProduct.is_active !== 0) ? 1 : 0
      formData.append('is_active', isActive)

      if (newProduct.imageFile && newProduct.imageFile instanceof File) {
        formData.append('image', newProduct.imageFile)
      } else if (newProduct.image_url && typeof newProduct.image_url === 'string' && newProduct.image_url.startsWith('data:')) {
        formData.append('image_base64', newProduct.image_url)
      } else if (newProduct.image_url || newProduct.imagePreview || newProduct.image) {
        formData.append('image_url', newProduct.image_url || newProduct.imagePreview || newProduct.image)
      }

      const response = await api.post('/products', formData)
      const p = response.data
      
      const newProductItem = {
          id: p.id,
          name: p.name,
          sku: p.sku,
          category: p.category?.name || newProduct.category || '',
          subcategory: p.subcategory?.name || newProduct.subcategory || '',
          brand: p.brand?.name || newProduct.brand || '',
          description: p.description,
          allowFractional: Boolean(p.allow_fractional),
          saleUnit: p.sale_unit || null,
          conversionFactor: p.conversion_factor ? Number(p.conversion_factor) : 1.0,
          wholesalePrice: p.wholesale_price !== null && p.wholesale_price !== undefined ? Number(p.wholesale_price) : null,
          alternateBarcodes: Array.isArray(p.alternate_barcodes) ? p.alternate_barcodes : (p.barcodes ? p.barcodes.filter(b => b.barcode_type === 'alternate').map(b => b.barcode) : []),
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
      }

      products.value.push(newProductItem)
      try {
        await putStoreItem('products', newProductItem)
      } catch (cacheErr) {
        console.warn('Failed to cache new product in IndexedDB:', cacheErr)
      }

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
    const isFractional = Boolean(product.allowFractional || product.allow_fractional);
    const minQty = isFractional ? 0.001 : 1;
    const rawQty = Number(qty);
    const addQuantity = Math.max(minQty, isFractional ? (Math.round((rawQty || 1) * 1000) / 1000) : Math.round(rawQty || 1));

    if (existingIndex !== -1) {
      const existingItem = cart.value[existingIndex];
      existingItem.price = packPrice;
      existingItem.packType = itemPack;
      if (maxStock <= 0 || (existingItem.quantity + addQuantity) <= maxStock) {
        existingItem.quantity = isFractional 
          ? Math.round((existingItem.quantity + addQuantity) * 1000) / 1000 
          : (existingItem.quantity + addQuantity);
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
          alternateBarcodes: product.alternateBarcodes || [],
          allowFractional: isFractional,
          saleUnit: product.saleUnit || null,
          wholesalePrice: product.wholesalePrice !== undefined ? product.wholesalePrice : null,
          pricingMode: 'retail',
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
      const isFractional = Boolean(product?.allowFractional || product?.allow_fractional || item.allowFractional);
      const minQty = isFractional ? 0.001 : 1;

      const qStr = String(newQuantity !== undefined && newQuantity !== null ? newQuantity : '').trim();
      const qParts = qStr.split('.');
      if (qParts[1] && qParts[1].length > 3) {
        return { success: false, message: 'Quantity cannot exceed 3 decimal places.' };
      }

      const parsedQty = isFractional ? (Math.round(Number(newQuantity) * 1000) / 1000) : Math.round(Number(newQuantity));
      const maxStock = product ? (product.stock !== undefined ? Number(product.stock) : 999999) : (item.stock || 999999);
      
      if (parsedQty <= 0) {
        removeFromCart(cartItemId)
      } else if (parsedQty <= maxStock) {
        item.quantity = parsedQty
      } else {
        return { success: false, message: `Only ${maxStock} units available.` }
      }
    }
    return { success: true }
  }

  const removeFromCart = (cartItemId) => {
    cart.value = cart.value.filter(item => String(item.cartItemId || item.id) !== String(cartItemId));
  }

  const overrideItemPrice = (cartItemId, newPrice) => {
    const item = cart.value.find(i => String(i.cartItemId || i.id) === String(cartItemId));
    if (item) {
      item.price = Number(newPrice);
      item.priceOverridden = true;
      return { success: true };
    }
    return { success: false, message: 'Item not found in cart.' };
  }

  // --- HELD CARTS (LOCALSTORAGE BUFFER) ---
  const heldCarts = ref([])

  const getHeldStorageKey = () => {
    let bizId = localStorage.getItem('hbos_business_id');
    let branchId = 1;
    try {
      const userStr = localStorage.getItem('hbos_user');
      if (userStr) {
        const u = JSON.parse(userStr);
        if (!bizId && u.business_id) bizId = u.business_id;
        if (u.branch_id) branchId = u.branch_id;
      }
    } catch (e) {
      // ignore
    }
    bizId = bizId || '1';
    return `hbos_held_carts_${bizId}_${branchId}`;
  }

  const loadHeldCarts = () => {
    try {
      const key = getHeldStorageKey();
      const raw = localStorage.getItem(key);
      if (!raw) {
        heldCarts.value = [];
        return [];
      }
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        heldCarts.value = parsed.filter(c => c && c.held_id && Array.isArray(c.items));
      } else {
        heldCarts.value = [];
      }
    } catch (e) {
      console.warn('Failed to load held carts from localStorage:', e);
      heldCarts.value = [];
    }
    return heldCarts.value;
  }

  const saveHeldCartsToStorage = (list) => {
    try {
      const key = getHeldStorageKey();
      localStorage.setItem(key, JSON.stringify(list));
      heldCarts.value = [...list];
    } catch (e) {
      console.error('Failed to save held carts to localStorage:', e);
    }
  }

  const holdCart = (customerNote = '', customerId = null, customerName = 'Walk-in Customer') => {
    if (cart.value.length === 0) {
      return { success: false, message: 'Cart is empty. Nothing to hold.' };
    }

    // Reject holding if any item quantity exceeds scale 3 (no silent truncation)
    for (const item of cart.value) {
      const qStr = String(item.quantity !== undefined && item.quantity !== null ? item.quantity : '').trim();
      const qParts = qStr.split('.');
      if (qParts[1] && qParts[1].length > 3) {
        return { success: false, message: `Item '${item.name || item.id}' quantity exceeds maximum allowed scale of 3 decimal places.` };
      }
    }

    let userId = null;
    let userName = 'Cashier';
    let branchId = 1;
    let bizId = localStorage.getItem('hbos_business_id') || 1;
    try {
      const userStr = localStorage.getItem('hbos_user');
      if (userStr) {
        const u = JSON.parse(userStr);
        userId = u.id || null;
        userName = u.name || 'Cashier';
        if (u.branch_id) branchId = u.branch_id;
        if (u.business_id) bizId = u.business_id;
      }
    } catch (e) {}

    const heldRecord = {
      schema_version: 1,
      held_id: 'HOLD-' + Date.now() + '-' + Math.floor(Math.random() * 1000),
      held_at: new Date().toISOString(),
      customer_id: customerId,
      customer_name: customerName,
      customer_note: customerNote || '',
      held_by_user_id: userId,
      held_by_user_name: userName,
      branch_id: Number(branchId),
      business_id: Number(bizId),
      items: JSON.parse(JSON.stringify(cart.value)),
      subtotal: Number(cartSubtotal.value) || 0,
      discount: Number(cartDiscount.value) || 0,
      total: Number(cartTotal.value) || 0
    };

    const currentList = loadHeldCarts();
    currentList.unshift(heldRecord);
    saveHeldCartsToStorage(currentList);

    // Clear active cart
    cart.value = [];
    cartDiscount.value = 0;

    return { success: true, held_id: heldRecord.held_id, record: heldRecord };
  }

  const resumeCart = (heldId) => {
    const currentList = loadHeldCarts();
    const index = currentList.findIndex(c => String(c.held_id) === String(heldId));
    if (index === -1) {
      return { success: false, message: 'Held cart not found.' };
    }

    const heldRecord = currentList[index];
    // Reject resuming if any held item quantity exceeds scale 3 (no silent truncation)
    for (const item of heldRecord.items || []) {
      const qStr = String(item.quantity !== undefined && item.quantity !== null ? item.quantity : '').trim();
      const qParts = qStr.split('.');
      if (qParts[1] && qParts[1].length > 3) {
        return { success: false, message: `Held cart contains item with quantity '${qStr}' exceeding maximum allowed scale of 3 decimal places.` };
      }
    }

    cart.value = JSON.parse(JSON.stringify(heldRecord.items || []));
    cartDiscount.value = Number(heldRecord.discount) || 0;

    currentList.splice(index, 1);
    saveHeldCartsToStorage(currentList);

    return { success: true, record: heldRecord };
  }

  const discardCart = (heldId) => {
    const currentList = loadHeldCarts();
    const updated = currentList.filter(c => String(c.held_id) !== String(heldId));
    saveHeldCartsToStorage(updated);
    return { success: true };
  }

  const completeSale = async (paymentMethodOrOptions, customerId = null, customerName = 'Walk-in Customer', extraOptions = {}) => {
    if (cart.value.length === 0) return { success: false, message: 'Cart is empty.' };

    try {
        let paymentMethod = 'Cash';
        let options = { ...extraOptions };

        if (typeof paymentMethodOrOptions === 'object' && paymentMethodOrOptions !== null) {
            options = { ...paymentMethodOrOptions, ...options };
            paymentMethod = options.payment_method || options.paymentMethod || 'Cash';
            if (options.customerId !== undefined) customerId = options.customerId;
            if (options.customerName !== undefined) customerName = options.customerName;
        } else if (typeof paymentMethodOrOptions === 'string') {
            paymentMethod = paymentMethodOrOptions;
        }

        const subtotal = Number(cartSubtotal.value) || 0;
        const discount = Number(cartDiscount.value) || 0;
        const tax = Math.round(subtotal * 0.1);
        const total = Math.max(0, subtotal - discount + tax);

        const itemsPayload = cart.value.map(item => {
            const rawId = String(item.productId || item.id);
            const cleanId = rawId.includes('_') ? rawId.split('_')[0] : rawId;
            const numericProductId = parseInt(cleanId, 10);
            const isFractional = Boolean(item.allowFractional || item.allow_fractional);
            const minQty = isFractional ? 0.001 : 1;
            const rawQty = parseFloat(item.quantity);
            const itemQty = isFractional 
                ? Math.max(minQty, Math.round((rawQty || 1) * 1000) / 1000) 
                : Math.max(1, Math.round(rawQty || 1));
            const unitPrice = Number(item.price) || 0;
            const lineTotal = Math.round((unitPrice * itemQty) * 100) / 100;
            return {
                product_id: numericProductId,
                quantity: itemQty,
                pricing_mode: item.pricingMode || item.pricing_mode || 'retail',
                unit_price: unitPrice,
                discount: Number(item.discount || 0),
                total: lineTotal
            };
        });

        const nowIso = new Date().toISOString();
        const invoiceNo = options.invoice_number || `INV-${String(Date.now()).slice(-8)}`;
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

        if (options.payments && Array.isArray(options.payments) && options.payments.length > 0) {
            payload.payments = options.payments;
        }
        if (options.paid_amount !== undefined) {
            payload.paid_amount = Number(options.paid_amount);
        }
        if (options.financial_account_id) {
            payload.financial_account_id = options.financial_account_id;
        }
        if (options.override_authorization && typeof options.override_authorization === 'object') {
            payload.override_authorization = options.override_authorization;
        }
        if (options.idempotency_key) {
            payload.idempotency_key = options.idempotency_key;
        }
        if (options.notes) {
            payload.notes = options.notes;
        }

        const currentCartItems = [...cart.value];
        const isOfflineAttempt = (typeof navigator !== 'undefined' && !navigator.onLine);

        if (!isOfflineAttempt) {
          try {
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
                paymentMethod: response.data?.payment_method || payload.payment_method,
                subtotal: subtotal,
                discount: discount,
                tax: tax,
                total: total,
                items: currentCartItems,
                payments: response.data?.payments || payload.payments || []
              } 
            };
          } catch (postError) {
            if (postError.response) {
              // Server responded with an explicit error (422, 403, etc.) - do not fall back to offline
              throw postError;
            }
            console.warn('[RetailStore] Network unreachable during sale post, falling back to offline outbox queue:', postError);
          }
        }

        // Offline Durable Save Fallback (REQ-0030)
        let branchId = 1;
        let bizId = 1;
        let userId = 1;
        try {
          const userStr = localStorage.getItem('hbos_user');
          if (userStr) {
            const u = JSON.parse(userStr);
            if (u.branch_id) branchId = u.branch_id;
            if (u.business_id) bizId = u.business_id;
            if (u.id) userId = u.id;
          }
        } catch (e) {}

        const offlineRecord = await createOfflineSale({
          branchId,
          businessId: bizId,
          userId,
          customerId,
          items: itemsPayload,
          discount,
          tax,
          paidAmount: payload.paid_amount !== undefined ? payload.paid_amount : total,
          paymentMethod: payload.payment_method,
          payments: payload.payments,
          financialAccountId: payload.financial_account_id,
          notes: payload.notes,
        });

        // Clear cart ONLY AFTER durable write commit succeeds (Req 53, 132)
        cart.value = [];
        cartDiscount.value = 0;

        return {
          success: true,
          isOffline: true,
          invoice: {
            invoiceNumber: offlineRecord.invoice_number,
            customer: customerName || 'Walk-in Customer',
            date: offlineRecord.created_at,
            paymentMethod: offlineRecord.payment_method,
            subtotal: offlineRecord.subtotal,
            discount: offlineRecord.discount,
            tax: offlineRecord.tax,
            total: offlineRecord.total,
            items: currentCartItems,
            payments: offlineRecord.payments || [],
            is_offline_pending: true,
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

  // --- CASH REGISTER SESSION ACTIONS ---
  const fetchActiveRegisterSession = async (branchId = null, drawerId = null) => {
    isRegisterSessionLoading.value = true
    try {
      const res = await cashRegisterService.getCurrentSession({
        branch_id: branchId,
        financial_account_id: drawerId,
      })
      if (res && res.has_active_session && res.session) {
        activeRegisterSession.value = res.session
        if (typeof window !== 'undefined' && window.localStorage) {
          localStorage.setItem('hbos_active_session_id', String(res.session.id))
        }
      } else {
        activeRegisterSession.value = null
        if (typeof window !== 'undefined' && window.localStorage) {
          localStorage.removeItem('hbos_active_session_id')
        }
      }
      return activeRegisterSession.value
    } catch (e) {
      console.warn('Failed to fetch active register session:', e)
      return null
    } finally {
      isRegisterSessionLoading.value = false
    }
  }

  const openRegisterSession = async ({ branch_id, financial_account_id, opening_float, notes }) => {
    isRegisterSessionLoading.value = true
    try {
      const res = await cashRegisterService.openRegister({
        branch_id,
        financial_account_id,
        opening_float,
        notes,
      })
      activeRegisterSession.value = res.session
      if (typeof window !== 'undefined' && window.localStorage && res.session) {
        localStorage.setItem('hbos_active_session_id', String(res.session.id))
      }
      return res.session
    } finally {
      isRegisterSessionLoading.value = false
    }
  }

  const hasPendingCashOutbox = async (branchId = null) => {
    try {
      const queue = await getPendingOutboxQueue()
      const pendingOutboxCash = queue.filter(entry => {
        if (entry.status !== 'PENDING') return false
        if (branchId && entry.payload?.branch_id && String(entry.payload.branch_id) !== String(branchId)) {
          return false
        }
        if (entry.type === 'sale') {
          const pMethod = entry.payload?.payment_method
          if (pMethod === 'cash') return true
          if (pMethod === 'split' && Array.isArray(entry.payload?.split_tenders)) {
            return entry.payload.split_tenders.some(t => t.method === 'cash' && Number(t.amount) > 0)
          }
        }
        if (entry.type === 'khata_payment' || entry.type === 'customer_payment') {
          return (entry.payload?.payment_method ?? 'cash') === 'cash'
        }
        if (entry.type === 'expense') {
          return true
        }
        return false
      })
      return pendingOutboxCash.length > 0
    } catch (e) {
      return false
    }
  }

  const closeRegisterSession = async ({ session_id, closing_cash_counted, variance_reason, notes, branch_id }) => {
    isRegisterSessionLoading.value = true
    try {
      const hasPending = await hasPendingCashOutbox(branch_id)
      if (hasPending) {
        throw new Error('Cannot close cash drawer while un-synced cash transactions remain in the local device queue. Please connect to the internet and sync pending sales first.')
      }

      const res = await cashRegisterService.closeRegister({
        session_id,
        closing_cash_counted,
        variance_reason,
        notes,
      })
      activeRegisterSession.value = null
      if (typeof window !== 'undefined' && window.localStorage) {
        localStorage.removeItem('hbos_active_session_id')
      }
      return res.session
    } finally {
      isRegisterSessionLoading.value = false
    }
  }

  return {
    products,
    categories,
    subcategories,
    brands,
    cart,
    sales,
    heldCarts,
    cartSubtotal,
    cartDiscount,
    cartTotal,
    activeRegisterSession,
    currentSession: activeRegisterSession,
    isRegisterSessionLoading,
    hasActiveRegisterSession,
    fetchActiveRegisterSession,
    fetchCurrentSession: fetchActiveRegisterSession,
    openRegisterSession,
    closeRegisterSession,
    hasPendingCashOutbox,
    fetchProducts,
    fetchCategories,
    fetchSales,
    addProduct,
    addToCart,
    updateProduct,
    deleteProduct,
    updateCartQuantity,
    removeFromCart,
    overrideItemPrice,
    loadHeldCarts,
    holdCart,
    resumeCart,
    discardCart,
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
    updateBrand,
    removeBrand,
    getSubcategoriesByCategoryId,
    syncState,
    syncStatus,
    pendingOutboxCount
  }
})
