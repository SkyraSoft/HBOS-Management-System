<template>
<div class="possale-page-wrapper">
    <div class="pos-app">
        <!-- HEADER -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <h1 class="page-title mb-0">New Sale</h1>
                
                <!-- CASH REGISTER DAY-OPEN / DAY-CLOSE STATUS (REQ-0024, REQ-0025) -->
                <div class="d-flex align-items-center gap-2 ms-2">
                    <span v-if="store.isRegisterSessionLoading || isSessionChecking" class="badge bg-light text-muted border px-2 py-1.5 d-flex align-items-center gap-1.5" style="font-size: 11.5px;">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <span>Checking Drawer...</span>
                    </span>
                    <span v-else-if="store.hasActiveRegisterSession" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1.5 d-flex align-items-center gap-1.5" style="font-size: 11.5px;">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Register Open (Session #{{ store.activeRegisterSession?.id }})</span>
                    </span>
                    <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1.5 d-flex align-items-center gap-1.5" style="font-size: 11.5px;">
                        <i class="fa-solid fa-lock"></i>
                        <span>Register Closed</span>
                    </span>

                    <button 
                        v-if="!store.hasActiveRegisterSession && !store.isRegisterSessionLoading && !isSessionChecking" 
                        type="button" 
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1 py-1 px-2.5"
                        id="btn-day-open"
                        @click="openDayOpenModal"
                    >
                        <i class="fa-solid fa-door-open"></i>
                        <span>Day Open</span>
                    </button>
                    <button 
                        v-else-if="store.hasActiveRegisterSession" 
                        type="button" 
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 py-1 px-2.5"
                        id="btn-day-close"
                        @click="openDayCloseModal"
                    >
                        <i class="fa-solid fa-door-closed"></i>
                        <span>Day Close</span>
                    </button>
                </div>
            </div>

            <div class="header-right">
                <div class="date-time">
                    <div class="time" id="currentTime">{{ currentTime }}</div>
                    <div class="date" id="currentDate">{{ currentDate }}</div>
                </div>
                <div class="header-divider"></div>
                <div class="cashier">
                    <div class="cashier-name">{{ cashierName }}</div>
                    <div class="cashier-role">{{ cashierRole }}</div>
                </div>
                <div class="avatar">{{ cashierInitials }}</div>
            </div>
        </header>

        <!-- POS -->
        <section class="pos-area">
            <!-- PRODUCTS -->
            <section class="products-panel">
                <!-- SEARCH -->
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" class="search-input" v-model="searchQuery" @input="onSearchInput" @keyup.enter="handleBarcodeScan(searchQuery)" placeholder="Search by product name or scan SKU...">
                    <button type="button" class="scan-btn" title="Scan Barcode / SKU" @click="handleScanButtonClick">
                        <i class="fa-solid fa-barcode"></i>
                    </button>
                </div>

                <!-- CATEGORIES -->
                <div class="categories">
                    <button 
                        v-for="cat in categories" 
                        :key="cat" 
                        :class="['category', { active: (selectedCategory === 'All' && (cat === 'All Products' || cat === 'All')) || (selectedCategory && selectedCategory.toLowerCase() === cat.toLowerCase()) }]" 
                        @click="selectCategory(cat)"
                    >
                        {{ cat }}
                    </button>
                </div>


                <!-- PRODUCTS GRID -->
                <div class="product-grid" id="productGrid">
                    <div v-if="isLoadingProducts" style="grid-column:1/-1; padding:50px 10px; text-align:center; color:#858b94;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:36px;margin-bottom:12px;color:var(--primary, #0d6efd);"></i>
                        <div>Loading products...</div>
                    </div>
                    <div v-else-if="filteredProducts.length === 0" style="grid-column:1/-1; padding:50px 10px; text-align:center; color:#858b94;">
                        <i class="fa-solid fa-box-open" style="font-size:40px;margin-bottom:12px;"></i>
                        <div>No products found.</div>
                    </div>
                    <article v-for="product in filteredProducts" :key="product.id" :class="['product-card', { 'low-stock': product.stock <= product.minStock, 'selected-card': String(selectedProductId) === String(product.id) }]" @click="handleSelectProduct(product)">
                        <div class="product-image-wrap">
                            <img class="product-image" :src="resolveProductImage(product.image, product.name)" :alt="product.name" loading="lazy">
                            <span :class="['stock-badge', getCardStock(product) <= product.minStock ? 'danger' : '']">{{ getCardStock(product) }} left</span>
                        </div>
                        <h3 class="product-name">{{ product.name }}</h3>
                        <div class="product-sku">SKU: {{ product.sku }}</div>

                        
                        <!-- DYNAMIC VARIATIONS -->
                        <div class="product-variations-container" style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                            <div v-for="attr in getProductAttributes(product)" :key="attr.name" class="variation-row" style="display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                <span class="fw-semibold text-secondary" style="flex-shrink: 0; min-width: 60px;">{{ attr.name }}:</span>
                                <div class="custom-pack-select" :class="{ 'open': openDropdownId === product.id + '_' + attr.name }" style="flex: 1; max-width: 140px;">
                                    <button type="button" class="pack-select-btn w-100" @click.stop="toggleDropdown(product.id + '_' + attr.name)" style="height: 28px; padding: 0 8px; font-size: 11.5px;">
                                        <span class="text-truncate">{{ productSelectedAttributes[product.id] ? productSelectedAttributes[product.id][attr.name] : '' }}</span>
                                        <i class="fa-solid fa-chevron-down dropdown-arrow" style="font-size: 10px;"></i>
                                    </button>
                                    <div class="pack-dropdown-menu" v-if="openDropdownId === product.id + '_' + attr.name" style="max-height: 150px; overflow-y: auto;">
                                        <div 
                                            v-for="val in attr.values" 
                                            :key="val" 
                                            class="pack-dropdown-item py-1.5 px-2" 
                                            :class="{ 'active': productSelectedAttributes[product.id] && productSelectedAttributes[product.id][attr.name] === val }"
                                            @click.stop="setProductAttribute(product.id, attr.name, val)"
                                        >
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-circle-check text-primary" style="font-size: 12px;" v-if="productSelectedAttributes[product.id] && productSelectedAttributes[product.id][attr.name] === val"></i>
                                                <i class="fa-regular fa-circle text-muted" style="font-size: 12px;" v-else></i>
                                                <span class="pack-item-name" style="font-size: 11.5px;">{{ val }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PRICE SECTION -->
                        <div class="product-price-section">
                            <span class="price-label">Price:</span>
                            <span class="product-price">Rs. {{ Number(getCardPrice(product)).toLocaleString("en-PK") }}</span>
                            <span v-if="canViewMargin && product.cost && product.price" class="badge bg-success-subtle text-success ms-1" style="font-size: 10px;">
                                {{ Math.round(((product.price - product.cost) / product.price) * 100) }}% margin
                            </span>
                        </div>

                        <!-- QUANTITY & ADD TO CART -->
                        <div class="product-actions-row">
                            <div class="card-qty-control">
                                <button type="button" class="card-qty-btn" @click.stop="decrementProductQty(product.id)">−</button>
                                <span class="card-qty-val">{{ getProductQty(product.id) }}</span>
                                <button type="button" class="card-qty-btn" @click.stop="incrementProductQty(product.id, product.stock)">+</button>
                            </div>
                            <button 
                                :class="['add-cart-btn flex-grow-1', { 'added': isProductInCart(product.id, getCardVariantLabel(product)) }]" 
                                @click.stop="handleSelectProduct(product)" 
                                :disabled="getCardStock(product) === 0"
                            >
                                <i :class="isProductInCart(product.id, getCardVariantLabel(product)) ? 'fa-solid fa-check' : 'fa-solid fa-cart-plus'"></i>
                                <span>{{ isProductInCart(product.id, getCardVariantLabel(product)) ? 'Added' : (getCardStock(product) === 0 ? 'Out of Stock' : 'Add to Cart') }}</span>
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- CART -->
            <aside class="cart-panel modern-order-summary-panel">
                <!-- ORDER'S SUMMARY CARD -->
                <div class="order-summary-card">
                    <!-- HEADER -->
                    <div class="order-header-row">
                        <h2 class="order-summary-title">Order's Summary</h2>
                        <button class="order-expand-btn" type="button" title="View Details">
                            <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                        </button>
                    </div>

                    <div class="order-meta-row">
                        <div class="order-no-text">
                            <span class="text-muted">Order No : </span>
                            <strong>{{ currentOrderNo }}</strong>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span v-if="store.syncStatus === 'offline' || !isNavigatorOnline" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" title="Offline POS Mode: Transactions saved locally on this device">
                                <i class="fa-solid fa-cloud-slash me-1"></i> Offline POS
                            </span>
                            <span v-else-if="store.pendingOutboxCount > 0" class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Transactions pending sync on this device">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> {{ store.pendingOutboxCount }} Pending
                            </span>
                            <span class="badge order-status-badge">In Progress</span>
                        </div>
                    </div>

                    <!-- CUSTOMER SELECTION: Primary Dropdown (Walk-in vs Regular) + Conditional Search Dropdown -->
                    <div class="customer-selection-container mb-2">
                        <!-- Step 1: Initial Dropdown (Walk-in Customer vs Regular Customer) -->
                        <div class="customer-type-select-wrap position-relative">
                            <i class="fa-solid fa-user-tag text-secondary position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); z-index: 2; pointer-events: none; font-size: 13px;"></i>
                            <select 
                                class="form-select customer-select-modern w-100 shadow-sm" 
                                v-model="customerTypeMode" 
                                @change="onCustomerTypeChange"
                                style="padding-left: 34px; height: 38px; font-weight: 600; cursor: pointer; border-radius: 10px;"
                            >
                                <option value="walk_in">🚶 Walk-in Customer</option>
                                <option value="regular">⭐ Regular Customer</option>
                            </select>
                        </div>

                        <!-- Step 2: Next Dropdown with Search Field on top (Appears when Regular Customer is chosen) -->
                        <div v-if="customerTypeMode === 'regular'" class="customer-search-dropdown-wrap mt-2 position-relative" ref="customerSearchWrapperRef">
                            <div class="input-group input-group-sm shadow-sm" style="border-radius: 8px; overflow: hidden;">
                                <span class="input-group-text bg-white border-end-0 text-muted ps-2.5">
                                    <i class="fa-solid fa-magnifying-glass" style="font-size: 12px;"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control border-start-0 ps-1" 
                                    v-model="customerSearchText" 
                                    @input="onCustomerSearchInput" 
                                    @focus="isCustomerDropdownOpen = true"
                                    placeholder="Type name or phone (e.g. Shwaib or 0300...)"
                                    autocomplete="off"
                                    style="font-size: 13px; height: 36px;"
                                />
                                <button 
                                    v-if="customerSearchText" 
                                    class="btn btn-outline-secondary border-start-0 border-end-0 bg-white" 
                                    type="button" 
                                    @click="clearCustomerSelection"
                                    title="Clear customer search"
                                >
                                    <i class="fa-solid fa-xmark text-muted"></i>
                                </button>
                                <button 
                                    class="btn btn-outline-primary px-2 text-nowrap fw-semibold" 
                                    type="button" 
                                    @click="openQuickCustomerModal" 
                                    title="Add New Regular Customer"
                                    style="font-size: 12px;"
                                >
                                    <i class="fa-solid fa-user-plus me-1"></i>+ New
                                </button>
                            </div>

                            <!-- Dropdown Menu: Exactly 6 at a time -->
                            <div 
                                v-if="isCustomerDropdownOpen" 
                                class="customer-dropdown-menu shadow-lg rounded-3 border bg-white position-absolute w-100 mt-1" 
                                style="z-index: 1050; left: 0; max-height: 250px; overflow-y: auto;"
                            >
                                <div v-if="customerFilteredList.length === 0" class="p-3 text-center text-muted small">
                                    <i class="fa-solid fa-user-slash d-block mb-1 text-secondary opacity-50" style="font-size: 18px;"></i>
                                    No regular customer matches "<span class="fw-semibold">{{ customerSearchText }}</span>".
                                    <div class="mt-2">
                                        <button class="btn btn-xs btn-primary rounded-2 px-2 py-1" @click="openQuickCustomerModal" type="button" style="font-size: 11px;">
                                            <i class="fa-solid fa-plus me-1"></i> Add as New Regular Customer
                                        </button>
                                    </div>
                                </div>
                                <div 
                                    v-for="c in customerFilteredList" 
                                    :key="c.id" 
                                    class="customer-dropdown-item px-3 py-2 border-bottom d-flex align-items-center justify-content-between cursor-pointer"
                                    @mousedown.prevent="selectRegularCustomer(c)"
                                    :class="{ 'bg-primary-subtle': selectedRegularCustomerObj?.id === c.id }"
                                >
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; font-size: 11px;">
                                            {{ (c.name || 'C').substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div class="text-truncate">
                                            <div class="fw-semibold text-dark text-truncate" style="font-size: 13px;">
                                                {{ c.name }} <span v-if="c.phone" class="text-secondary fw-normal ms-1 font-monospace" style="font-size: 12px;">{{ c.phone }}</span>
                                            </div>
                                            <div v-if="c.address" class="text-muted small text-truncate" style="font-size: 11px;">{{ c.address }}</div>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <span v-if="Number(c.current_balance || 0) > 0" class="badge bg-danger-subtle text-danger" style="font-size: 10px;">
                                            Khata: Rs {{ Number(c.current_balance).toLocaleString('en-PK') }}
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border" style="font-size: 10px;">
                                            Clear
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Regular Customer Confirmation Pill -->
                            <div v-if="selectedRegularCustomerObj" class="mt-1.5 p-1.5 px-2 bg-success-subtle text-success border border-success-subtle rounded-2 d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                                <span class="text-truncate">
                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    <strong>{{ selectedRegularCustomerObj.name }}</strong>
                                    <span v-if="selectedRegularCustomerObj.phone" class="text-muted ms-1">({{ selectedRegularCustomerObj.phone }})</span>
                                </span>
                                <span v-if="Number(selectedRegularCustomerObj.current_balance || 0) > 0" class="badge bg-danger text-white ms-1" style="font-size: 9.5px;">
                                    Due: Rs {{ Number(selectedRegularCustomerObj.current_balance).toLocaleString('en-PK') }}
                                </span>
                            </div>
                            <div v-else class="text-muted mt-1" style="font-size: 11px;">
                                <i class="fa-solid fa-info-circle me-1 text-primary"></i> Type 1 or more characters to search existing customers.
                            </div>
                        </div>
                    </div>

                    <!-- TOTAL ITEMS HEADER -->
                    <div class="total-items-header">
                        <span>Total Items</span>
                        <span class="items-count-badge">({{ cartItemCount }})</span>
                    </div>

                    <!-- CART ITEMS LIST -->
                    <div class="order-cart-items" id="cartItems">
                        <div class="empty-cart-modern" v-if="store.cart.length === 0">
                            <div class="empty-cart-icon-wrap">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </div>
                            <p class="empty-cart-text">Select products to add them to the cart.</p>
                        </div>
                        
                        <div class="modern-cart-item" v-for="item in store.cart" :key="item.cartItemId || item.id">
                            <img class="cart-item-thumb" :src="resolveProductImage(item.image, item.name)" :alt="item.name">
                            
                            <div class="cart-item-info">
                                <h4 class="cart-item-title">{{ item.name }}</h4>
                                <div class="cart-item-variant">
                                    {{ item.packType || 'Single Pack' }} | Qty: {{ item.quantity }} {{ item.allowFractional ? (item.saleUnit || item.unit || '') : '' }}
                                    <button v-if="item.wholesalePrice" class="badge ms-1 border-0" :class="item.pricingMode === 'wholesale' ? 'bg-primary text-white' : 'bg-light text-secondary'" @click="toggleItemPricingMode(item)" type="button" style="cursor: pointer; font-size: 9px;">
                                        {{ item.pricingMode === 'wholesale' ? 'Wholesale' : 'Retail' }}
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="cart-item-cost">Rs {{ (Math.round(Number(item.price || 0) * item.quantity * 100) / 100).toLocaleString("en-PK") }}</div>
                                    <button class="btn btn-xs p-0 text-muted" @click="promptPriceOverride(item)" title="Override selling price" type="button" style="font-size: 11px;">
                                        <i class="fa-solid fa-pen-to-square text-primary"></i>
                                        <span v-if="item.priceOverridden" class="badge bg-warning-subtle text-warning ms-1" style="font-size: 9px;">Overridden</span>
                                    </button>
                                    <span v-if="canViewMargin && item.cost && item.price" class="badge bg-success-subtle text-success ms-1" style="font-size: 9px;" title="Gross Margin %">
                                        {{ Math.round(((item.price - item.cost) / item.price) * 100) }}% margin
                                    </span>
                                </div>
                            </div>

                            <div class="cart-item-actions">
                                <div class="cart-inline-qty">
                                    <button class="cart-qty-mini-btn" @click="handleUpdateQuantity(item.cartItemId || item.id, item.allowFractional ? (Math.round((item.quantity - 0.5) * 1000) / 1000) : (item.quantity - 1))" type="button">−</button>
                                    <input v-if="item.allowFractional" type="number" step="0.001" min="0.001" class="cart-qty-mini-val" :value="item.quantity" @change="handleUpdateQuantity(item.cartItemId || item.id, parseFloat($event.target.value))" style="width: 55px; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; padding: 1px;" />
                                    <span v-else class="cart-qty-mini-val">{{ item.quantity }}</span>
                                    <button class="cart-qty-mini-btn" @click="handleUpdateQuantity(item.cartItemId || item.id, item.allowFractional ? (Math.round((item.quantity + 0.5) * 1000) / 1000) : (item.quantity + 1))" type="button">+</button>
                                </div>
                                <button class="cart-item-delete-btn" @click="handleRemoveItem(item.cartItemId || item.id)" title="Remove item" type="button">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT METHOD PILLS (Cash, Online, Khata, Split) -->
                    <div class="payment-pills-row">
                        <button 
                            :class="['payment-pill-btn', { active: paymentMethod === 'Cash' }]" 
                            @click="selectPaymentMethod('Cash')" 
                            type="button"
                            title="Direct Cash Payment"
                        >
                            <i class="fa-solid fa-money-bill-wave me-1.5"></i><span>Cash</span>
                        </button>

                        <button 
                            :class="['payment-pill-btn', { active: paymentMethod === 'Online' }]" 
                            @click="selectPaymentMethod('Online')" 
                            type="button"
                            title="Card, QR, Bank Transfer, Raast, or Mobile Wallet"
                        >
                            <i class="fa-solid fa-globe me-1.5"></i><span>Online</span>
                        </button>

                        <button 
                            :class="['payment-pill-btn', { active: paymentMethod === 'Khata', disabled: isWalkInCustomer }]" 
                            @click="selectPaymentMethod('Khata')" 
                            :disabled="isWalkInCustomer"
                            type="button"
                            :title="isWalkInCustomer ? 'Khata (Pay Later) is only available for Regular Customers' : 'Add full bill to Customer Khata'"
                        >
                            <i class="fa-solid fa-book me-1.5"></i><span>Khata</span>
                        </button>

                        <button 
                            :class="['payment-pill-btn', { active: paymentMethod === 'Split' }]" 
                            @click="openSplitPaymentModal" 
                            type="button"
                            title="Split bill between Cash, Online, and Khata"
                        >
                            <i class="fa-solid fa-layer-group me-1.5"></i><span>Split</span>
                        </button>
                    </div>

                    <!-- ORDER BILLING BREAKDOWN CARD -->
                    <div class="order-breakdown-card">
                        <div class="breakdown-line">
                            <span>Subtotal</span>
                            <span class="breakdown-val">Rs {{ store.cartSubtotal.toLocaleString("en-PK") }}</span>
                        </div>

                        <!-- INTERACTIVE DISCOUNT INPUT -->
                        <div class="breakdown-line discount-row-modern">
                            <div class="d-flex align-items-center gap-1">
                                <span>Discount</span>
                                <span class="text-muted" style="font-size: 11px;">(Rs)</span>
                            </div>
                            <div class="discount-input-container">
                                <input 
                                    type="number" 
                                    class="discount-input-field" 
                                    v-model.number="store.cartDiscount" 
                                    placeholder="0" 
                                    min="0"
                                    :max="store.cartSubtotal"
                                />
                            </div>
                        </div>

                        <div class="breakdown-line">
                            <span>VAT (10%)</span>
                            <span class="breakdown-val">Rs {{ Math.round(store.cartSubtotal * 0.1).toLocaleString("en-PK") }}</span>
                        </div>
                        
                        <div class="breakdown-divider-dashed"></div>

                        <div class="breakdown-line total-line">
                            <span class="total-title">Total</span>
                            <span class="total-amount">Rs {{ Math.max(0, store.cartSubtotal - store.cartDiscount + Math.round(store.cartSubtotal * 0.1)).toLocaleString("en-PK") }}</span>
                        </div>
                    </div>

                    <!-- GREEN VARIATION NOTE -->
                    <div class="order-variation-note">
                        <i class="fa-solid fa-circle-check text-success me-1.5"></i>
                        <span>In Order Summary, selected variation (Size / Pack Type) will be shown clearly.</span>
                    </div>

                    <!-- BOTTOM ACTION BUTTONS -->
                    <div class="order-bottom-actions">
                        <button class="order-cancel-btn" @click="startNewSale" type="button">
                            Cancel
                        </button>
                        <button class="order-phone-btn" @click="openHeldCartsModal" type="button" title="View Held Carts">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Held ({{ store.heldCarts.length }})
                        </button>
                        <button class="order-phone-btn" @click="handleHoldCart" :disabled="store.cart.length === 0" type="button" title="Hold Active Cart">
                            <i class="fa-solid fa-pause me-1"></i> Hold
                        </button>
                        <button class="order-confirm-btn" :disabled="store.cart.length === 0 || isProcessing" @click="handleConfirmOrderClick" type="button">
                            <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ isProcessing ? 'Processing...' : (paymentMethod === 'Split' ? 'Split Pay' : 'Confirm Order') }}
                        </button>
                    </div>
                </div>
            </aside>
        </section>

        <!-- SALE SUCCESS OVERLAY -->
        <section class="success-overlay" :class="{ show: showInvoiceModal }" @click.self="handleCloseInvoiceModal">
            <div class="success-container position-relative">
                <button class="invoice-modal-close-btn" @click.stop="handleCloseInvoiceModal" title="Close and return to Sales" type="button" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="success-left">
                    <div class="success-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2 class="success-title">Sale Completed Successfully</h2>
                    <p class="success-description">The transaction has been recorded. What would you like to do next?</p>
                    <div class="sale-info-grid">
                        <div class="sale-info-card">
                            <span>Invoice Number</span>
                            <strong id="successInvoice">{{ currentInvoice?.invoiceNumber }}</strong>
                        </div>
                        <div class="sale-info-card highlight">
                            <span>Total Amount</span>
                            <strong id="successAmount">PKR {{ currentInvoice?.total.toLocaleString("en-PK") }}</strong>
                        </div>
                        <div class="sale-info-card">
                            <span>Payment Method</span>
                            <strong id="successPayment">{{ currentInvoice?.paymentMethod }}</strong>
                        </div>
                        <div class="sale-info-card">
                            <span>Customer</span>
                            <strong id="successCustomer">{{ currentInvoice?.customer }}</strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2 no-print">
                        <span class="text-white-50 small">Roll Width:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" :class="['btn btn-sm', paperSize === '80mm' ? 'btn-primary' : 'btn-outline-light']" @click="setPaperSize('80mm')">80mm</button>
                            <button type="button" :class="['btn btn-sm', paperSize === '58mm' ? 'btn-primary' : 'btn-outline-light']" @click="setPaperSize('58mm')">58mm</button>
                        </div>
                    </div>
                    <div class="action-buttons no-print">
                        <button class="action-btn" @click="printInvoice">
                            <i class="fa-solid fa-print"></i> Print Receipt
                        </button>
                        <button class="action-btn" @click="shareWhatsApp" :disabled="isGeneratingImage">
                            <i class="fa-solid fa-spinner fa-spin" v-if="isGeneratingImage"></i>
                            <i class="fa-brands fa-whatsapp" v-else></i>
                            <span>{{ isGeneratingImage ? 'Generating Image...' : 'Share on WhatsApp' }}</span>
                        </button>
                        <button class="action-btn new-sale" @click="startNewSale">
                            <i class="fa-solid fa-plus"></i> Start New Sale
                        </button>
                    </div>
                </div>

                <div class="success-right">
                    <div :class="['invoice', paperSize === '58mm' ? 'thermal-58mm' : 'thermal-80mm']" id="invoiceArea">
                        <div class="invoice-header">
                            <h2>{{ storeDisplayName }}</h2>
                            <p>123 Market Road, City Center<br>Tel: (021) 12345678</p>
                        </div>
                        <div class="invoice-meta">
                            <div v-if="currentInvoice?.is_offline_pending" class="text-center py-1 my-1 w-100" style="background: #fef3c7; border: 1px dashed #d97706; border-radius: 4px; font-size: 11px; font-weight: bold; color: #92400e;">
                                *** OFFLINE RECEIPT — PENDING SERVER SYNC ***
                            </div>
                            <div>
                                <span>Invoice No:</span>
                                <span id="invoiceNumber">{{ currentInvoice?.invoiceNumber }}</span>
                            </div>
                            <div>
                                <span>Date:</span>
                                <span>{{ formatInvoiceDate(currentInvoice?.date) }}</span>
                            </div>
                            <div>
                                <span>Customer:</span>
                                <span>{{ currentInvoice?.customer || 'Walk-in Customer' }}</span>
                            </div>
                        </div>
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, itemIdx) in currentInvoice?.items" :key="item.cartItemId || item.id || itemIdx">
                                    <td>
                                        <div class="fw-semibold">{{ item.name }}</div>
                                        <div v-if="item.variantLabel && item.variantLabel !== 'Default'" class="invoice-item-sub">
                                            {{ item.variantLabel }}
                                        </div>
                                        <div v-else-if="item.packType && item.packType !== 'Single Pack'" class="invoice-item-sub">
                                            {{ item.packType }}
                                        </div>
                                    </td>
                                    <td>{{ item.quantity }}</td>
                                    <td class="text-end">{{ (Number(item.price) * Number(item.quantity)).toLocaleString("en-PK") }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="invoice-totals">
                            <div class="invoice-total-row">
                                <span>Subtotal</span>
                                <span id="invoiceSubtotal">{{ Number(currentInvoice?.subtotal || 0).toLocaleString("en-PK") }}</span>
                            </div>
                            <div class="invoice-total-row" v-if="Number(currentInvoice?.discount || 0) > 0">
                                <span>Discount</span>
                                <span id="invoiceDiscount">{{ Number(currentInvoice?.discount || 0).toLocaleString("en-PK") }}</span>
                            </div>
                            <div class="invoice-total-row invoice-grand-total">
                                <span>Total (PKR)</span>
                                <span id="invoiceTotal">{{ Number(currentInvoice?.total || 0).toLocaleString("en-PK") }}</span>
                            </div>
                        </div>
                        <div class="invoice-line"></div>
                        <div class="thank-you">Thank you for shopping with us!</div>
                        <div v-if="receiptReturnPolicy" class="receipt-return-policy-footer text-center mt-1 mb-2" style="font-size: 10px; color: #555; line-height: 1.3;">
                            {{ receiptReturnPolicy }}
                        </div>
                        <div class="barcode-svg-wrap" v-html="generateBarcodeSvg(currentInvoice?.invoiceNumber)"></div>
                        <div class="invoice-number">{{ currentInvoice?.invoiceNumber }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MODAL 1: QUICK ADD CUSTOMER MODAL (SRT-GAP-009) -->
        <div v-if="showQuickCustomerModal" class="pos-modal-backdrop" @click.self="closeQuickCustomerModal">
            <div class="pos-modal-card">
                <div class="pos-modal-header">
                    <h5 class="m-0 fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i>Quick Add Customer</h5>
                    <button class="btn-close" @click="closeQuickCustomerModal" type="button"></button>
                </div>
                <div class="pos-modal-body">
                    <!-- Duplicate Phone Warning -->
                    <div v-if="matchingDuplicateCustomer" class="alert alert-warning d-flex align-items-center justify-content-between p-2 mb-3">
                        <div class="small">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            <strong>Customer Exists:</strong> {{ matchingDuplicateCustomer.name }} ({{ matchingDuplicateCustomer.phone }})
                        </div>
                        <button class="btn btn-sm btn-warning text-nowrap ms-2 py-0 px-2" @click="selectExistingCustomer(matchingDuplicateCustomer)" type="button">
                            Select Customer
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" v-model="quickCustomer.name" placeholder="e.g. Muhammad Ali" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Primary Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" v-model="quickCustomer.phone" placeholder="03XXXXXXXXX or +923XXXXXXXXX" required />
                        <div class="form-text small" style="font-size: 11px;">Pakistan format: 03001234567 or +923001234567</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Secondary / Emergency Phone <span class="text-muted">(Optional)</span></label>
                        <input type="text" class="form-control form-control-sm" v-model="quickCustomer.secondary_phone" placeholder="Alternate contact number" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Address / Notes <span class="text-muted">(Optional)</span></label>
                        <input type="text" class="form-control form-control-sm" v-model="quickCustomer.address" placeholder="Area or shop address" />
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i> Opening Balance and Credit Limit are omitted at checkout counter per store policy.
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="closeQuickCustomerModal" type="button">Cancel</button>
                    <button class="btn btn-sm btn-primary" :disabled="!isQuickCustomerValid || isSavingCustomer" @click="submitQuickCustomer" type="button">
                        <span v-if="isSavingCustomer" class="spinner-border spinner-border-sm me-1"></span>
                        Save & Select Customer
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 2: HELD CARTS BUFFER MODAL (SRT-GAP-007) -->
        <div v-if="showHeldCartsModal" class="pos-modal-backdrop" @click.self="closeHeldCartsModal">
            <div class="pos-modal-card" style="max-width: 650px;">
                <div class="pos-modal-header">
                    <div>
                        <h5 class="m-0 fw-bold"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Held Carts Buffer</h5>
                        <div class="text-muted small" style="font-size: 12px;">{{ navigationStore.isMultiBranch ? 'Branch Shared Buffer — Active for current business & branch' : 'Shared Cart Buffer' }}</div>
                    </div>
                    <button class="btn-close" @click="closeHeldCartsModal" type="button"></button>
                </div>
                <div class="pos-modal-body p-0">
                    <div v-if="store.heldCarts.length === 0" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                        {{ navigationStore.isMultiBranch ? 'No held carts in this branch buffer.' : 'No held carts found.' }}
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Hold ID / Time</th>
                                    <th>Customer / Note</th>
                                    <th>Operator</th>
                                    <th class="text-end">Items & Total</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="held in store.heldCarts" :key="held.held_id">
                                    <td>
                                        <div class="fw-semibold">{{ held.held_id }}</div>
                                        <div class="text-muted small" style="font-size: 11px;">{{ formatInvoiceDate(held.held_at) }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ held.customer_name || 'Walk-in Customer' }}</div>
                                        <div v-if="held.customer_note" class="text-muted small" style="font-size: 11px;">
                                            <i class="fa-solid fa-note-sticky me-1"></i>{{ held.customer_note }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-solid fa-user me-1"></i>{{ held.held_by_user_name || 'Cashier' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold">Rs {{ (held.total || 0).toLocaleString('en-PK') }}</div>
                                        <div class="text-muted small" style="font-size: 11px;">{{ held.items?.length || 0 }} items</div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary py-1 px-2 me-1" @click="handleResumeCartClick(held.held_id)" type="button" title="Resume Cart">
                                            <i class="fa-solid fa-play me-1"></i>Resume
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger py-1 px-2" @click="handleDiscardCart(held.held_id)" type="button" title="Discard">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="closeHeldCartsModal" type="button">Close</button>
                </div>
            </div>
        </div>

        <!-- MODAL 2B: RESUME CART COLLISION MODAL -->
        <div v-if="showResumeCollisionModal" class="pos-modal-backdrop" @click.self="showResumeCollisionModal = false">
            <div class="pos-modal-card" style="max-width: 480px;">
                <div class="pos-modal-header bg-warning-subtle text-warning-emphasis">
                    <h5 class="m-0 fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Active Cart Contains Items</h5>
                    <button class="btn-close" @click="showResumeCollisionModal = false" type="button"></button>
                </div>
                <div class="pos-modal-body">
                    <p class="mb-3">You currently have <strong>{{ cartItemCount }} item(s)</strong> in the active cart. What would you like to do before resuming the held cart?</p>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-primary d-flex align-items-center justify-content-between p-2.5 text-start" @click="resolveCollisionHoldFirst" type="button">
                            <div>
                                <div class="fw-semibold">Hold Active Cart & Resume</div>
                                <div class="small opacity-75">Saves current active items to buffer first, then resumes held cart.</div>
                            </div>
                            <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                        <button class="btn btn-outline-danger d-flex align-items-center justify-content-between p-2.5 text-start" @click="resolveCollisionOverwrite" type="button">
                            <div>
                                <div class="fw-semibold">Replace Current Cart</div>
                                <div class="small opacity-75">Discards active items and directly loads the selected held cart.</div>
                            </div>
                            <i class="fa-solid fa-trash-can ms-2"></i>
                        </button>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="showResumeCollisionModal = false" type="button">Cancel</button>
                </div>
            </div>
        </div>

        <!-- MODAL 3: PRICE OVERRIDE AUTHORIZATION MODAL (SRT-GAP-008) -->
        <div v-if="showPriceOverrideModal" class="pos-modal-backdrop" @click.self="closePriceOverrideModal">
            <div class="pos-modal-card" style="max-width: 460px;">
                <div class="pos-modal-header">
                    <h5 class="m-0 fw-bold"><i class="fa-solid fa-tag text-primary me-2"></i>Line Item Price Override</h5>
                    <button class="btn-close" @click="closePriceOverrideModal" type="button"></button>
                </div>
                <div class="pos-modal-body">
                    <div class="p-2 mb-3 bg-light rounded border">
                        <div class="fw-bold">{{ overrideTargetItem?.name }}</div>
                        <div class="d-flex justify-content-between text-muted small mt-1">
                            <span>Catalog Unit Price:</span>
                            <span class="fw-semibold text-dark">Rs {{ (overrideTargetItem?.price || 0).toLocaleString('en-PK') }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Unit Selling Price (Rs) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" v-model.number="overrideForm.new_price" min="0" step="1" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Override Reason</label>
                        <input type="text" class="form-control form-control-sm" v-model="overrideForm.reason" placeholder="e.g. Bulk discount, Damaged box, Special deal" />
                    </div>

                    <!-- Cashier elevated approval required -->
                    <div v-if="!isElevatedUser" class="p-3 mb-3 border rounded bg-warning-subtle text-dark">
                        <div class="fw-bold text-warning-emphasis mb-1">
                            <i class="fa-solid fa-shield-halved me-1"></i>Manager / Owner Approval Required
                        </div>
                        <div class="small text-muted mb-2">
                            Cashier cannot self-authorize price overrides. Enter a Branch Manager or Business Owner credential.
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Approver Email / Username</label>
                            <input type="email" class="form-control form-control-sm" v-model="overrideForm.approver_email" placeholder="manager@store.com" autocomplete="off" />
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Approver Password / PIN</label>
                            <input type="password" class="form-control form-control-sm" v-model="overrideForm.approver_password" placeholder="••••••••" autocomplete="new-password" />
                        </div>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="closePriceOverrideModal" type="button">Cancel</button>
                    <button class="btn btn-sm btn-primary" :disabled="!isOverrideFormValid" @click="submitPriceOverride" type="button">
                        Apply Price Override
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 4: SPLIT MULTI-TENDER MODAL (SRT-GAP-006) -->
        <div v-if="showSplitModal" class="pos-modal-backdrop" @click.self="closeSplitPaymentModal">
            <div class="pos-modal-card" style="max-width: 600px;">
                <div class="pos-modal-header bg-dark text-white">
                    <div>
                        <h5 class="m-0 fw-bold"><i class="fa-solid fa-layer-group text-primary me-2"></i>Split Multi-Tender Payment</h5>
                        <div class="small opacity-75">Combine Cash, Digital, and Khata tenders</div>
                    </div>
                    <button class="btn-close btn-close-white" @click="closeSplitPaymentModal" type="button"></button>
                </div>
                <div class="pos-modal-body">
                    <!-- Grand Total Banner -->
                    <div class="d-flex justify-content-between align-items-center p-3 mb-3 rounded bg-primary text-white">
                        <div>
                            <div class="text-uppercase small opacity-75 fw-bold">Total Bill Amount</div>
                            <h3 class="m-0 fw-bold">Rs {{ payableTotal.toLocaleString('en-PK') }}</h3>
                        </div>
                        <div class="text-end">
                            <div class="text-uppercase small opacity-75 fw-bold">Customer</div>
                            <div class="fw-semibold">{{ selectedCustomer || 'Walk-in Customer' }}</div>
                        </div>
                    </div>

                    <!-- Tender Rows: Cash, Online, Khata -->
                    <div class="d-flex flex-column gap-3">
                        <!-- 1. Cash Tender -->
                        <div class="p-3 border rounded bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold"><i class="fa-solid fa-money-bill-wave text-success me-1"></i> Cash Tender</span>
                                <div class="d-flex align-items-center gap-2">
                                    <button 
                                        type="button" 
                                        class="btn btn-xs btn-outline-success rounded-pill px-2 py-0.5" 
                                        style="font-size: 11px;"
                                        @click="splitTenders.cash_applied = payableTotal; splitTenders.cash_tendered = payableTotal; splitTenders.online = 0; splitTenders.khata = 0;"
                                    >
                                        Full Bill
                                    </button>
                                    <span v-if="cashChange > 0" class="badge bg-success-subtle text-success fs-6">Change: Rs {{ cashChange.toLocaleString('en-PK') }}</span>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Cash Applied to Bill</label>
                                    <input type="number" class="form-control form-control-sm" v-model.number="splitTenders.cash_applied" min="0" step="1" />
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Cash Handed by Customer</label>
                                    <input type="number" class="form-control form-control-sm" v-model.number="splitTenders.cash_tendered" min="0" step="1" />
                                </div>
                            </div>
                            <div v-if="splitTenders.cash_tendered < splitTenders.cash_applied && splitTenders.cash_tendered > 0" class="text-danger small mt-1">
                                <i class="fa-solid fa-circle-exclamation me-1"></i> Handed cash is less than applied cash.
                            </div>
                        </div>

                        <!-- 2. Online Payment (Card / QR / Bank / Mobile Wallet) -->
                        <div class="p-3 border rounded bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold"><i class="fa-solid fa-globe text-primary me-1"></i> Online Paid (Card / QR / Bank)</span>
                                <button 
                                    type="button" 
                                    class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5" 
                                    style="font-size: 11px;"
                                    @click="fillRemainingOnline"
                                >
                                    Pay Remaining (Rs {{ Math.max(0, payableTotal - Number(splitTenders.cash_applied || 0) - Number(splitTenders.khata || 0)).toLocaleString('en-PK') }})
                                </button>
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">Amount Paid Online</label>
                                <input type="number" class="form-control form-control-sm" v-model.number="splitTenders.online" min="0" step="1" placeholder="Rs 0" />
                            </div>
                        </div>

                        <!-- 3. Khata / Credit Tender -->
                        <div class="p-3 border rounded" :class="isWalkInCustomer ? 'bg-light opacity-75' : 'bg-warning-subtle'">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-warning-emphasis">
                                    <i class="fa-solid fa-book text-warning-emphasis me-1"></i> Khata (Store Credit / Pay Later)
                                </span>
                                <span v-if="!isWalkInCustomer" class="d-flex align-items-center gap-2">
                                    <button 
                                        type="button" 
                                        class="btn btn-xs btn-outline-warning text-dark rounded-pill px-2 py-0.5" 
                                        style="font-size: 11px;"
                                        @click="fillRemainingKhata"
                                    >
                                        Remaining to Khata
                                    </button>
                                    <span class="badge bg-warning text-dark">Unpaid Due</span>
                                </span>
                                <span v-else class="badge bg-secondary text-white">Disabled for Walk-in</span>
                            </div>
                            <input 
                                type="number" 
                                class="form-control form-control-sm" 
                                v-model.number="splitTenders.khata" 
                                min="0" 
                                step="1" 
                                :disabled="isWalkInCustomer"
                                :placeholder="isWalkInCustomer ? 'Khata disabled for walk-in customer' : 'Rs 0'"
                            />
                            <div v-if="isWalkInCustomer" class="alert alert-secondary py-1 px-2 mt-2 mb-0 small">
                                <i class="fa-solid fa-lock me-1"></i> Khata (Pay Later) requires selecting a registered Regular Customer.
                            </div>
                        </div>
                    </div>

                    <!-- Live Settlement Summary -->
                    <div class="mt-3 p-3 bg-light rounded border">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Paid Online & Cash Applied:</span>
                            <span class="fw-semibold text-dark">Rs {{ totalPaidTenders.toLocaleString('en-PK') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Khata Credit (Receivable):</span>
                            <span class="fw-semibold text-dark">Rs {{ (splitTenders.khata || 0).toLocaleString('en-PK') }}</span>
                        </div>
                        <hr class="my-2" />
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total Settled:</span>
                            <span class="fw-bold fs-5" :class="isSplitBalanced ? 'text-success' : 'text-danger'">
                                Rs {{ totalTenderSettled.toLocaleString('en-PK') }} / Rs {{ payableTotal.toLocaleString('en-PK') }}
                            </span>
                        </div>
                        <div v-if="!isSplitBalanced" class="text-danger small mt-1 text-end">
                            <span v-if="totalTenderSettled < payableTotal">Remaining to settle: Rs {{ (payableTotal - totalTenderSettled).toLocaleString('en-PK') }}</span>
                            <span v-else>Over-settled by: Rs {{ (totalTenderSettled - payableTotal).toLocaleString('en-PK') }}</span>
                        </div>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="closeSplitPaymentModal" type="button">Cancel</button>
                    <button class="btn btn-sm btn-primary px-3" :disabled="!canSubmitSplit || isProcessing" @click="submitSplitCheckout" type="button">
                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-1"></span>
                        Complete Split Sale
                    </button>
                </div>
            </div>
        </div>

        <!-- DAY OPEN MODAL (REQ-0024, SRT-GAP-017) -->
        <div v-if="showDayOpenModal" class="pos-modal-backdrop" @click.self="closeDayOpenModal">
            <div class="pos-modal-card" style="max-width: 450px;">
                <div class="pos-modal-header bg-primary text-white">
                    <h5 class="m-0 fs-6 fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-door-open"></i> Open Cash Register (Day Open)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeDayOpenModal"></button>
                </div>
                <div class="pos-modal-body">
                    <p class="text-secondary small mb-3">
                        Enter the physical cash float counted at the start of your register session. This must match the drawer ledger balance.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Opening Float (PKR) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="form-control" 
                                id="input-day-open-float"
                                v-model.number="dayOpenFloat"
                                placeholder="0.00"
                                required
                            />
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Shift / Register Notes (Optional)</label>
                        <textarea 
                            class="form-control" 
                            rows="2" 
                            v-model="dayOpenNotes" 
                            placeholder="Optional notes regarding this shift or starting float..."
                        ></textarea>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="closeDayOpenModal" type="button">Cancel</button>
                    <button 
                        class="btn btn-sm btn-primary px-3" 
                        :disabled="dayCloseIsSubmitting" 
                        @click="submitDayOpen" 
                        type="button"
                        id="btn-confirm-day-open"
                    >
                        <span v-if="dayCloseIsSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                        Confirm & Open Register
                    </button>
                </div>
            </div>
        </div>

        <!-- DAY CLOSE BALANCING WIZARD MODAL (REQ-0025, SRT-GAP-018) -->
        <div v-if="showDayCloseModal" class="pos-modal-backdrop" @click.self="closeDayCloseModal">
            <div class="pos-modal-card" style="max-width: 500px;">
                <div class="pos-modal-header bg-dark text-white">
                    <h5 class="m-0 fs-6 fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-door-closed"></i> Cash Register Day Close & Reconciliation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeDayCloseModal"></button>
                </div>
                <div class="pos-modal-body">
                    <!-- Owner / Manager Informational Bar -->
                    <div v-if="isOwnerOrManager && store.activeRegisterSession" class="alert alert-info py-2 px-3 small mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Starting Float:</span>
                            <strong>Rs. {{ Number(store.activeRegisterSession.opening_float || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 }) }}</strong>
                        </div>
                        <div v-if="store.activeRegisterSession.closing_cash_expected !== undefined" class="d-flex justify-content-between">
                            <span>System Expected Cash:</span>
                            <strong>Rs. {{ Number(store.activeRegisterSession.closing_cash_expected || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 }) }}</strong>
                        </div>
                    </div>

                    <!-- Blind Cashier Instructions -->
                    <p class="text-secondary small mb-3">
                        Count physical cash in the drawer and enter the total below. All pending sales on this device will be verified prior to closure.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Counted Physical Cash (PKR) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="form-control form-control-lg fw-bold" 
                                id="input-day-close-counted"
                                v-model.number="dayCloseCounted"
                                placeholder="0.00"
                                required
                            />
                        </div>
                    </div>

                    <!-- Variance Reason (Requested if discrepancy exists) -->
                    <div v-if="dayCloseReasonRequired" class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        A discrepancy was detected between physical count and system records. Please provide an explanation below:
                    </div>

                    <div v-if="dayCloseReasonRequired" class="mb-3">
                        <label class="form-label fw-semibold small text-danger">Discrepancy Reason <span class="text-danger">*</span></label>
                        <textarea 
                            class="form-control border-danger" 
                            rows="2" 
                            id="input-day-close-reason"
                            v-model="dayCloseVarianceReason" 
                            placeholder="State reason for variance (e.g., miscount, unrecorded petty cash, coin rounding)..."
                            required
                        ></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Closing Notes (Optional)</label>
                        <textarea 
                            class="form-control" 
                            rows="2" 
                            v-model="dayCloseNotes" 
                            placeholder="Optional shift handover or closing notes..."
                        ></textarea>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button class="btn btn-sm btn-secondary" @click="closeDayCloseModal" type="button">Cancel</button>
                    <button 
                        class="btn btn-sm btn-danger px-3" 
                        :disabled="dayCloseIsSubmitting" 
                        @click="submitDayClose" 
                        type="button"
                        id="btn-confirm-day-close"
                    >
                        <span v-if="dayCloseIsSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                        Reconcile & Close Register
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRetailStore } from '@/stores/retail';
import { useCustomersStore } from '@/stores/customers';
import { useAuthStore } from '@/stores/auth';
import { useNavigationStore } from '@/stores/navigation';
import { useToast } from 'vue-toastification';
import html2canvas from 'html2canvas';
import api from '@/api';

const route = useRoute();
const router = useRouter();
const store = useRetailStore();
const customersStore = useCustomersStore();
const authStore = useAuthStore();
const navigationStore = useNavigationStore();
const toast = useToast();

const storeDisplayName = computed(() => {
  const isDemo = localStorage.getItem('hbos_demo_mode') === 'true' || localStorage.getItem('hbos_demo_mode') === 'single' || localStorage.getItem('hbos_demo_mode') === 'multi';
  const demoType = localStorage.getItem('hbos_demo_type') || localStorage.getItem('hbos_demo_mode');
  if (isDemo) {
    if (demoType === 'multi') {
      return authStore.user?.branch_name ? `Al-Madina Supermarket (${authStore.user.branch_name})` : 'Al-Madina Supermarket';
    }
    return 'Al-Madina General Store';
  }
  const baseName = authStore.user?.business_name || authStore.user?.business?.name || 'HBOS Store';
  if (navigationStore.isMultiBranch && authStore.user?.branch_name) {
    return `${baseName} (${authStore.user.branch_name})`;
  }
  return baseName;
});

const cashierName = computed(() => authStore.user?.name || 'Cashier');
const cashierRole = computed(() => authStore.user?.role || authStore.user?.roles?.[0]?.name || 'Salesperson');
const cashierInitials = computed(() => {
  const name = authStore.user?.name || '';
  if (!name) return 'POS';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
});

const searchQuery = ref(route.query.search || '');
const selectedCategory = ref(route.query.category || 'All');

const selectedProductId = ref(route.query.product || null);

const canViewMargin = computed(() => {
  const user = authStore.user;
  if (!user) return false;
  if (user.role === 'Business Owner' || user.roles?.some(r => r.name === 'Business Owner')) return true;
  if (authStore.can?.('view product costs')) return true;
  return false;
});

const toggleItemPricingMode = (item) => {
  if (item.pricingMode === 'wholesale') {
    item.pricingMode = 'retail';
    item.price = Number(item.unitPrice) || Number(item.price);
  } else {
    item.pricingMode = 'wholesale';
    if (item.wholesalePrice !== null && item.wholesalePrice !== undefined) {
      item.price = Number(item.wholesalePrice);
    }
  }
};

// DIRECT INLINE ATTRIBUTES & VARIATIONS STATE
const selectedAttributes = ref({});
const editableProductAttributes = ref([]);
const inlineQty = ref(1);

const activeProduct = computed(() => {
  if (!selectedProductId.value) return null;
  return store.products.find(p => String(p.id) === String(selectedProductId.value)) || null;
});




const getProductAttributes = (product) => {
  if (!product || !product.hasVariations) return [];
  if (product.attributes && Array.isArray(product.attributes)) {
    // If we have a matrix, we can determine available values
    const selected = productSelectedAttributes.value[product.id] || {};
    const variations = product.variations || [];
    
    return product.attributes.map(attr => {
      // Find all variations that match current selections for OTHER attributes
      // To see what values of THIS attribute are valid
      const validVariations = variations.filter(v => {
        const vAttrs = v.attributes || {};
        return product.attributes.every(otherAttr => {
          if (otherAttr.name === attr.name) return true; // ignore current attribute
          if (!selected[otherAttr.name]) return true; // ignore if not selected
          return String(vAttrs[otherAttr.name]) === String(selected[otherAttr.name]);
        });
      });
      
      // Extract unique values for THIS attribute from valid variations
      let availableValues = [];
      if (validVariations.length > 0) {
        availableValues = [...new Set(validVariations.map(v => v.attributes && v.attributes[attr.name]).filter(Boolean))];
      } else {
        availableValues = Array.isArray(attr.values) ? attr.values : [];
      }
      
      return {
        name: attr.name,
        values: availableValues.length > 0 ? availableValues : (Array.isArray(attr.values) ? attr.values : [])
      };
    });
  }
  return [];
};

const getMatchedVariant = (product) => {
  if (!product || !product.hasVariations || !product.variations) return null;
  const attrs = productSelectedAttributes.value[product.id] || {};
  return product.variations.find(v => {
    const vAttrs = v.attributes || {};
    return Object.keys(vAttrs).every(k => String(vAttrs[k]) === String(attrs[k]));
  });
};

const productSelectedAttributes = ref({});
const openDropdownId = ref(null);

watch(() => store.products, (products) => {
  if (products && Array.isArray(products)) {
    products.forEach(p => {
      if (!productSelectedAttributes.value[p.id]) {
        if (p.hasVariations && p.variations && p.variations.length > 0) {
          // Initialize with the first valid variation from the matrix
          const firstVariant = p.variations[0];
          productSelectedAttributes.value[p.id] = { ...firstVariant.attributes };
        } else if (p.attributes && p.attributes.length > 0) {
          const initVals = {};
          p.attributes.forEach(attr => {
            initVals[attr.name] = attr.values && attr.values.length > 0 ? attr.values[0] : '';
          });
          productSelectedAttributes.value[p.id] = initVals;
        }
      }
    });
  }
}, { immediate: true, deep: true });

const toggleDropdown = (id) => {
  if (openDropdownId.value === id) {
    openDropdownId.value = null;
  } else {
    openDropdownId.value = id;
  }
};

const setProductAttribute = (productId, attrName, value) => {
  if (!productSelectedAttributes.value[productId]) {
    productSelectedAttributes.value[productId] = {};
  }
  productSelectedAttributes.value[productId][attrName] = value;
  
  // Interconnected fallback: If changing this attribute invalidates other attributes, 
  // we must automatically switch them to the first available valid combination
  const product = store.products.find(p => String(p.id) === String(productId));
  if (product && product.hasVariations && product.variations) {
    const currentSelection = productSelectedAttributes.value[productId];
    // Check if current exact combination exists
    const exactMatch = product.variations.find(v => {
      const vAttrs = v.attributes || {};
      return Object.keys(currentSelection).every(k => String(vAttrs[k]) === String(currentSelection[k]));
    });
    
    // If not, find the first variation that matches the NEWLY set attribute
    if (!exactMatch) {
      const fallbackVariant = product.variations.find(v => {
        const vAttrs = v.attributes || {};
        return String(vAttrs[attrName]) === String(value);
      });
      if (fallbackVariant) {
        // Auto-correct the other attributes
        productSelectedAttributes.value[productId] = { ...fallbackVariant.attributes };
      }
    }
  }
  
  openDropdownId.value = null;
};


const getCardPrice = (product) => {
  if (!product.hasVariations) return Number(product.price) || 0;
  const variant = getMatchedVariant(product);
  return variant ? Number(variant.price) : (Number(product.price) || 0);
};

const getCardStock = (product) => {
  if (!product.hasVariations) return Number(product.stock) || 0;
  const variant = getMatchedVariant(product);
  return variant ? Number(variant.stock) : 0;
};

const getCardSku = (product) => {
  if (!product.hasVariations) return product.sku || 'PRD';
  const variant = getMatchedVariant(product);
  return variant ? variant.sku : product.sku;
};

const getCardVariantLabel = (product) => {
  if (!product.hasVariations) return 'Default';
  const attrs = productSelectedAttributes.value[product.id] || {};
  const valParts = Object.values(attrs).filter(Boolean);
  return valParts.length > 0 ? valParts.join(' / ') : 'Default';
};



// Sync editableProductAttributes and selectedAttributes whenever activeProduct changes
watch(activeProduct, (newProd) => {
  if (newProd) {
    const rawAttrs = getProductAttributes(newProd);
    editableProductAttributes.value = JSON.parse(JSON.stringify(rawAttrs));
    const initVals = {};
    editableProductAttributes.value.forEach(attr => {
      initVals[attr.name] = attr.values[0] || '';
    });
    selectedAttributes.value = initVals;
    inlineQty.value = 1;
  } else {
    editableProductAttributes.value = [];
    selectedAttributes.value = {};
  }
}, { immediate: true });

// Actions for editing attributes on the fly
const promptAddAttributeValue = (attr) => {
  const newVal = window.prompt(`Enter new option/value for "${attr.name}":`);
  if (newVal && newVal.trim() !== '') {
    const trimmed = newVal.trim();
    if (!attr.values.includes(trimmed)) {
      attr.values.push(trimmed);
    }
    selectedAttributes.value[attr.name] = trimmed;
    toast.success(`Added "${trimmed}" to ${attr.name}`);
  }
};

const removeAttribute = (index) => {
  const removed = editableProductAttributes.value[index];
  if (removed) {
    delete selectedAttributes.value[removed.name];
    editableProductAttributes.value.splice(index, 1);
    toast.info(`Removed ${removed.name} attribute`);
  }
};

const addNewCustomAttribute = () => {
  const name = window.prompt('Enter new Attribute Name (e.g. Warranty, Edition, Condition, Voltage):');
  if (name && name.trim() !== '') {
    const trimmedName = name.trim();
    const val = window.prompt(`Enter default option/value for "${trimmedName}":`) || 'Standard';
    const trimmedVal = val.trim();
    editableProductAttributes.value.push({
      name: trimmedName,
      values: [trimmedVal]
    });
    selectedAttributes.value[trimmedName] = trimmedVal;
    toast.success(`Added new attribute "${trimmedName}"`);
  }
};

const resetProductAttributes = () => {
  if (activeProduct.value) {
    const rawAttrs = getProductAttributes(activeProduct.value);
    editableProductAttributes.value = JSON.parse(JSON.stringify(rawAttrs));
    const initVals = {};
    editableProductAttributes.value.forEach(attr => {
      initVals[attr.name] = attr.values[0] || '';
    });
    selectedAttributes.value = initVals;
    toast.info('Reset attributes to category defaults');
  }
};

const computedInlinePrice = computed(() => {
  if (!activeProduct.value) return 0;
  let price = Number(activeProduct.value.price) || 0;
  
  // Dynamic attribute multiplier adjustments
  const attrs = selectedAttributes.value || {};
  for (const [key, val] of Object.entries(attrs)) {
    const v = String(val).toLowerCase();
    // Storage adjustments
    if (v === '128gb') price *= 1.0;
    else if (v === '256gb' || v === '512gb ssd') price *= 1.2;
    else if (v === '512gb' || v === '1tb ssd') price *= 1.45;
    // RAM adjustments
    else if (v === '8gb' && key.toLowerCase() === 'ram') price *= 1.1;
    else if (v === '12gb' || v === '16gb') price *= 1.25;
    else if (v === '32gb') price *= 1.5;
    // Processor adjustments
    else if (v === 'core i7') price *= 1.35;
    else if (v === 'core i9') price *= 1.7;
    // Screen Size adjustments (TV / Monitor / Tablet)
    else if (v === '43"' || v === '24"') price *= 1.25;
    else if (v === '50"' || v === '27"') price *= 1.5;
    else if (v === '55"' || v === '32"') price *= 1.8;
    else if (v === '65"') price *= 2.3;
    else if (v === '75"') price *= 3.0;
    // Display / Resolution
    else if (v === 'oled' || v === 'qled' || v === '4k') price *= 1.3;
    else if (v === '8k') price *= 1.8;
    // Capacity AC / Fridge / Washing Machine
    else if (v === '1.5 ton' || v === '300l' || v === '8kg') price *= 1.25;
    else if (v === '2 ton' || v === '400l' || v === '12kg') price *= 1.55;
    // Connectivity
    else if (v === 'wifi + cellular') price *= 1.2;
    // Version
    else if (v === 'pro' || v === 'ultra') price *= 1.3;
    // Pack Type adjustments
    else if (v === 'box' || v === 'dozen') price *= 12.0;
    else if (v === 'carton') price *= 24.0;
    else if (v === 'pack') price *= 6.0;
    // Size (ml/L/Weight) adjustments
    else if (v === '1.5l' || v === '1kg') price *= 1.8;
    else if (v === '2.25l' || v === '5kg') price *= 2.4;
    else if (v === '250ml' || v === '250g') price *= 0.5;
  }
  
  return Math.round(price);
});

const computedInlineStock = computed(() => {
  if (!activeProduct.value) return 0;
  const baseStock = Number(activeProduct.value.stock) || 0;
  const packVal = (selectedAttributes.value['Pack Type'] || '').toLowerCase();
  if (packVal === 'pack') return Math.max(1, Math.floor(baseStock / 6));
  if (packVal === 'box' || packVal === 'dozen') return Math.max(1, Math.floor(baseStock / 12));
  if (packVal === 'carton') return Math.max(1, Math.floor(baseStock / 24));
  return baseStock;
});

const computedInlineSku = computed(() => {
  if (!activeProduct.value) return '';
  const baseSku = activeProduct.value.sku || 'PRD';
  const valParts = Object.values(selectedAttributes.value).map(v => 
    String(v).replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 8)
  ).filter(Boolean);
  
  return valParts.length > 0 ? `${baseSku}-${valParts.join('-')}` : baseSku;
});

const addInlineVariationToCart = () => {
  if (!activeProduct.value) return;
  const prod = activeProduct.value;
  const attrValues = Object.values(selectedAttributes.value).filter(Boolean);
  const variantLabel = attrValues.length > 0 ? attrValues.join(' / ') : 'Default';
  const price = computedInlinePrice.value;
  const qty = inlineQty.value;
  
  handleAddToCart(prod, variantLabel, qty, price);
  toast.success(`Added ${prod.name} (${variantLabel}) to Order Summary`);
};

const selectedPacks = ref({});
const productQuantities = ref({});
const openPackDropdownId = ref(null);

const currentOrderNo = ref('A6-' + String(Math.floor(1000 + Math.random() * 9000)));
const customerPhone = ref('');
const customerTypeMode = ref('walk_in'); // 'walk_in' | 'regular'
const customerSearchText = ref('');
const isCustomerDropdownOpen = ref(false);
const customerSearchWrapperRef = ref(null);
const selectedCustomer = ref('Walk-in Customer');
const selectedRegularCustomerObj = ref(null);

const customerFilteredList = computed(() => {
  const allCusts = customersStore.customers || [];
  const q = (customerSearchText.value || '').trim().toLowerCase();
  
  if (!q) {
    // Show top 6 regular customers when search is empty
    return allCusts.slice(0, 6);
  }

  const qCleanPhone = q.replace(/[\s\-+]/g, '');
  
  // Filter by customer name or phone number
  const matches = allCusts.filter(c => {
    const nameStr = (c.name || '').toLowerCase();
    const phoneStr = String(c.phone || '').replace(/[\s\-+]/g, '');
    const altPhoneStr = String(c.secondary_phone || '').replace(/[\s\-+]/g, '');
    return nameStr.includes(q) || phoneStr.includes(qCleanPhone) || altPhoneStr.includes(qCleanPhone);
  });

  // Sort matches by relevance (starts with query ranks first)
  matches.sort((a, b) => {
    const aName = (a.name || '').toLowerCase();
    const bName = (b.name || '').toLowerCase();
    const aPhone = String(a.phone || '').replace(/[\s\-+]/g, '');
    const bPhone = String(b.phone || '').replace(/[\s\-+]/g, '');

    const aPrefix = aName.startsWith(q) || aPhone.startsWith(qCleanPhone);
    const bPrefix = bName.startsWith(q) || bPhone.startsWith(qCleanPhone);

    if (aPrefix && !bPrefix) return -1;
    if (!aPrefix && bPrefix) return 1;
    return aName.localeCompare(bName);
  });

  // Return exactly 6 results at a time (as requested: "list of six at a time should be shown, no more, no less")
  return matches.slice(0, 6);
});

const onCustomerSearchInput = () => {
  isCustomerDropdownOpen.value = true;
};

const selectRegularCustomer = (cust) => {
  selectedCustomer.value = cust.name;
  selectedRegularCustomerObj.value = cust;
  customerSearchText.value = `${cust.name} ${cust.phone || ''}`.trim();
  isCustomerDropdownOpen.value = false;
};

const onCustomerTypeChange = () => {
  if (customerTypeMode.value === 'walk_in') {
    selectedCustomer.value = 'Walk-in Customer';
    selectedRegularCustomerObj.value = null;
    customerSearchText.value = '';
    isCustomerDropdownOpen.value = false;
    if (paymentMethod.value === 'Khata') {
      paymentMethod.value = 'Cash';
    }
    splitTenders.value.khata = 0;
  } else {
    // Switching to regular
    isCustomerDropdownOpen.value = true;
    if (selectedRegularCustomerObj.value) {
      selectedCustomer.value = selectedRegularCustomerObj.value.name;
    } else {
      selectedCustomer.value = '';
    }
  }
};

const clearCustomerSelection = () => {
  customerSearchText.value = '';
  selectedRegularCustomerObj.value = null;
  selectedCustomer.value = '';
  isCustomerDropdownOpen.value = true;
  if (paymentMethod.value === 'Khata') {
    paymentMethod.value = 'Cash';
  }
  splitTenders.value.khata = 0;
};

const resolveProductImage = (img, name) => {
  if (img && typeof img === 'string' && img.trim() !== '') {
    const trimmed = img.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:') || trimmed.startsWith('blob:')) {
      return trimmed;
    }
    if (trimmed.startsWith('/demo/')) {
      return trimmed;
    }
    if (trimmed.startsWith('/storage/')) {
      return `http://localhost:8000${trimmed}`;
    }
    if (trimmed.startsWith('storage/')) {
      return `http://localhost:8000/${trimmed}`;
    }
    if (trimmed.startsWith('/')) {
      return trimmed;
    }
    return `http://localhost:8000/storage/${trimmed}`;
  }
  return '/demo/products/prod_01.webp';
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
  if (n.includes('water') || n.includes('bottle') || n.includes('pepsi') || n.includes('coke') || n.includes('cola') || n.includes('drink') || n.includes('beverage') || n.includes('juice') || n.includes('soda') || n.includes('sprite')) {
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

const handleRemoveItem = (cartItemId) => {
  store.removeFromCart(cartItemId);
};

const promptCustomerPhone = () => {
  const phone = prompt('Enter customer phone number:', customerPhone.value);
  if (phone !== null) {
    customerPhone.value = phone;
    toast.success('Customer phone attached to order.');
  }
};
const paymentMethod = ref('Cash');
const showInvoiceModal = ref(false);
const currentInvoice = ref(null);
const isProcessing = ref(false);

const currentTime = ref('');
const currentDate = ref('');

const handleClickOutside = (e) => {
  if (!e.target.closest('.custom-pack-select')) {
    openPackDropdownId.value = null;
  }
};

const isLoadingProducts = ref(true);
const isSessionChecking = ref(true);

const hydratePos = async () => {
    isLoadingProducts.value = true;
    isSessionChecking.value = true;
    const branchId = authStore.user?.branch_id || undefined;
    await Promise.allSettled([
        store.fetchCategories(),
        store.fetchProducts().finally(() => { isLoadingProducts.value = false; }),
        customersStore.fetchCustomers(),
        store.fetchActiveRegisterSession(branchId).finally(() => { isSessionChecking.value = false; })
    ]);
    store.loadHeldCarts();
};

onMounted(async () => {
    setInterval(() => {
        const now = new Date();
        currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        currentDate.value = now.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    }, 1000);

    document.addEventListener('click', handleClickOutside);

    await hydratePos();

    if (!route.query.tab) {
        router.replace({ path: '/POS', query: { ...route.query, tab: 'sales' } });
    }
});

watch(() => route.path, async (newPath) => {
    if (newPath === '/POS' || newPath === '/pos') {
        await hydratePos();
    }
});

// Cash Register Session UI State
const showDayOpenModal = ref(false);
const dayOpenFloat = ref(0);
const dayOpenNotes = ref('');
const showDayCloseModal = ref(false);
const dayCloseCounted = ref(0);
const dayCloseVarianceReason = ref('');
const dayCloseNotes = ref('');
const dayCloseReasonRequired = ref(false);
const dayCloseIsSubmitting = ref(false);

const isOwnerOrManager = computed(() => {
    const u = authStore.user;
    if (!u) return false;
    return u.role === 'Business Owner' || u.role === 'Branch Manager' || u.roles?.some(r => r.name === 'Business Owner' || r.name === 'Branch Manager');
});

const openDayOpenModal = () => {
    dayOpenFloat.value = 0;
    dayOpenNotes.value = '';
    showDayOpenModal.value = true;
};

const closeDayOpenModal = () => {
    showDayOpenModal.value = false;
};

const submitDayOpen = async () => {
    if (dayOpenFloat.value === null || dayOpenFloat.value === undefined || dayOpenFloat.value < 0) {
        toast.error("Please enter a valid non-negative opening float.");
        return;
    }
    dayCloseIsSubmitting.value = true;
    try {
        await store.openRegisterSession({
            branch_id: authStore.user?.branch_id || undefined,
            opening_float: dayOpenFloat.value,
            notes: dayOpenNotes.value,
        });
        toast.success("Cash register session opened successfully!");
        closeDayOpenModal();
    } catch (e) {
        const msg = e.response?.data?.errors?.opening_float?.[0] || e.response?.data?.errors?.session?.[0] || e.response?.data?.message || e.message;
        toast.error(msg);
    } finally {
        dayCloseIsSubmitting.value = false;
    }
};

const openDayCloseModal = () => {
    dayCloseCounted.value = 0;
    dayCloseVarianceReason.value = '';
    dayCloseNotes.value = '';
    dayCloseReasonRequired.value = false;
    showDayCloseModal.value = true;
};

const closeDayCloseModal = () => {
    showDayCloseModal.value = false;
};

const submitDayClose = async () => {
    if (dayCloseCounted.value === null || dayCloseCounted.value === undefined || dayCloseCounted.value < 0) {
        toast.error("Please enter a valid non-negative counted cash amount.");
        return;
    }
    if (dayCloseReasonRequired.value && !dayCloseVarianceReason.value.trim()) {
        toast.error("Variance reason is required to close the cash drawer.");
        return;
    }
    dayCloseIsSubmitting.value = true;
    try {
        await store.closeRegisterSession({
            session_id: store.activeRegisterSession?.id,
            closing_cash_counted: dayCloseCounted.value,
            variance_reason: dayCloseVarianceReason.value.trim() || undefined,
            notes: dayCloseNotes.value.trim() || undefined,
            branch_id: authStore.user?.branch_id,
        });
        toast.success("Cash register session closed successfully!");
        closeDayCloseModal();
    } catch (e) {
        if (e.response?.data?.errors?.reason_required || e.response?.data?.errors?.variance_reason) {
            dayCloseReasonRequired.value = true;
            const msg = e.response?.data?.errors?.variance_reason?.[0] || "Discrepancy detected. A variance reason is required.";
            toast.warning(msg);
        } else {
            const msg = e.response?.data?.message || e.message;
            toast.error(msg);
        }
    } finally {
        dayCloseIsSubmitting.value = false;
    }
};

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(() => [route.query.category, route.query.search, route.query.product, route.query.pack], ([newCat, newSearch, newProd, newPack]) => {
  if (newCat) {
    selectedCategory.value = newCat;
  } else {
    selectedCategory.value = 'All';
  }
  if (newSearch !== undefined) {
    searchQuery.value = newSearch;
  }
  if (newProd) {
    selectedProductId.value = newProd;
    if (newPack) {
      selectedPacks.value[newProd] = newPack;
    }
  }
}, { immediate: true });

const getAvailablePacks = (product) => {
  const basePrice = Number(product.price) || 0;
  const unit = (product.unit || '').toLowerCase();

  if (unit.includes('kg') || unit.includes('kilo')) {
    return [
      { name: '1 KG', price: basePrice },
      { name: '5 KG', price: basePrice * 5 },
      { name: '10 KG', price: basePrice * 10 }
    ];
  }

  if (unit.includes('liter') || unit.includes('litre') || unit === 'l') {
    return [
      { name: '1 Litre', price: basePrice },
      { name: '5 Litre', price: basePrice * 5 },
      { name: 'Carton (12L)', price: basePrice * 12 }
    ];
  }

  if (unit.includes('gram') || unit === 'g' || unit === 'gm') {
    return [
      { name: '250 Gram', price: Math.round(basePrice * 0.25) },
      { name: '500 Gram', price: Math.round(basePrice * 0.5) },
      { name: '1 KG', price: basePrice }
    ];
  }

  return [
    { name: 'Single Pack', price: basePrice },
    { name: 'Box', price: basePrice * 6 },
    { name: 'Carton', price: basePrice * 24 }
  ];
};

const getSelectedPack = (productId) => {
  const pId = String(productId);
  for (const key of Object.keys(selectedPacks.value)) {
    if (String(key) === pId) {
      return selectedPacks.value[key];
    }
  }
  const prod = store.products.find(p => String(p.id) === pId);
  if (prod) {
    const packs = getAvailablePacks(prod);
    return packs[0].name;
  }
  return 'Single Pack';
};

const getSelectedPackPrice = (product) => {
  const selected = getSelectedPack(product.id);
  const packs = getAvailablePacks(product);
  const found = packs.find(p => p.name === selected);
  return found ? found.price : (Number(product.price) || 0);
};

const togglePackDropdown = (productId) => {
  if (openPackDropdownId.value === productId) {
    openPackDropdownId.value = null;
  } else {
    openPackDropdownId.value = productId;
  }
};

const setProductPack = (productId, packName) => {
  const pId = String(productId);
  selectedPacks.value[pId] = packName;
  openPackDropdownId.value = null;
  selectedProductId.value = productId;
  
  // Instantly synchronize with cart if this product is already in the cart
  const product = store.products.find(p => String(p.id) === pId);
  if (product) {
    const packs = getAvailablePacks(product);
    const found = packs.find(p => p.name === packName);
    const newPrice = found ? found.price : Number(product.price || 0);
    
    const existingCartItem = store.cart.find(item => String(item.productId || item.id).split('_')[0] === pId);
    if (existingCartItem) {
      existingCartItem.cartItemId = `${pId}_${packName.replace(/\s+/g, '_')}`;
      existingCartItem.packType = packName;
      existingCartItem.price = newPrice;
    }
  }

  const q = { ...route.query, product: productId, pack: packName };
  router.push({ path: '/POS', query: q });
};

const getProductQty = (productId) => {
  return productQuantities.value[productId] || 1;
};

const incrementProductQty = (productId, maxStock) => {
  const current = getProductQty(productId);
  if (!maxStock || current < maxStock) {
    productQuantities.value[productId] = current + 1;
  }
};

const decrementProductQty = (productId) => {
  const current = getProductQty(productId);
  if (current > 1) {
    productQuantities.value[productId] = current - 1;
  }
};

const selectCategory = (cat) => {
  const catVal = cat === 'All Products' ? 'All' : cat;
  selectedCategory.value = catVal;
  const q = { ...route.query };
  if (catVal !== 'All') {
    q.category = catVal;
  } else {
    delete q.category;
  }
  router.push({ path: '/POS', query: q });
};

let searchDebounce = null;
const onSearchInput = () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    const q = { ...route.query };
    if (searchQuery.value && searchQuery.value.trim()) {
      q.search = searchQuery.value.trim();
    } else {
      delete q.search;
    }
    router.replace({ path: '/POS', query: q });
  }, 250);
};

