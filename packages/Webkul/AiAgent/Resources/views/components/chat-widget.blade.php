{{-- Agenting PIM — Non-blocking side panel (Copilot style) --}}

@php
    $magicAiEnabled  = (bool) core()->getConfigData('general.magic_ai.settings.enabled');
    $magicAiPlatform = core()->getConfigData('general.magic_ai.settings.ai_platform') ?? 'openai';
    $magicAiModels   = core()->getConfigData('general.magic_ai.settings.api_model') ?? '';
    $magicAiModel    = trim(explode(',', $magicAiModels)[0]) ?: ucfirst($magicAiPlatform);
@endphp

<v-agenting-pim></v-agenting-pim>

@pushOnce('scripts')
<script type="text/x-template" id="v-agenting-pim-template">
    <div>
        {{-- ── Side Panel ──────────────────────────────────── --}}
        <transition :name="noTransition ? '' : 'ap-slide'">
            <div
                v-if="isOpen"
                style="position:fixed;top:0;right:0;height:100vh;display:flex;flex-direction:column;background:#fff;border-left:1px solid #e5e7eb;width:420px;max-width:100vw;z-index:10000;"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-2.5 flex-shrink-0" style="background:linear-gradient(135deg,#6d28d9 0%,#7c3aed 50%,#8b5cf6 100%);">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                            <svg width="14" height="14" style="color:#fff;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        </div>
                        <div>
                            <p style="color:#fff;font-weight:600;font-size:13px;line-height:1.25;margin:0;">Agenting PIM</p>
                            <p style="color:rgba(255,255,255,0.55);font-size:10px;line-height:1.25;margin:0;">AI-powered operations</p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:4px;">
                        {{-- Model badge from Magic AI config --}}
                        <span style="font-size:10px;background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.25);border-radius:6px;padding:3px 8px;white-space:nowrap;max-width:130px;overflow:hidden;text-overflow:ellipsis;" title="{{ $magicAiModel }}">{{ $magicAiModel }}</span>
                        <a href="{{ route('ai-agent.settings') }}" title="AI Settings" style="color:rgba(255,255,255,0.65);display:flex;align-items:center;padding:5px;border-radius:6px;text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        </a>
                        <button v-if="activeTab === 'chat' && messages.length > 0" @click="newSession" title="New conversation" style="color:rgba(255,255,255,0.65);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;padding:5px;border-radius:6px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <button @click="close" title="Close" style="color:rgba(255,255,255,0.65);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;padding:5px;border-radius:6px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Product Context Banner --}}
                <div v-if="productContext" style="display:flex;align-items:center;gap:8px;padding:6px 16px;background:#f5f3ff;border-bottom:1px solid #e9d5ff;flex-shrink:0;">
                    <svg width="13" height="13" style="flex-shrink:0;color:#7c3aed;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span style="font-size:11px;color:#5b21b6;font-weight:500;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" v-text="'Editing: ' + (productContext.sku || 'Product #' + productContext.id)"></span>
                    <button @click="productContext = null" style="color:#8b5cf6;background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Tab Bar --}}
                <div style="display:flex;border-bottom:1px solid #e5e7eb;background:#f9fafb;flex-shrink:0;">
                    <button @click="activeTab = 'capabilities'"
                        :style="activeTab === 'capabilities'
                            ? 'border-bottom:2px solid #7c3aed;color:#7c3aed;font-weight:600;background:#fff;'
                            : 'color:#6b7280;background:transparent;'"
                        style="flex:1;padding:8px 16px;font-size:11px;cursor:pointer;border:none;display:flex;align-items:center;justify-content:center;gap:5px;transition:all 0.15s;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Capabilities
                    </button>
                    <button @click="activeTab = 'chat'"
                        :style="activeTab === 'chat'
                            ? 'border-bottom:2px solid #7c3aed;color:#7c3aed;font-weight:600;background:#fff;'
                            : 'color:#6b7280;background:transparent;'"
                        style="flex:1;padding:8px 16px;font-size:11px;cursor:pointer;border:none;display:flex;align-items:center;justify-content:center;gap:5px;transition:all 0.15s;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Chat
                        <span v-if="messages.filter(m => m.role === 'assistant').length > 0"
                            style="min-width:18px;height:18px;background:#ede9fe;color:#7c3aed;border-radius:9999px;font-size:9px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;padding:0 4px;"
                            v-text="messages.filter(m => m.role === 'assistant').length"></span>
                    </button>
                </div>

                {{-- Capabilities Tab --}}
                <div v-show="activeTab === 'capabilities'" style="flex:1;overflow-y:auto;padding:16px;">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-3 font-medium">Select an operation to begin:</p>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button v-for="cap in capabilities" :key="cap.key" @click="activateCapability(cap)"
                            class="flex flex-col items-start gap-2 p-3 rounded-lg border border-gray-200 dark:border-cherry-700 hover:border-violet-300 dark:hover:border-violet-600 hover:bg-violet-50 dark:hover:bg-cherry-800 transition-all text-left group">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="{ background: cap.color + '15' }">
                                <span v-html="cap.iconSvg" :style="{ color: cap.color }"></span>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 group-hover:text-violet-700 dark:group-hover:text-violet-400 leading-tight" v-text="cap.label"></p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug" v-text="cap.description"></p>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Chat Tab --}}
                <div v-show="activeTab === 'chat'" style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">

                    {{-- Chat sub-header: capability badge + clear --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span v-if="activeCapability"
                                style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:9999px;"
                                :style="{ background: activeCapability.color + '18', color: activeCapability.color }"
                                v-text="activeCapability.label"></span>
                            <span v-else style="font-size:10px;color:#9ca3af;font-weight:500;">General Chat</span>
                            <span v-if="messages.length > 0" style="font-size:10px;color:#9ca3af;">· <span v-text="messages.filter(m => m.role === 'user').length"></span> message(s)</span>
                        </div>
                        <button v-if="messages.length > 0" @click="clearChat"
                            style="display:flex;align-items:center;gap:4px;font-size:10px;color:#ef4444;padding:3px 8px;border-radius:6px;border:1px solid #fecaca;background:#fff5f5;cursor:pointer;transition:all 0.15s;"
                            title="Clear this chat">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            Clear chat
                        </button>
                    </div>

                    <div ref="messagesEl" style="flex:1;overflow-y:auto;padding:12px 16px;display:flex;flex-direction:column;gap:16px;min-height:0;">
                        {{-- Empty state --}}
                        <div v-if="messages.length === 0 && !isLoading" class="flex flex-col items-center justify-center h-full text-center py-8">
                            <svg class="w-10 h-10 text-violet-200 dark:text-violet-800 mb-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                <template v-if="activeCapability">Ready for <strong class="text-violet-600 dark:text-violet-400" v-text="activeCapability.label"></strong></template>
                                <template v-else>How can I help with your catalog?</template>
                            </p>
                            <p v-if="activeCapability" class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5 max-w-[260px] leading-relaxed" v-text="activeCapability.hint"></p>
                            <p v-if="productContext" class="text-[11px] text-violet-500 dark:text-violet-400 mt-2">Context: <strong v-text="productContext.sku || 'Product #' + productContext.id"></strong></p>
                        </div>

                        <template v-for="(msg, idx) in messages" :key="idx">
                            {{-- User --}}
                            <div v-if="msg.role === 'user'" class="flex justify-end gap-2 items-end">
                                <div class="max-w-[85%] space-y-1.5">
                                    <div v-if="msg.files && msg.files.length" class="flex flex-wrap gap-1.5 justify-end">
                                        <template v-for="(f, fi) in msg.files" :key="fi">
                                            <img v-if="f.type === 'image'" :src="f.preview" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-cherry-700"/>
                                            <div v-else class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 text-violet-600 dark:text-violet-400">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                <span class="max-w-[100px] truncate font-medium" v-text="f.name"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div v-if="msg.content" style="background:#7c3aed;color:#fff;font-size:13px;padding:10px 14px;border-radius:14px 14px 4px 14px;white-space:pre-wrap;line-height:1.55;max-width:100%;word-break:break-word;" v-text="msg.content"></div>
                                </div>
                            </div>

                            {{-- Assistant --}}
                            <div v-else class="flex gap-2 items-start">
                                <div class="w-6 h-6 rounded-md flex-shrink-0 flex items-center justify-center mt-0.5" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
                                    <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0 space-y-2">
                                    <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" v-html="renderMarkdown(msg.content)"></div>
                                    <div v-if="msg.result && Object.keys(msg.result).length" class="rounded-lg border border-gray-200 dark:border-cherry-700 overflow-hidden">
                                        <div class="px-3 py-1.5 bg-gray-50 dark:bg-cherry-800 border-b border-gray-200 dark:border-cherry-700">
                                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Result</p>
                                        </div>
                                        <div class="px-3 py-2 space-y-1">
                                            <div v-for="(val, key) in msg.result" :key="key" class="flex gap-2 text-xs">
                                                <span class="text-gray-400 flex-shrink-0 capitalize" v-text="String(key).replace(/_/g, ' ') + ':'"></span>
                                                <span class="text-gray-700 dark:text-gray-300 font-medium" v-text="val"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 flex-wrap">
                                        <a v-if="msg.product_url" :href="msg.product_url" class="inline-flex items-center gap-1 text-xs font-medium text-violet-600 dark:text-violet-400 px-2.5 py-1 bg-violet-50 dark:bg-violet-900/20 rounded-md border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            View Product
                                        </a>
                                        <a v-if="msg.download_url" :href="msg.download_url" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-md border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Typing --}}
                        <div v-if="isLoading" class="flex gap-2 items-start">
                            <div class="w-6 h-6 rounded-md flex-shrink-0 flex items-center justify-center" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
                                <svg class="w-3 h-3 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            </div>
                            <div class="bg-gray-100 dark:bg-cherry-800 px-3.5 py-2.5 rounded-xl rounded-bl-sm">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pending files --}}
                    <div v-if="pendingFiles.length > 0" style="display:flex;flex-wrap:wrap;gap:8px;padding:8px 16px;border-top:1px solid #e5e7eb;background:#f9fafb;flex-shrink:0;">
                        <div v-for="(f, idx) in pendingFiles" :key="idx" class="relative group">
                            <img v-if="f.type === 'image'" :src="f.preview" class="w-10 h-10 object-cover rounded-md border border-gray-200 dark:border-cherry-700"/>
                            <div v-else class="flex items-center gap-1 px-2 py-1.5 rounded-md border text-xs bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800 text-violet-600 dark:text-violet-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span class="max-w-[80px] truncate font-medium" v-text="f.name"></span>
                            </div>
                            <button @click="removeFile(idx)" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] hidden group-hover:flex items-center justify-center shadow-sm">&times;</button>
                        </div>
                    </div>

                    {{-- Input — always-visible bordered box --}}
                    <div style="border-top:1px solid #e5e7eb;padding:12px;flex-shrink:0;background:#fff;position:relative;z-index:1;">

                        {{-- Outer bordered container --}}
                        <div style="border:1.5px solid #d1d5db;border-radius:12px;background:#f9fafb;overflow:hidden;">

                            {{-- Textarea --}}
                            <textarea
                                ref="textInput"
                                v-model="inputText"
                                @keydown.enter.exact.prevent="send"
                                rows="3"
                                style="width:100%;resize:none;font-size:13px;color:#374151;background:transparent;padding:12px 14px 6px;border:none;outline:none;min-height:76px;max-height:160px;line-height:1.55;display:block;box-sizing:border-box;"
                                :placeholder="inputPlaceholder"
                                :disabled="isLoading"
                                @input="autoResize"
                                @focus="$event.target.parentElement.style.border='1.5px solid #7c3aed'"
                                @blur="$event.target.parentElement.style.border='1.5px solid #d1d5db'"
                            ></textarea>

                            {{-- Toolbar row --}}
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-top:1px solid #f3f4f6;">

                                {{-- Left: attach --}}
                                <label
                                    :title="fileInputTitle"
                                    style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#6b7280;padding:4px 10px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;transition:background 0.15s;"
                                    @mouseenter="$event.currentTarget.style.background='#f5f0ff';$event.currentTarget.style.color='#7c3aed';$event.currentTarget.style.borderColor='#c4b5fd';"
                                    @mouseleave="$event.currentTarget.style.background='#fff';$event.currentTarget.style.color='#6b7280';$event.currentTarget.style.borderColor='#e5e7eb';"
                                >
                                    <svg v-if="activeCapability && activeCapability.acceptsSpreadsheet" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                    <svg v-else width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                    Attach
                                    <input type="file" ref="fileInput" class="hidden" :accept="fileAccept" multiple @change="onFileSelect"/>
                                </label>

                                {{-- Right: Send button --}}
                                <button
                                    @click="send"
                                    :disabled="isLoading || (!inputText.trim() && pendingFiles.length === 0)"
                                    style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#fff;padding:6px 18px;border-radius:8px;border:none;cursor:pointer;background:linear-gradient(135deg,#7c3aed,#8b5cf6);transition:opacity 0.2s,transform 0.1s;"
                                    :style="{ opacity: (isLoading || (!inputText.trim() && pendingFiles.length === 0)) ? '0.35' : '1', cursor: (isLoading || (!inputText.trim() && pendingFiles.length === 0)) ? 'not-allowed' : 'pointer' }"
                                >
                                    <template v-if="!isLoading">
                                        <svg width="13" height="13" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="white" stroke="none"/></svg>
                                        Send
                                    </template>
                                    <template v-else>
                                        <svg class="animate-spin" width="13" height="13" fill="none" viewBox="0 0 24 24"><circle style="opacity:.3" cx="12" cy="12" r="10" stroke="white" stroke-width="4"/><path style="opacity:.8" fill="white" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                        Sending…
                                    </template>
                                </button>
                            </div>
                        </div>

                        <p style="font-size:10px;color:#9ca3af;text-align:center;margin-top:6px;">Enter to send &middot; Shift+Enter for new line</p>
                    </div>
                </div>
            </div>
        </transition>

        {{-- ── Trigger Button ──────────────────────────────── --}}
        <button
            v-show="!isOpen"
            @click="toggle"
            title="Open Agenting PIM"
            style="position:fixed;bottom:24px;right:24px;z-index:10002;width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(124,58,237,0.4);border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;"
            @mouseenter="$event.currentTarget.style.transform='scale(1.1)';$event.currentTarget.style.boxShadow='0 6px 20px rgba(124,58,237,0.5)'"
            @mouseleave="$event.currentTarget.style.transform='';$event.currentTarget.style.boxShadow='0 4px 15px rgba(124,58,237,0.4)'"
        >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
        </button>
    </div>
