import fs from 'fs';
let content = fs.readFileSync('src/views/ProductView.vue', 'utf8');
const newStyle = `
<style>
.legacy-product-wrapper .card-custom {
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
  border-radius: 12px !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02) !important;
  background: #fff !important;
  padding: 10px !important;
}
.legacy-product-wrapper .product-table {
  border-collapse: separate !important;
  border-spacing: 0 !important;
}
.legacy-product-wrapper .product-table th {
  border-bottom: 2px solid #f1f5f9 !important;
  color: #64748b !important;
  font-weight: 600 !important;
  text-transform: uppercase !important;
  font-size: 0.75rem !important;
  letter-spacing: 0.5px !important;
}
.legacy-product-wrapper .product-table td {
  border-bottom: 1px solid #f8fafc !important;
  color: #334155 !important;
  vertical-align: middle !important;
}
.legacy-product-wrapper .btn-action {
  border-radius: 6px !important;
  border: 1px solid #e2e8f0 !important;
  background: white !important;
  color: #64748b !important;
  padding: 6px 10px !important;
  transition: all 0.2s !important;
}
.legacy-product-wrapper .btn-action:hover {
  background: #f8fafc !important;
  color: #0f172a !important;
}
.legacy-product-wrapper .badge {
  border-radius: 20px !important;
  padding: 6px 12px !important;
  font-weight: 500 !important;
}
.legacy-product-wrapper .product-name {
  font-weight: 600 !important;
  color: #1e293b !important;
}
.legacy-product-wrapper .product-category {
  color: #64748b !important;
  font-size: 0.85rem !important;
}
</style>
`;
content = content.replace(/<\/style>$/, '</style>' + newStyle);
fs.writeFileSync('src/views/ProductView.vue', content);
console.log('Appended styles');
