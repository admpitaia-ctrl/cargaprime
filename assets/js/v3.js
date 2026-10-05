const cp3Reveals = document.querySelectorAll('.cp3-reveal');
const cp3RevealObserver = new IntersectionObserver((entries) => entries.forEach((entry) => {
  if (entry.isIntersecting) entry.target.classList.add('is-visible');
}), {threshold: .12});
cp3Reveals.forEach((item) => cp3RevealObserver.observe(item));

const cp3Journey = document.querySelector('[data-cp3-journey]');
if (cp3Journey) {
  const scenes = [...cp3Journey.querySelectorAll('[data-cp3-scene]')];
  const buttons = [...cp3Journey.querySelectorAll('[data-cp3-go]')];
  const line = cp3Journey.querySelector('.cp3-rail-line i');
  const activate = (index) => {
    scenes.forEach((scene, i) => {
      const active = i === index;
      scene.classList.toggle('active', active);
      const video = scene.querySelector('video');
      if (active) video?.play().catch(() => {}); else video?.pause();
    });
    buttons.forEach((button, i) => {
      button.classList.toggle('active', i === index);
      button.classList.toggle('done', i < index);
      button.querySelector(':scope > span').textContent = i < index ? '✓' : String(i + 1).padStart(2, '0');
    });
    if (line) line.style.height = `${(index / Math.max(1, scenes.length - 1)) * 100}%`;
  };
  const observer = new IntersectionObserver((entries) => {
    const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
    if (visible) activate(Number(visible.target.dataset.cp3Scene));
  }, {threshold: [.35, .55, .7]});
  scenes.forEach((scene) => observer.observe(scene));
  buttons.forEach((button) => button.addEventListener('click', () => scenes[Number(button.dataset.cp3Go)]?.scrollIntoView({behavior:'smooth', block:'center'})));
}
