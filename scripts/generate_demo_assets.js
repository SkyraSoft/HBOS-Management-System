const fs = require('fs');
const path = require('path');

const brandsDir = path.join(__dirname, '../Frontend/public/demo/brands');
const catsDir = path.join(__dirname, '../Frontend/public/demo/categories');
const prodsDir = path.join(__dirname, '../Frontend/public/demo/products');

[brandsDir, catsDir, prodsDir].forEach(d => {
  if (!fs.existsSync(d)) fs.mkdirSync(d, { recursive: true });
});

// Brand SVGs
const BRANDS = [
  {
    id: 1,
    file: 'brand_1.svg',
    name: 'DailyHarvest Foods',
    code: 'DH',
    primaryColor: '#1B5E20',
    secondaryColor: '#F9A825',
    accentColor: '#E8F5E9',
    tagline: 'Pure Grains & Staples',
    iconPath: `<circle cx="100" cy="80" r="36" fill="#F9A825" opacity="0.2"/>
               <path d="M100 45 C95 65 80 80 75 105 C90 100 95 90 100 45 Z" fill="#2E7D32"/>
               <path d="M100 45 C105 65 120 80 125 105 C110 100 105 90 100 45 Z" fill="#388E3C"/>
               <path d="M100 70 L100 125" stroke="#F9A825" stroke-width="4" stroke-linecap="round"/>
               <ellipse cx="88" cy="85" rx="5" ry="10" transform="rotate(-30 88 85)" fill="#F9A825"/>
               <ellipse cx="112" cy="85" rx="5" ry="10" transform="rotate(30 112 85)" fill="#F9A825"/>
               <ellipse cx="88" cy="105" rx="5" ry="10" transform="rotate(-30 88 105)" fill="#F9A825"/>
               <ellipse cx="112" cy="105" rx="5" ry="10" transform="rotate(30 112 105)" fill="#F9A825"/>`
  },
  {
    id: 2,
    file: 'brand_2.svg',
    name: 'Khyber Tea & Spices',
    code: 'KTS',
    primaryColor: '#B71C1C',
    secondaryColor: '#FF6F00',
    accentColor: '#FFF8E1',
    tagline: 'Heritage Aroma & Taste',
    iconPath: `<circle cx="100" cy="80" r="38" fill="#FF6F00" opacity="0.18"/>
               <path d="M85 95 C85 75 100 65 115 65 C120 80 110 95 85 95 Z" fill="#2E7D32"/>
               <path d="M75 105 C75 90 90 85 100 85 C105 98 95 105 75 105 Z" fill="#388E3C"/>
               <path d="M70 110 Q100 125 130 110 L125 125 Q100 135 75 125 Z" fill="#B71C1C"/>
               <path d="M92 60 Q96 50 92 40 M102 60 Q106 50 102 40" stroke="#FF6F00" stroke-width="3" stroke-linecap="round" fill="none"/>`
  },
  {
    id: 3,
    file: 'brand_3.svg',
    name: 'Indus Dairy & Oils',
    code: 'IDO',
    primaryColor: '#0D47A1',
    secondaryColor: '#FFB300',
    accentColor: '#E3F2FD',
    tagline: 'Farm Fresh & Pure Gold',
    iconPath: `<circle cx="100" cy="80" r="38" fill="#FFB300" opacity="0.2"/>
               <path d="M100 48 C100 48 80 80 80 98 C80 110 89 120 100 120 C111 120 120 110 120 98 C120 80 100 48 100 48 Z" fill="#1976D2"/>
               <path d="M100 70 C100 70 88 92 88 102 C88 110 94 114 100 114 C106 114 112 110 112 102 C112 92 100 70 100 70 Z" fill="#BBDEFB"/>
               <circle cx="100" cy="100" r="10" fill="#FFD54F"/>
               <path d="M100 86 L100 114 M86 100 L114 100 M90 90 L110 110 M90 110 L110 90" stroke="#FF8F00" stroke-width="2"/>`
  },
  {
    id: 4,
    file: 'brand_4.svg',
    name: 'PakClean & Care',
    code: 'PCC',
    primaryColor: '#006064',
    secondaryColor: '#00ACC1',
    accentColor: '#E0F7FA',
    tagline: 'Clean Living & Hygiene',
    iconPath: `<circle cx="100" cy="80" r="38" fill="#00ACC1" opacity="0.2"/>
               <path d="M100 45 L125 55 V85 C125 105 100 120 100 120 C100 120 75 105 75 85 V55 Z" fill="#00838F"/>
               <path d="M100 52 L118 60 V82 C118 97 100 110 100 110 C100 110 82 97 82 82 V60 Z" fill="#00ACC1"/>
               <polygon points="100,65 103,75 113,75 105,81 108,91 100,85 92,91 95,81 87,75 97,75" fill="#FFFFFF"/>
               <circle cx="70" cy="65" r="5" fill="#4DD0E1"/>
               <circle cx="130" cy="70" r="7" fill="#4DD0E1"/>`
  }
];

