/* =========================================================
 * pdf-combiner.js
 * Extracted from the standalone PDF Combiner app and adapted
 * for the Filament plugin context.
 *
 * Expected window globals set by the Blade view before this
 * script loads:
 *   window.pdfCombiner_workerSrc  — public URL of pdf.worker.min.js
 *   window.pdfCombiner_excludePhrase — phrase to filter pages (may be '')
 * ========================================================= */
(function () {
    'use strict';

    /* ----------------------------------------------------------
     * Wait for the DOM to be ready
     * ---------------------------------------------------------- */
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {

        // -------------------------------------------------------
        // Globals injected by Blade
        // -------------------------------------------------------
        const PAGE_EXCLUDE_PHRASE = (window.pdfCombiner_excludePhrase || '').toLowerCase();
        const WORKER_SRC          = window.pdfCombiner_workerSrc || '';

        // -------------------------------------------------------
        // State
        // -------------------------------------------------------
        let pdfFiles            = [];
        let currentStep         = 1;
        let pageMapping         = [];
        let mergedPdfBytesCache = null;
        let pageTextCache       = [];
        const sourceDocCache    = new Map();
        const pdfjsDocCache     = new Map();

        // -------------------------------------------------------
        // Element refs
        // -------------------------------------------------------
        const dropZone           = document.getElementById('pdfcomb-dropZone');
        const folderInput        = document.getElementById('pdfcomb-folderInput');
        const browseFolderBtn    = document.getElementById('pdfcomb-browseFolderBtn');
        const clearBtn           = document.getElementById('pdfcomb-clearBtn');
        const step1Summary       = document.getElementById('pdfcomb-step1Summary');
        const step1Count         = document.getElementById('pdfcomb-step1Count');
        const step1Next          = document.getElementById('pdfcomb-step1Next');
        const downloadBtn        = document.getElementById('pdfcomb-downloadBtn');
        const emailBtn           = document.getElementById('pdfcomb-emailBtn');
        const rerenderPreviewBtn = document.getElementById('pdfcomb-rerenderPreviewBtn');
        const dlStatus           = document.getElementById('pdfcomb-dlStatus');
        const dlStatusText       = document.getElementById('pdfcomb-dlStatusText');
        const previewPages       = document.getElementById('pdfcomb-previewPages');
        const previewStatus      = document.getElementById('pdfcomb-previewStatus');
        const previewStatusText  = document.getElementById('pdfcomb-previewStatusText');
        const previewPageCount   = document.getElementById('pdfcomb-previewPageCount');
        const searchInput        = document.getElementById('pdfcomb-searchInput');
        const clearSearchBtn     = document.getElementById('pdfcomb-clearSearchBtn');
        const searchResults      = document.getElementById('pdfcomb-searchResults');
        const filterNotice       = document.getElementById('pdfcomb-filterNotice');

        if (!dropZone) return; // page not present

        // -------------------------------------------------------
        // Wizard navigation
        // -------------------------------------------------------
        function goToStep(n) {
            document.querySelectorAll('.pdfcomb-step-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('pdfcomb-panel-' + n).classList.add('active');
            for (let i = 1; i <= 2; i++) {
                const si = document.getElementById('pdfcomb-si-' + i);
                const nc = document.getElementById('pdfcomb-sc-num-' + i);
                si.classList.remove('active', 'done');
                if (i < n)        { si.classList.add('done');   nc.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>'; }
                else if (i === n) { si.classList.add('active'); nc.textContent = i; }
                else              {                              nc.textContent = i; }
            }
            document.getElementById('pdfcomb-sc-line-1').classList.toggle('done', n > 1);
            currentStep = n;
            if (n === 2) previewCombined();
        }

        document.getElementById('pdfcomb-step1Next').addEventListener('click', () => goToStep(2));
        document.getElementById('pdfcomb-step2Back').addEventListener('click', () => goToStep(1));

        // -------------------------------------------------------
        // Drop zone
        // -------------------------------------------------------
        dropZone.addEventListener('click', (e) => {
            if (!e.target.closest('.pdfcomb-btn-accent')) folderInput.click();
        });
        browseFolderBtn.addEventListener('click', (e) => { e.stopPropagation(); folderInput.click(); });
        dropZone.addEventListener('dragover',  (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault(); dropZone.classList.remove('drag-over');
            addFiles(Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf'));
        });
        folderInput.addEventListener('change', (e) => {
            addFiles(Array.from(e.target.files).filter(f => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf')));
            folderInput.value = '';
        });
        ['dragenter','dragover','dragleave','drop'].forEach(ev =>
            document.body.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); }, false)
        );
        clearBtn.addEventListener('click', () => { pdfFiles = []; resetPreviewState(); updateStep1UI(); });

        // -------------------------------------------------------
        // File management
        // -------------------------------------------------------
        async function addFiles(files) {
            for (const file of files) {
                if (pdfFiles.some(f => f.name === file.name && f.size === file.size)) continue;
                const buf    = await file.arrayBuffer();
                const pdfDoc = await PDFLib.PDFDocument.load(buf, { ignoreEncryption: true });
                pdfFiles.push({ file, name: file.name, size: file.size, pageCount: pdfDoc.getPageCount(), id: Date.now() + Math.random() });
            }
            resetPreviewState();
            updateStep1UI();
        }

        function updateStep1UI() {
            const has = pdfFiles.length > 0;
            step1Summary.style.display = has ? 'block' : 'none';
            step1Count.textContent = pdfFiles.length;
            step1Next.disabled = !has;
        }

        function resetPreviewState() {
            pageMapping          = [];
            mergedPdfBytesCache  = null;
            pageTextCache        = [];
            sourceDocCache.clear();
            pdfjsDocCache.clear();
            previewPages.innerHTML       = '';
            previewPageCount.textContent = '';
            searchInput.value            = '';
            searchResults.style.display  = 'none';
            filterNotice.style.display   = 'none';
        }

        // -------------------------------------------------------
        // Page mapping / merge
        // -------------------------------------------------------
        async function buildInitialPageMapping() {
            pageMapping = [];
            filterNotice.style.display = 'none';
            if (!pdfFiles.length) return;
            const phraseLower  = PAGE_EXCLUDE_PHRASE;
            let skippedTotal   = 0;
            const skippedDetails = [];
            if (WORKER_SRC && typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = WORKER_SRC;
            }
            setProgressText('Analysing pages\u2026');
            for (let si = 0; si < pdfFiles.length; si++) {
                const file = pdfFiles[si];
                let pdfjsDoc = pdfjsDocCache.get(si);
                if (!pdfjsDoc) {
                    try {
                        const buf = await file.file.arrayBuffer();
                        pdfjsDoc  = await pdfjsLib.getDocument({ data: buf.slice(0) }).promise;
                        pdfjsDocCache.set(si, pdfjsDoc);
                    } catch {
                        for (let p = 0; p < file.pageCount; p++) pageMapping.push({ sourceIndex: si, pageIndexInSource: p });
                        continue;
                    }
                }
                const skipped = [];
                const total   = pdfjsDoc.numPages || file.pageCount;
                for (let p = 0; p < total; p++) {
                    setProgressText('Analysing: ' + file.name + ' (' + (p + 1) + '/' + total + ')');
                    let exclude = false;
                    if (phraseLower) {
                        try {
                            const page = await pdfjsDoc.getPage(p + 1);
                            const tc   = await page.getTextContent();
                            exclude    = tc.items.map(i => i.str).join(' ').toLowerCase().includes(phraseLower);
                        } catch { /* keep page */ }
                    }
                    if (exclude) { skippedTotal++; skipped.push(p + 1); }
                    else pageMapping.push({ sourceIndex: si, pageIndexInSource: p });
                }
                if (skipped.length) skippedDetails.push({ name: file.name, pages: skipped });
            }
            if (skippedTotal > 0) {
                filterNotice.style.display = 'block';
                filterNotice.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem;display:inline;vertical-align:middle;margin-right:.25rem;"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>' +
                    'Skipped ' + skippedTotal + ' page' + (skippedTotal !== 1 ? 's' : '') +
                    ' containing &ldquo;' + escapeHtml(PAGE_EXCLUDE_PHRASE) + '&rdquo;. ' +
                    skippedDetails.map(d => escapeHtml(d.name) + ': pages ' + d.pages.join(', ')).join(' &middot; ');
            }
        }

        async function ensureSourceDocs() {
            for (let i = 0; i < pdfFiles.length; i++) {
                if (!sourceDocCache.has(i)) {
                    const buf = await pdfFiles[i].file.arrayBuffer();
                    sourceDocCache.set(i, await PDFLib.PDFDocument.load(buf, { ignoreEncryption: true }));
                }
            }
        }

        async function buildMergedPdfFromPageMapping() {
            const merged = await PDFLib.PDFDocument.create();
            await ensureSourceDocs();
            for (const { sourceIndex, pageIndexInSource } of pageMapping) {
                try {
                    const [pg] = await merged.copyPages(sourceDocCache.get(sourceIndex), [pageIndexInSource]);
                    merged.addPage(pg);
                } catch (err) { console.error('copyPage failed', err); }
            }
            return merged.save({ useObjectStreams: false });
        }

        // -------------------------------------------------------
        // Preview
        // -------------------------------------------------------
        async function previewCombined() {
            if (!pdfFiles.length) return;
            if (mergedPdfBytesCache && previewPages.children.length > 0) return;
            showPreviewStatus('Building page list\u2026');
            if (pageMapping.length === 0) await buildInitialPageMapping();
            if (pageMapping.length === 0) {
                hidePreviewStatus();
                showAlert('All pages were excluded because they contain "' + PAGE_EXCLUDE_PHRASE + '".');
                return;
            }
            showPreviewStatus('Merging PDFs\u2026');
            mergedPdfBytesCache = await buildMergedPdfFromPageMapping();
            if (!mergedPdfBytesCache || mergedPdfBytesCache.byteLength < 50) {
                hidePreviewStatus();
                showAlert('Failed to merge PDFs. Check that the source files are valid.');
                return;
            }
            showPreviewStatus('Rendering pages\u2026');
            await renderPreviewPages();
            hidePreviewStatus();
        }

        async function rebuildFromFiles() {
            showPreviewStatus('Rebuilding\u2026');
            mergedPdfBytesCache = null;
            await buildInitialPageMapping();
            if (pageMapping.length === 0) { hidePreviewStatus(); showAlert('All pages excluded.'); return; }
            mergedPdfBytesCache = await buildMergedPdfFromPageMapping();
            if (!mergedPdfBytesCache || mergedPdfBytesCache.byteLength < 50) { hidePreviewStatus(); showAlert('Merge failed.'); return; }
            showPreviewStatus('Rendering pages\u2026');
            await renderPreviewPages();
            hidePreviewStatus();
        }

        async function renderPreviewPages() {
            previewPages.innerHTML = '';
            pageTextCache = [];
            previewPageCount.textContent = '(' + pageMapping.length + ' pages)';
            if (!mergedPdfBytesCache) return;
            if (WORKER_SRC && typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = WORKER_SRC;
            }
            const pdf = await pdfjsLib.getDocument({ data: mergedPdfBytesCache }).promise;
            for (let n = 1; n <= pdf.numPages; n++) {
                const page     = await pdf.getPage(n);
                const tc       = await page.getTextContent();
                pageTextCache.push(tc.items.map(i => i.str).join(' '));
                const viewport = page.getViewport({ scale: 0.6 });
                const canvas   = document.createElement('canvas');
                canvas.width   = viewport.width;
                canvas.height  = viewport.height;
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                const col = document.createElement('div');
                col.className = 'pdfcomb-page-col';
                col.dataset.pageNum = n;
                col.innerHTML =
                    '<div class="pdfcomb-preview-card">' +
                    '<div class="pdfcomb-preview-num">' +
                    '<span>Page ' + n + '<span class="pdfcomb-match-indicator" style="display:none;"></span></span>' +
                    '<button class="pdfcomb-btn-remove" data-page-index="' + (n - 1) + '" title="Remove page">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>' +
                    '</button>' +
                    '</div>' +
                    '<div class="pdfcomb-canvas-wrap"></div>' +
                    '</div>';
                col.querySelector('.pdfcomb-canvas-wrap').appendChild(canvas);
                previewPages.appendChild(col);
            }
            previewPages.querySelectorAll('button[data-page-index]').forEach(btn =>
                btn.addEventListener('click', e => removePreviewPage(parseInt(e.currentTarget.dataset.pageIndex)))
            );
            if (searchInput.value.trim()) handleSearch();
        }

        async function removePreviewPage(idx) {
            if (idx < 0 || idx >= pageMapping.length) return;
            pageMapping.splice(idx, 1);
            showPreviewStatus('Updating\u2026');
            mergedPdfBytesCache = await buildMergedPdfFromPageMapping();
            await renderPreviewPages();
            hidePreviewStatus();
        }

        rerenderPreviewBtn.addEventListener('click', rebuildFromFiles);

        // -------------------------------------------------------
        // Download
        // -------------------------------------------------------
        downloadBtn.addEventListener('click', downloadMerged);

        async function downloadMerged() {
            if (!pdfFiles.length) { showAlert('Please add PDF files first.'); return; }
            downloadBtn.disabled = true;
            let bytes = mergedPdfBytesCache;
            try {
                if (!bytes || bytes.byteLength < 50) {
                    if (pageMapping.length === 0) { showPreviewStatus('Analysing pages\u2026'); await buildInitialPageMapping(); }
                    if (pageMapping.length === 0) { showAlert('All pages excluded.'); return; }
                    showDlStatus('Merging ' + pageMapping.length + ' page' + (pageMapping.length !== 1 ? 's' : '') + '\u2026');
                    bytes = await buildMergedPdfFromPageMapping();
                    if (!bytes || bytes.byteLength < 50) { showAlert('Merge failed.'); return; }
                    mergedPdfBytesCache = bytes;
                }
                showDlStatus('Preparing download\u2026');
                triggerDownload(bytes);
            } finally { hideDlStatus(); downloadBtn.disabled = false; }
        }

        function triggerDownload(bytes) {
            const blob = new Blob([bytes], { type: 'application/pdf' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url; a.download = 'merged_' + Date.now() + '.pdf';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // -------------------------------------------------------
        // Email — triggers Alpine modal via custom event
        // -------------------------------------------------------
        emailBtn.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('pdfcomb-open-email-modal'));
        });

        // The send button is wired in the Blade via Alpine x-on:click
        window.pdfCombinerSendEmail = async function () {
            const sendBtn = document.getElementById('pdfcomb-sendEmailBtn');
            if (sendBtn) sendBtn.disabled = true;
            try {
                let bytes = mergedPdfBytesCache;
                if (!bytes || bytes.byteLength < 50) {
                    if (pageMapping.length === 0) await buildInitialPageMapping();
                    bytes = await buildMergedPdfFromPageMapping();
                    if (!bytes || bytes.byteLength < 50) { showAlert('Merge failed.'); return; }
                    mergedPdfBytesCache = bytes;
                }
                triggerDownload(bytes);
                const to      = document.getElementById('pdfcomb-emailTo').value.trim();
                const subject = document.getElementById('pdfcomb-emailSubject').value.trim();
                const body    = document.getElementById('pdfcomb-emailBody').value.trim();
                window.location.href = 'mailto:' + encodeURIComponent(to) +
                    '?subject=' + encodeURIComponent(subject) +
                    '&body=' + encodeURIComponent(body);
                window.dispatchEvent(new CustomEvent('pdfcomb-close-email-modal'));
            } finally {
                if (sendBtn) sendBtn.disabled = false;
            }
        };

        // -------------------------------------------------------
        // Status helpers
        // -------------------------------------------------------
        function showPreviewStatus(msg) { previewStatusText.textContent = msg; previewStatus.classList.remove('hidden'); }
        function hidePreviewStatus()    { previewStatus.classList.add('hidden'); }
        function showDlStatus(msg)      { dlStatusText.textContent = msg; dlStatus.classList.remove('hidden'); }
        function hideDlStatus()         { dlStatus.classList.add('hidden'); }
        function setProgressText(msg) {
            if (!previewStatus.classList.contains('hidden')) previewStatusText.textContent = msg;
            if (!dlStatus.classList.contains('hidden'))      dlStatusText.textContent      = msg;
        }

        // -------------------------------------------------------
        // Search
        // -------------------------------------------------------
        searchInput.addEventListener('input', handleSearch);
        clearSearchBtn.addEventListener('click', () => { searchInput.value = ''; handleSearch(); });

        function handleSearch() {
            const raw = searchInput.value.trim();
            document.querySelectorAll('.pdfcomb-preview-card.page-highlight').forEach(el => el.classList.remove('page-highlight'));
            document.querySelectorAll('.pdfcomb-match-indicator').forEach(el => { el.style.display = 'none'; el.textContent = ''; el.removeAttribute('title'); });
            if (!raw) { searchResults.style.display = 'none'; return; }
            const terms   = raw.split(',').map(t => t.trim().toLowerCase()).filter(Boolean);
            const perTerm = {};
            const termRx  = {};
            terms.forEach(t => { perTerm[t] = { count: 0, pages: new Set() }; termRx[t] = new RegExp(escapeRegex(t), 'g'); });
            let total = 0;
            const matchPages = [];
            pageTextCache.forEach((text, idx) => {
                const lower = text.toLowerCase();
                let cnt = 0; const breakdown = {};
                terms.forEach(t => {
                    const m = (lower.match(termRx[t]) || []).length;
                    if (m) { cnt += m; perTerm[t].count += m; perTerm[t].pages.add(idx + 1); breakdown[t] = m; }
                });
                if (cnt) {
                    total += cnt; matchPages.push(idx + 1);
                    const col = previewPages.querySelector('[data-page-num="' + (idx + 1) + '"]');
                    if (col) {
                        col.querySelector('.pdfcomb-preview-card').classList.add('page-highlight');
                        const ind = col.querySelector('.pdfcomb-match-indicator');
                        ind.style.display = 'inline';
                        ind.classList.add('pdfcomb-match-badge');
                        ind.textContent = cnt;
                        ind.title = Object.entries(breakdown).map(([k, v]) => k + ': ' + v).join(', ');
                    }
                }
            });
            searchResults.style.display = 'block';
            if (total > 0) {
                const bd = terms.map(t => escapeHtml(t) + '(' + perTerm[t].count + ')').join(', ');
                searchResults.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="inline w-4 h-4 mr-1 text-green-500"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>' +
                    total + ' match' + (total > 1 ? 'es' : '') + ' on ' + matchPages.length + ' page' + (matchPages.length > 1 ? 's' : '') + '. ' +
                    (terms.length > 1 ? 'Breakdown: ' + bd + '. ' : '') + 'Pages: ' + matchPages.join(', ');
                previewPages.querySelector('[data-page-num="' + matchPages[0] + '"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                searchResults.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="inline w-4 h-4 mr-1 text-red-500"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>' +
                    'No matches for ' + terms.map(t => '"' + escapeHtml(t) + '"').join(', ');
            }
        }

        // -------------------------------------------------------
        // Utilities
        // -------------------------------------------------------
        function escapeRegex(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
        function escapeHtml(t)  { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        function showAlert(msg) {
            // Dispatch an Alpine-compatible event so the toast can show
            window.dispatchEvent(new CustomEvent('pdfcomb-alert', { detail: { message: msg } }));
        }

    }); // end ready()

})();
