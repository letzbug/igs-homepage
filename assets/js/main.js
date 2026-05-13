
const toggle=document.querySelector('.mobile-toggle');
const menu=document.querySelector('.menu');
if(toggle){toggle.addEventListener('click',()=>menu.classList.toggle('open'))}

document.querySelectorAll('.acc-head').forEach(head=>{
  head.addEventListener('click',()=>head.parentElement.classList.toggle('open'));
});

const hero=document.querySelector('.hero[data-slideshow]');
if(hero){
  const slides=[
    'assets/img/hero/hero-01.jpg',
    'assets/img/hero/hero-02.jpg',
    'assets/img/hero/hero-03.jpg'
  ];
  let i=0;
  setInterval(()=>{
    i=(i+1)%slides.length;
    hero.style.setProperty('--hero-image',`url('${slides[i]}')`);
  },5200);
}
