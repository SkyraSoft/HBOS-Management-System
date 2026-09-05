<script>
        /* =====================================================
           ELEMENTS
        ===================================================== */

        const sidebar = document.getElementById("sidebar");
        const mobileMenu = document.getElementById("mobileMenu");

        const inventoryBtn = document.getElementById("inventoryBtn");
        const inventorySubmenu = document.getElementById("inventorySubmenu");
        const inventoryArrow = document.getElementById("inventoryArrow");

        const productsNav = document.getElementById("productsNav");
        const invoiceNav = document.getElementById("invoiceNav");

        const productsPage = document.getElementById("productsPage");
        const invoicePage = document.getElementById("invoicePage");
        const settingsPage = document.getElementById("settingsPage");
        const helpPage = document.getElementById("helpPage");

        const breadcrumbText =
            document.getElementById("breadcrumbText");

        const addProductBtn =
            document.getElementById("addProductBtn");

        const productPanel =
            document.getElementById("productPanel");

        const overlay =
            document.getElementById("overlay");

        const closePanel =
            document.getElementById("closePanel");

        const cancelPanel =
            document.getElementById("cancelPanel");

        const productForm =
            document.getElementById("productForm");

        const saveProduct =
            document.getElementById("saveProduct");

        const purchasePrice =
            document.getElementById("purchasePrice");

        const sellingPrice =
            document.getElementById("sellingPrice");

        const marginValue =
            document.getElementById("marginValue");

        const productSearch =
            document.getElementById("productSearch");

        const categoryFilter =
            document.getElementById("categoryFilter");

        const stockFilter =
            document.getElementById("stockFilter");

        const productTableBody =
            document.getElementById("productTableBody");

        const globalSearch =
            document.getElementById("globalSearch");

        const settingsBtn =
            document.getElementById("settingsBtn");

        const helpBtn =
            document.getElementById("helpBtn");

        const backProducts =
            document.getElementById("backProducts");

        const newProductBtn =
            document.getElementById("newProductBtn");

        const printInvoiceBtn =
            document.getElementById("printInvoiceBtn");

        const receiptPrint =
            document.getElementById("receiptPrint");

        const shareInvoice =
            document.getElementById("shareInvoice");

        const downloadInvoice =
            document.getElementById("downloadInvoice");

        const toast =
            document.getElementById("toast");


        /* =====================================================
           SIDEBAR
        ===================================================== */

        inventoryBtn.addEventListener("click", () => {

            inventorySubmenu.classList.toggle("show");
            inventoryArrow.classList.toggle("open");

        });


        mobileMenu.addEventListener("click", () => {

            sidebar.classList.toggle("open");

        });


        /* =====================================================
           PAGE SWITCHER
        ===================================================== */

        function hideAllPages() {

            productsPage.classList.add("hide");
            invoicePage.classList.remove("show");
            settingsPage.classList.remove("show");
            helpPage.classList.remove("show");

        }


        function showProducts() {

            hideAllPages();

            productsPage.classList.remove("hide");

            productsNav.classList.add("active");
            invoiceNav.classList.remove("active");

            breadcrumbText.textContent = "Products";

            sidebar.classList.remove("open");

        }


        function showInvoice() {

            hideAllPages();

            invoicePage.classList.add("show");

            productsNav.classList.remove("active");
            invoiceNav.classList.add("active");

            breadcrumbText.textContent = "Product Invoice";

            sidebar.classList.remove("open");

        }


        function showSettings() {

            hideAllPages();

            settingsPage.classList.add("show");

            productsNav.classList.remove("active");
            invoiceNav.classList.remove("active");

            breadcrumbText.textContent = "Settings";

            sidebar.classList.remove("open");

        }


        function showHelp() {

            hideAllPages();

            helpPage.classList.add("show");

            productsNav.classList.remove("active");
            invoiceNav.classList.remove("active");

            breadcrumbText.textContent = "Help";

            sidebar.classList.remove("open");

        }


        productsNav.addEventListener("click", showProducts);
        invoiceNav.addEventListener("click", showInvoice);
        settingsBtn.addEventListener("click", showSettings);
        helpBtn.addEventListener("click", showHelp);

        backProducts.addEventListener("click", showProducts);


        /* =====================================================
           PRODUCT PANEL
        ===================================================== */

        function openProductPanel() {

            overlay.classList.add("show");
            productPanel.classList.add("show");

        }


        function closeProductPanel() {

            overlay.classList.remove("show");
            productPanel.classList.remove("show");

        }


        addProductBtn.addEventListener(
            "click",
            openProductPanel
        );

        closePanel.addEventListener(
            "click",
            closeProductPanel
        );

        cancelPanel.addEventListener(
            "click",
            closeProductPanel
        );

        overlay.addEventListener(
            "click",
            closeProductPanel
        );


        /* =====================================================
           MARGIN CALCULATION
        ===================================================== */

        function calculateMargin() {

            const purchase =
                parseFloat(purchasePrice.value) || 0;

            const selling =
                parseFloat(sellingPrice.value) || 0;

            if (purchase > 0 && selling > 0) {

                const margin =
                    ((selling - purchase) / selling) * 100;

                marginValue.textContent =
                    margin.toFixed(1) + "%";

            } else {

                marginValue.textContent = "--%";

            }

        }


        purchasePrice.addEventListener(
            "input",
            calculateMargin
        );

        sellingPrice.addEventListener(
            "input",
            calculateMargin
        );


        /* =====================================================
           SAVE PRODUCT
        ===================================================== */

        saveProduct.addEventListener("click", () => {

            if (!productForm.checkValidity()) {

                productForm.reportValidity();

                return;

            }


            const name =
                document.getElementById("productName").value.trim();

            const sku =
                document.getElementById("sku").value.trim();

            const category =
                document.getElementById("category").value;

            const purchase =
                parseFloat(
                    document.getElementById("purchasePrice").value
                ) || 0;

            const selling =
                parseFloat(
                    document.getElementById("sellingPrice").value
                ) || 0;

            const stock =
                parseInt(
                    document.getElementById("stock").value
                ) || 0;


            /* Add product to table */

            const row =
                document.createElement("tr");

            row.dataset.category = category;

            row.dataset.stock =
                stock <= 5 ? "Low Stock" : "In Stock";


            const stockBadge =
                stock <= 5
                    ? '<span class="badge red">Low Stock</span>'
                    : '<span class="badge green">In Stock</span>';


            row.innerHTML = `

      <td>

        <div class="product-cell">

          <div class="product-image">
            📦
          </div>

          <div>

            <div class="product-name">
              ${escapeHTML(name)}
            </div>

            <div class="product-sku">
              ${escapeHTML(sku)}
            </div>

          </div>

        </div>

      </td>

      <td>
        ${escapeHTML(sku)}
      </td>

      <td>
        ${escapeHTML(category)}
      </td>

      <td>
        PKR ${formatMoney(purchase)}
      </td>

      <td>
        PKR ${formatMoney(selling)}
      </td>

      <td>
        ${stock}
      </td>

      <td>
        ${stockBadge}
      </td>

      <td>
        <button class="action-btn">
          View
        </button>
      </td>

    `;


            productTableBody.prepend(row);


            /* Update total */

            const totalProducts =
                document.getElementById("totalProducts");

            const currentTotal =
                parseInt(
                    totalProducts.textContent.replace(/,/g, "")
                ) || 0;

            totalProducts.textContent =
                (currentTotal + 1).toLocaleString();


            /* Invoice data */

            const invoiceNo =
                "#INV-" +
                Math.floor(
                    1000 + Math.random() * 9000
                );


            document.getElementById(
                "invoiceNumber"
            ).textContent = invoiceNo;

            document.getElementById(
                "receiptInvoice"
            ).textContent = invoiceNo;

            document.getElementById(
                "invoiceProduct"
            ).textContent = name;

            document.getElementById(
                "invoiceSku"
            ).textContent = sku;

            document.getElementById(
                "invoiceCategory"
            ).textContent = category;

            document.getElementById(
                "invoiceQty"
            ).textContent = stock;

            const total =
                purchase * stock;

            document.getElementById(
                "invoiceTotal"
            ).textContent =
                "PKR " + formatMoney(total);


            document.getElementById(
                "receiptProduct"
            ).textContent = name;

            document.getElementById(
                "receiptQty"
            ).textContent = stock;

            document.getElementById(
                "receiptAmount"
            ).textContent =
                formatMoney(total);

            document.getElementById(
                "receiptPurchase"
            ).textContent =
                "PKR " + formatMoney(purchase);

            document.getElementById(
                "receiptQuantity"
            ).textContent = stock;

            document.getElementById(
                "receiptTotal"
            ).textContent =
                "PKR " + formatMoney(total);


            const now =
                new Date();

            document.getElementById(
                "receiptDate"
            ).textContent =
                now.toLocaleDateString() +
                " - " +
                now.toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit"
                });


            closeProductPanel();

            productForm.reset();

            marginValue.textContent = "--%";

            showInvoice();

            showToast(
                "Product added successfully."
            );

        });


        /* =====================================================
           NEW PRODUCT
        ===================================================== */

        newProductBtn.addEventListener("click", () => {

            showProducts();

            setTimeout(() => {

                openProductPanel();

            }, 100);

        });


        /* =====================================================
           SEARCH / FILTER
        ===================================================== */

        function filterProducts() {

            const search =
                productSearch.value
                    .toLowerCase()
                    .trim();

            const category =
                categoryFilter.value;

            const stock =
                stockFilter.value;


            const rows =
                productTableBody.querySelectorAll("tr");


            rows.forEach(row => {

                const text =
                    row.textContent.toLowerCase();

                const rowCategory =
                    row.dataset.category || "";

                const rowStock =
                    row.dataset.stock || "";


                const matchesSearch =
                    !search ||
                    text.includes(search);

                const matchesCategory =
                    !category ||
                    rowCategory === category;

                const matchesStock =
                    !stock ||
                    rowStock === stock;


                row.style.display =
                    matchesSearch &&
                        matchesCategory &&
                        matchesStock
                        ? ""
                        : "none";

            });

        }


        productSearch.addEventListener(
            "input",
            filterProducts
        );

        categoryFilter.addEventListener(
            "change",
            filterProducts
        );

        stockFilter.addEventListener(
            "change",
            filterProducts
        );


        globalSearch.addEventListener(
            "input",
            () => {

                productSearch.value =
                    globalSearch.value;

                showProducts();

                filterProducts();

            }
        );


        /* =====================================================
           PRINT
        ===================================================== */

        function printInvoice() {

            const receipt =
                document.getElementById("receipt").innerHTML;

            const printWindow =
                window.open(
                    "",
                    "_blank",
                    "width=500,height=700"
                );

            if (!printWindow) {

                showToast(
                    "Please allow pop-ups to print."
                );

                return;

            }


            printWindow.document.write(`

      <!DOCTYPE html>

      <html>

      <head>

        <title>HBOS Invoice</title>

        <style>

          body {
            font-family: Arial, sans-serif;
            padding: 30px;
          }

          .receipt {
            width: 350px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 20px;
          }

          table {
            width: 100%;
            border-collapse: collapse;
          }

          th,
          td {
            padding: 7px 0;
            text-align: left;
          }

          .receipt-head {
            text-align: center;
            border-bottom: 1px dashed #aaa;
            padding-bottom: 12px;
          }

          .receipt-total {
            border-top: 1px dashed #aaa;
            margin-top: 10px;
            padding-top: 10px;
          }

          .receipt-total div {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
          }

          .grand {
            font-weight: bold;
            font-size: 16px;
          }

          .receipt-footer {
            text-align: center;
            margin-top: 15px;
          }

        </style>

      </head>

      <body>

        <div class="receipt">
          ${receipt}
        </div>

        <script>

          window.onload = function() {
            window.print();
          };

        <\/script>

      </body>

      </html>

    `);

            printWindow.document.close();

        }


        printInvoiceBtn.addEventListener(
            "click",
            printInvoice
        );

        receiptPrint.addEventListener(
            "click",
            printInvoice
        );


        /* =====================================================
           SHARE
        ===================================================== */

        shareInvoice.addEventListener(
            "click",
            async () => {

                const product =
                    document.getElementById(
                        "invoiceProduct"
                    ).textContent;

                const total =
                    document.getElementById(
                        "invoiceTotal"
                    ).textContent;


                const text =
                    "HBOS Inventory Invoice\n" +
                    "Product: " + product + "\n" +
                    "Total: " + total;


                if (
                    navigator.share
                ) {

                    try {

                        await navigator.share({
                            title: "HBOS Invoice",
                            text: text
                        });

                    } catch (error) { }

                } else {

                    try {

                        await navigator.clipboard.writeText(text);

                        showToast(
                            "Invoice details copied."
                        );

                    } catch (error) {

                        showToast(
                            "Sharing is not supported."
                        );

                    }

                }

            }
        );


        /* =====================================================
           DOWNLOAD / SAVE
        ===================================================== */

        downloadInvoice.addEventListener(
            "click",
            () => {

                const product =
                    document.getElementById(
                        "invoiceProduct"
                    ).textContent;

                const total =
                    document.getElementById(
                        "invoiceTotal"
                    ).textContent;

                const invoice =
                    document.getElementById(
                        "invoiceNumber"
                    ).textContent;


                const content =

                    "HBOS - MANAGEMENT SYSTEM\n" +
                    "==============================\n\n" +
                    "PRODUCT INVOICE\n\n" +
                    "Invoice: " + invoice + "\n" +
                    "Product: " + product + "\n" +
                    "Total: " + total + "\n\n" +
                    "Product successfully added to inventory.\n";


                const blob =
                    new Blob(
                        [content],
                        { type: "text/plain" }
                    );


                const url =
                    URL.createObjectURL(blob);


                const link =
                    document.createElement("a");

                link.href = url;

                link.download =
                    "HBOS-Product-Invoice.txt";

                document.body.appendChild(link);

                link.click();

                link.remove();

                URL.revokeObjectURL(url);

                showToast(
                    "Invoice downloaded."
                );

            }
        );


        /* =====================================================
           TOAST
        ===================================================== */

        function showToast(message) {

            toast.textContent = message;

            toast.classList.add("show");

            setTimeout(() => {

                toast.classList.remove("show");

            }, 2500);

        }


        /* =====================================================
           HELPERS
        ===================================================== */

        function formatMoney(value) {

            return Number(value).toLocaleString(
                "en-PK",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

        }


        function escapeHTML(value) {

            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

        }


        /* =====================================================
           INITIAL STATE
        ===================================================== */

        showProducts();

    </script>