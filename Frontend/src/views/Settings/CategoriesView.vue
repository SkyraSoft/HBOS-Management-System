<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRetailStore } from '@/stores/retail.js'

const store = useRetailStore()

onMounted(async () => {
  await store.fetchCategories()
  await store.fetchSubcategories()
  await store.fetchProducts()
})

const categories = computed(() => store.categories || [])
const subcategories = computed(() => store.subcategories || [])
const products = computed(() => store.products || [])

const searchQuery = ref('')
const typeFilter = ref('all')

const totalParentCount = computed(() => categories.value.length)
const totalSubCount = computed(() => subcategories.value.length)
const totalCategoriesCount = computed(() => categories.value.length + subcategories.value.length)

// Hierarchical List
const hierarchicalList = computed(() => {
  const list = []
  const q = searchQuery.value.trim().toLowerCase()
  const processedSubIds = new Set()

  for (const cat of (categories.value || [])) {
    const catProducts = (products.value || []).filter(p => p.category === cat.name)
    const childSubs = (subcategories.value || []).filter(s => {
      if (s.category_id && String(s.category_id) === String(cat.id)) return true
      if (s.category && (String(s.category.id) === String(cat.id) || s.category.name === cat.name)) return true
      const inProd = (products.value || []).some(p => p.subcategory === s.name && p.category === cat.name)
      return inProd
    })

    childSubs.forEach(s => processedSubIds.add(s.id))

    const catImg = cat.image || cat.icon || catProducts.find(p => p.image)?.image || ''
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
      status: cat.status !== 0 && cat.status !== false && cat.is_active !== 0,
      productCount: catProducts.length,
      childCount: childSubs.length,
      uniqueKey: 'cat-' + cat.id
    }

    const childItems = childSubs.map(sub => {
      const subProducts = (products.value || []).filter(p => p.subcategory === sub.name)
      const subImg = sub.image || sub.icon || subProducts.find(p => p.image)?.image || ''
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
        status: sub.status !== 0 && sub.status !== false && sub.is_active !== 0,
        productCount: subProducts.length,
        childCount: 0,
        uniqueKey: 'sub-' + sub.id
      }
    })

    if (!q) {
      if (typeFilter.value === 'all') {
        list.push(parentItem, ...childItems)
      } else if (typeFilter.value === 'parent') {
        list.push(parentItem)
      } else if (typeFilter.value === 'sub') {
        list.push(...childItems)
      }
    } else {
      const parentMatches = parentItem.name.toLowerCase().includes(q) || parentItem.description.toLowerCase().includes(q)
      const matchingChildren = childItems.filter(c => c.name.toLowerCase().includes(q) || c.description.toLowerCase().includes(q))
      if (parentMatches || matchingChildren.length > 0) {
        if (typeFilter.value === 'all') {
          list.push(parentItem, ...(parentMatches ? childItems : matchingChildren))
        } else if (typeFilter.value === 'parent' && parentMatches) {
          list.push(parentItem)
        } else if (typeFilter.value === 'sub') {
          list.push(...matchingChildren)
        }
      }
    }
  }

  // Orphan subcategories
  const remainingSubs = (subcategories.value || []).filter(s => !processedSubIds.has(s.id))
  for (const sub of remainingSubs) {
    const subProducts = (products.value || []).filter(p => p.subcategory === sub.name)
    const subImg = sub.image || sub.icon || subProducts.find(p => p.image)?.image || ''
    const item = {
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
      status: sub.status !== 0 && sub.status !== false && sub.is_active !== 0,
      productCount: subProducts.length,
      childCount: 0,
      uniqueKey: 'sub-' + sub.id
    }
    if (!q || item.name.toLowerCase().includes(q) || item.description.toLowerCase().includes(q)) {
      if (typeFilter.value === 'all' || typeFilter.value === 'sub') {
        list.push(item)
      }
    }
  }

  return list
})

// MODAL: ADD / EDIT
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

