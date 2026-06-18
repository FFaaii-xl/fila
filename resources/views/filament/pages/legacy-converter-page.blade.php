<x-filament-panels::page>
    <style>
        .kinetic-wrapper {
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.05), transparent 600px);
            min-height: 80vh;
            padding: 2rem !important;
        }
        .glass-card {
            background: rgba(10, 12, 16, 0.6);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.6);
        }
        .kinetic-dropzone {
            border: 2px dashed rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
            border-radius: 1.25rem;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .kinetic-dropzone:hover {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.04);
        }
        .drop-icon-glow {
            filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.2));
            color: #10b981;
            opacity: 0.6;
        }
        
        /* PROGRESS METRIC STYLE */
        .progress-metric-container {
            display: flex;
            width: 100%;
            height: 48px;
            background: rgba(255,255,255,0.03);
            border-radius: 1rem;
            overflow: hidden;
            margin-top: 12px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .progress-metric-badge {
            width: 60px;
            height: 100%;
            background: rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.3);
        }
        .progress-metric-track {
            flex-grow: 1;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            padding: 0 1rem;
        }
        .progress-metric-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #10b981;
            opacity: 0.15;
            transition: width 0.3s ease;
        }
        .progress-metric-text {
            position: relative;
            z-index: 10;
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            font-weight: 800;
            color: #10b981;
        }
    </style>

    <div class='kinetic-wrapper' x-data='{ 
        tab: "converter",
        processing: false,
        filesList: [], 
        dragging: false,
        currentFileIndex: 0,

        // Merger State
        mergerFilesList: [],
        mergerDragging: false,
        mergerProcessing: false,
        mergerResult: null,

        handleFiles(files) {
             Array.from(files).forEach(file => {
                this.filesList.push({
                    file: file,
                    name: file.name,
                    progress: 0,
                    status: "waiting",
                    result: null,
                    errorMsg: ""
                });
             });
        },

        removeFile(index) {
            this.filesList.splice(index, 1);
        },

        async startTransmission() {
            this.processing = true;
            
            const concurrencyLimit = 3;
            let index = 0;

            const next = async () => {
                if (index >= this.filesList.length) return;
                
                const currentIndex = index++;
                const node = this.filesList[currentIndex];
                
                if (node.status === "waiting") {
                    this.currentFileIndex = currentIndex;
                    await this.uploadFile(node);
                }
                
                await next();
            };

            const workers = [];
            for (let i = 0; i < Math.min(concurrencyLimit, this.filesList.length); i++) {
                workers.push(next());
            }

            await Promise.all(workers);
            
            this.processing = false;
            location.reload();
        },

        uploadFile(node) {
            return new Promise((resolve) => {
                node.status = "uploading";
                
                const formData = new FormData();
                formData.append("file", node.file);
                formData.append("_token", "{{ csrf_token() }}");

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "{{ route('admin.legacy.convert') }}", true);

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        node.progress = Math.round((e.loaded / e.total) * 100);
                    }
                };

                xhr.onload = () => {
                    if (xhr.status === 200) {
                        const resp = JSON.parse(xhr.responseText);
                        node.status = "success";
                        node.result = resp;
                        node.progress = 100;
                    } else {
                        try {
                            const error = JSON.parse(xhr.responseText);
                            node.status = "error";
                            node.errorMsg = error.message || "Transmission Refused";
                        } catch(e) {
                            node.status = "error";
                            node.errorMsg = "Port Error";
                        }
                    }
                    resolve();
                };

                xhr.onerror = () => {
                    node.status = "error";
                    node.errorMsg = "Link Severed";
                    resolve();
                };

                xhr.send(formData);
            });
        },

        handleMergerFiles(files) {
             Array.from(files).forEach(file => {
                this.mergerFilesList.push({
                    file: file,
                    name: file.name,
                    size: (file.size / 1024).toFixed(1) + " KB"
                });
             });
        },

        removeMergerFile(index) {
            this.mergerFilesList.splice(index, 1);
        },

        async startMerge() {
            if (this.mergerFilesList.length < 2) {
                alert("Harap pilih minimal 2 file untuk digabungkan!");
                return;
            }
            
            this.mergerProcessing = true;
            this.mergerResult = null;
            
            const formData = new FormData();
            this.mergerFilesList.forEach(node => {
                formData.append("files[]", node.file);
            });
            formData.append("_token", "{{ csrf_token() }}");

            try {
                const response = await fetch("{{ route('admin.legacy.merge') }}", {
                    method: "POST",
                    body: formData
                });
                
                const resp = await response.json();
                if (resp.success) {
                    this.mergerResult = resp;
                } else {
                    alert(resp.message || "Gagal menggabungkan file");
                }
            } catch (e) {
                alert("Terjadi kesalahan saat menghubungi server");
            } finally {
                this.mergerProcessing = false;
            }
        }
    }'>
        <div class="flex flex-col max-w-4xl mx-auto">
            <div class="mb-12 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h2 class="text-4xl font-black text-white uppercase tracking-tighter" x-text="tab === 'converter' ? 'Converter Hub' : 'Report Merger'">Converter Hub</h2>
                    <p class="text-[10px] text-gray-600 font-mono uppercase tracking-[0.3em] font-bold mt-2" x-text="tab === 'converter' ? 'Tactical AJAX Transmission Port' : 'Merge Multiple Sheets into One Standard Template'"></p>
                </div>
                
                <div class="flex bg-white/5 p-1 rounded-xl border border-white/5">
                    <button type="button" @click="tab = 'converter'" :class="tab === 'converter' ? 'bg-emerald-500 text-white shadow-lg font-bold' : 'text-gray-400 hover:text-white'" class="px-4 py-2 font-black text-[10px] uppercase tracking-widest rounded-lg transition-all">
                        Converter
                    </button>
                    <button type="button" @click="tab = 'merger'" :class="tab === 'merger' ? 'bg-emerald-500 text-white shadow-lg font-bold' : 'text-gray-400 hover:text-white'" class="px-4 py-2 font-black text-[10px] uppercase tracking-widest rounded-lg transition-all">
                        Report Merger
                    </button>
                </div>
            </div>

            <!-- TAB 1: CONVERTER -->
            <div x-show="tab === 'converter'" class="glass-card">
                <div class="kinetic-dropzone" 
                        :class="dragging ? 'border-primary-500/50 bg-primary-500/5' : ''"
                        @click="$refs.fileInput.click()"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)">
                    
                    <x-heroicon-o-cloud-arrow-up class="w-8 h-8 drop-icon-glow" />
                    <span class="text-sm font-black text-white uppercase tracking-widest">Drag legacy files here</span>
                    <span class="text-[10px] text-gray-600 font-mono uppercase">Excel format only (.xlsx, .xls)</span>
                    
                    <input type="file" x-ref="fileInput" multiple class="hidden" 
                            @change="handleFiles($event.target.files)">
                </div>

                <div class="mt-8 space-y-4" x-show="filesList.length > 0" x-cloak>
                    <template x-for="(node, index) in filesList" :key="index">
                        <div class="p-5 bg-white/5 border border-white/5 rounded-2xl transition-all"
                             :class="node.status === 'error' ? 'border-rose-500/20 bg-rose-500/5' : (node.status === 'success' ? 'border-emerald-500/20 bg-emerald-500/5' : '')">
                            
                            <div class="flex items-center justify-between pointer-events-none mb-3">
                                <div class="flex items-center gap-3">
                                    <x-heroicon-o-document class="w-4 h-4 text-gray-500" />
                                    <span x-text="node.name" class="text-[10px] text-gray-400 font-mono uppercase tracking-widest"></span>
                                </div>
                                
                                <div class="flex items-center gap-3 pointer-events-auto">
                                    <span x-show="node.status === 'waiting'" class="text-[8px] font-mono text-gray-600 uppercase tracking-widest">Ready</span>
                                    <span x-show="node.status === 'uploading'" class="text-[8px] font-mono text-emerald-500 uppercase tracking-widest animate-pulse">Broadcasting...</span>
                                    <span x-show="node.status === 'success'" class="text-[8px] font-mono text-emerald-500 uppercase tracking-widest font-black">Success Port</span>
                                    <span x-show="node.status === 'error'" class="text-[8px] font-mono text-rose-500 uppercase tracking-widest" x-text="node.errorMsg"></span>

                                    <button type="button" x-show="node.status === 'waiting'" @click.stop="removeFile(index)" class="text-gray-600 hover:text-rose-500 transition-colors pointer-events-auto">
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="progress-metric-container" x-show="node.status !== 'waiting'">
                                <div class="progress-metric-badge">
                                    <x-heroicon-o-cloud-arrow-up class="w-3 h-3" />
                                    <span class="text-[9px] font-black mt-0.5" x-text="index + 1"></span>
                                </div>
                                <div class="progress-metric-track">
                                    <div class="progress-metric-fill" :style="'width: ' + node.progress + '%'"></div>
                                    <span class="progress-metric-text" x-text="node.progress + '%'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="startTransmission()" x-show="filesList.length > 0" x-cloak class="w-full mt-8 bg-emerald-500 hover:bg-emerald-400 text-white font-black text-[10px] uppercase tracking-[0.2em] py-4 rounded-xl shadow-[0_20px_40px_-10px_rgba(16,185,129,0.3)] transition-all active:scale-95 flex items-center justify-center gap-2">
                    <x-heroicon-o-paper-airplane class="w-4 h-4" />
                    Start Sequential Transmission
                </button>
            </div>

            <!-- TAB 2: MERGER -->
            <div x-show="tab === 'merger'" x-cloak class="glass-card">
                <div class="kinetic-dropzone" 
                        :class="mergerDragging ? 'border-primary-500/50 bg-primary-500/5' : ''"
                        @click="$refs.mergerFileInput.click()"
                        @dragover.prevent="mergerDragging = true"
                        @dragleave.prevent="mergerDragging = false"
                        @drop.prevent="mergerDragging = false; handleMergerFiles($event.dataTransfer.files)">
                    
                    <x-heroicon-o-document-duplicate class="w-8 h-8 drop-icon-glow" />
                    <span class="text-sm font-black text-white uppercase tracking-widest">Drag files to merge here</span>
                    <span class="text-[10px] text-gray-600 font-mono uppercase">Upload 2 or more reports to merge (.xlsx, .xls, .csv)</span>
                    
                    <input type="file" x-ref="mergerFileInput" multiple class="hidden" 
                            @change="handleMergerFiles($event.target.files)">
                </div>

                <div class="mt-8 space-y-4" x-show="mergerFilesList.length > 0">
                    <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest font-mono mb-2">Selected Files Queue</h4>
                    <template x-for="(node, index) in mergerFilesList" :key="index">
                        <div class="flex items-center justify-between p-4 bg-white/5 border border-white/5 rounded-xl transition-all">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-document class="w-4 h-4 text-gray-500" />
                                <div>
                                    <span x-text="node.name" class="text-[11px] font-bold text-gray-300 uppercase tracking-wider block"></span>
                                    <span x-text="node.size" class="text-[8px] text-gray-600 font-mono uppercase tracking-widest mt-0.5 block"></span>
                                </div>
                            </div>
                            
                            <button type="button" @click.stop="removeMergerFile(index)" class="text-gray-600 hover:text-rose-500 transition-colors pointer-events-auto">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" @click="startMerge()" x-show="mergerFilesList.length >= 2 && !mergerProcessing" class="w-full mt-8 bg-emerald-500 hover:bg-emerald-400 text-white font-black text-[10px] uppercase tracking-[0.2em] py-4 rounded-xl shadow-[0_20px_40px_-10px_rgba(16,185,129,0.3)] transition-all active:scale-95 flex items-center justify-center gap-2">
                    <x-heroicon-o-bolt class="w-4 h-4" />
                    Merge and Synthesize Reports
                </button>

                <div x-show="mergerProcessing" class="mt-8 py-8 text-center bg-white/5 border border-white/5 rounded-xl">
                    <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-[10px] text-emerald-500 font-black uppercase tracking-[0.3em] animate-pulse">Synthesizing Datasets...</p>
                </div>

                <div x-show="mergerResult !== null" class="mt-8 p-6 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-500">
                            <x-heroicon-o-check class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-white uppercase tracking-widest">Synthesis Complete</h4>
                            <p class="text-[9px] text-gray-500 font-mono uppercase tracking-wider mt-0.5">Your merged file is ready for download</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-black/30 border border-white/5 rounded-xl mb-6">
                        <div>
                            <span class="block text-[8px] font-mono text-gray-600 uppercase tracking-widest">Files Merged</span>
                            <span x-text="mergerResult?.files_merged" class="text-lg font-black text-emerald-500 mt-1 block"></span>
                        </div>
                        <div>
                            <span class="block text-[8px] font-mono text-gray-600 uppercase tracking-widest">Original Rows</span>
                            <span x-text="mergerResult?.rows_before" class="text-lg font-black text-white mt-1 block"></span>
                        </div>
                        <div>
                            <span class="block text-[8px] font-mono text-gray-600 uppercase tracking-widest">Merged Rows</span>
                            <span x-text="mergerResult?.rows_after" class="text-lg font-black text-white mt-1 block"></span>
                        </div>
                        <div>
                            <span class="block text-[8px] font-mono text-gray-600 uppercase tracking-widest">Non-Catalog Warning</span>
                            <span :class="mergerResult?.missing > 0 ? 'text-rose-500' : 'text-emerald-500'" x-text="mergerResult?.missing > 0 ? mergerResult.missing + '!' : 'NONE'" class="text-lg font-black mt-1 block"></span>
                        </div>
                    </div>

                    <a :href="mergerResult?.download_url" download class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-black text-[10px] uppercase tracking-[0.2em] py-4 rounded-xl shadow-[0_20px_40px_-10px_rgba(16,185,129,0.3)] transition-all active:scale-95 flex items-center justify-center gap-2">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        Download Merged Report
                    </a>
                </div>
            </div>

            <!-- TRANSMISSION HISTORY LOG -->
            <div class='mt-16 space-y-8'>
                <div class='flex items-center gap-4'>
                    <div class='h-px flex-grow bg-white/5'></div>
                    <h3 class='text-[10px] font-black text-gray-700 uppercase tracking-[0.5em] mono-font'>Historical Manifest Archive</h3>
                    <div class='h-px flex-grow bg-white/5'></div>
                </div>

                <div class='space-y-12'>
                    @foreach($history as $group)
                        <div class='space-y-4'>
                            <div class='flex items-center gap-3 px-4'>
                                <div class='w-2 h-2 rounded-full bg-gray-800'></div>
                                <span class='text-[11px] font-black text-gray-500 uppercase tracking-widest outfit-font'>{{ date('l, d M Y', strtotime($group['date'])) }}</span>
                            </div>
                            <div class='grid gap-2'>
                                @foreach($group['files'] as $f)
                                    <div class='group flex items-center justify-between p-4 bg-white/[0.02] hover:bg-white/[0.05] border border-white/5 rounded-2xl transition-all'>
                                        <div class='flex items-center gap-4'>
                                            <div class='w-10 h-10 flex items-center justify-center bg-white/5 rounded-xl text-gray-600 group-hover:text-emerald-500 transition-colors'>
                                                <x-heroicon-o-document class="w-5 h-5" />
                                            </div>
                                            <div>
                                                <div class='text-[12px] font-bold text-gray-400 group-hover:text-white transition-colors uppercase'>{{ $f['name'] }}</div>
                                                <div class='text-[8px] font-mono text-gray-600 uppercase tracking-widest mt-0.5'>SYNC_TIME: {{ $f['time'] }} | SIZE: {{ $f['size'] }}</div>
                                            </div>
                                        </div>
                                        <a href='{{ $f['url'] }}' download class='px-5 py-2 bg-white/5 hover:bg-emerald-500/10 border border-white/10 hover:border-emerald-500/30 rounded-xl text-[9px] font-black text-gray-500 hover:text-emerald-500 uppercase tracking-widest transition-all'>
                                            Download Port
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(empty($history))
                    <div class='py-20 text-center'>
                        <p class='text-[10px] text-gray-800 font-black uppercase tracking-[0.3em]'>No Historical Records Found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
