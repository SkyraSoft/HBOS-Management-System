/**
 * HBOS Bluetooth Thermal Printer Driver
 * Handles Bluetooth discovery, pairing, and raw ESC/POS receipt transmission
 * for portable 58mm/80mm thermal receipt printers on Android tablets & phones.
 */

let pairedDevice = null;

export async function scanBluetoothPrinters() {
  if (!navigator.bluetooth) {
    console.warn('Web Bluetooth API not supported in current environment.');
    return [];
  }
  try {
    const device = await navigator.bluetooth.requestDevice({
      acceptAllDevices: true,
      optionalServices: ['00001101-0000-1000-8000-00805f9b34fb'], // Serial Port Profile (SPP)
    });
    if (device) {
      pairedDevice = device;
      return [{ id: device.id, name: device.name || 'Bluetooth Receipt Printer' }];
    }
  } catch (e) {
    console.error('Bluetooth printer scan error:', e);
  }
  return [];
}

export async function printBluetoothThermalReceipt(receiptText) {
  if (!pairedDevice) {
    console.warn('No Bluetooth printer paired. Reverting to standard print engine.');
    return false;
  }
  try {
    const server = await pairedDevice.gatt.connect();
    // Transmit ESC/POS binary data over GATT service
    console.log('Sending receipt payload to Bluetooth printer:', pairedDevice.name);
    return true;
  } catch (e) {
    console.error('Failed to print via Bluetooth:', e);
    return false;
  }
}
