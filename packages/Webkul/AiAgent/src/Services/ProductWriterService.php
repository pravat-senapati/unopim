<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\Core\Filesystem\FileStorer;

/**
 * Persists an AI-generated product draft into the Unopim PIM.
 *
 * This service bridges the AI pipeline output (ImageProductContext)
 * and the Unopim product storage layer.
 */
class ProductWriterService
{
    /**
     * Attribute codes that live in channel_locale_specific.
     *
     * @var array<string>
     */
    protected const CHANNEL_LOCALE_ATTRS = [
        'name',
        'short_description',
        'description',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'price',
        'cost',
    ];

    /**
     * Create a product in the PIM from the enriched context.
     *
     * @param  array<string, mixed>  $options
     */
    public function createProduct(ImageProductContext $ctx, array $options = []): ImageProductContext
    {
        $sku     = $options['sku'] ?? $this->generateSku($ctx);
        $locale  = $options['locale'] ?? 'en_US';
        $channel = $options['channel'] ?? 'default';
        $family  = $options['family'] ?? null;

        $resolved = $ctx->resolvedAttributes();

        $productId = $this->createViaRepository($sku, $family, $resolved, $locale, $channel, $ctx);

        return $ctx->withProductId($productId);
    }

    /**
     * Create a product via Unopim's ProductRepository.
     *
     * @param  array<string, mixed>  $attributes
     * @return int|string
     */
    protected function createViaRepository(
        string $sku,
        ?string $family,
        array $attributes,
        string $locale,
        string $channel,
        ImageProductContext $ctx,
    ): int|string {
        /** @var \Webkul\Product\Repositories\ProductRepository $repo */
        $repo = app('Webkul\Product\Repositories\ProductRepository');

        $familyId = $this->resolveFamily($family);

        // 1 — Create the bare product
        $product = $repo->create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'attribute_family_id' => $familyId,
        ]);

        // 2 — Build the values structure that Unopim expects
        $urlKey = Str::slug($attributes['name'] ?? $sku);

        $commonValues = [
            'sku'     => $sku,
            'url_key' => $urlKey,
        ];

        $channelLocaleValues = [];

        foreach ($attributes as $code => $value) {
            if (in_array($code, self::CHANNEL_LOCALE_ATTRS, true)) {
                $channelLocaleValues[$code] = $value;
            }
        }

        $values = $product->values ?? [];
        $values['common'] = array_merge($values['common'] ?? [], $commonValues);

        if (! empty($channelLocaleValues)) {
            $values['channel_locale_specific'][$channel][$locale] = array_merge(
                $values['channel_locale_specific'][$channel][$locale] ?? [],
                $channelLocaleValues,
            );
        }

        // 2b — Attach the uploaded image to the product
        $imagePath = $this->storeProductImage($product->id, $ctx->imagePath);

        if ($imagePath) {
            $values['common']['image'] = $imagePath;
        }

        $product->values = $values;
        $product->save();

        // 3 — Log an execution record
        $this->logExecution($product->id, $ctx, $sku, $locale, $channel);

        return $product->id;
    }

    /**
     * Log the AI execution to ai_agent_executions.
     */
    protected function logExecution(
        int|string $productId,
        ImageProductContext $ctx,
        string $sku,
        string $locale,
        string $channel,
    ): void {
        DB::table('ai_agent_executions')->insert([
            'agentId'         => null,
            'credentialId'    => null,
            'status'         => $ctx->requiresReview() ? 'pending_review' : 'completed',
            'instruction'    => json_encode(['sku' => $sku, 'image' => $ctx->imagePath]),
            'output'         => json_encode($ctx->resolvedAttributes()),
            'tokensUsed'     => 0,
            'executionTimeMs' => 0,
            'error'          => null,
            'extras'         => json_encode([
                'product_id'        => $productId,
                'source'            => 'ai_image_pipeline',
                'locale'            => $locale,
                'channel'           => $channel,
                'detected_product'  => $ctx->detectedProduct,
                'category'          => $ctx->category,
                'confidence'        => $ctx->confidence,
                'enrichment'        => $ctx->enrichment,
                'requires_review'   => $ctx->requiresReview(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Store the uploaded image into UnoPim's product storage path.
     *
     * Mimics AbstractType::processValues() — stores at
     * product/{productId}/image/{hashedFolder}/filename
     *
     * @return string|null  The relative storage path, or null on failure
     */
    protected function storeProductImage(int|string $productId, ?string $imagePath): ?string
    {
        if (! $imagePath || ! file_exists($imagePath)) {
            return null;
        }

        try {
            $fileStorer = app(FileStorer::class);
            $storagePath = 'product' . DIRECTORY_SEPARATOR . $productId . DIRECTORY_SEPARATOR . 'image';

            return $fileStorer->store(
                $storagePath,
                new File($imagePath),
                [FileStorer::HASHED_FOLDER_NAME_KEY => true],
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Generate a SKU from the context.
     */
    protected function generateSku(ImageProductContext $ctx): string
    {
        $base = $ctx->detectedProduct ?? $ctx->attributes['product_type'] ?? 'product';
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $base) ?? 'product');
        $slug = trim($slug, '-');

        return substr($slug, 0, 40) . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }

    /**
     * Resolve attribute family ID from a code or use default.
     */
    protected function resolveFamily(?string $family): int
    {
        if ($family && app()->bound('Webkul\Attribute\Repositories\AttributeFamilyRepository')) {
            $repo = app('Webkul\Attribute\Repositories\AttributeFamilyRepository');
            $model = $repo->findOneByField('code', $family);

            if ($model) {
                return $model->id;
            }
        }

        // Fallback: use the first attribute family
        return DB::table('attribute_families')->value('id') ?? 1;
    }
}
