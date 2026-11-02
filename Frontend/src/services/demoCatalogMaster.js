/**
 * HBOS Master Demo Catalog
 * 
 * Strict Canonical Specifications:
 * - Exactly 4 Brands
 * - Exactly 8 Categories
 * - Exactly 16 Subcategories (2 per Category)
 * - Exactly 32 Named Product Families (2 per Subcategory)
 * - 44 Stockable SKU/Product records (all distinct physical pack sizes are independent records)
 * - 8 Canonical Barcode Aliases (carton/wholesale multipliers mapped to products)
 * - 100% Local Bundled Assets (no runtime external image URLs)
 */

export const DEMO_BRANDS = [
  {
    id: 1,
    name: 'DailyHarvest Foods',
    code: 'DH',
    description: 'Premium quality farm-direct basmati rice, pulses, stone-ground flours, and natural sweeteners.',
    image: '/demo/brands/brand_1.webp',
    status: 'active'
  },
  {
    id: 2,
    name: 'Khyber Tea & Spices',
    code: 'KTS',
    description: 'Heritage black and green teas, authentic aromatic recipe mixes, and hand-selected whole and ground spices.',
    image: '/demo/brands/brand_2.webp',
    status: 'active'
  },
  {
    id: 3,
    name: 'Indus Dairy & Oils',
    code: 'IDO',
    description: 'Wholesome UHT dairy milk, fresh table butter, pure banaspati ghee, and premium refined edible cooking oils.',
    image: '/demo/brands/brand_3.webp',
    status: 'active'
  },
  {
    id: 4,
    name: 'PakClean & Care',
    code: 'PCC',
    description: 'Household hygiene essentials, antibacterial beauty soaps, herbal hair care, and powerful laundry detergents.',
    image: '/demo/brands/brand_4.webp',
    status: 'active'
  }
];

export const DEMO_CATEGORIES = [
  {
    id: 1,
    name: 'Grains & Staples',
    code: 'CAT-GRN',
    description: 'Staple long-grain basmati rice and wholesome organic lentils and pulses.',
    image: '/demo/categories/cat_1.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 2,
    name: 'Flours & Baking',
    code: 'CAT-FLR',
    description: 'Traditional chakki whole wheat atta, super fine maida, and pure white sugars.',
    image: '/demo/categories/cat_2.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 3,
    name: 'Beverages & Refreshments',
    code: 'CAT-BEV',
    description: 'Rich amber black teas, fragrant green kahwa, syrups, and fruit juices.',
    image: '/demo/categories/cat_3.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 4,
    name: 'Dairy & Breakfast',
    code: 'CAT-DRY',
    description: 'Farm-pure UHT full cream milk, dairy cream, salted table butter, and spreads.',
    image: '/demo/categories/cat_4.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 5,
    name: 'Cooking Oils & Ghee',
    code: 'CAT-OIL',
    description: 'Aromatic pure banaspati ghee, desi cow ghee, canola, and sunflower cooking oils.',
    image: '/demo/categories/cat_5.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 6,
    name: 'Spices & Seasonings',
    code: 'CAT-SPC',
    description: 'Authentic Pakistani biryani and nihari recipe mixes, turmeric, and Kashmiri red chili.',
    image: '/demo/categories/cat_6.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 7,
    name: 'Personal Care & Hygiene',
    code: 'CAT-PC',
    description: 'Nourishing beauty soaps, antibacterial bars, and herbal anti-dandruff shampoos.',
    image: '/demo/categories/cat_7.webp',
    is_active: 1,
    status: 1
  },
  {
    id: 8,
    name: 'Laundry & Household Cleaning',
    code: 'CAT-HME',
    description: 'High-power washing powders, automatic machine detergents, dishwashing gels, and surface cleaners.',
    image: '/demo/categories/cat_8.webp',
    is_active: 1,
    status: 1
  }
];

export const DEMO_SUBCATEGORIES = [
  // Cat 1: Grains & Staples
  { id: 1, category_id: 1, name: 'Basmati Rice', code: 'SUB-RICE', description: 'Long-grain kernel and sella basmati rice', is_active: 1, status: 1 },
  { id: 2, category_id: 1, name: 'Pulses & Daal', code: 'SUB-DAAL', description: 'Chana, masoor, and moong pulses', is_active: 1, status: 1 },
  // Cat 2: Flours & Baking
  { id: 3, category_id: 2, name: 'Wheat Flour & Atta', code: 'SUB-ATTA', description: 'Chakki whole wheat atta and fine maida', is_active: 1, status: 1 },
  { id: 4, category_id: 2, name: 'Sugar & Sweeteners', code: 'SUB-SUG', description: 'Refined white crystal sugar and desi shakkar', is_active: 1, status: 1 },
  // Cat 3: Beverages & Refreshments
  { id: 5, category_id: 3, name: 'Black & Green Tea', code: 'SUB-TEA', description: 'Danedar black tea and royal green tea kahwa', is_active: 1, status: 1 },
  { id: 6, category_id: 3, name: 'Cold Drinks & Juices', code: 'SUB-JUC', description: 'Traditional rose syrup and fruit juices', is_active: 1, status: 1 },
  // Cat 4: Dairy & Breakfast
  { id: 7, category_id: 4, name: 'UHT Milk & Cream', code: 'SUB-MLK', description: 'Full cream homogenized milk and dairy cream', is_active: 1, status: 1 },
  { id: 8, category_id: 4, name: 'Butter & Spreads', code: 'SUB-BTR', description: 'Salted table butter and chocolate spreads', is_active: 1, status: 1 },
  // Cat 5: Cooking Oils & Ghee
  { id: 9, category_id: 5, name: 'Banaspati Ghee', code: 'SUB-GHEE', description: 'Vegetable banaspati and pure desi cow ghee', is_active: 1, status: 1 },
  { id: 10, category_id: 5, name: 'Edible Cooking Oils', code: 'SUB-OIL', description: 'Refined canola and sunflower cooking oils', is_active: 1, status: 1 },
  // Cat 6: Spices & Seasonings
  { id: 11, category_id: 6, name: 'Specialty Recipe Mixes', code: 'SUB-MIX', description: 'Biryani, Nihari, and Karahi recipe mixes', is_active: 1, status: 1 },
  { id: 12, category_id: 6, name: 'Ground & Whole Spices', code: 'SUB-GRN', description: 'Kashmiri lal mirch and golden turmeric powder', is_active: 1, status: 1 },
  // Cat 7: Personal Care & Hygiene
  { id: 13, category_id: 7, name: 'Beauty & Bath Soaps', code: 'SUB-SOP', description: 'Floral beauty soaps and antibacterial soap bars', is_active: 1, status: 1 },
  { id: 14, category_id: 7, name: 'Hair Care & Shampoos', code: 'SUB-SHM', description: 'Anti-dandruff herbal and silky shine shampoos', is_active: 1, status: 1 },
  // Cat 8: Laundry & Household Cleaning
  { id: 15, category_id: 8, name: 'Washing Powders & Detergents', code: 'SUB-DET', description: 'Ultra white washing powders and machine detergents', is_active: 1, status: 1 },
  { id: 16, category_id: 8, name: 'Surface Cleaners & Dishwash', code: 'SUB-CLN', description: 'Dishwashing liquid gels and disinfectant surface cleaners', is_active: 1, status: 1 }
];

