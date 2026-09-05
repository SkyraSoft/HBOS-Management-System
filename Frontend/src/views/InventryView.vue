<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRetailStore } from '@/stores/retail.js'

const route = useRoute()
const router = useRouter()
const store = useRetailStore()

// Tab state: 'products', 'categories', 'brands'
const activeTab = ref(route.query.tab || 'products')

// Modal state


const closeProductModal = () => {
  showProductModal.value = false
  if (route.query.action) {
    const q = { ...route.query }
    delete q.action
    router.replace({ path: '/inventry', query: q })
  }
}

// ====================================================
// 2. COLUMN-BASED INLINE FILTERING STATE (PRODUCTS)
// ====================================================
const colFilters = reactive({
  nameOrSku: '',
  category: '',
  brand: '',
  minCost: '',
  minPrice: '',
  marginRange: '',
  stockRange: '',
  status: '',
  sortBy: 'name_asc'
})

watch(() => [route.query.tab, route.query.filter, route.query.stock, route.query.action], ([newTab, newFilter, newStock, newAction]) => {
  if (newTab) {
    activeTab.value = newTab
  } else {
    activeTab.value = 'products'
  }

  if (newAction === 'add' || route.query.action === 'add' || route.query.modal === 'add') {
    openAddProductModal()
  }

  const stockFilter = newFilter || newStock
  if (stockFilter === 'low-stock' || stockFilter === 'low_stock' || stockFilter === 'low') {
    colFilters.stockRange = 'low_stock'
    colFilters.status = 'low_stock'
  } else if (stockFilter === 'out-of-stock' || stockFilter === 'out_of_stock') {
    colFilters.stockRange = 'out_of_stock'
    colFilters.status = 'out_of_stock'
  } else if (stockFilter === 'in-stock' || stockFilter === 'in_stock') {
    colFilters.stockRange = 'in_stock'
    colFilters.status = 'in_stock'
  }
}, { immediate: true })

const setTab = (tab) => {
  activeTab.value = tab
  router.push({ path: '/inventry', query: { tab } })
}

onMounted(async () => {
  await store.fetchCategories()
  await store.fetchSubcategories()
  await store.fetchBrands()
  await store.fetchProducts()
})

// ====================================================
// 1. DATA & COMPUTED METRICS (THE 9 KPI CARDS)
// ====================================================
const products = computed(() => store.products || [])
const categories = computed(() => store.categories || [])
const subcategories = computed(() => store.subcategories || [])
const brands = computed(() => store.brands || [])

// 1. Total Products
const kpiTotalProducts = computed(() => products.value.length)

// 2. Total Stock Units
const kpiTotalStockUnits = computed(() => {
  return products.value.reduce((sum, p) => sum + (parseInt(p.stock) || 0), 0)
})

// 3. In Stock Items
const kpiInStock = computed(() => {
  return products.value.filter(p => (parseInt(p.stock) || 0) > (parseInt(p.minStock) || 10)).length
})

// 4. Low Stock Alerts
const kpiLowStock = computed(() => {
  return products.value.filter(p => {
    const s = parseInt(p.stock) || 0
    const min = parseInt(p.minStock) || 10
    return s > 0 && s <= min
  }).length
})

// 5. Out of Stock
const kpiOutOfStock = computed(() => {
  return products.value.filter(p => (parseInt(p.stock) || 0) === 0).length
})

// 6. Total Asset Value (Cost Valuation)
const kpiTotalAssetValue = computed(() => {
  return products.value.reduce((sum, p) => {
    const cost = parseFloat(p.cost || p.purchasePrice) || 0
    const qty = parseInt(p.stock) || 0
    return sum + (cost * qty)
  }, 0)
})

// 7. Retail Potential Value (Selling Valuation)
const kpiRetailPotentialValue = computed(() => {
  return products.value.reduce((sum, p) => {
    const price = parseFloat(p.price || p.sellingPrice) || 0
    const qty = parseInt(p.stock) || 0
    return sum + (price * qty)
  }, 0)
})

// 8. Active Categories Count
const kpiActiveCategories = computed(() => {
  return categories.value.length + subcategories.value.length
})

// 9. Partner Brands Count
const kpiPartnerBrands = computed(() => brands.value.length)

const calculateMarginNum = (cost, price) => {
  const c = parseFloat(cost) || 0
  const p = parseFloat(price) || 0
  if (p <= 0 || c <= 0) return 0
  return ((p - c) / p) * 100
}

const filteredProducts = computed(() => {
  let list = [...products.value]

  // 1. Name or SKU column filter
  if (colFilters.nameOrSku.trim()) {
    const q = colFilters.nameOrSku.trim().toLowerCase()
    list = list.filter(p => {
      const name = (p.name || '').toLowerCase()
      const sku = (p.sku || '').toLowerCase()
      const barcode = (p.barcode || '').toLowerCase()
      return name.includes(q) || sku.includes(q) || barcode.includes(q)
    })
  }

  // 2. Category column filter
  if (colFilters.category) {
    list = list.filter(p => p.category === colFilters.category || p.subcategory === colFilters.category)
  }

  // 3. Brand column filter
  if (colFilters.brand) {
    list = list.filter(p => p.brand === colFilters.brand)
  }

  // 4. Cost column filter
  if (colFilters.minCost !== '') {
    const minC = parseFloat(colFilters.minCost) || 0
    list = list.filter(p => (parseFloat(p.cost || p.purchasePrice) || 0) >= minC)
  }

  // 5. Retail Price column filter
  if (colFilters.minPrice !== '') {
    const minP = parseFloat(colFilters.minPrice) || 0
    list = list.filter(p => (parseFloat(p.price || p.sellingPrice) || 0) >= minP)
  }

  // 6. Margin column filter
  if (colFilters.marginRange) {
    list = list.filter(p => {
      const m = calculateMarginNum(p.cost || p.purchasePrice, p.price || p.sellingPrice)
      if (colFilters.marginRange === 'gt50') return m >= 50
      if (colFilters.marginRange === 'gt30') return m >= 30
      if (colFilters.marginRange === 'lt15') return m < 15
      return true
    })
  }

  // 7. Stock quantity filter
  if (colFilters.stockRange) {
    list = list.filter(p => {
      const s = parseInt(p.stock) || 0
      const min = parseInt(p.minStock) || 10
      if (colFilters.stockRange === 'in_stock') return s > min
      if (colFilters.stockRange === 'low_stock') return s > 0 && s <= min
      if (colFilters.stockRange === 'out_of_stock') return s === 0
      if (colFilters.stockRange === 'high_stock') return s >= 50
      return true
    })
  }

  // 8. Status column filter
  if (colFilters.status) {
    list = list.filter(p => {
      const s = parseInt(p.stock) || 0
      const min = parseInt(p.minStock) || 10
      if (colFilters.status === 'in_stock') return s > min
      if (colFilters.status === 'low_stock') return s > 0 && s <= min
      if (colFilters.status === 'out_of_stock') return s === 0
      return true
    })
  }

  // Sorting
  if (colFilters.sortBy === 'name_asc') list.sort((a, b) => (a.name || '').localeCompare(a.name || ''))
  else if (colFilters.sortBy === 'name_desc') list.sort((a, b) => (b.name || '').localeCompare(a.name || ''))
  else if (colFilters.sortBy === 'price_high') list.sort((a, b) => (b.price || b.sellingPrice || 0) - (a.price || a.sellingPrice || 0))
  else if (colFilters.sortBy === 'price_low') list.sort((a, b) => (a.price || a.sellingPrice || 0) - (b.price || b.sellingPrice || 0))
  else if (colFilters.sortBy === 'stock_high') list.sort((a, b) => (b.stock || 0) - (a.stock || 0))
  else if (colFilters.sortBy === 'stock_low') list.sort((a, b) => (a.stock || 0) - (b.stock || 0))

  return list
})

const hasActiveFilters = computed(() => {
  return colFilters.nameOrSku || colFilters.category || colFilters.brand || colFilters.minCost || colFilters.minPrice || colFilters.marginRange || colFilters.stockRange || colFilters.status
})

const resetAllColumnFilters = () => {
  colFilters.nameOrSku = ''
  colFilters.category = ''
  colFilters.brand = ''
  colFilters.minCost = ''
  colFilters.minPrice = ''
  colFilters.marginRange = ''
  colFilters.stockRange = ''
  colFilters.status = ''
  colFilters.sortBy = 'name_asc'
  if (route.query.filter || route.query.stock) {
    router.replace({ path: route.path, query: { tab: activeTab.value } })
  }
}

