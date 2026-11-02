/**
 * HBOS Fast In-Memory Indexed Search Engine
 * Provides instant substring matching, category filtering, and brand filtering
 * directly from locally cached products in IndexedDB.
 */

import { getStoreItems } from './localDb.js';

let cachedProducts = null;
let lastCacheTime = 0;

export async function loadProductCatalogCache(forceRefresh = false) {
  const now = Date.now();
  if (!forceRefresh && cachedProducts && now - lastCacheTime < 30000) {
    return cachedProducts;
  }
  cachedProducts = await getStoreItems('products');
  lastCacheTime = now;
  return cachedProducts;
}

export async function searchLocalProducts({ query = '', categoryId = null, brandId = null, limit = 50 }) {
  const products = await loadProductCatalogCache();
  const q = String(query).trim().toLowerCase();

  return products
    .filter((p) => {
      // 1. Category Filter
      if (categoryId && String(p.category_id) !== String(categoryId)) {
        return false;
      }
      // 2. Brand Filter
      if (brandId && String(p.brand_id) !== String(brandId)) {
        return false;
      }
      // 3. Search Query (Sub-string match on Name, SKU, Primary Barcode, or Alternate Barcodes)
      if (q.length > 0) {
        const nameMatch = p.name && p.name.toLowerCase().includes(q);
        const skuMatch = p.sku && p.sku.toLowerCase().includes(q);
        const barcodeMatch = p.barcode && p.barcode.toLowerCase().includes(q);
        const altBarcodeMatch = Array.isArray(p.alternate_barcodes) && p.alternate_barcodes.some(b => String(b).toLowerCase().includes(q));
        return nameMatch || skuMatch || barcodeMatch || altBarcodeMatch;
      }
      return true;
    })
    .slice(0, limit);
}

export async function findProductByBarcodeOffline(barcode) {
  const products = await loadProductCatalogCache();
  const bc = String(barcode).trim().toLowerCase();
  if (!bc) return null;

  // 1. Primary barcode match
  const primaryMatch = products.find(p => p.barcode && String(p.barcode).trim().toLowerCase() === bc);
  if (primaryMatch) return primaryMatch;

  // 2. Alternate barcode match
  const altMatch = products.find(p =>
    Array.isArray(p.alternate_barcodes) &&
    p.alternate_barcodes.some(b => String(b).trim().toLowerCase() === bc)
  );
  if (altMatch) return altMatch;

  // 3. SKU match fallback
  return products.find(p => p.sku && String(p.sku).trim().toLowerCase() === bc) || null;
}

export function invalidateSearchCache() {
  cachedProducts = null;
  lastCacheTime = 0;
}
