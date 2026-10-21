// SPDX-License-Identifier: GPL-3.0-or-later
define([], function() {
    'use strict';
    var alphabet = {A:'Alfa',B:'Bravo',C:'Charlie',D:'Delta',E:'Echo',F:'Foxtrot',G:'Golf',H:'Hotel',I:'India',J:'Juliett',K:'Kilo',L:'Lima',M:'Mike',N:'November',O:'Oscar',P:'Papa',Q:'Quebec',R:'Romeo',S:'Sierra',T:'Tango',U:'Uniform',V:'Victor',W:'Whiskey',X:'X-ray',Y:'Yankee',Z:'Zulu'};
    function normalize(value) {
        return value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'')
            .replace(/([a-z])(\d)/g,'$1 $2').replace(/(\d)([a-z])/g,'$1 $2')
            .replace(/[^a-z0-9 ]/g,' ').replace(/\b(?:alpha)\b/g,'alfa')
            .replace(/\bjuliet\b/g,'juliett').replace(/\bx\s+ray\b/g,'xray')
            .replace(/\bniner\b/g,'nine').replace(/\bfife\b/g,'five').replace(/\bfower\b/g,'four')
            .replace(/\btree\b/g,'three').replace(/\s+/g,' ').trim();
    }
    function contains(text,term) {return (' '+text+' ').includes(' '+normalize(term)+' ');}
    function check(text,scenario) {
        var normalized=normalize(text);
        return {items:scenario.checks.map(function(item){return {label:item.label,found:item.any.some(function(term){return contains(normalized,term);})};}),
            conflicts:(scenario.forbidden||[]).filter(function(term){return contains(normalized,term);})};
    }
    function init(scenarios) {
        var select=document.getElementById('ssc-radio-scenario');
        if(!select){return;}
        var response=document.getElementById('ssc-radio-response');
        var feedback=document.getElementById('ssc-radio-feedback');
        var status=document.getElementById('ssc-radio-status');
        var current;
        var recorder=null, stream=null, chunks=[], objecturl=null, timer=null;
        var discardRecording=false, disposed=false, recordingEpoch=0;
        var recordButton=document.getElementById('ssc-radio-record');
        var audio=document.getElementById('ssc-radio-audio');
        scenarios.forEach(function(s,i){var option=document.createElement('option');option.value=String(i);option.textContent=s.group+' · '+s.title;select.appendChild(option);});
        function render(){
            current=scenarios[Number(select.value)||0];
            document.getElementById('ssc-radio-context').textContent=current.context;
            document.getElementById('ssc-radio-prompt').textContent=current.prompt;
            document.getElementById('ssc-radio-model').textContent=current.model;
            document.getElementById('ssc-radio-notes').textContent=current.notes;
            document.getElementById('ssc-radio-reveal').open=false;
            response.value='';feedback.textContent='';
        }
        select.addEventListener('change',render);render();
        document.getElementById('ssc-radio-next').addEventListener('click',function(){select.value=String((Number(select.value)+1)%scenarios.length);render();response.focus();});
        document.getElementById('ssc-radio-check').addEventListener('click',function(){
            feedback.textContent='';
            if(!response.value.trim()){feedback.textContent='Type a practice response first.';return;}
            var result=check(response.value,current);var list=document.createElement('ul');
            result.items.forEach(function(item){var row=document.createElement('li');row.textContent=(item.found?'Found: ':'Review: ')+item.label;list.appendChild(row);});
            feedback.appendChild(list);
            if(result.conflicts.length){var warning=document.createElement('p');warning.className='alert alert-warning';warning.textContent='Possible conflict with the instruction: '+result.conflicts.join('; ')+'. Check the full meaning before continuing.';feedback.appendChild(warning);}
            var note=document.createElement('p');note.textContent='This checks selected written elements only. It is not a phraseology, pronunciation or competence grade. Compare the full response with the model and instructor guidance.';feedback.appendChild(note);
        });
        var listen=document.getElementById('ssc-radio-listen');
        if(!('speechSynthesis' in window)){listen.disabled=true;listen.textContent='Browser voice unavailable';}
        listen.addEventListener('click',function(){
            window.speechSynthesis.cancel();
            var utterance=new SpeechSynthesisUtterance('Training scenario. '+current.prompt);
            utterance.lang='en-GB';utterance.rate=0.8;
            var voice=window.speechSynthesis.getVoices().find(function(v){return /^en(-|_)/i.test(v.lang);});
            if(voice){utterance.voice=voice;}
            window.speechSynthesis.speak(utterance);
        });
        document.getElementById('ssc-radio-stopvoice').addEventListener('click',function(){if('speechSynthesis' in window){window.speechSynthesis.cancel();}});
        function stopTracks(){if(stream){stream.getTracks().forEach(function(track){track.stop();});stream=null;}if(timer){clearTimeout(timer);timer=null;}}
        function stopRecording(){if(recorder&&recorder.state==='recording'){recordButton.disabled=true;recorder.stop();}stopTracks();}
        if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia||!window.MediaRecorder){recordButton.disabled=true;status.textContent='Recording is unavailable in this browser. You can still rehearse aloud and use the text checklist.';}
        recordButton.addEventListener('click',async function(){
            if(recorder&&recorder.state==='recording'){stopRecording();return;}
            recordButton.disabled=true;
            var requestEpoch=++recordingEpoch; discardRecording=false;
            try{
                stream=await navigator.mediaDevices.getUserMedia({audio:true});
                if(disposed||requestEpoch!==recordingEpoch){stopTracks();recordButton.disabled=false;return;}
                chunks=[];
                recorder=new MediaRecorder(stream);
                recorder.addEventListener('dataavailable',function(event){if(event.data.size){chunks.push(event.data);}});
                recorder.addEventListener('stop',function(){
                    stopTracks();
                    recordButton.textContent='Record practice locally';recordButton.disabled=false;
                    if(discardRecording||disposed){chunks=[];return;}
                    if(objecturl){URL.revokeObjectURL(objecturl);}
                    objecturl=URL.createObjectURL(new Blob(chunks,{type:recorder.mimeType||'audio/webm'}));
                    audio.src=objecturl;audio.hidden=false;recordButton.textContent='Record practice locally';recordButton.disabled=false;
                    status.textContent='Recording stopped. Playback is available in this browser session. Nothing was uploaded.';
                });
                recorder.start();recordButton.textContent='Stop recording';recordButton.disabled=false;
                status.textContent='Recording locally. Stop when finished; automatic stop after 60 seconds.';
                timer=setTimeout(stopRecording,60000);
            }catch(error){stopTracks();recordButton.disabled=false;status.textContent='Microphone recording could not start. Check your browser permission or use the text practice instead.';}
        });
        document.getElementById('ssc-radio-clear').addEventListener('click',function(){
            discardRecording=true;recordingEpoch++;stopRecording();audio.pause();audio.removeAttribute('src');audio.hidden=true;
            if(objecturl){URL.revokeObjectURL(objecturl);objecturl=null;}chunks=[];status.textContent='Recording cleared from the practice player.';
        });
        function spell(){var input=document.getElementById('ssc-radio-token').value.toUpperCase();document.getElementById('ssc-radio-spelled').textContent=[...input].filter(function(c){return alphabet[c];}).map(function(c){return alphabet[c];}).join(' ');}
        document.getElementById('ssc-radio-spell').addEventListener('click',spell);
        document.getElementById('ssc-radio-random').addEventListener('click',function(){var letters=Object.keys(alphabet),token='';for(var i=0;i<5;i++){token+=letters[Math.floor(Math.random()*letters.length)];}document.getElementById('ssc-radio-token').value=token;spell();});
        window.addEventListener('pagehide',function(){disposed=true;discardRecording=true;recordingEpoch++;stopRecording();if(objecturl){URL.revokeObjectURL(objecturl);}if('speechSynthesis' in window){window.speechSynthesis.cancel();}});
    }
    return {init:init,normalize:normalize,check:check};
});
