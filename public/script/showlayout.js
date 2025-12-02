(() => {
  let overlay;
  let grid;
  let titleEl;

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

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        hideLayout();
      }
    });
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

    images.slice(0, 4).forEach((src, index) => {
      const img = document.createElement('img');
      img.src = src;
      img.alt = `${title} preview ${index + 1}`;
      img.loading = 'lazy';
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

