// DOM
const weapon = document.getElementById("weapon");
weapon.addEventListener("click", ()=>{
    window.location.href = "weapons/weapon.html";

})
// SCALE-TO-FIT: lobby is authored at a fixed 915x412 (Pixel 7 landscape).
// Scale + center that box to whatever viewport it actually renders in,
// instead of stretching/cropping the fixed-px layout.
const DESIGN_W = 915;
const DESIGN_H = 412;
const lobbyContainer = document.querySelector('.lobby-container');
function resizeLobby() {
    if (!lobbyContainer) return;
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    // Scale container to fit screen height
    const scale = vh / DESIGN_H;

    // Dynamically expand container width to fill full viewport width
    const scaledWidth = vw / scale;

    lobbyContainer.style.width = `${scaledWidth}px`;
    lobbyContainer.style.height = `${DESIGN_H}px`;
    lobbyContainer.style.transform = `scale(${scale})`;
}

resizeLobby();
window.addEventListener('resize', resizeLobby);
screen.orientation?.addEventListener('change', resizeLobby);

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



// SETTINGS CONTENT

const settingsContainer = document.querySelector('.settings-container');

function resizeSettingsPanel() {
    if (!settingsContainer) return;

    // Don't stretch the landscape design into a portrait frame —
    // checkOrientation() is about to redirect to the rotate prompt.
    const isLandscape = window.matchMedia('(orientation: landscape)').matches;
    if (!isLandscape) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const scale = vh / DESIGN_H;
    const scaledWidth = vw / scale;

    settingsContainer.style.width = `${scaledWidth}px`;
    settingsContainer.style.height = `${DESIGN_H}px`;
    settingsContainer.style.transform = `scale(${scale})`;
}

resizeSettingsPanel();
window.addEventListener('resize', resizeSettingsPanel);
screen.orientation?.addEventListener('change', resizeSettingsPanel);

// SETTINGS TAB + SIDEBAR SWITCHING
const settingsTabs = document.querySelectorAll('#settingsModal .tab');
const settingsFrame = document.getElementById('contentFrame');

settingsTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const page = tab.dataset.page;
        if (page === 'pages/sensitivity.html') {
            window.location.href = page;
            return;
        }
        settingsTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        settingsFrame.src = page;
    });
});

const settingsSideItems = document.querySelectorAll('#settingsModal .side-item');
settingsSideItems.forEach(item => {
    item.addEventListener('click', () => {
        settingsSideItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        settingsFrame.src = item.dataset.page;
    });
});

// STORE TAB + SIDEBAR SWITCHING
const storeTabs = document.querySelectorAll('#storeModal .tab');
const storeFrame = document.getElementById('storeContentFrame');

storeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        storeTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        storeFrame.src = tab.dataset.page;
    });
});

const storeSideItems = document.querySelectorAll('#storeModal .side-item');
storeSideItems.forEach(item => {
    item.addEventListener('click', () => {
        storeSideItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        storeFrame.src = item.dataset.page;
    });
});


// MAIL MODAL
// ---------- DOM refs ----------
const stgFrame    = document.getElementById("stg-content-frame");
const stgNotice   = document.getElementById("stg-tab-notice");
const stgSystem   = document.getElementById("stg-tab-system");
const stgFeedback = document.getElementById("stg-tab-feedback");
const stgClose    = document.getElementById("stg-close-btn");

const stgTabs = [stgNotice, stgSystem, stgFeedback];

function activateTab(tab, src){
    stgFrame.src = src;
    stgTabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");
}

// ---------- Event listeners ----------
// NOTE: paths now consistently point into the "mails/" folder,
// matching the iframe's initial src="mails/notice.html" in lobby.html.
stgNotice.addEventListener("click", () => activateTab(stgNotice, "mails/notice.html"));
stgSystem.addEventListener("click", () => activateTab(stgSystem, "mails/system.html"));
stgFeedback.addEventListener("click", () => activateTab(stgFeedback, "mails/feedback.html"));

// Hook this up to whatever closes the settings panel in your app
stgClose?.addEventListener("click", () => {
    // e.g. window.location.href = "lobby.html";
});

// ---------- Responsive scaling ----------
const STG_DESIGN_H = 412;
const stgContainer = document.querySelector('.stg-container');

function resizeMailPanel() {
    if (!stgContainer) return;

    // Don't stretch the landscape design into a portrait frame —
    // checkOrientation() would redirect to the rotate prompt.
    const isLandscape = window.matchMedia('(orientation: landscape)').matches;
    if (!isLandscape) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const scale = vh / STG_DESIGN_H;
    const scaledWidth = vw / scale;

    stgContainer.style.width = `${scaledWidth}px`;
    stgContainer.style.height = `${STG_DESIGN_H}px`;
    stgContainer.style.transform = `scale(${scale})`;
}

resizeMailPanel();
window.addEventListener('resize', resizeMailPanel);
screen.orientation?.addEventListener('change', resizeMailPanel);