const formatMoney = (val) => Number(val || 0).toLocaleString("en-PK", { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const calculateMarginDisplay = (cost, price) => {
  const m = calculateMarginNum(cost, price)
  return m.toFixed(1)
}

const getStockBadgeClass = (stock, minStock = 10) => {
  const s = parseInt(stock) || 0
  const min = parseInt(minStock) || 10
  if (s === 0) return 'badge-out'
  if (s <= min) return 'badge-low'
  return 'badge-in'
}

const getStockStatusText = (stock, minStock = 10) => {
  const s = parseInt(stock) || 0
  const min = parseInt(minStock) || 10
  if (s === 0) return 'Out of Stock'
  if (s <= min) return 'Low Stock'
  return 'In Stock'
}

// ====================================================
// 3. PRODUCT MODAL / DRAWER (ADD & EDIT)
// ====================================================

const addAttribute = () => {
  productForm.attributes.push({ name: '', valuesText: '' })
}
const removeAttribute = (index) => {
  productForm.attributes.splice(index, 1)
  generateVariations()
}
const generateVariations = () => {
  if (!productForm.hasVariations || productForm.attributes.length === 0) {
    productForm.variations = []
    return
  }
  const arrays = productForm.attributes.map(a => {
    return a.valuesText.split(',').map(v => v.trim()).filter(Boolean).map(v => ({ [a.name]: v }))
  }).filter(arr => arr.length > 0)
  
  if (arrays.length === 0) {
    productForm.variations = []
    return
  }
  
  const combine = (acc, curr) => {
    if (acc.length === 0) return curr
    const result = []
    for (const a of acc) {
      for (const c of curr) {
        result.push({ ...a, ...c })
      }
    }
    return result
  }
  
  const combinations = arrays.reduce(combine, [])
  
  // Try to preserve existing variation data if it exists
  const existingMap = new Map()
  for (const v of productForm.variations) {
    const key = Object.values(v.attributes).sort().join('|')
    existingMap.set(key, v)
  }
  
  productForm.variations = combinations.map(combo => {
    const key = Object.values(combo).sort().join('|')
    if (existingMap.has(key)) {
      const existing = existingMap.get(key)
      existing.attributes = combo
      return existing
    }
    const valParts = Object.values(combo).map(v => String(v).replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0,4))
    const skuSuffix = valParts.join('-')
    return {
      sku: productForm.sku + '-' + skuSuffix,
      attributes: combo,
      price: productForm.sellingPrice,
      stock: 10
    }
  })
}

const showProductModal = ref(false)
const isEditingProduct = ref(false)
const productForm = reactive({
  id: null,
  name: '',
  sku: '',
  barcode: '',
  brand: '',
  category: '',
  subcategory: '',
  purchasePrice: 0,
  sellingPrice: 0,
  stock: 0,
  minStock: 10,
  expectedSellDate: '',
  unit: 'Piece',
  description: '',
  image: null,
  imagePreview: '',
  hasVariations: false,
  attributes: [],
  variations: []
})

const openAddProductModal = () => {
  isEditingProduct.value = false
  productForm.id = null
  productForm.name = ''
  productForm.sku = 'SKU-' + Math.floor(1000 + Math.random() * 9000)
  productForm.barcode = ''
  productForm.brand = ''
  productForm.category = ''
  productForm.subcategory = ''
  productForm.purchasePrice = 0
  productForm.sellingPrice = 0
  productForm.stock = 10
  productForm.minStock = 5
  productForm.expectedSellDate = ''
  productForm.unit = 'Piece'
  productForm.description = ''
  productForm.image = null
  productForm.imagePreview = ''
  productForm.hasVariations = false
  productForm.attributes = []
  productForm.variations = []
  showProductModal.value = true
}

const openEditProductModal = (product) => {
  isEditingProduct.value = true
  productForm.id = product.id
  productForm.name = product.name || ''
  productForm.sku = product.sku || ''
  productForm.barcode = product.barcode || ''
  productForm.brand = product.brand || ''
  productForm.category = product.category || ''
  productForm.subcategory = product.subcategory || ''
  productForm.purchasePrice = product.cost || product.purchasePrice || 0
  productForm.sellingPrice = product.price || product.sellingPrice || 0
  productForm.stock = product.stock || 0
  productForm.minStock = product.minStock || 5
  productForm.expectedSellDate = product.expected_sell_date || ''
  productForm.unit = product.unit || 'Piece'
  productForm.description = product.description || ''
  productForm.image = null
  productForm.imagePreview = product.image || ''
  productForm.hasVariations = !!product.hasVariations
  productForm.attributes = product.attributes ? product.attributes.map(a => ({ name: a.name, valuesText: (a.values || []).join(', ') })) : []
  productForm.variations = product.variations ? JSON.parse(JSON.stringify(product.variations)) : []
  showProductModal.value = true
}

const handleProductImageUpload = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  productForm.image = file
  const reader = new FileReader()
  reader.onload = (ev) => {
    productForm.imagePreview = ev.target.result
  }
  reader.readAsDataURL(file)
}


const saveProduct = async () => {
  if (!productForm.name.trim() || !productForm.sku.trim() || !productForm.sellingPrice) {
    showToast('Please fill Product Name, SKU, and Selling Price.')
    return
  }

  const payload = {
    name: productForm.name.trim(),
    sku: productForm.sku.trim(),
    barcode: productForm.barcode.trim() || productForm.sku.trim(),
    brand: productForm.brand,
    category: productForm.category,
    subcategory: productForm.subcategory,
    purchasePrice: parseFloat(productForm.purchasePrice) || 0,
    sellingPrice: parseFloat(productForm.sellingPrice) || 0,
    cost: parseFloat(productForm.purchasePrice) || 0,
    price: parseFloat(productForm.sellingPrice) || 0,
    stock: parseInt(productForm.stock) || 0,
    minStock: parseInt(productForm.minStock) || 5,
    expected_sell_date: productForm.expectedSellDate || null,
    unit: productForm.unit,
    description: productForm.description,
    imageFile: productForm.image,
    image_url: productForm.imagePreview || ''
  }
  
  if (productForm.hasVariations) {
    payload.has_variations = true;
    payload.attributes = productForm.attributes.map(a => ({
      name: a.name,
      values: a.valuesText.split(',').map(v => v.trim()).filter(Boolean)
    }));
    payload.variations = productForm.variations;
  } else {
    payload.has_variations = false;
    payload.attributes = [];
    payload.variations = [];
  }

  try {
    if (isEditingProduct.value && productForm.id) {
      await store.updateProduct(productForm.id, payload)
      showToast('Product updated successfully!')
    } else {
      await store.addProduct(payload)
      showToast('Product added to inventory successfully!')
    }
    closeProductModal()
  } catch (e) {
    console.error(e)
    showToast('Error saving product.')
  }
}


const resolveProductImage = (img, name = '') => {
  if (img && typeof img === 'string' && img.trim() !== '') {
    const trimmed = img.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:') || trimmed.startsWith('blob:')) {
      return trimmed;
    }
    if (trimmed.startsWith('/storage/')) {
      return `http://localhost:8000${trimmed}`;
    }
    if (trimmed.startsWith('storage/')) {
      return `http://localhost:8000/${trimmed}`;
    }
    if (trimmed.startsWith('/')) {
      return `http://localhost:8000${trimmed}`;
    }
    return `http://localhost:8000/storage/${trimmed}`;
  }
  return getFallbackImage(name);
};

