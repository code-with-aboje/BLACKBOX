/* =========================================================================
   WEAPONS PAGE SCRIPT  -  "SKINS LOCKER" VERSION
   -------------------------------------------------------------------------
   This page lets the player look at each weapon and choose which SKIN
   is equipped on it. It does NOT decide which weapons go into a match
   (that would be a separate "Loadout" concept) - this page is only
   about cosmetics, one skin per weapon.

   Every weapon starts with its "Default" skin already equipped, since
   that is the very first skin listed for each weapon below.

   Read this file top to bottom - each section is labelled and every
   function explains what it does and why, in plain English.
   ========================================================================= */


/* =========================================================================
   SECTION 1: THE DATA
   -------------------------------------------------------------------------
   This is the "database" for this page, but it just lives in memory as
   a plain JavaScript object - nothing here touches a real database yet.

   Structure:
     WEAPONS = {
         categoryName: [ weaponObject, weaponObject, ... ],
         categoryName: [ ... ],
     }

   Each weaponObject looks like:
     {
         name: "VULCAN-9",              <- shown as the big title
         stats: { damage: 7, ... },     <- used to draw the bar charts
         skins: ["Default", "Ashwake"]  <- shown as clickable thumbnails
                                            in the bottom carousel
     }

   IMPORTANT: for every weapon, skins[0] is always "Default". The page
   uses that fact to equip "Default" on every weapon automatically when
   it first loads (see Section 3).

   The skin names (Default, Ashwake, Circuit, Dune, etc.) are NOT special
   keywords - they are just cosmetic labels made up as placeholders. You
   can rename them, add more, or remove them. Later, each one could point
   to a real image or 3D model instead of just plain text.
   ========================================================================= */

const WEAPONS = {
    rifle: [
        {
            name: "VULCAN-9",
            model: "../Assets/GUNS/ump_optimized.glb",
            stats: { damage: 7, rate: 6, range: 8, mobility: 4 },
            skins: ["Default", "Ashwake", "Circuit", "Dune"]
        },
        {
            name: "PHANTOM MK.II",
            stats: { damage: 6, rate: 7, range: 7, mobility: 5 },
            skins: ["Default", "Nightglass", "Rust"]
        },
        {
            name: "RAVAGE-7",
            stats: { damage: 8, rate: 5, range: 6, mobility: 3 },
            skins: ["Default", "Ember"]
        }
    ],
    pistol: [
        {
            name: "TALON",
            stats: { damage: 5, rate: 6, range: 4, mobility: 9 },
            skins: ["Default", "Copperline"]
        },
        {
            name: "WISP-1",
            stats: { damage: 4, rate: 8, range: 3, mobility: 9 },
            skins: ["Default", "Frostbyte", "Signal"]
        }
    ],
    smg: [
        {
            name: "HORNET X",
            stats: { damage: 4, rate: 9, range: 4, mobility: 8 },
            skins: ["Default", "Wasteland"]
        },
        {
            name: "STINGRAY",
            stats: { damage: 5, rate: 8, range: 5, mobility: 7 },
            skins: ["Default", "Voltage"]
        }
    ],
    shotgun: [
        {
            name: "GRAVEDIGGER",
            stats: { damage: 9, rate: 3, range: 2, mobility: 4 },
            skins: ["Default", "Scorch"]
        }
    ],
    sniper: [
        {
            name: "LONGSHADOW",
            stats: { damage: 10, rate: 1, range: 10, mobility: 2 },
            skins: ["Default", "Permafrost"]
        }
    ]
};

/* This just controls the order the stat bars are drawn in, and gives
   each internal key (like "rate") a nicer label to display ("Fire Rate"). */
const STAT_DISPLAY_ORDER = [
    { key: "damage",   label: "Damage" },
    { key: "rate",     label: "Fire Rate" },
    { key: "range",    label: "Range" },
    { key: "mobility", label: "Mobility" }
];


/* =========================================================================
   SECTION 2: PAGE STATE
   -------------------------------------------------------------------------
   "State" just means: the few pieces of information the page needs to
   remember right now, so it knows what to draw on screen.

   We are NOT saving any of this to a database yet. If you refresh the
   page, everything resets back to "Default equipped on everything".
   That is expected for now - it is the next thing to add later
   (e.g. Supabase, or even just localStorage as a simpler first step).
   ========================================================================= */

