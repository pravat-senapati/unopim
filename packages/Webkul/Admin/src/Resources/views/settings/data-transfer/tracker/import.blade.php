<x-admin::layouts>
    <!-- Title of the page -->

    <x-slot:title>
        @lang('admin::app.settings.data-transfer.tracker.index.title')
        </x-slot>

        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                <a
                    href="{{ route('admin.settings.data_transfer.imports.index') }}"
                    class="text-gray-600 hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white">
                    @lang('admin::app.settings.data-transfer.tracker.index.title')
                </a>

                - {{ ucfirst(trans($import->jobInstance->entity_type)) }} / {{ ucfirst(trans($import->jobInstance->code)) }}
            </p>

            <!-- Edit Button -->
            @if ($import->jobInstance->type !== 'system')
            <a
                href="{{ route(sprintf('admin.settings.data_transfer.%ss.edit', $import->jobInstance->type), $import->jobInstance->id) }}"
                class="primary-button">
                @lang('admin::app.settings.data-transfer.tracker.import.edit-btn')
            </a>
            @endif
        </div>

        <!-- Import Vue Compontent -->
        <v-import />

        @pushOnce('scripts')
        <script type="text/x-template" id="v-import-template">
            <!-- Body Content -->
            <div class="mt-3.5 rounded-lg border border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900 overflow-hidden box-shadow">

                <div
                    class="p-5"
                    v-if="importResource.state == 'pending'">
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start mb-5">
                        <!-- Step 1: Queued (active) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-500 ring-4 ring-orange-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 mt-5 mx-2" style="background: linear-gradient(to right, #22c55e, #f97316)"></div>
                        <!-- Step 2: Validating (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 000 4h6a2 2 0 000-4M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <!-- Step 3: Importing (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <!-- Step 4: Complete (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="flex gap-1 mt-1 items-center flex-shrink-0">
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-bounce" style="animation-delay:0s"></span>
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-bounce" style="animation-delay:.15s"></span>
                                <span class="w-2 h-2 rounded-full bg-orange-400 animate-bounce" style="animation-delay:.3s"></span>
                            </span>
                            <div>
                                <p class="font-semibold text-orange-700 dark:text-orange-300">@lang('admin::app.settings.data-transfer.imports.import.pending-info')</p>
                                <p class="text-sm text-orange-600/80 dark:text-orange-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.imports.import.pending-info-sub')</p>
                            </div>
                        </div>
                    </div>

                    <!-- Info row -->
                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.status')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@lang('admin::app.settings.data-transfer.tracker.waiting')</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.total-records')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ importResource.processed_rows_count ? Number(importResource.processed_rows_count).toLocaleString() : '—' }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="20" height="14" rx="2" />
                                    <path d="M8 21h8m-4-4v4" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.job-id')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">#@{{ importResource.id }}</p>
                        </div>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            @lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')
                        </span>
                        <a
                            class="transparent-button text-sm hover:dark:bg-cherry-800"
                            href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                            target="_blank">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                </div>

                <!-- ==================== VALIDATING ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'validating'">
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start mb-5">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-violet-600 ring-4 ring-violet-200 animate-pulse">
                                <svg class="w-4 h-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-violet-700 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div class="rounded-lg bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 mt-0.5 animate-spin text-violet-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="font-semibold text-violet-700 dark:text-violet-300">@lang('admin::app.settings.data-transfer.imports.import.validating-info')</p>
                                    <p class="text-sm text-violet-600/80 dark:text-violet-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.imports.import.validating-info-sub')</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center flex-shrink-0 ml-4">
                                <button
                                    class="transparent-button text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900"
                                    @click="pauseImport"
                                    :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <rect x="6" y="4" width="4" height="16" />
                                        <rect x="14" y="4" width="4" height="16" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.pause')
                                </button>
                                <button
                                    class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900"
                                    @click="cancelImport"
                                    :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                                <a
                                    class="transparent-button hover:dark:bg-cherry-800"
                                    href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                                    target="_blank">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Info row -->
                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.running-time')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.total-records')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ importResource.processed_rows_count ? Number(importResource.processed_rows_count).toLocaleString() : '—' }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="20" height="14" rx="2" />
                                    <path d="M8 21h8m-4-4v4" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.job-id')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">#@{{ importResource.id }}</p>
                        </div>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></span>
                            @lang('admin::app.settings.data-transfer.tracker.live') &middot; @lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')
                        </span>
                    </div>
                </div>

                <!-- ==================== VALIDATED ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'validated'">
                    <!-- Step pipeline (valid) -->
                    <div class="flex w-full items-start mb-5" v-if="isValid">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 mt-5 mx-2" style="background: linear-gradient(to right, #22c55e, #f97316)"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-500 ring-4 ring-orange-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-orange-600 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div
                        class="rounded-lg p-4 mb-4"
                        :class="isValid ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                        <p class="flex gap-2 items-center mb-2" v-if="isValid">
                            <i class="icon-done h-fit rounded-full bg-green-200 text-2xl text-green-600 dark:!text-green-600"></i>
                            @lang('admin::app.settings.data-transfer.imports.import.validation-success-info')
                            <span class="flex gap-1 ml-1 items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:0s"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:.15s"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:.3s"></span>
                            </span>
                        </p>
                        <p class="flex gap-2 items-center mb-2" v-else>
                            <i class="icon-cancel h-fit rounded-full bg-red-200 text-2xl text-red-600"></i>
                            @lang('admin::app.settings.data-transfer.imports.import.validation-failed-info')
                        </p>

                        <div class="grid gap-1.5 ml-8">
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">@lang('admin::app.settings.data-transfer.imports.import.total-rows-processed')</span>
                                @{{ importResource.processed_rows_count }}
                            </p>
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">@lang('admin::app.settings.data-transfer.imports.import.total-invalid-rows')</span>
                                @{{ importResource.invalid_rows_count }}
                            </p>
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">@lang('admin::app.settings.data-transfer.imports.import.total-errors')</span>
                                @{{ importResource.errors_count }}
                            </p>
                            <div class="grid gap-1" v-if="importResource.errors?.length">
                                <p class="break-all text-sm text-red-600" v-for="error in importResource.errors">@{{ error }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== WARNINGS SECTION - REDESIGNED ==================== -->
                    <div class="mb-6" v-if="warnings?.length">
                        <!-- Warning Header Card -->
                        <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700/50 mb-4">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-100/50 dark:bg-amber-500/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-orange-100/50 dark:bg-orange-500/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                            
                            <div class="relative p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">
                                                @lang('admin::app.settings.data-transfer.tracker.warnings')
                                            </h3>
                                            <p class="text-sm text-amber-600 dark:text-amber-400">
                                                {{ trans('admin::app.settings.data-transfer.tracker.warning-count') }}: @{{ warnings.length }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Warning Badge -->
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700 dark:bg-amber-800/50 dark:text-amber-300">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @{{ warnings.length }} @lang('admin::app.settings.data-transfer.tracker.found')
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Items List - Modern Card Design -->
                        
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2" v-if="importResource.errors_count && importResource.error_file_path">
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.imports.download_error_report', $import->id) }}"
                            target="_blank">
                            @lang('admin::app.settings.data-transfer.imports.import.download-error-report')
                        </a>
                    </div>
                </div>

                <!-- ==================== FAILED ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'failed'">
                    <div class="rounded-lg p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="flex gap-2 items-center">
                            <i class="icon-cancel h-fit rounded-full bg-red-200 text-2xl text-red-600"></i>
                            @lang('admin::app.settings.data-transfer.tracker.failed-info')
                        </p>
                        <div class="grid gap-1 ml-8 mt-2" v-if="importResource.errors?.length">
                            <p class="break-all text-sm text-red-600" v-for="error in importResource.errors">@{{ error }}</p>
                        </div>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.failed')
                        </span>
                        <a
                            class="transparent-button text-sm hover:dark:bg-cherry-800"
                            href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                            target="_blank">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                </div>

                <!-- ==================== PAUSED ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'paused'">
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <p class="flex gap-2 items-center">
                                <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16" />
                                    <rect x="14" y="4" width="4" height="16" />
                                </svg>
                                <span class="text-amber-800 dark:text-amber-300 font-semibold" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.tracker.paused-info-export')</span>
                                <span class="text-amber-800 dark:text-amber-300 font-semibold" v-else>@lang('admin::app.settings.data-transfer.tracker.paused-info')</span>
                            </p>
                            <div class="flex gap-2 flex-shrink-0 ml-4">
                                <button class="primary-button" @click="resumeImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.resume')
                                </button>
                                <button class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900" @click="cancelImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-amber-800 dark:text-amber-300">@lang('admin::app.settings.data-transfer.imports.import.progress')</span>
                            <span class="text-sm font-medium text-amber-800 dark:text-amber-300">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-amber-200 dark:bg-amber-800 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.tracker.running-time')</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.imports.import.completed-batches')</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ stats.batches.completed }} / @{{ stats.batches.total }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.tracker.job-id')</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">#@{{ importResource.id }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.paused')
                        </span>
                        <a class="transparent-button text-sm hover:dark:bg-cherry-800" href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}" target="_blank">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                </div>

                <!-- ==================== CANCELLED ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'cancelled'">
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <p class="font-semibold text-red-800 dark:text-red-300" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.tracker.cancelled-info-export')</p>
                            <p class="font-semibold text-red-800 dark:text-red-300" v-else>@lang('admin::app.settings.data-transfer.tracker.cancelled-info')</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">@lang('admin::app.settings.data-transfer.imports.import.progress')</span>
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-red-200 dark:bg-red-800 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900" v-for="(value, key) in summary" :key="key">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@{{ key }}</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(value).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700" v-if="importResource.started_at">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.total-duration')</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">@{{ totalDuration() }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.cancelled')
                        </span>
                        <a class="transparent-button text-sm hover:dark:bg-cherry-800" href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}" target="_blank">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                </div>

                <!-- ==================== PROCESSING / PROCESSED ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'processing' || importResource.state == 'processed'">
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start mb-5">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-600 ring-4 ring-green-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-700 text-center leading-tight">
                                <template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template>
                                <template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template>
                            </span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 mt-0.5 animate-spin text-green-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-700 dark:text-green-300" v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.imports.import.importing-info')</p>
                                    <p class="font-semibold text-green-700 dark:text-green-300" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.exporting-info')</p>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5" v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.imports.import.importing-info-sub')</p>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.exporting-info-sub')</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center flex-shrink-0 ml-4">
                                <button
                                    class="transparent-button text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900"
                                    @click="pauseImport"
                                    :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <rect x="6" y="4" width="4" height="16" />
                                        <rect x="14" y="4" width="4" height="16" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.pause')
                                </button>
                                <button
                                    class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900"
                                    @click="cancelImport"
                                    :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                                <a
                                    class="transparent-button hover:dark:bg-cherry-800"
                                    href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                                    target="_blank">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                <template v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.tracker.import-progress')</template>
                                <template v-else>@lang('admin::app.settings.data-transfer.tracker.export-progress')</template>
                            </span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-green-100 dark:bg-green-900/40 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full transition-all duration-500" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <!-- ==================== WARNINGS SECTION - REAL-TIME DURING PROCESSING - REDESIGNED ==================== -->
                    <div class="mb-6" v-if="warnings?.length">
                        <!-- Warning Header Card -->
                        <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700/50 mb-4">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-100/50 dark:bg-amber-500/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-orange-100/50 dark:bg-orange-500/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                            
                            <div class="relative p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center animate-pulse">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">
                                                @lang('admin::app.settings.data-transfer.tracker.warnings')
                                            </h3>
                                            <p class="text-sm text-amber-600 dark:text-amber-400">
                                                {{ trans('admin::app.settings.data-transfer.tracker.warning-count') }}: @{{ warnings.length }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Warning Badge with Live Indicator -->
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700 dark:bg-amber-800/50 dark:text-amber-300">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                                        @{{ warnings.length }} @lang('admin::app.settings.data-transfer.tracker.found')
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Items List - Modern Card Design -->
                        <div class="space-y-3">
                            <div 
                                v-for="(warning, index) in warnings" 
                                :key="index" 
                                class="group relative overflow-hidden rounded-lg border border-amber-200 dark:border-amber-700/50 bg-white dark:bg-cherry-900/50 shadow-sm hover:shadow-md transition-all duration-300"
                            >
                                <!-- Left Accent Bar -->
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500"></div>
                                
                                <!-- Collapsible Header -->
                                <button
                                    @click="toggleWarningItems(index)"
                                    class="w-full flex items-center justify-between p-4 pl-5 hover:bg-amber-50/50 dark:hover:bg-amber-900/20 transition-colors"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <!-- Expand/Collapse Icon with Animation -->
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center flex-shrink-0">
                                            <svg 
                                                class="w-4 h-4 text-amber-600 dark:text-amber-400" 
                                                :style="{ 'transform': expandedWarningIndex === index ? 'rotate(90deg)' : 'rotate(0deg)', 'transition': 'transform 0.2s ease-in-out' }"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                        <!-- Warning Title/SKU -->
                                        <div class="min-w-0 max-w-[568px] overflow-x-auto whitespace-nowrap">
                                            <span class="font-semibold text-gray-800 dark:text-gray-200 truncate">
                                                @{{ warning.reason }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400" v-if="warning.item && Object.keys(warning.item).length">
                                            @{{ Object.keys(warning.item).length }} @lang('admin::app.settings.data-transfer.tracker.details')
                                        </span>
                                    </div>
                                    
                                    <!-- Expand Indicator -->
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-800/30 dark:text-amber-400"
                                            v-if="expandedWarningIndex !== index"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            @lang('admin::app.settings.data-transfer.tracker.view-details')
                                        </span>
                                         
                                    </div>
                                </button>
                                
                                <!-- Expanded Details Panel -->
                                <div 
                                    v-if="expandedWarningIndex === index" 
                                    class="border-t border-amber-100 dark:border-amber-800/30 bg-amber-50/30 dark:bg-amber-900/10"
                                >
                                    <div class="p-4 pl-5">
                                        <!-- Details Header -->
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <span class="text-sm font-medium text-amber-700 dark:text-amber-300">
                                                @lang('admin::app.settings.data-transfer.tracker.warning-details')
                                            </span>
                                        </div>
                                        
                                        <!-- Key-Value Pairs with Scrollable Container -->
                                        <div v-if="warning.item && Object.keys(warning.item).length > 0" class="bg-white dark:bg-cherry-900 rounded-lg border border-amber-200 dark:border-amber-700/30 overflow-hidden">
                                            <div class="max-h-64 overflow-y-auto">
                                                <ul class="divide-y divide-amber-100 dark:divide-amber-800/30">
                                                    <li 
                                                        v-for="(warnMsg, warnIndex) in warning.item" 
                                                        :key="warnIndex"
                                                        class="p-3 hover:bg-amber-50/50 dark:hover:bg-amber-900/10"
                                                    >
                                                        <div class="flex items-start gap-3">
                                                            <!-- Warning Icon -->
                                                            <div class="w-6 h-6 rounded-md bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            
                                                            <div class="flex-1 min-w-0">
                                                                <!-- Key -->
                                                                <div class="flex items-center gap-2 mb-1">
                                                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                                                        @{{ warnIndex }}
                                                                    </span>
                                                                </div>
                                                                
                                                                <!-- Value with scrollable/break-word -->
                                                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                                                    <div class="break-words whitespace-pre-wrap font-mono text-xs bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg border border-amber-100 dark:border-amber-800/30 max-h-32 overflow-y-auto">
                                                                        @{{ typeof warnMsg === 'object' ? JSON.stringify(warnMsg, null, 2) : warnMsg }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <!-- Empty State -->
                                        <div v-else class="text-center py-4">
                                            <svg class="w-8 h-8 mx-auto text-amber-300 dark:text-amber-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                @lang('admin::app.settings.data-transfer.tracker.no-details')
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats grid -->
                    <div class="grid grid-cols-4 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.total-batches')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ stats.batches.total }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.completed-batches')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ stats.batches.completed }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-updated')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ Number(stats.summary?.updated || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-created')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(stats.summary?.created || 0).toLocaleString() }}</p>
                        </div>
                    </div>

                    <!-- Info row -->
                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.running-time')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.estimated-remaining')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedETA() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.records-deleted')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ Number(stats.summary?.deleted || 0).toLocaleString() }}</p>
                        </div>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            @lang('admin::app.settings.data-transfer.tracker.live') &middot; <template v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template>
                        </span>
                    </div>
                </div>

                <!-- ==================== LINKING ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'linking'">
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start mb-5">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 mt-5 mx-2" style="background: linear-gradient(to right, #22c55e, #f97316)"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-500 ring-4 ring-orange-200 animate-pulse">
                                <svg class="w-4 h-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 mt-0.5 animate-spin text-green-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-700 dark:text-green-300">@lang('admin::app.settings.data-transfer.imports.import.linking-info')</p>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.imports.import.linking-info-sub')</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center flex-shrink-0 ml-4">
                                <button class="transparent-button text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900" @click="pauseImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <rect x="6" y="4" width="4" height="16" />
                                        <rect x="14" y="4" width="4" height="16" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.pause')
                                </button>
                                <button class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900" @click="cancelImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                                <a class="transparent-button hover:dark:bg-cherry-800" href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}" target="_blank">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">@lang('admin::app.settings.data-transfer.tracker.linking-progress')</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-green-100 dark:bg-green-900/40 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full transition-all duration-500" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <!-- Stats grid -->
                    <div class="grid grid-cols-2 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.total-batches')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ stats.batches.total }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.completed-batches')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ stats.batches.completed }}</p>
                        </div>
                    </div>

                    <!-- Info row -->
                    <div class="grid grid-cols-2 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.running-time')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.estimated-remaining')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedETA() }}</p>
                        </div>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            @lang('admin::app.settings.data-transfer.tracker.live') &middot; @lang('admin::app.settings.data-transfer.tracker.linking-progress')
                        </span>
                    </div>
                </div>

                <!-- ==================== INDEXING ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'indexing'">
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start mb-5">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 mt-5 mx-2" style="background: linear-gradient(to right, #22c55e, #f97316)"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-500 ring-4 ring-orange-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 mt-0.5 animate-spin text-orange-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="font-semibold text-orange-700 dark:text-orange-300">@lang('admin::app.settings.data-transfer.imports.import.indexing-info')</p>
                                    <p class="text-sm text-orange-600/80 dark:text-orange-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.imports.import.indexing-info-sub')</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center flex-shrink-0 ml-4">
                                <button class="transparent-button text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900" @click="pauseImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <rect x="6" y="4" width="4" height="16" />
                                        <rect x="14" y="4" width="4" height="16" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.pause')
                                </button>
                                <button class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900" @click="cancelImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                                <a class="transparent-button hover:dark:bg-cherry-800" href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}" target="_blank">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">@lang('admin::app.settings.data-transfer.tracker.indexing-progress')</span>
                            <span class="text-sm font-bold text-orange-600 dark:text-orange-400">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-orange-100 dark:bg-orange-900/40 rounded-full h-2.5">
                            <div class="bg-orange-500 h-2.5 rounded-full transition-all duration-500" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <!-- Stats grid -->
                    <div class="grid grid-cols-2 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.total-batches')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ stats.batches.total }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.completed-batches')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ stats.batches.completed }}</p>
                        </div>
                    </div>

                    <!-- Info row -->
                    <div class="grid grid-cols-2 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 6v6l3.5 2" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.running-time')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.imports.import.indexing-type')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@lang('admin::app.settings.data-transfer.imports.import.indexing-type')</p>
                        </div>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                            @lang('admin::app.settings.data-transfer.tracker.live') &middot; @lang('admin::app.settings.data-transfer.tracker.indexing-progress')
                        </span>
                    </div>
                </div>

                <!-- ==================== COMPLETED ==================== -->
                <div
                    class="p-5"
                    v-else-if="importResource.state == 'completed'">
                    <!-- Step pipeline: all done -->
                    <div class="flex w-full items-start mb-5">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500 ring-4 ring-green-200">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message card -->
                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                                <div>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                                            
                                            @lang('admin::app.settings.data-transfer.tracker.job-label') <span class="text-green-700 dark:text-green-400">@{{ jobInstance.entity_type ? (jobInstance.entity_type.charAt(0).toUpperCase() + jobInstance.entity_type.slice(1)) : '' }} / @{{ jobInstance.code }}</span>&nbsp;
                                             <span>
                                                @{{ jobInstance.type === 'export'
                                                    ? '@lang("admin::app.settings.data-transfer.tracker.export-completed-success")'
                                                    : '@lang("admin::app.settings.data-transfer.tracker.completed-success")'
                                                }}
                                            </span>
                                        </span>
                                        <span
                                            v-if="importResource.started_at"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-600 text-white whitespace-nowrap">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="9" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5" />
                                            </svg>
                                            @{{ totalDuration() }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.tracker.completed-info-sub')</p>
                                </div>
                            </div>
                            <a
                                class="transparent-button hover:dark:bg-cherry-800 flex-shrink-0 ml-4"
                                href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                                target="_blank">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                            </a>
                        </div>
                    </div>

                    <!-- Stats grid (use importResource.summary for completed — it's the final aggregated data from the Completed job) -->
                    <div class="grid grid-cols-4 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-created')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(importResource.summary?.created || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-updated')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ Number(importResource.summary?.updated || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-deleted')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(importResource.summary?.deleted || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.total-duration')</p>
                            <p class="text-xl font-bold text-violet-600 dark:text-violet-400 mt-1">@{{ totalDuration() }}</p>
                        </div>
                    </div>

                    <!-- ==================== WARNINGS SECTION - COMPLETED STATE ==================== -->
                    <div class="mb-6" v-if="warnings?.length">
                        <!-- Warning Header Card -->
                        <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700/50 mb-4">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-100/50 dark:bg-amber-500/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-orange-100/50 dark:bg-orange-500/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                            
                            <div class="relative p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">
                                                @lang('admin::app.settings.data-transfer.tracker.warnings')
                                            </h3>
                                            <p class="text-sm text-amber-600 dark:text-amber-400">
                                                {{ trans('admin::app.settings.data-transfer.tracker.warning-count') }}: @{{ warnings.length }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Warning Badge -->
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700 dark:bg-amber-800/50 dark:text-amber-300">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @{{ warnings.length }} @lang('admin::app.settings.data-transfer.tracker.found')
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Items List - Modern Card Design -->
                        <div class="space-y-3">
                            <div 
                                v-for="(warning, index) in warnings" 
                                :key="index" 
                                class="group relative overflow-hidden rounded-lg border border-amber-200 dark:border-amber-700/50 bg-white dark:bg-cherry-900/50 shadow-sm hover:shadow-md transition-all duration-300"
                            >
                                <!-- Left Accent Bar -->
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500"></div>
                                
                                <!-- Collapsible Header -->
                                <button
                                    @click="toggleWarningItems(index)"
                                    class="w-full flex items-center justify-between p-4 pl-5 hover:bg-amber-50/50 dark:hover:bg-amber-900/20 transition-colors"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <!-- Expand/Collapse Icon with Animation -->
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center flex-shrink-0">
                                            <svg 
                                                class="w-4 h-4 text-amber-600 dark:text-amber-400" 
                                                :style="{ 'transform': expandedWarningIndex === index ? 'rotate(90deg)' : 'rotate(0deg)', 'transition': 'transform 0.2s ease-in-out' }"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                        
                                        <!-- Warning Title/SKU -->
                                        <div class="min-w-0 max-w-[568px] overflow-x-auto whitespace-nowrap">
                                            <span class="font-semibold text-gray-800 dark:text-gray-200 truncate">
                                                @{{ warning.reason || warning.sku }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400" v-if="warning.item && Object.keys(warning.item).length">
                                            @{{ Object.keys(warning.item).length }} @lang('admin::app.settings.data-transfer.tracker.details')
                                        </span>
                                    </div>
                                    
                                    <!-- Expand Indicator -->
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-800/30 dark:text-amber-400"
                                            v-if="expandedWarningIndex !== index"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            @lang('admin::app.settings.data-transfer.tracker.view-details')
                                        </span>
                                         
                                    </div>
                                </button>
                                
                                <!-- Expanded Details Panel -->
                                <div 
                                    v-if="expandedWarningIndex === index" 
                                    class="border-t border-amber-100 dark:border-amber-800/30 bg-amber-50/30 dark:bg-amber-900/10"
                                >
                                    <div class="p-4 pl-5">
                                        <!-- Details Header -->
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <span class="text-sm font-medium text-amber-700 dark:text-amber-300">
                                                @lang('admin::app.settings.data-transfer.tracker.warning-details')
                                            </span>
                                        </div>
                                        
                                        <!-- Key-Value Pairs with Scrollable Container -->
                                         
                                        <div v-if="warning.item && Object.keys(warning.item).length > 0" class="bg-white dark:bg-cherry-900 rounded-lg border border-amber-200 dark:border-amber-700/30 overflow-hidden">
                                            <div class="max-h-64 overflow-y-auto">
                                                <ul class="divide-y divide-amber-100 dark:divide-amber-800/30">
                                                    <li 
                                                        v-for="(warnMsg, warnIndex) in warning.item" 
                                                        :key="warnIndex"
                                                        class="p-3 hover:bg-amber-50/50 dark:hover:bg-amber-900/10"
                                                    >
                                                        <div class="flex items-start gap-3">
                                                            <!-- Warning Icon -->
                                                            <div class="w-6 h-6 rounded-md bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            
                                                            <div class="flex-1 min-w-0">
                                                                <!-- Key -->
                                                                <div class="flex items-center gap-2 mb-1">
                                                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                                                        @{{ warnIndex }}
                                                                    </span>
                                                                </div>
                                                                
                                                                <!-- Value with scrollable/break-word -->
                                                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                                                    <div class="break-words whitespace-pre-wrap font-mono text-xs bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg border border-amber-100 dark:border-amber-800/30 max-h-32 overflow-y-auto">
                                                                        @{{ typeof warnMsg === 'object' ? JSON.stringify(warnMsg, null, 2) : warnMsg }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <!-- Empty State -->
                                        <div v-else class="text-center py-4">
                                            <svg class="w-8 h-8 mx-auto text-amber-300 dark:text-amber-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                @lang('admin::app.settings.data-transfer.tracker.no-details')
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export/error downloads -->
                    <div class="flex gap-2 mb-2" v-if="jobInstance.type == 'export' && importResource.file_path">
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.tracker.archive.download', $import->id) }}"
                            target="_blank"
                            v-if="toBoolean(jobInstance.filters.with_media)">
                            @lang('admin::app.settings.data-transfer.exports.export.download-created-file')
                        </a>
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.tracker.download', $import->id) }}"
                            target="_blank"
                            v-else>
                            @lang('admin::app.settings.data-transfer.exports.export.download-created-file')
                        </a>
                    </div>
                    <div class="flex gap-2 mb-2" v-if="jobInstance.type == 'import' && importResource.errors_count && importResource.error_file_path">
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.imports.download_error_report', $import->id) }}"
                            target="_blank">
                            @lang('admin::app.settings.data-transfer.imports.import.download-error-report')
                        </a>
                    </div>

                    <!-- Bottom bar -->
                    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.completed')
                        </span>
                        <a
                            class="primary-button"
                            href="{{ route('admin.settings.data_transfer.imports.import-view', $import->job_instances_id) }}"
                            v-if="jobInstance.type == 'import'">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            @lang('admin::app.settings.data-transfer.tracker.run-again')
                        </a>
                    </div>
                </div>

            </div>
        </script>

        <script type="module">
            app.component('v-import', {
                template: '#v-import-template',

                data() {
                    return {
                        importResource: @json($import),
                        jobInstance: @json($jobInstance),
                        isValid: "{{ $isValid }}",
                        summary: @json($summary),
                        stats: @json($stats),
                        warnings: @json($warnings ?? []),
                        expandedWarningIndex: null,

                        elapsedSeconds: 0,
                        clockInterval: null,
                        workStartedAt: null,
                        isActionInProgress: false,
                    };
                },

                mounted() {
                    this.getStats();
                },

                methods: {
                    toBoolean(value) {
                        return value.toLowerCase() == 1;
                    },

                    toggleWarningItems(index) {
                        // Ensure we're comparing numbers properly
                        const idx = Number(index);
                        const current = this.expandedWarningIndex === null ? null : Number(this.expandedWarningIndex);
                        this.expandedWarningIndex = current === idx ? null : idx;
                    },

                    formatDuration(seconds) {
                        if (seconds < 60) return seconds + 's';
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        if (m < 60) return m + 'm ' + (s > 0 ? s + 's' : '');
                        const h = Math.floor(m / 60);
                        return h + 'h ' + (m % 60) + 'm';
                    },

                    formattedElapsed() {
                        return this.formatDuration(this.elapsedSeconds);
                    },

                    formattedETA() {
                        const progress = parseFloat(this.stats.progress);
                        if (!progress || progress <= 0 || progress >= 100 || !this.workStartedAt) return '—';
                        const workElapsed = (Date.now() - this.workStartedAt) / 1000;
                        if (workElapsed < 2) return '—';
                        const remaining = (workElapsed / progress) * (100 - progress);
                        return this.formatDuration(Math.floor(remaining));
                    },

                    totalDuration() {
                        if (this.importResource.started_at && this.importResource.completed_at) {
                            const start = new Date(this.importResource.started_at).getTime();
                            const end = new Date(this.importResource.completed_at).getTime();
                            return this.formatDuration(Math.floor((end - start) / 1000));
                        }
                        return this.formatDuration(this.elapsedSeconds);
                    },

                    startClock() {
                        if (this.clockInterval || !this.importResource.started_at) return;
                        const startTime = new Date(this.importResource.started_at).getTime();
                        this.elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                        this.clockInterval = setInterval(() => {
                            this.elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                        }, 1000);
                    },

                    stopClock() {
                        if (this.clockInterval) {
                            clearInterval(this.clockInterval);
                            this.clockInterval = null;
                        }
                    },

                    validate() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.validate', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;
                                this.isValid = response.data.is_valid;
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    },

                    start() {
                        this.importResource.state = 'validation';

                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.start', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;
                                this.stats = response.data.stats;
                                this.getStats();
                            })
                            .catch(error => {
                                this.importResource.state = 'validated';
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    },

                    link() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.link', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;
                                this.stats = response.data.stats;
                                if (this.importResource.state == 'linking') {
                                    this.link();
                                } else if (this.importResource.state == 'indexing') {
                                    this.index();
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    },

                    index() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.index_data', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;
                                this.stats = response.data.stats;
                                if (this.importResource.state == 'indexing') {
                                    this.index();
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    },

                    pauseImport() {
                        this.isActionInProgress = true;
                        this.$axios.post("{{ route('admin.settings.data_transfer.imports.pause', $import->id) }}")
                            .then((response) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: response.data.message
                                });
                                this.getStats();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'Failed to pause import.'
                                });
                            })
                            .finally(() => {
                                this.isActionInProgress = false;
                            });
                    },

                    resumeImport() {
                        this.isActionInProgress = true;
                        this.$axios.post("{{ route('admin.settings.data_transfer.imports.resume', $import->id) }}")
                            .then((response) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });
                                this.getStats();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'Failed to resume import.'
                                });
                            })
                            .finally(() => {
                                this.isActionInProgress = false;
                            });
                    },

                    cancelImport() {
                        if (!confirm('@lang("admin::app.settings.data-transfer.tracker.cancel-confirm")')) {
                            return;
                        }
                        this.isActionInProgress = true;
                        this.$axios.post("{{ route('admin.settings.data_transfer.imports.cancel', $import->id) }}")
                            .then((response) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: response.data.message
                                });
                                this.getStats();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'Failed to cancel import.'
                                });
                            })
                            .finally(() => {
                                this.isActionInProgress = false;
                            });
                    },

                    getStats() {
                        let state = 'processed';

                        if (this.importResource.state == 'linking') {
                            state = 'linked';
                        } else if (this.importResource.state == 'indexing') {
                            state = 'indexed';
                        }

                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.stats', $import->id) }}/" + state)
                            .then((response) => {
                                this.importResource = response.data.import;
                                this.stats = response.data.stats;
                                this.isValid = response.data.isValid;
                                this.summary = response.data.summary;
                                this.jobInstance = response.data.jobInstance;

                                const activeStates = ['validating', 'processing', 'processed', 'linking', 'indexing'];
                                if (activeStates.includes(this.importResource.state)) {
                                    this.startClock();
                                    if (parseFloat(this.stats.progress) > 0 && this.workStartedAt === null) {
                                        this.workStartedAt = Date.now();
                                    }
                                    // Fetch warnings in real-time during active processing
                                    this.getWarnings();
                                } else {
                                    this.stopClock();
                                    // Final fetch of warnings when job completes
                                    this.getWarnings();
                                }

                                const pollingStates = ['pending', 'validating', 'validated', 'processing', 'processed', 'linking', 'indexing', 'paused'];
                                if (pollingStates.includes(this.importResource.state)) {
                                    setTimeout(() => {
                                        this.getStats();
                                    }, 1000);
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    },

                    getWarnings() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.tracker.warnings', $import->id) }}")
                            .then((response) => {
                                this.warnings = response.data.warnings;

                            })
                            .catch(error => {
                                // Silently fail for warnings - non-critical
                            });
                    }
                }
            })
        </script>
        @endPushOnce
</x-admin::layouts>