/**
 * HBOS Local-Only Image Storage Engine
 * Stores product thumbnails, category icons, and store logos 100% locally
 * in IndexedDB / client disk storage — zero cloud server upload bloat.
 */

import { putStoreItem, getStoreItems, deleteStoreItem } from './localDb.js';

export async function saveImageLocally(key, base64Data) {
  if (!key || !base64Data) return null;
  const record = {
    key: String(key),
    data: base64Data,
    updated_at: new Date().toISOString(),
  };
  await putStoreItem('images', record);
  return record;
}

export async function getImageLocally(key) {
  if (!key) return null;
  const items = await getStoreItems('images');
  const img = items.find((i) => i.key === String(key));
  return img ? img.data : null;
}

export async function removeImageLocally(key) {
  if (!key) return;
  await deleteStoreItem('images', String(key));
}