const handleBarcodeScan = (scannedQuery) => {
  const query = String(scannedQuery || '').trim();
  if (!query) return;

  const qLower = query.toLowerCase();

  // 1. Check exact primary barcode match
  let targetProduct = store.products.find(p => String(p.barcode || '').trim().toLowerCase() === qLower);
  let multiplier = 1;
  let packLabel = '';

  // 2. Check exact SKU match
  if (!targetProduct) {
    targetProduct = store.products.find(p => String(p.sku || '').trim().toLowerCase() === qLower);
  }

  // 3. Check alternate barcodes (with canonical quantity multipliers)
  if (!targetProduct) {
    for (const p of store.products) {
      if (Array.isArray(p.alternateBarcodes)) {
        const matchedAlt = p.alternateBarcodes.find(b => {
          const val = typeof b === 'object' && b !== null ? b.barcode : b;
          return String(val || '').trim().toLowerCase() === qLower;
        });
        if (matchedAlt) {
          targetProduct = p;
          if (typeof matchedAlt === 'object' && matchedAlt !== null) {
            multiplier = Number(matchedAlt.multiplier) || 1;
            packLabel = matchedAlt.label || `Master Carton (${multiplier} units)`;
          }
          break;
        }
      }
    }
  }

  if (targetProduct) {
    const qtyToAdd = multiplier;
    const result = store.addToCart(targetProduct, qtyToAdd, packLabel || 'Single Pack');
    if (result && result.success) {
      toast.success(multiplier > 1 
        ? `Scanned: Added ${packLabel} (${multiplier} units of ${targetProduct.name}) to cart` 
        : `Scanned: Added ${targetProduct.name} to cart`);
      searchQuery.value = '';
      const q = { ...route.query };
      delete q.search;
      router.replace({ path: '/POS', query: q });
    } else {
      toast.warning(result?.message || 'Could not add product to cart');
    }
  }
};