// Which category tab is currently selected (rifle, pistol, smg, shotgun, sniper)
let currentCategory = "rifle";

// Which weapon is selected inside that category's list (0 = first weapon)
let currentWeaponIndex = 0;

// Which skin is currently being PREVIEWED in the showcase.
// This is not necessarily the equipped one - clicking a skin thumbnail
// just changes this, so you can look before you equip.
let previewedSkinIndex = 0;

// This is the important one: it remembers which skin is actually
// EQUIPPED for every weapon, keyed by the weapon's name.
// Example after setup:  { "VULCAN-9": 0, "TALON": 0, "HORNET X": 0, ... }
// The number is an index into that weapon's skins array (0 = "Default").
const equippedSkinByWeapon = {};


/* =========================================================================
   SECTION 3: GIVE EVERY WEAPON ITS DEFAULT SKIN TO START WITH
   -------------------------------------------------------------------------
   This runs once, immediately, before the page draws anything. It walks
   through every category and every weapon inside WEAPONS, and marks
   skin index 0 (which is always "Default") as equipped for each one.
   ========================================================================= */

function equipDefaultSkinsOnEveryWeapon() {
    // Object.keys(WEAPONS) gives us an array of the category names,
    // e.g. ["rifle", "pistol", "smg", "shotgun", "sniper"]
    const categoryNames = Object.keys(WEAPONS);

    for (let c = 0; c < categoryNames.length; c++) {
        const categoryName = categoryNames[c];
        const weaponsInThisCategory = WEAPONS[categoryName];

        for (let w = 0; w < weaponsInThisCategory.length; w++) {
            const weapon = weaponsInThisCategory[w];
            equippedSkinByWeapon[weapon.name] = 0; // 0 = "Default"
        }
    }
}

// Run it right away, before anything is drawn.
equipDefaultSkinsOnEveryWeapon();


/* DOM */

const categoryTabElements = document.querySelectorAll(".wp-cat");
const weaponListElement = document.getElementById("wpList");
const weaponNameElement = document.getElementById("wpName");
const weaponTypeElement = document.getElementById("wpType");
const statsContainerElement = document.getElementById("wpStats");
const skinsContainerElement = document.getElementById("wpSkins");
const equipButtonElement = document.getElementById("wpEquipBtn");


/* =========================================================================
   SECTION 5: HELPER FUNCTION - GET THE CURRENTLY SELECTED WEAPON
   ========================================================================= */

function getCurrentWeapon() {
    const weaponsInThisCategory = WEAPONS[currentCategory];
    return weaponsInThisCategory[currentWeaponIndex];
}


/* =========================================================================
   SECTION 6: DRAWING THE LEFT-SIDE WEAPON LIST
   -------------------------------------------------------------------------
   Builds one row per weapon in the current category. Each row shows the
   weapon's name and, underneath it, which skin is currently equipped -
   so you can tell at a glance what's set without opening each weapon.
   ========================================================================= */

function drawWeaponList() {
    weaponListElement.innerHTML = "";

    const weaponsInThisCategory = WEAPONS[currentCategory];

    for (let i = 0; i < weaponsInThisCategory.length; i++) {
        const weapon = weaponsInThisCategory[i];
        const equippedIndex = equippedSkinByWeapon[weapon.name];
        const equippedSkinName = weapon.skins[equippedIndex];

        const rowElement = document.createElement("div");
        rowElement.className = "wp-item";

        if (i === currentWeaponIndex) {
            rowElement.classList.add("active");
        }

        rowElement.innerHTML =
            '<div class="wp-item-name">' + weapon.name + '</div>' +
            '<div class="wp-item-equipped">Equipped: ' + equippedSkinName + '</div>';

        rowElement.addEventListener("click", function () {
            currentWeaponIndex = i;
            // When switching weapons, show whatever is already equipped
            // on THAT weapon, not skin index 0 by default.
            previewedSkinIndex = equippedSkinByWeapon[weapon.name];
            drawEverything();
        });

        weaponListElement.appendChild(rowElement);
    }
}


