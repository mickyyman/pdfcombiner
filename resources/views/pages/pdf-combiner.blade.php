<x-filament-panels::page>
    {{-- Inject runtime config for pdf-combiner.js --}}
    <script>
        window.pdfCombiner_workerSrc     = "{{ asset('vendor/mickyyman/pdf-combiner/pdf.worker.min.js') }}";
        window.pdfCombiner_excludePhrase = @js($this->excludePhrase);
    </script>

    <style>
        /* CSS variables */
        .pdfcomb-wrap {
            --pc-accent:   #7c3aed;
            --pc-accent-h: #6d28d9;
            --pc-danger:   #ef4444;
            --pc-success:  #22c55e;
            --pc-warning:  #f59e0b;
            --pc-surface:  rgb(var(--gray-100));
            --pc-border:   rgb(var(--gray-200));
            --pc-muted:    rgb(var(--gray-400));
            --pc-text:     rgb(var(--gray-900));
        }
        .dark .pdfcomb-wrap {
            --pc-surface: rgb(var(--gray-800));
            --pc-border:  rgb(var(--gray-700));
            --pc-muted:   rgb(var(--gray-400));
            --pc-text:    rgb(var(--gray-100));
        }
        /* Step bar */
        .pdfcomb-steps-bar { display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem; }
        .pdfcomb-step { display:flex;align-items:center;gap:.5rem; }
        .pdfcomb-step-circle {
            width:30px;height:30px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:.75rem;font-weight:700;flex-shrink:0;
            border:2px solid var(--pc-border);background:transparent;color:var(--pc-muted);
            transition:all .3s;
        }
        .pdfcomb-step.active .pdfcomb-step-circle { border-color:var(--pc-accent);background:var(--pc-accent);color:#fff; }
        .pdfcomb-step.done   .pdfcomb-step-circle { border-color:var(--pc-success);background:var(--pc-success);color:#fff; }
        .pdfcomb-step-connector { width:48px;height:2px;margin:0 .5rem;background:var(--pc-border);flex-shrink:0;transition:background .3s; }
        .pdfcomb-step-connector.done { background:var(--pc-success); }
        .pdfcomb-step-label { font-size:.78rem;color:var(--pc-muted);white-space:nowrap;transition:color .3s; }
        .pdfcomb-step.active .pdfcomb-step-label { color:var(--pc-text);font-weight:600; }
        .pdfcomb-step.done   .pdfcomb-step-label { color:var(--pc-success); }
        /* Wizard card */
        .pdfcomb-card { background:var(--pc-surface);border:1px solid var(--pc-border);border-radius:12px;padding:1.5rem; }
        /* Panels */
        .pdfcomb-step-panel        { display:none; }
        .pdfcomb-step-panel.active { display:block; }
        /* Drop zone */
        .pdfcomb-drop-zone {
            border:2px dashed var(--pc-border);border-radius:10px;
            padding:3rem 2rem;text-align:center;cursor:pointer;
            transition:border-color .25s,background .25s;background:transparent;
        }
        .pdfcomb-drop-zone:hover,.pdfcomb-drop-zone.drag-over { border-color:var(--pc-accent);background:rgba(124,58,237,.06); }
        .pdfcomb-dz-icon  { margin-bottom:.75rem; }
        .pdfcomb-dz-title { font-weight:600;margin-bottom:.25rem;color:var(--pc-text);font-size:.95rem; }
        .pdfcomb-dz-sub   { font-size:.875rem;margin-bottom:1rem;color:var(--pc-muted); }
        /* Buttons */
        .pdfcomb-btn-accent {
            background:var(--pc-accent);border:none;color:#fff;
            padding:.5rem 1.25rem;border-radius:8px;font-weight:600;font-size:.875rem;
            cursor:pointer;transition:background .2s,transform .15s;
            display:inline-flex;align-items:center;gap:.4rem;
        }
        .pdfcomb-btn-accent:hover:not(:disabled) { background:var(--pc-accent-h);transform:translateY(-1px); }
        .pdfcomb-btn-accent:disabled { opacity:.45;cursor:not-allowed;transform:none; }
        .pdfcomb-btn-ghost {
            background:transparent;border:1px solid var(--pc-border);color:var(--pc-muted);
            padding:.4rem .9rem;border-radius:8px;font-size:.875rem;cursor:pointer;
            transition:border-color .2s,color .2s;
            display:inline-flex;align-items:center;gap:.35rem;
        }
        .pdfcomb-btn-ghost:hover { border-color:var(--pc-text);color:var(--pc-text); }
        .pdfcomb-btn-remove {
            background:transparent;border:1px solid var(--pc-danger);color:var(--pc-danger);
            padding:.15rem .3rem;border-radius:5px;font-size:.75rem;cursor:pointer;
            display:inline-flex;align-items:center;justify-content:center;gap:.25rem;
            transition:background .2s;
        }
        .pdfcomb-btn-remove:hover { background:rgba(239,68,68,.1); }
        /* Step 1 summary */
        .pdfcomb-summary-row { display:flex;align-items:center;justify-content:space-between;margin-top:.75rem; }
        .pdfcomb-summary-count { font-size:.875rem;color:var(--pc-muted);display:flex;align-items:center;gap:.3rem; }
        /* Wizard nav */
        .pdfcomb-nav { display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--pc-border); }
        /* Toolbar (step 2) */
        .pdfcomb-toolbar      { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem; }
        .pdfcomb-toolbar-left { font-weight:600;font-size:.875rem;color:var(--pc-text);display:flex;align-items:center;gap:.3rem; }
        .pdfcomb-toolbar-btns { display:flex;align-items:center;gap:.5rem;flex-wrap:wrap; }
        .pdfcomb-preview-count { font-weight:400;font-size:.75rem;color:var(--pc-muted);margin-left:.25rem; }
        /* Status bar */
        .pdfcomb-status { background:var(--pc-surface);border:1px solid var(--pc-border);border-radius:8px;padding:.6rem 1rem;display:flex;align-items:center;gap:.75rem;font-size:.85rem;color:var(--pc-muted);margin-top:.75rem; }
        .pdfcomb-status.hidden { display:none; }
        .pdfcomb-spinner { width:1rem;height:1rem;border-radius:50%;flex-shrink:0;border:2px solid var(--pc-border);border-top-color:var(--pc-accent);animation:pdfcomb-spin .7s linear infinite; }
        @keyframes pdfcomb-spin { to { transform:rotate(360deg); } }
        /* Filter notice */
        .pdfcomb-filter-notice { background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:8px;padding:.55rem .85rem;font-size:.8rem;color:var(--pc-warning);margin-bottom:.75rem; }
        /* Search */
        .pdfcomb-search-row { margin-bottom:.75rem; }
        .pdfcomb-search-wrap { display:flex;align-items:stretch;border:1px solid var(--pc-border);border-radius:8px;overflow:hidden; }
        .pdfcomb-search-prefix { background:var(--pc-surface);padding:0 .75rem;display:flex;align-items:center;color:var(--pc-muted); }
        .pdfcomb-search-input { flex:1;background:transparent;border:none;outline:none;padding:.45rem .5rem;font-size:.875rem;color:var(--pc-text); }
        .pdfcomb-search-input::placeholder { color:var(--pc-muted); }
        .pdfcomb-search-clear { background:var(--pc-surface);padding:0 .75rem;display:flex;align-items:center;cursor:pointer;color:var(--pc-muted);transition:color .2s; }
        .pdfcomb-search-clear:hover { color:var(--pc-text); }
        .pdfcomb-search-results { display:none;margin-top:.25rem;font-size:.75rem;color:var(--pc-muted); }
        /* Preview grid */
        .pdfcomb-preview-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;margin-top:.5rem; }
        @media (min-width:640px) { .pdfcomb-preview-grid { grid-template-columns:repeat(3,1fr); } }
        @media (min-width:768px) { .pdfcomb-preview-grid { grid-template-columns:repeat(4,1fr); } }
        .pdfcomb-preview-card { background:var(--pc-surface);border:1px solid var(--pc-border);border-radius:8px;padding:.4rem;transition:border-color .2s; }
        .pdfcomb-preview-card.page-highlight { border-color:var(--pc-warning)!important;box-shadow:0 0 0 2px rgba(245,158,11,.35); }
        .pdfcomb-preview-num { font-size:.68rem;color:var(--pc-muted);margin-bottom:.25rem;display:flex;justify-content:space-between;align-items:center; }
        .pdfcomb-canvas-wrap { display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:4px; }
        .pdfcomb-canvas-wrap canvas { max-width:100%;height:auto;display:block; }
        .pdfcomb-match-badge { background:var(--pc-warning);color:#000;font-weight:700;padding:.05rem .25rem;border-radius:3px;font-size:.65rem;margin-left:.25rem; }
        /* Modal */
        .pdfcomb-modal-overlay { position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:1rem; }
        .pdfcomb-modal-box { background:var(--pc-surface);border:1px solid var(--pc-border);border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.4); }
        .pdfcomb-modal-header { display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--pc-border); }
        .pdfcomb-modal-title  { font-weight:600;font-size:.95rem;color:var(--pc-text);display:flex;align-items:center;gap:.4rem; }
        .pdfcomb-modal-body   { padding:1.25rem; }
        .pdfcomb-modal-footer { display:flex;justify-content:flex-end;gap:.5rem;padding:.75rem 1.25rem;border-top:1px solid var(--pc-border); }
        .pdfcomb-form-group   { margin-bottom:.75rem; }
        .pdfcomb-form-label   { font-size:.78rem;color:var(--pc-muted);display:block;margin-bottom:.25rem; }
        .pdfcomb-form-ctrl    { width:100%;background:transparent;border:1px solid var(--pc-border);border-radius:8px;padding:.4rem .7rem;font-size:.875rem;color:var(--pc-text);outline:none;transition:border-color .2s;box-sizing:border-box; }
        .pdfcomb-form-ctrl:focus { border-color:var(--pc-accent);box-shadow:0 0 0 3px rgba(124,58,237,.2); }
        .pdfcomb-modal-notice { background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:8px;padding:.6rem .8rem;font-size:.78rem;color:var(--pc-warning);display:flex;align-items:flex-start;gap:.4rem; }
        /* Footer */
        .pdfcomb-footer { text-align:center;font-size:.75rem;margin-top:1rem;color:var(--pc-muted);display:flex;align-items:center;justify-content:center;gap:.3rem; }
        /* Toast */
        .pdfcomb-toast { position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;background:#1e293b;color:#f1f5f9;border-radius:8px;padding:.75rem 1.1rem;font-size:.875rem;max-width:340px;box-shadow:0 8px 24px rgba(0,0,0,.4); }
        /* Icon helpers */
        .pdfcomb-icon-sm { width:1rem;height:1rem;flex-shrink:0;display:inline-block;vertical-align:middle; }
        .pdfcomb-icon-dz { width:3rem;height:3rem;display:block;margin:0 auto;color:var(--pc-accent); }
    </style>

    <div
        class="pdfcomb-wrap"
        x-data="{
            emailModalOpen: false,
            toastMsg: '',
            toastVisible: false,
            showToast(msg) {
                this.toastMsg = msg;
                this.toastVisible = true;
                setTimeout(() => { this.toastVisible = false; }, 4000);
            }
        }"
        x-on:pdfcomb-open-email-modal.window="emailModalOpen = true"
        x-on:pdfcomb-close-email-modal.window="emailModalOpen = false"
        x-on:pdfcomb-alert.window="showToast($event.detail.message)"
    >
        {{-- Step bar --}}
        <div class="pdfcomb-steps-bar">
            <div class="pdfcomb-step active" id="pdfcomb-si-1">
                <div class="pdfcomb-step-circle" id="pdfcomb-sc-num-1">1</div>
                <div class="pdfcomb-step-label">Add Files</div>
            </div>
            <div class="pdfcomb-step-connector" id="pdfcomb-sc-line-1"></div>
            <div class="pdfcomb-step" id="pdfcomb-si-2">
                <div class="pdfcomb-step-circle" id="pdfcomb-sc-num-2">2</div>
                <div class="pdfcomb-step-label">Preview &amp; Export</div>
            </div>
        </div>

        {{-- Wizard card --}}
        <div class="pdfcomb-card">

            {{-- Step 1 --}}
            <div class="pdfcomb-step-panel active" id="pdfcomb-panel-1">
                <div class="pdfcomb-drop-zone" id="pdfcomb-dropZone">
                    <div class="pdfcomb-dz-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pdfcomb-icon-dz">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.338-2.32 5.75 5.75 0 0 1 1.33 11.094"/>
                        </svg>
                    </div>
                    <div class="pdfcomb-dz-title">Drag &amp; Drop PDF files here</div>
                    <div class="pdfcomb-dz-sub">or pick a folder</div>
                    <button class="pdfcomb-btn-accent" id="pdfcomb-browseFolderBtn" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                            <path d="M2 6a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6z"/>
                        </svg>
                        Select Folder
                    </button>
                    <input type="file" id="pdfcomb-folderInput" accept=".pdf" multiple style="display:none;" webkitdirectory directory>
                </div>

                <div id="pdfcomb-step1Summary" style="display:none;">
                    <div class="pdfcomb-summary-row">
                        <span class="pdfcomb-summary-count">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm" style="color:var(--pc-success)">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            <span id="pdfcomb-step1Count">0</span> file(s) ready
                        </span>
                        <button class="pdfcomb-btn-remove" id="pdfcomb-clearBtn" style="padding:.25rem .6rem;font-size:.8rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5z" clip-rule="evenodd"/>
                            </svg>
                            Clear all
                        </button>
                    </div>
                </div>

                <div class="pdfcomb-nav">
                    <div></div>
                    <button class="pdfcomb-btn-accent" id="pdfcomb-step1Next" disabled>
                        Next
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="pdfcomb-step-panel" id="pdfcomb-panel-2">

                <div class="pdfcomb-toolbar">
                    <span class="pdfcomb-toolbar-left">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm" style="color:var(--pc-accent)">
                            <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                            <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" clip-rule="evenodd"/>
                        </svg>
                        Preview
                        <span id="pdfcomb-previewPageCount" class="pdfcomb-preview-count"></span>
                    </span>
                    <div class="pdfcomb-toolbar-btns">
                        <button class="pdfcomb-btn-ghost" id="pdfcomb-rerenderPreviewBtn" style="padding:.3rem .7rem;font-size:.8rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39zm1.23-3.723a.75.75 0 0 0 .219-.53V3.93a.75.75 0 0 0-1.5 0V6.36l-.31-.31A7 7 0 0 0 3.239 9.187a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219z" clip-rule="evenodd"/>
                            </svg>
                            Rebuild
                        </button>
                        <button class="pdfcomb-btn-ghost" id="pdfcomb-emailBtn" style="padding:.3rem .7rem;font-size:.8rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                                <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3z"/>
                                <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839z"/>
                            </svg>
                            Email
                        </button>
                        <button class="pdfcomb-btn-accent" id="pdfcomb-downloadBtn" style="padding:.38rem .9rem;font-size:.85rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                                <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75z"/>
                                <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/>
                            </svg>
                            Download
                        </button>
                    </div>
                </div>

                <div class="pdfcomb-search-row">
                    <div class="pdfcomb-search-wrap">
                        <div class="pdfcomb-search-prefix">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11zM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input type="text" class="pdfcomb-search-input" id="pdfcomb-searchInput" placeholder="Search pages (comma-separated terms)&hellip;">
                        <div class="pdfcomb-search-clear" id="pdfcomb-clearSearchBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z"/>
                            </svg>
                        </div>
                    </div>
                    <div id="pdfcomb-searchResults" class="pdfcomb-search-results"></div>
                </div>

                <div id="pdfcomb-filterNotice" class="pdfcomb-filter-notice" style="display:none;"></div>

                <div class="pdfcomb-status hidden" id="pdfcomb-previewStatus">
                    <div class="pdfcomb-spinner"></div>
                    <span id="pdfcomb-previewStatusText">Generating preview&hellip;</span>
                </div>

                <div id="pdfcomb-previewPages" class="pdfcomb-preview-grid"></div>

                <div class="pdfcomb-status hidden" id="pdfcomb-dlStatus">
                    <div class="pdfcomb-spinner"></div>
                    <span id="pdfcomb-dlStatusText">Processing&hellip;</span>
                </div>

                <div class="pdfcomb-nav">
                    <button class="pdfcomb-btn-ghost" id="pdfcomb-step2Back">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08L3.23 10.54a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10z" clip-rule="evenodd"/>
                        </svg>
                        Back
                    </button>
                    <div></div>
                </div>
            </div>

        </div>{{-- /.pdfcomb-card --}}

        <p class="pdfcomb-footer">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1zm3 8V5.5a3 3 0 1 0-6 0V9h6z" clip-rule="evenodd"/>
            </svg>
            All processing happens in your browser. Files are never uploaded.
        </p>

        {{-- Email modal --}}
        <div
            class="pdfcomb-modal-overlay"
            x-show="emailModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click.self="emailModalOpen = false"
            style="display:none;"
        >
            <div class="pdfcomb-modal-box">
                <div class="pdfcomb-modal-header">
                    <span class="pdfcomb-modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm" style="color:var(--pc-accent)">
                            <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3z"/>
                            <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839z"/>
                        </svg>
                        Send via email
                    </span>
                    <button class="pdfcomb-btn-ghost" style="padding:.2rem .45rem;" x-on:click="emailModalOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z"/>
                        </svg>
                    </button>
                </div>
                <div class="pdfcomb-modal-body">
                    <div class="pdfcomb-form-group">
                        <label class="pdfcomb-form-label" for="pdfcomb-emailTo">Recipient</label>
                        <input type="email" class="pdfcomb-form-ctrl" id="pdfcomb-emailTo" placeholder="recipient@example.com">
                    </div>
                    <div class="pdfcomb-form-group">
                        <label class="pdfcomb-form-label" for="pdfcomb-emailSubject">Subject</label>
                        <input type="text" class="pdfcomb-form-ctrl" id="pdfcomb-emailSubject" value="Merged PDF">
                    </div>
                    <div class="pdfcomb-form-group">
                        <label class="pdfcomb-form-label" for="pdfcomb-emailBody">Message</label>
                        <textarea class="pdfcomb-form-ctrl" id="pdfcomb-emailBody" rows="3" style="resize:vertical;">Please find the merged PDF attached.</textarea>
                    </div>
                    <div class="pdfcomb-modal-notice">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm" style="margin-top:.15rem;flex-shrink:0;">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9z" clip-rule="evenodd"/>
                        </svg>
                        <span>Browsers cannot attach files to email. The PDF will be <strong>downloaded first</strong> &mdash; attach it manually after your mail client opens.</span>
                    </div>
                </div>
                <div class="pdfcomb-modal-footer">
                    <button class="pdfcomb-btn-ghost" style="font-size:.85rem;" x-on:click="emailModalOpen = false">Cancel</button>
                    <button class="pdfcomb-btn-accent" id="pdfcomb-sendEmailBtn" style="font-size:.85rem;" x-on:click="window.pdfCombinerSendEmail()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pdfcomb-icon-sm">
                            <path d="M3.105 2.289a.75.75 0 0 0-.826.95l1.414 4.949A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.062l-1.414 4.95a.75.75 0 0 0 .826.95 28.896 28.896 0 0 0 15.293-7.154.75.75 0 0 0 0-1.115A28.897 28.897 0 0 0 3.105 2.289z"/>
                        </svg>
                        Download &amp; Open Mail
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div
            class="pdfcomb-toast"
            x-show="toastVisible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display:none;"
            x-text="toastMsg"
        ></div>

    </div>{{-- /.pdfcomb-wrap --}}

</x-filament-panels::page>