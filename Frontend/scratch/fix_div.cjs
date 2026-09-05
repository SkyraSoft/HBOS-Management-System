const fs = require('fs');
let vue = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

// replace:
//     </main>
//     
// </template>

// with:
//     </main>
// </div>
// </template>

vue = vue.replace('    </main>\n    \n</template>', '    </main>\n</div>\n</template>');

// What if the spacing is a bit different?
// Let's use a regex that matches `</main>\s*</template>`
vue = vue.replace(/<\/main>\s*<\/template>/, '</main>\n</div>\n</template>');

fs.writeFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', vue, 'utf8');
console.log("Fixed missing closing div");
