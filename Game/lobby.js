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