const getFallbackImage = (name) => {
  const n = (name || '').toLowerCase();
  if (n.includes('apple') || n.includes('fruit') || n.includes('banana') || n.includes('mango')) {
    return 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('bread') || n.includes('wheat') || n.includes('bakery') || n.includes('flour') || n.includes('atta')) {
    return 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('rice') || n.includes('grain') || n.includes('pulse') || n.includes('daal') || n.includes('dal')) {
    return 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('milk') || n.includes('dairy') || n.includes('cheese') || n.includes('butter') || n.includes('yogurt')) {
    return 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('oil') || n.includes('ghee') || n.includes('cooking')) {
    return 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('pepsi') || n.includes('coke') || n.includes('cola') || n.includes('drink') || n.includes('beverage') || n.includes('juice') || n.includes('soda')) {
    return 'https://images.unsplash.com/photo-1629203851122-3726ecdf080e?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('egg')) {
    return 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('snack') || n.includes('chips') || n.includes('biscuit') || n.includes('cookie')) {
    return 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('tea') || n.includes('coffee')) {
    return 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('sugar') || n.includes('salt') || n.includes('spice') || n.includes('masala')) {
    return 'https://images.unsplash.com/photo-1588698144670-f80e927c9a96?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('vegetable') || n.includes('tomato') || n.includes('potato') || n.includes('onion')) {
    return 'https://images.unsplash.com/photo-1597362925123-77861d3fbac7?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('iphone') || n.includes('mobile') || n.includes('phone') || n.includes('samsung') || n.includes('gelaxy')) {
    return 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('watch') || n.includes('smartwatch')) {
    return 'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('laptop') || n.includes('macbook') || n.includes('computer')) {
    return 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('headphone') || n.includes('audio') || n.includes('earbud')) {
    return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('mouse') || n.includes('keyboard')) {
    return 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('tee') || n.includes('shirt') || n.includes('clothing')) {
    return 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80';
  }
  if (n.includes('jean') || n.includes('denim') || n.includes('pant')) {
    return 'https://images.unsplash.com/photo-1542272604-780c96856592?auto=format&fit=crop&w=800&q=80';
  }
  return 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=800&q=80';
};

const deleteProductItem = async (product) => {
  if (confirm(`Delete "${product.name}" from inventory?`)) {
    await store.deleteProduct(product.id)
    showToast('Product deleted from inventory.')
  }
}

// ====================================================
// 4. QUICK RESTOCK MODAL
// ====================================================
const showRestockModal = ref(false)
const restockProduct = ref(null)
const restockQty = ref(10)
const restockCost = ref(0)

const openQuickRestock = (product) => {
  restockProduct.value = product
  restockQty.value = 10
  restockCost.value = product.cost || product.purchasePrice || 0
  showRestockModal.value = true
}

const submitQuickRestock = async () => {
  if (!restockProduct.value || restockQty.value <= 0) return
  const currentStock = parseInt(restockProduct.value.stock) || 0
  const newTotalStock = currentStock + parseInt(restockQty.value)
  
  await store.updateProduct(restockProduct.value.id, {
    ...restockProduct.value,
    stock: newTotalStock,
    purchasePrice: restockCost.value || restockProduct.value.cost
  })
  
  showToast(`Restocked ${restockQty.value} units for ${restockProduct.value.name}!`)
  showRestockModal.value = false
}

// ====================================================
// 5. VIEW PRODUCT DETAILS MODAL
// ====================================================
const showDetailsModal = ref(false)
const selectedProductDetails = ref(null)

const openProductDetails = (product) => {
  selectedProductDetails.value = product
  showDetailsModal.value = true
}

// ====================================================
// 6. WORDPRESS HIERARCHICAL CATEGORY DIRECTORY
// ====================================================
const categorySearchQuery = ref('')
const categoryTypeFilter = ref('') // '', 'parent', 'sub'
const categoryParentFilter = ref('') // '', or Parent Category name
const categorySubFilter = ref('') // '', or Sub Category name
const categoryStatusFilter = ref('') // '', 'active', 'inactive'

const categorySubcategoryOptions = computed(() => {
  if (!categoryParentFilter.value) {
    return subcategories.value || []
  }
  return (subcategories.value || []).filter(s => {
    if (s.category && s.category.name === categoryParentFilter.value) return true
    const parentCat = (categories.value || []).find(c => c.name === categoryParentFilter.value)
    if (parentCat && s.category_id && String(s.category_id) === String(parentCat.id)) return true
    return (products.value || []).some(p => p.subcategory === s.name && p.category === categoryParentFilter.value)
  })
})

const resetCategoryFilters = () => {
  categorySearchQuery.value = ''
  categoryTypeFilter.value = ''
  categoryParentFilter.value = ''
  categorySubFilter.value = ''
  categoryStatusFilter.value = ''
}

const hierarchicalCategoryList = computed(() => {
  const list = []
  const q = categorySearchQuery.value.trim().toLowerCase()
  const typeF = categoryTypeFilter.value
  const parentF = categoryParentFilter.value
  const subF = categorySubFilter.value
  const statusF = categoryStatusFilter.value
  const processedSubIds = new Set()

  for (const cat of (categories.value || [])) {
    // If a specific parent filter is selected and this isn't it, skip this parent
    if (parentF && cat.name !== parentF) {
      continue
    }

    const catProducts = (products.value || []).filter(p => p.category === cat.name)
    const childSubs = (subcategories.value || []).filter(s => {
      if (s.category_id && String(s.category_id) === String(cat.id)) return true
      if (s.category && (String(s.category.id) === String(cat.id) || s.category.name === cat.name)) return true
      const inProd = (products.value || []).some(p => p.subcategory === s.name && p.category === cat.name)
      return inProd
    })

    childSubs.forEach(s => processedSubIds.add(s.id))

    const catImg = cat.image || cat.icon || catProducts.find(p => p.image)?.image || ''
    const catActive = cat.status !== 0 && cat.status !== false && cat.is_active !== 0

    const parentItem = {
      id: cat.id,
      name: cat.name,
      displayName: cat.name,
      image: catImg,
      level: 0,
      isParent: true,
      rawType: 'parent',
      type: 'Parent Category',
      parentCategoryName: '',
      parentId: null,
      description: cat.description || '',
      status: catActive,
      productCount: catProducts.length,
      childCount: childSubs.length,
      uniqueKey: 'cat-' + cat.id
    }

    const childItems = childSubs.map(sub => {
      const subProducts = (products.value || []).filter(p => p.subcategory === sub.name)
      const subImg = sub.image || sub.icon || subProducts.find(p => p.image)?.image || ''
      const subActive = sub.status !== 0 && sub.status !== false && sub.is_active !== 0
      return {
        id: sub.id,
        name: sub.name,
        displayName: '— ' + sub.name,
        image: subImg,
        level: 1,
        isParent: false,
        rawType: 'sub',
        type: 'Sub-Category',
        parentCategoryName: cat.name,
        parentId: cat.id,
        description: sub.description || '',
        status: subActive,
        productCount: subProducts.length,
        childCount: 0,
        uniqueKey: 'sub-' + sub.id
      }
    })

    // Filter parent by status if statusF is set
    const parentStatusMatches = !statusF || (statusF === 'active' && catActive) || (statusF === 'inactive' && !catActive)
    
    // Filter parent by search query
    const parentSearchMatches = !q || parentItem.name.toLowerCase().includes(q) || (parentItem.description || '').toLowerCase().includes(q)

    // Filter children by status, sub-category filter, and search
    const matchingChildren = childItems.filter(c => {
      const childStatusMatches = !statusF || (statusF === 'active' && c.status) || (statusF === 'inactive' && !c.status)
      const childSubMatches = !subF || c.name === subF
      const childSearchMatches = !q || c.name.toLowerCase().includes(q) || (c.description || '').toLowerCase().includes(q) || parentItem.name.toLowerCase().includes(q)
      return childStatusMatches && childSubMatches && childSearchMatches
    })

    // Check Type filter
    if (typeF === 'parent') {
      if (!subF && parentStatusMatches && parentSearchMatches) {
        list.push(parentItem)
      }
    } else if (typeF === 'sub') {
      list.push(...matchingChildren)
    } else {
      // typeF is '' (All Types)
      if (subF) {
        if (matchingChildren.length > 0) {
          list.push(parentItem)
          list.push(...matchingChildren)
        }
      } else {
        if (parentStatusMatches && parentSearchMatches) {
          list.push(parentItem)
          list.push(...matchingChildren)
        } else if (matchingChildren.length > 0) {
          list.push(...matchingChildren)
        }
      }
    }
  }

  // Handle orphan subcategories if no specific parent filter is selected or if it matches
  if (!parentF && typeF !== 'parent') {
    const remainingSubs = (subcategories.value || []).filter(s => !processedSubIds.has(s.id))
    for (const sub of remainingSubs) {
      if (subF && sub.name !== subF) continue

      const subProducts = (products.value || []).filter(p => p.subcategory === sub.name)
      const subImg = sub.image || sub.icon || subProducts.find(p => p.image)?.image || ''
      const subActive = sub.status !== 0 && sub.status !== false && sub.is_active !== 0

      const childStatusMatches = !statusF || (statusF === 'active' && subActive) || (statusF === 'inactive' && !subActive)
      const childSearchMatches = !q || sub.name.toLowerCase().includes(q) || (sub.description || '').toLowerCase().includes(q)

      if (childStatusMatches && childSearchMatches) {
        list.push({
          id: sub.id,
          name: sub.name,
          displayName: '— ' + sub.name,
          image: subImg,
          level: 1,
          isParent: false,
          rawType: 'sub',
          type: 'Sub-Category',
          parentCategoryName: sub.category?.name || 'Unassigned',
          parentId: sub.category_id || null,
          description: sub.description || '',
          status: subActive,
          productCount: subProducts.length,
          childCount: 0,
          uniqueKey: 'sub-' + sub.id
        })
      }
    }
  }

  return list
})