function generateBrandSvg(b) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 200" width="260" height="200">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${b.accentColor}"/>
      <stop offset="100%" stop-color="#FFFFFF"/>
    </linearGradient>
    <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="3" stdDeviation="3" flood-opacity="0.12"/>
    </filter>
  </defs>
  <rect width="260" height="200" rx="16" fill="url(#bgGrad)" stroke="${b.primaryColor}" stroke-width="2" stroke-opacity="0.2"/>
  <g filter="url(#shadow)">
    ${b.iconPath}
  </g>
  <text x="130" y="152" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" font-size="15" font-weight="800" text-anchor="middle" fill="${b.primaryColor}" letter-spacing="0.5">${b.name}</text>
  <text x="130" y="172" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" font-size="10.5" font-weight="600" text-anchor="middle" fill="${b.secondaryColor}" letter-spacing="0.8">${b.tagline.toUpperCase()}</text>
</svg>`;
}

// Category SVGs (8 categories)
const CATEGORIES = [
  {
    id: 1,
    file: 'cat_1.svg',
    name: 'Grains & Staples',
    color: '#2E7D32',
    bg: '#E8F5E9',
    symbol: `<path d="M35 65 C35 45 48 30 64 30 C80 30 93 45 93 65 L93 85 C93 90 89 94 84 94 L44 94 C39 94 35 90 35 85 Z" fill="#C8E6C9"/>
             <path d="M64 25 C60 40 48 52 44 72 C56 68 60 60 64 25 Z" fill="#2E7D32"/>
             <path d="M64 25 C68 40 80 52 84 72 C72 68 68 60 64 25 Z" fill="#388E3C"/>
             <path d="M40 80 Q64 90 88 80 L85 92 Q64 100 43 92 Z" fill="#81C784"/>`
  },
  {
    id: 2,
    file: 'cat_2.svg',
    name: 'Flours & Baking',
    color: '#E65100',
    bg: '#FFF3E0',
    symbol: `<path d="M40 50 L88 50 L84 95 C84 98 80 102 76 102 L52 102 C48 102 44 98 44 95 Z" fill="#FFE0B2" stroke="#E65100" stroke-width="3"/>
             <path d="M38 50 Q64 42 90 50 L88 58 Q64 50 40 58 Z" fill="#FFB74D"/>
             <circle cx="64" cy="76" r="14" fill="#FFFFFF" opacity="0.8"/>
             <text x="64" y="81" font-family="sans-serif" font-size="12" font-weight="900" text-anchor="middle" fill="#E65100">ATTA</text>`
  },
  {
    id: 3,
    file: 'cat_3.svg',
    name: 'Beverages & Refreshments',
    color: '#C2185B',
    bg: '#FCE4EC',
    symbol: `<path d="M42 48 L86 48 L80 96 C80 100 76 104 71 104 L57 104 C52 104 48 100 48 96 Z" fill="#F8BBD0" stroke="#C2185B" stroke-width="3"/>
             <path d="M86 58 C94 58 98 66 98 74 C98 82 92 88 82 88" stroke="#C2185B" stroke-width="3.5" fill="none" stroke-linecap="round"/>
             <path d="M54 36 Q58 26 54 18 M64 36 Q68 26 64 18 M74 36 Q78 26 74 18" stroke="#E91E63" stroke-width="2.5" stroke-linecap="round" fill="none"/>`
  },
  {
    id: 4,
    file: 'cat_4.svg',
    name: 'Dairy & Breakfast',
    color: '#0277BD',
    bg: '#E1F5FE',
    symbol: `<rect x="44" y="46" width="40" height="56" rx="6" fill="#B3E5FC" stroke="#0277BD" stroke-width="3"/>
             <polygon points="44,46 52,32 76,32 84,46" fill="#81D4FA" stroke="#0277BD" stroke-width="2.5"/>
             <rect x="58" y="24" width="12" height="8" rx="2" fill="#0277BD"/>
             <path d="M52 68 Q64 78 76 68 L76 88 Q64 96 52 88 Z" fill="#0277BD"/>`
  },
  {
    id: 5,
    file: 'cat_5.svg',
    name: 'Cooking Oils & Ghee',
    color: '#F57F17',
    bg: '#FFFDE7',
    symbol: `<path d="M52 46 L76 46 L82 98 C82 102 78 106 73 106 L55 106 C50 106 46 102 46 98 Z" fill="#FFF59D" stroke="#F57F17" stroke-width="3"/>
             <rect x="59" y="32" width="10" height="14" fill="#F57F17"/>
             <path d="M64 62 C64 62 55 76 55 83 C55 89 59 93 64 93 C69 93 73 89 73 83 C73 76 64 62 64 62 Z" fill="#F57F17"/>`
  },
  {
    id: 6,
    file: 'cat_6.svg',
    name: 'Spices & Seasonings',
    color: '#D84315',
    bg: '#FBE9E7',
    symbol: `<path d="M38 72 C38 90 50 102 64 102 C78 102 90 90 90 72 Z" fill="#FFCCBC" stroke="#D84315" stroke-width="3"/>
             <line x1="34" y1="72" x2="94" y2="72" stroke="#D84315" stroke-width="4" stroke-linecap="round"/>
             <path d="M72 40 L84 68" stroke="#BF360C" stroke-width="5" stroke-linecap="round"/>
             <circle cx="56" cy="85" r="4" fill="#D84315"/>
             <circle cx="68" cy="88" r="3" fill="#BF360C"/>
             <circle cx="62" cy="78" r="3.5" fill="#FF5722"/>`
  },
  {
    id: 7,
    file: 'cat_7.svg',
    name: 'Personal Care & Hygiene',
    color: '#00838F',
    bg: '#E0F7FA',
    symbol: `<rect x="42" y="58" width="44" height="42" rx="10" fill="#B2EBF2" stroke="#00838F" stroke-width="3"/>
             <circle cx="50" cy="42" r="7" fill="#80DEEA"/>
             <circle cx="66" cy="34" r="9" fill="#4DD0E1"/>
             <circle cx="78" cy="45" r="6" fill="#80DEEA"/>
             <path d="M52 79 C58 75 70 75 76 79" stroke="#00838F" stroke-width="3" stroke-linecap="round" fill="none"/>`
  },
  {
    id: 8,
    file: 'cat_8.svg',
    name: 'Laundry & Household Cleaning',
    color: '#283593',
    bg: '#E8EAF6',
    symbol: `<rect x="42" y="48" width="44" height="54" rx="4" fill="#C5CAE9" stroke="#283593" stroke-width="3"/>
             <circle cx="64" cy="74" r="14" stroke="#283593" stroke-width="3" fill="#E8EAF6"/>
             <polygon points="64,64 67,71 74,71 68,76 71,83 64,79 57,83 60,76 54,71 61,71" fill="#3F51B5"/>`
  }
];

function generateCategorySvg(c) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128">
  <rect width="128" height="128" rx="20" fill="${c.bg}" stroke="${c.color}" stroke-width="2" stroke-opacity="0.3"/>
  <g transform="translate(0, 0)">
    ${c.symbol}
  </g>
</svg>`;
}

