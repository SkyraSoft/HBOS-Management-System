const fs = require('fs');

let content = fs.readFileSync('src/views/ProductView.vue', 'utf8');

const oldHandle = `function handleImageUpload(event) {
    const file = event.target.files[0]
    if (file) form.imageName = file.name
  }`;

const newHandle = `function handleImageUpload(event) {
    const file = event.target.files[0]
    if (file) {
      form.imageName = file.name
      form.imageFile = file
    }
  }`;

content = content.replace(oldHandle, newHandle);

const oldSave = `    const res = await store.addProduct({
      name: form.name.trim(),
      category: form.category,
      sku,
      description: form.description,
      cost: form.cost,
      price: form.price,
      stock: form.stock,
      minStock: form.minStock,
      maxStock: form.maxStock,
      unit: form.unit,
      active: form.active
    })`;

const newSave = `    const res = await store.addProduct({
      name: form.name.trim(),
      category: form.category,
      sku,
      description: form.description,
      cost: form.cost,
      price: form.price,
      stock: form.stock,
      minStock: form.minStock,
      maxStock: form.maxStock,
      unit: form.unit,
      active: form.active,
      imageFile: form.imageFile
    })`;

content = content.replace(oldSave, newSave);

const oldDefault = `    maxStock: 100,
    active: true,
    batchTracking: false,
    imageName: ''
  })`;

const newDefault = `    maxStock: 100,
    active: true,
    batchTracking: false,
    imageName: '',
    imageFile: null
  })`;

content = content.replace(oldDefault, newDefault);

fs.writeFileSync('src/views/ProductView.vue', content);
console.log('ProductView.vue updated');
