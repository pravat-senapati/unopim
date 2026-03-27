@php
    $channelValue = core()->getConfigData('general.magic_ai.translation.source_channel');
    $localeValue = core()->getConfigData('general.magic_ai.translation.source_locale');
    $targetChannel = core()->getConfigData('general.magic_ai.translation.target_channel');
    $targetLocales = core()->getConfigData('general.magic_ai.translation.target_locale');
    $targetLocales = json_encode(explode(',', $targetLocales) ?? []);
    $model = core()->getConfigData('general.magic_ai.translation.ai_model');
    
    // Get product IDs from session (set by bulkedit.translation controller)
    
    $channels = core()->getAllChannels();
    $channelOptions = [];
    foreach ($channels as $channel) {
        $channelName = $channel->name;
        $channelOptions[] = [
            'id'    => $channel->code,
            'label' => empty($channelName) ? "[$channel->code]" : $channelName,
        ];
    }
    $channelOptionsJson = json_encode($channelOptions);
@endphp
<v-modal-bulk-translation ref="bulkTranslationModal"
    :channel-value="{{ json_encode($channelValue) }}"
    :locale-value='@json($localeValue)'
    :channel-target="{{ json_encode($targetChannel) }}"
    :target-locales="{{$targetLocales}}"
    :model="'{{$model}}'"
