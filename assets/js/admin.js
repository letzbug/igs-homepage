const form=document.querySelector('[data-editor-form]');
const editor=document.querySelector('#body-editor');
const source=document.querySelector('#body-source');
if(editor&&source){
  document.querySelectorAll('[data-command]').forEach(button=>button.addEventListener('click',()=>{
    editor.focus();const command=button.dataset.command;
    let value=button.dataset.value||null;
    if(command==='createLink'){value=prompt('Link-Adresse (https:// oder seite.html)');if(!value)return;}
    document.execCommand(command,false,value);
  }));
  document.querySelector('[data-toggle-source]')?.addEventListener('click',event=>{
    const opening=source.hidden;source.hidden=!opening;editor.hidden=opening;
    event.currentTarget.textContent=opening?'Visuellen Editor anzeigen':'HTML-Quelltext anzeigen';
    if(opening)source.value=editor.innerHTML;else editor.innerHTML=source.value;
  });
  document.querySelector('[data-insert-image]')?.addEventListener('click',()=>{
    const path=document.querySelector('#image-path').value.trim();const alt=document.querySelector('#image-alt').value.trim();
    if(!/^(assets|uploads)\/[a-zA-Z0-9_./-]+$/.test(path)||path.includes('..')||!alt){alert('Gültigen Bildpfad und Bildbeschreibung eingeben.');return;}
    editor.focus();document.execCommand('insertImage',false,'/'+path);
    const img=editor.querySelector(`img[src="/${CSS.escape(path)}"]`);if(img)img.alt=alt;
  });
  form?.addEventListener('submit',()=>{if(!source.hidden)editor.innerHTML=source.value;source.value=editor.innerHTML;});
}
