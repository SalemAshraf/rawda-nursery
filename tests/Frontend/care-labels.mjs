import assert from 'node:assert/strict';
import {moodLabel} from '../../resources/js/lib/careLabels.js';
assert.equal(moodLabel('happy','ar'),'سعيد');
assert.equal(moodLabel('happy','en'),'Happy');
for(const text of ['Happy after drawing','happy today',' HAPPY ','سعيد بعد الرسم','happy\ncalm'])assert.equal(moodLabel(text,'ar'),text);
assert.equal(moodLabel(null,'ar'),null);
console.log('Known mood labels translated; free text and null retained.');