></v-modal-bulk-translation>
@pushOnce('scripts')
    <script type="text/x-template" id="v-modal-bulk-translation-template">
        <div>
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="translationForm"
            >
                <form @submit="handleSubmit($event, translate)" ref="translationForm">
                    <x-admin::modal
                        ref="translationModal"
                        clip
                        @toggle="handleToggle"
                    >
                        <x-slot:header>
                            <p class="flex items-center text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.products.edit.translate.title')
                            </p>
                        </x-slot:header>
                        <x-slot:content class="flex gap-5 mt-3.5 max-xl:flex-wrap">
                            <div class="w-full max-h-[calc(100vh-160px)] overflow-y-auto pr-2">
                            <section class="left-column flex flex-col gap-2 flex-1/5" :class="currentStep === 3 ? '' : 'w-full'">
                                <section class="grid gap-2 items-center justify-center modal-steps-section mb-4 dark:text-white">
                                    <div class="flex justify-center items-center">
                                        <div class="w-3 h-3 bg-violet-700 rounded-full"></div>
                                        <hr class="w-[200px] dark:bg-cherry-600 h-1 border-0" :class="currentStep >= 2 ? 'bg-violet-700' : 'bg-violet-100'">
                                        <div class="w-3 h-3 bg-violet-400 rounded-full" :class="currentStep >= 2 ? 'bg-violet-700' : 'bg-violet-400'"></div>
                                    </div>
        
                                    <div class="flex justify-around items-center text-center dark:text-slate-50">
                                        <p class="text-sm" :class="currentStep === 1 ? 'text-violet-700' : ''">@lang('admin::app.catalog.products.edit.translate.step') 1 <br> @lang('admin::app.catalog.products.edit.translate.select-source')</p>
                                        <p class="text-sm" :class="currentStep === 2 ? 'text-violet-700' : ''">@lang('admin::app.catalog.products.edit.translate.step') 2 <br> @lang('admin::app.catalog.products.edit.translate.select-target')</p>
                                    </div>
        
                                    @lang('admin::app.catalog.products.edit.translate.first-step-title')
                                </section>
        
                                <section class="bg-violet-50 dark:bg-cherry-800 rounded-md mb-2 p-3" id="step-1">
                                    <h3 class="dark:text-white mb-2 text-sm font-bold">
                                        @lang('admin::app.catalog.products.edit.translate.source-content')
                                    </h3>
        
                                    <!-- Source Channel -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.edit.translate.source-channel')
                                        </x-admin::form.control-group.label>
                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="channel"
                                            rules="required"
                                            ::value="sourceChannel"
                                            :options="$channelOptionsJson"
                                            @input="getSourceLocale"
                                            ::disabled="currentStep > 2"
                                        >
                                        </x-admin::form.control-group.control>
        
                                        <x-admin::form.control-group.error control-name="channel"></x-admin::form.control-group.error>
                                    </x-admin::form.control-group >
        
                                    <!-- Source Locale -->
                                    <x-admin::form.control-group v-if="localeOption">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.edit.translate.locale')
                                        </x-admin::form.control-group.label>
        
                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="locale"
                                            rules="required"
                                            ref="localeRef"
                                            ::value="sourceLocale"
                                            ::options="localeOption"
                                            @input="resetTargetLocales"
                                            ::disabled="currentStep > 2"
                                        >
                                        </x-admin::form.control-group.control>
        
                                        <x-admin::form.control-group.error control-name="locale"></x-admin::form.control-group.error>
                                    </x-admin::form.control-group >
        
                                    <!-- Attributes -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required w-full">
                                            @lang('admin::app.catalog.products.edit.translate.attributes')
                                        </x-admin::form.control-group.label>
                                        <div class="w-full ">
                                            <v-async-select-handler
                                                name="filtered_attributes"
                                                multiple="true"
                                                :onselect="false"
                                                track-by="code"
                                                label-by="name"
                                                :value="selectedAttributes"
                                                list-route="{{ route('admin.catalog.bulkedit.attributes.fetch-translation') }}"
                                                @input="updateSelectedAttributes"
                                                ::disabled="currentStep > 2"
                                            />
        
                                            <p v-if="validationError" class="text-red-500 text-sm mt-1">
                                                @{{ validationError }}
                                            </p>
                                        </div>
                                    </x-admin::form.control-group>
                                </section>
        
                                <template v-if="currentStep > 1">
                                    <h2 class="mt-6 mb-2 text-center">@lang('admin::app.catalog.products.edit.translate.second-step-title')</h2>
                                    <section class="bg-violet-50 dark:bg-cherry-800 rounded-md mb-2 p-3" id="step-2">
                                        <h3 class="dark:text-white mb-2 text-sm font-bold">
                                            @lang('admin::app.catalog.products.edit.translate.target-content')
                                        </h3>
        
                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.translate.target-channel')
                                            </x-admin::form.control-group.label>
                                            <x-admin::form.control-group.control
                                                type="select"
                                                name="targetChannel"
                                                rules="required"
                                                ::value="targetChannel"
                                                :options="$channelOptionsJson"
                                                @input="getTargetLocale"
                                                ::disabled="currentStep > 2"
                                            />
                                            <x-admin::form.control-group.error control-name="targetChannel" />
                                        </x-admin::form.control-group>
        
                                        <x-admin::form.control-group v-if="targetLocOptions">
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.translate.target-locales')
                                            </x-admin::form.control-group.label>
                                            <x-admin::form.control-group.control
                                                type="multiselect"
                                                id="section"
                                                ref="targetLocOptionsRef"
                                                name="targetLocale"
                                                rules="required"
                                                ::value="targetLocales"
                                                ::options="targetLocOptions"
                                                track-by="id"
                                                label-by="label"
                                                ::disabled="currentStep > 2"
                                            />
                                            <x-admin::form.control-group.error control-name="targetLocale" />
                                        </x-admin::form.control-group>
                                    </section>
                                </template>
                            </section>
                            </div>
                        </x-slot:header>
    
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <template v-if="currentStep === 1">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="nextStep"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.next')
                                    </button>
                                </template>
                                <template v-else-if="currentStep === 2">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="previousStep"
                                        ::disabled="isLoading"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.cancel')
                                    </button>
                                    <button
                                        type="submit"
                                        class="primary-button"
                                        ::disabled="isLoading"
                                    >
                                        <!-- Spinner -->
                                        <template v-if="isLoading">
                                            <img
                                                class="animate-spin h-5 w-5 text-violet-700"
                                                src="{{ unopim_asset('images/spinner.svg') }}"
                                            />
    
                                            @lang('admin::app.catalog.products.edit.translate.translating')
                                        </template>
    
                                        <template v-else>
                                            <span class="icon-magic text-2xl text-violet-700"></span>
                                            @lang('admin::app.catalog.products.edit.translate.translate-btn')
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </x-slot:footer>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-modal-bulk-translation', {
            template: '#v-modal-bulk-translation-template',
             props: [
                'channelValue',
                'localeValue',
                'model',
                'channelTarget',
                'targetLocales'
            ],
            data() {
                return {
                    productIds: window.bulkTranslationProductIds || [],
                    selectedAttributes: [],
                    targetLocOptions: null,
                    localeOption: null,
                    sourceData: null,
                    isLoading: false,
                    sourceLocale: this.localeValue,
                    sourceChannel: this.channelValue,
                    targetChannel: this.channelTarget,
                    targetLocales: this.targetLocales,
                    currentStep: 1,
                    validationError: '',
                    applied: null,
                };
            },

             mounted() {
                this.registerEvents();
            },

            created() {
                this.registerGlobalEvents();
            },

            methods: {
                registerEvents() {
                    this.$emitter.on('change-datagrid', this.updateProperties);
                },

                updateProperties({available, applied }) {
                    this.available = available;
                    this.applied = applied;
                },

                open({
                    title = "{{ trans('admin::app.catalog.products.bulk-translation.modal.title') }}",
                    agree = () => {},
                }) {
                    this.resetForm();
                    
                    // Product IDs are available from session via window.bulkTranslationProductIds
                    // Also try to get from datagrid if available
                    this.agreeCallback = agree;
                    this.$refs.translationModal.toggle();
                    this.fetchSourceLocales();
                },

                resetForm() {
                    this.currentStep = 1;
                    this.sourceLocale =  this.localeValue;
                    this.sourceChannel = this.sourceChannel;
                    this.targetChannel = this.targetChannel;
                    this.targetLocales = this.targetLocales;
                    this.selectedAttributes = [];
                    this.validationError = '';
                    this.isLoading = false;
                    this.localeOption = null;
                    this.targetLocOptions = null;
                },

                updateSelectedAttributes(event) {
                    this.selectedAttributes = event || [];
                },

                cancel() {
                    this.$refs.translationModal.close();
                    this.resetForm();
                },

                previousStep() {
                    this.currentStep = 1;
                },

                handleToggle() {
                    if (!this.$refs.translationModal.isOpen) {
                        this.resetForm();
                    }
                },

                fetchSourceLocales() {
                    this.getLocale(this.sourceChannel)
                        .then((options) => {
                            this.localeOption = JSON.stringify(options);
                        })
                        .catch((error) => {
                            console.error('Error fetching source locales:', error);
                        });
                },

                fetchTargetLocales() {
                    this.getLocale(this.targetChannel)
                        .then((options) => {
                            if (this.targetChannel === this.sourceChannel) {
                                options = options.filter(option => option.id != this.sourceLocale);
                            }

                            this.targetLocOptions = JSON.stringify(options);
                        })
                        .catch((error) => {
                            console.error('Error fetching target locales:', error);
                        });
                },

                getSourceLocale(event) {
                    if (event) {
                        this.sourceChannel = typeof event === 'string' ? JSON.parse(event).id : event.id;

                        this.getLocale(this.sourceChannel)
                            .then((options) => {
                                if (this.$refs['localeRef']) {
                                    this.$refs['localeRef'].selectedValue = null;
                                }

                                this.localeOption = JSON.stringify(options);

                                if (options.length == 1) {
                                    this.sourceLocale = options[0].id;

                                    if (this.$refs['localeRef']) {
                                        this.$refs['localeRef'].selectedValue = options[0];
                                    }
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });
                    }
                },    

                getTargetLocale(event) {
                    if (event) {
                        this.targetChannel = typeof event === 'string' ? JSON.parse(event).id : event.id;

                        this.getLocale(this.targetChannel)
                            .then((options) => {
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = null;
                                }

                                if (this.targetChannel === this.sourceChannel) {
                                    options = options.filter(option => option.id != this.sourceLocale);
                                }

                                this.targetLocOptions = JSON.stringify(options);
                                this.targetLocales = options;

                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = options;
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });
                    }
                },   

                resetTargetLocales(event) {
                    if (event) {
                        this.sourceLocale = typeof event === 'string' ? JSON.parse(event).id : event.id;
                        this.getLocale(this.targetChannel)
                            .then((options) => {
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = null;
                                }
                                if (this.targetChannel === this.sourceChannel) {
                                    options = options.filter(option => option.id != this.sourceLocale);
                                }
                                this.targetLocOptions = JSON.stringify(options);
                                this.targetLocales = options;
                                if (this.$refs['targetLocOptionsRef']) {
                                    this.$refs['targetLocOptionsRef'].selectedValue = options;
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });
                    }
                },

                getLocale(channel) {
                    return this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                        params: {
                            channel: channel,
                        },
                    })
                    .then((response) => {
                        return response.data?.locales || [];
                    })
                    .catch((error) => {
                        console.error('Error fetching locales:', error);
                        throw error;
                    });
                },

                validateStep1() {
                    this.validationError = '';
                    
                    if (!this.sourceChannel) {
                        this.validationError = "{{ trans('admin::app.catalog.products.bulk-translation.validation.select-source-channel') }}";
                        return false;
                    }
                    
                    if (!this.sourceLocale) {
                        this.validationError = "{{ trans('admin::app.catalog.products.bulk-translation.validation.select-source-locale') }}";
                        return false;
                    }
                    
                    if (!this.selectedAttributes || this.selectedAttributes.length === 0) {
                        this.validationError = "{{ trans('admin::app.catalog.products.bulk-translation.validation.select-attribute-or-family') }}";
                        return false;
                    }
                    
                    return true;
                },

                validateStep2() {
                    this.validationError = '';
                    
                    if (!this.targetChannel) {
                        this.validationError = "{{ trans('admin::app.catalog.products.bulk-translation.validation.select-target-channel') }}";
                        return false;
                    }
                    
                    if (!this.targetLocales || this.targetLocales.length === 0) {
                        this.validationError = "{{ trans('admin::app.catalog.products.bulk-translation.validation.select-target-locale') }}";
                        return false;
                    }
                    
                    return true;
                },

                nextStep() {
                    if (this.currentStep === 1) {
                        if (!this.validateStep1()) return;
                        this.currentStep = 2;
                        this.fetchTargetLocales();
                    }
                },

                translate(params, {
                    resetForm,
                    resetField,
                    setErrors
                }) {
                    if (!this.validateStep2()) return;

                    this.isLoading = true;
                    const formData = new FormData(this.$refs.translationForm);
                    formData.append('product_ids', this?.applied?.massActions?.indices);
                    this.$axios.post("{{ route('admin.catalog.products.bulk-translate') }}", formData)
                        .then((response) => {
                            this.isLoading = false;

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message || '@lang("admin::app.catalog.products.bulk-translation.modal.translation-started")'
                            });

                            this.cancel();
                        })
                        .catch((error) => {
                            this.isLoading = false;
                            console.error('Translation error:', error);
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || "{{ trans('admin::app.catalog.products.bulk-translation.validation.translation-failed') }}"
                            });
                        });
                },

                registerGlobalEvents() {
                    this.$emitter.on('open-bulk-translation-modal', this.open);
                }
            },
        });
    </script>
@endPushOnce