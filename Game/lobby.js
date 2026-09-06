const friendList = document.getElementById('friendList');
const expandToggle = document.getElementById('expandToggle');
let outsideName = document.getElementById("outsideName");
expandToggle.addEventListener('click', (e) => {
    e.stopPropagation();

    const isExpanded = friendList.classList.toggle('expanded');

    if (isExpanded) {
        outsideName.style.display = "none";
        friendList.style.paddingBottom = "20px";
    } else {
        outsideName.style.display = "";       // back to default (visible)
        friendList.style.paddingBottom = "20px"; // whatever your original padding was
    }
});


// MODALS

// SETTINGS
// Open modal
document.querySelectorAll('[data-modal]').forEach(trigger => {
    trigger.addEventListener('click', () => {
        document.getElementById(trigger.dataset.modal).classList.add('active');
    });
});

// Close via X button
document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.dataset.close).classList.remove('active');
    });
});

// Close by clicking outside the box
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

// REDIRECTS TO HOMEPAGE

const back = document.getElementById("back");
back.addEventListener("click", ()=>{
    window.location.href = "/homepage.html";
})

// LOADING OVERLAY: segmented progress bar driven by character-viewer.js
const SEGMENT_COUNT = 20;
const loadingOverlay = document.getElementById('loading-overlay');
const loadingTrack = document.getElementById('loadingBarTrack');
const loadingPct = document.getElementById('loadingBarPct');

if (loadingTrack) {
    for (let i = 0; i < SEGMENT_COUNT; i++) {
        const seg = document.createElement('div');
        seg.className = 'loading-bar-seg';
        loadingTrack.appendChild(seg);
    }
}
const loadingSegments = loadingTrack ? loadingTrack.querySelectorAll('.loading-bar-seg') : [];

function setLoadProgress(percent) {
    const lit = Math.round((percent / 100) * SEGMENT_COUNT);
    loadingSegments.forEach((seg, i) => seg.classList.toggle('lit', i < lit));
    if (loadingPct) loadingPct.textContent = `${Math.round(percent)}%`;
}

window.addEventListener('model-progress', (e) => setLoadProgress(e.detail.percent));

window.addEventListener('model-ready', () => {
    setLoadProgress(100);
    setTimeout(() => {
        loadingOverlay?.classList.add('hidden');
        setTimeout(() => loadingOverlay?.remove(), 350); // clean up after fade completes
    }, 250);
});

// CHECKS PHONE ORIENTATION — redirect to orientationCheck.html if rotated to portrait
let redirected = false;
let orientationCheckTimer = null;
function checkOrientation() {
  clearTimeout(orientationCheckTimer);
  orientationCheckTimer = setTimeout(() => {
    const isLandscape = window.matchMedia('(orientation: landscape)').matches;
    if (!isLandscape && !redirected) {
      redirected = true;
      window.location.replace('/orientationCheck.html');
    }
  }, 300);
}

checkOrientation(); // in case the page is already loaded in portrait
window.addEventListener('resize', checkOrientation);
screen.orientation?.addEventListener('change', checkOrientation);