const handleScanButtonClick = () => {
  const code = window.prompt('Scan or enter barcode / SKU:');
  if (code) {
    handleBarcodeScan(code);
  }
};

const categories = computed(() => {
  const cats = ['All Products'];
  const seen = new Set(['all products', 'all']);

  // 1. Categories from backend categories table
  if (store.categories && Array.isArray(store.categories)) {
    store.categories.forEach(c => {
      const name = (typeof c === 'string' ? c : (c?.name || '')).trim();
      if (name && !seen.has(name.toLowerCase())) {
        seen.add(name.toLowerCase());
        cats.push(name);
      }
    });
  }

  // 2. Categories from existing products
  if (store.products && Array.isArray(store.products)) {
    store.products.forEach(p => {
      let name = '';
      if (p.category) {
        name = typeof p.category === 'object' ? (p.category.name || '') : String(p.category);
      }
      name = name.trim();
      if (name && !seen.has(name.toLowerCase())) {
        seen.add(name.toLowerCase());
        cats.push(name);
      }
    });
  }

  return cats;
});

const filteredProducts = computed(() => {
  const selected = (selectedCategory.value || 'All').trim();
  const search = (searchQuery.value || '').trim().toLowerCase();

  return store.products.filter(p => {
    // Exclude inactive products from POS retail catalog
    if (p.active === false || p.is_active === false) {
      return false;
    }

    // 1. Category Filtering
    let matchesCategory = false;
    if (selected === 'All' || selected === 'All Products' || selected === '') {
      matchesCategory = true;
    } else {
      const selLower = selected.toLowerCase();
      
      // Get product category name
      let prodCatName = '';
      if (p.category) {
        prodCatName = typeof p.category === 'object' ? (p.category.name || '') : String(p.category);
      }
      prodCatName = prodCatName.trim().toLowerCase();

      // Exact or singular/plural name match
      if (prodCatName && (prodCatName === selLower || prodCatName.replace(/s$/, '') === selLower.replace(/s$/, ''))) {
        matchesCategory = true;
      }

      // Category ID match via store.categories
      if (!matchesCategory && store.categories && Array.isArray(store.categories) && p.categoryId) {
        const foundCat = store.categories.find(c => {
          const cName = (typeof c === 'string' ? c : (c?.name || '')).trim().toLowerCase();
          return cName === selLower || cName.replace(/s$/, '') === selLower.replace(/s$/, '');
        });

        if (foundCat && Number(p.categoryId) === Number(foundCat.id)) {
          matchesCategory = true;
        }
      }
    }

    // 2. Search Filtering (Name, SKU, Primary Barcode, Alternate Barcodes)
    let matchesSearch = true;
    if (search) {
      const pName = (p.name || '').toLowerCase();
      const pSku = (p.sku || '').toLowerCase();
      const pBarcode = (p.barcode || '').toLowerCase();
      const altMatch = Array.isArray(p.alternateBarcodes) && p.alternateBarcodes.some(b => {
        const val = typeof b === 'object' && b !== null ? b.barcode : b;
        return String(val || '').toLowerCase().includes(search);
      });
      matchesSearch = pName.includes(search) || pSku.includes(search) || pBarcode.includes(search) || altMatch;
    }

    return matchesCategory && matchesSearch;
  });
});

