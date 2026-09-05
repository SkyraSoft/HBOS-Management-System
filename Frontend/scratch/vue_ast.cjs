const fs = require('fs');

const source = fs.readFileSync('c:/xampp/htdocs/HBOS/src/views/InventryView.vue', 'utf8');

const { parse } = require('@vue/compiler-sfc');
const { parse: parseDom } = require('@vue/compiler-dom');

const ast = parseDom(source);

function findUnclosed(node) {
    if (node.type === 1) { // Element
        if (node.isSelfClosing) return;
        // check if it has end tag
        // actually Vue compiler AST gives us errors.
    }
}
// wait, vue/compiler-dom gives errors too.
