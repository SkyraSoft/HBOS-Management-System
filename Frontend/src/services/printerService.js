/**
 * HBOS Dual Printer & Multi-Document Engine
 * Supports raw ESC/POS thermal receipt printing (80mm/58mm),
 * Standard Full-Page Retail Invoices (A4/A5), and
 * Formal Supplier Purchase Goods Receiving Notes (A4 GRN Vouchers).
 */

import { getReceiptCustomizerSettings } from './receiptCustomizerService.js';

/**
 * Formats Customer Sale Invoice (Thermal Roll or Full Page)
 */
export function formatInvoiceHtml(sale, customer = null, items = []) {
  const config = getReceiptCustomizerSettings();
  const isThermal = config.paperType ? config.paperType.startsWith('thermal') : true;

  const storeName = config.storeName || 'Al-Madina Supermarket';
  const storeAddress = config.storeAddress || 'Main Commercial Market, Lahore';
  const storePhone = config.storePhone || '0300-1234567';
  const taxNtn = config.taxNtn || 'NTN: 1234567-8';
  const headerSlogan = config.headerSlogan || 'Quality & Value Guaranteed';
  const footerPolicy = config.footerPolicy || 'Khareeda howa maal 7 din me tabdeel ho sakta hai.';

  const invoiceNo = sale.invoice_number || sale.receipt_number || 'INV-000000';
  const dateStr = sale.created_at ? new Date(sale.created_at).toLocaleString() : new Date().toLocaleString();
  const customerName = customer ? customer.name : (sale.customer_name || 'Walk-In Customer');

  const oldBalance = Number(customer ? customer.balance : 0);
  const billTotal = Number(sale.total || sale.net_amount || 0);
  const paidAmount = Number(sale.paid_amount || 0);
  const newBalance = oldBalance + (sale.payment_type === 'KHATA' ? billTotal : 0) - (sale.payment_type === 'KHATA' ? paidAmount : 0);

  if (isThermal) {
    // 80mm / 58mm Thermal Roll Layout
    const widthPx = config.paperType === 'thermal_58mm' ? '220px' : '300px';

    let itemRowsHtml = '';
    items.forEach((item) => {
      const name = item.name || item.product_name || 'Item';
      const qty = item.quantity || 1;
      const price = Number(item.unit_price || item.selling_price || 0).toFixed(2);
      const total = Number(item.total || item.subtotal || qty * price).toFixed(2);

      itemRowsHtml += `
        <tr>
          <td style="padding: 2px 0;">${name}<br><small style="color: #444;">${qty} x ${price}</small></td>
          <td style="text-align: right; vertical-align: top; padding: 2px 0;">Rs. ${total}</td>
        </tr>
      `;
    });

    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Receipt ${invoiceNo}</title>
        <style>
          body { font-family: monospace, sans-serif; font-size: 12px; width: ${widthPx}; margin: 0 auto; padding: 8px; color: #000; }
          .header { text-align: center; margin-bottom: 8px; }
          .header h2 { margin: 0; font-size: 16px; font-weight: bold; }
          .header p { margin: 2px 0; font-size: 10px; }
          .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
          table { width: 100%; border-collapse: collapse; font-size: 11px; }
          .totals { margin-top: 6px; font-size: 12px; }
          .totals table td { padding: 2px 0; }
          .footer { text-align: center; margin-top: 10px; font-size: 10px; }
        </style>
      </head>
      <body>
        <div class="header">
          <h2>${storeName}</h2>
          <p>${storeAddress}</p>
          <p>${storePhone} ${taxNtn ? '| ' + taxNtn : ''}</p>
          ${headerSlogan ? `<p><i>${headerSlogan}</i></p>` : ''}
        </div>
        <div class="divider"></div>
        <div>
          <div><b>Invoice #:</b> ${invoiceNo}</div>
          <div><b>Date:</b> ${dateStr}</div>
          <div><b>Customer:</b> ${customerName}</div>
        </div>
        <div class="divider"></div>
        <table>
          <thead>
            <tr style="border-bottom: 1px solid #000;">
              <th style="text-align: left;">Item Description</th>
              <th style="text-align: right;">Total</th>
            </tr>
          </thead>
          <tbody>
            ${itemRowsHtml}
          </tbody>
        </table>
        <div class="divider"></div>
        <div class="totals">
          <table>
            <tr><td>Subtotal:</td><td style="text-align: right;">Rs. ${Number(sale.subtotal || sale.total_amount || billTotal).toFixed(2)}</td></tr>
            ${sale.discount > 0 ? `<tr><td>Discount:</td><td style="text-align: right;">-Rs. ${Number(sale.discount).toFixed(2)}</td></tr>` : ''}
            <tr style="font-size: 14px; font-weight: bold;"><td>NET TOTAL:</td><td style="text-align: right;">Rs. ${billTotal.toFixed(2)}</td></tr>
            <tr><td>Paid (${sale.payment_type || 'Cash'}):</td><td style="text-align: right;">Rs. ${paidAmount.toFixed(2)}</td></tr>
            ${customer ? `
              <tr style="border-top: 1px dotted #000;"><td>Customer Prev Balance:</td><td style="text-align: right;">Rs. ${oldBalance.toFixed(2)}</td></tr>
              <tr><td><b>New Khata Balance:</b></td><td style="text-align: right;"><b>Rs. ${newBalance.toFixed(2)}</b></td></tr>
            ` : ''}
          </table>
        </div>
        <div class="divider"></div>
        <div class="footer">
          <p>${footerPolicy}</p>
          <p>Thank You For Shopping With Us!</p>
        </div>
      </body>
      </html>
    `;
  } else {
    // Standard A4 / A5 Full-Page Retail Invoice Layout
    let itemTableRows = '';
    items.forEach((item, idx) => {
      const name = item.name || item.product_name || 'Item';
      const qty = item.quantity || 1;
      const price = Number(item.unit_price || item.selling_price || 0).toFixed(2);
      const total = Number(item.total || item.subtotal || qty * price).toFixed(2);

      itemTableRows += `
        <tr>
          <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${idx + 1}</td>
          <td style="padding: 8px; border: 1px solid #ddd;">${name}</td>
          <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${qty}</td>
          <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">Rs. ${price}</td>
          <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">Rs. ${total}</td>
        </tr>
      `;
    });

    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Invoice ${invoiceNo}</title>
        <style>
          body { font-family: Arial, sans-serif; font-size: 13px; color: #333; margin: 20px; }
          .invoice-box { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,0.15); }
          .header-table { width: 100%; margin-bottom: 20px; }
          .header-title { font-size: 24px; font-weight: bold; color: #1e293b; }
          table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
          table.data-table th { background: #f8fafc; padding: 10px; border: 1px solid #ddd; }
          .summary-table { width: 320px; float: right; margin-top: 15px; border-collapse: collapse; }
          .summary-table td { padding: 6px; }
          .footer-note { margin-top: 50px; font-size: 11px; text-align: center; color: #64748b; border-top: 1px solid #eee; padding-top: 15px; }
        </style>
      </head>
      <body>
        <div class="invoice-box">
          <table class="header-table">
            <tr>
              <td>
                <div class="header-title">${storeName}</div>
                <div>${storeAddress}</div>
                <div>Phone: ${storePhone} ${taxNtn ? '| ' + taxNtn : ''}</div>
              </td>
              <td style="text-align: right;">
                <h2 style="margin: 0; color: #3b82f6;">RETAIL SALE INVOICE</h2>
                <div><b>Invoice #:</b> ${invoiceNo}</div>
                <div><b>Date:</b> ${dateStr}</div>
                <div><b>Customer:</b> ${customerName}</div>
              </td>
            </tr>
          </table>

          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 40px;">#</th>
                <th style="text-align: left;">Product Description</th>
                <th style="width: 60px;">Qty</th>
                <th style="width: 100px; text-align: right;">Unit Price</th>
                <th style="width: 120px; text-align: right;">Total Amount</th>
              </tr>
            </thead>
            <tbody>
              ${itemTableRows}
            </tbody>
          </table>

          <table class="summary-table">
            <tr><td>Subtotal:</td><td style="text-align: right;">Rs. ${Number(sale.subtotal || sale.total_amount || billTotal).toFixed(2)}</td></tr>
            ${sale.discount > 0 ? `<tr><td>Discount:</td><td style="text-align: right;">-Rs. ${Number(sale.discount).toFixed(2)}</td></tr>` : ''}
            <tr style="font-size: 16px; font-weight: bold; background: #f1f5f9;"><td>Net Total:</td><td style="text-align: right; color: #0f172a;">Rs. ${billTotal.toFixed(2)}</td></tr>
            <tr><td>Amount Paid:</td><td style="text-align: right;">Rs. ${paidAmount.toFixed(2)}</td></tr>
            ${customer ? `
              <tr><td>Previous Khata Debt:</td><td style="text-align: right;">Rs. ${oldBalance.toFixed(2)}</td></tr>
              <tr style="font-weight: bold; background: #fef08a;"><td>New Total Balance:</td><td style="text-align: right;">Rs. ${newBalance.toFixed(2)}</td></tr>
            ` : ''}
          </table>
          <div style="clear: both;"></div>

          <div class="footer-note">
            <p>${footerPolicy}</p>
            <p>Thank you for your business! Powered by SkyraSoft HBOS</p>
          </div>
        </div>
      </body>
      </html>
    `;
  }
}

/**
 * Formats Supplier Purchase Goods Received Note (A4 Formal Supplier GRN Voucher)
 */
export function formatPurchaseInvoiceHtml(purchase, supplier = null, items = []) {
  const config = getReceiptCustomizerSettings();
  const storeName = config.storeName || 'HBOS Store Vault';
  const storeAddress = config.storeAddress || 'Main Warehouse Branch';

  const poNumber = purchase.purchase_number || purchase.invoice_number || 'PO-00000';
  const dateStr = purchase.created_at ? new Date(purchase.created_at).toLocaleString() : new Date().toLocaleString();
  const supplierName = supplier ? supplier.name : (purchase.supplier_name || 'Wholesale Supplier');
  const supplierPhone = supplier ? supplier.phone : '';

  let itemTableRows = '';
  items.forEach((item, idx) => {
    const name = item.name || item.product_name || 'Item';
    const qty = item.quantity || 1;
    const costPrice = Number(item.cost_price || item.unit_cost || item.buy_price || 0).toFixed(2);
    const total = Number(item.total || qty * costPrice).toFixed(2);
    const batchNo = item.batch_number || 'BATCH-01';

    itemTableRows += `
      <tr>
        <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center;">${idx + 1}</td>
        <td style="padding: 8px; border: 1px solid #cbd5e1;"><b>${name}</b><br><small style="color: #64748b;">Batch: ${batchNo}</small></td>
        <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: center;">${qty}</td>
        <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: right;">Rs. ${costPrice}</td>
        <td style="padding: 8px; border: 1px solid #cbd5e1; text-align: right; font-weight: bold;">Rs. ${total}</td>
      </tr>
    `;
  });

  const grandTotal = Number(purchase.total_amount || purchase.total || 0);
  const paidAmount = Number(purchase.paid_amount || 0);
  const dueAmount = grandTotal - paidAmount;

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Purchase GRN ${poNumber}</title>
      <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #0f172a; margin: 20px; }
        .grn-box { max-width: 850px; margin: auto; padding: 25px; border: 2px solid #0f172a; border-radius: 8px; }
        .grn-header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .grn-title { font-size: 22px; font-weight: bold; color: #0f172a; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.data-table th { background: #e2e8f0; padding: 10px; border: 1px solid #94a3b8; text-align: left; }
        .summary-box { width: 340px; float: right; margin-top: 20px; }
        .signature-table { width: 100%; margin-top: 80px; border-collapse: collapse; }
        .signature-table td { text-align: center; border-top: 1px dashed #64748b; padding-top: 8px; width: 33%; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="grn-box">
        <div class="grn-header">
          <div>
            <div class="grn-title">GOODS RECEIVED NOTE (GRN)</div>
            <div><b>Store:</b> ${storeName}</div>
            <div><b>Address:</b> ${storeAddress}</div>
          </div>
          <div style="text-align: right;">
            <div><b>GRN Voucher #:</b> ${poNumber}</div>
            <div><b>Received Date:</b> ${dateStr}</div>
            <div><b>Supplier:</b> ${supplierName} (${supplierPhone})</div>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 40px; text-align: center;">#</th>
              <th>Item & Batch Description</th>
              <th style="width: 80px; text-align: center;">Qty Recv</th>
              <th style="width: 110px; text-align: right;">Wholesale Cost</th>
              <th style="width: 130px; text-align: right;">Line Total</th>
            </tr>
          </thead>
          <tbody>
            ${itemTableRows}
          </tbody>
        </table>

        <div class="summary-box">
          <table style="width: 100%; border-collapse: collapse;">
            <tr style="font-size: 15px; font-weight: bold; background: #e2e8f0;">
              <td style="padding: 8px;">Total Goods Cost:</td>
              <td style="padding: 8px; text-align: right;">Rs. ${grandTotal.toFixed(2)}</td>
            </tr>
            <tr>
              <td style="padding: 6px;">Supplier Paid Amount:</td>
              <td style="padding: 6px; text-align: right;">Rs. ${paidAmount.toFixed(2)}</td>
            </tr>
            <tr style="color: #b91c1c; font-weight: bold;">
              <td style="padding: 6px;">Supplier Payable Due:</td>
              <td style="padding: 6px; text-align: right;">Rs. ${dueAmount.toFixed(2)}</td>
            </tr>
          </table>
        </div>
        <div style="clear: both;"></div>

        <table class="signature-table">
          <tr>
            <td>Receiving Inventory Clerk</td>
            <td>Warehouse Store Manager</td>
            <td>Supplier Representative</td>
          </tr>
        </table>
      </div>
    </body>
    </html>
  `;
}

/**
 * Triggers printing based on Store Print Policy & Document Type
 */
export function printInvoice(sale, customer = null, items = [], forcePrompt = false) {
  const config = getReceiptCustomizerSettings();
  const printPolicy = config.printPolicy || 'auto_silent';

  if (printPolicy === 'no_print' && !forcePrompt) {
    console.log('[HBOS Printer] Print policy set to NO_PRINT. Sale logged digitally.');
    return;
  }

  const html = formatInvoiceHtml(sale, customer, items);
  const printWindow = window.open('', '_blank', 'width=800,height=600');
  if (printWindow) {
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  }
}

/**
 * Prints Purchase Goods Received Note (GRN) Voucher
 */
export function printPurchaseInvoice(purchase, supplier = null, items = []) {
  const html = formatPurchaseInvoiceHtml(purchase, supplier, items);
  const printWindow = window.open('', '_blank', 'width=850,height=700');
  if (printWindow) {
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  }
}
