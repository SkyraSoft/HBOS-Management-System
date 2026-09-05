const fs = require('fs');

let pvCode = fs.readFileSync('src/views/ProductView.vue', 'utf8');

// Regex replace everything from <div class="col-md-6"> down to the closing div of that column that contains Product Image
// Actually, it's safer to just replace the whole inner block.
const toReplace = `<div class="col-md-6">
                    <div v-if="form.imageName" class="mt-2 text-success small">
                      <i class="bi bi-image me-1"></i>{{ form.imageName }}
                    </div>
                  </div>`;
const newBlock = `                  <div class="col-md-6">
                    <label class="pv-label">Product Image</label>
                    <div style="padding: 15px; border: 1px dashed #d1d5db; border-radius: 8px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 13px;">
                      <i class="bi bi-magic fs-4 mb-2 d-block" style="color: #2447c6;"></i>
                      Image automatically generated from Unsplash.
                    </div>
                  </div>`;
pvCode = pvCode.replace(toReplace, newBlock);

fs.writeFileSync('src/views/ProductView.vue', pvCode);
console.log('Fixed PV');
