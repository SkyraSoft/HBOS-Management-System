/**
 * HBOS Receipt Customizer Engine
 * Stores user preferences for invoice layout, header/footer slogans,
 * NTN tax registration, WhatsApp helpline, and column visibility toggles.
 */

const RECEIPT_SETTINGS_KEY = 'hbos_receipt_customizer_settings';

const DEFAULT_SETTINGS = {
  storeName: 'Al-Madina Supermarket',
  storeAddress: 'Main Retail Market, Saddar, Lahore',
  storePhone: '0300-1234567',
  taxNtn: 'NTN: 1234567-8',
  headerSlogan: 'Welcome to Al-Madina — Quality & Freshness Everyday!',
  footerPolicy: 'Goods once sold can be exchanged within 7 days with original receipt.',
  whatsappHelpline: '0300-1234567',
  showLogo: true,
  showUnitPrice: true,
  showDiscountCol: true,
  showSku: false,
  showCustomerBalance: true,
  showBarcodeQr: true,
  paperType: 'thermal_80mm', // 'thermal_80mm' | 'thermal_58mm' | 'standard_a4' | 'standard_a5'
};

export function getReceiptCustomizerSettings() {
  try {
    const raw = localStorage.getItem(RECEIPT_SETTINGS_KEY);
    if (raw) {
      return { ...DEFAULT_SETTINGS, ...JSON.parse(raw) };
    }
  } catch (e) {
    console.error('Failed to load receipt settings:', e);
  }
  return { ...DEFAULT_SETTINGS };
}

export function saveReceiptCustomizerSettings(settings) {
  try {
    const updated = { ...getReceiptCustomizerSettings(), ...settings };
    localStorage.setItem(RECEIPT_SETTINGS_KEY, JSON.stringify(updated));
    return updated;
  } catch (e) {
    console.error('Failed to save receipt settings:', e);
    return null;
  }
}
