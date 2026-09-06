/* Modern theme script: clock, dropdown navigation, properties dialog, quick actions. */
(function () {
    'use strict';

    /* ---------- Clock ---------- */

    var clockEl = document.getElementById('clock');

    function renderClock() {
        if (!clockEl) {
            return;
        }
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        clockEl.textContent = '🕐 ' + hh + ':' + mm;
    }

    renderClock();
    setInterval(renderClock, 30000);

    /* ---------- Categorized dropdown navigation ---------- */

    var groups = document.querySelectorAll('[data-dropdown]');
    var openGroup = null;

    function closeGroup(group) {
        if (!group) {
            return;
        }
        group.classList.remove('open');
        var toggle = group.querySelector('.nav-drop-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function closeAllGroups() {
        groups.forEach(closeGroup);
        openGroup = null;
    }

    groups.forEach(function (group) {
        var toggle = group.querySelector('.nav-drop-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = group.classList.contains('open');
            closeAllGroups();
            if (!wasOpen) {
                group.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
                openGroup = group;
            }
        });

        group.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeGroup(group);
                if (openGroup === group) {
                    openGroup = null;
                    toggle.focus();
                }
            }
        });
    });

    document.addEventListener('click', function (e) {
        if (openGroup && !openGroup.contains(e.target)) {
            closeAllGroups();
        }
    });

    /* ---------- Properties dialog ---------- */

    var overlay = document.getElementById('propertiesOverlay');

    window.openProperties = function () {
        if (overlay) {
            overlay.classList.add('open');
        }
    };

    window.closeProperties = function () {
        if (overlay) {
            overlay.classList.remove('open');
        }
    };

    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeProperties();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeProperties();
        }
    });

    /* ---------- Generic data-action handler (quick actions) ---------- */
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-action]');
        if (!el) {
            return;
        }
        var action = el.getAttribute('data-action');
        if (action === 'properties') {
            e.preventDefault();
            openProperties();
        } else if (action === 'toggle_icons') {
            e.preventDefault();
            document.body.classList.toggle('hide-icons');
        }
    });
})();