const cartItemCount = computed(() => {
  return store.cart.reduce((sum, item) => sum + item.quantity, 0);
});


const handleSelectProduct = (product) => {
  if (product.active === false || product.is_active === false) {
    toast.error('Product is inactive and cannot be sold.');
    return;
  }

  const variant = getMatchedVariant(product);
  
  // If variant exists and its stock is 0, block add to cart
  if (product.hasVariations && variant && Number(variant.stock) <= 0) {
    toast.error('Selected variation is out of stock.');
    return;
  }
  if (!product.hasVariations && Number(product.stock) <= 0) {
    toast.error('Product is out of stock.');
    return;
  }

  const variantLabel = getCardVariantLabel(product);
  const price = getCardPrice(product);
  const qty = getProductQty(product.id) || 1;
  const sku = getCardSku(product);
  
  const result = store.addToCart(product, variantLabel, qty, price);
  if (!result.success) {
    toast.error(result.message);
  } else {
    // Optionally update the sku in the cart item if store doesn't handle it
    const lastItem = store.cart[store.cart.length - 1];
    if (lastItem && String(lastItem.id) === String(product.id)) {
        lastItem.sku = sku;
    }
  }
  productQuantities.value[product.id] = 1; // reset qty after adding
};


const handleAddToCart = (product, packType = 'Single Pack', qty = 1, customPrice = null) => {
  if (product.stock === 0) return;
  const result = store.addToCart(product, packType, qty, customPrice);
  if (!result.success) {
    toast.error(result.message);
  }
};

