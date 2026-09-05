const fs = require('fs');

// 1. Remove Image Upload UI from ProductView.vue
let pvCode = fs.readFileSync('src/views/ProductView.vue', 'utf8');

const uploadUI = `<div class="col-md-6">
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
                    </div>`;

// Replace it with an empty string or just a small note that it is automatic
const newUI = `<div class="col-md-6">
                      <label class="pv-label">Product Image</label>
                      <div style="padding: 15px; border: 1px dashed #d1d5db; border-radius: 8px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 13px;">
                        <i class="bi bi-magic fs-4 mb-2 d-block text-primary"></i>
                        Image will be automatically generated from Unsplash based on the product name.
                      </div>
                    </div>`;

if(pvCode.includes(uploadUI)) {
    pvCode = pvCode.replace(uploadUI, newUI);
} else {
    // If it doesn't strictly match, try to use regex to wipe it out
    console.log("Strict match failed for PV upload UI.");
}
fs.writeFileSync('src/views/ProductView.vue', pvCode);
console.log('Updated ProductView UI.');
