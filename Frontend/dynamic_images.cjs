const fs = require('fs');

// PosSaleView.vue
let posCode = fs.readFileSync('src/views/PosSaleView.vue', 'utf8');
posCode = posCode.replace(
    `<img class="product-image" :src="product.image || 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=500&q=80'" :alt="product.name" loading="lazy">`,
    `<img class="product-image" :src="product.image || \`https://source.unsplash.com/800x800/?\${encodeURIComponent(product.category || product.name)}\`" :alt="product.name" loading="lazy">`
);
fs.writeFileSync('src/views/PosSaleView.vue', posCode);

// ProductView.vue
let pvCode = fs.readFileSync('src/views/ProductView.vue', 'utf8');

// Replace table list image
const oldTableImg = `<img v-if="product.image" :src="product.image" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;" :alt="product.name" loading="lazy">
                            <div v-else style="width: 40px; height: 40px; border-radius: 6px; background: #e9efff; color: #2447c6; display: flex; align-items: center; justify-content: center;"><i class="bi bi-image"></i></div>`;

const newTableImg = `<img :src="product.image || \`https://source.unsplash.com/100x100/?\${encodeURIComponent(product.category || product.name)}\`" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;" :alt="product.name" loading="lazy">`;
pvCode = pvCode.replace(oldTableImg, newTableImg);

// Replace detail hero image
const oldHeroImg = `<img v-if="selectedProduct.image" :src="selectedProduct.image" style="width: 100%; height: 100%; object-fit: cover;" :alt="selectedProduct.name">
                    <i v-else class="bi bi-box-seam"></i>`;

const newHeroImg = `<img :src="selectedProduct.image || \`https://source.unsplash.com/800x800/?\${encodeURIComponent(selectedProduct.category || selectedProduct.name)}\`" style="width: 100%; height: 100%; object-fit: cover;" :alt="selectedProduct.name">`;
pvCode = pvCode.replace(oldHeroImg, newHeroImg);

fs.writeFileSync('src/views/ProductView.vue', pvCode);
console.log('Dynamic images added.');