const isProductInCart = (id, packType = 'Single Pack') => {
  const targetId = String(id);
  const targetPack = packType || 'Single Pack';
  const cartItemId = `${targetId}_${targetPack.replace(/\s+/g, '_')}`;
  return store.cart.some(item => String(item.cartItemId || item.id) === cartItemId || (String(item.id) === targetId && item.packType === targetPack));
};

const handleUpdateQuantity = (cartItemId, newQty) => {
  const result = store.updateCartQuantity(cartItemId, newQty);
  if (!result.success) {
    toast.error(result.message);
  }
};

const formatInvoiceDate = (dateVal) => {
  if (!dateVal) {
    const now = new Date();
    return now.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    });
  }
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) {
    const now = new Date();
    return now.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    });
  }
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
};

const generateBarcodeSvg = (code) => {
  const clean = String(code || '22738712').replace(/[^0-9A-Za-z]/g, '') || '22738712';
  let bars = '';
  let x = 6;
  const barPattern = [2, 1, 3, 1, 1, 2, 2, 3, 1, 2, 1, 3, 2, 1, 2, 1, 3, 1, 2, 2, 1, 3, 1, 2, 2, 1, 1, 3, 2, 1];
  for (let i = 0; i < clean.length; i++) {
    const charCode = clean.charCodeAt(i);
    const p1 = barPattern[(charCode + i) % barPattern.length];
    const p2 = barPattern[(charCode * 2 + i) % barPattern.length];
    const p3 = barPattern[(charCode * 3 + i) % barPattern.length];
    
    bars += `<rect x="${x}" y="0" width="${p1 * 1.4}" height="42" fill="#000000" />`;
    x += (p1 * 1.4) + 1.8;
    bars += `<rect x="${x}" y="0" width="${p2 * 1.4}" height="42" fill="#000000" />`;
    x += (p2 * 1.4) + 1.8;
    bars += `<rect x="${x}" y="0" width="${p3 * 1.4}" height="42" fill="#000000" />`;
    x += (p3 * 1.4) + 2.2;
  }
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${x + 6} 42" width="100%" height="38" preserveAspectRatio="none">${bars}</svg>`;
};

