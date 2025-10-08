// Profile Card Interactive Tilt + Gradient Animation
(function() {
  const clamp = (value, min = 0, max = 100) => Math.min(Math.max(value, min), max);
  const round = (value, precision = 3) => parseFloat(value.toFixed(precision));
  const adjust = (value, fromMin, fromMax, toMin, toMax) =>
    round(toMin + ((toMax - toMin) * (value - fromMin)) / (fromMax - fromMin));
  const easeInOutCubic = x => (x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2);

  function updateCardTransform(offsetX, offsetY, card, wrap) {
    const width = card.offsetWidth;
    const height = card.offsetHeight;
    const percentX = clamp((100 / width) * offsetX);
    const percentY = clamp((100 / height) * offsetY);
    const centerX = percentX - 50;
    const centerY = percentY - 50;
    const properties = {
      '--pointer-x': `${percentX}%`,
      '--pointer-y': `${percentY}%`,
      '--background-x': `${adjust(percentX, 0, 100, 35, 65)}%`,
      '--background-y': `${adjust(percentY, 0, 100, 35, 65)}%`,
      '--pointer-from-center': `${clamp(Math.hypot(percentY - 50, percentX - 50) / 50, 0, 1)}`,
      '--pointer-from-top': `${percentY / 100}`,
      '--pointer-from-left': `${percentX / 100}`,
      '--rotate-x': `${round(-(centerX / 5))}deg`,
      '--rotate-y': `${round(centerY / 4)}deg`
    };
    Object.entries(properties).forEach(([property, value]) => {
      wrap.style.setProperty(property, value);
    });
  }

  function createSmoothAnimation(duration, startX, startY, card, wrap) {
    const startTime = performance.now();
    const targetX = wrap.offsetWidth / 2;
    const targetY = wrap.offsetHeight / 2;
    function animationLoop(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = clamp(elapsed / duration);
      const easedProgress = easeInOutCubic(progress);
      const currentX = adjust(easedProgress, 0, 1, startX, targetX);
      const currentY = adjust(easedProgress, 0, 1, startY, targetY);
      updateCardTransform(currentX, currentY, card, wrap);
      if (progress < 1) {
        requestAnimationFrame(animationLoop);
      }
    }
    requestAnimationFrame(animationLoop);
  }

  document.querySelectorAll('.pc-card-wrapper').forEach(function(wrap) {
    const card = wrap.querySelector('.pc-card');
    if (!card) return;
    let isActive = false;
    wrap.addEventListener('pointerenter', function() {
      isActive = true;
      wrap.classList.add('active');
      card.classList.add('active');
    });
    wrap.addEventListener('pointermove', function(event) {
      if (!isActive) return;
      const rect = card.getBoundingClientRect();
      updateCardTransform(event.clientX - rect.left, event.clientY - rect.top, card, wrap);
    });
    wrap.addEventListener('pointerleave', function(event) {
      isActive = false;
      wrap.classList.remove('active');
      card.classList.remove('active');
      createSmoothAnimation(600, event.offsetX, event.offsetY, card, wrap);
    });
    // Initial animation
    const initialX = wrap.offsetWidth - 70;
    const initialY = 60;
    updateCardTransform(initialX, initialY, card, wrap);
    createSmoothAnimation(1500, initialX, initialY, card, wrap);
  });
})();