/* =========================================================================
   SECTION 7: DRAWING THE CENTER SHOWCASE (name, subtitle, stats, button)
   ========================================================================= */

function drawShowcase() {
    const weapon = getCurrentWeapon();
    const previewedSkinName = weapon.skins[previewedSkinIndex];
    const equippedIndex = equippedSkinByWeapon[weapon.name];

    // Are we currently looking at the skin that is already equipped,
    // or are we previewing a different one?
    const isLookingAtEquippedSkin = (previewedSkinIndex === equippedIndex);

    // ---- Title + subtitle ----
    weaponNameElement.textContent = weapon.name;

    if (isLookingAtEquippedSkin) {
        weaponTypeElement.textContent = currentCategory + " \u00B7 " + previewedSkinName + " (equipped)";
        weaponTypeElement.classList.add("is-equipped");
    } else {
        weaponTypeElement.textContent = currentCategory + " \u00B7 " + previewedSkinName + " (previewing)";
        weaponTypeElement.classList.remove("is-equipped");
    }

    // ---- Stat bars ----
    statsContainerElement.innerHTML = "";

    for (let i = 0; i < STAT_DISPLAY_ORDER.length; i++) {
        const statInfo = STAT_DISPLAY_ORDER[i];
        const statValue = weapon.stats[statInfo.key];

        const rowElement = document.createElement("div");
        rowElement.className = "wp-stat-row";

        const labelElement = document.createElement("div");
        labelElement.className = "wp-stat-label";
        labelElement.textContent = statInfo.label;

        const trackElement = document.createElement("div");
        trackElement.className = "wp-stat-track";

        for (let segmentNumber = 0; segmentNumber < 10; segmentNumber++) {
            const segmentElement = document.createElement("div");
            segmentElement.className = "wp-stat-seg";

            if (segmentNumber < statValue) {
                segmentElement.classList.add("lit");
            }

            trackElement.appendChild(segmentElement);
        }

        rowElement.appendChild(labelElement);
        rowElement.appendChild(trackElement);
        statsContainerElement.appendChild(rowElement);
    }

    // ---- Equip button ----
    // If we're already looking at the equipped skin, there is nothing
    // to equip, so we show a disabled-looking "EQUIPPED" state instead.
    if (isLookingAtEquippedSkin) {
        equipButtonElement.textContent = "EQUIPPED";
        equipButtonElement.classList.add("is-equipped");
    } else {
        equipButtonElement.textContent = "EQUIP";
        equipButtonElement.classList.remove("is-equipped");
    }
}


/* =========================================================================
   SECTION 8: DRAWING THE SKIN CAROUSEL
   -------------------------------------------------------------------------
   One box per skin belonging to the current weapon. Clicking a box just
   PREVIEWS that skin (updates previewedSkinIndex) - it does not equip
   anything by itself. The box for the weapon's actually-equipped skin
   also gets a small "EQUIPPED" tag under its name, regardless of which
   one is currently being previewed.
   ========================================================================= */

function drawSkinCarousel() {
    skinsContainerElement.innerHTML = "";

    const weapon = getCurrentWeapon();
    const equippedIndex = equippedSkinByWeapon[weapon.name];

    for (let i = 0; i < weapon.skins.length; i++) {
        const skinName = weapon.skins[i];

        const skinBoxElement = document.createElement("div");
        skinBoxElement.className = "wp-skin";

        // "active" just means "this is the one currently shown in the
        // showcase above" - it does NOT mean equipped.
        if (i === previewedSkinIndex) {
            skinBoxElement.classList.add("active");
        }

        // Build the box's contents: the skin name, and an "EQUIPPED"
        // tag underneath it ONLY if this is the equipped one.
        let innerHtml = skinName;
        if (i === equippedIndex) {
            innerHtml += '<div class="wp-skin-tag">Equipped</div>';
        }
        skinBoxElement.innerHTML = innerHtml;

        skinBoxElement.addEventListener("click", function () {
            previewedSkinIndex = i;
            drawShowcase();      // updates subtitle + equip button state
            drawSkinCarousel();  // re-highlight the newly previewed box
        });

        skinsContainerElement.appendChild(skinBoxElement);
    }
}