// 45 Product Records (32 Product Families + distinct physical pack sizes)
const PRODUCTS = [
  // 1. DailyHarvest Super Basmati Rice (1kg, 5kg, 10kg)
  {
    file: 'prod_01.svg',
    name: 'DailyHarvest Super Basmati Rice 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '1 KG',
    color: '#2E7D32',
    accent: '#F9A825',
    shape: 'pouch'
  },
  {
    file: 'prod_02.svg',
    name: 'DailyHarvest Super Basmati Rice 5 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '5 KG',
    color: '#2E7D32',
    accent: '#F9A825',
    shape: 'bag'
  },
  {
    file: 'prod_03.svg',
    name: 'DailyHarvest Super Basmati Rice 10 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '10 KG',
    color: '#2E7D32',
    accent: '#F9A825',
    shape: 'sack'
  },
  // 2. DailyHarvest Kainat 1121 Sella Rice (1kg, 5kg)
  {
    file: 'prod_04.svg',
    name: 'DailyHarvest Kainat 1121 Sella Rice 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '1 KG',
    color: '#1B5E20',
    accent: '#FFD54F',
    shape: 'pouch'
  },
  {
    file: 'prod_05.svg',
    name: 'DailyHarvest Kainat 1121 Sella Rice 5 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '5 KG',
    color: '#1B5E20',
    accent: '#FFD54F',
    shape: 'bag'
  },
  // 3. DailyHarvest Premium Dal Chana 1kg
  {
    file: 'prod_06.svg',
    name: 'DailyHarvest Premium Dal Chana 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '1 KG',
    color: '#F57F17',
    accent: '#FFF59D',
    shape: 'transparent_pouch'
  },
  // 4. DailyHarvest Washed Dal Masoor 1kg
  {
    file: 'prod_07.svg',
    name: 'DailyHarvest Washed Dal Masoor 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Grains & Staples',
    pack: '1 KG',
    color: '#E64A19',
    accent: '#FFCCBC',
    shape: 'transparent_pouch'
  },
  // 5. DailyHarvest Chakki Whole Wheat Atta (5kg, 10kg)
  {
    file: 'prod_08.svg',
    name: 'DailyHarvest Chakki Whole Wheat Atta 5 kg',
    brand: 'DailyHarvest Foods',
    category: 'Flours & Baking',
    pack: '5 KG',
    color: '#D84315',
    accent: '#FFE0B2',
    shape: 'flour_bag'
  },
  {
    file: 'prod_09.svg',
    name: 'DailyHarvest Chakki Whole Wheat Atta 10 kg',
    brand: 'DailyHarvest Foods',
    category: 'Flours & Baking',
    pack: '10 KG',
    color: '#D84315',
    accent: '#FFE0B2',
    shape: 'flour_sack'
  },
  // 6. DailyHarvest Super Fine Maida 1kg
  {
    file: 'prod_10.svg',
    name: 'DailyHarvest Super Fine Maida 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Flours & Baking',
    pack: '1 KG',
    color: '#795548',
    accent: '#EFEBE9',
    shape: 'flour_bag'
  },
  // 7. DailyHarvest Refined White Sugar (1kg, 5kg)
  {
    file: 'prod_11.svg',
    name: 'DailyHarvest Refined White Sugar 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Flours & Baking',
    pack: '1 KG',
    color: '#00838F',
    accent: '#E0F7FA',
    shape: 'pouch'
  },
  {
    file: 'prod_12.svg',
    name: 'DailyHarvest Refined White Sugar 5 kg',
    brand: 'DailyHarvest Foods',
    category: 'Flours & Baking',
    pack: '5 KG',
    color: '#00838F',
    accent: '#E0F7FA',
    shape: 'bag'
  },
  // 8. DailyHarvest Pure Desi Shakkar 1kg
  {
    file: 'prod_13.svg',
    name: 'DailyHarvest Pure Desi Shakkar 1 kg',
    brand: 'DailyHarvest Foods',
    category: 'Flours & Baking',
    pack: '1 KG',
    color: '#4E342E',
    accent: '#D7CCC8',
    shape: 'pouch'
  },
  // 9. Khyber Gold Danedar Tea (450g, 900g)
  {
    file: 'prod_14.svg',
    name: 'Khyber Gold Danedar Tea 450g',
    brand: 'Khyber Tea & Spices',
    category: 'Beverages & Refreshments',
    pack: '450g',
    color: '#B71C1C',
    accent: '#FFD54F',
    shape: 'tea_pouch'
  },
  {
    file: 'prod_15.svg',
    name: 'Khyber Gold Danedar Tea 900g',
    brand: 'Khyber Tea & Spices',
    category: 'Beverages & Refreshments',
    pack: '900g',
    color: '#B71C1C',
    accent: '#FFD54F',
    shape: 'tea_pouch'
  },
  // 10. Khyber Royal Peshawari Green Tea Kahwa 250g
  {
    file: 'prod_16.svg',
    name: 'Khyber Royal Peshawari Green Tea Kahwa 250g',
    brand: 'Khyber Tea & Spices',
    category: 'Beverages & Refreshments',
    pack: '250g',
    color: '#2E7D32',
    accent: '#C8E6C9',
    shape: 'tea_box'
  },
  // 11. Khyber Traditional Rooh-e-Sharbat Syrup 800ml
  {
    file: 'prod_17.svg',
    name: 'Khyber Traditional Rooh-e-Sharbat Syrup 800ml',
    brand: 'Khyber Tea & Spices',
    category: 'Beverages & Refreshments',
    pack: '800ml',
    color: '#C2185B',
    accent: '#F8BBD0',
    shape: 'syrup_bottle'
  },
  // 12. Khyber Pure Mango Nectar Fruit Juice 1L
  {
    file: 'prod_18.svg',
    name: 'Khyber Pure Mango Nectar Fruit Juice 1 Litre',
    brand: 'Khyber Tea & Spices',
    category: 'Beverages & Refreshments',
    pack: '1 Litre',
    color: '#FF8F00',
    accent: '#FFF8E1',
    shape: 'juice_brik'
  },
  // 13. Indus Pure UHT Full Cream Milk (1L, 250ml)
  {
    file: 'prod_19.svg',
    name: 'Indus Pure UHT Full Cream Milk 1 Litre',
    brand: 'Indus Dairy & Oils',
    category: 'Dairy & Breakfast',
    pack: '1 Litre',
    color: '#0D47A1',
    accent: '#E3F2FD',
    shape: 'milk_tetra'
  },
  {
    file: 'prod_20.svg',
    name: 'Indus Pure UHT Full Cream Milk 250ml',
    brand: 'Indus Dairy & Oils',
    category: 'Dairy & Breakfast',
    pack: '250ml',
    color: '#0D47A1',
    accent: '#E3F2FD',
    shape: 'small_tetra'
  },
  // 14. Indus Fresh Heavy Dairy Cream 200ml
  {
    file: 'prod_21.svg',
    name: 'Indus Fresh Heavy Dairy Cream 200ml',
    brand: 'Indus Dairy & Oils',
    category: 'Dairy & Breakfast',
    pack: '200ml',
    color: '#1565C0',
    accent: '#FFF9C4',
    shape: 'small_tetra'
  },
  // 15. Indus Traditional Salted Table Butter 200g
  {
    file: 'prod_22.svg',
    name: 'Indus Traditional Salted Table Butter 200g',
    brand: 'Indus Dairy & Oils',
    category: 'Dairy & Breakfast',
    pack: '200g',
    color: '#F9A825',
    accent: '#FFFDE7',
    shape: 'butter_box'
  },
  // 16. Indus Rich Hazelnut & Cocoa Spread 350g
  {
    file: 'prod_23.svg',
    name: 'Indus Rich Hazelnut & Cocoa Spread 350g',
    brand: 'Indus Dairy & Oils',
    category: 'Dairy & Breakfast',
    pack: '350g',
    color: '#3E2723',
    accent: '#D7CCC8',
    shape: 'jar'
  },
  // 17. Indus Premium Banaspati Ghee (1kg Pouch, 5kg Tin)
  {
    file: 'prod_24.svg',
    name: 'Indus Premium Banaspati Ghee 1 kg Pouch',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '1 kg',
    color: '#2E7D32',
    accent: '#F9A825',
    shape: 'oil_pouch'
  },
  {
    file: 'prod_25.svg',
    name: 'Indus Premium Banaspati Ghee 5 kg Tin',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '5 kg',
    color: '#2E7D32',
    accent: '#F9A825',
    shape: 'tin'
  },
  // 18. Indus Pure Desi Cow Ghee 1kg Tin
  {
    file: 'prod_26.svg',
    name: 'Indus Pure Desi Cow Ghee 1 kg Tin',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '1 kg',
    color: '#E65100',
    accent: '#FFE082',
    shape: 'tin'
  },
  // 19. Indus Gold Canola Cooking Oil (1L, 5L)
  {
    file: 'prod_27.svg',
    name: 'Indus Gold Canola Cooking Oil 1 Litre Pouch',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '1 Litre',
    color: '#F57F17',
    accent: '#FFF9C4',
    shape: 'oil_pouch'
  },
  {
    file: 'prod_28.svg',
    name: 'Indus Gold Canola Cooking Oil 5 Litre Bottle',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '5 Litres',
    color: '#F57F17',
    accent: '#FFF9C4',
    shape: 'oil_can'
  },
  // 20. Indus SunPure Refined Sunflower Oil (1L, 5L)
  {
    file: 'prod_29.svg',
    name: 'Indus SunPure Refined Sunflower Oil 1 Litre Pouch',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '1 Litre',
    color: '#FF8F00',
    accent: '#FFF8E1',
    shape: 'oil_pouch'
  },
  {
    file: 'prod_30.svg',
    name: 'Indus SunPure Refined Sunflower Oil 5 Litre Bottle',
    brand: 'Indus Dairy & Oils',
    category: 'Cooking Oils & Ghee',
    pack: '5 Litres',
    color: '#FF8F00',
    accent: '#FFF8E1',
    shape: 'oil_can'
  },
  // 21. Khyber Special Bombay Biryani Masala 50g
  {
    file: 'prod_31.svg',
    name: 'Khyber Special Bombay Biryani Masala 50g',
    brand: 'Khyber Tea & Spices',
    category: 'Spices & Seasonings',
    pack: '50g',
    color: '#C62828',
    accent: '#FFEBEE',
    shape: 'spice_box'
  },
  // 22. Khyber Nihari & Haleem Recipe Spice Mix 50g
  {
    file: 'prod_32.svg',
    name: 'Khyber Nihari & Haleem Recipe Spice Mix 50g',
    brand: 'Khyber Tea & Spices',
    category: 'Spices & Seasonings',
    pack: '50g',
    color: '#BF360C',
    accent: '#FBE9E7',
    shape: 'spice_box'
  },
  // 23. Khyber Pure Kashmiri Lal Mirch Powder 200g
  {
    file: 'prod_33.svg',
    name: 'Khyber Pure Kashmiri Lal Mirch Powder 200g',
    brand: 'Khyber Tea & Spices',
    category: 'Spices & Seasonings',
    pack: '200g',
    color: '#D50000',
    accent: '#FFCDD2',
    shape: 'spice_pouch'
  },
  // 24. Khyber Golden Turmeric Haldi Powder 200g
  {
    file: 'prod_34.svg',
    name: 'Khyber Golden Turmeric Haldi Powder 200g',
    brand: 'Khyber Tea & Spices',
    category: 'Spices & Seasonings',
    pack: '200g',
    color: '#FF8F00',
    accent: '#FFF8E1',
    shape: 'spice_pouch'
  },
  // 25. PakClean Floral Glow Beauty Soap 140g
  {
    file: 'prod_35.svg',
    name: 'PakClean Floral Glow Beauty Soap 140g',
    brand: 'PakClean & Care',
    category: 'Personal Care & Hygiene',
    pack: '140g',
    color: '#D81B60',
    accent: '#FCE4EC',
    shape: 'soap_box'
  },
  // 26. PakClean Total Antibacterial Health Soap 125g
  {
    file: 'prod_36.svg',
    name: 'PakClean Total Antibacterial Health Soap 125g',
    brand: 'PakClean & Care',
    category: 'Personal Care & Hygiene',
    pack: '125g',
    color: '#C62828',
    accent: '#FFEBEE',
    shape: 'soap_box'
  },
  // 27. PakClean Anti-Dandruff Herbal Shampoo (360ml, 180ml)
  {
    file: 'prod_37.svg',
    name: 'PakClean Anti-Dandruff Herbal Shampoo 360ml',
    brand: 'PakClean & Care',
    category: 'Personal Care & Hygiene',
    pack: '360ml',
    color: '#00838F',
    accent: '#E0F7FA',
    shape: 'shampoo_bottle'
  },
  {
    file: 'prod_38.svg',
    name: 'PakClean Anti-Dandruff Herbal Shampoo 180ml',
    brand: 'PakClean & Care',
    category: 'Personal Care & Hygiene',
    pack: '180ml',
    color: '#00838F',
    accent: '#E0F7FA',
    shape: 'shampoo_bottle'
  },
  // 28. PakClean Silky Shine Smooth Shampoo 360ml
  {
    file: 'prod_39.svg',
    name: 'PakClean Silky Shine Smooth Shampoo 360ml',
    brand: 'PakClean & Care',
    category: 'Personal Care & Hygiene',
    pack: '360ml',
    color: '#6A1B9A',
    accent: '#F3E5F5',
    shape: 'shampoo_bottle'
  },
  // 29. PakClean Ultra White Washing Powder (1kg, 3kg)
  {
    file: 'prod_40.svg',
    name: 'PakClean Ultra White Washing Powder 1 kg',
    brand: 'PakClean & Care',
    category: 'Laundry & Household Cleaning',
    pack: '1 KG',
    color: '#1565C0',
    accent: '#E3F2FD',
    shape: 'detergent_pouch'
  },
  {
    file: 'prod_41.svg',
    name: 'PakClean Ultra White Washing Powder 3 kg',
    brand: 'PakClean & Care',
    category: 'Laundry & Household Cleaning',
    pack: '3 KG',
    color: '#1565C0',
    accent: '#E3F2FD',
    shape: 'detergent_bag'
  },
  // 30. PakClean Matic Machine Detergent 2 kg
  {
    file: 'prod_42.svg',
    name: 'PakClean Matic Machine Detergent 2 kg',
    brand: 'PakClean & Care',
    category: 'Laundry & Household Cleaning',
    pack: '2 KG',
    color: '#00695C',
    accent: '#E0F2F1',
    shape: 'detergent_box'
  },
  // 31. PakClean Lemon Power Dishwash Liquid 500ml
  {
    file: 'prod_43.svg',
    name: 'PakClean Lemon Power Dishwash Liquid 500ml',
    brand: 'PakClean & Care',
    category: 'Laundry & Household Cleaning',
    pack: '500ml',
    color: '#F9A825',
    accent: '#FFFDE7',
    shape: 'dishwash_bottle'
  },
  // 32. PakClean Citrus Floor Disinfectant 1 Litre
  {
    file: 'prod_44.svg',
    name: 'PakClean Citrus Floor Disinfectant 1 Litre',
    brand: 'PakClean & Care',
    category: 'Laundry & Household Cleaning',
    pack: '1 Litre',
    color: '#EF6C00',
    accent: '#FFF3E0',
    shape: 'cleaner_bottle'
  }
];