export const DEMO_PRODUCT_FAMILIES = [
  // Subcat 1: Basmati Rice (Cat 1)
  { id: 1, subcategory_id: 1, category_id: 1, brand_id: 1, name: 'DailyHarvest Super Basmati Rice', code: 'FAM-DH-SBR' },
  { id: 2, subcategory_id: 1, category_id: 1, brand_id: 1, name: 'DailyHarvest Kainat 1121 Sella Rice', code: 'FAM-DH-KSR' },
  // Subcat 2: Pulses & Daal (Cat 1)
  { id: 3, subcategory_id: 2, category_id: 1, brand_id: 1, name: 'DailyHarvest Premium Dal Chana', code: 'FAM-DH-DCH' },
  { id: 4, subcategory_id: 2, category_id: 1, brand_id: 1, name: 'DailyHarvest Washed Dal Masoor', code: 'FAM-DH-DMS' },
  // Subcat 3: Wheat Flour & Atta (Cat 2)
  { id: 5, subcategory_id: 3, category_id: 2, brand_id: 1, name: 'DailyHarvest Chakki Whole Wheat Atta', code: 'FAM-DH-WWA' },
  { id: 6, subcategory_id: 3, category_id: 2, brand_id: 1, name: 'DailyHarvest Super Fine Maida', code: 'FAM-DH-SFM' },
  // Subcat 4: Sugar & Sweeteners (Cat 2)
  { id: 7, subcategory_id: 4, category_id: 2, brand_id: 1, name: 'DailyHarvest Refined White Sugar', code: 'FAM-DH-RWS' },
  { id: 8, subcategory_id: 4, category_id: 2, brand_id: 1, name: 'DailyHarvest Pure Desi Shakkar', code: 'FAM-DH-PDS' },
  // Subcat 5: Black & Green Tea (Cat 3)
  { id: 9, subcategory_id: 5, category_id: 3, brand_id: 2, name: 'Khyber Gold Danedar Tea', code: 'FAM-KTS-GDT' },
  { id: 10, subcategory_id: 5, category_id: 3, brand_id: 2, name: 'Khyber Royal Peshawari Green Tea Kahwa', code: 'FAM-KTS-PGT' },
  // Subcat 6: Cold Drinks & Juices (Cat 3)
  { id: 11, subcategory_id: 6, category_id: 3, brand_id: 2, name: 'Khyber Traditional Rooh-e-Sharbat Syrup', code: 'FAM-KTS-RSS' },
  { id: 12, subcategory_id: 6, category_id: 3, brand_id: 2, name: 'Khyber Pure Mango Nectar Fruit Juice', code: 'FAM-KTS-PMN' },
  // Subcat 7: UHT Milk & Cream (Cat 4)
  { id: 13, subcategory_id: 7, category_id: 4, brand_id: 3, name: 'Indus Pure UHT Full Cream Milk', code: 'FAM-IDO-UHT' },
  { id: 14, subcategory_id: 7, category_id: 4, brand_id: 3, name: 'Indus Fresh Heavy Dairy Cream', code: 'FAM-IDO-HDC' },
  // Subcat 8: Butter & Spreads (Cat 4)
  { id: 15, subcategory_id: 8, category_id: 4, brand_id: 3, name: 'Indus Traditional Salted Table Butter', code: 'FAM-IDO-STB' },
  { id: 16, subcategory_id: 8, category_id: 4, brand_id: 3, name: 'Indus Rich Hazelnut & Cocoa Spread', code: 'FAM-IDO-HCS' },
  // Subcat 9: Banaspati Ghee (Cat 5)
  { id: 17, subcategory_id: 9, category_id: 5, brand_id: 3, name: 'Indus Premium Banaspati Ghee (Poly Pouch)', code: 'FAM-IDO-PBG' },
  { id: 18, subcategory_id: 9, category_id: 5, brand_id: 3, name: 'Indus Pure Desi Cow Ghee (Tin)', code: 'FAM-IDO-DCG' },
  // Subcat 10: Edible Cooking Oils (Cat 5)
  { id: 19, subcategory_id: 10, category_id: 5, brand_id: 3, name: 'Indus Gold Canola Cooking Oil', code: 'FAM-IDO-CAN' },
  { id: 20, subcategory_id: 10, category_id: 5, brand_id: 3, name: 'Indus SunPure Refined Sunflower Oil', code: 'FAM-IDO-SFO' },
  // Subcat 11: Specialty Recipe Mixes (Cat 6)
  { id: 21, subcategory_id: 11, category_id: 6, brand_id: 2, name: 'Khyber Special Bombay Biryani Masala', code: 'FAM-KTS-BBM' },
  { id: 22, subcategory_id: 11, category_id: 6, brand_id: 2, name: 'Khyber Nihari & Haleem Recipe Spice Mix', code: 'FAM-KTS-NHM' },
  // Subcat 12: Ground & Whole Spices (Cat 6)
  { id: 23, subcategory_id: 12, category_id: 6, brand_id: 2, name: 'Khyber Pure Kashmiri Lal Mirch Powder', code: 'FAM-KTS-KLM' },
  { id: 24, subcategory_id: 12, category_id: 6, brand_id: 2, name: 'Khyber Golden Turmeric Haldi Powder', code: 'FAM-KTS-GTH' },
  // Subcat 13: Beauty & Bath Soaps (Cat 7)
  { id: 25, subcategory_id: 13, category_id: 7, brand_id: 4, name: 'PakClean Floral Glow Beauty Soap', code: 'FAM-PCC-FGB' },
  { id: 26, subcategory_id: 13, category_id: 7, brand_id: 4, name: 'PakClean Total Antibacterial Health Soap', code: 'FAM-PCC-TAH' },
  // Subcat 14: Hair Care & Shampoos (Cat 7)
  { id: 27, subcategory_id: 14, category_id: 7, brand_id: 4, name: 'PakClean Anti-Dandruff Herbal Shampoo', code: 'FAM-PCC-ADH' },
  { id: 28, subcategory_id: 14, category_id: 7, brand_id: 4, name: 'PakClean Silky Shine Smooth Shampoo', code: 'FAM-PCC-SSS' },
  // Subcat 15: Washing Powders & Detergents (Cat 8)
  { id: 29, subcategory_id: 15, category_id: 8, brand_id: 4, name: 'PakClean Ultra White Washing Powder', code: 'FAM-PCC-UWW' },
  { id: 30, subcategory_id: 15, category_id: 8, brand_id: 4, name: 'PakClean Matic Front & Top Load Detergent', code: 'FAM-PCC-MTD' },
  // Subcat 16: Surface Cleaners & Dishwash (Cat 8)
  { id: 31, subcategory_id: 16, category_id: 8, brand_id: 4, name: 'PakClean Lemon Power Dishwashing Liquid Gel', code: 'FAM-PCC-LDG' },
  { id: 32, subcategory_id: 16, category_id: 8, brand_id: 4, name: 'PakClean Citrus Floor & Surface Disinfectant', code: 'FAM-PCC-CFD' }
];