const showCategoryModal = ref(false)
const categoryForm = reactive({
  id: null,
  isEdit: false,
  name: '',
  categoryType: 'parent',
  parentCategory: '',
  description: '',
  status: true
})

const openAddCategoryModal = () => {
  categoryForm.id = null
  categoryForm.isEdit = false
  categoryForm.name = ''
  categoryForm.categoryType = 'parent'
  categoryForm.parentCategory = ''
  categoryForm.description = ''
  categoryForm.status = true
  showCategoryModal.value = true
}

const editCategoryItem = (item) => {
  categoryForm.id = item.id
  categoryForm.isEdit = true
  categoryForm.name = item.name
  categoryForm.categoryType = item.rawType === 'sub' ? 'sub' : 'parent'
  categoryForm.parentCategory = item.parentId || ''
  categoryForm.description = item.description || ''
  categoryForm.status = item.status !== false
  showCategoryModal.value = true
}

const saveCategoryForm = async () => {
  if (!categoryForm.name.trim()) {
    showToast('Category name is required.')
    return
  }

  if (categoryForm.isEdit) {
    if (categoryForm.categoryType === 'sub') {
      await store.updateSubcategory(categoryForm.id, {
        name: categoryForm.name.trim(),
        category_id: categoryForm.parentCategory || null,
        description: categoryForm.description
      })
    } else {
      await store.updateCategory(categoryForm.id, {
        name: categoryForm.name.trim(),
        description: categoryForm.description
      })
    }
    showToast('Category updated successfully!')
  } else {
    if (categoryForm.categoryType === 'sub') {
      await store.addSubcategory({
        name: categoryForm.name.trim(),
        category_id: categoryForm.parentCategory || null,
        description: categoryForm.description
      })
    } else {
      await store.addCategory({
        name: categoryForm.name.trim(),
        description: categoryForm.description
      })
    }
    showToast('Category created successfully!')
  }
  showCategoryModal.value = false
}

const deleteCategoryItem = async (item) => {
  if (confirm(`Delete ${item.type} "${item.name}"?`)) {
    if (item.rawType === 'sub') {
      await store.removeSubcategory(item.id)
    } else {
      await store.removeCategory(item.id)
    }
    showToast('Category deleted!')
  }
}

// Category View Details Modal
const showViewCategoryModal = ref(false)
const selectedCategoryForView = ref(null)

const viewCategoryItem = (item) => {
  selectedCategoryForView.value = item
  showViewCategoryModal.value = true
}

const handleEditFromViewModal = () => {
  const item = selectedCategoryForView.value
  showViewCategoryModal.value = false
  selectedCategoryForView.value = null
  if (item) editCategoryItem(item)
}

// ====================================================
// 7. BRAND DIRECTORY
// ====================================================
const showBrandModal = ref(false)
const brandForm = reactive({
  name: '',
  description: '',
  imageFile: null,
  imagePreview: ''
})

const openAddBrandModal = () => {
  brandForm.name = ''
  brandForm.description = ''
  brandForm.imageFile = null
  brandForm.imagePreview = ''
  showBrandModal.value = true
}

const handleBrandImageUpload = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  brandForm.imageFile = file
  const reader = new FileReader()
  reader.onload = (ev) => {
    brandForm.imagePreview = ev.target.result
  }
  reader.readAsDataURL(file)
}

const saveBrand = async () => {
  if (!brandForm.name.trim()) {
    showToast('Brand name is required.')
    return
  }

  const formData = new FormData()
  formData.append('name', brandForm.name.trim())
  if (brandForm.description) formData.append('description', brandForm.description)
  if (brandForm.imageFile) formData.append('image', brandForm.imageFile)

  await store.addBrand(formData)
  showToast('Brand added successfully!')
  showBrandModal.value = false
}

const deleteBrandItem = async (brand) => {
  if (confirm(`Delete brand "${brand.name}"?`)) {
    await store.removeBrand(brand.id)
    showToast('Brand removed!')
  }
}

// ====================================================
// TOAST NOTIFICATION
// ====================================================
const toastMsg = ref('')
const isToastOpen = ref(false)
const showToast = (msg) => {
  toastMsg.value = msg
  isToastOpen.value = true
  setTimeout(() => { isToastOpen.value = false }, 2500)
}
</script>

