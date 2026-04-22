function openLightbox(src) {
    const lb = document.getElementById('imgLightbox');
    const img = document.getElementById('imgLightboxImg');
    if (!lb || !img) return;

    img.style.width = '';
    img.style.height = '';
    img.src = '';
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    const tmp = new Image();
    tmp.onload = () => {
        img.src = src;
        requestAnimationFrame(() => lb.classList.add('is-open'));
    };
    tmp.src = src;
}

function closeLightbox() {
    const lb = document.getElementById('imgLightbox');
    if (!lb) return;
    lb.classList.add('is-closing');
    lb.addEventListener('transitionend', () => {
        lb.classList.remove('is-open', 'is-closing');
        lb.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }, { once: true });
}

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-lightbox-close]')) { closeLightbox(); return; }

    const zoomImg = e.target.closest('.qres__image-img, .opt__image');
    if (zoomImg && zoomImg.src) openLightbox(zoomImg.src);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});
