@include('admin.reports.report-style')
<x-hhr-toolbar x-data="{ search: '{{ request('search', '') }}' }" class="mb-4 no-print shadow-sm">
    <x-slot:filters>
        <div class="hhr-group">
            <input type="date" name="date" id="date" value="{{ $date }}" 
                class="form-input" 
                onchange="this.form.submit()">
        </div>
    </x-slot:filters>

    <x-slot:search>
        <div class="relative w-full">
            <input type="text" name="search" id="search" value="{{ $search ?? '' }}" 
                x-model="search"
                list="nota-suggestions"
                placeholder="Cari pedagang / produk / produsen..."
                class="form-input w-full pl-8 pr-8" style="padding-left: 1.8rem !important; padding-right: 2rem !important;"
                autocomplete="off"
                oninput="debounceSearch()">
            
            <div class="absolute left-2.5 top-1/2 -translate-y-1/2 opacity-30">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            </div>

            <button type="button" x-show="search.length > 0" x-cloak @click="search = ''; clearSearch()" 
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-red-500 hover:text-red-400 transition-colors"
                title="Reset Pencarian">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                </svg>
            </button>

            <datalist id="nota-suggestions">
                @if(isset($suggestions))
                    @foreach($suggestions as $suggest)
                        <option value="{{ $suggest }}">
                    @endforeach
                @endif
            </datalist>
        </div>
    </x-slot:search>

    <x-slot:actions>
        @isset($roleLabel)
            <span class="hidden md:inline-flex items-center px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-[9px] font-black uppercase tracking-[0.2em] text-white/50 mr-1">
                {{ $roleLabel }}
            </span>
        @endisset
        @if(auth()->user()->owner_type === 'Admin')
        <label class="flex flex-col justify-center px-2 py-1 bg-[#7c3aed]/20 border border-[#7c3aed]/30 hover:bg-[#7c3aed]/40 rounded-lg cursor-pointer transition-colors whitespace-nowrap mr-1" title="Ubah warna nota secara otomatis setiap harinya">
            <div class="flex items-center gap-1.5">
                <input type="hidden" name="randomize_color" value="0">
                <input type="checkbox" name="randomize_color" id="randomize_color" value="1" 
                       {{ request('randomize_color', '1') == '1' ? 'checked' : '' }}
                       onchange="this.form.submit()"
                       class="form-checkbox h-3.5 w-3.5 text-[#7c3aed] bg-transparent border-white/50 rounded focus:ring-0 focus:ring-offset-0 cursor-pointer">
                <span class="text-[10px] font-bold text-[#b794f6] uppercase tracking-wider">Warna Acak</span>
            </div>
        </label>

        <button type="button" onclick="openBackupsModal()" class="hhr-btn opacity-60 hover:opacity-100 hover:bg-white/10" title="Riwayat Backup Nota">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
        </button>

        <button type="button" onclick="window.open('/admin/print-nota?date=' + document.getElementById('date').value + '&search=' + encodeURIComponent(document.getElementById('search').value || '') + '&randomize_color=' + (document.getElementById('randomize_color').checked ? '1' : '0'), '_blank')" 
            class="hhr-btn opacity-60 hover:opacity-100 hover:bg-white/10" title="Cetak Batch Nota">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/></svg>
        </button>
        @endif
    </x-slot:actions>
</x-hhr-toolbar>

{{-- BACKUP MODAL — Pure vanilla JS, rendered OUTSIDE toolbar form --}}
@if(auth()->user()->owner_type === 'Admin')
<div id="backups-modal-overlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#111827; border:1px solid #374151; border-radius:0.75rem; box-shadow:0 25px 50px rgba(0,0,0,0.5); max-width:28rem; width:100%; overflow:hidden;">
        <div style="padding:0.9rem 1rem; border-bottom:1px solid #1f2937; display:flex; justify-content:space-between; align-items:center; background:rgba(31,41,55,0.5);">
            <h3 style="font-size:0.8rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.12em;">Riwayat Backup Nota</h3>
            <button onclick="closeBackupsModal()" type="button" style="color:#9ca3af; cursor:pointer; background:none; border:none; padding:4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding:0.9rem; max-height:60vh; overflow-y:auto;">
            @if(empty($backups))
                <div style="text-align:center; padding:1.5rem 0; color:#6b7280; font-size:0.875rem;">
                    Belum ada backup nota untuk tanggal ini.
                </div>
            @else
                @foreach($backups as $backup)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:0.7rem 0.75rem; background:rgba(31,41,55,0.4); border-radius:0.5rem; border:1px solid rgba(55,65,81,0.5); margin-bottom:0.45rem;">
                    <div style="display:flex; flex-direction:column;">
                        <span style="color:#fff; font-size:0.875rem; font-weight:500;">{{ $backup->time }}</span>
                        <span style="color:#9ca3af; font-size:0.75rem;">{{ $backup->size }}</span>
                    </div>
                    <a href="/admin/nota/backup/download?file={{ urlencode($backup->path) }}" style="padding:0.375rem 0.75rem; background:rgba(99,102,241,0.2); color:#a5b4fc; font-size:0.75rem; font-weight:700; border-radius:0.25rem; text-decoration:none; text-transform:uppercase; letter-spacing:0.05em;">
                        Download
                    </a>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endif

<script>
    // === BACKUP MODAL (Vanilla JS) ===
    function openBackupsModal() {
        var m = document.getElementById('backups-modal-overlay');
        if (m) m.style.display = 'flex';
    }
    function closeBackupsModal() {
        var m = document.getElementById('backups-modal-overlay');
        if (m) m.style.display = 'none';
    }
    document.addEventListener('click', function(e) {
        var overlay = document.getElementById('backups-modal-overlay');
        if (overlay && e.target === overlay) closeBackupsModal();
    });

    // === SEARCH DEBOUNCE ===
    let debounceTimer;
    function debounceSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const searchInput = document.getElementById('search');
            sessionStorage.setItem('last_focus', 'search');
            sessionStorage.setItem('cursor_pos', searchInput.selectionStart);
            updateContent();
        }, 800);
    }

    function clearSearch() {
        const searchInput = document.getElementById('search');
        searchInput.value = '';
        sessionStorage.setItem('last_focus', 'search');
        updateContent();
    }

    function updateContent() {
        const searchInput = document.getElementById('search');
        const form = searchInput.form;
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        const url = `${form.action}?${params.toString()}`;

        const container = document.getElementById('nota-ajax-container');
        if(container) container.style.opacity = '0.4';

        window.history.pushState(null, '', url);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('nota-ajax-container');
                
                if (newContent && container) {
                    container.innerHTML = newContent.innerHTML;
                    container.style.opacity = '1';

                    if (window.Alpine) {
                        window.Alpine.store('toast')?.add('success', 'Data diperbarui');
                    }
                    
                    if (sessionStorage.getItem('last_focus') === 'search') {
                        const newSearch = document.getElementById('search');
                        newSearch.focus();
                        const pos = sessionStorage.getItem('cursor_pos');
                        if (pos) newSearch.setSelectionRange(pos, pos);
                    }
                } else {
                    window.location.reload();
                }
            })
            .catch(() => window.location.reload());
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        if (sessionStorage.getItem('last_focus') === 'search') {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                const pos = sessionStorage.getItem('cursor_pos');
                if (pos) {
                    searchInput.setSelectionRange(pos, pos);
                }
            }
            sessionStorage.removeItem('last_focus');
            sessionStorage.removeItem('cursor_pos');
        }
    });
</script>