const openAddModal = () => {
  categoryForm.id = null
  categoryForm.isEdit = false
  categoryForm.name = ''
  categoryForm.categoryType = 'parent'
  categoryForm.parentCategory = ''
  categoryForm.description = ''
  categoryForm.status = true
  showCategoryModal.value = true
}

const openEditModal = (item) => {
  categoryForm.id = item.id
  categoryForm.isEdit = true
  categoryForm.name = item.name
  categoryForm.categoryType = item.rawType === 'sub' ? 'sub' : 'parent'
  categoryForm.parentCategory = item.parentId || ''
  categoryForm.description = item.description || ''
  categoryForm.status = item.status !== false
  showCategoryModal.value = true
}

const saveCategory = async () => {
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

const deleteCategory = async (item) => {
  if (confirm(`Delete ${item.type} "${item.name}"?`)) {
    if (item.rawType === 'sub') {
      await store.removeSubcategory(item.id)
    } else {
      await store.removeCategory(item.id)
    }
    showToast('Category deleted!')
  }
}

// MODAL: VIEW DETAILS
const showViewModal = ref(false)
const selectedCategory = ref(null)

const viewCategory = (item) => {
  selectedCategory.value = item
  showViewModal.value = true
}

const handleEditFromView = () => {
  const item = selectedCategory.value
  showViewModal.value = false
  selectedCategory.value = null
  if (item) openEditModal(item)
}

// Toast
const toastMsg = ref('')
const isToastOpen = ref(false)
const showToast = (msg) => {
  toastMsg.value = msg
  isToastOpen.value = true
  setTimeout(() => { isToastOpen.value = false }, 2500)
}
</script>

<template>
  <div class="cat-settings-root">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
      <div>
        <h1 class="fs-4 fw-bold text-dark m-0">Categories & Hierarchy Configuration</h1>
        <p class="text-muted small m-0">Configure master categories, nested sub-categories, and POS department mappings.</p>
      </div>

      <button class="btn btn-primary btn-sm fw-semibold rounded-3 px-3 shadow-sm" @click="openAddModal" type="button">
        <i class="bi bi-plus-lg"></i> Add New Category
      </button>
    </div>

    <!-- KPI Metric Summary -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center gap-3">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="bi bi-tags"></i>
          </div>
          <div>
            <span class="text-muted small fw-bold text-uppercase">Total Categories</span>
            <div class="fs-4 fw-bold text-dark lh-1">{{ totalCategoriesCount }}</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center gap-3">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #f3e8ff; color: #7e22ce; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="bi bi-folder2-open"></i>
          </div>
          <div>
            <span class="text-muted small fw-bold text-uppercase">Parent Categories</span>
            <div class="fs-4 fw-bold text-dark lh-1">{{ totalParentCount }}</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center gap-3">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="bi bi-diagram-3"></i>
          </div>
          <div>
            <span class="text-muted small fw-bold text-uppercase">Sub-Categories</span>
            <div class="fs-4 fw-bold text-dark lh-1">{{ totalSubCount }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <!-- Toolbar -->
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 380px;">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input v-model="searchQuery" type="text" class="form-control bg-light border-start-0" placeholder="Search category by name or description..." />
          </div>
        </div>

        <div class="d-flex gap-2">
          <select v-model="typeFilter" class="form-select form-select-sm rounded-3">
            <option value="all">All Category Types</option>
            <option value="parent">Parent Categories Only</option>
            <option value="sub">Sub-Categories Only</option>
          </select>
        </div>
      </div>

      <!-- Hierarchical Table -->
      <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 13.5px;">
          <thead class="table-light text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">
            <tr>
              <th style="width: 320px;" class="ps-4">Category Name</th>
              <th>Type</th>
              <th>Parent Category</th>
              <th>Associated Products</th>
              <th>Status</th>
              <th style="text-align: right;" class="pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="hierarchicalList.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-tags fs-1 d-block mb-2 text-muted"></i>
                No categories found. Click "Add New Category" to create one.
              </td>
            </tr>
            <tr v-for="cat in hierarchicalList" :key="cat.uniqueKey" :class="{ 'bg-light bg-opacity-50': cat.isParent }">
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2" :style="{ paddingLeft: cat.level * 20 + 'px' }">
                  <div style="width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 13px;" :style="cat.isParent ? 'background: #e0e7ff; color: #3730a3;' : 'background: #f1f5f9; color: #475569;'">
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
                <span class="badge rounded-pill px-2.5 py-1" :class="cat.status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                  {{ cat.status ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <td style="text-align: right;" class="pe-4">
                <div class="d-flex justify-content-end gap-1">
                  <button class="btn btn-sm btn-light border p-1 px-2 rounded-2" title="View Details" @click="viewCategory(cat)">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="btn btn-sm btn-light border p-1 px-2 rounded-2" title="Edit" @click="openEditModal(cat)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-light border text-danger p-1 px-2 rounded-2" title="Delete" @click="deleteCategory(cat)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: ADD / EDIT CATEGORY -->
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
                  <input v-model="categoryForm.categoryType" class="form-check-input" type="radio" value="parent" id="mgrTypeParent" />
                  <label class="form-check-label small fw-medium" for="mgrTypeParent">Parent Category</label>
                </div>
                <div class="form-check">
                  <input v-model="categoryForm.categoryType" class="form-check-input" type="radio" value="sub" id="mgrTypeSub" />
                  <label class="form-check-label small fw-medium" for="mgrTypeSub">Sub-Category</label>
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

            <div class="form-check form-switch">
              <input v-model="categoryForm.status" class="form-check-input" type="checkbox" id="catStatusSwitch" />
              <label class="form-check-label small fw-medium" for="catStatusSwitch">Category Active Status</label>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showCategoryModal = false">Cancel</button>
            <button type="button" class="btn btn-primary rounded-3 px-3" @click="saveCategory">
              {{ categoryForm.isEdit ? 'Save Changes' : 'Create Category' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: VIEW DETAILS -->
    <div v-if="showViewModal && selectedCategory" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 bg-light px-4 py-3">
            <h5 class="modal-title fw-bold text-dark m-0">Category Details</h5>
            <button type="button" class="btn-close" @click="showViewModal = false"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-light border">
              <div style="width: 54px; height: 54px; border-radius: 10px; overflow: hidden; background: #fff; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                <img v-if="selectedCategory.image" :src="selectedCategory.image" style="width: 100%; height: 100%; object-fit: cover;" />
                <i v-else class="bi bi-folder2-open text-primary fs-3"></i>
              </div>
              <div>
                <label class="text-muted small fw-semibold text-uppercase d-block m-0">Category Name</label>
                <div class="fs-5 fw-bold text-dark">{{ selectedCategory.name }}</div>
              </div>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-6">
                <span class="text-muted small">Type:</span>
                <div class="fw-semibold">{{ selectedCategory.type }}</div>
              </div>
              <div class="col-6" v-if="selectedCategory.rawType === 'sub'">
                <span class="text-muted small">Parent:</span>
                <div class="fw-semibold">{{ selectedCategory.parentCategoryName || '—' }}</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Associated Products:</span>
                <div class="fw-bold text-primary">{{ selectedCategory.productCount }} Products</div>
              </div>
              <div class="col-6">
                <span class="text-muted small">Status:</span>
                <div>
                  <span class="badge rounded-pill px-2.5 py-1" :class="selectedCategory.status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                    {{ selectedCategory.status ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light px-4 py-2.5">
            <button type="button" class="btn btn-light rounded-3" @click="showViewModal = false">Close</button>
            <button type="button" class="btn btn-primary rounded-3" @click="handleEditFromView">
              <i class="bi bi-pencil me-1"></i> Edit Category
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-custom" :class="{ 'toast-custom-show': isToastOpen }">
      <i class="bi bi-check-circle-fill text-success"></i>
      <span>{{ toastMsg }}</span>
    </div>
  </div>
</template>

<style scoped>
.cat-settings-root {
  padding: 8px;
  font-family: 'Inter', system-ui, sans-serif;
}

.toast-custom {
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

.toast-custom-show {
  transform: translateY(0);
  opacity: 1;
}
</style>
