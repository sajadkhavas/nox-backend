<x-filament-panels::page>

<style>
.fm-wrap{display:flex;flex-direction:column;gap:12px;height:calc(100vh - 180px);}
.fm-toolbar{display:flex;flex-wrap:wrap;gap:6px;align-items:center;flex-shrink:0;}
.fm-body{display:flex;gap:12px;flex:1;min-height:0;}
.fm-tree{width:240px;flex-shrink:0;background:#1e1e2e;border-radius:8px;overflow-y:auto;padding:6px;}
.fm-tree-group{margin-bottom:6px;}
.fm-tree-head{color:#585b70;font-size:10px;font-weight:700;text-transform:uppercase;padding:4px 8px 2px;letter-spacing:.06em;}
.fm-tree-file{display:flex;justify-content:space-between;align-items:center;padding:4px 8px;border-radius:5px;cursor:pointer;color:#cdd6f4;transition:background .12s;}
.fm-tree-file:hover{background:#313244;}
.fm-tree-file.active{background:#45475a;color:#89b4fa;}
.fm-tree-file span{font-family:monospace;font-size:12px;direction:ltr;}
.fm-tree-file small{color:#585b70;font-size:10px;}
.fm-editor-wrap{flex:1;display:flex;flex-direction:column;border-radius:8px;overflow:hidden;border:1px solid #313244;min-width:0;background:#1e1e2e;}
.fm-tabbar{background:#181825;padding:6px 10px 0;display:flex;gap:4px;border-bottom:1px solid #313244;flex-shrink:0;align-items:center;}
.fm-tab{background:#1e1e2e;color:#6c7086;padding:4px 14px;border-radius:6px 6px 0 0;font-size:12px;font-family:monospace;border:1px solid #313244;border-bottom:none;}
.fm-tab.active{color:#89b4fa;border-color:#45475a;}
.fm-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#45475a;gap:10px;}
#fm-monaco{flex:1;width:100%;min-height:0;}
.fm-filepath{font-family:monospace;font-size:11px;color:#585b70;background:#181825;padding:2px 10px;border-radius:20px;max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;direction:ltr;}
.fm-dot{width:8px;height:8px;border-radius:50%;background:#fab387;display:inline-block;margin-right:4px;}
.fm-build{background:#1e1e2e;border-radius:8px;padding:10px 14px;flex-shrink:0;}
.fm-build pre{font-size:11px;color:#a6e3a1;overflow:auto;max-height:140px;font-family:monospace;white-space:pre-wrap;margin:0;}
.fm-git{background:#1e1e2e;border-radius:8px;padding:10px 14px;flex-shrink:0;}
.fm-git pre{font-size:11px;color:#f9e2af;font-family:monospace;white-space:pre-wrap;margin:0;}
.fm-sec-title{font-size:10px;font-weight:700;color:#585b70;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
.fm-btn{padding:6px 14px;border-radius:6px;font-size:13px;font-weight:600;border:none;cursor:pointer;color:#fff;transition:opacity .15s;}
.fm-btn:hover{opacity:.85;}
.fm-search{background:#181825;border:1px solid #313244;border-radius:6px;padding:4px 10px;font-size:12px;color:#cdd6f4;width:160px;}
.fm-search::placeholder{color:#45475a;}
</style>

<div class="fm-wrap" id="fm-root" dir="rtl">

    <div class="fm-toolbar">
        <button class="fm-btn" style="background:#40a02b" id="btn-save">✅ ذخیره</button>
        <button class="fm-btn" style="background:#fe640b" id="btn-save-build">🔧 ذخیره+Build</button>
        <button class="fm-btn" style="background:#1e66f5" id="btn-build">▶ Build</button>
        <button class="fm-btn" style="background:#4c4f69" id="btn-download">⬇ دانلود</button>
        <button class="fm-btn" style="background:#d20f39" id="btn-delete">🗑 حذف</button>
        <button class="fm-btn" style="background:#8839ef" id="btn-new">＋ جدید</button>
        <button class="fm-btn" style="background:#4c4f69" id="btn-commit">⎇ Commit</button>
        <input class="fm-search" type="text" id="fm-search" placeholder="🔍 جستجو..." oninput="fmSearch(this.value)" dir="ltr"/>
        <span id="fm-filepath" class="fm-filepath" style="display:none"></span>
        <span id="fm-unsaved" class="fm-dot" style="display:none" title="ذخیره نشده"></span>
    </div>

    <div class="fm-body">
        <div class="fm-tree" id="fm-tree">
            @foreach($basePaths as $label => $path)
            <div class="fm-tree-group">
                <div class="fm-tree-head">{{ $label }}</div>
                @foreach($this->getFiles($path) as $file)
                    @if(!$file['isDir'])
                    <div class="fm-tree-file"
                        data-path="{{ addslashes($file['path']) }}"
                        data-name="{{ $file['name'] }}"
                        onclick="fmClickFile(this)"
                        title="{{ $file['modified'] }}">
                        <span>{{ $file['name'] }}</span>
                        <small>{{ $file['size'] }}</small>
                    </div>
                    @endif
                @endforeach
            </div>
            @endforeach
        </div>

        <div class="fm-editor-wrap">
            <div class="fm-tabbar">
                <div id="fm-tab" class="fm-tab active" style="color:#585b70">بدون فایل</div>
            </div>
            <div id="fm-empty" class="fm-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:.3">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="10" y1="20" x2="14" y2="4"/><line x1="6" y1="16" x2="10" y2="16"/><line x1="14" y1="8" x2="18" y2="8"/>
                </svg>
                <p style="font-size:13px;color:#585b70;">یک فایل از سمت چپ انتخاب کنید</p>
                <p style="font-size:11px;color:#45475a;">Monaco Editor — مثل VSCode</p>
            </div>
            <div id="fm-monaco" style="display:none;flex:1;min-height:0;"></div>
        </div>
    </div>

    @if($buildOutput)
    <div class="fm-build">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <div class="fm-sec-title">📦 Build Output</div>
            <button wire:click="clearBuildOutput" style="font-size:11px;color:#6c7086;background:none;border:none;cursor:pointer;">✕ پاک</button>
        </div>
        <pre>{{ $buildOutput }}</pre>
    </div>
    @endif

    @if($gitStatus)
    <div class="fm-git">
        <div class="fm-sec-title">⎇ Git Status</div>
        <pre>{{ $gitStatus }}</pre>
    </div>
    @endif

</div>

{{-- Modals --}}
<x-filament::modal wire:model="showNewFileModal" heading="فایل جدید" width="md">
    <div class="flex flex-col gap-4" dir="rtl">
        <div>
            <label class="text-sm text-gray-400 mb-1 block">نام فایل</label>
            <input wire:model="newFileName" type="text" placeholder="MyPage.tsx"
                class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm font-mono text-white" dir="ltr"/>
        </div>
        <div>
            <label class="text-sm text-gray-400 mb-1 block">پوشه</label>
            <select wire:model="newFileFolder" class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm text-white">
                <option value="">انتخاب کنید...</option>
                @foreach($basePaths as $label => $path)
                    <option value="{{ $label }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <x-slot name="footerActions">
        <x-filament::button wire:click="createFile" color="primary">ایجاد فایل</x-filament::button>
        <x-filament::button wire:click="$set('showNewFileModal', false)" color="gray">لغو</x-filament::button>
    </x-slot>
</x-filament::modal>

<x-filament::modal wire:model="showCommitModal" heading="Git Commit" width="md">
    <div dir="rtl">
        @if($gitStatus)
        <div class="mb-3 p-3 bg-gray-900 rounded text-xs font-mono text-yellow-400 whitespace-pre-wrap">{{ $gitStatus }}</div>
        @endif
        <label class="text-sm text-gray-400 mb-1 block">پیام Commit</label>
        <input wire:model="commitMessage" type="text" placeholder="feat: update homepage"
            class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-sm text-white" dir="ltr"/>
    </div>
    <x-slot name="footerActions">
        <x-filament::button wire:click="gitCommit" color="success">Commit</x-filament::button>
        <x-filament::button wire:click="$set('showCommitModal', false)" color="gray">لغو</x-filament::button>
    </x-slot>
</x-filament::modal>

<script>
(function(){
'use strict';

var FM = { editor: null, ready: false, file: '', name: '', unsaved: false, activeEl: null };

var langMap = {
    js:'javascript', jsx:'javascript', ts:'typescript', tsx:'typescript',
    php:'php', css:'css', scss:'scss', html:'html', vue:'html',
    json:'json', md:'markdown', yaml:'yaml', yml:'yaml',
    sh:'shell', bash:'shell', xml:'xml', svg:'xml', sql:'sql', env:'ini'
};

// لود Monaco از jsdelivr
function loadMonaco() {
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.js';
    s.onload = function() {
        require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs' }});
        require(['vs/editor/editor.main'], function() {
            FM.editor = monaco.editor.create(document.getElementById('fm-monaco'), {
                value: '', language: 'typescript', theme: 'vs-dark',
                automaticLayout: true, fontSize: 14, lineNumbers: 'on',
                minimap: { enabled: true }, scrollBeyondLastLine: false,
                wordWrap: 'on', tabSize: 2, padding: { top: 12 },
                bracketPairColorization: { enabled: true },
                folding: true, smoothScrolling: true,
            });
            FM.ready = true;
            FM.editor.onDidChangeModelContent(function() {
                if (FM.file) {
                    FM.unsaved = true;
                    document.getElementById('fm-unsaved').style.display = 'inline-block';
                }
            });
            FM.editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, fmSave);
        });
    };
    document.head.appendChild(s);
}

// دریافت event از Livewire
window.addEventListener('file-opened', function(e) {
    var d = Array.isArray(e.detail) ? e.detail[0] : e.detail;
    fmLoadContent(d.content, d.ext, d.filename, d.path);
});

window.addEventListener('file-closed', function() {
    document.getElementById('fm-empty').style.display = 'flex';
    document.getElementById('fm-monaco').style.display = 'none';
    document.getElementById('fm-tab').textContent = 'بدون فایل';
    document.getElementById('fm-filepath').style.display = 'none';
    FM.file = ''; FM.name = ''; FM.unsaved = false;
    document.getElementById('fm-unsaved').style.display = 'none';
});

// دانلود فایل
window.addEventListener('download-file', function(e) {
    var d = Array.isArray(e.detail) ? e.detail[0] : e.detail;
    var blob = new Blob([atob(d.content)], { type: 'text/plain' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = d.filename;
    a.click();
});

function fmLoadContent(content, ext, filename, path) {
    document.getElementById('fm-empty').style.display = 'none';
    var mc = document.getElementById('fm-monaco');
    mc.style.display = 'block';
    mc.style.flex = '1';
    document.getElementById('fm-tab').textContent = filename;
    document.getElementById('fm-tab').style.color = '#89b4fa';
    var fp = document.getElementById('fm-filepath');
    fp.textContent = path || filename;
    fp.style.display = 'inline-block';
    FM.file = path || filename;
    FM.name = filename;
    FM.unsaved = false;
    document.getElementById('fm-unsaved').style.display = 'none';

    if (!FM.ready) { setTimeout(function() { fmLoadContent(content, ext, filename, path); }, 150); return; }

    var lang = langMap[ext] || 'plaintext';
    var old = FM.editor.getModel();
    FM.editor.setModel(monaco.editor.createModel(content, lang));
    if (old) old.dispose();
    FM.editor.setScrollPosition({ scrollTop: 0 });
    FM.editor.focus();
}

// کلیک روی فایل — از Livewire component پیدا می‌کنیم
window.fmClickFile = function(el) {
    if (FM.activeEl) FM.activeEl.classList.remove('active');
    el.classList.add('active');
    FM.activeEl = el;
    var path = el.getAttribute('data-path');
    var component = window.Livewire.getByName('filament.pages.file-manager-page')[0]
        || window.Livewire.all()[0];
    if (component) component.call('openFile', path);
};

function getLivewire() {
    if (window.Livewire && window.Livewire.all) {
        return window.Livewire.all().find(function(c) {
            return c.name && c.name.includes('file-manager');
        }) || window.Livewire.all()[0];
    }
    return null;
}

window.fmSave = function() {
    if (!FM.file || !FM.ready) return;
    var content = FM.editor.getValue();
    var lw = getLivewire();
    if (lw) {
        lw.set('fileContent', content);
        setTimeout(function() { lw.call('saveFile'); }, 80);
    }
    FM.unsaved = false;
    document.getElementById('fm-unsaved').style.display = 'none';
};

window.fmSaveAndBuild = function() {
    if (!FM.file || !FM.ready) return;
    var lw = getLivewire();
    if (lw) {
        lw.set('fileContent', FM.editor.getValue());
        setTimeout(function() { lw.call('saveAndBuild'); }, 80);
    }
    FM.unsaved = false;
    document.getElementById('fm-unsaved').style.display = 'none';
};

window.fmBuild = function() {
    var lw = getLivewire(); if (lw) lw.call('runBuild');
};

window.fmDownload = function() {
    var lw = getLivewire(); if (lw) lw.call('downloadFile');
};

window.fmDelete = function() {
    if (!FM.file) return;
    if (confirm('فایل "' + FM.name + '" حذف شود؟')) {
        var lw = getLivewire(); if (lw) lw.call('deleteFile');
    }
};

window.fmNewFile = function() {
    var lw = getLivewire(); if (lw) lw.set('showNewFileModal', true);
};

window.fmCommit = function() {
    var lw = getLivewire(); if (lw) lw.set('showCommitModal', true);
};

window.fmSearch = function(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.fm-tree-file').forEach(function(el) {
        var name = el.getAttribute('data-name').toLowerCase();
        el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
};

// bind دکمه‌ها
document.getElementById('btn-save').onclick = fmSave;
document.getElementById('btn-save-build').onclick = fmSaveAndBuild;
document.getElementById('btn-build').onclick = fmBuild;
document.getElementById('btn-download').onclick = fmDownload;
document.getElementById('btn-delete').onclick = fmDelete;
document.getElementById('btn-new').onclick = fmNewFile;
document.getElementById('btn-commit').onclick = fmCommit;

loadMonaco();
})();
</script>

</x-filament-panels::page>