</script>

<style>
.ap-slide-enter-active, .ap-slide-leave-active { transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.ap-slide-enter-from, .ap-slide-leave-to { transform: translateX(100%); }
</style>

<script type="module">
app.component('v-agenting-pim', {
    template: '#v-agenting-pim-template',

    data() {
        const svg = (d, opts = {}) => {
            const w = opts.w || 16, h = opts.h || 16;
            return `<svg width="${w}" height="${h}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
        };
        return {
            isOpen: false,
            activeTab: 'capabilities',
            activeCapability: null,
            messages: [],
            inputText: '',
            pendingFiles: [],
            isLoading: false,
            productContext: null,
            noTransition: false,
            capabilities: [
                { key: 'create_from_image', label: 'Create from Image', description: 'Upload photos to auto-create products',
                  iconSvg: svg('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'),
                  color: '#7C3AED', hint: 'Upload a product image. AI will detect and create the product.', acceptsImages: true, acceptsSpreadsheet: false },
                { key: 'update_products', label: 'Update Products', description: 'Update attributes/status by SKU',
                  iconSvg: svg('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'),
                  color: '#059669', hint: 'e.g. "Set status=active for SKU-001, SKU-002"', acceptsImages: false, acceptsSpreadsheet: true },
                { key: 'upload_csv', label: 'Bulk Import CSV', description: 'Upload CSV/XLSX to batch update',
                  iconSvg: svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>'),
                  color: '#D97706', hint: 'Upload a CSV/XLSX with SKU column to batch update products.', acceptsImages: false, acceptsSpreadsheet: true },
                { key: 'delete_products', label: 'Delete Products', description: 'Remove products by SKU list',
                  iconSvg: svg('<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>'),
                  color: '#DC2626', hint: 'e.g. "Delete SKU-001, SKU-002, SKU-003"', acceptsImages: false, acceptsSpreadsheet: false },
                { key: 'export_products', label: 'Export Products', description: 'Generate CSV/XLSX export',
                  iconSvg: svg('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'),
                  color: '#0891B2', hint: 'e.g. "Export all active Electronics products to CSV"', acceptsImages: false, acceptsSpreadsheet: false },
                { key: 'assign_categories', label: 'Assign Categories', description: 'Assign category paths to products',
                  iconSvg: svg('<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>'),
                  color: '#6366F1', hint: 'e.g. "Assign Electronics > Laptops to SKU-001"', acceptsImages: false, acceptsSpreadsheet: false },
                { key: 'generate_variants', label: 'Generate Variants', description: 'Auto-generate size/color variants',
                  iconSvg: svg('<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'),
                  color: '#9333EA', hint: 'e.g. "Generate S/M/L × Red/Blue for SHIRT-001"', acceptsImages: false, acceptsSpreadsheet: false },
                { key: 'edit_image', label: 'Edit Product Image', description: 'Remove/change background',
                  iconSvg: svg('<circle cx="12" cy="12" r="3"/><path d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12z" stroke-dasharray="4 2"/>'),
                  color: '#EC4899', hint: 'Upload an image, then say "Remove background"', acceptsImages: true, acceptsSpreadsheet: false },
            ],
        };
    },

    computed: {
        fileAccept() {
            if (this.activeCapability?.acceptsSpreadsheet) return '.csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel';
            return 'image/jpeg,image/png,image/webp,image/gif';
        },
        fileInputTitle() { return this.activeCapability?.acceptsSpreadsheet ? 'Attach CSV or XLSX' : 'Attach image'; },
        inputPlaceholder() {
            if (this.isLoading) return 'Processing…';
            if (this.activeCapability) return this.activeCapability.hint;
            return 'Ask me anything about your catalog…';
        },
    },

    mounted() {
        this.detectProductContext();
        this.restoreState();
    },

    watch: {
        isOpen(val) { this.adjustLayout(val); this.saveState(); },
        messages: { deep: true, handler() { this.saveState(); } },
        activeTab() { this.saveState(); },
        activeCapability: { deep: true, handler() { this.saveState(); } },
    },

    methods: {
        detectProductContext() {
            const match = window.location.pathname.match(/\/catalog\/products\/edit\/(\d+)/);
            if (match) {
                this.productContext = { id: parseInt(match[1], 10), sku: null, name: null };
                this.$nextTick(() => {
                    const skuInput = document.querySelector('input[name="sku"]');
                    if (skuInput && skuInput.value) this.productContext.sku = skuInput.value;
                    const heading = document.querySelector('h1.text-xl') || document.querySelector('[class*="text-xl"]');
                    if (heading && heading.textContent) this.productContext.name = heading.textContent.trim().substring(0, 80);
                });
            }
        },
        adjustLayout(open, instant = false) {
            const appEl = document.getElementById('app');
            if (!appEl) return;
            if (open) {
                if (!instant) appEl.style.transition = 'margin-right 0.25s ease';
                else appEl.style.transition = 'none';
                appEl.style.marginRight = '420px';
                document.body.style.overflowX = 'hidden';
                if (instant) {
                    // restore transition after two paint frames
                    requestAnimationFrame(() => requestAnimationFrame(() => { appEl.style.transition = ''; }));
                }
            } else {
                appEl.style.transition = instant ? 'none' : 'margin-right 0.25s ease';
                appEl.style.marginRight = '';
                document.body.style.overflowX = '';
            }
        },
        toggle() { this.isOpen = !this.isOpen; if (this.isOpen) this.$nextTick(() => this.$refs.textInput?.focus()); },
        close() { this.isOpen = false; },
        newSession() { this.messages = []; this.inputText = ''; this.pendingFiles = []; this.activeCapability = null; this.activeTab = 'capabilities'; this.saveState(); },
        clearChat() { this.messages = []; this.inputText = ''; this.pendingFiles = []; this.saveState(); this.$nextTick(() => this.$refs.textInput?.focus()); },
        activateCapability(cap) { this.activeCapability = cap; this.activeTab = 'chat'; this.$nextTick(() => this.$refs.textInput?.focus()); },

        onFileSelect(e) {
            const spreadsheetExts = /\.(csv|xlsx|xls)$/i;
            const spreadsheetMimes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
            Array.from(e.target.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = ev => { this.pendingFiles.push({ file, type: 'image', preview: ev.target.result, name: file.name }); };
                    reader.readAsDataURL(file);
                } else if (spreadsheetMimes.includes(file.type) || spreadsheetExts.test(file.name)) {
                    this.pendingFiles.push({ file, type: 'spreadsheet', preview: null, name: file.name });
                }
            });
            e.target.value = '';
        },
        removeFile(idx) { this.pendingFiles.splice(idx, 1); },

        async send() {
            const text = this.inputText.trim();
            const files = [...this.pendingFiles];
            if (!text && files.length === 0) return;

            // Build user message for display
            const userMsg = { role: 'user', content: text || (files.length ? '📎 ' + files.map(f => f.name).join(', ') : ''), files: files.map(f => ({ type: f.type, preview: f.preview, name: f.name })) };
            this.messages.push(userMsg);
            this.inputText = ''; this.resetTextarea(); this.scrollBottom(); this.isLoading = true;

            try {
                const fd = new FormData();
                // Always send message field (even empty string) to avoid validation issues
                fd.append('message', text || (files.length > 0 ? 'Process the attached file(s): ' + files.map(f => f.name).join(', ') : ''));
                if (this.activeCapability) fd.append('action_type', this.activeCapability.key);
                files.forEach((f, i) => { if (f.type === 'image') fd.append('images[' + i + ']', f.file); else fd.append('files[' + i + ']', f.file); });
                fd.append('history', JSON.stringify(this.messages.slice(0, -1).map(m => ({ role: m.role, content: m.content || '' }))));
                fd.append('context[current_page]', window.location.pathname);
                if (this.productContext) {
                    fd.append('context[product_id]', this.productContext.id);
                    if (this.productContext.sku) fd.append('context[product_sku]', this.productContext.sku);
                    if (this.productContext.name) fd.append('context[product_name]', this.productContext.name);
                }
                const res = await this.$axios.post("{{ route('ai-agent.chat.send') }}", fd, { headers: { 'Content-Type': 'multipart/form-data' } });
                const data = res.data;
                // Clear pending files only on success
                this.pendingFiles = [];
                this.messages.push({ role: 'assistant', content: data.reply || 'No response received.', action: data.action || null, result: data.result || null, product_url: data.product_url || null, download_url: data.download_url || null });
            } catch (err) {
                // Restore pending files so user can retry
                if (files.length > 0 && this.pendingFiles.length === 0) this.pendingFiles = files;
                this.messages.push({ role: 'assistant', content: err.response?.data?.reply || err.response?.data?.message || 'Something went wrong. Please try again.' });
            } finally { this.isLoading = false; this.scrollBottom(); }
        },

        scrollBottom() { this.$nextTick(() => { const el = this.$refs.messagesEl; if (el) el.scrollTop = el.scrollHeight; }); },
        autoResize() { const el = this.$refs.textInput; if (el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 160) + 'px'; } },
        resetTextarea() { this.$nextTick(() => { const el = this.$refs.textInput; if (el) { el.style.height = 'auto'; el.style.height = '72px'; } }); },

        renderMarkdown(text) {
            if (!text) return '';
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-cherry-800 px-1 py-0.5 rounded text-xs font-mono text-violet-700 dark:text-violet-400">$1</code>')
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-violet-600 underline hover:no-underline" target="_blank">$1</a>')
                .replace(/^### (.+)$/gm, '<p class="font-semibold text-sm mt-2 mb-1">$1</p>')
                .replace(/^## (.+)$/gm, '<p class="font-bold text-sm mt-2 mb-1">$1</p>')
                .replace(/^# (.+)$/gm, '<p class="font-bold text-base mt-2 mb-1">$1</p>')
                .replace(/^- (.+)$/gm, '<p class="flex gap-1.5 my-0.5"><span class="text-violet-400 font-bold flex-shrink-0">&bull;</span><span>$1</span></p>')
                .replace(/^(\d+)\. (.+)$/gm, '<p class="flex gap-1.5 my-0.5"><span class="text-violet-400 font-bold flex-shrink-0">$1.</span><span>$2</span></p>')
                .replace(/\n\n/g, '<br><br>')
                .replace(/\n/g, '<br>');
        },

        saveState() {
            try {
                sessionStorage.setItem('agenting_pim_state', JSON.stringify({
                    isOpen: this.isOpen, activeTab: this.activeTab,
                    activeCapability: this.activeCapability ? this.activeCapability.key : null,
                    messages: this.messages.map(m => ({ role: m.role, content: m.content, result: m.result || null, product_url: m.product_url || null, download_url: m.download_url || null })),
                }));
            } catch (e) {}
        },

        restoreState() {
            try {
                const raw = sessionStorage.getItem('agenting_pim_state');
                if (!raw) return;
                const s = JSON.parse(raw);
                if (s.activeTab) this.activeTab = s.activeTab;
                if (s.activeCapability) this.activeCapability = this.capabilities.find(c => c.key === s.activeCapability) || null;
                if (Array.isArray(s.messages) && s.messages.length > 0) this.messages = s.messages;
                if (s.isOpen) {
                    // Disable the Vue panel slide-in transition and #app margin animation on page load.
                    // Both noTransition and isOpen must change in the SAME synchronous tick so
                    // Vue's <transition> sees name="" when it processes the enter.
                    this.noTransition = true;
                    this.isOpen = true;
                    this.$nextTick(() => {
                        // instant=true prevents #app transition CSS from being set
                        this.adjustLayout(true, true);
                        this.scrollBottom();
                        this.$refs.textInput?.focus();
                        // Re-enable transitions only after two paint frames (more reliable than setTimeout)
                        requestAnimationFrame(() => requestAnimationFrame(() => { this.noTransition = false; }));
                    });
                }
            } catch (e) {}
        },
    },

    beforeUnmount() {
        const appEl = document.getElementById('app');
        if (appEl) appEl.style.marginRight = '';
    },
});
</script>
@endPushOnce
