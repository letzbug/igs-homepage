const button=document.querySelector('.igs-menu-button');
const nav=document.querySelector('.igs-nav');
if(button&&nav){button.addEventListener('click',()=>{const open=button.getAttribute('aria-expanded')==='true';button.setAttribute('aria-expanded',String(!open));nav.classList.toggle('is-open',!open);});}
document.querySelectorAll('.igs-photo-layers').forEach(scene=>{
  if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;
  scene.addEventListener('pointermove',event=>{const r=scene.getBoundingClientRect();const x=(event.clientX-r.left)/r.width-.5;const y=(event.clientY-r.top)/r.height-.5;scene.style.setProperty('--tilt-x',`${(-y*7).toFixed(2)}deg`);scene.style.setProperty('--tilt-y',`${(x*9).toFixed(2)}deg`);});
  scene.addEventListener('pointerleave',()=>{scene.style.setProperty('--tilt-x','0deg');scene.style.setProperty('--tilt-y','0deg');});
});
document.querySelectorAll('.acc-head').forEach(head=>{
  head.setAttribute('tabindex','0');head.setAttribute('role','button');
  head.setAttribute('aria-expanded',String(head.parentElement.classList.contains('open')));
  const toggle=()=>{const open=head.parentElement.classList.toggle('open');head.setAttribute('aria-expanded',String(open));};
  head.addEventListener('click',toggle);
  head.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();toggle();}});
});