/* =========================================================================
   SECTION 9: ONE FUNCTION TO REDRAW THE WHOLE PAGE
   ========================================================================= */

function drawEverything() {
    drawWeaponList();
    drawShowcase();
    drawSkinCarousel();
    notifyWeaponChanged();
}


/* =========================================================================
   SECTION 10: CATEGORY TAB CLICKS (RIFLE / PISTOL / SMG / SHOTGUN / SNIPER)
   ========================================================================= */

categoryTabElements.forEach(function (tabElement) {
    tabElement.addEventListener("click", function () {

        categoryTabElements.forEach(function (otherTab) {
            otherTab.classList.remove("active");
        });
        tabElement.classList.add("active");

        currentCategory = tabElement.dataset.cat;
        currentWeaponIndex = 0;

        // Show whatever is equipped on the first weapon of this new
        // category, instead of always jumping back to skin 0.
        const firstWeapon = WEAPONS[currentCategory][0];
        previewedSkinIndex = equippedSkinByWeapon[firstWeapon.name];

        drawEverything();
    });
});


/* =========================================================================
   SECTION 11: THE EQUIP BUTTON
   -------------------------------------------------------------------------
   Pressing this takes whatever skin is currently being PREVIEWED and
   makes it the EQUIPPED skin for the current weapon, by writing into
   equippedSkinByWeapon. If you're already looking at the equipped skin,
   the button shows "EQUIPPED" and clicking it does nothing new.
   ========================================================================= */

equipButtonElement.addEventListener("click", function () {
    const weapon = getCurrentWeapon();
    const equippedIndex = equippedSkinByWeapon[weapon.name];

    // Nothing to do if we're already equipped on the previewed skin.
    if (previewedSkinIndex === equippedIndex) {
        return;
    }

    equippedSkinByWeapon[weapon.name] = previewedSkinIndex;

    // Redraw everything so the list's "Equipped: ..." text, the
    // subtitle, the button state, and the carousel's tag all update.
    drawEverything();
});


/* BACK BUTTON */

 document.getElementById("wpBack").addEventListener("click", function () {
     window.parent.document.getElementById("weaponModal").classList.remove("active");
 });


/* =========================================================================
   SECTION 13: SCALE-TO-FIT (makes the fixed-size page fit any screen)
   -------------------------------------------------------------------------
   This page is designed at a fixed height of 412 pixels. This function
   stretches/shrinks it with a CSS "transform: scale(...)" so it always
   fills the real screen, no matter the device. Same technique used in
   lobby.js.

   Reminder from an earlier bug: if you ever add a SECOND function with
   this exact name in this file, the second one silently overwrites the
   first, and calling the first one too early crashes the whole script.
   Keep function names unique per file.
   ========================================================================= */

const WEAPONS_PAGE_DESIGN_HEIGHT = 412;
const weaponsContainerElement = document.getElementById("weaponsContainer");

function resizeWeaponsPage() {
    if (!weaponsContainerElement) {
        return;
    }

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    const scaleAmount = viewportHeight / WEAPONS_PAGE_DESIGN_HEIGHT;
    const scaledWidth = viewportWidth / scaleAmount;

    weaponsContainerElement.style.width = scaledWidth + "px";
    weaponsContainerElement.style.height = WEAPONS_PAGE_DESIGN_HEIGHT + "px";
    weaponsContainerElement.style.transform = "scale(" + scaleAmount + ")";
}

resizeWeaponsPage();
window.addEventListener("resize", resizeWeaponsPage);
screen.orientation?.addEventListener("change", resizeWeaponsPage);


/* =========================================================================
   SECTION 14: FIRST DRAW
   -------------------------------------------------------------------------
   Everything above this point only DEFINED functions and set up click
   listeners - none of it actually draws anything on screen yet. This
   line is what actually paints the page the first time it loads.
   ========================================================================= */
function notifyWeaponChanged() {
    const weapon = getCurrentWeapon();

    //always keep the latest info available synchronously,
    // so anyone loading later can just read it directly
    window.currentWeaponInfo = { modelPath: weapon.model, name: weapon.name };

    const event = new CustomEvent("weaponChanged", {
        detail: window.currentWeaponInfo
    });
    window.dispatchEvent(event);
}

drawEverything();