function generateProductSvg(p) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 280" width="280" height="280">
  <defs>
    <linearGradient id="bg_${p.file.replace('.svg','')}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${p.accent}"/>
      <stop offset="100%" stop-color="#FFFFFF"/>
    </linearGradient>
    <filter id="packShadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="4" stdDeviation="4" flood-opacity="0.15"/>
    </filter>
  </defs>
  <!-- Background -->
  <rect width="280" height="280" rx="16" fill="url(#bg_${p.file.replace('.svg','')})" stroke="${p.color}" stroke-width="1.5" stroke-opacity="0.25"/>
  
  <!-- Category banner top right -->
  <rect x="15" y="14" width="120" height="22" rx="11" fill="${p.color}" fill-opacity="0.12"/>
  <text x="75" y="29" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="9.5" font-weight="700" fill="${p.color}" text-anchor="middle" letter-spacing="0.3">${p.category.toUpperCase().slice(0, 18)}</text>

  <!-- Pack Size Pill top right -->
  <rect x="195" y="14" width="70" height="24" rx="12" fill="${p.color}"/>
  <text x="230" y="30" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="11" font-weight="800" fill="#FFFFFF" text-anchor="middle">${p.pack}</text>

  <!-- Central Packaging Illustration Container -->
  <g filter="url(#packShadow)" transform="translate(140, 125)">
    <!-- Packaging Base -->
    <rect x="-55" y="-65" width="110" height="130" rx="12" fill="#FFFFFF" stroke="${p.color}" stroke-width="2.5"/>
    
    <!-- Top Packaging Seal / Cap -->
    <rect x="-40" y="-76" width="80" height="14" rx="4" fill="${p.color}"/>
    
    <!-- Decorative Brand Ribbon -->
    <rect x="-55" y="-45" width="110" height="34" fill="${p.color}"/>
    <text x="0" y="-24" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="10.5" font-weight="800" fill="#FFFFFF" text-anchor="middle" letter-spacing="0.5">${p.brand.split(' ')[0]}</text>
    
    <!-- Center Emblem -->
    <circle cx="0" cy="12" r="22" fill="${p.accent}" stroke="${p.color}" stroke-width="1.5"/>
    <text x="0" y="16" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="9" font-weight="800" fill="${p.color}" text-anchor="middle">100%</text>

    <!-- Bottom Barcode Representation -->
    <g transform="translate(-36, 42)">
      <line x1="0" y1="0" x2="0" y2="14" stroke="#333" stroke-width="1.5"/>
      <line x1="4" y1="0" x2="4" y2="14" stroke="#333" stroke-width="2.5"/>
      <line x1="9" y1="0" x2="9" y2="14" stroke="#333" stroke-width="1"/>
      <line x1="13" y1="0" x2="13" y2="14" stroke="#333" stroke-width="2"/>
      <line x1="18" y1="0" x2="18" y2="14" stroke="#333" stroke-width="3"/>
      <line x1="24" y1="0" x2="24" y2="14" stroke="#333" stroke-width="1.5"/>
      <line x1="29" y1="0" x2="29" y2="14" stroke="#333" stroke-width="2"/>
      <line x1="35" y1="0" x2="35" y2="14" stroke="#333" stroke-width="1"/>
      <line x1="40" y1="0" x2="40" y2="14" stroke="#333" stroke-width="2.5"/>
      <line x1="46" y1="0" x2="46" y2="14" stroke="#333" stroke-width="1.5"/>
      <line x1="52" y1="0" x2="52" y2="14" stroke="#333" stroke-width="2.5"/>
      <line x1="58" y1="0" x2="58" y2="14" stroke="#333" stroke-width="1"/>
      <line x1="64" y1="0" x2="64" y2="14" stroke="#333" stroke-width="2"/>
      <line x1="70" y1="0" x2="70" y2="14" stroke="#333" stroke-width="3"/>
    </g>
  </g>

  <!-- Product Title at Bottom -->
  <text x="140" y="240" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="12" font-weight="700" fill="#212529" text-anchor="middle">${p.name.length > 32 ? p.name.slice(0, 30) + '...' : p.name}</text>
  <text x="140" y="258" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="10" font-weight="600" fill="${p.color}" text-anchor="middle">${p.brand}</text>
</svg>`;
}

// Generate all Brand SVGs
BRANDS.forEach(b => {
  fs.writeFileSync(path.join(brandsDir, b.file), generateBrandSvg(b), 'utf8');
  console.log(`Generated Brand: ${b.file}`);
});

// Generate all Category SVGs
CATEGORIES.forEach(c => {
  fs.writeFileSync(path.join(catsDir, c.file), generateCategorySvg(c), 'utf8');
  console.log(`Generated Category: ${c.file}`);
});

// Generate all Product SVGs
PRODUCTS.forEach(p => {
  fs.writeFileSync(path.join(prodsDir, p.file), generateProductSvg(p), 'utf8');
  console.log(`Generated Product: ${p.file}`);
});

console.log(`\nSuccessfully created:\n- ${BRANDS.length} Brand logos\n- ${CATEGORIES.length} Category icons\n- ${PRODUCTS.length} Product images (all 32 families covered)`);

