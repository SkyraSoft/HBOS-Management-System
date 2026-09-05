const fs = require('fs');
const { parse } = require('@vue/compiler-sfc');

const source = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

const { descriptor, errors } = parse(source);

if (errors.length > 0) {
    console.log("Errors found:");
    errors.forEach(err => {
        console.log(err.message, "at line", err.loc.start.line);
        // Let's print the line around the error
        const lines = source.split('\n');
        for(let i = err.loc.start.line - 2; i <= err.loc.start.line + 2; i++) {
            if (i >= 0 && i < lines.length) {
                console.log(i + 1 + ": " + lines[i]);
            }
        }
    });
} else {
    console.log("No errors found by vue/compiler-sfc.");
}
