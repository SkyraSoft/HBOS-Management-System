const fs = require('fs');

let pvCode = fs.readFileSync('src/views/ProductView.vue', 'utf8');

const toReplace = `                  <div class="col-md-6">
                      <label class="pv-label">Product Image</label>
                      <div style="padding: 15px; border: 1px dashed #d1d5db; border-radius: 8px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 13px;">
                        <i class="bi bi-magic fs-4 mb-2 d-block" style="color: #2447c6;"></i>
                      Image automatically generated from Unsplash.
                      </div>
                    </div>`;

const newBlock = `                  <div class="col-md-6">
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

pvCode = pvCode.replace(toReplace, newBlock);

fs.writeFileSync('src/views/ProductView.vue', pvCode);
console.log('Restored image upload UI');
