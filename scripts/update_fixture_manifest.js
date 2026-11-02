const fs = require('fs');
const path = require('path');

async function main() {
  const master = await import('../Frontend/src/services/demoCatalogMaster.js');

  const manifestPath = path.join(__dirname, '../docs/release/hbos_demo_fixture_manifest.json');
  let manifest = {};
  if (fs.existsSync(manifestPath)) {
    try {
      manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    } catch (e) {
      manifest = {};
    }
  }

  manifest.manifest_version = "2.0.0";
  manifest.created_at = "2026-11-01T12:00:00+05:00";
  manifest.currency = "PKR";

  // Single Store Template
  manifest.templates = manifest.templates || {};
  manifest.templates.single_store = {
    template_id: "demo_single",
    business_name: "Al-Madina General Store",
    city: "Karachi",
    locality: "Bahadurabad Commercial Area",
    owner_name: "Haji Mohammad Irfan",
    phone: "03001234500",
    store_type: "SINGLE_STORE_INDEPENDENT",
    opening_cash_float: 8500.00,
    cash_account: {
      id: "demo_acc_cash_01",
      name: "Main Cash Drawer",
      type: "cash",
      initial_balance: 8500.00
    },
    active_register_session: {
      id: "demo_session_001",
      status: "open",
      opening_float: 8500.00,
      cashier_name: "Haji Mohammad Irfan",
      opened_at: "2026-11-01T08:00:00+05:00"
    },
    catalog_summary: {
      total_brands: master.DEMO_BRANDS.length,
      total_categories: master.DEMO_CATEGORIES.length,
      total_subcategories: master.DEMO_SUBCATEGORIES.length,
      total_product_families: master.DEMO_PRODUCT_FAMILIES.length,
      total_products: master.DEMO_PRODUCTS.length,
      total_barcode_aliases: master.DEMO_BARCODE_ALIASES.length,
      categories: master.DEMO_CATEGORIES.map(c => c.name),
      brands: master.DEMO_BRANDS.map(b => b.name)
    },
    brands: master.DEMO_BRANDS,
    categories: master.DEMO_CATEGORIES,
    subcategories: master.DEMO_SUBCATEGORIES,
    product_families: master.DEMO_PRODUCT_FAMILIES,
    products: master.DEMO_PRODUCTS,
    barcode_aliases: master.DEMO_BARCODE_ALIASES,
    customers: master.DEMO_CUSTOMERS,
    initial_total_receivables: master.DEMO_CUSTOMERS.reduce((s, c) => s + (c.current_balance || 0), 0),
    suppliers: master.DEMO_SUPPLIERS,
    initial_total_payables: master.DEMO_SUPPLIERS.reduce((s, sup) => s + (sup.current_balance || 0), 0)
  };

  // Multi Store Template
  const branchInvs = master.generateBranchInventories(master.DEMO_PRODUCTS);

  manifest.templates.multi_store = {
    template_id: "demo_multi",
    business_name: "Al-Madina Supermarket Chain",
    city: "Karachi",
    owner_name: "Haji Mohammad Irfan",
    phone: "03001234500",
    store_type: "SMALL_MULTI_BRANCH",
    branches: [
      {
        id: 1,
        name: "Clifton Main Store",
        code: "BR-01",
        is_primary: true,
        address: "Block 5, Clifton, Karachi",
        cash_drawer_balance: 15000.00
      },
      {
        id: 2,
        name: "Saddar Express",
        code: "BR-02",
        is_primary: false,
        address: "Preedy Street, Saddar, Karachi",
        cash_drawer_balance: 8000.00
      },
      {
        id: 3,
        name: "Gulshan Outlet",
        code: "BR-03",
        is_primary: false,
        address: "Block 13-D, Gulshan-e-Iqbal, Karachi",
        cash_drawer_balance: 6500.00
      }
    ],
    catalog_summary: manifest.templates.single_store.catalog_summary,
    brands: master.DEMO_BRANDS,
    categories: master.DEMO_CATEGORIES,
    subcategories: master.DEMO_SUBCATEGORIES,
    product_families: master.DEMO_PRODUCT_FAMILIES,
    products: master.DEMO_PRODUCTS,
    barcode_aliases: master.DEMO_BARCODE_ALIASES,
    branch_inventories: branchInvs,
    customers: master.DEMO_CUSTOMERS,
    initial_total_receivables: master.DEMO_CUSTOMERS.reduce((s, c) => s + (c.current_balance || 0), 0),
    suppliers: master.DEMO_SUPPLIERS,
    initial_total_payables: master.DEMO_SUPPLIERS.reduce((s, sup) => s + (sup.current_balance || 0), 0),
    in_transit_transfers: [
      {
        transfer_id: "TRF-2026-001",
        source_branch_id: 1,
        source_branch_name: "Clifton Main Store",
        destination_branch_id: 2,
        destination_branch_name: "Saddar Express",
        status: "in_transit",
        items: [
          {
            product_id: 31,
            sku: "IDO-CAN-01L",
            product_name: "Indus Gold Canola Cooking Oil 1 Litre Pouch",
            quantity: 20
          },
          {
            product_id: 23,
            sku: "IDO-UHT-01L",
            product_name: "Indus Pure UHT Full Cream Milk 1 Litre",
            quantity: 30
          }
        ],
        dispatched_at: "2026-11-01T09:30:00+05:00"
      }
    ]
  };

  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2), 'utf8');
  console.log('Successfully updated hbos_demo_fixture_manifest.json with canonical master catalog.');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});