const payableTotal = computed(() => {
  return Math.max(0, store.cartSubtotal - store.cartDiscount + Math.round(store.cartSubtotal * 0.1));
});

const isWalkInCustomer = computed(() => {
  if (customerTypeMode.value === 'walk_in') return true;
  return !selectedCustomer.value || selectedCustomer.value === 'Walk-in Customer' || selectedCustomer.value.trim() === '';
});

const selectPaymentMethod = (method) => {
  if (method === 'Khata' && isWalkInCustomer.value) {
    toast.warning("Khata (Pay Later) is only available for Regular Customers. Please select or add a Regular Customer first.");
    return;
  }
  paymentMethod.value = method;
};

const isElevatedUser = computed(() => {
  const r = (authStore.user?.role || '').toLowerCase();
  return r.includes('owner') || r.includes('admin') || r.includes('manager');
});

// Paper size (SRT-GAP-011: DEVICE_LOCAL scope)
const paperSize = ref(localStorage.getItem('hbos_receipt_paper_size') || '80mm');
const setPaperSize = (size) => {
  paperSize.value = size;
  localStorage.setItem('hbos_receipt_paper_size', size);
};

// Canonical Business Receipt Return Policy (SRT-GAP-011: BUSINESS_RECEIPT_SETTINGS)
const receiptReturnPolicy = ref('');
const loadReceiptSettings = async () => {
  try {
    const res = await api.get('/settings');
    const data = res.data || {};
    receiptReturnPolicy.value = data.receipt_settings?.return_policy || data.receipt_footer || '';
  } catch (e) {
    console.error('Failed to load canonical receipt settings:', e);
  }
};

const isNavigatorOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

const handleDocumentClick = (e) => {
  if (customerSearchWrapperRef.value && !customerSearchWrapperRef.value.contains(e.target)) {
    isCustomerDropdownOpen.value = false;
  }
};

onMounted(() => {
  loadReceiptSettings();
  if (typeof window !== 'undefined') {
    window.addEventListener('online', () => { isNavigatorOnline.value = true; });
    window.addEventListener('offline', () => { isNavigatorOnline.value = false; });
    document.addEventListener('click', handleDocumentClick);
  }
});

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    document.removeEventListener('click', handleDocumentClick);
  }
});

// -------------------------------------------------------------------------
// SRT-GAP-009: QUICK ADD CUSTOMER
// -------------------------------------------------------------------------
const showQuickCustomerModal = ref(false);
const isSavingCustomer = ref(false);
const quickCustomer = ref({
  name: '',
  phone: '',
  secondary_phone: '',
  address: ''
});

// Full Pakistani Phone Normalization (03XXXXXXXXX / +923XXXXXXXXX canonical comparison)
function normalizePakistaniPhone(phone) {
  if (!phone) return '';
  const digits = String(phone).trim().replace(/[^0-9]/g, '');
  if (digits.startsWith('923') && digits.length === 12) {
    return '03' + digits.slice(2);
  }
  if (digits.startsWith('03') && digits.length === 11) {
    return digits;
  }
  if (digits.startsWith('3') && digits.length === 10) {
    return '0' + digits;
  }
  return digits;
}

const matchingDuplicateCustomer = computed(() => {
  const normInput = normalizePakistaniPhone(quickCustomer.value.phone);
  if (!normInput || normInput.length < 10) return null;
  return customersStore.customers?.find(c => {
    const normExisting = normalizePakistaniPhone(c.phone);
    return normExisting && normExisting === normInput;
  }) || null;
});

const isQuickCustomerValid = computed(() => {
  return (quickCustomer.value.name || '').trim().length >= 2 &&
         (quickCustomer.value.phone || '').trim().length >= 7;
});

const openQuickCustomerModal = () => {
  quickCustomer.value = {
    name: '',
    phone: '',
    secondary_phone: '',
    address: ''
  };
  showQuickCustomerModal.value = true;
};

const closeQuickCustomerModal = () => {
  showQuickCustomerModal.value = false;
};

const selectExistingCustomer = (cust) => {
  customerTypeMode.value = 'regular';
  selectRegularCustomer(cust);
  closeQuickCustomerModal();
  toast.success(`Selected customer ${cust.name}`);
};

const submitQuickCustomer = async () => {
  if (!isQuickCustomerValid.value) return;
  isSavingCustomer.value = true;
  try {
    const payload = {
      name: quickCustomer.value.name.trim(),
      phone: quickCustomer.value.phone.trim(),
      secondary_phone: quickCustomer.value.secondary_phone?.trim() || null,
      address: quickCustomer.value.address?.trim() || null
    };

    const res = await customersStore.addCustomer(payload);
    if (res.success) {
      toast.success(`Customer "${payload.name}" added successfully!`);
      customerTypeMode.value = 'regular';
      selectedCustomer.value = payload.name;
      const createdObj = res.customer || res.data || { id: res.id, ...payload };
      selectedRegularCustomerObj.value = createdObj;
      customerSearchText.value = `${payload.name} ${payload.phone || ''}`.trim();
      isCustomerDropdownOpen.value = false;
      closeQuickCustomerModal();
    } else {
      toast.error(res.message || 'Failed to add customer.');
    }
  } catch (err) {
    console.error('Error creating customer:', err);
    toast.error('Network error creating customer.');
  } finally {
    isSavingCustomer.value = false;
  }
};

// -------------------------------------------------------------------------
// SRT-GAP-007: CART HOLD & MULTI-CART BUFFER (LOCALSTORAGE)
// -------------------------------------------------------------------------
const showHeldCartsModal = ref(false);
const showResumeCollisionModal = ref(false);
const pendingResumeId = ref(null);

const openHeldCartsModal = () => {
  store.loadHeldCarts();
  showHeldCartsModal.value = true;
};

const closeHeldCartsModal = () => {
  showHeldCartsModal.value = false;
};

const handleHoldCart = () => {
  if (store.cart.length === 0) {
    toast.error('Cannot hold an empty cart.');
    return;
  }
  const note = prompt('Optional hold note (e.g. Customer stepped out, Table 4):', '') || '';
  const result = store.holdCart(note, null, selectedCustomer.value);
  if (result.success) {
    toast.success(`Cart held successfully (#${result.held_id})`);
    customerTypeMode.value = 'walk_in';
    selectedCustomer.value = 'Walk-in Customer';
    selectedRegularCustomerObj.value = null;
    customerSearchText.value = '';
    isCustomerDropdownOpen.value = false;
  } else {
    toast.error(result.message);
  }
};

const handleResumeCartClick = (heldId) => {
  if (store.cart.length > 0) {
    pendingResumeId.value = heldId;
    showResumeCollisionModal.value = true;
    return;
  }
  executeResumeCart(heldId);
};

const executeResumeCart = (heldId) => {
  const result = store.resumeCart(heldId);
  if (result.success) {
    if (result.record?.customer_name && result.record.customer_name !== 'Walk-in Customer') {
      customerTypeMode.value = 'regular';
      selectedCustomer.value = result.record.customer_name;
      const found = customersStore.customers?.find(c => c.name === result.record.customer_name);
      if (found) {
        selectedRegularCustomerObj.value = found;
        customerSearchText.value = `${found.name} ${found.phone || ''}`.trim();
      } else {
        customerSearchText.value = result.record.customer_name;
      }
    } else {
      customerTypeMode.value = 'walk_in';
      selectedCustomer.value = 'Walk-in Customer';
      selectedRegularCustomerObj.value = null;
      customerSearchText.value = '';
    }
    toast.success('Held cart resumed successfully.');
    closeHeldCartsModal();
  } else {
    toast.error(result.message);
  }
};

const resolveCollisionHoldFirst = () => {
  const note = 'Auto-held prior to resume';
  store.holdCart(note, null, selectedCustomer.value);
  showResumeCollisionModal.value = false;
  if (pendingResumeId.value) {
    executeResumeCart(pendingResumeId.value);
    pendingResumeId.value = null;
  }
};

const resolveCollisionOverwrite = () => {
  store.cart = [];
  store.cartDiscount = 0;
  showResumeCollisionModal.value = false;
  if (pendingResumeId.value) {
    executeResumeCart(pendingResumeId.value);
    pendingResumeId.value = null;
  }
};

const handleDiscardCart = (heldId) => {
  if (confirm('Are you sure you want to discard this held cart?')) {
    store.discardCart(heldId);
    toast.info('Held cart discarded.');
  }
};

// -------------------------------------------------------------------------
// SRT-GAP-008: PRICE OVERRIDE WORKFLOW
// -------------------------------------------------------------------------
const showPriceOverrideModal = ref(false);
const overrideTargetItem = ref(null);
const overrideForm = ref({
  new_price: 0,
  reason: '',
  approver_email: '',
  approver_password: ''
});
const pendingOverrideAuth = ref(null);

const promptPriceOverride = (item) => {
  overrideTargetItem.value = item;
  overrideForm.value = {
    new_price: item.price,
    reason: '',
    approver_email: '',
    approver_password: ''
  };
  showPriceOverrideModal.value = true;
};

const closePriceOverrideModal = () => {
  overrideForm.value.approver_password = '';
  showPriceOverrideModal.value = false;
};

const isOverrideFormValid = computed(() => {
  if (overrideForm.value.new_price < 0) return false;
  if (!isElevatedUser.value) {
    return !!(overrideForm.value.approver_email && overrideForm.value.approver_password);
  }
  return true;
});

const submitPriceOverride = () => {
  if (!overrideTargetItem.value) return;
  const itemId = overrideTargetItem.value.cartItemId || overrideTargetItem.value.id;
  const newPrice = Number(overrideForm.value.new_price);
  
  if (!isElevatedUser.value) {
    pendingOverrideAuth.value = {
      approver_email: overrideForm.value.approver_email,
      approver_password: overrideForm.value.approver_password,
      reason: overrideForm.value.reason || 'Cashier counter override'
    };
  } else {
    pendingOverrideAuth.value = null;
  }

  store.overrideItemPrice(itemId, newPrice);
  overrideForm.value.approver_password = '';
  showPriceOverrideModal.value = false;
  toast.success(`Unit price updated to Rs ${newPrice.toLocaleString('en-PK')}`);
};

// -------------------------------------------------------------------------
// SRT-GAP-006: SPLIT MULTI-TENDER MODAL & CHANGE CALCULATOR
// -------------------------------------------------------------------------
const showSplitModal = ref(false);
const splitTenders = ref({
  cash_applied: 0,
  cash_tendered: 0,
  online: 0,
  khata: 0
});

const totalPaidTenders = computed(() => {
  return Number(splitTenders.value.cash_applied || 0) + Number(splitTenders.value.online || 0);
});

const totalTenderSettled = computed(() => {
  return totalPaidTenders.value + Number(splitTenders.value.khata || 0);
});

const isSplitBalanced = computed(() => {
  return Math.abs(totalTenderSettled.value - payableTotal.value) < 1;
});

const cashChange = computed(() => {
  return Math.max(0, Number(splitTenders.value.cash_tendered || 0) - Number(splitTenders.value.cash_applied || 0));
});

const canSubmitSplit = computed(() => {
  if (!isSplitBalanced.value) return false;
  if (Number(splitTenders.value.cash_applied || 0) < 0 ||
      Number(splitTenders.value.online || 0) < 0 ||
      Number(splitTenders.value.khata || 0) < 0) {
    return false;
  }
  if (Number(splitTenders.value.khata || 0) > 0 && isWalkInCustomer.value) {
    return false;
  }
  return true;
});

const openSplitPaymentModal = () => {
  paymentMethod.value = 'Split';
  splitTenders.value = {
    cash_applied: payableTotal.value,
    cash_tendered: payableTotal.value,
    online: 0,
    khata: 0
  };
  showSplitModal.value = true;
};

const closeSplitPaymentModal = () => {
  showSplitModal.value = false;
};

const fillRemainingOnline = () => {
  const remaining = Math.max(0, payableTotal.value - Number(splitTenders.value.cash_applied || 0) - Number(splitTenders.value.khata || 0));
  splitTenders.value.online = remaining;
};

const fillRemainingKhata = () => {
  if (isWalkInCustomer.value) return;
  const remaining = Math.max(0, payableTotal.value - Number(splitTenders.value.cash_applied || 0) - Number(splitTenders.value.online || 0));
  splitTenders.value.khata = remaining;
};

const submitSplitCheckout = async () => {
  if (!canSubmitSplit.value) return;
  isProcessing.value = true;

  const payments = [];
  if (Number(splitTenders.value.cash_applied || 0) > 0) {
    payments.push({
      payment_method: 'Cash',
      amount: Number(splitTenders.value.cash_applied)
    });
  }
  if (Number(splitTenders.value.online || 0) > 0) {
    payments.push({
      payment_method: 'Online',
      amount: Number(splitTenders.value.online)
    });
  }
  if (Number(splitTenders.value.khata || 0) > 0) {
    payments.push({
      payment_method: 'Khata',
      amount: Number(splitTenders.value.khata)
    });
  }

  const paidAmount = Number(splitTenders.value.cash_applied || 0) + Number(splitTenders.value.online || 0);
  
  let resolvedCustId = null;
  if (!isWalkInCustomer.value) {
    if (selectedRegularCustomerObj.value?.id) {
      resolvedCustId = selectedRegularCustomerObj.value.id;
    } else if (customersStore.customers) {
      const found = customersStore.customers.find(c => c.name === selectedCustomer.value || String(c.id) === String(selectedCustomer.value));
      if (found) resolvedCustId = found.id;
    }
  }

  const options = {
    payment_method: 'Split',
    customerId: resolvedCustId,
    customerName: selectedCustomer.value,
    payments: payments,
    paid_amount: paidAmount,
    override_authorization: pendingOverrideAuth.value || undefined
  };

  const result = await store.completeSale('Split', resolvedCustId, selectedCustomer.value, options);
  isProcessing.value = false;

  pendingOverrideAuth.value = null;

  if (result.success) {
    closeSplitPaymentModal();
    currentInvoice.value = result.invoice;
    showInvoiceModal.value = true;
    toast.success('Split tender sale completed successfully!');
  } else {
    toast.error(result.message || 'Split tender sale failed.');
  }
};

const handleConfirmOrderClick = () => {
  if (paymentMethod.value === 'Split') {
    openSplitPaymentModal();
  } else if (paymentMethod.value === 'Khata' && isWalkInCustomer.value) {
    toast.warning("Khata (Pay Later) is only available for Regular Customers. Please select or add a Regular Customer.");
  } else {
    handleCompleteSale();
  }
};

const handleCompleteSale = async () => {
  if (store.cart.length === 0) {
      toast.error("Please add at least one product to the cart.");
      return;
  }
  if (paymentMethod.value === 'Khata' && isWalkInCustomer.value) {
      toast.warning("Khata (Pay Later) is only available for Regular Customers. Please select or add a Regular Customer.");
      return;
  }
  isProcessing.value = true;
  
  let resolvedCustId = null;
  if (!isWalkInCustomer.value) {
    if (selectedRegularCustomerObj.value?.id) {
      resolvedCustId = selectedRegularCustomerObj.value.id;
    } else if (customersStore.customers) {
      const found = customersStore.customers.find(c => c.name === selectedCustomer.value || String(c.id) === String(selectedCustomer.value));
      if (found) resolvedCustId = found.id;
    }
  }

  const isKhata = paymentMethod.value === 'Khata';
  const options = {
    payment_method: paymentMethod.value,
    customerId: resolvedCustId,
    customerName: selectedCustomer.value,
    paid_amount: isKhata ? 0 : payableTotal.value,
    payments: [{ payment_method: paymentMethod.value, amount: payableTotal.value }],
    override_authorization: pendingOverrideAuth.value || undefined
  };

  const result = await store.completeSale(paymentMethod.value, resolvedCustId, selectedCustomer.value, options);
  isProcessing.value = false;
  
  pendingOverrideAuth.value = null;

  if (result.success) {
    currentInvoice.value = result.invoice;
    showInvoiceModal.value = true;
    toast.success("Sale completed successfully!");
  } else {
    toast.error(result.message);
  }
};

const handleCloseInvoiceModal = () => {
    showInvoiceModal.value = false;
    currentInvoice.value = null;
    store.cart = [];
    store.cartDiscount = 0;
    customerTypeMode.value = 'walk_in';
    selectedCustomer.value = 'Walk-in Customer';
    selectedRegularCustomerObj.value = null;
    customerSearchText.value = '';
    isCustomerDropdownOpen.value = false;
    paymentMethod.value = 'Cash';
    selectedProductId.value = null;
    openPackDropdownId.value = null;
    router.push({ path: '/POS', query: { tab: 'sales' } });
};

