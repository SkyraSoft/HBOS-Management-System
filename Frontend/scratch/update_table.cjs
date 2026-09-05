const fs = require('fs');

const cssModifications = `
        /* =========================================================
           PRODUCT DIRECTORY TABLE UPGRADE
        ========================================================= */
        .product-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .product-table th {
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            padding: 16px 24px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .product-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .product-table tbody tr:hover {
            background-color: #fcfdfd;
        }

        .product-table td {
            padding: 18px 24px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
            font-size: 14px;
            color: #334155;
        }

        .product-table tr:last-child td {
            border-bottom: 0;
        }

        .product-name {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .product-sku {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-active {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .badge-low {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .badge-out {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .row-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .icon-button {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .icon-button:hover {
            background: #f8fafc;
            color: #2447c6;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .search-box {
            position: relative;
            width: 340px;
        }

        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }

        .search-box input {
            width: 100%;
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0 16px 0 44px;
            outline: none;
            font-size: 14px;
            color: #334155;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .search-box input:focus {
            background: white;
            border-color: #2447c6;
            box-shadow: 0 0 0 3px rgba(36, 71, 198, .1);
        }

        .stock-val {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        .stock-unit {
            font-size: 12px;
            color: #64748b;
            margin-left: 4px;
        }
`;

function run() {
    let html = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Product.html', 'utf8');

    // Remove old CSS
    const rulesToRemove = [
        /(\.product-table\s*\{[\s\S]*?\n\s*\})/g,
        /(\.product-table th\s*\{[\s\S]*?\n\s*\})/g,
        /(\.product-table tbody tr\s*\{[\s\S]*?\n\s*\})/g,
        /(\.product-table td\s*\{[\s\S]*?\n\s*\})/g,
        /(\.product-table tr:last-child td\s*\{[\s\S]*?\n\s*\})/g,
        /(\.product-name\s*\{[\s\S]*?\n\s*\})/g,
        /(\.product-sku\s*\{[\s\S]*?\n\s*\})/g,
        /(\.badge-status\s*\{[\s\S]*?\n\s*\})/g,
        /(\.badge-active\s*\{[\s\S]*?\n\s*\})/g,
        /(\.badge-low\s*\{[\s\S]*?\n\s*\})/g,
        /(\.badge-out\s*\{[\s\S]*?\n\s*\})/g,
        /(\.row-actions\s*\{[\s\S]*?\n\s*\})/g,
        /(\.icon-button\s*\{[\s\S]*?\n\s*\})/g,
        /(\.icon-button:hover\s*\{[\s\S]*?\n\s*\})/g,
        /(\.search-box\s*\{[\s\S]*?\n\s*\})/g,
        /(\.search-box i\s*\{[\s\S]*?\n\s*\})/g,
        /(\.search-box input\s*\{[\s\S]*?\n\s*\})/g,
        /(\.search-box input:focus\s*\{[\s\S]*?\n\s*\})/g
    ];

    let headIdx = html.indexOf('</style>');
    let headHtml = html.substring(0, headIdx);
    
    rulesToRemove.forEach(regex => {
        headHtml = headHtml.replace(regex, '');
    });

    headHtml += cssModifications + '\n    ';
    html = headHtml + html.substring(headIdx);

    // Update row template
    const newTemplateString = fs.readFileSync('c:/xampp/htdocs/HBOS/scratch/new_row.html', 'utf8');
    
    const startRegex = /row\.innerHTML = `[\s\S]*?`;/g;
    html = html.replace(startRegex, "row.innerHTML = `\n" + newTemplateString + "\n            `;");

    fs.writeFileSync('c:/xampp/htdocs/HBOS/legacy_html/Product.html', html);
    console.log("Replaced CSS and JS template successfully.");
}

run();
