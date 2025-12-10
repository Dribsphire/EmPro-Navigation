(() => {
  let overlay;
  let grid;
  let titleEl;
  let lightbox;
  let currentImages = [];
  let currentImageIndex = 0;

  function createOverlay() {
    if (overlay || !document.body) {
      return;
    }

    overlay = document.createElement('div');
    overlay.className = 'map-layout-overlay';
    overlay.innerHTML = `
      <div class="map-layout-content">
        <button type="button" class="map-layout-close" aria-label="Close preview">&times;</button>
        <h3 class="map-layout-title"></h3>
        <div class="map-layout-grid"></div>
      </div>
    `;

    document.body.appendChild(overlay);

    grid = overlay.querySelector('.map-layout-grid');
    titleEl = overlay.querySelector('.map-layout-title');

    const closeButton = overlay.querySelector('.map-layout-close');
    if (closeButton) {
      closeButton.addEventListener('click', hideLayout);
    }

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        hideLayout();
      }
    });

    // Escape key handler for layout overlay
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && overlay && overlay.classList.contains('show')) {
        hideLayout();
      }
    });
  }

  function createLightbox() {
    if (lightbox || !document.body) {
      return;
    }

    lightbox = document.createElement('div');
    lightbox.className = 'image-lightbox';
    lightbox.innerHTML = `
      <div class="lightbox-overlay"></div>
      <div class="lightbox-container">
        <button type="button" class="lightbox-close" aria-label="Close lightbox">&times;</button>
        <button type="button" class="lightbox-prev" aria-label="Previous image">&#8249;</button>
        <button type="button" class="lightbox-next" aria-label="Next image">&#8250;</button>
        <div class="lightbox-image-container">
          <img class="lightbox-image" src="" alt="" />
        </div>
        <div class="lightbox-counter"></div>
      </div>
    `;

    document.body.appendChild(lightbox);

    // Close button
    const closeBtn = lightbox.querySelector('.lightbox-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', hideLightbox);
    }

    // Previous button
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    if (prevBtn) {
      prevBtn.addEventListener('click', () => showPreviousImage());
    }

    // Next button
    const nextBtn = lightbox.querySelector('.lightbox-next');
    if (nextBtn) {
      nextBtn.addEventListener('click', () => showNextImage());
    }

    // Close on overlay click
    const overlayEl = lightbox.querySelector('.lightbox-overlay');
    if (overlayEl) {
      overlayEl.addEventListener('click', hideLightbox);
    }

    // Keyboard navigation
    document.addEventListener('keydown', handleLightboxKeyboard);
  }

  function handleLightboxKeyboard(event) {
    if (!lightbox || !lightbox.classList.contains('show')) {
      return;
    }

    switch(event.key) {
      case 'Escape':
        hideLightbox();
        break;
      case 'ArrowLeft':
        showPreviousImage();
        break;
      case 'ArrowRight':
        showNextImage();
        break;
    }
  }

  function showLightbox(imageSrc, imageIndex, images) {
    ensureLightbox();
    if (!lightbox) return;

    currentImages = images;
    currentImageIndex = imageIndex;

    const img = lightbox.querySelector('.lightbox-image');
    const counter = lightbox.querySelector('.lightbox-counter');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');

    if (img) {
      img.src = imageSrc;
      img.alt = `Image ${imageIndex + 1} of ${images.length}`;
    }

    if (counter) {
      counter.textContent = `${imageIndex + 1} / ${images.length}`;
    }

    // Show/hide navigation buttons
    if (prevBtn) {
      prevBtn.style.display = images.length > 1 ? 'block' : 'none';
    }
    if (nextBtn) {
      nextBtn.style.display = images.length > 1 ? 'block' : 'none';
    }

    lightbox.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }

  function hideLightbox() {
    if (lightbox) {
      lightbox.classList.remove('show');
      document.body.style.overflow = ''; // Restore scrolling
    }
  }

  function showPreviousImage() {
    if (currentImages.length === 0) return;
    currentImageIndex = (currentImageIndex - 1 + currentImages.length) % currentImages.length;
    const img = lightbox.querySelector('.lightbox-image');
    const counter = lightbox.querySelector('.lightbox-counter');
    if (img) {
      img.src = currentImages[currentImageIndex];
    }
    if (counter) {
      counter.textContent = `${currentImageIndex + 1} / ${currentImages.length}`;
    }
  }

  function showNextImage() {
    if (currentImages.length === 0) return;
    currentImageIndex = (currentImageIndex + 1) % currentImages.length;
    const img = lightbox.querySelector('.lightbox-image');
    const counter = lightbox.querySelector('.lightbox-counter');
    if (img) {
      img.src = currentImages[currentImageIndex];
    }
    if (counter) {
      counter.textContent = `${currentImageIndex + 1} / ${currentImages.length}`;
    }
  }

  function ensureLightbox() {
    if (lightbox) {
      return;
    }

    if (document.body) {
      createLightbox();
      return;
    }

    document.addEventListener('DOMContentLoaded', createLightbox, { once: true });
  }

  function ensureOverlay() {
    if (overlay) {
      return;
    }

    if (document.body) {
      createOverlay();
      return;
    }

    document.addEventListener('DOMContentLoaded', createOverlay, { once: true });
  }

  function hideLayout() {
    overlay?.classList.remove('show');
  }

  function renderImages(images = [], title) {
    if (!grid) {
      return;
    }

    grid.innerHTML = '';

    if (!images.length) {
      const empty = document.createElement('p');
      empty.className = 'map-layout-empty';
      empty.textContent = 'Layout preview coming soon.';
      grid.appendChild(empty);
      return;
    }

    // Show all images, not just first 4
    images.forEach((src, index) => {
      const img = document.createElement('img');
      img.src = src;
      img.alt = `${title} preview ${index + 1}`;
      img.loading = 'lazy';
      img.style.cursor = 'pointer';
      img.title = 'Click to view full size';
      
      // Add click handler to open lightbox
      img.addEventListener('click', () => {
        showLightbox(src, index, images);
      });
      
      grid.appendChild(img);
    });
  }

  function showBuildingLayout(name, images = []) {
    ensureOverlay();

    if (!overlay || !grid || !titleEl) {
      return;
    }

    titleEl.textContent = name;
    renderImages(images, name);
    overlay.classList.add('show');
  }

  window.showBuildingLayout = showBuildingLayout;
  ensureOverlay();
})();

