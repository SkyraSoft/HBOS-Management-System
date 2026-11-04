const fs = require('fs');

const card = `            <!-- Live Web Application Card -->
            <div class="card" style="border-color: rgba(56, 189, 248, 0.6); background: linear-gradient(180deg, #1e293b 0%, #0c4a6e 100%);">
                <div>
                    <div class="card-icon">&#128640;</div>
                    <span class="badge" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8;">NEW &bull; CLOUD LIVE WEB ACCESS</span>
                    <h2>HBOS Cloud Web Terminal</h2>
                    <p>Instant browser-based access with 100% of the latest features: 9-module Help &amp; Support Knowledge Base (90 FAQs), all 8 active reports with authentic retail seed data, Unified 360&deg; Supplier Profile, Receipts Logo Display controls, Duration-filtered Expenses, and full offline caching.</p>
                </div>
                <div>
                    <a href="/app/" class="btn" style="width: 100%; box-sizing: border-box; background: linear-gradient(90deg, #38bdf8, #818cf8);">Launch Live Web Terminal &rarr;</a>
                    <div class="file-meta">
                        Platform: Chrome &bull; Edge &bull; Safari &bull; Any Browser &bull; Zero Installation<br>
                        Status: <span style="color: #4ade80;">Active Live Cloud Deployment</span><br>
                        <span style="color: #38bdf8;">&#10004; Direct Online Access &bull; PWA Installable</span>
                    </div>
                </div>
            </div>
`;

['Frontend/public/download.html', 'api/public/download.html'].forEach(path => {
    let content = fs.readFileSync(path, 'utf8');
    if (!content.includes('HBOS Cloud Web Terminal')) {
        content = content.replace('<div class="grid">', '<div class="grid">\n' + card);
        fs.writeFileSync(path, content, 'utf8');
        console.log('Updated ' + path);
    }
});
