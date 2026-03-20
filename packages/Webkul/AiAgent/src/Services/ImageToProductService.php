<?php

namespace Webkul\AiAgent\Services;

use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\ApiException;

/**
 * Small, focused agent that accepts a product image upload and:
 *
 *   1. Validates + stores the uploaded file
 *   2. Sends it to VisionService for AI analysis
 *   3. Enriches sparse attributes via a second AI call
 *   4. Creates the product in Unopim
 *
 * This is the single entry-point the controller calls.
 */
class ImageToProductService
{
    public function __construct(
        protected VisionService $visionService,
        protected EnrichmentService $enrichmentService,
        protected ProductWriterService $productWriterService,
    ) {}

    /**
     * Execute the full image → product flow.
     *
     * @param  \Illuminate\Http\UploadedFile  $image         Uploaded image file
     * @param  int                            $credentialId  AI credential to use
     * @param  array{
     *     locale?: string,
     *     channel?: string,
     *     sku?: string,
     *     family?: string,
     * }  $options  Optional overrides
     *
     * @return ImageProductContext  The fully populated context
     *
     * @throws ApiException
     * @throws \InvalidArgumentException
     */
    public function execute(
        \Illuminate\Http\UploadedFile $image,
        int $credentialId,
        array $options = [],
    ): ImageProductContext {
        // 1 — Store the uploaded image
        $storedPath = $this->storeImage($image);

        // 2 — Vision: analyze the image, get attributes + category
        $ctx = $this->visionService->analyze(
            imageContent: $this->toBase64DataUri($storedPath, $image->getMimeType()),
            credentialId: $credentialId,
            options: [
                'locale'      => $options['locale'] ?? 'en',
                'maxAttempts'  => 3,
                'temperature'  => 0.2,
            ],
        );

        // Keep the real stored path (not the data URI)
        $ctx = $ctx->withImagePath($storedPath);

        // 3 — Enrich: fill missing attributes (name, description, SEO, etc.)
        $ctx = $this->enrichmentService->enrich($ctx, $credentialId, $options);

        // 4 — Write: create the product in Unopim
        $ctx = $this->productWriterService->createProduct($ctx, $options);

        return $ctx;
    }

    /**
     * Store the uploaded image in the public disk under ai-agent/images/.
     */
    protected function storeImage(\Illuminate\Http\UploadedFile $image): string
    {
        $path = $image->store('ai-agent/images', 'public');

        return storage_path('app/public/' . $path);
    }

    /**
     * Read a local file and return a base64 data URI.
     */
    protected function toBase64DataUri(string $filePath, string $mimeType): string
    {
        $raw = file_get_contents($filePath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($raw);
    }
}
