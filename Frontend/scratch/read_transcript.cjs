const fs = require('fs');
const lines = fs.readFileSync('C:/Users/SART/.gemini/antigravity-ide/brain/6dd955ac-cedd-4c35-8421-e45ea875e13a/.system_generated/logs/transcript_full.jsonl', 'utf8').split('\n');
for(let l of lines) {
    if(l.includes('"step_index":3028')) {
        const obj = JSON.parse(l);
        console.log(JSON.stringify(obj.tool_calls[0].arguments, null, 2));
    }
}
