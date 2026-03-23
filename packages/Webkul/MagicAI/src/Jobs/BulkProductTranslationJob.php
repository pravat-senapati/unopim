<?php

namespace Webkul\MagicAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\Product\Repositories\ProductRepository;

class BulkProductTranslationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Repository for managing job instances.
     */
    protected JobInstancesRepository $jobInstancesRepository;

    /**
     * Repository for tracking job execution.
     */
    protected JobTrackRepository $jobTrackRepository;

    /**
     * Current job track instance.
     *
     * @var mixed
     */
    protected $jobTrackInstance;

    /**
     * Logger instance for this job.
     *
     * @var mixed
     */
    protected $jobLogger;

    /**
     * Total number of products to translate.
     */
    protected int $totalProducts = 0;

    /**
     * Number of products successfully translated.
     */
    protected int $processedProducts = 0;

    protected $productIds;

    protected $attributeCodes;

    protected $sourceChannel;

    protected $sourceLocale;

    protected $targetChannel;

    protected $targetLocales;

    protected $userId;

    protected $attributesAll;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $productIds,
        array $attributeCodes,
        string $sourceChannel,
        string $sourceLocale,
        string $targetChannel,
        array $targetLocales,
        int $userId
    ) {
        $this->productIds = $productIds;
        $this->attributeCodes = $attributeCodes;
        $this->sourceChannel = $sourceChannel;
        $this->sourceLocale = $sourceLocale;
        $this->targetChannel = $targetChannel;
        $this->targetLocales = $targetLocales;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->jobInstancesRepository = app(JobInstancesRepository::class);
        $this->jobTrackRepository = app(JobTrackRepository::class);

        $jobInstance = $this->jobInstancesRepository->findOneByField('code', 'bulk_product_translation')
            ?? $this->createDemoJobInstance();

        $this->jobTrackInstance = $this->jobTrackRepository->create([
            'state'            => AbstractJob::STATE_PENDING,
            'meta'             => $jobInstance->toJson(),
            'job_instances_id' => $jobInstance->id,
            'user_id'          => $this->userId,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->jobLogger = JobLogger::make($this->jobTrackInstance->id);

        $productRepository = app(ProductRepository::class);
        $attributeRepository = app(AttributeRepository::class);
        $this->attributesAll = $attributeRepository->whereIn('code', $this->attributeCodes)->get()->keyBy('code');

        try {
            $this->started();

            // Get all products
            $products = $productRepository->whereIn('id', $this->productIds)->get();

            $this->totalProducts = $products->count();

            $this->markValidated($this->totalProducts);


            // Process each product
            foreach ($products as $product) {
                $this->translateProduct(
                    $product,
                    $this->attributesAll,
                    $productRepository
                );

                // Update progress every 10 products
                if ($this->processedProducts % 10 === 0) {
                    $this->updateProgress($this->processedProducts);
                }
            }

            $this->updateProgress($this->processedProducts);

            $this->markCompleted();
        } catch (\Exception $e) {
            $this->jobLogger->error('Job failed: '.$e->getMessage());
            $this->jobTrackRepository->update([
                'state'  => AbstractJob::STATE_FAILED,
                'errors' => [$e->getMessage()],
            ], $this->jobTrackInstance->id);
        }
    }

    /**
     * Mark the job as started and update its state.
     */
    public function started()
    {
        $this->jobLogger->info('Bulk product translation job started');

        $this->jobTrackRepository->update([
            'state'      => AbstractJob::STATE_PROCESSING,
            'started_at' => now(),
            'summary'    => [],
        ], $this->jobTrackInstance->id);
    }

    /**
     * Mark the job as validated and update summary counts.
     */
    public function markValidated(int $count)
    {
        $this->jobTrackRepository->update([
            'state'              => AbstractJob::STATE_PROCESSING,
            'invalid_rows_count' => 0,
            'summary'            => [
                'total_rows_count' => $count,
            ],
        ], $this->jobTrackInstance->id);
    }

    /**
     * Mark the job as completed and update summary details.
     */
    public function markCompleted()
    {
        $this->jobTrackInstance->refresh();

        $summary = [
            'updated'   => $this->processedProducts,
            'created'   => 0,
            'skipped'   => $this->jobTrackInstance->invalid_rows_count,
        ];

        $this->jobTrackRepository->update([
            'state'        => AbstractJob::STATE_COMPLETED,
            'summary'      => $summary,
            'completed_at' => now(),
        ], $this->jobTrackInstance->id);

        $this->jobLogger->info('Bulk product translation job completed');
    }

    /**
     * Update job progress with the number of processed rows.
     */
    public function updateProgress(int $processedCount)
    {
        $this->jobTrackRepository->update([
            'state'                => AbstractJob::STATE_PROCESSING,
            'processed_rows_count' => $processedCount,
        ], $this->jobTrackInstance->id);
    }

    /**
     * Create a demo job instance for bulk product translation.
     */
    public function createDemoJobInstance()
    {
        return $this->jobInstancesRepository->create([
            'type'                   => 'system',
            'action'                 => 'translation',
            'code'                   => 'bulk_product_translation',
            'entity_type'            => 'products',
            'validation_strategy'    => 'strict',
            'allowed_errors'         => 0,
            'field_separator'        => ',',
            'file_path'              => '',
            'images_directory_path'  => '',
            'filters'                => '',
        ]);
    }

    /**
     * Translate a single product's attributes.
     */
    protected function translateProduct(
        $product,
        $attributes,
        ProductRepository $productRepository
    ) {
        $productValues = $product->values ?? [];
        $sku = $productValues['common']['sku'];

        $replaceTranslation = core()->getConfigData('general.magic_ai.translation.replace');
        // Get existing values structure or create new one
        if (! isset($productValues['channel_locale_specific'])) {
            $productValues['channel_locale_specific'] = [];
        }

        if (! isset($productValues['channel_locale_specific'][$this->targetChannel])) {
            $productValues['channel_locale_specific'][$this->targetChannel] = [];
        }
        // Process each target locale

        // Translate each attribute
        $attributesToTranslate = [];
        foreach ($this->attributeCodes as $attributeCode) {
            $attribute = $attributes[$attributeCode] ?? null;
            if (! $attribute) {
                continue;
            }

            // Get source value from the product
            $sourceValue = $this->getSourceValue(
                $productValues,
                $attributeCode,
                $this->sourceChannel,
                $this->sourceLocale
            );

            if (! $sourceValue) {
                $this->jobLogger->error("$attributeCode: value is blank of the product $sku");

                continue;
            }

            $attributesToTranslate[$attributeCode] = $sourceValue;
        }
        $updateProduct = false;
        if (! empty($attributesToTranslate)) {
            $translatedData = $this->translateMultipleAttributeValue(
                $attributesToTranslate,
                $this->sourceLocale,
                $this->targetLocales
            );

            foreach ($this->targetLocales as $targetLocale) {
                if (!isset($translatedData[$targetLocale])) {
                    $this->jobLogger->error("Missing translation for locale {$targetLocale} for product {$sku}");
                    continue;
                }
                $attributesData = $translatedData[$targetLocale] ?? [];
                foreach ($attributesData as $attributeCode => $translatedValue) {
                    $attribute = $this->attributesAll[$attributeCode];
                    $updateProduct = true;
                    if ($attribute->value_per_locale && $attribute->value_per_channel) {
                        $productValues['channel_locale_specific'][$this->targetChannel][$targetLocale][$attributeCode] =
                            $replaceTranslation
                            || ! isset($productValues['channel_locale_specific'][$this->targetChannel][$targetLocale][$attributeCode])
                            ? $translatedValue
                            : $productValues['channel_locale_specific'][$this->targetChannel][$targetLocale][$attributeCode];
                    } elseif ($attribute->value_per_locale) {
                        $productValues['locale_specific'][$targetLocale][$attributeCode] =
                            $replaceTranslation
                            || ! isset($productValues['locale_specific'][$targetLocale][$attributeCode])
                            ? $translatedValue
                            : $productValues['locale_specific'][$targetLocale][$attributeCode];
                    }
                }
            }

            if ($updateProduct) {
                $product->values = $productValues;
                $product->save();
                $this->processedProducts++;
            }
        }
    }

    protected function translateMultipleAttributeValue(
        array $contents,
        string $sourceLocale,
        array $targetLocales
    ): array {
        if (! core()->getConfigData('general.magic_ai.translation.enabled')) {
            return $contents;
        }

        $translatedResults = [];

        try {

            $model = core()->getConfigData('general.magic_ai.translation.ai_model') ?? 'gpt-4o-mini';

            $jsonPayload = json_encode($contents);

            /**
             * Decide batching strategy
             */
            $chunks = strlen($jsonPayload) < 6000
                ? [$contents]
                : array_chunk($contents, 10, true);

            foreach ($chunks as $chunk) {
                $payload = [
                    'source_locale'  => $sourceLocale,
                    'target_locales' => $targetLocales,
                    'attributes'     => $chunk,
                ];

                $prompt = "You are a translation API. Translate the following JSON fields from
                
                Source locale: {$sourceLocale} to Target locales: ".implode(', ', $targetLocales).'. 
                Return ONLY valid JSON in this format: {
                    "locale": {
                        "attribute_code": "translated value"
                    }
                    }
                Do not include any additional text, descriptions, explanations.
    
                JSON:
                '.json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $response = MagicAI::setModel($model)
                    ->setPlatForm(core()->getConfigData('general.magic_ai.settings.ai_platform'))
                    ->setPrompt($prompt)
                    ->ask();

                $translated = trim($response);

                $translated = preg_replace('/```json|```/', '', $translated);
                $decoded = json_decode($translated, true);

                if (! is_array($decoded)) {
                    $jsonError = function_exists('json_last_error_msg')
                        ? json_last_error_msg()
                        : 'Unknown JSON error';

                    $this->jobLogger->error(
                        'AI returned invalid JSON. Raw response: ' . $translated . ' | JSON error: ' . $jsonError
                    );
                    continue;
                }

                /**
                 * Merge locale results safely
                 */
                foreach ($decoded as $locale => $attributes) {

                    if (! isset($translatedResults[$locale])) {
                        $translatedResults[$locale] = [];
                    }

                    $translatedResults[$locale] = array_merge(
                        $translatedResults[$locale],
                        $attributes
                    );
                }
            }

            return $translatedResults;
        } catch (\Exception $e) {

            Log::error('Bulk translation error: '.$e->getMessage());

            if ($this->jobLogger) {
                $this->jobLogger->error('Translation failed: '.$e->getMessage());
            }

            return [];
        }
    }

    /**
     * Get the source value from product values.
     */
    protected function getSourceValue(array $productValues, string $attributeCode, string $channel, string $locale): ?string
    {
        // Check channel_locale_specific first
        if (isset($productValues['channel_locale_specific'][$channel][$locale][$attributeCode])) {
            return $productValues['channel_locale_specific'][$channel][$locale][$attributeCode];
        }

        if (isset($productValues['locale_specific'][$locale][$attributeCode])) {
            return $productValues['locale_specific'][$locale][$attributeCode];
        }

        // Check regular values
        if (isset($productValues[$attributeCode])) {
            $attributeValues = $productValues[$attributeCode];

            if (isset($attributeValues[$channel][$locale])) {
                return $attributeValues[$channel][$locale];
            }
        }

        return null;
    }
}
