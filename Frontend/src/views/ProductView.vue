<template>
  <div class="pv-app">

    <!-- PAGE CONTENT -->
    <div class="pv-page" :style="isModal ? 'padding: 0;' : ''">

        <!-- ====================================================
             PRODUCTS LIST PAGE
        ===================================================== -->
        <section v-if="currentPage === 'products'" class="pv-section">
          <div class="pv-page-header">
            <div>
              <h1 class="pv-page-title">Products</h1>
              <p class="pv-page-subtitle">Manage your products, pricing, stock and product information.</p>
            </div>
            <div class="pv-header-actions">
              <button class="pv-btn-outline" @click="showPage('details')" :disabled="!selectedProductId" type="button">
                <i class="bi bi-card-list me-1"></i>Product Details
              </button>
              <button class="pv-btn-primary" @click="showPage('add')" type="button">
                <i class="bi bi-plus-lg me-1"></i>Add New Product
              </button>
            </div>
          </div>

          <!-- STATS -->
          <div class="pv-stats-grid">
            <div class="pv-stat-card">
              <div class="pv-stat-label">TOTAL PRODUCTS</div>
              <div class="pv-stat-value">{{ store.products.length }}</div>
            </div>
            <div class="pv-stat-card">
              <div class="pv-stat-label">ACTIVE PRODUCTS</div>
              <div class="pv-stat-value">{{ activeCount }}</div>
            </div>
            <div class="pv-stat-card">
              <div class="pv-stat-label">LOW STOCK</div>
              <div class="pv-stat-value pv-text-danger">{{ lowStockCount }}</div>
            </div>
            <div class="pv-stat-card">
              <div class="pv-stat-label">TOTAL STOCK</div>
              <div class="pv-stat-value">{{ totalStock }}</div>
            </div>
          </div>

          <!-- TABLE CARD -->
          <div class="pv-card">
            <div class="pv-toolbar">
              <h2 class="pv-card-title">Product Directory</h2>
              <div class="pv-search-box">
                <i class="bi bi-search"></i>
                <input type="text" v-model="search" placeholder="Search products, SKU...">
              </div>
            </div>

            <div class="pv-table-wrapper">
              <table class="pv-table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Brand</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                    <th>SKU</th>
                    <th>Purchase</th>
                    <th>Selling</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="product in filteredProducts" :key="product.id">
                      <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                          <img v-if="product.image" :src="product.image" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;" :alt="product.name" loading="lazy">
                            <div v-else style="width: 40px; height: 40px; border-radius: 6px; background: #e9efff; color: #2447c6; display: flex; align-items: center; justify-content: center;"><i class="bi bi-image"></i></div>
                          <div>
                            <div class="pv-product-name">{{ product.name }}</div>
                            <div class="pv-product-sku">Product ID #{{ product.id }}</div>
                          </div>
                        </div>
                      </td>
                    <td>{{ product.brand || '—' }}</td>
                    <td>{{ product.category || '—' }}</td>
                    <td>{{ product.subcategory || '—' }}</td>
                    <td>{{ product.sku || '—' }}</td>
                    <td style="font-weight:500;">PKR {{ formatNumber(product.cost) }}</td>
                    <td style="font-weight:500;">PKR {{ formatNumber(product.price) }}</td>
                    <td>
                      <span class="pv-stock-val">{{ formatNumber(product.stock) }}</span>
                      <span class="pv-stock-unit"> {{ product.unit }}</span>
                    </td>
                    <td>
                      <span class="pv-badge" :class="statusClass(product)">{{ statusLabel(product) }}</span>
                    </td>
                    <td>
                      <div class="pv-row-actions">
                        <button class="pv-icon-btn" title="View Details" @click="showPage('details', product.id)" type="button">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="pv-icon-btn" title="Edit Product" @click="showPage('edit', product.id)" type="button">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="pv-icon-btn pv-icon-btn-danger" title="Delete Product" @click="deleteProduct(product.id)" type="button">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="filteredProducts.length === 0" class="pv-empty-state">
              <div class="pv-empty-icon"><i class="bi bi-box-seam"></i></div>
              <h3>No Products Yet</h3>
              <p>Start by adding your first product to the product directory.</p>
              <button class="pv-btn-primary" @click="showPage('add')" type="button">
                <i class="bi bi-plus-lg me-1"></i>Add New Product
              </button>
            </div>
          </div>
        </section>


        <!-- ====================================================
             ADD PRODUCT PAGE
        ===================================================== -->
        <section v-if="currentPage === 'add'" class="pv-section">
          <div class="pv-page-header">
            <div>
              <h1 class="pv-page-title">Add New Product</h1>
              <p class="pv-page-subtitle">Add product information, pricing, and stock settings.</p>
            </div>
            <div class="pv-header-actions">
              <button class="pv-btn-outline" v-if="!isModal" @click="showPage('products')" type="button">Cancel</button>
              
            </div>
          </div>

          <!-- 2-column desktop layout: main form left, summary right -->
          <div class="pv-edit-layout">
            <!-- LEFT: MAIN FORM -->
            <div class="pv-edit-main">
              <!-- BASIC INFORMATION -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-info-circle"></i> Basic Information</div>
                <div class="row g-4">
                  <div class="col-12">
                    <label class="pv-label">Product Name *</label>
                    <input class="pv-input" v-model="form.name" placeholder="e.g. Coca Cola 1.5L">
                  </div>
                  
                    <div class="col-md-6">
                      <label class="pv-label">Category</label>
                      <select class="pv-input" v-model="form.category" @change="onCategoryChangeAdd">
                        <option value="">Select Category</option>
                        <option v-for="cat in store.categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">Sub-Category</label>
                      <select class="pv-input" v-model="form.subcategory">
                        <option value="">Select Sub-Category</option>
                        <option v-for="sub in availableSubcategoriesForAdd" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">Brand Name</label>
                      <input type="text" class="pv-input" v-model="form.brand" list="brandsListOptions" placeholder="Select or type brand (e.g. Nestlé, Apple)">
                      <datalist id="brandsListOptions">
                        <option v-for="b in store.brands" :key="b.id" :value="b.name"></option>
                      </datalist>
                    </div>
                  <div class="col-md-6">
                    <label class="pv-label">SKU (Stock Keeping Unit)</label>
                    <div class="pv-input-group">
                      <input class="pv-input" v-model="form.sku" placeholder="e.g. BEV-001">
                      <button class="pv-input-addon" type="button" @click="generateSKU()">
                        <i class="bi bi-arrow-repeat"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="pv-label">Description (Optional)</label>
                    <textarea class="pv-input" v-model="form.description" rows="3"
                      placeholder="Short description for internal notes or receipt..."></textarea>
                  </div>
                </div>
              </div>

              <!-- PRICING -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-cash-stack"></i> Pricing</div>
                <div class="row g-4">
                  <div class="col-md-6">
                    <label class="pv-label">Purchase Price (PKR) *</label>
                    <div class="pv-input-group">
                      <span class="pv-input-prefix">Rs</span>
                      <input type="number" class="pv-input" v-model.number="form.cost" min="0" step="0.01">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="pv-label">Selling Price (PKR) *</label>
                    <div class="pv-input-group">
                      <span class="pv-input-prefix">Rs</span>
                      <input type="number" class="pv-input" v-model.number="form.price" min="0" step="0.01">
                    </div>
                  </div>
                </div>
                <div class="pv-price-result">
                  <div class="pv-price-item">
                    <div class="pv-price-label">Profit Per Unit</div>
                    <div class="pv-price-value">Rs {{ profitPerUnit.toFixed(2) }}</div>
                  </div>
                  <div class="pv-price-item">
                    <div class="pv-price-label">Profit Margin</div>
                    <div class="pv-price-value pv-text-success">{{ profitMargin.toFixed(2) }}%</div>
                  </div>
                </div>
              </div>

              <!-- INVENTORY -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-boxes"></i> Inventory Settings</div>
                <div class="row g-4">
                  <div class="col-md-6">
                    <label class="pv-label">Unit of Measure *</label>
                    <select class="pv-input" v-model="form.unit">
                      <option>Piece (pc)</option>
                      <option>Box</option>
                      <option>Pack</option>
                      <option>Kg</option>
                      <option>Liter</option>
                      <option>Dozen</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="pv-label">Initial Stock</label>
                    <input type="number" class="pv-input" v-model.number="form.stock" min="0">
                  </div>
                  <div class="col-md-6">
                    <label class="pv-label">Minimum Stock Level</label>
                    <input type="number" class="pv-input" v-model.number="form.minStock" min="0">
                  </div>
                  <div class="col-md-6">
                    <label class="pv-label">Maximum Stock Level</label>
                    <input type="number" class="pv-input" v-model.number="form.maxStock" min="0">
                  </div>
                </div>
              </div>

              <!-- ATTRIBUTES & VARIATIONS SECTION -->
              <div class="pv-form-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="pv-section-heading m-0"><i class="bi bi-tags"></i> Product Attributes & Variations</div>
                  <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="hasVariationsAdd" v-model="form.hasVariations" style="cursor: pointer; width: 40px; height: 20px;">
                    <label class="form-check-label small fw-bold text-dark" for="hasVariationsAdd" style="cursor: pointer;">Enable Variations</label>
                  </div>
                </div>

                <div v-if="form.hasVariations">
                  <p class="text-secondary small mb-3">
                    Add custom attributes (such as Size, Weight, Color, or Pack Type) and add multiple values to generate product variations.
                  </p>

                  <!-- PRESET ATTRIBUTE BUTTONS -->
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="small fw-semibold text-muted">Quick Presets:</span>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(form, 'Size')">+ Size</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(form, 'Weight')">+ Weight</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(form, 'Color')">+ Color</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(form, 'Pack Type')">+ Pack Type</button>
                  </div>

                  <!-- ATTRIBUTES LIST -->
                  <div class="attributes-container mb-3">
                    <div v-for="(attr, aIndex) in form.attributes" :key="aIndex" class="attribute-item-card p-3 mb-3 bg-light rounded-3 border">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1 me-3">
                          <label class="pv-label m-0 fw-bold" style="white-space: nowrap;">Attribute Name:</label>
                          <input type="text" class="pv-input py-1 px-2" v-model="attr.name" placeholder="e.g. Size, Color, Pack Type" style="max-width: 220px;" @input="generateVariationsFromAttributes(form)" />
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeAttribute(form, aIndex)" title="Delete Attribute">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>

                      <!-- VALUES CHIPS & INPUT -->
                      <div class="mt-2">
                        <label class="pv-label mb-1 small text-muted">Values / Options:</label>
                        <div class="d-flex flex-wrap align-items-center gap-2 p-2 bg-white rounded border">
                          <span v-for="(val, vIndex) in attr.values" :key="vIndex" class="badge bg-primary text-white rounded-pill py-1.5 px-3 d-flex align-items-center gap-2 fs-7">
                            <span>{{ val }}</span>
                            <span role="button" class="fw-bold" style="cursor: pointer;" @click="removeAttributeValue(form, aIndex, vIndex)">×</span>
                          </span>
                          <div class="d-flex align-items-center gap-1 flex-grow-1" style="min-width: 180px;">
                            <input 
                              type="text" 
                              class="form-control form-control-sm border-0 shadow-none px-1" 
                              v-model="attr.newValueInput" 
                              placeholder="Type value & press Enter..." 
                              @keydown.enter.prevent="addAttributeValue(form, aIndex)" 
                            />
                            <button type="button" class="btn btn-sm btn-light border px-2 py-0.5 text-secondary" @click="addAttributeValue(form, aIndex)">
                              + Add
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2 rounded-3 border-dashed" @click="addNewAttribute(form)">
                      <i class="bi bi-plus-circle me-1"></i> Add Custom Attribute
                    </button>
                  </div>

                  <!-- GENERATED VARIATIONS TABLE -->
                  <div v-if="form.variations && form.variations.length > 0" class="variations-table-wrapper mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="fw-bold text-dark fs-6"><i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Generated Variations ({{ form.variations.length }})</div>
                      <button type="button" class="btn btn-sm btn-outline-primary" @click="generateVariationsFromAttributes(form)">
                        <i class="bi bi-arrow-repeat me-1"></i> Regenerate
                      </button>
                    </div>

                    <div class="table-responsive border rounded-3 bg-white">
                      <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light">
                          <tr>
                            <th class="ps-3">Variation</th>
                            <th>SKU</th>
                            <th style="width: 130px;">Price (PKR)</th>
                            <th style="width: 110px;">Stock</th>
                            <th class="text-center" style="width: 80px;">Active</th>
                            <th class="text-end pe-3" style="width: 60px;">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(v, vIdx) in form.variations" :key="vIdx">
                            <td class="ps-3 fw-semibold text-dark">{{ v.name }}</td>
                            <td>
                              <input type="text" class="form-control form-control-sm" v-model="v.sku" />
                            </td>
                            <td>
                              <input type="number" step="0.01" class="form-control form-control-sm" v-model.number="v.price" />
                            </td>
                            <td>
                              <input type="number" class="form-control form-control-sm" v-model.number="v.stock" />
                            </td>
                            <td class="text-center">
                              <input type="checkbox" class="form-check-input" v-model="v.active" />
                            </td>
                            <td class="text-end pe-3">
                              <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="form.variations.splice(vIdx, 1)">
                                <i class="bi bi-trash"></i>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ADDITIONAL SETTINGS -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-sliders"></i> Additional Settings</div>
                <div class="row g-4">
                  <div class="col-md-6">
                    <div class="pv-switch-row mb-4">
                      <input class="pv-switch" type="checkbox" id="batchTracking" v-model="form.batchTracking">
                      <div>
                        <label class="pv-switch-label" for="batchTracking">Enable Batch & Expiry Tracking</label>
                        <div class="pv-switch-desc">Useful for perishable goods.</div>
                      </div>
                    </div>
                    <div class="pv-switch-row">
                      <input class="pv-switch" type="checkbox" id="productStatus" v-model="form.active">
                      <div>
                        <label class="pv-switch-label" for="productStatus">Product Status</label>
                        <div class="pv-switch-desc">Active products appear in POS.</div>
                      </div>
                    </div>
                  </div>
                                    <div class="col-md-6">
                      <label class="pv-label">Product Image (Optional)</label>
                      <label class="pv-image-upload" :style="form.imagePreview ? 'padding: 0; overflow: hidden; border-style: solid;' : ''">
                        <input type="file" accept="image/*" hidden @change="handleImageUpload">
                        <template v-if="!form.imagePreview">
                          <i class="bi bi-cloud-arrow-up"></i>
                          <strong>Click to upload or drag and drop</strong>
                          <small>JPG, PNG up to 2MB</small>
                        </template>
                        <img v-else :src="form.imagePreview" style="width: 100%; height: 100%; max-height: 140px; object-fit: contain; display: block;" alt="Preview">
                      </label>
                      <div v-if="form.imageName && !form.imagePreview" class="mt-2 text-success small">
                        <i class="bi bi-image me-1"></i>{{ form.imageName }}
                      </div>
                    </div>
                </div>
              </div>

              <div class="pv-form-footer">
                <button class="pv-btn-outline" @click="isModal ? emit('close') : showPage('products')" type="button">Cancel</button>
                <button class="pv-btn-primary" @click="saveProduct()" type="button">
                  <i class="bi bi-check2 me-1"></i>Save Product
                </button>
              </div>
            </div>

            <!-- RIGHT: LIVE PREVIEW SIDEBAR -->
            <div class="pv-edit-sidebar" v-if="!isModal">
              <div class="pv-detail-card pv-edit-summary-card">
                <div class="pv-detail-card-title"><i class="bi bi-eye"></i> Live Preview</div>
                <div class="pv-edit-summary-name">{{ form.name || 'Product Name' }}</div>
                <div class="pv-edit-summary-sku">SKU: {{ form.sku || 'Auto-generated' }}</div>
                <div class="pv-summary-divider"></div>
                <div class="pv-summary-row"><span>Category</span><span>{{ form.category || '—' }}</span></div>
                <div class="pv-summary-row"><span>Unit</span><span>{{ form.unit }}</span></div>
                <div class="pv-summary-row"><span>Purchase Price</span><span>Rs {{ formatNumber(form.cost) }}</span></div>
                <div class="pv-summary-row"><span>Selling Price</span><span>Rs {{ formatNumber(form.price) }}</span></div>
                <div class="pv-summary-row pv-text-success"><span>Profit/Unit</span><span>Rs {{ profitPerUnit.toFixed(2) }}</span></div>
                <div class="pv-summary-row pv-text-success"><span>Margin</span><span>{{ profitMargin.toFixed(2) }}%</span></div>
                <div class="pv-summary-row"><span>Initial Stock</span><span>{{ form.stock }}</span></div>
                <div class="pv-summary-row">
                  <span>Status</span>
                  <span class="pv-badge" :class="form.active ? 'pv-badge-active' : 'pv-badge-out'">
                    {{ form.active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>

              <div class="pv-detail-card">
                <div class="pv-detail-card-title"><i class="bi bi-lightbulb"></i> Tips</div>
                <ul class="pv-tips-list">
                  <li>Use a descriptive product name for easy search.</li>
                  <li>Set a minimum stock level to get low-stock alerts.</li>
                  <li>The SKU generator creates a unique code based on category.</li>
                  <li>Only active products will appear in the POS system.</li>
                </ul>
              </div>
            </div>
          </div>
        </section>


        <!-- ====================================================
             PRODUCT DETAILS PAGE
        ===================================================== -->
        <section v-if="currentPage === 'details'" class="pv-section">
          <div class="pv-page-header">
            <div>
              <h1 class="pv-page-title">Product Details</h1>
              <p class="pv-page-subtitle">Complete information about the selected product.</p>
            </div>
            <div class="pv-header-actions">
              <button class="pv-btn-outline" @click="showPage('products')" type="button">Back to Products</button>
              <button v-if="selectedProduct" class="pv-btn-primary" @click="showPage('edit', selectedProduct.id)" type="button">
                <i class="bi bi-pencil me-1"></i>Edit Product
              </button>
            </div>
          </div>

          <!-- No product selected yet -->
          <div v-if="!selectedProduct" class="pv-empty-state pv-card" style="margin-top:0;">
            <div class="pv-empty-icon"><i class="bi bi-card-list"></i></div>
            <h3>No Product Selected</h3>
            <p>Please select a product from the Products list to view its details.</p>
            <button class="pv-btn-primary" @click="showPage('products')" type="button">
              <i class="bi bi-arrow-left me-1"></i>Go to Products
            </button>
          </div>

          <div v-if="selectedProduct" class="pv-detail-grid">
            <!-- LEFT COLUMN -->
            <div class="pv-detail-left">

              <!-- Product Header Card -->
              <div class="pv-detail-card pv-detail-hero">
                  <div class="pv-detail-hero-avatar" style="overflow: hidden; padding: 0;">
                    <img v-if="selectedProduct.image" :src="selectedProduct.image" style="width: 100%; height: 100%; object-fit: cover;" :alt="selectedProduct.name">
                      <div v-else style="width: 100%; height: 100%; background: #e9efff; color: #2447c6; display: flex; align-items: center; justify-content: center; font-size: 40px;"><i class="bi bi-image"></i></div>
                  </div>
                <div class="pv-detail-hero-info">
                  <div class="pv-detail-product-name">{{ selectedProduct.name }}</div>
                  <div class="pv-detail-sku">SKU: {{ selectedProduct.sku || '—' }}</div>
                  <div class="mt-2">
                    <span class="pv-badge" :class="statusClass(selectedProduct)">{{ statusLabel(selectedProduct) }}</span>
                    <span class="pv-badge pv-badge-cat ms-2">{{ selectedProduct.category || 'Uncategorized' }}</span>
                  </div>
                </div>
              </div>

              <!-- Key Stats Row -->
              <div class="pv-detail-stats-row">
                <div class="pv-detail-stat-box">
                  <div class="pv-detail-stat-icon pv-icon-blue"><i class="bi bi-boxes"></i></div>
                  <div>
                    <div class="pv-detail-stat-val">{{ selectedProduct.stock }}</div>
                    <div class="pv-detail-stat-lbl">Current Stock</div>
                  </div>
                </div>
                <div class="pv-detail-stat-box">
                  <div class="pv-detail-stat-icon pv-icon-green"><i class="bi bi-tag"></i></div>
                  <div>
                    <div class="pv-detail-stat-val">Rs {{ formatNumber(selectedProduct.price) }}</div>
                    <div class="pv-detail-stat-lbl">Selling Price</div>
                  </div>
                </div>
                <div class="pv-detail-stat-box">
                  <div class="pv-detail-stat-icon pv-icon-orange"><i class="bi bi-graph-up"></i></div>
                  <div>
                    <div class="pv-detail-stat-val">
                      {{ selectedProduct.price > 0 ? (((selectedProduct.price - selectedProduct.cost) / selectedProduct.price) * 100).toFixed(1) : '0.0' }}%
                    </div>
                    <div class="pv-detail-stat-lbl">Profit Margin</div>
                  </div>
                </div>
              </div>

              <!-- Product Information Card -->
              <div class="pv-detail-card">
                <div class="pv-detail-card-title">
                  <i class="bi bi-info-circle"></i> Product Information
                </div>
                <table class="pv-detail-table">
                  <tbody>
                    <tr><td class="pv-dt-label">Product Name</td><td class="pv-dt-val">{{ selectedProduct.name }}</td></tr>
                    <tr><td class="pv-dt-label">Brand</td><td class="pv-dt-val">{{ selectedProduct.brand || '—' }}</td></tr>
                    <tr><td class="pv-dt-label">Category</td><td class="pv-dt-val">{{ selectedProduct.category || '—' }}</td></tr>
                    <tr><td class="pv-dt-label">Subcategory</td><td class="pv-dt-val">{{ selectedProduct.subcategory || '—' }}</td></tr>
                    <tr><td class="pv-dt-label">SKU</td><td class="pv-dt-val"><code class="pv-code">{{ selectedProduct.sku || '—' }}</code></td></tr>
                    <tr><td class="pv-dt-label">Unit of Measure</td><td class="pv-dt-val">{{ selectedProduct.unit }}</td></tr>
                    <tr><td class="pv-dt-label">Status</td><td class="pv-dt-val"><span class="pv-badge" :class="statusClass(selectedProduct)">{{ statusLabel(selectedProduct) }}</span></td></tr>
                    <tr>
                      <td class="pv-dt-label">Description</td>
                      <td class="pv-dt-val">{{ selectedProduct.description || 'No description available.' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="pv-detail-right">

              <!-- Pricing Card -->
              <div class="pv-detail-card">
                <div class="pv-detail-card-title"><i class="bi bi-cash-stack"></i> Pricing</div>
                <table class="pv-detail-table">
                  <tbody>
                    <tr>
                      <td class="pv-dt-label">Purchase Price</td>
                      <td class="pv-dt-val pv-dt-price">PKR {{ formatNumber(selectedProduct.cost) }}</td>
                    </tr>
                    <tr>
                      <td class="pv-dt-label">Selling Price</td>
                      <td class="pv-dt-val pv-dt-price">PKR {{ formatNumber(selectedProduct.price) }}</td>
                    </tr>
                    <tr>
                      <td class="pv-dt-label">Profit Per Unit</td>
                      <td class="pv-dt-val pv-text-success pv-dt-price">
                        PKR {{ formatNumber(selectedProduct.price - selectedProduct.cost) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="pv-dt-label">Profit Margin</td>
                      <td class="pv-dt-val pv-text-success pv-dt-price">
                        {{ selectedProduct.price > 0 ? (((selectedProduct.price - selectedProduct.cost) / selectedProduct.price) * 100).toFixed(2) : '0.00' }}%
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Stock & Inventory Card -->
              <div class="pv-detail-card">
                <div class="pv-detail-card-title"><i class="bi bi-boxes"></i> Stock & Inventory</div>
                <table class="pv-detail-table">
                  <tbody>
                    <tr>
                      <td class="pv-dt-label">Current Stock</td>
                      <td class="pv-dt-val"><strong>{{ selectedProduct.stock }} {{ selectedProduct.unit }}</strong></td>
                    </tr>
                    <tr>
                      <td class="pv-dt-label">Minimum Stock</td>
                      <td class="pv-dt-val">{{ selectedProduct.minStock }} {{ selectedProduct.unit }}</td>
                    </tr>
                    <tr>
                      <td class="pv-dt-label">Maximum Stock</td>
                      <td class="pv-dt-val">{{ selectedProduct.maxStock || 100 }} {{ selectedProduct.unit }}</td>
                    </tr>
                    <tr>
                      <td class="pv-dt-label">Stock Condition</td>
                      <td class="pv-dt-val">
                        <strong :class="selectedProduct.stock <= selectedProduct.minStock ? 'pv-text-danger' : 'pv-text-success'">
                          {{ selectedProduct.stock <= selectedProduct.minStock ? '<i class="fa-solid fa-triangle-exclamation"></i> Low Stock' : '<i class="fa-solid fa-check"></i> Healthy' }}
                        </strong>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Stock bar -->
                <div class="pv-stock-bar-wrap">
                  <div class="pv-stock-bar-labels">
                    <span>0</span><span>Min: {{ selectedProduct.minStock }}</span><span>Max: {{ selectedProduct.maxStock || 100 }}</span>
                  </div>
                  <div class="pv-stock-bar-track">
                    <div class="pv-stock-bar-fill"
                      :class="selectedProduct.stock <= selectedProduct.minStock ? 'pv-bar-low' : 'pv-bar-ok'"
                      :style="{ width: Math.min(100, (selectedProduct.stock / (selectedProduct.maxStock || 100)) * 100) + '%' }">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Quick Actions -->
              <div class="pv-detail-card">
                <div class="pv-detail-card-title"><i class="bi bi-lightning"></i> Quick Actions</div>
                <div class="pv-quick-actions">
                  <button class="pv-action-btn" @click="showPage('edit', selectedProduct.id)" type="button">
                    <i class="bi bi-pencil-square"></i> Edit Product
                  </button>
                  <button class="pv-action-btn pv-action-btn-danger" @click="deleteProduct(selectedProduct.id)" type="button">
                    <i class="bi bi-trash"></i> Delete Product
                  </button>
                  <button class="pv-action-btn pv-action-btn-secondary" @click="showPage('products')" type="button">
                    <i class="bi bi-arrow-left"></i> Back to List
                  </button>
                </div>
              </div>

            </div>
          </div>
        </section>


        <!-- ====================================================
             EDIT PRODUCT PAGE
        ===================================================== -->
        <section v-if="currentPage === 'edit'" class="pv-section">
          <div class="pv-page-header">
            <div>
              <h1 class="pv-page-title">Edit Product</h1>
              <p class="pv-page-subtitle">Update product information, pricing, and stock settings.</p>
            </div>
            <div class="pv-header-actions">
              <button class="pv-btn-outline" v-if="!isModal" @click="showPage('details', selectedProductId)" type="button">Cancel</button>
              <button v-if="selectedProduct" class="pv-btn-primary" @click="saveEdit()" type="button">
                <i class="bi bi-check2 me-1"></i>Update Product
              </button>
            </div>
          </div>

          <!-- No product selected yet -->
          <div v-if="!selectedProduct" class="pv-empty-state pv-card" style="margin-top:0;">
            <div class="pv-empty-icon"><i class="bi bi-pencil-square"></i></div>
            <h3>No Product Selected</h3>
            <p>Please select a product from the Products list first, then click Edit to modify it.</p>
            <button class="pv-btn-primary" @click="showPage('products')" type="button">
              <i class="bi bi-arrow-left me-1"></i>Go to Products
            </button>
          </div>

          <div v-if="selectedProduct" class="pv-edit-layout">
            <!-- LEFT: MAIN FORM -->
            <div class="pv-edit-main">

              <!-- BASIC INFORMATION -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-info-circle"></i> Basic Information</div>
                <div class="row g-4">
                  <div class="col-12">
                    <label class="pv-label">Product Name *</label>
                    <input class="pv-input" v-model="editForm.name" placeholder="e.g. Coca Cola 1.5L">
                  </div>
                  
                    <div class="col-md-6">
                      <label class="pv-label">Category</label>
                      <select class="pv-input" v-model="editForm.category" @change="onCategoryChangeEdit">
                          <option value="">Select Category</option>
                          <option v-for="cat in store.categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">Sub-Category</label>
                      <select class="pv-input" v-model="editForm.subcategory">
                          <option value="">Select Sub-Category</option>
                          <option v-for="sub in availableSubcategoriesForEdit" :key="sub.id" :value="sub.name">{{ sub.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">Brand Name</label>
                      <input type="text" class="pv-input" v-model="editForm.brand" list="editBrandsListOptions" placeholder="Select or type brand">
                      <datalist id="editBrandsListOptions">
                        <option v-for="b in store.brands" :key="b.id" :value="b.name"></option>
                      </datalist>
                    </div>
                    <div class="col-md-6">
                      <label class="pv-label">SKU</label>
                      <input class="pv-input" v-model="editForm.sku" placeholder="e.g. BEV-001">
                    </div>
                  <div class="col-12">
                    <label class="pv-label">Description (Optional)</label>
                    <textarea class="pv-input" v-model="editForm.description" rows="3"></textarea>
                  </div>
                </div>
              </div>

              <!-- PRICING -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-cash-stack"></i> Pricing</div>
                <div class="row g-4">
                  <div class="col-md-6">
                    <label class="pv-label">Purchase Price (PKR) *</label>
                    <div class="pv-input-group">
                      <span class="pv-input-prefix">Rs</span>
                      <input type="number" class="pv-input" v-model.number="editForm.cost" min="0" step="0.01">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="pv-label">Selling Price (PKR) *</label>
                    <div class="pv-input-group">
                      <span class="pv-input-prefix">Rs</span>
                      <input type="number" class="pv-input" v-model.number="editForm.price" min="0" step="0.01">
                    </div>
                  </div>
                </div>
                <div class="pv-price-result">
                  <div class="pv-price-item">
                    <div class="pv-price-label">Profit Per Unit</div>
                    <div class="pv-price-value">Rs {{ editProfitPerUnit.toFixed(2) }}</div>
                  </div>
                  <div class="pv-price-item">
                    <div class="pv-price-label">Profit Margin</div>
                    <div class="pv-price-value pv-text-success">{{ editProfitMargin.toFixed(2) }}%</div>
                  </div>
                </div>
              </div>

              <!-- INVENTORY -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-boxes"></i> Inventory Settings</div>
                <div class="row g-4">
                  <div class="col-md-4">
                    <label class="pv-label">Unit of Measure *</label>
                    <select class="pv-input" v-model="editForm.unit">
                      <option>Piece (pc)</option>
                      <option>Box</option>
                      <option>Pack</option>
                      <option>Kg</option>
                      <option>Liter</option>
                      <option>Dozen</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="pv-label">Current Stock</label>
                    <input type="number" class="pv-input" v-model.number="editForm.stock" min="0">
                  </div>
                  <div class="col-md-4">
                    <label class="pv-label">Minimum Stock Level</label>
                    <input type="number" class="pv-input" v-model.number="editForm.minStock" min="0">
                  </div>
                  <div class="col-md-4">
                    <label class="pv-label">Maximum Stock Level</label>
                    <input type="number" class="pv-input" v-model.number="editForm.maxStock" min="0">
                  </div>
                  <div class="col-12 mt-3">
                    <label class="pv-label">Update Product Image (Optional)</label>
                    <label class="pv-image-upload" :style="editForm.imagePreview ? 'padding: 0; overflow: hidden; border-style: solid;' : ''">
                      <input type="file" accept="image/*" hidden @change="handleEditImageUpload">
                      <template v-if="!editForm.imagePreview">
                          <i class="bi bi-cloud-arrow-up"></i>
                          <strong>Click to upload new image</strong>
                          <small>JPG, PNG up to 2MB</small>
                      </template>
                      <img v-else :src="editForm.imagePreview" style="width: 100%; height: 100%; max-height: 140px; object-fit: contain; display: block;" alt="Preview">
                    </label>
                    <div v-if="editForm.imageName && !editForm.imagePreview" class="mt-2 text-success small">
                      <i class="bi bi-image me-1"></i>{{ editForm.imageName }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- ATTRIBUTES & VARIATIONS SECTION -->
              <div class="pv-form-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="pv-section-heading m-0"><i class="bi bi-tags"></i> Product Attributes & Variations</div>
                  <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="hasVariationsEdit" v-model="editForm.hasVariations" style="cursor: pointer; width: 40px; height: 20px;">
                    <label class="form-check-label small fw-bold text-dark" for="hasVariationsEdit" style="cursor: pointer;">Enable Variations</label>
                  </div>
                </div>

                <div v-if="editForm.hasVariations">
                  <p class="text-secondary small mb-3">
                    Add custom attributes (such as Size, Weight, Color, or Pack Type) and add multiple values to generate product variations.
                  </p>

                  <!-- PRESET ATTRIBUTE BUTTONS -->
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="small fw-semibold text-muted">Quick Presets:</span>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(editForm, 'Size')">+ Size</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(editForm, 'Weight')">+ Weight</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(editForm, 'Color')">+ Color</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addAttributePreset(editForm, 'Pack Type')">+ Pack Type</button>
                  </div>

                  <!-- ATTRIBUTES LIST -->
                  <div class="attributes-container mb-3">
                    <div v-for="(attr, aIndex) in editForm.attributes" :key="aIndex" class="attribute-item-card p-3 mb-3 bg-light rounded-3 border">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1 me-3">
                          <label class="pv-label m-0 fw-bold" style="white-space: nowrap;">Attribute Name:</label>
                          <input type="text" class="pv-input py-1 px-2" v-model="attr.name" placeholder="e.g. Size, Color, Pack Type" style="max-width: 220px;" @input="generateVariationsFromAttributes(editForm)" />
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeAttribute(editForm, aIndex)" title="Delete Attribute">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>

                      <!-- VALUES CHIPS & INPUT -->
                      <div class="mt-2">
                        <label class="pv-label mb-1 small text-muted">Values / Options:</label>
                        <div class="d-flex flex-wrap align-items-center gap-2 p-2 bg-white rounded border">
                          <span v-for="(val, vIndex) in attr.values" :key="vIndex" class="badge bg-primary text-white rounded-pill py-1.5 px-3 d-flex align-items-center gap-2 fs-7">
                            <span>{{ val }}</span>
                            <span role="button" class="fw-bold" style="cursor: pointer;" @click="removeAttributeValue(editForm, aIndex, vIndex)">×</span>
                          </span>
                          <div class="d-flex align-items-center gap-1 flex-grow-1" style="min-width: 180px;">
                            <input 
                              type="text" 
                              class="form-control form-control-sm border-0 shadow-none px-1" 
                              v-model="attr.newValueInput" 
                              placeholder="Type value & press Enter..." 
                              @keydown.enter.prevent="addAttributeValue(editForm, aIndex)" 
                            />
                            <button type="button" class="btn btn-sm btn-light border px-2 py-0.5 text-secondary" @click="addAttributeValue(editForm, aIndex)">
                              + Add
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2 rounded-3 border-dashed" @click="addNewAttribute(editForm)">
                      <i class="bi bi-plus-circle me-1"></i> Add Custom Attribute
                    </button>
                  </div>

                  <!-- GENERATED VARIATIONS TABLE -->
                  <div v-if="editForm.variations && editForm.variations.length > 0" class="variations-table-wrapper mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="fw-bold text-dark fs-6"><i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Generated Variations ({{ editForm.variations.length }})</div>
                      <button type="button" class="btn btn-sm btn-outline-primary" @click="generateVariationsFromAttributes(editForm)">
                        <i class="bi bi-arrow-repeat me-1"></i> Regenerate
                      </button>
                    </div>

                    <div class="table-responsive border rounded-3 bg-white">
                      <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light">
                          <tr>
                            <th class="ps-3">Variation</th>
                            <th>SKU</th>
                            <th style="width: 130px;">Price (PKR)</th>
                            <th style="width: 110px;">Stock</th>
                            <th class="text-center" style="width: 80px;">Active</th>
                            <th class="text-end pe-3" style="width: 60px;">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(v, vIdx) in editForm.variations" :key="vIdx">
                            <td class="ps-3 fw-semibold text-dark">{{ v.name }}</td>
                            <td>
                              <input type="text" class="form-control form-control-sm" v-model="v.sku" />
                            </td>
                            <td>
                              <input type="number" step="0.01" class="form-control form-control-sm" v-model.number="v.price" />
                            </td>
                            <td>
                              <input type="number" class="form-control form-control-sm" v-model.number="v.stock" />
                            </td>
                            <td class="text-center">
                              <input type="checkbox" class="form-check-input" v-model="v.active" />
                            </td>
                            <td class="text-end pe-3">
                              <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="editForm.variations.splice(vIdx, 1)">
                                <i class="bi bi-trash"></i>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- STATUS -->
              <div class="pv-form-card">
                <div class="pv-section-heading"><i class="bi bi-sliders"></i> Status</div>
                <div class="pv-switch-row">
                  <input class="pv-switch" type="checkbox" id="editStatus" v-model="editForm.active">
                  <div>
                    <label class="pv-switch-label" for="editStatus">Product Status</label>
                    <div class="pv-switch-desc">Active products appear in POS.</div>
                  </div>
                </div>
              </div>

              <div class="pv-form-footer">
                <button class="pv-btn-outline" v-if="!isModal" @click="showPage('details', selectedProductId)" type="button">Cancel</button>
                <button class="pv-btn-primary" @click="saveEdit()" type="button">
                  <i class="bi bi-check2 me-1"></i>Update Product
                </button>
              </div>
            </div>

            <!-- RIGHT: CURRENT VALUES SUMMARY -->
            <div class="pv-edit-sidebar" v-if="!isModal">
              <div class="pv-detail-card pv-edit-summary-card">
                <div class="pv-detail-card-title"><i class="bi bi-eye"></i> Current Values</div>
                <div class="pv-edit-summary-name">{{ selectedProduct.name }}</div>
                <div class="pv-edit-summary-sku">SKU: {{ selectedProduct.sku || '—' }}</div>
                <div class="pv-summary-divider"></div>
                <div class="pv-summary-row"><span>Original Cost</span><span>PKR {{ formatNumber(selectedProduct.cost) }}</span></div>
                <div class="pv-summary-row"><span>Original Price</span><span>PKR {{ formatNumber(selectedProduct.price) }}</span></div>
                <div class="pv-summary-row"><span>Current Stock</span><span>{{ selectedProduct.stock }} {{ selectedProduct.unit }}</span></div>
                <div class="pv-summary-row"><span>Category</span><span>{{ selectedProduct.category || '—' }}</span></div>
                <div class="pv-summary-row">
                  <span>Status</span>
                  <span class="pv-badge" :class="statusClass(selectedProduct)">{{ statusLabel(selectedProduct) }}</span>
                </div>
              </div>

              <div class="pv-detail-card pv-edit-preview-card">
                <div class="pv-detail-card-title"><i class="bi bi-calculator"></i> New Values Preview</div>
                <div class="pv-summary-row"><span>New Cost</span><span>PKR {{ formatNumber(editForm.cost) }}</span></div>
                <div class="pv-summary-row"><span>New Price</span><span>PKR {{ formatNumber(editForm.price) }}</span></div>
                <div class="pv-summary-row pv-text-success"><span>Profit/Unit</span><span>PKR {{ editProfitPerUnit.toFixed(2) }}</span></div>
                <div class="pv-summary-row pv-text-success"><span>Margin</span><span>{{ editProfitMargin.toFixed(2) }}%</span></div>
                <div class="pv-summary-row"><span>New Stock</span><span>{{ editForm.stock }} {{ editForm.unit }}</span></div>
              </div>
            </div>
          </div>
        
        </section>

        <!-- ====================================================
             CATEGORIES DIRECTORY PAGE
        ===================================================== -->
        <section v-if="currentPage === 'categories'" class="pv-section">
          <div class="pv-page-header">
            <div>
              <h1 class="pv-page-title">Categories</h1>
              <p class="pv-page-subtitle">Organize and manage your product categories and sub-categories.</p>
            </div>
            <div class="pv-header-actions">
              <button class="pv-btn-primary" @click="openCategoryModal" type="button">
                <i class="bi bi-plus-lg me-1"></i>Add New Category
              </button>
            </div>
          </div>

          <!-- STATS -->
          <div class="pv-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="pv-stat-card">
              <div class="pv-stat-label">TOTAL CATEGORIES</div>
              <div class="pv-stat-value">{{ hierarchicalCategoryList.length }}</div>
            </div>
            <div class="pv-stat-card">
              <div class="pv-stat-label">ACTIVE CATEGORIES</div>
              <div class="pv-stat-value pv-text-success">{{ activeCategoryCount }}</div>
            </div>
            <div class="pv-stat-card">
              <div class="pv-stat-label">INACTIVE CATEGORIES</div>
              <div class="pv-stat-value pv-text-muted">{{ inactiveCategoryCount }}</div>
            </div>
          </div>

          <!-- TABLE CARD -->
          <div class="pv-card">
            <!-- POLISHED CATEGORIES FILTER TOOLBAR -->
            <div class="cat-filter-bar">
              <!-- Search Category Input -->
              <div class="cat-search-box">
                <i class="bi bi-search"></i>
                <input 
                  v-model="categorySearch" 
                  type="text" 
                  placeholder="Search Category..." 
                />
                <button v-if="categorySearch" @click="categorySearch = ''" class="clear-btn" type="button">
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
                <option v-for="c in store.categories" :key="c.id" :value="c.name">{{ c.name }}</option>
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
              <button v-if="categorySearch || categoryTypeFilter || categoryParentFilter || categorySubFilter || categoryStatusFilter" @click="resetCategoryFilters" class="cat-btn-reset" type="button">
                <i class="bi bi-x-lg"></i> Reset
              </button>

              <!-- Add Category Button (Right Aligned) -->
              <button class="cat-btn-add ms-auto" @click="openCategoryModal" type="button">
                <i class="bi bi-plus-lg"></i> Add Category
              </button>
            </div>

            <div class="pv-table-wrapper">
              <table class="pv-table">
                <thead>
                  <tr>
                    <th>Category Name</th>
                    <th>Type</th>
                    <th>Associated Products</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in hierarchicalCategoryList" :key="item.uniqueKey">
                    <td>
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: #eef2ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                          <i :class="item.rawType === 'parent' ? 'bi bi-folder2-open' : 'bi bi-tags'"></i>
                        </div>
                        <div>
                          <div class="pv-product-name">{{ item.displayName }}</div>
                          <div class="pv-product-sku text-muted small" v-if="item.description">{{ item.description }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge rounded-pill px-2.5 py-1" :style="item.rawType === 'parent' ? 'background: #e0e7ff; color: #3730a3;' : 'background: #f1f5f9; color: #475569;'">
                        {{ item.type }}
                      </span>
                    </td>
                    <td>
                      <span class="pv-stock-val">{{ item.productCount }}</span>
                      <span class="pv-stock-unit"> products</span>
                    </td>
                    <td>
                      <span class="pv-badge" :class="item.status ? 'pv-badge-active' : 'pv-badge-inactive'">
                        {{ item.status ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <div class="pv-row-actions">
                        <button class="pv-icon-btn" title="View Category Details" @click="viewCategoryItem(item)" type="button">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="pv-icon-btn" title="Edit Category" @click="editCategoryItem(item)" type="button">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="pv-icon-btn pv-icon-btn-danger" :title="'Delete ' + item.type" @click="deleteCategoryItem(item)" type="button">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="hierarchicalCategoryList.length === 0" class="pv-empty-state">
              <div class="pv-empty-icon"><i class="bi bi-folder-x"></i></div>
              <h3>No Categories Found</h3>
              <p>Start organizing your inventory by adding your first product category.</p>
              <button class="pv-btn-primary" @click="openCategoryModal" type="button">
                <i class="bi bi-plus-lg me-1"></i>Add New Category
              </button>
            </div>
          </div>
        </section>

        <!-- ====================================================
             BRANDS DIRECTORY PAGE
        ===================================================== -->
        <section v-if="currentPage === 'brands'" class="pv-section">
          <div class="pv-page-header">
            <div>
              <h1 class="pv-page-title">Brands</h1>
              <p class="pv-page-subtitle">Manage and track your product brands and manufacturers.</p>
            </div>
            <div class="pv-header-actions">
              <button class="pv-btn-primary" @click="openBrandModal" type="button">
                <i class="bi bi-plus-lg me-1"></i>Add New Brand
              </button>
            </div>
          </div>

          <!-- STATS -->
          <div class="pv-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="pv-stat-card">
              <div class="pv-stat-label">TOTAL BRANDS</div>
              <div class="pv-stat-value">{{ allBrandItems.length }}</div>
            </div>
            <div class="pv-stat-card">
              <div class="pv-stat-label">ASSOCIATED PRODUCTS</div>
              <div class="pv-stat-value pv-text-success">{{ totalBrandedProductsCount }}</div>
            </div>
          </div>

          <!-- TABLE CARD -->
          <div class="pv-card">
            <div class="pv-toolbar">
              <h2 class="pv-card-title">Brand Directory</h2>
              <div class="pv-search-box">
                <i class="bi bi-search"></i>
                <input type="text" v-model="brandSearch" placeholder="Search brands...">
              </div>
            </div>

            <div class="pv-table-wrapper">
              <table class="pv-table">
                <thead>
                  <tr>
                    <th>Brand Name</th>
                    <th>Active Categories</th>
                    <th>Associated Products</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="brand in filteredBrandList" :key="brand.id">
                    <td>
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <div v-if="brand.image" style="width: 38px; height: 38px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; background: #ffffff;">
                          <img :src="brand.image" :alt="brand.name" style="width: 100%; height: 100%; object-fit: contain;" />
                        </div>
                        <div v-else style="width: 38px; height: 38px; border-radius: 8px; background: #fdf2f8; color: #db2777; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                          <i class="bi bi-award"></i>
                        </div>
                        <div>
                          <div class="pv-product-name">{{ brand.name }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center flex-wrap gap-1" v-if="brand.categories && brand.categories.length > 0">
                        <span 
                          v-for="cat in brand.categories" 
                          :key="cat"
                          class="badge rounded-pill px-2.5 py-1 text-dark"
                          style="background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; font-size: 12px; font-weight: 500;"
                        >
                          {{ cat }}
                        </span>
                      </div>
                      <span v-else class="text-muted small">—</span>
                    </td>
                    <td>
                      <span class="pv-stock-val">{{ brand.productCount }}</span>
                      <span class="pv-stock-unit"> products</span>
                    </td>
                    <td>
                      <div class="pv-row-actions">
                        <button class="pv-icon-btn pv-icon-btn-danger" title="Delete Brand" @click="deleteBrandItem(brand)" type="button">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="filteredBrandList.length === 0" class="pv-empty-state">
              <div class="pv-empty-icon"><i class="bi bi-award"></i></div>
              <h3>No Brands Found</h3>
              <p>Start organizing your products by adding your first brand.</p>
              <button class="pv-btn-primary" @click="openBrandModal" type="button">
                <i class="bi bi-plus-lg me-1"></i>Add New Brand
              </button>
            </div>
          </div>
        </section>

        <!-- ====================================================
             ADD NEW BRAND POPUP / MODAL
        ===================================================== -->
        <div v-if="showBrandModal" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;">
          <div class="modal-dialog modal-dialog-centered" style="max-width: 480px; width: 92%; margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: #ffffff;">
              
              <!-- Modal Header -->
              <div class="modal-header bg-light border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark m-0" style="font-size: 1.15rem;">Add New Brand</h5>
                <button type="button" class="btn-close" @click="closeBrandModal" aria-label="Close"></button>
              </div>

              <!-- Modal Body -->
              <div class="modal-body px-4 py-3" style="background: #ffffff;">
                <div class="mb-3">
                  <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 0.9rem;">Brand Name <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control form-control-lg rounded-3 border-1" 
                    v-model="brandForm.name" 
                    placeholder="e.g. Nestlé, PepsiCo, Apple" 
                    @keyup.enter="saveBrandForm"
                    style="font-size: 0.95rem; padding: 10px 14px;"
                    autofocus
                  />
                </div>

                <div class="mb-2">
                  <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 0.9rem;">Brand Logo / Image</label>
                  <div 
                    class="pv-cat-modal-dropzone" 
                    :class="{ 'dragging': isBrandDragging }"
                    @dragover.prevent="isBrandDragging = true"
                    @dragleave.prevent="isBrandDragging = false"
                    @drop.prevent="onBrandDrop"
                    @click="triggerBrandFileInput"
                    style="padding: 16px 12px;"
                  >
                    <input 
                      type="file" 
                      ref="brandFileInputRef" 
                      @change="onBrandFileSelected" 
                      accept="image/png, image/jpeg, image/gif, image/webp, image/svg+xml" 
                      style="display: none;" 
                    />

                    <div v-if="!brandForm.imagePreview">
                      <div class="pv-cat-modal-drop-icon" style="font-size: 22px; margin-bottom: 2px;">
                        <i class="bi bi-cloud-arrow-up"></i>
                      </div>
                      <div class="pv-cat-modal-drop-text" style="font-size: 13px;">
                        <span class="pv-cat-modal-upload-link">Upload a logo</span> or drag and drop
                      </div>
                      <div class="pv-cat-modal-drop-subtext" style="font-size: 11px;">PNG, JPG, WebP, SVG up to 2MB</div>
                    </div>

                    <div v-else class="pv-cat-modal-preview-box" @click.stop>
                      <img :src="brandForm.imagePreview" alt="Brand Logo Preview" class="pv-cat-modal-preview-img" style="max-height: 70px; object-fit: contain;" />
                      <div class="pv-cat-modal-preview-info">
                        <span class="pv-cat-modal-preview-name">{{ brandForm.imageName }}</span>
                        <button type="button" class="pv-cat-modal-remove-btn" @click.stop="removeBrandImage">
                          <i class="bi bi-trash3 me-1"></i> Remove
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="modal-footer bg-light border-0 px-4 py-3 d-flex justify-content-end gap-2">
                <button type="button" class="pv-cat-modal-btn-cancel" @click="closeBrandModal">Cancel</button>
                <button type="button" class="pv-cat-modal-btn-save" @click="saveBrandForm" :disabled="isSavingBrand">
                  <i class="bi bi-save me-1" v-if="!isSavingBrand"></i>
                  <span v-if="isSavingBrand" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isSavingBrand ? 'Saving...' : 'Save Brand' }}
                </button>
              </div>

            </div>
          </div>
        </div>

        <!-- ====================================================
             ADD / EDIT CATEGORY POPUP / MODAL (COMPACT ADD-EXPENSE STYLE)
        ===================================================== -->
        <div v-if="showCategoryModal" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;">
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 680px; width: 95%; margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="max-height: 88vh; background: #ffffff;">
              
              <!-- Modal Header -->
              <div class="modal-header bg-light border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark m-0" style="font-size: 1.15rem;">{{ categoryForm.isEdit ? 'Edit Category' : 'Add New Category' }}</h5>
                <button type="button" class="btn-close" @click="closeCategoryModal" aria-label="Close"></button>
              </div>

              <!-- Modal Body with Scrollable Form -->
              <div class="modal-body px-4 py-3" style="background: #ffffff; overflow-y: auto;">
                
                <!-- 1. BASIC INFORMATION -->
                <div class="pv-cat-modal-section">
                  <h6 class="pv-cat-modal-title">Basic Information</h6>
                  <div class="pv-cat-modal-divider"></div>

                  <div class="pv-cat-modal-row">
                    <div class="pv-cat-modal-field">
                      <label class="pv-cat-modal-label">Category Name <span class="text-danger">*</span></label>
                      <input 
                        type="text" 
                        class="pv-cat-modal-input" 
                        v-model="categoryForm.name" 
                        placeholder="e.g. Beverages" 
                      />
                    </div>

                    <div class="pv-cat-modal-field">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="pv-cat-modal-label mb-0">Category Code</label>
                        <button type="button" @click="autoGenerateCode" class="pv-cat-modal-autogen">Auto-generate</button>
                      </div>
                      <input 
                        type="text" 
                        class="pv-cat-modal-input" 
                        v-model="categoryForm.code" 
                        placeholder="e.g. CAT-001" 
                      />
                    </div>
                  </div>
                </div>

                <!-- 2. HIERARCHY & DISPLAY -->
                <div class="pv-cat-modal-section">
                  <h6 class="pv-cat-modal-title">Hierarchy & Display</h6>
                  <div class="pv-cat-modal-divider"></div>

                  <div class="pv-cat-modal-field mb-2">
                    <label class="pv-cat-modal-label">Category Type <span class="text-danger">*</span></label>
                    <div class="pv-cat-modal-radio-group">
                      <label class="pv-cat-modal-radio-label">
                        <input 
                          type="radio" 
                          name="modalCatType" 
                          value="parent" 
                          v-model="categoryForm.categoryType" 
                          class="form-check-input mt-0" 
                        />
                        <span>Parent Category</span>
                      </label>

                      <label class="pv-cat-modal-radio-label">
                        <input 
                          type="radio" 
                          name="modalCatType" 
                          value="sub" 
                          v-model="categoryForm.categoryType" 
                          class="form-check-input mt-0" 
                        />
                        <span>Sub-Category</span>
                      </label>
                    </div>
                  </div>

                  <!-- Select Parent Category when Sub-Category is active -->
                  <div class="pv-cat-modal-field mb-2" v-if="categoryForm.categoryType === 'sub'">
                    <label class="pv-cat-modal-label">Parent Category <span class="text-danger">*</span></label>
                    <select class="pv-cat-modal-input" v-model="categoryForm.parentCategory">
                      <option value="">Select Parent Category</option>
                      <option v-for="c in store.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                  </div>

                  <div class="pv-cat-modal-field">
                    <label class="pv-cat-modal-label">Description</label>
                    <textarea 
                      class="pv-cat-modal-textarea" 
                      v-model="categoryForm.description" 
                      placeholder="Optional description for this category..."
                    ></textarea>
                  </div>
                </div>

                <!-- 3. MEDIA -->
                <div class="pv-cat-modal-section">
                  <h6 class="pv-cat-modal-title">Media</h6>
                  <div class="pv-cat-modal-divider"></div>

                  <div class="pv-cat-modal-field">
                    <label class="pv-cat-modal-label">Category Icon / Image</label>
                    
                    <div 
                      class="pv-cat-modal-dropzone" 
                      :class="{ 'dragging': isDragging }"
                      @dragover.prevent="isDragging = true"
                      @dragleave.prevent="isDragging = false"
                      @drop.prevent="onCategoryDrop"
                      @click="triggerFileInput"
                    >
                      <input 
                        type="file" 
                        ref="fileInputRef" 
                        @change="onCategoryFileSelected" 
                        accept="image/png, image/jpeg, image/gif" 
                        style="display: none;" 
                      />

                      <div v-if="!categoryForm.imagePreview">
                        <div class="pv-cat-modal-drop-icon">
                          <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <div class="pv-cat-modal-drop-text">
                          <span class="pv-cat-modal-upload-link">Upload a file</span> or drag and drop
                        </div>
                        <div class="pv-cat-modal-drop-subtext">PNG, JPG, GIF up to 2MB</div>
                      </div>

                      <div v-else class="pv-cat-modal-preview-box" @click.stop>
                        <img :src="categoryForm.imagePreview" alt="Category Preview" class="pv-cat-modal-preview-img" />
                        <div class="pv-cat-modal-preview-info">
                          <span class="pv-cat-modal-preview-name">{{ categoryForm.imageName }}</span>
                          <button type="button" class="pv-cat-modal-remove-btn" @click.stop="removeCategoryImage">
                            <i class="bi bi-trash3 me-1"></i> Remove
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 4. SETTINGS -->
                <div class="pv-cat-modal-section mb-1">
                  <h6 class="pv-cat-modal-title">Settings</h6>
                  <div class="pv-cat-modal-divider"></div>

                  <div class="pv-cat-modal-settings-box">
                    <div>
                      <div class="pv-cat-modal-settings-title">Category Status</div>
                      <div class="pv-cat-modal-settings-desc">Inactive categories will not appear in POS or Inventory searches.</div>
                    </div>
                    <div class="form-check form-switch fs-5 mb-0">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        role="switch" 
                        v-model="categoryForm.status" 
                        style="cursor: pointer;" 
                      />
                    </div>
                  </div>
                </div>

              </div>

              <!-- 5. FORM ACTIONS -->
              <div class="modal-footer bg-light border-0 px-4 py-2.5 d-flex justify-content-end gap-2">
                <button type="button" class="pv-cat-modal-btn-cancel" @click="closeCategoryModal">Cancel</button>
                <button type="button" class="pv-cat-modal-btn-save" @click="saveCategoryForm" :disabled="isSavingCategory">
                  <i class="bi bi-save me-1" v-if="!isSavingCategory"></i>
                  <span v-if="isSavingCategory" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isSavingCategory ? 'Saving...' : (categoryForm.isEdit ? 'Update Category' : 'Save Category') }}
                </button>
              </div>

            </div>
          </div>
        </div>

        <!-- ====================================================
             VIEW CATEGORY DETAILS MODAL
        ===================================================== -->
        <div v-if="showViewCategoryModal && selectedCategoryForView" class="modal fade show d-block" tabindex="-1" style="z-index: 1050; background: rgba(0,0,0,0.5); pointer-events: auto;">
          <div class="modal-dialog modal-dialog-centered" style="max-width: 520px; width: 95%; margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: #ffffff;">
              <div class="modal-header bg-light border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <div style="width: 34px; height: 34px; border-radius: 8px; background: #eef2ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i :class="selectedCategoryForView.rawType === 'parent' ? 'bi bi-folder2-open' : 'bi bi-tags'"></i>
                  </div>
                  <h5 class="modal-title fw-bold text-dark m-0" style="font-size: 1.1rem;">Category Details</h5>
                </div>
                <button type="button" class="btn-close" @click="closeViewCategoryModal" aria-label="Close"></button>
              </div>

              <div class="modal-body px-4 py-3">
                <!-- Category Image & Name Header -->
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3 bg-light border">
                  <div 
                    style="width: 64px; height: 64px; border-radius: 12px; overflow: hidden; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
                  >
                    <img 
                      v-if="selectedCategoryForView.image" 
                      :src="selectedCategoryForView.image" 
                      :alt="selectedCategoryForView.name" 
                      style="width: 100%; height: 100%; object-fit: cover;" 
                    />
                    <div 
                      v-else 
                      style="width: 100%; height: 100%; background: #eef2ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 24px;"
                    >
                      <i :class="selectedCategoryForView.rawType === 'parent' ? 'bi bi-folder2-open' : 'bi bi-tags'"></i>
                    </div>
                  </div>
                  <div>
                    <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Category Name</label>
                    <div class="fs-5 fw-bold text-dark mb-0">{{ selectedCategoryForView.name }}</div>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label class="text-muted small fw-semibold text-uppercase">Type</label>
                    <div>
                      <span class="badge rounded-pill px-2.5 py-1" :style="selectedCategoryForView.rawType === 'parent' ? 'background: #e0e7ff; color: #3730a3;' : 'background: #f1f5f9; color: #475569;'">
                        {{ selectedCategoryForView.type }}
                      </span>
                    </div>
                  </div>

                  <div class="col-6" v-if="selectedCategoryForView.rawType === 'sub'">
                    <label class="text-muted small fw-semibold text-uppercase">Parent Category</label>
                    <div class="fw-semibold text-dark">{{ selectedCategoryForView.parentCategoryName || '—' }}</div>
                  </div>

                  <div class="col-6">
                    <label class="text-muted small fw-semibold text-uppercase">Status</label>
                    <div>
                      <span class="pv-badge" :class="selectedCategoryForView.status ? 'pv-badge-active' : 'pv-badge-inactive'">
                        {{ selectedCategoryForView.status ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                  </div>

                  <div class="col-6">
                    <label class="text-muted small fw-semibold text-uppercase">Associated Products</label>
                    <div class="fw-bold text-primary">{{ selectedCategoryForView.productCount }} {{ selectedCategoryForView.productCount === 1 ? 'Product' : 'Products' }}</div>
                  </div>
                </div>

                <div class="mb-2" v-if="selectedCategoryForView.description">
                  <label class="text-muted small fw-semibold text-uppercase">Description</label>
                  <div class="p-2.5 rounded-3 bg-light text-secondary small">{{ selectedCategoryForView.description }}</div>
                </div>
              </div>

              <div class="modal-footer bg-light border-0 px-4 py-2.5 d-flex justify-content-end gap-2">
                <button type="button" class="pv-cat-modal-btn-cancel" @click="closeViewCategoryModal">Close</button>
                <button type="button" class="pv-cat-modal-btn-save" @click="handleEditFromViewModal">
                  <i class="bi bi-pencil me-1"></i> Edit Category
                </button>
              </div>
            </div>
          </div>
        </div>
        
      </div>

    <!-- ===== TOAST ===== -->
    <div class="pv-toast" :class="{ 'pv-toast-show': toastVisible }">
      <i class="bi bi-check-circle-fill text-success me-2"></i>
      {{ toastMessage }}
    </div>

  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
})
const emit = defineEmits(['success', 'close'])
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRetailStore } from '@/stores/retail.js'
import api from '@/api'

const router = useRouter()
const route = useRoute()
const store = useRetailStore()

const products = computed(() => store.products || [])
const categories = computed(() => store.categories || [])
const subcategories = computed(() => store.subcategories || [])
const brands = computed(() => store.brands || [])

onMounted(async () => {
  await store.fetchCategories()
    await store.fetchSubcategories()
    await store.fetchBrands()
  await store.fetchProducts()
})

// ======= STATE =======
const currentPage = ref(props.isModal && props.modalPage ? props.modalPage : (route.query.tab || 'products'))
const showCategoryModal = ref(false)
const showBrandModal = ref(false)

watch(() => route.query.tab, (newTab) => {
  if (props.isModal && props.modalPage) {
    currentPage.value = props.modalPage;
  } else if (newTab) {
    currentPage.value = newTab;
  } else if (!props.isModal) {
    currentPage.value = 'products';
  }
  showCategoryModal.value = false;
  showBrandModal.value = false;
}, { immediate: true })

const categorySearch = ref('')
const categoryTypeFilter = ref('') // '', 'parent', 'sub'
const categoryParentFilter = ref('') // '', or Parent Category name
const categorySubFilter = ref('') // '', or Sub Category name
const categoryStatusFilter = ref('') // '', 'active', 'inactive'

const categorySubcategoryOptions = computed(() => {
  if (!categoryParentFilter.value) {
    return store.subcategories || []
  }
  return (store.subcategories || []).filter(s => {
    if (s.category && s.category.name === categoryParentFilter.value) return true
    const parentCat = (store.categories || []).find(c => c.name === categoryParentFilter.value)
    if (parentCat && s.category_id && String(s.category_id) === String(parentCat.id)) return true
    return (store.products || []).some(p => p.subcategory === s.name && p.category === categoryParentFilter.value)
  })
})

const resetCategoryFilters = () => {
  categorySearch.value = ''
  categoryTypeFilter.value = ''
  categoryParentFilter.value = ''
  categorySubFilter.value = ''
  categoryStatusFilter.value = ''
}

const parentCategoryCount = computed(() => {
  return (store.categories || []).length
})

const subCategoryCount = computed(() => {
  return (store.subcategories || []).length
})

const hierarchicalCategoryList = computed(() => {
  const list = []
  const q = categorySearch.value.trim().toLowerCase()
  const typeF = categoryTypeFilter.value
  const parentF = categoryParentFilter.value
  const subF = categorySubFilter.value
  const statusF = categoryStatusFilter.value
  const processedSubIds = new Set()

  // 1. Loop through parent categories
  for (const cat of (store.categories || [])) {
    if (parentF && cat.name !== parentF) {
      continue
    }

    const catProducts = (store.products || []).filter(p => p.category === cat.name)
    const childSubs = (store.subcategories || []).filter(s => {
      if (s.category_id && String(s.category_id) === String(cat.id)) return true
      if (s.category && (String(s.category.id) === String(cat.id) || s.category.name === cat.name)) return true
      const inProd = (store.products || []).some(p => p.subcategory === s.name && p.category === cat.name)
      if (inProd) return true
      return false
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
      const subProducts = (store.products || []).filter(p => p.subcategory === sub.name)
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

  // 2. Any orphan subcategories (if any)
  if (!parentF && typeF !== 'parent') {
    const remainingSubs = (store.subcategories || []).filter(s => !processedSubIds.has(s.id))
    for (const sub of remainingSubs) {
      if (subF && sub.name !== subF) continue

      const subProducts = (store.products || []).filter(p => p.subcategory === sub.name)
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
          parentCategoryName: sub.category?.name || '',
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

const activeCategoryCount = computed(() => {
  return hierarchicalCategoryList.value.filter(c => c.status !== false).length
})

const inactiveCategoryCount = computed(() => {
  return hierarchicalCategoryList.value.filter(c => c.status === false).length
})

// ======= CATEGORY MANAGEMENT & MODALS =======
const isSavingCategory = ref(false)
const categoryForm = reactive({
  id: null,
  isEdit: false,
  name: '',
  code: '',
  categoryType: 'parent',
  parentCategory: '',
  description: '',
  imageFile: null,
  imageName: '',
  imagePreview: '',
  status: true
})
const fileInputRef = ref(null)
const isDragging = ref(false)

const selectedCategoryForView = ref(null)
const showViewCategoryModal = ref(false)

const openCategoryModal = (presetParentId = null) => {
  categoryForm.id = null
  categoryForm.isEdit = false
  categoryForm.name = ''
  categoryForm.code = ''
  categoryForm.description = ''
  categoryForm.imageFile = null
  categoryForm.imageName = ''
  categoryForm.imagePreview = ''
  categoryForm.status = true
  if (presetParentId) {
    categoryForm.categoryType = 'sub'
    categoryForm.parentCategory = presetParentId
  } else {
    categoryForm.categoryType = 'parent'
    categoryForm.parentCategory = ''
  }
  showCategoryModal.value = true
}

const closeCategoryModal = () => {
  showCategoryModal.value = false
  categoryForm.id = null
  categoryForm.isEdit = false
  categoryForm.name = ''
  categoryForm.code = ''
  categoryForm.description = ''
  categoryForm.imageFile = null
  categoryForm.imageName = ''
  categoryForm.imagePreview = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const viewCategoryItem = (item) => {
  if (!item) return
  const prods = (store.products || []).filter(p => 
    item.rawType === 'parent' ? p.category === item.name : p.subcategory === item.name
  )
  const resolvedImage = item.image || prods.find(p => p.image)?.image || ''
  selectedCategoryForView.value = {
    ...item,
    image: resolvedImage,
    products: prods
  }
  showViewCategoryModal.value = true
}

const closeViewCategoryModal = () => {
  showViewCategoryModal.value = false
  selectedCategoryForView.value = null
}

const handleEditFromViewModal = () => {
  const item = selectedCategoryForView.value
  showViewCategoryModal.value = false
  selectedCategoryForView.value = null
  if (item) {
    editCategoryItem(item)
  }
}

const editCategoryItem = (item) => {
  categoryForm.id = item.id
  categoryForm.isEdit = true
  categoryForm.name = item.name
  categoryForm.code = item.code || ''
  categoryForm.categoryType = item.rawType === 'sub' ? 'sub' : 'parent'
  
  if (item.rawType === 'sub') {
    if (item.parentId) {
      categoryForm.parentCategory = item.parentId
    } else if (item.parentCategoryName) {
      const match = (store.categories || []).find(c => c.name === item.parentCategoryName)
      categoryForm.parentCategory = match ? match.id : ''
    } else {
      categoryForm.parentCategory = ''
    }
  } else {
    categoryForm.parentCategory = ''
  }

  categoryForm.description = item.description || ''
  categoryForm.status = item.status !== false
  categoryForm.imageFile = null
  categoryForm.imageName = ''
  categoryForm.imagePreview = item.image || ''
  showCategoryModal.value = true
}

const autoGenerateCode = () => {
  const prefix = categoryForm.categoryType === 'sub' ? 'SUB' : 'CAT'
  const rand = Math.floor(1000 + Math.random() * 9000)
  categoryForm.code = `${prefix}-${rand}`
}

const triggerFileInput = () => {
  if (fileInputRef.value) fileInputRef.value.click()
}

const onCategoryFileSelected = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  categoryForm.imageFile = file
  categoryForm.imageName = file.name
  const reader = new FileReader()
  reader.onload = (ev) => {
    categoryForm.imagePreview = ev.target.result
  }
  reader.readAsDataURL(file)
}

const onCategoryDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (!file) return
  categoryForm.imageFile = file
  categoryForm.imageName = file.name
  const reader = new FileReader()
  reader.onload = (ev) => {
    categoryForm.imagePreview = ev.target.result
  }
  reader.readAsDataURL(file)
}

const removeCategoryImage = () => {
  categoryForm.imageFile = null
  categoryForm.imageName = ''
  categoryForm.imagePreview = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const saveCategoryForm = async () => {
  const name = (categoryForm.name || '').trim()
  if (!name) {
    showToast('Category Name is required.')
    return
  }

  if (categoryForm.categoryType === 'sub' && !categoryForm.parentCategory) {
    showToast('Please select a Parent Category for this Sub-Category.')
    return
  }

  isSavingCategory.value = true
  try {
    if (categoryForm.isEdit) {
      if (categoryForm.categoryType === 'parent') {
        const res = await store.updateCategory(categoryForm.id, {
          name,
          description: categoryForm.description
        })
        if (res && res.success !== false) {
          showToast('Category updated successfully!')
          closeCategoryModal()
          await store.fetchCategories()
        } else {
          showToast(res?.message || 'Failed to update category.')
        }
      } else {
        const res = await store.updateSubcategory(categoryForm.id, {
          name,
          category_id: categoryForm.parentCategory || null,
          description: categoryForm.description
        })
        if (res && res.success !== false) {
          showToast('Sub-Category updated successfully!')
          closeCategoryModal()
          await store.fetchSubcategories()
        } else {
          showToast(res?.message || 'Failed to update subcategory.')
        }
      }
    } else {
      if (categoryForm.categoryType === 'parent') {
        const res = await store.addCategory({
          name,
          description: categoryForm.description
        })
        if (res && res.success !== false) {
          showToast('Parent Category added successfully!')
          closeCategoryModal()
          await store.fetchCategories()
        } else {
          showToast(res?.message || 'Failed to add category.')
        }
      } else {
        const res = await store.addSubcategory({
          name,
          category_id: categoryForm.parentCategory,
          description: categoryForm.description
        })
        if (res && res.success !== false) {
          showToast('Sub-Category added successfully!')
          closeCategoryModal()
          await store.fetchSubcategories()
        } else {
          showToast(res?.message || 'Failed to add subcategory.')
        }
      }
    }
  } catch (err) {
    console.error(err)
    showToast('An error occurred while saving category.')
  } finally {
    isSavingCategory.value = false
  }
}

const deleteCategoryItem = async (item) => {
  if (confirm(`Are you sure you want to delete the ${item.type} "${item.name}"?`)) {
    if (item.rawType === 'parent') {
      const res = await store.removeCategory(item.id)
      if (res && res.success !== false) {
        showToast('Category deleted successfully.')
        await store.fetchCategories()
      } else {
        showToast('Failed to delete category.')
      }
    } else {
      const res = await store.removeSubcategory(item.id)
      if (res && res.success !== false) {
        showToast('Subcategory deleted successfully.')
        await store.fetchSubcategories()
      } else {
        showToast('Failed to delete subcategory.')
      }
    }
  }
}

// ======= BRANDS MANAGEMENT =======
const isSavingBrand = ref(false)
const brandSearch = ref('')
const brandForm = reactive({
  name: '',
  imageFile: null,
  imageName: '',
  imagePreview: ''
})
const brandFileInputRef = ref(null)
const isBrandDragging = ref(false)

const openBrandModal = () => {
  brandForm.name = ''
  brandForm.imageFile = null
  brandForm.imageName = ''
  brandForm.imagePreview = ''
  if (brandFileInputRef.value) brandFileInputRef.value.value = ''
  showBrandModal.value = true
}

const closeBrandModal = () => {
  showBrandModal.value = false
  brandForm.name = ''
  brandForm.imageFile = null
  brandForm.imageName = ''
  brandForm.imagePreview = ''
  if (brandFileInputRef.value) brandFileInputRef.value.value = ''
}

const triggerBrandFileInput = () => {
  if (brandFileInputRef.value) brandFileInputRef.value.click()
}

const handleBrandFile = (file) => {
  if (!file) return
  if (!file.type.match(/image\/(png|jpeg|jpg|gif|webp|svg)/i)) {
    showToast('Please upload a valid image (PNG, JPG, GIF, WebP, SVG).')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    showToast('Image size must be less than 2MB.')
    return
  }
  brandForm.imageFile = file
  brandForm.imageName = file.name
  const reader = new FileReader()
  reader.onload = (e) => {
    brandForm.imagePreview = e.target.result
  }
  reader.readAsDataURL(file)
}

const onBrandFileSelected = (e) => {
  const file = e.target.files?.[0]
  handleBrandFile(file)
}

const onBrandDrop = (e) => {
  isBrandDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  handleBrandFile(file)
}

const removeBrandImage = () => {
  brandForm.imageFile = null
  brandForm.imageName = ''
  brandForm.imagePreview = ''
  if (brandFileInputRef.value) brandFileInputRef.value.value = ''
}

const saveBrandForm = async () => {
  const name = (brandForm.name || '').trim()
  if (!name) {
    showToast('Brand Name is required.')
    return
  }

  isSavingBrand.value = true
  try {
    let payload
    if (brandForm.imageFile) {
      payload = new FormData()
      payload.append('name', name)
      payload.append('image', brandForm.imageFile)
    } else if (brandForm.imagePreview) {
      payload = { name, image: brandForm.imagePreview }
    } else {
      payload = { name }
    }

    let res = null
    if (typeof store.addBrand === 'function') {
      res = await store.addBrand(payload)
    } else {
      const response = await api.post('/brands', payload)
      if (response.data && store.brands) {
        store.brands.unshift(response.data)
      }
      res = { success: true }
    }

    if (res && res.success) {
      showToast('Brand added successfully!')
      closeBrandModal()
      if (store.fetchBrands) await store.fetchBrands()
    } else {
      showToast(res?.message || 'Failed to save brand.')
    }
  } catch (err) {
    console.error(err)
    const errorMsg = err.response?.data?.message || err.message || 'An error occurred while saving brand.'
    showToast(errorMsg)
  } finally {
    isSavingBrand.value = false
  }
}

const deleteBrandItem = async (brand) => {
  if (confirm(`Are you sure you want to delete the brand "${brand.name}"?`)) {
    try {
      if (typeof store.removeBrand === 'function') {
        const res = await store.removeBrand(brand.id)
        if (res && res.success !== false) {
          showToast('Brand deleted successfully.')
          if (store.fetchBrands) await store.fetchBrands()
        } else {
          showToast('Failed to delete brand.')
        }
      } else {
        await api.delete(`/brands/${brand.id}`)
        if (store.brands) store.brands = store.brands.filter(b => b.id !== brand.id)
        showToast('Brand deleted successfully.')
        if (store.fetchBrands) await store.fetchBrands()
      }
    } catch (err) {
      console.error(err)
      showToast('Failed to delete brand.')
    }
  }
}

const allBrandItems = computed(() => {
  return (store.brands || []).map(b => {
    const brandProducts = (store.products || []).filter(p => p.brand === b.name)
    const distinctCategories = [...new Set(brandProducts.map(p => p.category).filter(Boolean))]
    return {
      id: b.id,
      name: b.name,
      image: b.image || '',
      productCount: brandProducts.length,
      categories: distinctCategories
    }
  })
})

const totalBrandedProductsCount = computed(() => {
  return (store.products || []).filter(p => Boolean(p.brand)).length
})

const filteredBrandList = computed(() => {
  const q = brandSearch.value.trim().toLowerCase()
  if (!q) return allBrandItems.value
  return allBrandItems.value.filter(b => b.name.toLowerCase().includes(q))
})

// Cascading Subcategories Scoped to Selected Category
const availableSubcategoriesForAdd = computed(() => {
  if (!form.category) return store.subcategories || []
  return store.getSubcategoriesByCategoryId(form.category)
})

const availableSubcategoriesForEdit = computed(() => {
  if (!editForm.category) return store.subcategories || []
  return store.getSubcategoriesByCategoryId(editForm.category)
})

const onCategoryChangeAdd = () => {
  if (form.subcategory) {
    const valid = availableSubcategoriesForAdd.value.some(s => s.name === form.subcategory)
    if (!valid) form.subcategory = ''
  }
}

const onCategoryChangeEdit = () => {
  if (editForm.subcategory) {
    const valid = availableSubcategoriesForEdit.value.some(s => s.name === editForm.subcategory)
    if (!valid) editForm.subcategory = ''
  }
}

const selectedProductId = ref(null)
const search = ref('')
const sidebarOpen = ref(false)
const toastVisible = ref(false)
const toastMessage = ref('')

// Add Product Form
const defaultForm = () => ({
  name: '',
  category: '',
  subcategory: '',
  brand: '',
  sku: '',
  description: '',
  cost: 0,
  price: 0,
  unit: 'Piece (pc)',
  stock: 0,
  minStock: 10,
  maxStock: 100,
  active: true,
  batchTracking: false,
  imageName: '',
  imageFile: null,
  hasVariations: false,
  attributes: [],
  variations: []
})
const form = reactive(defaultForm())

// Edit Product Form
const editForm = reactive({
  name: '', category: '', subcategory: '', brand: '', sku: '', description: '',
  cost: 0, price: 0, unit: 'Piece (pc)',
  stock: 0, minStock: 10, maxStock: 100, active: true,
  imageName: '', imageFile: null, imagePreview: '',
  hasVariations: false, attributes: [], variations: []
})

// ======= COMPUTED =======
const breadcrumb = computed(() => {
  const map = { products: 'Products', add: 'Add New Product', details: 'Product Details', edit: 'Edit Product' }
  return map[currentPage.value] || 'Products'
})

const filteredProducts = computed(() => {
  if (!search.value.trim()) return store.products
  const q = search.value.toLowerCase()
  return store.products.filter(p =>
    p.name?.toLowerCase().includes(q) ||
    p.sku?.toLowerCase().includes(q) ||
    p.category?.toLowerCase().includes(q)
  )
})

const activeCount = computed(() => store.products.filter(p => p.active !== false).length)
const lowStockCount = computed(() => store.products.filter(p => p.stock <= (p.minStock ?? p.min ?? 0)).length)
const totalStock = computed(() => store.products.reduce((sum, p) => sum + Number(p.stock || 0), 0))

const groupedProducts = computed(() => {
  const groups = {};
  store.products.forEach(p => {
    if (!p.category) return;
    if (!groups[p.category]) groups[p.category] = [];
    groups[p.category].push(p);
  });
  return groups;
})

const selectedProduct = computed(() =>
  store.products.find(p => p.id === selectedProductId.value) || null
)

const profitPerUnit = computed(() => (form.price || 0) - (form.cost || 0))
const profitMargin = computed(() =>
  form.price > 0 ? (profitPerUnit.value / form.price) * 100 : 0
)

const editProfitPerUnit = computed(() => (editForm.price || 0) - (editForm.cost || 0))
const editProfitMargin = computed(() =>
  editForm.price > 0 ? (editProfitPerUnit.value / editForm.price) * 100 : 0
)

// ======= METHODS =======
function showPage(page, productId = null) {
  if (productId !== null) selectedProductId.value = productId

  if (page === 'edit' && selectedProductId.value) {
    const p = store.products.find(x => x.id === selectedProductId.value)
    if (p) {
      editForm.name = p.name
      editForm.category = p.category || ''
      editForm.subcategory = p.subcategory || ''
      editForm.brand = p.brand || ''
      editForm.sku = p.sku || ''
      editForm.description = p.description || ''
      editForm.cost = p.cost || p.cost || 0
      editForm.price = p.price || p.price || 0
      editForm.unit = p.unit || 'Piece (pc)'
      editForm.stock = p.stock || 0
      editForm.minStock = p.minStock ?? p.min ?? 10
      editForm.maxStock = p.maxStock ?? p.max ?? 100
      editForm.active = p.active !== false
      editForm.imageName = ''
      editForm.imageFile = null
      editForm.imagePreview = p.image || ''
      editForm.hasVariations = Boolean(p.hasVariations || (p.attributes && p.attributes.length > 0))
      editForm.attributes = p.attributes ? JSON.parse(JSON.stringify(p.attributes)) : []
      editForm.variations = p.variations ? JSON.parse(JSON.stringify(p.variations)) : []
    }
  }

  currentPage.value = page
  if (!props.isModal && route.path === '/products') {
    router.push({ path: '/products', query: { tab: page } })
  }
  sidebarOpen.value = false
  window.scrollTo(0, 0)
}






const getParentCategory = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName)
    return cat ? cat.name : ''
}
const getSubcategory = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName)
    if (!cat) return ''
    const sub = subcategories.value.find(s => s.category_id === cat.id)
    return sub ? sub.name : ''
}

const filteredSubcategories = (categoryName) => {
    const cat = categories.value.find(c => c.name === categoryName);
    if (!cat) return [];
    return subcategories.value.filter(s => s.category_id === cat.id);
}


const addAttributePreset = (targetForm, type) => {
  if (!targetForm.hasVariations) targetForm.hasVariations = true;
  if (!targetForm.attributes) targetForm.attributes = [];
  
  const existing = targetForm.attributes.find(a => a.name.toLowerCase() === type.toLowerCase());
  if (existing) return;

  let defaultVals = [];
  if (type === 'Size') defaultVals = ['250ml', '500ml', '1.5L', '2.25L'];
  else if (type === 'Weight') defaultVals = ['250g', '500g', '1kg', '5kg'];
  else if (type === 'Color') defaultVals = ['Red', 'Blue', 'Black', 'White'];
  else if (type === 'Pack Type') defaultVals = ['Single Pack', 'Box', 'Carton'];

  targetForm.attributes.push({
    name: type,
    values: defaultVals,
    newValueInput: ''
  });

  generateVariationsFromAttributes(targetForm);
};

const addNewAttribute = (targetForm) => {
  if (!targetForm.attributes) targetForm.attributes = [];
  targetForm.attributes.push({
    name: '',
    values: [],
    newValueInput: ''
  });
};

const removeAttribute = (targetForm, index) => {
  targetForm.attributes.splice(index, 1);
  generateVariationsFromAttributes(targetForm);
};

const addAttributeValue = (targetForm, attrIndex) => {
  const attr = targetForm.attributes[attrIndex];
  if (!attr) return;
  const val = (attr.newValueInput || '').trim();
  if (val && !attr.values.includes(val)) {
    attr.values.push(val);
    attr.newValueInput = '';
    generateVariationsFromAttributes(targetForm);
  }
};

const removeAttributeValue = (targetForm, attrIndex, valIndex) => {
  const attr = targetForm.attributes[attrIndex];
  if (!attr) return;
  attr.values.splice(valIndex, 1);
  generateVariationsFromAttributes(targetForm);
};

const generateVariationsFromAttributes = (targetForm) => {
  if (!targetForm.attributes || targetForm.attributes.length === 0) {
    targetForm.variations = [];
    return;
  }

  const validAttrs = targetForm.attributes.filter(a => a.name && a.name.trim() && a.values && a.values.length > 0);
  if (validAttrs.length === 0) {
    targetForm.variations = [];
    return;
  }

  const combinations = validAttrs.reduce((acc, attr) => {
    const res = [];
    acc.forEach(prev => {
      attr.values.forEach(val => {
        res.push({
          ...prev,
          [attr.name]: val,
          labelParts: [...(prev.labelParts || []), String(val)]
        });
      });
    });
    return res;
  }, [{ labelParts: [] }]);

  const basePrice = Number(targetForm.price) || 0;
  const baseStock = Number(targetForm.stock) || 0;
  const baseSku = targetForm.sku || 'SKU';

  targetForm.variations = combinations.map(c => {
    const varName = c.labelParts.join(' / ');
    const existingVar = (targetForm.variations || []).find(v => v.name === varName);

    let priceMult = 1.0;
    let stockMult = 1.0;
    const lower = varName.toLowerCase();
    if (lower.includes('box') || lower.includes('dozen')) {
      priceMult = 12.0;
      stockMult = 0.1;
    } else if (lower.includes('carton')) {
      priceMult = 24.0;
      stockMult = 0.05;
    } else if (lower.includes('pack') && !lower.includes('single')) {
      priceMult = 6.0;
      stockMult = 0.2;
    } else if (lower.includes('1.5l') || lower.includes('1kg')) {
      priceMult = 1.8;
    } else if (lower.includes('2.25l') || lower.includes('5kg')) {
      priceMult = 2.4;
    }

    const skuSuffix = c.labelParts.map(p => String(p).replace(/[^a-zA-Z0-9]/g, '').toUpperCase()).join('-');

    return {
      name: varName,
      sku: existingVar?.sku || `${baseSku}-${skuSuffix}`,
      price: existingVar?.price !== undefined ? existingVar.price : Math.round(basePrice * priceMult),
      stock: existingVar?.stock !== undefined ? existingVar.stock : Math.max(1, Math.round(baseStock * stockMult)),
      active: existingVar?.active !== undefined ? existingVar.active : true,
      attributesMap: { ...c }
    };
  });
};

async function saveProduct() {
  if (!form.name.trim()) {
    showToast('Please enter a product name.')
    return
  }

  const sku = form.sku.trim() || 'PRD-' + String(Date.now()).slice(-6)

  const res = await store.addProduct({
    name: form.name.trim(),
    category: form.category,
    subcategory: form.subcategory,
    brand: form.brand,
    sku,
    description: form.description,
    cost: form.cost,
    price: form.price,
    stock: form.stock,
    minStock: form.minStock,
    maxStock: form.maxStock,
    unit: form.unit,
    active: form.active,
    imageFile: form.imageFile,
    hasVariations: form.hasVariations,
    attributes: form.attributes,
    variations: form.variations
  })

  if (res.success) {
    const newProduct = store.products[store.products.length - 1]
    // selectedProductId.value = newProduct.id

    showToast('Product added successfully.')
    Object.assign(form, defaultForm())
    // showPage('details', newProduct.id)
  } else {
    showToast(res.message || 'Failed to add product.')
  }
}

async function saveEdit() {
  if (!editForm.name.trim()) {
    showToast('Please enter a product name.')
    return
  }

  const res = await store.updateProduct(selectedProductId.value, {
    name: editForm.name.trim(),
    category: editForm.category,
    subcategory: editForm.subcategory,
    brand: editForm.brand,
    sku: editForm.sku,
    description: editForm.description,
    cost: editForm.cost,
    price: editForm.price,
    stock: editForm.stock,
    minStock: editForm.minStock,
    maxStock: editForm.maxStock,
    unit: editForm.unit,
    active: editForm.active,
    imageFile: editForm.imageFile,
    hasVariations: editForm.hasVariations,
    attributes: editForm.attributes,
    variations: editForm.variations
  })

  if (res.success) {
    showToast('Product updated successfully.')
    showPage('details', selectedProductId.value)
  } else {
    showToast(res.message || 'Failed to update product.')
  }
}

async function deleteProduct(id) {
  const product = store.products.find(p => p.id === id)
  if (!product) return
  if (!confirm(`Delete "${product.name}"?`)) return
  
  const res = await store.deleteProduct(id)
  if (res.success) {
    showToast('Product deleted successfully.')
    selectedProductId.value = null
    showPage('products')
  } else {
    showToast('Failed to delete product.')
  }
}

function generateSKU() {
  const prefixMap = {
    'Beverages': 'BEV', 'Grocery': 'GRC', 'Snacks': 'SNK',
    'Dairy': 'DRY', 'Household': 'HOU', 'Personal Care': 'PER'
  }
  const prefix = prefixMap[form.category] || 'PRD'
  form.sku = prefix + '-' + Math.floor(100 + Math.random() * 900)
}

function handleImageUpload(event) {
    const file = event.target.files[0]
    if (file) {
      form.imageName = file.name
      form.imageFile = file
      if (form.imagePreview) URL.revokeObjectURL(form.imagePreview)
      form.imagePreview = URL.createObjectURL(file)
    }
  }

function handleEditImageUpload(event) {
    const file = event.target.files[0]
    if (file) {
      editForm.imageName = file.name
      editForm.imageFile = file
      // Do not revoke object URL if it was the original server URL
      if (editForm.imagePreview && editForm.imagePreview.startsWith('blob:')) {
         URL.revokeObjectURL(editForm.imagePreview)
      }
      editForm.imagePreview = URL.createObjectURL(file)
    }
}

function statusLabel(product) {
  if (product.active === false) return 'Inactive'
  if (product.stock <= (product.minStock ?? product.min ?? 0)) return 'Low Stock'
  return 'Active'
}

function statusClass(product) {
  if (product.active === false) return 'pv-badge-out'
  if (product.stock <= (product.minStock ?? product.min ?? 0)) return 'pv-badge-low'
  return 'pv-badge-active'
}

function formatNumber(n) {
  return Number(n || 0).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function showToast(message) {
  toastMessage.value = message
  toastVisible.value = true
  setTimeout(() => { toastVisible.value = false }, 2500)
}
</script>

<style scoped>
/* ===== ROOT ===== */
.pv-app {
  display: block;
  width: 100%;
  background: transparent;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  color: #171a24;
}

/* ===== PAGE ===== */
.pv-page { flex: 1; padding: 10px 0 30px 0; }
.pv-section { width: 100%; }

.pv-page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-top: 24px;
  flex-wrap: wrap;
  gap: 12px;
}

.pv-page-title { font-size: 1.6rem; font-weight: 700; color: #171a24; margin: 0 0 4px; }
.pv-page-subtitle { color: #697386; font-size: 0.95rem; margin: 0; font-weight: 400; }

.pv-header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* ===== BUTTONS ===== */
.pv-btn-primary {
  background: #2447c6;
  color: #fff;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s;
  display: inline-flex;
  align-items: center;
}
.pv-btn-primary:hover { background: #1939a8; }
.pv-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.pv-btn-outline {
  background: #fff;
  color: #171a24;
  border: 1px solid #d8deea;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s;
  display: inline-flex;
  align-items: center;
}
.pv-btn-outline:hover { background: #f0f4ff; border-color: #2447c6; color: #2447c6; }

/* ===== STATS ===== */
.pv-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-top: 24px;
}

.pv-stat-card {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 10px;
  padding: 20px 22px;
}

.pv-stat-label {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 1px;
  color: #697386;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.pv-stat-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: #171a24;
}

/* ===== CARD ===== */
.pv-card {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 12px;
  overflow: hidden;
}

.pv-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid #edf2f7;
}

.pv-card-title { font-size: 1.05rem; font-weight: 700; color: #171a24; margin: 0; }

.pv-search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.pv-search-box i {
  position: absolute;
  left: 12px;
  color: #697386;
  font-size: 0.9rem;
}

.pv-search-box input {
  padding: 8px 14px 8px 36px;
  border: 1px solid #d8deea;
  border-radius: 8px;
  font-size: 0.9rem;
  width: 280px;
  outline: none;
  transition: border-color 0.18s;
  font-family: inherit;
}

.pv-search-box input:focus { border-color: #2447c6; }

/* ===== TABLE ===== */
.pv-table-wrapper { overflow-x: auto; }

.pv-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.pv-table th {
  padding: 14px 24px;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #697386;
  border-bottom: 2px solid #f1f5f9;
  background: #fafbfd;
}

.pv-table td {
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.9rem;
  color: #171a24;
}

.pv-table tbody tr:last-child td { border-bottom: none; }
.pv-table tbody tr:hover { background: #fafbfd; }

.pv-product-name { font-weight: 600; color: #171a24; }
.pv-product-sku { font-size: 0.8rem; color: #697386; margin-top: 2px; }
.pv-stock-unit { color: #697386; font-size: 0.85rem; margin-left: 2px; }

/* ===== BADGES ===== */
.pv-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.2px;
}

.pv-badge-active { background: #d1fae5; color: #065f46; }
.pv-badge-low { background: #fef3c7; color: #92400e; }
.pv-badge-out { background: #fee2e2; color: #991b1b; }

/* ===== ROW ACTIONS ===== */
.pv-row-actions { display: flex; gap: 6px; justify-content: flex-end; }

.pv-icon-btn {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 7px;
  padding: 6px 9px;
  cursor: pointer;
  color: #697386;
  transition: all 0.18s;
  font-size: 0.9rem;
}
.pv-icon-btn:hover { background: #f0f4ff; color: #2447c6; border-color: #2447c6; }
.pv-icon-btn-danger:hover { background: #fee2e2; color: #d52d2d; border-color: #d52d2d; }

/* ===== EMPTY STATE ===== */
.pv-empty-state {
  text-align: center;
  padding: 60px 24px;
  color: #697386;
}
.pv-empty-icon { font-size: 3rem; margin-bottom: 16px; color: #c1c8d4; }
.pv-empty-state h3 { font-size: 1.1rem; font-weight: 700; color: #171a24; margin-bottom: 8px; }

/* ===== FORMS ===== */
.pv-form-layout { display: flex; flex-direction: column; gap: 20px; width: 100%; }

.pv-form-card {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 12px;
  padding: 24px;
}

.pv-section-heading {
  font-size: 0.9rem;
  font-weight: 700;
  color: #171a24;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.pv-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #4b5563;
  margin-bottom: 6px;
}

.pv-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d8deea;
  border-radius: 8px;
  font-size: 0.9rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.18s;
  background: #fff;
  color: #171a24;
}
.pv-input:focus { border-color: #2447c6; box-shadow: 0 0 0 3px rgba(36, 71, 198, 0.1); }

.pv-input-group { display: flex; }
.pv-input-group .pv-input { border-radius: 0 8px 8px 0; }
.pv-input-prefix {
  display: flex;
  align-items: center;
  padding: 0 12px;
  background: #f6f8fc;
  border: 1px solid #d8deea;
  border-right: none;
  border-radius: 8px 0 0 8px;
  font-size: 0.875rem;
  color: #697386;
}
.pv-input-addon {
  padding: 0 12px;
  background: #f6f8fc;
  border: 1px solid #d8deea;
  border-left: none;
  border-radius: 0 8px 8px 0;
  cursor: pointer;
  color: #697386;
  transition: all 0.18s;
}
.pv-input-addon:hover { background: #e9efff; color: #2447c6; }

.pv-price-result {
  display: flex;
  gap: 20px;
  margin-top: 16px;
  padding: 14px 18px;
  background: #f6f8fc;
  border-radius: 8px;
}
.pv-price-item {}
.pv-price-label { font-size: 0.8rem; color: #697386; margin-bottom: 4px; }
.pv-price-value { font-size: 1.1rem; font-weight: 700; color: #171a24; }

.pv-switch-row { display: flex; align-items: flex-start; gap: 12px; }
.pv-switch { margin-top: 3px; cursor: pointer; }
.pv-switch-label { font-size: 0.9rem; font-weight: 600; color: #171a24; cursor: pointer; }
.pv-switch-desc { font-size: 0.8rem; color: #697386; margin-top: 2px; }

.pv-image-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 2px dashed #d8deea;
  border-radius: 10px;
  padding: 30px 20px;
  cursor: pointer;
  text-align: center;
  color: #697386;
  font-size: 0.9rem;
  transition: border-color 0.18s;
}
.pv-image-upload:hover { border-color: #2447c6; background: #f0f4ff; }
.pv-image-upload i { font-size: 1.8rem; color: #697386; }

.pv-form-footer {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding-top: 4px;
}

/* ===== DETAIL PAGES & FORMS DESKTOP ===== */
.pv-edit-layout {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  width: 100%;
}
.pv-edit-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.pv-edit-sidebar {
  width: 320px;
  min-width: 320px;
  position: sticky;
  top: 88px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.pv-edit-summary-card {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 12px;
  padding: 24px;
}
.pv-edit-summary-name {
  font-size: 1.15rem;
  font-weight: 700;
  color: #171a24;
  margin-top: 8px;
  word-break: break-word;
}
.pv-edit-summary-sku {
  font-size: 0.8rem;
  color: #697386;
  margin-top: 2px;
}
.pv-summary-divider {
  height: 1px;
  background: #e9efff;
  margin: 16px 0;
}
.pv-summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  padding: 8px 0;
  border-bottom: 1px dashed #f1f5f9;
}
.pv-summary-row:last-child {
  border-bottom: none;
}
.pv-summary-row span:first-child {
  color: #697386;
}
.pv-summary-row span:last-child {
  font-weight: 600;
  color: #171a24;
}
.pv-tips-list {
  padding-left: 20px;
  margin: 12px 0 0 0;
  font-size: 0.85rem;
  color: #697386;
}
.pv-tips-list li {
  margin-bottom: 8px;
}
.pv-tips-list li:last-child {
  margin-bottom: 0;
}

/* ===== DETAIL PAGES (3-Column / Rich Panels) ===== */
.pv-detail-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 24px;
  width: 100%;
}
.pv-detail-left, .pv-detail-right {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.pv-detail-card {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 12px;
  padding: 24px;
}
.pv-detail-hero {
  display: flex;
  align-items: center;
  gap: 20px;
  background: linear-gradient(135deg, #ffffff 0%, #fafbfd 100%);
}
.pv-detail-hero-avatar {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background: #e9efff;
  color: #2447c6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}
.pv-detail-hero-info {
  flex: 1;
}
.pv-detail-product-name {
  font-size: 1.4rem;
  font-weight: 700;
  color: #171a24;
}
.pv-detail-sku {
  font-size: 0.85rem;
  color: #697386;
  margin-top: 4px;
}
.pv-badge-cat {
  background: #f0f4ff;
  color: #2447c6;
}
.pv-detail-stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
.pv-detail-stat-box {
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.pv-detail-stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}
.pv-icon-blue { background: #e9efff; color: #2447c6; }
.pv-icon-green { background: #d1fae5; color: #065f46; }
.pv-icon-orange { background: #fef3c7; color: #92400e; }

.pv-detail-stat-val {
  font-size: 1.3rem;
  font-weight: 700;
  color: #171a24;
}
.pv-detail-stat-lbl {
  font-size: 0.75rem;
  color: #697386;
  margin-top: 2px;
}
.pv-detail-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #171a24;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 12px;
}
.pv-detail-table {
  width: 100%;
  border-collapse: collapse;
}
.pv-detail-table td {
  padding: 12px 0;
  font-size: 0.9rem;
  border-bottom: 1px solid #f8fafc;
}
.pv-detail-table tr:last-child td {
  border-bottom: none;
}
.pv-dt-label {
  color: #697386;
  width: 35%;
}
.pv-dt-val {
  color: #171a24;
  font-weight: 600;
}
.pv-dt-price {
  font-size: 1.05rem;
}
.pv-code {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.85rem;
}
.pv-stock-bar-wrap {
  margin-top: 20px;
}
.pv-stock-bar-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #697386;
  margin-bottom: 6px;
}
.pv-stock-bar-track {
  height: 8px;
  background: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}
.pv-stock-bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}
.pv-bar-low { background: #d52d2d; }
.pv-bar-ok { background: #0b9f68; }

.pv-quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.pv-action-btn {
  width: 100%;
  padding: 12px;
  border: 1px solid #d8deea;
  background: #fff;
  color: #171a24;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.18s;
}
.pv-action-btn:hover {
  background: #f0f4ff;
  border-color: #2447c6;
  color: #2447c6;
}
.pv-action-btn-danger {
  color: #d52d2d;
  border-color: #fca5a5;
}
.pv-action-btn-danger:hover {
  background: #fee2e2;
  border-color: #ef4444;
  color: #b91c1c;
}
.pv-action-btn-secondary {
  color: #697386;
}
.pv-action-btn-secondary:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #334155;
}

/* ===== UTILITIES ===== */
.pv-text-success { color: #0b9f68 !important; }
.pv-text-danger { color: #d52d2d !important; }

/* ===== CATEGORY TOGGLE GROUP ===== */
.pv-cat-toggle-group {
  display: inline-flex;
  background: #f1f5f9;
  padding: 3px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.pv-cat-toggle-btn {
  border: none;
  background: transparent;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
}
.pv-cat-toggle-btn:hover {
  color: #334155;
}
.pv-cat-toggle-btn.active {
  background: #ffffff;
  color: #4338ca;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
}

/* ===== TOAST ===== */
.pv-toast {
  position: fixed;
  top: 24px;
  left: 50%;
  transform: translate(-50%, -20px);
  opacity: 0;
  pointer-events: none;
  background: #fff;
  border: 1px solid #d8deea;
  border-radius: 10px;
  padding: 14px 20px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  font-size: 0.9rem;
  font-weight: 600;
  color: #171a24;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.3s ease, opacity 0.3s ease;
  z-index: 1000;
}
.pv-toast-show { transform: translate(-50%, 0); opacity: 1; pointer-events: auto; }

/* ===== ADD NEW CATEGORY COMPACT MODAL (MATCHING ADD EXPENSE) ===== */
.pv-cat-modal-section {
  margin-bottom: 16px;
}

.pv-cat-modal-title {
  font-size: 14.5px;
  font-weight: 700;
  color: #171a24;
  margin: 0 0 4px 0;
}

.pv-cat-modal-divider {
  height: 1px;
  background-color: #e2e8f0;
  width: 100%;
  margin-bottom: 12px;
}

.pv-cat-modal-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

@media (max-width: 576px) {
  .pv-cat-modal-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

.pv-cat-modal-field {
  display: flex;
  flex-direction: column;
}

.pv-cat-modal-label {
  font-size: 13px;
  font-weight: 600;
  color: #4b5563;
  margin-bottom: 4px;
}

.pv-cat-modal-autogen {
  background: none;
  border: none;
  padding: 0;
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.15s;
}

.pv-cat-modal-autogen:hover {
  color: #1d4ed8;
  text-decoration: underline;
}

.pv-cat-modal-input {
  width: 100%;
  height: 40px;
  border: 1px solid #d8deea;
  border-radius: 8px;
  padding: 0 12px;
  font-size: 13.5px;
  color: #171a24;
  background: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

.pv-cat-modal-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.pv-cat-modal-radio-group {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-top: 2px;
}

.pv-cat-modal-radio-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
  font-weight: 500;
  color: #1e293b;
  cursor: pointer;
  margin-bottom: 0;
}

.pv-cat-modal-radio-label input[type="radio"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.pv-cat-modal-textarea {
  width: 100%;
  min-height: 64px;
  height: 64px;
  border: 1px solid #d8deea;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13.5px;
  color: #171a24;
  background: #ffffff;
  resize: vertical;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

.pv-cat-modal-textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.pv-cat-modal-dropzone {
  border: 1.5px dashed #cbd5e1;
  border-radius: 8px;
  padding: 16px 14px;
  text-align: center;
  background: #ffffff;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pv-cat-modal-dropzone:hover,
.pv-cat-modal-dropzone.dragging {
  border-color: #2563eb;
  background: #f8fafc;
}

.pv-cat-modal-drop-icon {
  font-size: 26px;
  color: #64748b;
  line-height: 1;
  margin-bottom: 4px;
}

.pv-cat-modal-drop-text {
  font-size: 13px;
  color: #475569;
}

.pv-cat-modal-upload-link {
  color: #2563eb;
  font-weight: 600;
}

.pv-cat-modal-drop-subtext {
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 2px;
}

.pv-cat-modal-preview-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.pv-cat-modal-preview-img {
  max-height: 55px;
  max-width: 80px;
  border-radius: 6px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
}

.pv-cat-modal-preview-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.pv-cat-modal-preview-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}

.pv-cat-modal-remove-btn {
  background: none;
  border: none;
  padding: 0;
  color: #ef4444;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  margin-top: 2px;
}

.pv-cat-modal-remove-btn:hover {
  text-decoration: underline;
}

.pv-cat-modal-settings-box {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
}

.pv-cat-modal-settings-title {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1px;
}

.pv-cat-modal-settings-desc {
  font-size: 12px;
  color: #64748b;
}

.pv-cat-modal-btn-cancel {
  border: 1px solid #d8deea;
  background: #ffffff;
  color: #475569;
  border-radius: 8px;
  font-weight: 600;
  height: 38px;
  padding: 0 16px;
  font-size: 13.5px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.pv-cat-modal-btn-cancel:hover {
  background: #f8fafc;
  border-color: #94a3b8;
}

.pv-cat-modal-btn-save {
  background: #2563eb;
  color: #ffffff;
  border: 1px solid #2563eb;
  border-radius: 8px;
  font-weight: 600;
  height: 38px;
  padding: 0 18px;
  font-size: 13.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;
}

.pv-cat-modal-btn-save:hover:not(:disabled) {
  background: #1d4ed8;
  border-color: #1d4ed8;
}

.pv-cat-modal-btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .pv-edit-layout {
    flex-direction: column;
  }
  .pv-edit-sidebar {
    width: 100%;
    min-width: 100%;
    position: static;
  }
  .pv-detail-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

@media (max-width: 768px) {
  .pv-stats-grid { grid-template-columns: repeat(2, 1fr); }
  .pv-detail-stats-row { grid-template-columns: 1fr; }
  .pv-form-layout { max-width: 100%; }
}

@media (max-width: 480px) {
  .pv-stats-grid { grid-template-columns: 1fr 1fr; }
  .pv-page { padding: 16px; }
}
</style>