const startNewSale = () => {
    handleCloseInvoiceModal();
};

const printInvoice = () => {
  const invoiceEl = document.getElementById('invoiceArea');
  if (!invoiceEl) {
    window.print();
    return;
  }

  const printFrame = document.createElement('iframe');
  printFrame.style.position = 'fixed';
  printFrame.style.right = '0';
  printFrame.style.bottom = '0';
  printFrame.style.width = '0';
  printFrame.style.height = '0';
  printFrame.style.border = '0';
  document.body.appendChild(printFrame);

  const doc = printFrame.contentWindow.document;
  doc.open();
  doc.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title>Receipt - ${currentInvoice.value?.invoiceNumber || 'HBOS'}</title>
        <style>
          @page {
            size: auto;
            margin: 0mm;
          }
          * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          body {
            background: #ffffff;
            color: #000000;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 10px;
          }
          .invoice {
            width: 290px;
            max-width: 100%;
            background: #ffffff;
            padding: 12px 10px;
            margin: 0 auto;
            color: #000000;
          }
          .invoice-header {
            text-align: center;
            margin-bottom: 18px;
          }
          .invoice-header h2 {
            font-size: 16px;
            font-weight: 800;
            margin: 0 0 4px;
            text-transform: uppercase;
            color: #000000;
            letter-spacing: 0.5px;
          }
          .invoice-header p {
            font-size: 11.5px;
            color: #333333;
            margin: 0;
            line-height: 1.4;
          }
          .invoice-meta {
            margin-bottom: 16px;
            font-size: 11.5px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            color: #000000;
          }
          .invoice-meta div {
            display: flex;
            justify-content: space-between;
          }
          .invoice-meta span:first-child {
            color: #555555;
          }
          .invoice-meta span:last-child {
            font-weight: 600;
            color: #000000;
          }
          .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11.5px;
          }
          .invoice-table th {
            padding: 6px 0;
            border-top: 1px dashed #777777;
            border-bottom: 1px dashed #777777;
            text-align: left;
            font-weight: 700;
            color: #000000;
          }
          .invoice-table td {
            padding: 6px 0;
            vertical-align: top;
            color: #000000;
          }
          .invoice-table .text-end {
            text-align: right;
          }
          .invoice-item-sub {
            font-size: 10px;
            color: #666666;
          }
          .invoice-totals {
            border-top: 1px dashed #777777;
            padding-top: 12px;
            margin-bottom: 18px;
          }
          .invoice-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            margin-bottom: 6px;
            color: #000000;
          }
          .invoice-grand-total {
            font-size: 15px;
            font-weight: 800;
            margin-top: 8px;
            border-top: 1px dashed #777777;
            padding-top: 8px;
            color: #000000;
          }
          .invoice-line {
            border-top: 1px dashed #777777;
            margin-bottom: 14px;
          }
          .thank-you {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #000000;
          }
          .barcode-svg-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 6px auto;
            width: 82%;
          }
          .barcode-svg-wrap svg {
            width: 100%;
            height: 38px;
          }
          .invoice-number {
            text-align: center;
            font-size: 11px;
            letter-spacing: 3px;
            font-weight: 600;
            color: #000000;
          }
        
/* Smart Customer Search Combobox */
.customer-search-input-wrapper {
    position: relative;
    width: 100%;
}

.search-customer-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 13px;
}

.clear-customer-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 14px;
    cursor: pointer;
    padding: 2px;
}
.clear-customer-btn:hover {
    color: #ef4444;
}

.customer-dropdown-menu {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    max-height: 250px;
    overflow-y: auto;
    z-index: 1050;
    padding: 6px;
}

.customer-dropdown-item {
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s;
}

.customer-dropdown-item:hover {
    background: #f1f5f9;
}

</style>
      </head>
      <body>
        ${invoiceEl.outerHTML}
      </body>
    </html>
  `);
  doc.close();

  setTimeout(() => {
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();
    setTimeout(() => {
      if (document.body.contains(printFrame)) {
        document.body.removeChild(printFrame);
      }
    }, 1500);
  }, 250);
};

const isGeneratingImage = ref(false);

const shareWhatsApp = async () => {
  if (!currentInvoice.value) return;
  const inv = currentInvoice.value;
  const invoiceEl = document.getElementById('invoiceArea');
  if (!invoiceEl) {
    toast.error('Invoice not found.');
    return;
  }

  isGeneratingImage.value = true;
  toast.info('Generating invoice image for WhatsApp...');

  try {
    // Render only the invoice element with high-res scale (2.5x)
    const canvas = await html2canvas(invoiceEl, {
      scale: 2.5,
      backgroundColor: '#ffffff',
      useCORS: true,
      logging: false,
      allowTaint: true,
      scrollX: 0,
      scrollY: 0
    });

    const invNum = inv.invoiceNumber || 'INV';
    const cleanNum = invNum.replace(/[^0-9A-Za-z]/g, '');
    const fileName = `HBOS-Invoice-${cleanNum}.png`;

    canvas.toBlob(async (blob) => {
      if (!blob) {
        toast.error('Failed to generate invoice image.');
        isGeneratingImage.value = false;
        return;
      }

      const file = new File([blob], fileName, { type: 'image/png' });

      // 1. Try Native Web Share API with File (Mobile devices & supported browsers)
      if (navigator.canShare && navigator.canShare({ files: [file] })) {
        try {
          await navigator.share({
            files: [file],
            title: `${storeDisplayName.value} Invoice - ${invNum}`,
            text: `Invoice from ${storeDisplayName.value} (${invNum})`
          });
          toast.success('Invoice image shared to WhatsApp!');
          isGeneratingImage.value = false;
          return;
        } catch (shareErr) {
          if (shareErr.name === 'AbortError') {
            isGeneratingImage.value = false;
            return;
          }
          console.warn('Native share error, switching to clipboard/download fallback:', shareErr);
        }
      }

      // 2. Desktop Fallback: Copy image to clipboard for instant Ctrl+V into WhatsApp Web/App
      try {
        if (navigator.clipboard && window.ClipboardItem) {
          await navigator.clipboard.write([
            new ClipboardItem({ 'image/png': blob })
          ]);
        }
      } catch (clipErr) {
        console.warn('Clipboard write fallback error:', clipErr);
      }

      // Automatically trigger file download of the crisp invoice image
      const downloadUrl = URL.createObjectURL(blob);
      const downloadLink = document.createElement('a');
      downloadLink.href = downloadUrl;
      downloadLink.download = fileName;
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);

      // Open WhatsApp chat
      const phone = customerPhone.value ? customerPhone.value.replace(/[^0-9]/g, '') : '';
      const waUrl = phone ? `https://wa.me/${phone}` : `https://web.whatsapp.com`;
      window.open(waUrl, '_blank');

      toast.success('Invoice image copied & downloaded! Paste (Ctrl+V) directly into WhatsApp.', { timeout: 6000 });
      isGeneratingImage.value = false;
    }, 'image/png', 1.0);

  } catch (err) {
    console.error('Error generating WhatsApp image:', err);
    toast.error('Failed to generate invoice image.');
    isGeneratingImage.value = false;
  }
};
</script>

<style scoped>
/* Phase-4 Modals and Thermal Print Styling */
.pos-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 16px;
}
.pos-modal-card {
    background: #ffffff;
    border-radius: 14px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}
.pos-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}
.pos-modal-body {
    padding: 20px;
    overflow-y: auto;
}
.pos-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.thermal-58mm {
    width: 220px !important;
    font-size: 10px !important;
}
.thermal-80mm {
    width: 290px !important;
}

