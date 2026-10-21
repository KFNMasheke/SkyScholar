// SPDX-License-Identifier: GPL-3.0-or-later
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const root=path.join(__dirname,'..');
const scenarios=JSON.parse(fs.readFileSync(path.join(root,'authoring/radiotelephony/scenarios.json'),'utf8')).scenarios;
let api;
class Element {
    constructor(){this.value='';this.textContent='';this.hidden=true;this.disabled=false;this.handlers={};this.children=[];}
    addEventListener(name,handler){this.handlers[name]=handler;}
    appendChild(child){this.children.push(child);}
    removeAttribute(name){delete this[name];}
    pause(){}
    focus(){}
    click(){return this.handlers.click();}
}
const elements=new Map();
const doc={getElementById(id){if(!elements.has(id)){elements.set(id,new Element());}return elements.get(id);},createElement(){return new Element();}};
let stopped=0,created=0,revoked=0;
class Recorder {
    constructor(){this.state='inactive';this.mimeType='audio/webm';this.handlers={};}
    addEventListener(name,handler){this.handlers[name]=handler;}
    start(){this.state='recording';}
    stop(){this.state='inactive';this.handlers.dataavailable({data:new Blob(['local practice'])});queueMicrotask(()=>this.handlers.stop());}
}
const events={};
vm.runInNewContext(fs.readFileSync(path.join(root,'moodle/local/skyscholarcontent/amd/build/radio.min.js'),'utf8'),{
    define(name,deps,factory){api=factory();},document:doc,
    window:{MediaRecorder:Recorder,addEventListener(name,handler){events[name]=handler;}},
    MediaRecorder:Recorder, navigator:{mediaDevices:{async getUserMedia(){return {getTracks(){return [{stop(){stopped++;}}];}};}}},
    URL:{createObjectURL(){created++;return 'blob:local-test';},revokeObjectURL(){revoked++;}},
    Blob,queueMicrotask,setTimeout(){return 1;},clearTimeout(){}
});
for(const scenario of scenarios){
    const result=api.check(scenario.model,scenario);
    assert.ok(result.items.every(x=>x.found),scenario.id+' model does not meet its own checklist');
    assert.equal(result.conflicts.length,0,scenario.id+' model has conflicting terms');
    assert.ok(api.check('',scenario).items.every(x=>!x.found));
}
const hold=scenarios.find(x=>x.id==='hold-short');
assert.ok(api.check('Not holding short; cleared for takeoff, ZS-ABC',hold).conflicts.length>0);
assert.equal(api.normalize('Alpha JULIET X-ray NINER'),'alfa juliett xray nine');
assert.ok(!('pass' in api.check(hold.model,hold)),'Checklist must not award qualification/pass');
(async()=>{
    api.init(scenarios);
    await elements.get('ssc-radio-record').click();
    elements.get('ssc-radio-clear').click();
    await Promise.resolve();
    assert.equal(created,0,'Clear while recording must not recreate a playable blob on stop');
    assert.equal(elements.get('ssc-radio-audio').hidden,true);
    assert.ok(stopped>0,'Clearing must release microphone tracks');
    await elements.get('ssc-radio-record').click();
    elements.get('ssc-radio-record').click();
    await Promise.resolve();
    assert.equal(created,1,'Normal stop should expose one local playback object');
    elements.get('ssc-radio-clear').click();
    assert.ok(revoked>0,'Clear must revoke the local playback URL');
    await elements.get('ssc-radio-record').click();
    events.pagehide();
    await Promise.resolve();
    assert.equal(created,1,'Leaving must not create another recording');
    console.log('PASS 27 scenario models, empty/conflicting responses, limited checklist semantics and microphone clear/stop/page-leave lifecycle.');
})().catch(error=>{console.error(error);process.exit(1);});
