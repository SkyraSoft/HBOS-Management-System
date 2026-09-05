const fs = require('fs');
const jsdom = require("jsdom");
const { JSDOM } = jsdom;

const html = fs.readFileSync('c:/xampp/htdocs/HBOS/legacy_html/Khata.html', 'utf8');

const dom = new JSDOM(html, { runScripts: "dangerously" });
const document = dom.window.document;

// Find the Record Payment button that opens the page
const openBtn = document.querySelector('button[onclick="showPaymentPage()"]');
if (openBtn) {
    console.log("Found openBtn!");
    try {
        // Trigger click which runs inline onclick
        openBtn.click();
        console.log("Clicked openBtn!");
        
        const khataPage = document.getElementById("khataPage");
        const paymentPage = document.getElementById("paymentPage");
        
        console.log("khataPage classes:", khataPage.className);
        console.log("paymentPage classes:", paymentPage.className);
        
    } catch(err) {
        console.error("Error during click:", err);
    }
} else {
    console.log("Could not find openBtn!");
}