export const DEMO_PRODUCTS = [
  // Family 1: DailyHarvest Super Basmati Rice
  {
    id: 1,
    family_id: 1,
    name: 'DailyHarvest Super Basmati Rice 1 kg',
    sku: 'DH-SBR-01KG',
    barcode: '896400100101',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 1,
    subcategory: 'Basmati Rice',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 340.00,
    selling_price: 410.00,
    wholesale_price: 375.00,
    stock: 120.000,
    min_stock: 25.000,
    image: '/demo/products/prod_01.webp',
    description: 'Aged long-grain kernel basmati rice, supreme fragrance.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400100199', barcode_type: 'ALTERNATE', multiplier: 10, label: 'Master Carton (10x1kg)' }
    ]
  },
  {
    id: 2,
    family_id: 1,
    name: 'DailyHarvest Super Basmati Rice 5 kg',
    sku: 'DH-SBR-05KG',
    barcode: '896400100105',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 1,
    subcategory: 'Basmati Rice',
    unit: 'bag',
    allow_fractional: false,
    cost_price: 1650.00,
    selling_price: 1990.00,
    wholesale_price: 1820.00,
    stock: 45.000,
    min_stock: 10.000,
    image: '/demo/products/prod_02.webp',
    description: '5 kg family zipper bag of aged super basmati rice.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 3,
    family_id: 1,
    name: 'DailyHarvest Super Basmati Rice 10 kg',
    sku: 'DH-SBR-10KG',
    barcode: '896400100110',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 1,
    subcategory: 'Basmati Rice',
    unit: 'sack',
    allow_fractional: false,
    cost_price: 3200.00,
    selling_price: 3850.00,
    wholesale_price: 3550.00,
    stock: 25.000,
    min_stock: 5.000,
    image: '/demo/products/prod_03.webp',
    description: '10 kg master woven sack of aged super basmati rice.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 2: DailyHarvest Kainat 1121 Sella Rice
  {
    id: 4,
    family_id: 2,
    name: 'DailyHarvest Kainat 1121 Sella Rice 1 kg',
    sku: 'DH-KSR-01KG',
    barcode: '896400100201',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 1,
    subcategory: 'Basmati Rice',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 310.00,
    selling_price: 380.00,
    wholesale_price: 345.00,
    stock: 90.000,
    min_stock: 20.000,
    image: '/demo/products/prod_04.webp',
    description: 'Parboiled extra-long 1121 sella rice for biryani and pulao.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 5,
    family_id: 2,
    name: 'DailyHarvest Kainat 1121 Sella Rice 5 kg',
    sku: 'DH-KSR-05KG',
    barcode: '896400100205',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 1,
    subcategory: 'Basmati Rice',
    unit: 'bag',
    allow_fractional: false,
    cost_price: 1500.00,
    selling_price: 1820.00,
    wholesale_price: 1650.00,
    stock: 35.000,
    min_stock: 8.000,
    image: '/demo/products/prod_05.webp',
    description: '5 kg bag parboiled 1121 kainat sella basmati rice.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 3: DailyHarvest Premium Dal Chana
  {
    id: 6,
    family_id: 3,
    name: 'DailyHarvest Premium Dal Chana 1 kg',
    sku: 'DH-DCH-01KG',
    barcode: '896400100301',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 2,
    subcategory: 'Pulses & Daal',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 210.00,
    selling_price: 260.00,
    wholesale_price: 235.00,
    stock: 80.000,
    min_stock: 15.000,
    image: '/demo/products/prod_06.webp',
    description: 'Sorted and washed yellow gram pulse.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 4: DailyHarvest Washed Dal Masoor
  {
    id: 7,
    family_id: 4,
    name: 'DailyHarvest Washed Dal Masoor 1 kg',
    sku: 'DH-DMS-01KG',
    barcode: '896400100401',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 1,
    category: 'Grains & Staples',
    subcategory_id: 2,
    subcategory: 'Pulses & Daal',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 250.00,
    selling_price: 310.00,
    wholesale_price: 280.00,
    stock: 75.000,
    min_stock: 15.000,
    image: '/demo/products/prod_07.webp',
    description: 'Fine polished red split lentils.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 5: DailyHarvest Chakki Whole Wheat Atta
  {
    id: 8,
    family_id: 5,
    name: 'DailyHarvest Chakki Whole Wheat Atta 5 kg',
    sku: 'DH-WWA-05KG',
    barcode: '896400100505',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 2,
    category: 'Flours & Baking',
    subcategory_id: 3,
    subcategory: 'Wheat Flour & Atta',
    unit: 'bag',
    allow_fractional: false,
    cost_price: 650.00,
    selling_price: 750.00,
    wholesale_price: 700.00,
    stock: 50.000,
    min_stock: 12.000,
    image: '/demo/products/prod_08.webp',
    description: '100% whole wheat stone-ground traditional chakki flour.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400100599', barcode_type: 'ALTERNATE', multiplier: 4, label: 'Wholesale Bundle (4x5kg)' }
    ]
  },
  {
    id: 9,
    family_id: 5,
    name: 'DailyHarvest Chakki Whole Wheat Atta 10 kg',
    sku: 'DH-WWA-10KG',
    barcode: '896400100510',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 2,
    category: 'Flours & Baking',
    subcategory_id: 3,
    subcategory: 'Wheat Flour & Atta',
    unit: 'bag',
    allow_fractional: false,
    cost_price: 1250.00,
    selling_price: 1450.00,
    wholesale_price: 1350.00,
    stock: 40.000,
    min_stock: 10.000,
    image: '/demo/products/prod_09.webp',
    description: '10 kg family pack chakki whole wheat flour.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 6: DailyHarvest Super Fine Maida
  {
    id: 10,
    family_id: 6,
    name: 'DailyHarvest Super Fine Maida 1 kg',
    sku: 'DH-SFM-01KG',
    barcode: '896400100601',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 2,
    category: 'Flours & Baking',
    subcategory_id: 3,
    subcategory: 'Wheat Flour & Atta',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 130.00,
    selling_price: 160.00,
    wholesale_price: 145.00,
    stock: 65.000,
    min_stock: 12.000,
    image: '/demo/products/prod_10.webp',
    description: 'Premium refined all-purpose flour for baking and frying.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 7: DailyHarvest Refined White Sugar
  {
    id: 11,
    family_id: 7,
    name: 'DailyHarvest Refined White Sugar 1 kg',
    sku: 'DH-RWS-01KG',
    barcode: '896400100701',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 2,
    category: 'Flours & Baking',
    subcategory_id: 4,
    subcategory: 'Sugar & Sweeteners',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 140.00,
    selling_price: 160.00,
    wholesale_price: 150.00,
    stock: 150.000,
    min_stock: 30.000,
    image: '/demo/products/prod_11.webp',
    description: 'Pure cane sparkling white crystal sugar 1 kg.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 12,
    family_id: 7,
    name: 'DailyHarvest Refined White Sugar 5 kg',
    sku: 'DH-RWS-05KG',
    barcode: '896400100705',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 2,
    category: 'Flours & Baking',
    subcategory_id: 4,
    subcategory: 'Sugar & Sweeteners',
    unit: 'bag',
    allow_fractional: false,
    cost_price: 680.00,
    selling_price: 780.00,
    wholesale_price: 730.00,
    stock: 45.000,
    min_stock: 10.000,
    image: '/demo/products/prod_12.webp',
    description: '5 kg retail pack refined cane sugar.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 8: DailyHarvest Pure Desi Shakkar
  {
    id: 13,
    family_id: 8,
    name: 'DailyHarvest Pure Desi Shakkar 1 kg',
    sku: 'DH-PDS-01KG',
    barcode: '896400100801',
    brand_id: 1,
    brand: 'DailyHarvest Foods',
    category_id: 2,
    category: 'Flours & Baking',
    subcategory_id: 4,
    subcategory: 'Sugar & Sweeteners',
    unit: 'kg',
    allow_fractional: true,
    cost_price: 180.00,
    selling_price: 220.00,
    wholesale_price: 195.00,
    stock: 55.000,
    min_stock: 10.000,
    image: '/demo/products/prod_13.webp',
    description: 'Natural unrefined gur/shakkar powder rich in iron.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 9: Khyber Gold Danedar Tea
  {
    id: 14,
    family_id: 9,
    name: 'Khyber Gold Danedar Tea 450g',
    sku: 'KTS-GDT-450G',
    barcode: '896400200145',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 3,
    category: 'Beverages & Refreshments',
    subcategory_id: 5,
    subcategory: 'Black & Green Tea',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 680.00,
    selling_price: 780.00,
    wholesale_price: 730.00,
    stock: 85.000,
    min_stock: 20.000,
    image: '/demo/products/prod_14.webp',
    description: 'Full-bodied Kenyan black tea blend with rich golden brew.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400200198', barcode_type: 'ALTERNATE', multiplier: 24, label: 'Master Carton (24x450g)' }
    ]
  },
  {
    id: 15,
    family_id: 9,
    name: 'Khyber Gold Danedar Tea 900g',
    sku: 'KTS-GDT-900G',
    barcode: '896400200190',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 3,
    category: 'Beverages & Refreshments',
    subcategory_id: 5,
    subcategory: 'Black & Green Tea',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 1320.00,
    selling_price: 1520.00,
    wholesale_price: 1420.00,
    stock: 40.000,
    min_stock: 10.000,
    image: '/demo/products/prod_15.webp',
    description: '900g value economy pack of Khyber Gold tea.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 10: Khyber Royal Peshawari Green Tea Kahwa
  {
    id: 16,
    family_id: 10,
    name: 'Khyber Royal Peshawari Green Tea Kahwa 250g',
    sku: 'KTS-PGT-250G',
    barcode: '896400200225',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 3,
    category: 'Beverages & Refreshments',
    subcategory_id: 5,
    subcategory: 'Black & Green Tea',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 290.00,
    selling_price: 350.00,
    wholesale_price: 320.00,
    stock: 60.000,
    min_stock: 12.000,
    image: '/demo/products/prod_16.webp',
    description: 'Traditional green tea blend with cardamom aroma.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 11: Khyber Traditional Rooh-e-Sharbat Syrup
  {
    id: 17,
    family_id: 11,
    name: 'Khyber Traditional Rooh-e-Sharbat Syrup 800ml',
    sku: 'KTS-RSS-800M',
    barcode: '896400200380',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 3,
    category: 'Beverages & Refreshments',
    subcategory_id: 6,
    subcategory: 'Cold Drinks & Juices',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 320.00,
    selling_price: 390.00,
    wholesale_price: 355.00,
    stock: 70.000,
    min_stock: 15.000,
    image: '/demo/products/prod_17.webp',
    description: 'Herbal rose cooling syrup for milk and water refreshments.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 12: Khyber Pure Mango Nectar Fruit Juice
  {
    id: 18,
    family_id: 12,
    name: 'Khyber Pure Mango Nectar Fruit Juice 1 Litre',
    sku: 'KTS-PMN-01L',
    barcode: '896400200401',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 3,
    category: 'Beverages & Refreshments',
    subcategory_id: 6,
    subcategory: 'Cold Drinks & Juices',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 220.00,
    selling_price: 280.00,
    wholesale_price: 250.00,
    stock: 95.000,
    min_stock: 20.000,
    image: '/demo/products/prod_18.webp',
    description: 'Made from ripe Chaunsa mango pulp in a 1L aseptic tetra brick.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 21: Khyber Special Bombay Biryani Masala
  {
    id: 19,
    family_id: 21,
    name: 'Khyber Special Bombay Biryani Masala 50g',
    sku: 'KTS-BBM-50G',
    barcode: '896400200550',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 6,
    category: 'Spices & Seasonings',
    subcategory_id: 11,
    subcategory: 'Specialty Recipe Mixes',
    unit: 'box',
    allow_fractional: false,
    cost_price: 100.00,
    selling_price: 130.00,
    wholesale_price: 112.00,
    stock: 140.000,
    min_stock: 30.000,
    image: '/demo/products/prod_31.webp',
    description: 'Iconic spicy rice recipe spice blend with whole plums.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400200599', barcode_type: 'ALTERNATE', multiplier: 12, label: 'Outer Box (12x50g)' }
    ]
  },

  // Family 22: Khyber Nihari & Haleem Recipe Spice Mix
  {
    id: 20,
    family_id: 22,
    name: 'Khyber Nihari & Haleem Recipe Spice Mix 50g',
    sku: 'KTS-NHM-50G',
    barcode: '896400200650',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 6,
    category: 'Spices & Seasonings',
    subcategory_id: 11,
    subcategory: 'Specialty Recipe Mixes',
    unit: 'box',
    allow_fractional: false,
    cost_price: 100.00,
    selling_price: 130.00,
    wholesale_price: 112.00,
    stock: 110.000,
    min_stock: 25.000,
    image: '/demo/products/prod_32.webp',
    description: 'Rich slow-cooked meat stew recipe spice mix.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 23: Khyber Pure Kashmiri Lal Mirch Powder
  {
    id: 21,
    family_id: 23,
    name: 'Khyber Pure Kashmiri Lal Mirch Powder 200g',
    sku: 'KTS-KLM-200G',
    barcode: '896400200720',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 6,
    category: 'Spices & Seasonings',
    subcategory_id: 12,
    subcategory: 'Ground & Whole Spices',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 250.00,
    selling_price: 320.00,
    wholesale_price: 280.00,
    stock: 90.000,
    min_stock: 18.000,
    image: '/demo/products/prod_33.webp',
    description: 'Vibrant natural red chili powder for aroma and deep color.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 24: Khyber Golden Turmeric Haldi Powder
  {
    id: 22,
    family_id: 24,
    name: 'Khyber Golden Turmeric Haldi Powder 200g',
    sku: 'KTS-GTH-200G',
    barcode: '896400200820',
    brand_id: 2,
    brand: 'Khyber Tea & Spices',
    category_id: 6,
    category: 'Spices & Seasonings',
    subcategory_id: 12,
    subcategory: 'Ground & Whole Spices',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 190.00,
    selling_price: 240.00,
    wholesale_price: 215.00,
    stock: 95.000,
    min_stock: 18.000,
    image: '/demo/products/prod_34.webp',
    description: 'High-curcumin pure ground turmeric powder.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 13: Indus Pure UHT Full Cream Milk
  {
    id: 23,
    family_id: 13,
    name: 'Indus Pure UHT Full Cream Milk 1 Litre',
    sku: 'IDO-UHT-01L',
    barcode: '896400300101',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 4,
    category: 'Dairy & Breakfast',
    subcategory_id: 7,
    subcategory: 'UHT Milk & Cream',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 235.00,
    selling_price: 270.00,
    wholesale_price: 250.00,
    stock: 220.000,
    min_stock: 40.000,
    image: '/demo/products/prod_19.webp',
    description: 'Pure homogenized 3.5% fat full cream UHT milk pack.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400300199', barcode_type: 'ALTERNATE', multiplier: 12, label: 'Master Carton (12x1L)' }
    ]
  },
  {
    id: 24,
    family_id: 13,
    name: 'Indus Pure UHT Full Cream Milk 250ml',
    sku: 'IDO-UHT-250M',
    barcode: '896400300125',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 4,
    category: 'Dairy & Breakfast',
    subcategory_id: 7,
    subcategory: 'UHT Milk & Cream',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 65.00,
    selling_price: 80.00,
    wholesale_price: 72.00,
    stock: 160.000,
    min_stock: 35.000,
    image: '/demo/products/prod_20.webp',
    description: '250ml single-serve pocket milk carton with straw.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 14: Indus Fresh Heavy Dairy Cream
  {
    id: 25,
    family_id: 14,
    name: 'Indus Fresh Heavy Dairy Cream 200ml',
    sku: 'IDO-HDC-200M',
    barcode: '896400300220',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 4,
    category: 'Dairy & Breakfast',
    subcategory_id: 7,
    subcategory: 'UHT Milk & Cream',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 150.00,
    selling_price: 180.00,
    wholesale_price: 162.00,
    stock: 80.000,
    min_stock: 18.000,
    image: '/demo/products/prod_21.webp',
    description: 'Thick pasteurized whipping and dessert dairy cream.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 15: Indus Traditional Salted Table Butter
  {
    id: 26,
    family_id: 15,
    name: 'Indus Traditional Salted Table Butter 200g',
    sku: 'IDO-STB-200G',
    barcode: '896400300320',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 4,
    category: 'Dairy & Breakfast',
    subcategory_id: 8,
    subcategory: 'Butter & Spreads',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 285.00,
    selling_price: 340.00,
    wholesale_price: 310.00,
    stock: 65.000,
    min_stock: 15.000,
    image: '/demo/products/prod_22.webp',
    description: 'Creamy salted pasture butter brick wrapped in foil.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 16: Indus Rich Hazelnut & Cocoa Spread
  {
    id: 27,
    family_id: 16,
    name: 'Indus Rich Hazelnut & Cocoa Spread 350g',
    sku: 'IDO-HCS-350G',
    barcode: '896400300435',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 4,
    category: 'Dairy & Breakfast',
    subcategory_id: 8,
    subcategory: 'Butter & Spreads',
    unit: 'jar',
    allow_fractional: false,
    cost_price: 520.00,
    selling_price: 650.00,
    wholesale_price: 580.00,
    stock: 50.000,
    min_stock: 10.000,
    image: '/demo/products/prod_23.webp',
    description: 'Smooth hazelnut chocolate breakfast spread jar.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 17: Indus Premium Banaspati Ghee (Poly Pouch)
  {
    id: 28,
    family_id: 17,
    name: 'Indus Premium Banaspati Ghee 1 kg Pouch',
    sku: 'IDO-PBG-01L',
    barcode: '896400300501',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 9,
    subcategory: 'Banaspati Ghee',
    unit: 'pouch',
    allow_fractional: false,
    cost_price: 460.00,
    selling_price: 520.00,
    wholesale_price: 490.00,
    stock: 110.000,
    min_stock: 25.000,
    image: '/demo/products/prod_24.webp',
    description: 'Vitamin A & D enriched aromatic granular banaspati ghee.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400300599', barcode_type: 'ALTERNATE', multiplier: 16, label: 'Master Carton (16x1kg)' }
    ]
  },
  {
    id: 29,
    family_id: 17,
    name: 'Indus Premium Banaspati Ghee 5 kg Tin',
    sku: 'IDO-PBG-05L',
    barcode: '896400300505',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 9,
    subcategory: 'Banaspati Ghee',
    unit: 'tin',
    allow_fractional: false,
    cost_price: 2250.00,
    selling_price: 2550.00,
    wholesale_price: 2400.00,
    stock: 40.000,
    min_stock: 8.000,
    image: '/demo/products/prod_25.webp',
    description: '5 kg sealed metal tin of aromatic banaspati ghee.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 18: Indus Pure Desi Cow Ghee (Tin)
  {
    id: 30,
    family_id: 18,
    name: 'Indus Pure Desi Cow Ghee 1 kg Tin',
    sku: 'IDO-DCG-01L',
    barcode: '896400300601',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 9,
    subcategory: 'Banaspati Ghee',
    unit: 'tin',
    allow_fractional: false,
    cost_price: 2050.00,
    selling_price: 2400.00,
    wholesale_price: 2200.00,
    stock: 30.000,
    min_stock: 6.000,
    image: '/demo/products/prod_26.webp',
    description: '100% clarified golden grass-fed cow butter ghee.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 19: Indus Gold Canola Cooking Oil
  {
    id: 31,
    family_id: 19,
    name: 'Indus Gold Canola Cooking Oil 1 Litre Pouch',
    sku: 'IDO-CAN-01L',
    barcode: '896400300701',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 10,
    subcategory: 'Edible Cooking Oils',
    unit: 'pouch',
    allow_fractional: false,
    cost_price: 480.00,
    selling_price: 540.00,
    wholesale_price: 505.00,
    stock: 130.000,
    min_stock: 30.000,
    image: '/demo/products/prod_27.webp',
    description: 'Omega-3 rich refined canola oil with high smoke point.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 32,
    family_id: 19,
    name: 'Indus Gold Canola Cooking Oil 5 Litre Bottle',
    sku: 'IDO-CAN-05L',
    barcode: '896400300705',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 10,
    subcategory: 'Edible Cooking Oils',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 2350.00,
    selling_price: 2650.00,
    wholesale_price: 2480.00,
    stock: 35.000,
    min_stock: 8.000,
    image: '/demo/products/prod_28.webp',
    description: '5 Litre ergonomic handle bottle of refined canola oil.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 20: Indus SunPure Refined Sunflower Oil
  {
    id: 33,
    family_id: 20,
    name: 'Indus SunPure Refined Sunflower Oil 1 Litre Pouch',
    sku: 'IDO-SFO-01L',
    barcode: '896400300801',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 10,
    subcategory: 'Edible Cooking Oils',
    unit: 'pouch',
    allow_fractional: false,
    cost_price: 490.00,
    selling_price: 550.00,
    wholesale_price: 515.00,
    stock: 120.000,
    min_stock: 25.000,
    image: '/demo/products/prod_29.webp',
    description: 'Light and cholesterol-free sunflower cooking oil.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 34,
    family_id: 20,
    name: 'Indus SunPure Refined Sunflower Oil 5 Litre Bottle',
    sku: 'IDO-SFO-05L',
    barcode: '896400300805',
    brand_id: 3,
    brand: 'Indus Dairy & Oils',
    category_id: 5,
    category: 'Cooking Oils & Ghee',
    subcategory_id: 10,
    subcategory: 'Edible Cooking Oils',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 2400.00,
    selling_price: 2700.00,
    wholesale_price: 2520.00,
    stock: 32.000,
    min_stock: 7.000,
    image: '/demo/products/prod_30.webp',
    description: '5 Litre family pack sunflower cooking oil bottle.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 25: PakClean Floral Glow Beauty Soap
  {
    id: 35,
    family_id: 25,
    name: 'PakClean Floral Glow Beauty Soap 140g',
    sku: 'PCC-FGB-140G',
    barcode: '896400400114',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 7,
    category: 'Personal Care & Hygiene',
    subcategory_id: 13,
    subcategory: 'Beauty & Bath Soaps',
    unit: 'bar',
    allow_fractional: false,
    cost_price: 100.00,
    selling_price: 130.00,
    wholesale_price: 112.00,
    stock: 180.000,
    min_stock: 40.000,
    image: '/demo/products/prod_35.webp',
    description: 'French rose extract moisturising bath soap bar.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400400199', barcode_type: 'ALTERNATE', multiplier: 4, label: 'Value Bundle (Pack of 4)' }
    ]
  },

  // Family 26: PakClean Total Antibacterial Health Soap
  {
    id: 36,
    family_id: 26,
    name: 'PakClean Total Antibacterial Health Soap 125g',
    sku: 'PCC-TAH-125G',
    barcode: '896400400212',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 7,
    category: 'Personal Care & Hygiene',
    subcategory_id: 13,
    subcategory: 'Beauty & Bath Soaps',
    unit: 'bar',
    allow_fractional: false,
    cost_price: 95.00,
    selling_price: 125.00,
    wholesale_price: 108.00,
    stock: 170.000,
    min_stock: 35.000,
    image: '/demo/products/prod_36.webp',
    description: '99.9% germ protection herbal active soap bar.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 27: PakClean Anti-Dandruff Herbal Shampoo
  {
    id: 37,
    family_id: 27,
    name: 'PakClean Anti-Dandruff Herbal Shampoo 360ml',
    sku: 'PCC-ADH-360M',
    barcode: '896400400336',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 7,
    category: 'Personal Care & Hygiene',
    subcategory_id: 14,
    subcategory: 'Hair Care & Shampoos',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 470.00,
    selling_price: 580.00,
    wholesale_price: 520.00,
    stock: 75.000,
    min_stock: 15.000,
    image: '/demo/products/prod_37.webp',
    description: 'Tea tree and neem herbal formula for healthy scalp.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 38,
    family_id: 27,
    name: 'PakClean Anti-Dandruff Herbal Shampoo 180ml',
    sku: 'PCC-ADH-180M',
    barcode: '896400400318',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 7,
    category: 'Personal Care & Hygiene',
    subcategory_id: 14,
    subcategory: 'Hair Care & Shampoos',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 260.00,
    selling_price: 320.00,
    wholesale_price: 285.00,
    stock: 90.000,
    min_stock: 18.000,
    image: '/demo/products/prod_38.webp',
    description: '180ml travel size anti-dandruff herbal shampoo.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 28: PakClean Silky Shine Smooth Shampoo
  {
    id: 39,
    family_id: 28,
    name: 'PakClean Silky Shine Smooth Shampoo 360ml',
    sku: 'PCC-SSS-360M',
    barcode: '896400400436',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 7,
    category: 'Personal Care & Hygiene',
    subcategory_id: 14,
    subcategory: 'Hair Care & Shampoos',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 470.00,
    selling_price: 580.00,
    wholesale_price: 520.00,
    stock: 70.000,
    min_stock: 15.000,
    image: '/demo/products/prod_39.webp',
    description: 'Argan oil and silk protein smoothing shampoo.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 29: PakClean Ultra White Washing Powder
  {
    id: 40,
    family_id: 29,
    name: 'PakClean Ultra White Washing Powder 1 kg',
    sku: 'PCC-UWW-01KG',
    barcode: '896400400501',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 8,
    category: 'Laundry & Household Cleaning',
    subcategory_id: 15,
    subcategory: 'Washing Powders & Detergents',
    unit: 'pack',
    allow_fractional: false,
    cost_price: 460.00,
    selling_price: 540.00,
    wholesale_price: 495.00,
    stock: 130.000,
    min_stock: 25.000,
    image: '/demo/products/prod_40.webp',
    description: 'Advanced enzyme stain removal detergent powder.',
    is_active: 1,
    alternate_barcodes: []
  },
  {
    id: 41,
    family_id: 29,
    name: 'PakClean Ultra White Washing Powder 3 kg',
    sku: 'PCC-UWW-03KG',
    barcode: '896400400503',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 8,
    category: 'Laundry & Household Cleaning',
    subcategory_id: 15,
    subcategory: 'Washing Powders & Detergents',
    unit: 'bag',
    allow_fractional: false,
    cost_price: 1320.00,
    selling_price: 1550.00,
    wholesale_price: 1420.00,
    stock: 45.000,
    min_stock: 10.000,
    image: '/demo/products/prod_41.webp',
    description: '3 kg mega saver bag with optical brighteners.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 30: PakClean Matic Front & Top Load Detergent
  {
    id: 42,
    family_id: 30,
    name: 'PakClean Matic Machine Detergent 2 kg',
    sku: 'PCC-MTD-02KG',
    barcode: '896400400602',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 8,
    category: 'Laundry & Household Cleaning',
    subcategory_id: 15,
    subcategory: 'Washing Powders & Detergents',
    unit: 'box',
    allow_fractional: false,
    cost_price: 1050.00,
    selling_price: 1250.00,
    wholesale_price: 1140.00,
    stock: 55.000,
    min_stock: 12.000,
    image: '/demo/products/prod_42.webp',
    description: 'Low-foam automatic washing machine detergent box.',
    is_active: 1,
    alternate_barcodes: []
  },

  // Family 31: PakClean Lemon Power Dishwashing Liquid Gel
  {
    id: 43,
    family_id: 31,
    name: 'PakClean Lemon Power Dishwash Liquid 500ml',
    sku: 'PCC-LDG-500M',
    barcode: '896400400750',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 8,
    category: 'Laundry & Household Cleaning',
    subcategory_id: 16,
    subcategory: 'Surface Cleaners & Dishwash',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 220.00,
    selling_price: 280.00,
    wholesale_price: 245.00,
    stock: 110.000,
    min_stock: 25.000,
    image: '/demo/products/prod_43.webp',
    description: 'Concentrated grease-cutting lemon dishwash gel.',
    is_active: 1,
    alternate_barcodes: [
      { barcode: '896400400799', barcode_type: 'ALTERNATE', multiplier: 12, label: 'Master Carton (12x500ml)' }
    ]
  },

  // Family 32: PakClean Citrus Floor & Surface Disinfectant
  {
    id: 44,
    family_id: 32,
    name: 'PakClean Citrus Floor Disinfectant 1 Litre',
    sku: 'PCC-CFD-01L',
    barcode: '896400400801',
    brand_id: 4,
    brand: 'PakClean & Care',
    category_id: 8,
    category: 'Laundry & Household Cleaning',
    subcategory_id: 16,
    subcategory: 'Surface Cleaners & Dishwash',
    unit: 'bottle',
    allow_fractional: false,
    cost_price: 290.00,
    selling_price: 360.00,
    wholesale_price: 320.00,
    stock: 85.000,
    min_stock: 18.000,
    image: '/demo/products/prod_44.webp',
    description: 'Multi-surface floor disinfectant with 24hr citrus fragrance.',
    is_active: 1,
    alternate_barcodes: []
  }
];

export const DEMO_BARCODE_ALIASES = [
  { barcode: '896400100199', product_id: 1, barcode_type: 'ALTERNATE', multiplier: 10, label: 'Master Carton (10x1kg)' },
  { barcode: '896400100599', product_id: 8, barcode_type: 'ALTERNATE', multiplier: 4, label: 'Wholesale Bundle (4x5kg)' },
  { barcode: '896400200198', product_id: 14, barcode_type: 'ALTERNATE', multiplier: 24, label: 'Master Carton (24x450g)' },
  { barcode: '896400200599', product_id: 19, barcode_type: 'ALTERNATE', multiplier: 12, label: 'Outer Box (12x50g)' },
  { barcode: '896400300199', product_id: 23, barcode_type: 'ALTERNATE', multiplier: 12, label: 'Master Carton (12x1L)' },
  { barcode: '896400300599', product_id: 28, barcode_type: 'ALTERNATE', multiplier: 16, label: 'Master Carton (16x1kg)' },
  { barcode: '896400400199', product_id: 35, barcode_type: 'ALTERNATE', multiplier: 4, label: 'Value Bundle (Pack of 4)' },
  { barcode: '896400400799', product_id: 43, barcode_type: 'ALTERNATE', multiplier: 12, label: 'Master Carton (12x500ml)' }
];

export const DEMO_CUSTOMERS = [
  {
    id: 1,
    name: 'Tariq Mehmood',
    phone: '03001234501',
    address: 'House 14, Street 3, Bahadurabad, Karachi',
    credit_limit: 25000.00,
    opening_balance: 3500.00,
    current_balance: 3500.00,
    total_purchases: 48500.00,
    is_active: 1,
    status: 'active'
  },
  {
    id: 2,
    name: 'Haji Abdul Rehman',
    phone: '03219876543',
    address: 'Shop 5, Tariq Road Commercial Area, Karachi',
    credit_limit: 40000.00,
    opening_balance: 5200.00,
    current_balance: 5200.00,
    total_purchases: 89000.00,
    is_active: 1,
    status: 'active'
  },
  {
    id: 3,
    name: 'Chaudhry Aslam',
    phone: '03335551234',
    address: 'Flat 4B, Gulshan Block 13-D, Karachi',
    credit_limit: 20000.00,
    opening_balance: 2100.00,
    current_balance: 2100.00,
    total_purchases: 32400.00,
    is_active: 1,
    status: 'active'
  },
  {
    id: 4,
    name: 'Mohammad Imran',
    phone: '03451122334',
    address: 'House 22, Preedy Street, Saddar, Karachi',
    credit_limit: 15000.00,
    opening_balance: 1600.00,
    current_balance: 1600.00,
    total_purchases: 27800.00,
    is_active: 1,
    status: 'active'
  }
];

export const DEMO_SUPPLIERS = [
  {
    id: 1,
    name: 'DailyHarvest Agriculture Trading Co.',
    contact_person: 'Bilal Farooq',
    phone: '03009988771',
    email: 'orders@dailyharvest.pk',
    address: 'Grain Market, Jodia Bazaar, Karachi',
    opening_balance: 45000.00,
    current_balance: 45000.00,
    total_purchases: 450000.00,
    is_active: 1,
    status: 'active'
  },
  {
    id: 2,
    name: 'Khyber Tea & Commodity Importers',
    contact_person: 'Mirza Jahangir',
    phone: '03215544332',
    email: 'supply@khybertea.pk',
    address: 'Tea Market, M.A. Jinnah Road, Karachi',
    opening_balance: 32000.00,
    current_balance: 32000.00,
    total_purchases: 380000.00,
    is_active: 1,
    status: 'active'
  },
  {
    id: 3,
    name: 'Indus & PakClean FMCG Distributors',
    contact_person: 'Khurram Shehzad',
    phone: '03332211009',
    email: 'distribution@induspcc.pk',
    address: 'Plot 48, SITE Industrial Area, Karachi',
    opening_balance: 28000.00,
    current_balance: 28000.00,
    total_purchases: 510000.00,
    is_active: 1,
    status: 'active'
  }
];

/**
 * Generate Branch Inventories for Multi-Store Setup.
 * Reconciled across:
 * - Branch 1 (Clifton Main Store, Primary): flagship inventory
 * - Branch 2 (Saddar Express): mid-tier inventory
 * - Branch 3 (Gulshan Outlet): neighbourhood outlet inventory
 */
export function generateBranchInventories(products = DEMO_PRODUCTS) {
  const branchInvs = [];
  products.forEach(p => {
    const baseStock = Number(p.stock) || 50;
    const baseMin = Number(p.min_stock) || 10;

    // Branch 1: Clifton Main Store (~60% of total stock)
    const qty1 = Math.round(baseStock * 0.6 * 1000) / 1000;
    branchInvs.push({
      id: `bi_${p.id}_1`,
      product_id: p.id,
      branch_id: 1,
      branch_name: 'Clifton Main Store',
      quantity_on_hand: qty1,
      minimum_stock: Math.round(baseMin * 0.6),
      reorder_level: Math.round(baseMin * 0.8),
      valuation: Math.round(qty1 * p.cost_price * 100) / 100
    });

    // Branch 2: Saddar Express (~25% of total stock)
    const qty2 = Math.round(baseStock * 0.25 * 1000) / 1000;
    branchInvs.push({
      id: `bi_${p.id}_2`,
      product_id: p.id,
      branch_id: 2,
      branch_name: 'Saddar Express',
      quantity_on_hand: qty2,
      minimum_stock: Math.round(baseMin * 0.25),
      reorder_level: Math.round(baseMin * 0.4),
      valuation: Math.round(qty2 * p.cost_price * 100) / 100
    });

    // Branch 3: Gulshan Outlet (~15% of total stock)
    const qty3 = Math.round(baseStock * 0.15 * 1000) / 1000;
    branchInvs.push({
      id: `bi_${p.id}_3`,
      product_id: p.id,
      branch_id: 3,
      branch_name: 'Gulshan Outlet',
      quantity_on_hand: qty3,
      minimum_stock: Math.round(baseMin * 0.15),
      reorder_level: Math.round(baseMin * 0.25),
      valuation: Math.round(qty3 * p.cost_price * 100) / 100
    });
  });
  return branchInvs;
}