/* Inject Original Legacy CSS from Pos-Sale.html */
:root { --primary: #2447c6; --primary-dark: #1939a8; --primary-light: #e9efff; --text: #171a24; --muted: #697386; --border: #d8deea; --background: #f6f8fc; --white: #ffffff; --success: #0b9f68; --danger: #d52d2d; --warning: #c77b00; --sidebar-width: 280px; }
* { box-sizing: border-box; }
.pos-app { font-family: Inter, sans-serif; background: #fff; color: var(--text); overflow: hidden; height: calc(100vh - 65px); display: flex; flex-direction: column; }
button, input, select { font-family: inherit; }
.topbar { height: 64px; min-height: 64px; border-bottom: 1px solid #d9dde4; display: flex; align-items: center; justify-content: space-between; padding: 0 16px 0 25px; background: #fff; }
.page-title { font-size: 25px; font-weight: 700; color: #05070a; margin: 0; }
.header-right { display: flex; align-items: center; gap: 22px; }
.date-time { text-align: right; line-height: 1.15; }
.time { font-size: 14px; font-weight: 600; }
.date { font-size: 12px; color: #4d535d; margin-top: 4px; }
.header-divider { width: 1px; height: 34px; background: #d3d7de; }
.cashier { text-align: right; line-height: 1.15; }
.cashier-name { font-size: 14px; font-weight: 600; }
.cashier-role { font-size: 12px; color: #4d535d; margin-top: 4px; }
.avatar { width: 38px; height: 38px; border-radius: 50%; background: #2447c6; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; letter-spacing: .5px; }
.mobile-menu-btn { display: none; background: 0 0; border: none; font-size: 20px; color: #4b515d; cursor: pointer; padding: 0; margin-right: 15px; }
.pos-area { display: flex; flex: 1; min-height: 0; overflow: hidden; background: #eef1f6; }
.products-panel { flex: 1; min-width: 0; display: flex; flex-direction: column; padding: 16px 8px; overflow-y: auto; overflow-x: hidden; }
.search-box { position: relative; height: 53px; margin-bottom: 15px; flex: 0 0 53px; border: 3px solid #2447c6; border-radius: 26px; overflow: hidden; background: #fff; display: flex; align-items: center; transition: box-shadow .2s ease; }
.search-box:focus-within { box-shadow: 0 0 0 4px rgba(36, 71, 198, .15); }
.search-icon { padding-left: 20px; color: #171a24; font-size: 17px; }
.search-input { flex: 1; height: 100%; border: none; background: 0 0; padding: 0 15px; font-size: 16px; color: #171a24; outline: 0; }
.scan-btn { width: 55px; height: 100%; border: none; background: 0 0; color: #4d535d; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: color .2s ease; }
.scan-btn:hover { color: #2447c6; }
.categories { display: flex; flex: 0 0 auto; flex-shrink: 0; gap: 8px; overflow-x: auto; overflow-y: hidden; padding: 2px 2px 10px; margin-bottom: 15px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.categories::-webkit-scrollbar { display: none; }
.category { flex: 0 0 auto; height: 38px; padding: 0 18px; border: 1px solid #cbd1da; border-radius: 22px; background: #f3f4f6; color: #40454d; cursor: pointer; font-size: 14px; font-weight: 600; white-space: nowrap; transition: all 0.15s ease; user-select: none; }
.category:hover { background: #e9ebee; border-color: #94a3b8; }
.category.active { background: #000; color: #fff; border-color: #000; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15); }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px 10px; align-items: stretch; }
.product-card { min-width: 0; min-height: 350px; background: #f8f9fb; border: 1px solid #cbd1da; border-radius: 13px; padding: 14px 12px; display: flex; flex-direction: column; overflow: hidden; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
.product-card:hover { box-shadow: 0 3px 10px rgba(0, 0, 0, .07); }
.product-card.low-stock { border-color: #ff5f67; }
.product-image-wrap { position: relative; width: 100%; height: 160px; flex: 0 0 160px; border-radius: 10px; overflow: hidden; background: #e8ebef; margin-bottom: 12px; }
.product-image { width: 100%; height: 100%; object-fit: cover; display: block; }
.stock-badge { position: absolute; top: 7px; right: 7px; min-height: 29px; padding: 3px 10px; border-radius: 20px; background: rgba(255, 255, 255, .95); color: #040404; font-size: 12px; font-weight: 600; display: flex; align-items: center; box-shadow: 0 2px 5px rgba(0, 0, 0, .1); }
.stock-badge.danger { background: #ff5f67; color: #fff; }
.product-name { font-size: 17px; font-weight: 700; color: #000; margin: 0 0 6px; line-height: 1.4; height: 46px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.product-sku { font-size: 13px; color: #787e87; margin-bottom: auto; height: 25px; line-height: 25px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.product-bottom { margin-top: auto; padding-top: 5px; }
.product-price { font-size: 23px; font-weight: 800; color: #000; margin-bottom: 12px; line-height: 1; }
.add-cart-btn { width: 100%; height: 46px; border: none; background: #f3f5f8; color: #2447c6; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all .2s ease; }
.add-cart-btn:hover:not(:disabled) { background: #2447c6; color: #fff; }
.add-cart-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.add-cart-btn.added { background: #009b60; color: #fff; }
.cart-panel { width: 25%; min-width: 280px; flex: 0 0 25%; background: #fff; border-left: 1px solid #d3d7de; display: flex; flex-direction: column; overflow: hidden; }
.cart-top { padding: 10px 12px; border-bottom: 1px solid #e2e6eb; background: #fff; flex: 0 0 auto; }
.customer-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.customer-title { font-size: 12px; font-weight: 700; color: #767c85; letter-spacing: .5px; }
.new-customer { border: none; background: 0 0; color: #2447c6; font-size: 13px; font-weight: 600; cursor: pointer; padding: 0; }
.new-customer:hover { text-decoration: underline; }
.customer-select { width: 100%; height: 42px; border: 2px solid #e1e4e9; border-radius: 9px; padding: 0 12px; font-size: 14px; color: #171a24; font-weight: 600; background-color: #fff; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23131313%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 17px top 50%; background-size: 11px auto; }
.customer-select:focus { border-color: #2447c6; outline: 0; }
.cart-items { flex: 1; overflow-y: auto; overflow-x: hidden; background: #fff; }
.empty-cart { height: 100%; display: flex; align-items: center; justify-content: center; text-align: center; color: #858b94; padding: 30px; }
.empty-cart-icon { font-size: 45px; color: #c9ced6; margin-bottom: 12px; }
.empty-cart p { font-size: 15px; margin: 0; line-height: 1.4; }
.cart-item { padding: 10px 12px; border-bottom: 1px solid #eaedf1; }
.cart-item-top { display: flex; justify-content: space-between; margin-bottom: 12px; }
.cart-item-name { font-size: 14px; font-weight: 700; color: #080808; margin-bottom: 2px; }
.cart-item-unit { font-size: 13px; color: #767c85; }
.cart-item-price { font-size: 14px; font-weight: 700; color: #080808; }
.quantity-control { display: inline-flex; align-items: center; border: 1px solid #d6dae0; border-radius: 6px; height: 32px; overflow: hidden; }
.qty-btn { width: 38px; height: 100%; border: none; background: #f8f9fb; color: #434850; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover { background: #ebedf1; color: #000; }
.qty-value { width: 36px; text-align: center; font-size: 14px; font-weight: 600; color: #000; border-left: 1px solid #d6dae0; border-right: 1px solid #d6dae0; height: 100%; display: flex; align-items: center; justify-content: center; background: #fff; }
.cart-summary { flex: 0 0 auto; background: #f4f6f9; border-top: 1px solid #e1e5eb; padding: 12px 12px; }
.summary-row { display: flex; justify-content: space-between; font-size: 14px; color: #444a53; font-weight: 600; margin-bottom: 8px; }
.discount-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #c4c9d1; }
.discount-input { width: 110px; height: 32px; border: 1px solid #c8ccd3; border-radius: 6px; padding: 0 8px; font-size: 13px; background: #fff; }
.discount-input:focus { border-color: #2447c6; outline: 0; }
.discount-row span { font-size: 15px; color: #de2424; font-weight: 600; }
.total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.total-label { font-size: 18px; font-weight: 800; color: #080808; }
.total-value { font-size: 22px; font-weight: 900; color: #2447c6; }
.payment-methods { display: flex; gap: 6px; margin-bottom: 14px; }
.payment-btn { flex: 1; height: 55px; border: 2px solid #dce0e6; border-radius: 8px; background: #fff; color: #444a53; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; transition: all .15s ease; }
.payment-btn i { font-size: 18px; }
.payment-btn:hover { border-color: #2447c6; color: #2447c6; background: #f7f9ff; }
.payment-btn.active { border-color: #2447c6; background: #2447c6; color: #fff; }
.complete-sale { width: 100%; height: 54px; border: none; border-radius: 10px; background: #2447c6; color: #fff; font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s ease; }
.complete-sale:hover:not(:disabled) { background: #1939a8; }
.complete-sale:disabled { opacity: 0.5; cursor: not-allowed; }

/* SUCCESS MODAL / OVERLAY */
.success-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #eef1f6; z-index: 1000; overflow-y: auto; opacity: 0; visibility: hidden; transition: all .3s ease; display: block; }
.success-overlay.show { opacity: 1; visibility: visible; }
.success-container { max-width: 950px; margin: 40px auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 0, 0, .08); display: grid; grid-template-columns: 1fr 400px; overflow: hidden; min-height: 600px; }
.success-left { padding: 50px; display: flex; flex-direction: column; justify-content: center; }
.success-icon { width: 80px; height: 80px; background: #e0faec; color: #0b9f68; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 25px; }
.success-title { font-size: 36px; font-weight: 800; color: #05070a; margin: 0 0 10px; line-height: 1.2; }
.success-description { font-size: 18px; color: #5a616b; margin-bottom: 35px; line-height: 1.5; }
.sale-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 40px; }
.sale-info-card { background: #f6f8fb; padding: 18px 20px; border-radius: 12px; display: flex; flex-direction: column; gap: 5px; }
.sale-info-card span { font-size: 13px; color: #6e7682; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.sale-info-card strong { font-size: 17px; color: #05070a; font-weight: 700; word-break: break-all; }
.sale-info-card.highlight { background: #f0f4ff; }
.sale-info-card.highlight strong { color: #2447c6; font-size: 20px; }
.action-buttons { display: flex; flex-direction: column; gap: 12px; }
.action-btn { width: 100%; height: 54px; border: 2px solid #e1e4e9; background: #fff; color: #171a24; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all .2s ease; }
.action-btn:hover { background: #f6f8fb; border-color: #cbd1da; }
.action-btn.new-sale { background: #2447c6; border-color: #2447c6; color: #fff; margin-top: 10px; }
.action-btn.new-sale:hover { background: #1939a8; border-color: #1939a8; }
.success-right { background: #f9fafc; border-left: 1px solid #eaedf2; padding: 40px 30px; display: flex; justify-content: center; }
.invoice { width: 100%; max-width: 320px; background: #fff; padding: 30px 25px; border-radius: 4px; box-shadow: 0 4px 15px rgba(0, 0, 0, .05); position: relative; }
.invoice::before, .invoice::after { content: ""; position: absolute; left: 0; right: 0; height: 8px; background-size: 16px 16px; }
.invoice::before { top: -4px; background-image: radial-gradient(circle at 8px 0, transparent 8px, #fff 9px); }
.invoice::after { bottom: -4px; background-image: radial-gradient(circle at 8px 16px, transparent 8px, #fff 9px); }
.invoice-header { text-align: center; margin-bottom: 25px; }
.invoice-header h2 { font-size: 18px; font-weight: 800; margin: 0 0 5px; text-transform: uppercase; }
.invoice-header p { font-size: 12px; color: #5a616b; margin: 0; line-height: 1.4; }
.invoice-meta { margin-bottom: 25px; font-size: 12px; color: #171a24; display: flex; flex-direction: column; gap: 6px; }
.invoice-meta div { display: flex; justify-content: space-between; }
.invoice-meta span:first-child { color: #5a616b; }
.invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
.invoice-table th { padding: 8px 0; border-top: 1px dashed #cbd1da; border-bottom: 1px dashed #cbd1da; text-align: left; font-weight: 600; color: #171a24; }
.invoice-table td { padding: 8px 0; vertical-align: top; color: #171a24; }
.invoice-table .text-end { text-align: right; }
.invoice-totals { border-top: 1px dashed #cbd1da; padding-top: 15px; margin-bottom: 25px; }
.invoice-total-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; color: #171a24; }
.invoice-grand-total { font-size: 16px; font-weight: 800; margin-top: 12px; border-top: 1px dashed #cbd1da; padding-top: 12px; }
.thank-you { text-align: center; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
.barcode { height: 40px; background: repeating-linear-gradient(90deg, #000, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 5px, #fff 5px, #fff 8px, #000 8px, #000 12px, #fff 12px, #fff 14px); margin: 0 20px 10px; }
.barcode-svg-wrap { display: flex; justify-content: center; align-items: center; margin: 0 15px 8px; }
.invoice-item-sub { font-size: 10px; color: #64748b; font-weight: 500; }
.invoice-number { text-align: center; font-size: 11px; letter-spacing: 3px; color: #171a24; }

@media print {
    body * { visibility: hidden; }
    .invoice, .invoice * { visibility: visible; }
    .invoice { position: absolute; left: 0; top: 0; box-shadow: none; border: none; margin: 0; padding: 0; width: 100%; max-width: none; }
    .invoice::before, .invoice::after { display: none; }
    .no-print { display: none !important; }
}

/* RESPONSIVE LAYOUT */
@media (max-width: 1200px) {
    .product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .cart-panel { width: 32%; flex: 0 0 32%; }
}

@media (max-width: 992px) {
    .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cart-panel { width: 35%; flex: 0 0 35%; }
    .success-container { grid-template-columns: 1fr; margin: 20px; min-height: auto; }
    .success-right { display: none; } /* Hide right side invoice on small screens, users can click print */
}

@media (max-width: 768px) {
    .pos-area { flex-direction: column; overflow-y: auto; }
    .products-panel, .cart-panel { width: 100%; max-width: 100%; flex: none; overflow: visible; }
    .products-panel { min-height: 60vh; }
    .cart-panel { border-left: none; border-top: 2px solid #d3d7de; }
    .header-right { display: none; } /* Hide some header elements */
}

@media (max-width: 576px) {
    .product-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
}

.product-price-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.single-pack-tag {
  font-size: 11px;
  font-weight: 600;
  color: #1d4ed8;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
}


/* Pack Selection Styling */
.pack-selector-wrap {
  margin: 8px 0 10px 0;
}

.pack-option-pill {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 10px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}

.pack-option-pill:hover {
  border-color: #93c5fd;
  background: #eff6ff;
}

.pack-option-pill.selected {
  border-color: #2563eb;
  background: #eff6ff;
}

.pack-option-left {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pack-check-icon {
  color: #2563eb;
  font-size: 13px;
}

.pack-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #1e293b;
}

.pack-price {
  font-size: 12.5px;
  font-weight: 700;
  color: #0f172a;
}

.cart-item-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 3px;
}

.cart-pack-badge {
  font-size: 10.5px;
  font-weight: 700;
  color: #1d4ed8;
  background: #dbeafe;
  padding: 2px 6px;
  border-radius: 4px;
}


/* Pack Type Dropdown & Modern Controls */
.pack-dropdown-container {
  margin: 8px 0 6px 0;
  position: relative;
}

.pack-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.pack-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.custom-pack-select {
  position: relative;
  width: 100%;
}

.pack-select-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12.5px;
  font-weight: 600;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.pack-select-btn:hover, .custom-pack-select.open .pack-select-btn {
  border-color: #2563eb;
  background: #eff6ff;
  color: #1d4ed8;
}

.dropdown-arrow {
  font-size: 10px;
  color: #64748b;
  transition: transform 0.2s ease;
}

.custom-pack-select.open .dropdown-arrow {
  transform: rotate(180deg);
  color: #1d4ed8;
}

.pack-dropdown-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #ffffff;
  border: 1.5px solid #bfdbfe;
  border-radius: 10px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  z-index: 100;
  overflow: hidden;
  animation: fadeIn 0.15s ease;
}

.pack-dropdown-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.12s ease;
  border-bottom: 1px solid #f1f5f9;
}

.pack-dropdown-item:last-child {
  border-bottom: none;
}

.pack-dropdown-item:hover {
  background: #f8fafc;
}

.pack-dropdown-item.active {
  background: #eff6ff;
  color: #1d4ed8;
  font-weight: 600;
}

.pack-item-name {
  font-weight: 600;
  color: #1e293b;
}

.pack-dropdown-item.active .pack-item-name {
  color: #1d4ed8;
}

.pack-item-price {
  font-weight: 700;
  color: #0f172a;
}

.pack-dropdown-item.active .pack-item-price {
  color: #1d4ed8;
}

.product-price-section {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin: 6px 0 10px 0;
}

.price-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.product-price {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

.product-actions-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: auto;
}

.card-qty-control {
  display: flex;
  align-items: center;
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  height: 38px;
  padding: 0 4px;
}

.card-qty-btn {
  background: none;
  border: none;
  width: 22px;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  color: #475569;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.1s ease;
}

.card-qty-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.card-qty-val {
  min-width: 22px;
  text-align: center;
  font-weight: 700;
  font-size: 13px;
  color: #0f172a;
}

.cart-item-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 3px;
}

.cart-pack-badge {
  font-size: 10.5px;
  font-weight: 700;
  color: #1d4ed8;
  background: #dbeafe;
  padding: 2px 6px;
  border-radius: 4px;
}


/* Modern Order's Summary Card Design */
.modern-order-summary-panel {
  width: 380px;
  min-width: 360px;
  background: #f8fafc;
  padding: 16px;
  overflow-y: auto;
  border-left: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
}

.order-summary-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 18px 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  border: 1px solid #eef2f6;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.order-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.order-summary-title {
  font-size: 19px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}

.order-expand-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.order-expand-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.order-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.order-no-text {
  font-size: 13.5px;
  color: #475569;
}

.order-status-badge {
  background: #fef3c7;
  color: #d97706;
  font-size: 11.5px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
}

.order-customer-select-wrap {
  margin-bottom: 14px;
}

.customer-select-modern {
  width: 100%;
  height: 38px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 0 12px;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  background: #f8fafc;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s ease;
}

.customer-select-modern:focus {
  border-color: #2563eb;
  background: #ffffff;
}

.total-items-header {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.items-count-badge {
  color: #94a3b8;
  font-weight: 600;
}

.order-cart-items {
  flex: 1;
  min-height: 140px;
  max-height: 260px;
  overflow-y: auto;
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-right: 4px;
}

.empty-cart-modern {
  text-align: center;
  padding: 25px 10px;
  color: #94a3b8;
}

.empty-cart-icon-wrap {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 10px auto;
  font-size: 20px;
  color: #cbd5e1;
}

.empty-cart-text {
  font-size: 12.5px;
  margin: 0;
}

.modern-cart-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  background: #ffffff;
  border: 1.5px solid #f1f5f9;
  border-radius: 14px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
  transition: all 0.15s ease;
}

.modern-cart-item:hover {
  border-color: #cbd5e1;
}

.cart-item-thumb {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  object-fit: cover;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  flex-shrink: 0;
}

.cart-item-info {
  flex: 1;
  min-width: 0;
}

.cart-item-title {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 2px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cart-item-variant {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
  margin-bottom: 2px;
}

.cart-item-cost {
  font-size: 13px;
  font-weight: 800;
  color: #2563eb;
}

.cart-item-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}

.cart-inline-qty {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  height: 24px;
  padding: 0 2px;
}

.cart-qty-mini-btn {
  background: none;
  border: none;
  width: 18px;
  height: 100%;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-qty-mini-btn:hover {
  color: #0f172a;
}

.cart-qty-mini-val {
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
  min-width: 14px;
  text-align: center;
}

.cart-item-delete-btn {
  background: none;
  border: none;
  color: #f97316;
  font-size: 13px;
  cursor: pointer;
  padding: 2px 4px;
  transition: color 0.1s ease;
}

.cart-item-delete-btn:hover {
  color: #ef4444;
}

/* Payment Pills */
.payment-pills-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
  margin-bottom: 14px;
}

.payment-pill-btn {
  height: 38px;
  padding: 0 4px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  color: #475569;
  font-size: 11.5px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.payment-pill-btn:hover:not(:disabled) {
  border-color: #93c5fd;
  background: #f8fafc;
}

.payment-pill-btn.active {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.payment-pill-btn.disabled,
.payment-pill-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed !important;
  background: #f1f5f9;
  border-color: #e2e8f0;
  color: #94a3b8;
  box-shadow: none !important;
}

.customer-dropdown-item {
  transition: background-color 0.12s ease;
}

.customer-dropdown-item:hover {
  background-color: #f1f5f9;
}

/* Order Breakdown Card */
.order-breakdown-card {
  background: #f8fafc;
  border-radius: 16px;
  padding: 14px;
  margin-bottom: 16px;
  border: 1px solid #f1f5f9;
}

.breakdown-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12.5px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 6px;
}

.breakdown-val {
  font-weight: 700;
  color: #1e293b;
}

.breakdown-divider-dashed {
  border-top: 1.5px dashed #cbd5e1;
  margin: 10px 0 8px 0;
}

.breakdown-line.total-line {
  margin-bottom: 0;
}

.total-title {
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}

.total-amount {
  font-size: 17px;
  font-weight: 900;
  color: #0f172a;
}

/* Bottom Action Buttons */
.order-bottom-actions {
  display: grid;
  grid-template-columns: 1fr 1.3fr 1.6fr;
  gap: 8px;
  margin-top: auto;
}

.order-cancel-btn {
  height: 42px;
  border: 1.5px solid #fecdd3;
  border-radius: 25px;
  background: #ffffff;
  color: #e11d48;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}

.order-cancel-btn:hover {
  background: #fff1f2;
}

.order-phone-btn {
  height: 42px;
  border: 1.5px solid #bfdbfe;
  border-radius: 25px;
  background: #ffffff;
  color: #2563eb;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.order-phone-btn:hover {
  background: #eff6ff;
}

.order-confirm-btn {
  height: 42px;
  border: none;
  border-radius: 25px;
  background: #16a34a;
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
  box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3);
}

.order-confirm-btn:hover {
  background: #15803d;
}

.order-confirm-btn:disabled {
  background: #94a3b8;
  box-shadow: none;
  cursor: not-allowed;
}


.discount-row-modern {
  margin: 6px 0;
}

.discount-input-container {
  display: flex;
  align-items: center;
  gap: 4px;
}

.discount-input-field {
  width: 90px;
  height: 30px;
  border: 1.5px solid #fecdd3;
  background: #ffffff;
  color: #e11d48;
  font-weight: 700;
  font-size: 13px;
  text-align: right;
  padding: 2px 8px;
  border-radius: 8px;
  outline: none;
  transition: all 0.15s ease;
}

.discount-input-field:focus {
  border-color: #f43f5e;
  box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.15);
}


.invoice-modal-close-btn {
  position: absolute;
  top: 18px;
  right: 18px;
  width: 44px;
  height: 44px;
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: #334155;
  cursor: pointer;
  z-index: 99999;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.invoice-modal-close-btn:hover {
  background: #fee2e2;
  color: #ef4444;
  border-color: #f87171;
  transform: scale(1.12);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.28);
}





/* DIRECT INLINE ATTRIBUTES & VARIATIONS FORM (ORIGINAL EXACT SIZE, CENTERED) */
.inline-variation-container {
  width: 100%;
  max-width: 680px;
  margin: 16px auto 22px;
  display: flex;
  justify-content: center;
  scroll-margin-top: 100px;
}
.variation-card-inline {
  background: #ffffff;
  width: 100%;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}
.variation-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.variation-modal-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}
.variation-modal-title {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}
.variation-modal-sub {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}
.variation-close-btn {
  background: transparent;
  border: none;
  font-size: 16px;
  color: #64748b;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}
.variation-close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}
.variation-modal-body {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 24px;
  padding: 20px;
}
.variation-left-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px 12px;
}
.variation-img-wrap {
  width: 130px;
  height: 130px;
  border-radius: 12px;
  overflow: hidden;
  background: #ffffff;
  margin-bottom: 12px;
  border: 1px solid #e2e8f0;
}
.variation-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.variation-prod-name {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 4px;
  text-transform: capitalize;
}
.variation-prod-sku {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}
.variation-right-col {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.variation-form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.variation-form-label {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  margin: 0;
}
.variation-select {
  height: 42px;
  padding: 0 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  background: #ffffff;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s ease;
}
.variation-select:focus {
  border-color: #2563eb;
}
.variation-price-stock-box {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.var-stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.var-stat-label {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
}
.var-stat-price {
  font-size: 18px;
  font-weight: 900;
  color: #2563eb;
}
.var-stat-stock {
  font-size: 13.5px;
  font-weight: 700;
  color: #166534;
}
.variation-qty-row {
  display: flex;
  align-items: center;
  padding-top: 2px;
}
.variation-action-buttons {
  display: flex;
  gap: 10px;
  margin-top: 4px;
}
.var-cancel-btn {
  flex: 1;
  height: 44px;
  border: 1.5px solid #cbd5e1;
  background: #ffffff;
  color: #334155;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}
.var-cancel-btn:hover {
  background: #f1f5f9;
}
.var-add-btn {
  flex: 2;
  height: 44px;
  border: none;
  background: #2563eb;
  color: #ffffff;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s ease;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}
.var-add-btn:hover:not(:disabled) {
  background: #1d4ed8;
}
.var-add-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

</style>
