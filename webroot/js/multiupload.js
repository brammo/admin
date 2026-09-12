/**
 * Sequential multi-file upload with per-file progress modal.
 *
 * Expects a [data-multiupload] widget with data-url, data-csrf-token,
 * data-modal, and optional data-label-* attributes. The modal contains
 * overall progress, a file list, a row template, Cancel and Close buttons.
 */
(function () {
    'use strict';

    function formatBytes(bytes) {
        if (!bytes || bytes < 1024) {
            return (bytes || 0) + ' B';
        }
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }

        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function fillLabel(template, values) {
        return String(template || '').replace(/\{(\w+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(values, key) ? String(values[key]) : match;
        });
    }

    function initMultiUpload(root) {
        if (!root || root.dataset.multiuploadBound) {
            return;
        }
        root.dataset.multiuploadBound = '1';

        var input = root.querySelector('input[type="file"]');
        var modalSelector = root.dataset.modal;
        var modalEl = modalSelector ? document.querySelector(modalSelector) : null;
        if (!input || !modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        var url = root.dataset.url;
        var csrfToken = root.dataset.csrfToken || '';
        var labels = {
            uploading: root.dataset.labelUploading || 'Uploading…',
            processing: root.dataset.labelProcessing || 'Processing…',
            done: root.dataset.labelDone || 'Done',
            failed: root.dataset.labelFailed || 'Failed',
            cancelled: root.dataset.labelCancelled || 'Cancelled',
            progress: root.dataset.labelProgress || 'Uploading {n} of {total}',
            summaryOk: root.dataset.labelSummaryOk || 'Uploaded {n} of {total}',
            summaryFail: root.dataset.labelSummaryFail || 'Failed: {n}',
        };

        var listEl = modalEl.querySelector('[data-multiupload-list]');
        var rowTemplate = modalEl.querySelector('template[data-multiupload-row]');
        var counterEl = modalEl.querySelector('[data-multiupload-counter]');
        var overallBar = modalEl.querySelector('[data-multiupload-overall]');
        var cancelBtn = modalEl.querySelector('[data-multiupload-cancel]');
        var closeBtn = modalEl.querySelector('[data-multiupload-close]');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        var queue = [];
        var rows = [];
        var index = 0;
        var savedCount = 0;
        var failedCount = 0;
        var totalBytes = 0;
        var completedBytes = 0;
        var currentLoaded = 0;
        var currentXhr = null;
        var cancelled = false;
        var running = false;

        input.addEventListener('change', function () {
            if (!input.files || input.files.length === 0 || running) {
                return;
            }
            startQueue(Array.prototype.slice.call(input.files));
            input.value = '';
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', cancelQueue);
        }

        function startQueue(fileList) {
            queue = fileList;
            rows = [];
            index = 0;
            savedCount = 0;
            failedCount = 0;
            totalBytes = 0;
            completedBytes = 0;
            currentLoaded = 0;
            cancelled = false;
            running = true;
            currentXhr = null;

            listEl.innerHTML = '';
            queue.forEach(function (file) {
                totalBytes += file.size || 0;
                rows.push(createRow(file));
            });

            if (cancelBtn) {
                cancelBtn.style.display = '';
                cancelBtn.disabled = false;
            }
            if (closeBtn) {
                closeBtn.style.display = 'none';
            }

            updateOverall(0);
            updateCounter(0);
            modal.show();
            uploadNext();
        }

        function createRow(file) {
            var node = rowTemplate.content.firstElementChild.cloneNode(true);
            var nameEl = node.querySelector('[data-row-name]');
            var sizeEl = node.querySelector('[data-row-size]');
            var statusEl = node.querySelector('[data-row-status]');
            var barEl = node.querySelector('[data-row-bar]');

            if (nameEl) {
                nameEl.textContent = file.name;
                nameEl.title = file.name;
            }
            if (sizeEl) {
                sizeEl.textContent = formatBytes(file.size);
            }
            if (statusEl) {
                statusEl.textContent = labels.uploading;
            }
            setBar(barEl, 0);

            listEl.appendChild(node);

            return {
                file: file,
                node: node,
                statusEl: statusEl,
                barEl: barEl,
            };
        }

        function setBar(barEl, pct) {
            if (!barEl) {
                return;
            }
            var value = Math.max(0, Math.min(100, Math.round(pct)));
            barEl.style.width = value + '%';
            barEl.setAttribute('aria-valuenow', String(value));
            if (barEl === overallBar) {
                barEl.textContent = value + '%';
            }
        }

        function updateOverall(currentFileLoaded) {
            currentLoaded = currentFileLoaded || 0;
            var loaded = completedBytes + currentLoaded;
            var pct = totalBytes > 0 ? (loaded / totalBytes) * 100 : (index / Math.max(queue.length, 1)) * 100;
            setBar(overallBar, pct);
        }

        function updateCounter(currentIndex) {
            if (!counterEl) {
                return;
            }
            var n = Math.min(currentIndex + 1, queue.length);
            counterEl.textContent = fillLabel(labels.progress, {
                n: n,
                total: queue.length,
            });
        }

        function uploadNext() {
            if (cancelled) {
                finishQueue();
                return;
            }

            if (index >= queue.length) {
                finishQueue();
                return;
            }

            var row = rows[index];
            var file = row.file;
            updateCounter(index);
            updateOverall(0);
            setBar(row.barEl, 0);
            if (row.statusEl) {
                row.statusEl.className = 'small text-muted';
                row.statusEl.textContent = labels.uploading;
            }

            var formData = new FormData();
            formData.append('files[]', file);

            var xhr = new XMLHttpRequest();
            currentXhr = xhr;
            xhr.open('POST', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', csrfToken);
            }

            xhr.upload.addEventListener('progress', function (e) {
                if (!e.lengthComputable) {
                    return;
                }
                var pct = (e.loaded / e.total) * 100;
                setBar(row.barEl, pct);
                updateOverall(e.loaded);
                if (e.loaded >= e.total && row.statusEl) {
                    row.statusEl.textContent = labels.processing;
                }
            });

            xhr.onload = function () {
                currentXhr = null;
                handleResponse(row, xhr);
            };

            xhr.onerror = function () {
                currentXhr = null;
                markFailed(row, labels.failed);
                advance(file.size || 0);
            };

            xhr.onabort = function () {
                currentXhr = null;
                if (row.statusEl) {
                    row.statusEl.className = 'small text-warning';
                    row.statusEl.textContent = labels.cancelled;
                }
                advance(file.size || 0);
            };

            xhr.send(formData);
        }

        function handleResponse(row, xhr) {
            var payload = null;
            try {
                payload = JSON.parse(xhr.responseText);
            } catch (err) {
                markFailed(row, labels.failed);
                advance(row.file.size || 0);
                return;
            }

            var files = (payload && payload.files) || [];
            var first = files[0] || null;
            var saved = typeof payload.saved === 'number'
                ? payload.saved
                : (first && !first.error ? 1 : 0);

            if (xhr.status >= 200 && xhr.status < 300 && saved > 0 && first && !first.error) {
                markSaved(row);
            } else {
                var message = (first && first.error)
                    || (payload && payload.message)
                    || labels.failed;
                markFailed(row, message);
            }

            advance(row.file.size || 0);
        }

        function markSaved(row) {
            savedCount += 1;
            setBar(row.barEl, 100);
            if (row.barEl) {
                row.barEl.classList.add('bg-success');
            }
            if (row.statusEl) {
                row.statusEl.className = 'small text-success';
                row.statusEl.textContent = labels.done;
            }
        }

        function markFailed(row, message) {
            failedCount += 1;
            if (row.barEl) {
                row.barEl.classList.add('bg-danger');
                setBar(row.barEl, 100);
            }
            if (row.statusEl) {
                row.statusEl.className = 'small text-danger';
                row.statusEl.textContent = message || labels.failed;
            }
        }

        function advance(fileSize) {
            completedBytes += fileSize || 0;
            currentLoaded = 0;
            updateOverall(0);
            index += 1;
            uploadNext();
        }

        function cancelQueue() {
            if (!running || cancelled) {
                return;
            }
            cancelled = true;
            if (cancelBtn) {
                cancelBtn.disabled = true;
            }
            if (currentXhr) {
                currentXhr.abort();
                return;
            }
            for (var i = index; i < rows.length; i++) {
                if (rows[i].statusEl) {
                    rows[i].statusEl.className = 'small text-warning';
                    rows[i].statusEl.textContent = labels.cancelled;
                }
            }
            finishQueue();
        }

        function finishQueue() {
            running = false;
            currentXhr = null;

            if (cancelled) {
                for (var i = index; i < rows.length; i++) {
                    if (rows[i].statusEl && rows[i].statusEl.textContent === labels.uploading) {
                        rows[i].statusEl.className = 'small text-warning';
                        rows[i].statusEl.textContent = labels.cancelled;
                    }
                }
            }

            if (overallBar) {
                overallBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                if (failedCount && !savedCount) {
                    overallBar.classList.add('bg-danger');
                } else if (savedCount) {
                    overallBar.classList.add('bg-success');
                }
                setBar(overallBar, 100);
            }

            if (counterEl) {
                var summary = fillLabel(labels.summaryOk, {
                    n: savedCount,
                    total: queue.length,
                });
                if (failedCount) {
                    summary += ' — ' + fillLabel(labels.summaryFail, { n: failedCount });
                }
                counterEl.textContent = summary;
            }

            if (cancelBtn) {
                cancelBtn.style.display = 'none';
            }
            if (closeBtn) {
                closeBtn.style.display = '';
            }

            if (savedCount > 0) {
                window.setTimeout(function () {
                    window.location.reload();
                }, 600);
            }
        }
    }

    function boot() {
        document.querySelectorAll('[data-multiupload]').forEach(initMultiUpload);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
