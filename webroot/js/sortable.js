/**
 * Drag-and-drop reorder for [data-sortable] grids via SortableJS.
 *
 * Expects data-url and data-csrf-token on the container. Each direct child
 * item needs data-id. Drag starts from [data-sortable-handle] only.
 * Optional data-sortable-draggable sets the SortableJS draggable selector.
 * Optional data-sortable-handle-selector overrides the handle selector.
 */
(function () {
    'use strict';

    function collectIds(container) {
        var ids = [];
        container.querySelectorAll(':scope > [data-id]').forEach(function (el) {
            var id = el.getAttribute('data-id');
            if (id) {
                ids.push(id);
            }
        });

        return ids;
    }

    function postOrder(url, csrfToken, ids) {
        var body = new URLSearchParams();
        ids.forEach(function (id) {
            body.append('ids[]', id);
        });

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body,
            credentials: 'same-origin',
        });
    }

    function initSortable(container) {
        if (!container || container.dataset.sortableBound) {
            return;
        }
        if (typeof Sortable === 'undefined') {
            return;
        }

        container.dataset.sortableBound = '1';

        var url = container.dataset.url;
        var csrfToken = container.dataset.csrfToken || '';
        if (!url) {
            return;
        }

        var options = {
            handle: container.dataset.sortableHandleSelector || '[data-sortable-handle]',
            animation: 150,
            filter: 'a, button, .btn',
            preventOnFilter: false,
            onEnd: function () {
                postOrder(url, csrfToken, collectIds(container));
            },
        };

        var draggable = container.dataset.sortableDraggable;
        if (draggable) {
            options.draggable = draggable;
        }

        Sortable.create(container, options);
    }

    function boot() {
        document.querySelectorAll('[data-sortable]').forEach(initSortable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
