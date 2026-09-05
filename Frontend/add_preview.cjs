const fs = require('fs');
let code = fs.readFileSync('src/views/ProductView.vue', 'utf8');

// 1. Template replacement
const oldTemplate = `<label class="pv-image-upload">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <strong>Click to upload or drag and drop</strong>
                        <small>JPG, PNG up to 2MB</small>
                        <input type="file" accept="image/*" hidden @change="handleImageUpload">
                      </label>
                      <div v-if="form.imageName" class="mt-2 text-success small">
                        <i class="bi bi-image me-1"></i>{{ form.imageName }}
                      </div>`;

const newTemplate = `<label class="pv-image-upload" :style="form.imagePreview ? 'padding: 0; overflow: hidden; border-style: solid;' : ''">
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
                      </div>`;

code = code.replace(oldTemplate, newTemplate);

// 2. defaultForm replacement
const oldDefault = `    imageName: '',
    imageFile: null
  })`;

const newDefault = `    imageName: '',
    imageFile: null,
    imagePreview: ''
  })`;

code = code.replace(oldDefault, newDefault);

// 3. handleImageUpload replacement
const oldHandle = `    const file = event.target.files[0]
    if (file) {
      form.imageName = file.name
      form.imageFile = file
    }
  }`;

const newHandle = `    const file = event.target.files[0]
    if (file) {
      form.imageName = file.name
      form.imageFile = file
      if (form.imagePreview) URL.revokeObjectURL(form.imagePreview)
      form.imagePreview = URL.createObjectURL(file)
    }
  }`;

code = code.replace(oldHandle, newHandle);

fs.writeFileSync('src/views/ProductView.vue', code);
console.log('Done adding preview logic');
