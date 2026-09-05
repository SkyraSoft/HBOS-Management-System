const fs = require('fs');
let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// I need to find the duplicate <input type="text" id="sku" ...> that got inserted.
// Wait, I can just use a regex to strip everything between the FIRST <div class="panel-footer">...</div>
// and the SECOND <div class="panel-footer">...</div> if that's what happened.
// Actually, let's just find the first `</main>` which I failed to remove, OR
// let's just find all instances of `</main>` and remove them.
vue = vue.replace(/<\/main>/g, '');

// Now let's remove the massive duplicate block if there is one.
// The replace_file_content tool replaces TARGET with REPLACEMENT.
// My target was `</div>\n    </section>\n\n    <div class="toast" id="toast">\n        Product added successfully.\n    </div>\n\n\n    \n</template>`
// And I replaced it with `</div>\n    </section>\n\n    <div class="toast" id="toast">\n        Product added successfully.\n    </div>\n\n</main>\n    \n</template>`
// But somehow the diff showed it duplicating lines 850-940!
// Let me just look at the exact text near `</template>`

let lines = vue.split('\n');
let templateEndIdx = lines.findIndex(l => l.trim() === '</template>');

if (templateEndIdx !== -1) {
    // Add </main> before </template>
    lines.splice(templateEndIdx, 0, '</main>');
}

// But wait, what if `replace_file_content` left duplicate form elements?
// Let's count how many times `<section class="page add-product-page"` appears.
let addProductPageCount = 0;
for(let l of lines) {
    if (l.includes('class="page add-product-page"')) {
        addProductPageCount++;
    }
}
console.log('add-product-page count:', addProductPageCount);

// Wait, the diff block showed that it replaced lines 937 to 951 with a massive block of lines.
// It basically duplicated everything from line 740 to 950 inside the target.
// To fix it, I will look for `<input type="text" id="sku"` and see if it appears more than once.
let skuCount = 0;
let skuIndices = [];
for(let i=0; i<lines.length; i++) {
    if (lines[i].includes('id="sku"')) {
        skuCount++;
        skuIndices.push(i);
    }
}
console.log('sku count:', skuCount);

if (skuCount > 1) {
    // We have a duplicate block. The first one is legit, the second one is the duplicate.
    // Let's find the start of the duplicate block. It probably started exactly where the fuzzy replace started matching.
    // The fuzzy replace started matching around line 937 `✓ Save Product`.
    // Let's just restore the file from before. Do I have a backup? No.
    // Let's manually slice out the duplicate block.
    // The duplicate block starts with `                        <input type="text" id="sku"`
    // and ends with `    </div>\n    </section>`
    let badStart = skuIndices[1] - 4; // roughly where the duplicate form group starts
    let badEnd = -1;
    for(let i=badStart; i<lines.length; i++) {
        if (lines[i].includes('</template>')) {
            badEnd = i - 1; // leave </template> alone
            break;
        }
    }
    
    // We just want to replace the whole duplicate block with the original footer end.
    let correctFooter = `
        <div class="panel-footer">

            <button class="secondary-btn" id="cancelPanel" @click="closeProductPanel">
                Cancel
            </button>

            <button class="save-btn" id="saveProduct" type="button" @click="saveProduct">
                ✓ Save Product
            </button>

        </div>

    </div>
    </section>

    <div class="toast" id="toast">
        Product added successfully.
    </div>
    </main>
    `;
    
    // Let's find the FIRST panel-footer
    let firstFooter = lines.findIndex(l => l.includes('<div class="panel-footer">'));
    if (firstFooter !== -1) {
        lines.splice(firstFooter, templateEndIdx - firstFooter, correctFooter);
    }
}

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', lines.join('\n'), 'utf8');
console.log('Fixed Vue file.');

