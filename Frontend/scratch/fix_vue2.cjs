const fs = require('fs');
let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// The bottom of the file looks like:
/*
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
    
</main>
</template>

            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
*/

let lines = vue.split('\n');
let templateEndIdx = lines.findIndex(l => l.trim() === '</template>');

// I need to replace from `<div class="toast" id="toast">` down to `</template>` and restore `<style scoped>`

let badStart = lines.findIndex(l => l.includes('<div class="toast" id="toast">'));

let correctEnd = `    <div class="toast" id="toast">
        Product added successfully.
    </div>
    </main>
    
</template>

<style scoped>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }`;

// Let's find `            margin: 0;` after `</template>`
let marginStart = -1;
for(let i=templateEndIdx+1; i<lines.length; i++) {
    if (lines[i].includes('margin: 0;')) {
        marginStart = i;
        break;
    }
}

if (badStart !== -1 && marginStart !== -1) {
    let blockToRemoveCount = (marginStart - badStart) + 1; // +1 to remove margin: 0; line since it's in our replacement
    // actually, let's just replace from badStart to marginStart inclusive
    lines.splice(badStart, (marginStart - badStart) + 1, correctEnd);
    fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', lines.join('\n'), 'utf8');
    console.log("Restored end of file");
} else {
    console.log("Could not find blocks");
}