<template>
  <div class="inv-page-root">

    <!-- PAGE TOP BAR -->
    <div class="inv-topbar">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="inv-badge-pill">{{ activeTab === 'categories' ? 'Directory' : (activeTab === 'brands' ? 'Partners' : 'Master Control') }}</span>
          <span class="text-muted small">• {{ activeTab === 'categories' ? 'Catalog Structure' : (activeTab === 'brands' ? 'Brand Directory' : 'Live Store Inventory') }}</span>
        </div>
        <h1 class="inv-main-title">{{ activeTab === 'categories' ? 'Categories' : (activeTab === 'brands' ? 'Brands' : 'Inventory & Product Hub') }}</h1>
        <p class="inv-main-sub">
          {{ activeTab === 'categories' ? 'Organize and manage your product categories and sub-categories.' : (activeTab === 'brands' ? 'Manage partner brands, manufacturer logos, and associated product lines.' : 'Unified inventory stock control, column-filtered catalog grid, and live stock tracking.') }}
        </p>
      </div>

      <!-- HEADER ACTIONS (ONLY ON PRODUCTS TAB) -->
      <div class="d-flex align-items-center gap-2 flex-wrap" v-if="activeTab === 'products'">
        <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm" @click="openAddProductModal" type="button" style="height: 38px;">
          <i class="bi bi-plus-lg"></i> Add New Product
        </button>
      </div>
    </div>

    <!-- ====================================================
         THE 9 MASTER KPI CARDS (Displayed on products tab)
    ===================================================== -->
    <div v-if="activeTab === 'products'" class="inv-9kpi-grid mb-4">
      <!-- 1. Total Products -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap bg-primary-subtle text-primary"><i class="bi bi-box-seam"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Total Products</span>
          <div class="kpi-num">{{ kpiTotalProducts }}</div>
          <span class="kpi-sub"><i class="bi bi-tag text-primary"></i> Unique SKUs</span>
        </div>
      </div>

      <!-- 2. Total Stock Units -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap bg-info-subtle text-info"><i class="bi bi-layers"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Stock on Hand</span>
          <div class="kpi-num">{{ kpiTotalStockUnits.toLocaleString() }}</div>
          <span class="kpi-sub text-info"><i class="bi bi-box"></i> Physical units</span>
        </div>
      </div>

      <!-- 3. In Stock -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">In Stock</span>
          <div class="kpi-num text-success">{{ kpiInStock }}</div>
          <span class="kpi-sub text-success"><i class="bi bi-arrow-up-short"></i> Healthy levels</span>
        </div>
      </div>

      <!-- 4. Low Stock Alerts -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Low Stock</span>
          <div class="kpi-num text-warning">{{ kpiLowStock }}</div>
          <span class="kpi-sub text-warning"><i class="bi bi-arrow-repeat"></i> Needs reorder</span>
        </div>
      </div>

      <!-- 5. Out of Stock -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap bg-danger-subtle text-danger"><i class="bi bi-slash-circle"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Out of Stock</span>
          <div class="kpi-num text-danger">{{ kpiOutOfStock }}</div>
          <span class="kpi-sub text-danger"><i class="bi bi-x-circle"></i> Zero inventory</span>
        </div>
      </div>

      <!-- 6. Total Asset Value -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap" style="background: #f3e8ff; color: #7e22ce;"><i class="bi bi-safe"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Asset Valuation</span>
          <div class="kpi-num fs-5" style="color: #6b21a8;">PKR {{ formatMoney(kpiTotalAssetValue) }}</div>
          <span class="kpi-sub text-muted">Cost price sum</span>
        </div>
      </div>

      <!-- 7. Retail Potential Value -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap" style="background: #ecfdf5; color: #047857;"><i class="bi bi-cash-stack"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Retail Value</span>
          <div class="kpi-num fs-5 text-success">PKR {{ formatMoney(kpiRetailPotentialValue) }}</div>
          <span class="kpi-sub text-success">Sales potential</span>
        </div>
      </div>

      <!-- 8. Active Categories -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap" style="background: #e0e7ff; color: #4338ca;"><i class="bi bi-folder2-open"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Categories</span>
          <div class="kpi-num">{{ kpiActiveCategories }}</div>
          <span class="kpi-sub text-muted">{{ categories.length }} Main, {{ subcategories.length }} Subs</span>
        </div>
      </div>

      <!-- 9. Partner Brands -->
      <div class="kpi-box">
        <div class="kpi-icon-wrap" style="background: #ccfbf1; color: #0f766e;"><i class="bi bi-award"></i></div>
        <div class="kpi-data">
          <span class="kpi-title">Partner Brands</span>
          <div class="kpi-num">{{ kpiPartnerBrands }}</div>
          <span class="kpi-sub text-muted">Brand partners</span>
        </div>
      </div>
    </div>

    <!-- ====================================================
         TAB 1: MASTER TABLE WITH COLUMN-HEADER INLINE FILTERS
    ===================================================== -->
    <div v-if="activeTab === 'products'" class="inv-card">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white">
        <div class="d-flex align-items-center gap-2">
          <span class="fw-bold text-dark fs-6">Master Inventory Ledger</span>
          <span class="badge bg-primary-subtle text-primary">{{ filteredProducts.length }} items shown</span>
        </div>

        <div class="d-flex align-items-center gap-2">
          <select v-model="colFilters.sortBy" class="form-select form-select-sm rounded-3" style="width: auto;">
            <option value="name_asc">Sort: Name (A-Z)</option>
            <option value="name_desc">Sort: Name (Z-A)</option>
            <option value="price_high">Sort: Price (High to Low)</option>
            <option value="price_low">Sort: Price (Low to High)</option>
            <option value="stock_high">Sort: Stock (High to Low)</option>
            <option value="stock_low">Sort: Stock (Low to High)</option>
          </select>

          <button v-if="hasActiveFilters" @click="resetAllColumnFilters" class="btn btn-sm btn-outline-danger rounded-3" type="button">
            <i class="bi bi-x-lg me-1"></i> Clear Filters
          </button>
        </div>
      </div>

      <!-- TABLE WITH INLINE COLUMN FILTERS -->
      <div class="inv-table-responsive">
        <table class="inv-table">
          <thead>
            <!-- 1. COLUMN TITLES -->
            <tr>
              <th style="min-width: 250px;">Product Name & SKU</th>
              <th style="min-width: 160px;">Category</th>
              <th style="min-width: 140px;">Brand</th>
              <th style="min-width: 120px;">Cost (PKR)</th>
              <th style="min-width: 120px;">Retail (PKR)</th>
              <th style="min-width: 110px;">Margin %</th>
              <th style="min-width: 120px;">Stock / Units</th>
              <th style="min-width: 130px;">Stock Status</th>
              <th style="text-align: right; min-width: 130px;">Actions</th>
            </tr>

            <!-- 2. INLINE COLUMN FILTERS ROW -->
            <tr class="inv-filter-row">
              <!-- Name & SKU filter -->
              <th>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white border-end-0 py-1"><i class="bi bi-search text-muted" style="font-size: 11px;"></i></span>
                  <input v-model="colFilters.nameOrSku" type="text" class="form-control form-control-sm border-start-0 py-1" placeholder="Filter Name / SKU..." style="font-size: 11.5px;" />
                </div>
              </th>

              <!-- Category filter -->
              <th>
                <select v-model="colFilters.category" class="form-select form-select-sm py-1" style="font-size: 11.5px;">
                  <option value="">All Categories</option>
                  <optgroup label="Parent Categories">
                    <option v-for="c in categories" :key="'p-'+c.id" :value="c.name">{{ c.name }}</option>
                  </optgroup>
                  <optgroup label="Sub-Categories" v-if="subcategories.length">
                    <option v-for="s in subcategories" :key="'s-'+s.id" :value="s.name">{{ s.name }}</option>
                  </optgroup>
                </select>
              </th>

              <!-- Brand filter -->
              <th>
                <select v-model="colFilters.brand" class="form-select form-select-sm py-1" style="font-size: 11.5px;">
                  <option value="">All Brands</option>
                  <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                </select>
              </th>

              <!-- Cost filter -->
              <th>
                <input v-model="colFilters.minCost" type="number" step="1" class="form-control form-control-sm py-1" placeholder="Min Cost..." style="font-size: 11.5px;" />
              </th>

              <!-- Price filter -->
              <th>
                <input v-model="colFilters.minPrice" type="number" step="1" class="form-control form-control-sm py-1" placeholder="Min Price..." style="font-size: 11.5px;" />
              </th>

              <!-- Margin filter -->
              <th>
                <select v-model="colFilters.marginRange" class="form-select form-select-sm py-1" style="font-size: 11.5px;">
                  <option value="">All Margins</option>
                  <option value="gt50">≥ 50% Profit</option>
                  <option value="gt30">≥ 30% Profit</option>
                  <option value="lt15">&lt; 15% Profit</option>
                </select>
              </th>

              <!-- Stock Quantity filter -->
              <th>
                <select v-model="colFilters.stockRange" class="form-select form-select-sm py-1" style="font-size: 11.5px;">
                  <option value="">All Stock</option>
                  <option value="high_stock">High Stock (≥ 50)</option>
                  <option value="in_stock">Healthy Stock (> Min)</option>
                  <option value="low_stock">Low Stock (≤ Min)</option>
                  <option value="out_of_stock">Out of Stock (0)</option>
                </select>
              </th>

              <!-- Status filter -->
              <th>
                <select v-model="colFilters.status" class="form-select form-select-sm py-1" style="font-size: 11.5px;">
                  <option value="">All Status</option>
                  <option value="in_stock">In Stock</option>
                  <option value="low_stock">Low Stock</option>
                  <option value="out_of_stock">Out of Stock</option>
                </select>
              </th>

              <!-- Clear Filters button -->
              <th style="text-align: right;">
                <button v-if="hasActiveFilters" @click="resetAllColumnFilters" class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-2" style="font-size: 11px;" title="Reset filters">
                  <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="filteredProducts.length === 0">
              <td colspan="9" class="text-center py-5">
                <div class="inv-empty-box">
                  <i class="bi bi-box-seam fs-1 text-muted"></i>
                  <h5 class="fw-bold mt-2">No matching products found</h5>
                  <p class="text-muted small">Try clearing one or more column filters above.</p>
                  <button class="btn btn-sm btn-outline-danger rounded-3 me-2" @click="resetAllColumnFilters">
                    <i class="bi bi-x-lg me-1"></i> Clear Filters
                  </button>
                  <button class="btn btn-sm btn-primary rounded-3" @click="openAddProductModal">
                    <i class="bi bi-plus-lg me-1"></i> Add New Product
                  </button>
                </div>
              </td>
            </tr>

            <tr v-for="product in filteredProducts" :key="product.id || product.sku">
              <td>
                <div class="d-flex align-items-center gap-2.5">
                  <div class="prod-thumb">
                    <img :src="resolveProductImage(product.image, product.name)" :alt="product.name" />
                  </div>
                  <div>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;">{{ product.name }}</div>
                    <span class="sku-code font-monospace">{{ product.sku || 'N/A' }}</span>
                  </div>
                </div>
              </td>

              <td>
                <div>
                  <span class="cat-pill">{{ product.category || 'General' }}</span>
                  <div v-if="product.subcategory" class="text-muted small font-monospace mt-0.5" style="font-size: 11px;">
                    ↳ {{ product.subcategory }}
                  </div>
                </div>
              </td>

              <td>
                <span class="fw-medium text-secondary">{{ product.brand || '—' }}</span>
              </td>

              <td class="text-muted font-monospace">
                {{ formatMoney(product.cost || product.purchasePrice) }}
              </td>

              <td class="fw-bold text-dark font-monospace">
                {{ formatMoney(product.price || product.sellingPrice) }}
              </td>

              <td>
                <span class="margin-badge">
                  {{ calculateMarginDisplay(product.cost || product.purchasePrice, product.price || product.sellingPrice) }}%
                </span>
              </td>

              <td>
                <div class="fw-bold" :class="(product.stock || 0) <= (product.minStock || 10) ? 'text-danger' : 'text-dark'">
                  {{ product.stock || 0 }} <span class="text-muted small fw-normal">{{ product.unit || 'pcs' }}</span>
                </div>
              </td>

              <td>
                <span class="stock-pill" :class="getStockBadgeClass(product.stock, product.minStock)">
                  <span class="pill-dot"></span>
                  {{ getStockStatusText(product.stock, product.minStock) }}
                </span>
              </td>

              <td style="text-align: right;">
                <div class="d-flex justify-content-end gap-1">
                  <button class="inv-act-btn" title="View Details" @click="openProductDetails(product)">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="inv-act-btn" title="Quick Restock" @click="openQuickRestock(product)">
                    <i class="bi bi-plus-circle"></i>
                  </button>
                  <button class="inv-act-btn" title="Edit Product" @click="openEditProductModal(product)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="inv-act-btn text-danger" title="Delete Product" @click="deleteProductItem(product)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- FOOTER -->
      <div class="inv-table-footer">
        <span class="text-muted small">
          Showing <strong class="text-dark">{{ filteredProducts.length }}</strong> of <strong class="text-dark">{{ products.length }}</strong> products
        </span>
        <div class="d-flex gap-1">
          <button class="btn btn-sm btn-light border" disabled><i class="bi bi-chevron-left"></i></button>
          <button class="btn btn-sm btn-primary">1</button>
          <button class="btn btn-sm btn-light border" disabled><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
    </div>

    <!-- ====================================================
         TAB 2: WORDPRESS HIERARCHICAL CATEGORY DIRECTORY
    ===================================================== -->
    <div v-if="activeTab === 'categories'" class="inv-card">
      <!-- POLISHED CATEGORIES FILTER TOOLBAR -->
      <div class="cat-filter-bar">
        <!-- Search Category Input -->
        <div class="cat-search-box">
          <i class="bi bi-search"></i>
          <input 
            v-model="categorySearchQuery" 
            type="text" 
            placeholder="Search Category..." 
          />
          <button v-if="categorySearchQuery" @click="categorySearchQuery = ''" class="clear-btn" type="button">
            <i class="bi bi-x-circle-fill"></i>
          </button>
        </div>

        <!-- All Types Filter -->
        <select v-model="categoryTypeFilter" class="cat-filter-select cat-select-type">
          <option value="">All Types</option>
          <option value="parent">Parent Category</option>
          <option value="sub">Sub-Category</option>
        </select>

        <!-- All Parent Categories Filter -->
        <select v-model="categoryParentFilter" class="cat-filter-select cat-select-parent">
          <option value="">All Parent Categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.name">{{ c.name }}</option>
        </select>

        <!-- All Sub-Categories Filter -->
        <select v-model="categorySubFilter" class="cat-filter-select cat-select-sub">
          <option value="">All Sub-Categories</option>
          <option v-for="s in categorySubcategoryOptions" :key="s.id" :value="s.name">{{ s.name }}</option>
        </select>

        <!-- All Status Filter -->
        <select v-model="categoryStatusFilter" class="cat-filter-select cat-select-status">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>

        <!-- Reset Button -->
        <button v-if="categorySearchQuery || categoryTypeFilter || categoryParentFilter || categorySubFilter || categoryStatusFilter" @click="resetCategoryFilters" class="cat-btn-reset" type="button">
          <i class="bi bi-x-lg"></i> Reset
        </button>

        <!-- Add Category Button (Right Aligned) -->
        <button class="cat-btn-add ms-auto" @click="openAddCategoryModal" type="button">
          <i class="bi bi-plus-lg"></i> Add Category
        </button>
      </div>

      <div class="inv-table-responsive">
        <table class="inv-table">
          <thead>
            <tr>
              <th style="width: 320px;">Category Name</th>
              <th>Type</th>
              <th>Parent Category</th>
              <th>Associated Products</th>
              <th>Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="hierarchicalCategoryList.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-tags fs-1 d-block mb-2 text-muted"></i>
                No categories found. Click "Add Category" to create one.
              </td>
            </tr>

            <tr v-for="cat in hierarchicalCategoryList" :key="cat.uniqueKey" :class="{ 'bg-light bg-opacity-50': cat.isParent }">
              <td>
                <div class="d-flex align-items-center gap-2" :style="{ paddingLeft: cat.level * 20 + 'px' }">
                  <div class="cat-icon-badge" :class="cat.isParent ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary'">
                    <i :class="cat.isParent ? 'bi bi-folder2-open' : 'bi bi-tag'"></i>
                  </div>
                  <span :class="cat.isParent ? 'fw-bold text-dark fs-6' : 'fw-medium text-secondary'">
                    {{ cat.displayName }}
                  </span>
                </div>
              </td>

              <td>
                <span class="badge rounded-pill px-2.5 py-1" :style="cat.isParent ? 'background: #e0e7ff; color: #3730a3;' : 'background: #f1f5f9; color: #475569;'">
                  {{ cat.type }}
                </span>
              </td>

              <td>
                <span class="text-muted">{{ cat.parentCategoryName || '—' }}</span>
              </td>

              <td>
                <span class="fw-bold text-primary">{{ cat.productCount }} Products</span>
              </td>

              <td>
                <span class="stock-pill" :class="cat.status ? 'badge-in' : 'badge-out'">
                  <span class="pill-dot"></span> {{ cat.status ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <td style="text-align: right;">
                <div class="d-flex justify-content-end gap-1">
                  <button class="inv-act-btn" title="View Category" @click="viewCategoryItem(cat)">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="inv-act-btn" title="Edit Category" @click="editCategoryItem(cat)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="inv-act-btn text-danger" title="Delete Category" @click="deleteCategoryItem(cat)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ====================================================
         TAB 3: BRAND DIRECTORY
    ===================================================== -->
    <div v-if="activeTab === 'brands'">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h2 class="fs-5 fw-bold text-dark m-0">Brand Directory</h2>
          <p class="text-muted small m-0">Manage registered partner brands and assign them to catalog items.</p>
        </div>
        <button class="btn btn-sm btn-primary rounded-3 fw-semibold px-3" @click="openAddBrandModal" type="button">
          <i class="bi bi-plus-lg"></i> Add New Brand
        </button>
      </div>

      <div class="row g-3">
        <div v-if="brands.length === 0" class="col-12 text-center py-5">
          <div class="inv-card p-5">
            <i class="bi bi-award fs-1 text-muted"></i>
            <h5 class="fw-bold mt-2">No brands registered</h5>
            <button class="btn btn-sm btn-primary rounded-3 mt-2" @click="openAddBrandModal">
              <i class="bi bi-plus-lg"></i> Add Brand
            </button>
          </div>
        </div>

        <div v-for="b in brands" :key="b.id" class="col-md-4 col-lg-3">
          <div class="inv-card p-3 d-flex flex-column justify-content-between h-100">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-logo-box">
                <img v-if="b.image" :src="b.image" :alt="b.name" />
                <div v-else class="text-primary fw-bold fs-5">{{ (b.name || 'B').charAt(0).toUpperCase() }}</div>
              </div>
              <div class="flex-grow-1 overflow-hidden">
                <h3 class="fs-6 fw-bold text-dark m-0 text-truncate">{{ b.name }}</h3>
                <span class="text-muted small">{{ (products.filter(p => p.brand === b.name)).length }} Products</span>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-1 mt-3 pt-2 border-top">
              <button class="btn btn-sm btn-light text-danger p-1 px-2 rounded-2" @click="deleteBrandItem(b)" title="Delete Brand">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODAL: ADD / EDIT PRODUCT
    ===================================================== -->
    <div v-if="showProductModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">
              <i class="bi bi-box-seam me-1 text-primary"></i>
              {{ isEditingProduct ? 'Edit Inventory Product' : 'Add New Inventory Product' }}
            </h5>
            <button type="button" class="btn-close" @click="closeProductModal"></button>
          </div>

          <div class="modal-body px-4 py-3">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label small fw-semibold text-secondary">Product Name <span class="text-danger">*</span></label>
                <input v-model="productForm.name" type="text" class="form-control rounded-3" placeholder="e.g. Nestlé Pure Life 1.5L" />
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">SKU / Code <span class="text-danger">*</span></label>
                <input v-model="productForm.sku" type="text" class="form-control rounded-3" placeholder="SKU-1001" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Category</label>
                <select v-model="productForm.category" class="form-select rounded-3">
                  <option value="">Select Category</option>
                  <option v-for="c in categories" :key="c.id" :value="c.name">{{ c.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Sub-Category</label>
                <select v-model="productForm.subcategory" class="form-select rounded-3">
                  <option value="">Select Sub-Category</option>
                  <option v-for="s in subcategories" :key="s.id" :value="s.name">{{ s.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Brand</label>
                <select v-model="productForm.brand" class="form-select rounded-3">
                  <option value="">Select Brand</option>
                  <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Purchase Cost (PKR)</label>
                <input v-model.number="productForm.purchasePrice" type="number" step="0.01" class="form-control rounded-3" placeholder="0.00" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Selling Price (PKR) <span class="text-danger">*</span></label>
                <input v-model.number="productForm.sellingPrice" type="number" step="0.01" class="form-control rounded-3" placeholder="0.00" />
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Calculated Margin</label>
                <div class="p-2 rounded-3 bg-success-subtle text-success fw-bold text-center">
                  {{ calculateMarginDisplay(productForm.purchasePrice, productForm.sellingPrice) }}% Profit
                </div>
              </div>

              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Initial Stock</label>
                  <input v-model.number="productForm.stock" type="number" class="form-control rounded-3" placeholder="0" />
                </div>
                
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Min Reorder Alert</label>
                  <input v-model.number="productForm.minStock" type="number" class="form-control rounded-3" placeholder="5" />
                </div>
                
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-secondary">Expected Sell Date (Batch Limit)</label>
                  <input v-model="productForm.expectedSellDate" type="date" class="form-control rounded-3" />
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Unit of Measure</label>
                <select v-model="productForm.unit" class="form-select rounded-3">
                  <option value="Piece">Piece (pcs)</option>
                  <option value="Box">Box</option>
                  <option value="Kg">Kg</option>
                  <option value="Liter">Liter</option>
                  <option value="Pack">Pack</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Product Image</label>
                <input type="file" @change="handleProductImageUpload" accept="image/*" class="form-control rounded-3" />
                <div v-if="productForm.imagePreview" class="mt-2">
                  <img :src="productForm.imagePreview" style="height: 60px; border-radius: 8px; border: 1px solid #e2e8f0;" />
                </div>
              </div>

              
              <!-- DYNAMIC VARIATIONS BUILDER -->
              <div class="col-12">
                <div class="form-check form-switch mb-3 mt-2">
                  <input class="form-check-input" type="checkbox" id="hasVariationsSwitch" v-model="productForm.hasVariations" @change="generateVariations">
                  <label class="form-check-label fw-bold text-dark" for="hasVariationsSwitch">Product has variations (e.g., Size, Color, Pack)</label>
                </div>

                <div v-if="productForm.hasVariations" class="p-3 border rounded-3 bg-light">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="fw-bold text-secondary mb-0">Attributes</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addAttribute"><i class="bi bi-plus"></i> Add Attribute</button>
                  </div>
                  
                  <div v-for="(attr, idx) in productForm.attributes" :key="idx" class="row g-2 mb-2 align-items-center">
                    <div class="col-4">
                      <input type="text" class="form-control form-control-sm" v-model="attr.name" placeholder="Name (e.g. Size)" @input="generateVariations">
                    </div>
                    <div class="col-7">
                      <input type="text" class="form-control form-control-sm" v-model="attr.valuesText" placeholder="Values separated by comma (e.g. S, M, L)" @input="generateVariations">
                    </div>
                    <div class="col-1 text-end">
                      <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeAttribute(idx)"><i class="bi bi-trash"></i></button>
                    </div>
                  </div>

                  <div v-if="productForm.variations.length > 0" class="mt-4">
                    <label class="fw-bold text-secondary mb-2">Variation Matrix</label>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered bg-white" style="font-size: 13px;">
                        <thead class="bg-light">
                          <tr>
                            <th>Variant</th>
                            <th style="width: 140px;">SKU</th>
                            <th style="width: 120px;">Price (Rs)</th>
                            <th style="width: 100px;">Stock</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(v, vIdx) in productForm.variations" :key="vIdx">
                            <td class="align-middle fw-medium">{{ Object.values(v.attributes).join(' / ') }}</td>
                            <td><input type="text" class="form-control form-control-sm" v-model="v.sku"></td>
                            <td><input type="number" class="form-control form-control-sm" v-model.number="v.price"></td>
                            <td><input type="number" class="form-control form-control-sm" v-model.number="v.stock"></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-secondary">Description / Internal Notes</label>
                <textarea v-model="productForm.description" rows="2" class="form-control rounded-3" placeholder="Optional notes..."></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="closeProductModal">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3 px-4" @click="saveProduct">
              {{ isEditingProduct ? 'Update Product' : 'Save Product' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODAL: QUICK RESTOCK
    ===================================================== -->
    <div v-if="showRestockModal && restockProduct" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0"><i class="bi bi-box-arrow-in-down text-primary me-1"></i> Quick Restock</h5>
            <button type="button" class="btn-close" @click="showRestockModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="mb-3">
              <span class="text-muted small fw-semibold text-uppercase">Item</span>
              <div class="fw-bold text-dark fs-6">{{ restockProduct.name }}</div>
              <span class="text-muted small font-monospace">Current Stock: {{ restockProduct.stock || 0 }} {{ restockProduct.unit || 'pcs' }}</span>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Quantity to Add</label>
              <input v-model.number="restockQty" type="number" min="1" class="form-control rounded-3" />
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Unit Cost (PKR)</label>
              <input v-model.number="restockCost" type="number" step="0.01" class="form-control rounded-3" />
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showRestockModal = false">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3" @click="submitQuickRestock">
              Confirm Restock
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODAL: VIEW CATEGORY DETAILS
    ===================================================== -->
    <div v-if="showViewCategoryModal && selectedCategoryForView" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">Category Details</h5>
            <button type="button" class="btn-close" @click="showViewCategoryModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-light border">
              <div style="width: 54px; height: 54px; border-radius: 10px; overflow: hidden; background: #fff; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                <img v-if="selectedCategoryForView.image" :src="selectedCategoryForView.image" style="width: 100%; height: 100%; object-fit: cover;" />
                <i v-else class="bi bi-folder2-open text-primary fs-3"></i>
              </div>
              <div>
                <label class="text-muted small fw-semibold text-uppercase d-block m-0">Category Name</label>
                <div class="fs-5 fw-bold text-dark">{{ selectedCategoryForView.name }}</div>
              </div>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-6">
                <span class="text-muted small">Type:</span>
                <div class="fw-semibold">{{ selectedCategoryForView.type }}</div>
              </div>
              <div class="col-6" v-if="selectedCategoryForView.rawType === 'sub'">
                <span class="text-muted small">Parent:</span>
                <div class="fw-semibold">{{ selectedCategoryForView.parentCategoryName || '—' }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Associated Products:</span>
                <div class="fw-bold text-primary">{{ selectedCategoryForView.productCount }} Products</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Status:</span>
                <div>
                  <span class="stock-pill" :class="selectedCategoryForView.status ? 'badge-in' : 'badge-out'">
                    <span class="pill-dot"></span> {{ selectedCategoryForView.status ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showViewCategoryModal = false">Close</button>
            <button type="button" class="btn btn-primary rounded-3" @click="handleEditFromViewModal">
              <i class="bi bi-pencil me-1"></i> Edit Category
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODAL: ADD / EDIT CATEGORY
    ===================================================== -->
    <div v-if="showCategoryModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">
              {{ categoryForm.isEdit ? 'Edit Category' : 'Add New Category' }}
            </h5>
            <button type="button" class="btn-close" @click="showCategoryModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Category Name <span class="text-danger">*</span></label>
              <input v-model="categoryForm.name" type="text" class="form-control rounded-3" placeholder="e.g. Beverages" />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Hierarchy Level</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input v-model="categoryForm.categoryType" class="form-check-input" type="radio" value="parent" id="typeParent" />
                  <label class="form-check-label small fw-medium" for="typeParent">Parent Category</label>
                </div>
                <div class="form-check">
                  <input v-model="categoryForm.categoryType" class="form-check-input" type="radio" value="sub" id="typeSub" />
                  <label class="form-check-label small fw-medium" for="typeSub">Sub-Category</label>
                </div>
              </div>
            </div>

            <div v-if="categoryForm.categoryType === 'sub'" class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Parent Category</label>
              <select v-model="categoryForm.parentCategory" class="form-select rounded-3">
                <option value="">Select Parent Category</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Description</label>
              <textarea v-model="categoryForm.description" rows="2" class="form-control rounded-3" placeholder="Optional notes..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showCategoryModal = false">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3" @click="saveCategoryForm">
              {{ categoryForm.isEdit ? 'Save Changes' : 'Create Category' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODAL: ADD BRAND
    ===================================================== -->
    <div v-if="showBrandModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">Add New Brand</h5>
            <button type="button" class="btn-close" @click="showBrandModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Brand Name <span class="text-danger">*</span></label>
              <input v-model="brandForm.name" type="text" class="form-control rounded-3" placeholder="e.g. Nestlé" />
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Brand Logo</label>
              <input type="file" @change="handleBrandImageUpload" accept="image/*" class="form-control rounded-3" />
              <div v-if="brandForm.imagePreview" class="mt-2 text-center">
                <img :src="brandForm.imagePreview" style="height: 50px; border-radius: 8px; border: 1px solid #e2e8f0;" />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">Description</label>
              <textarea v-model="brandForm.description" rows="2" class="form-control rounded-3" placeholder="Optional notes..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showBrandModal = false">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3" @click="saveBrand">Save Brand</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODAL: VIEW PRODUCT DETAILS
    ===================================================== -->
    <div v-if="showDetailsModal && selectedProductDetails" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">Product Details</h5>
            <button type="button" class="btn-close" @click="showDetailsModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-light border">
              <div style="width: 60px; height: 60px; border-radius: 10px; overflow: hidden; background: #fff; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                <img v-if="selectedProductDetails.image" :src="selectedProductDetails.image" style="width: 100%; height: 100%; object-fit: cover;" />
                <i v-else class="bi bi-box text-primary fs-2"></i>
              </div>
              <div>
                <h4 class="fw-bold text-dark m-0 fs-5">{{ selectedProductDetails.name }}</h4>
                <span class="font-monospace text-muted small">{{ selectedProductDetails.sku || 'N/A' }}</span>
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <span class="text-muted small">Category:</span>
                <div class="fw-semibold">{{ selectedProductDetails.category || 'General' }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Brand:</span>
                <div class="fw-semibold">{{ selectedProductDetails.brand || '—' }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Purchase Cost:</span>
                <div class="fw-bold text-dark">PKR {{ formatMoney(selectedProductDetails.cost || selectedProductDetails.purchasePrice) }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Selling Price:</span>
                <div class="fw-bold text-primary fs-6">PKR {{ formatMoney(selectedProductDetails.price || selectedProductDetails.sellingPrice) }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Current Stock:</span>
                <div class="fw-bold fs-6">{{ selectedProductDetails.stock || 0 }} {{ selectedProductDetails.unit || 'pcs' }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Stock Status:</span>
                <div>
                  <span class="stock-pill" :class="getStockBadgeClass(selectedProductDetails.stock, selectedProductDetails.minStock)">
                    <span class="pill-dot"></span> {{ getStockStatusText(selectedProductDetails.stock, selectedProductDetails.minStock) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showDetailsModal = false">Close</button>
            <button type="button" class="btn btn-primary rounded-3" @click="showDetailsModal = false; openEditProductModal(selectedProductDetails)">
              <i class="bi bi-pencil me-1"></i> Edit Product
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TOAST -->
    <div class="inv-toast" :class="{ 'inv-toast-show': isToastOpen }">
      <i class="bi bi-check-circle-fill text-success"></i>
      <span>{{ toastMsg }}</span>
    </div>

  </div>
</template>

<style scoped>
.inv-page-root {
  padding: 24px 32px;
  background: #f8fafc;
  min-height: 100%;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

/* TOPBAR */
.inv-topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.inv-badge-pill {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: #e0e7ff;
  color: #4338ca;
}

.inv-main-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 4px 0;
}

.inv-main-sub {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
  max-width: 650px;
}

.inv-nav-pills-bar {
  display: flex;
  gap: 6px;
  background: #e2e8f0;
  padding: 4px;
  border-radius: 10px;
}

.inv-pill-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  color: #475569;
  border: none;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.inv-pill-btn:hover {
  color: #0f172a;
}

.inv-pill-btn.active {
  background: #ffffff;
  color: #2563eb;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

/* 9 KPI GRID */
.inv-9kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 14px;
}

.kpi-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.kpi-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
  flex-shrink: 0;
}

.kpi-data {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.kpi-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: #64748b;
  margin-bottom: 2px;
}

.kpi-num {
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0f172a;
  line-height: 1.1;
  margin-bottom: 2px;
  white-space: nowrap;
}

.kpi-sub {
  font-size: 11px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 3px;
}

/* CARD & TABLE */
.inv-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  overflow: hidden;
}

.inv-table-responsive {
  width: 100%;
  overflow-x: auto;
}

.inv-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.inv-table thead th {
  background: #f8fafc;
  color: #475569;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 10px 14px;
  border-bottom: 1px solid #e2e8f0;
}

.inv-filter-row th {
  background: #f1f5f9;
  padding: 8px 10px;
  border-bottom: 2px solid #e2e8f0;
}

.inv-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s ease;
}

.inv-table tbody tr:hover {
  background: #f8fafc;
}

.inv-table tbody td {
  padding: 11px 14px;
  vertical-align: middle;
}

.prod-thumb {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.prod-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumb-icon {
  font-size: 15px;
  color: #94a3b8;
}

.sku-code {
  font-size: 10.5px;
  background: #f1f5f9;
  color: #475569;
  padding: 1px 5px;
  border-radius: 4px;
}

.cat-pill {
  font-size: 11px;
  font-weight: 600;
  background: #eef2ff;
  color: #4338ca;
  padding: 2px 8px;
  border-radius: 5px;
}

.margin-badge {
  font-size: 11px;
  font-weight: 700;
  background: #ecfdf5;
  color: #059669;
  padding: 2px 6px;
  border-radius: 4px;
}

.stock-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 100px;
}

.pill-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
}

.badge-in { background: #ecfdf5; color: #059669; }
.badge-in .pill-dot { background: #10b981; }

.badge-low { background: #fffbeb; color: #d97706; }
.badge-low .pill-dot { background: #f59e0b; }

.badge-out { background: #fef2f2; color: #dc2626; }
.badge-out .pill-dot { background: #ef4444; }

.inv-act-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #475569;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  font-size: 12px;
}

.inv-act-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.inv-table-footer {
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  border-top: 1px solid #f1f5f9;
}

.cat-icon-badge {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  flex-shrink: 0;
}

.brand-logo-box {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}

.brand-logo-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* TOAST */
.inv-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #0f172a;
  color: #ffffff;
  padding: 12px 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13.5px;
  font-weight: 500;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  z-index: 9999;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.inv-toast-show {
  transform: translateY(0);
  opacity: 1;
}

/* CATEGORIES FILTER BAR */
.cat-filter-bar {
  padding: 14px 18px;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.cat-search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0 12px;
  height: 38px;
  flex: 1 1 240px;
  max-width: 320px;
  color: #64748b;
  box-sizing: border-box;
}

.cat-search-box input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 13px;
  width: 100%;
  color: #0f172a;
}

.cat-filter-select {
  height: 38px;
  border: 1px solid #e2e8f0;
  background-color: #f8fafc;
  border-radius: 8px;
  padding: 0 12px;
  font-size: 13px;
  font-weight: 500;
  color: #334155;
  outline: none;
  cursor: pointer;
  box-sizing: border-box;
}

.cat-filter-select:focus {
  border-color: #2563eb;
  background-color: #ffffff;
}

.cat-select-type {
  width: 140px;
  flex-shrink: 0;
}

.cat-select-parent {
  width: 180px;
  flex-shrink: 0;
}

.cat-select-sub {
  width: 180px;
  flex-shrink: 0;
}

.cat-select-status {
  width: 130px;
  flex-shrink: 0;
}

.cat-btn-reset {
  height: 38px;
  padding: 0 14px;
  border-radius: 8px;
  border: 1px solid #fca5a5;
  background: #ffffff;
  color: #dc2626;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  box-sizing: border-box;
  transition: all 0.15s ease;
}

.cat-btn-reset:hover {
  background: #fef2f2;
  border-color: #ef4444;
}

.cat-btn-add {
  height: 38px;
  padding: 0 16px;
  border-radius: 8px;
  border: none;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  box-sizing: border-box;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.cat-btn-add:hover {
  background: #1d4ed8;
}

@media (max-width: 992px) {
  .inv-9kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>