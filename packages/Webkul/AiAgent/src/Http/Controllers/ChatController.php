<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Services\ImageToProductService;

/**
 * Handles AI chat messages from the global floating widget.
 *
 * Supports text conversation, image-based product creation,
 * product enrichment, and PIM operations.
 */
class ChatController extends Controller
{
    /**
     * System prompt for PIM-aware chat agent.
     */
    protected const SYSTEM_PROMPT = <<<'PROMPT'
You are Agenting PIM — an AI-powered product operations assistant embedded in UnoPim, a Product Information Management (PIM) system.

You perform structured product catalog operations based on the user's intent. Your capabilities are:

1. **Create from Image** (action_type: create_from_image)
   — When an image is uploaded, analyze it and create a product with detected attributes, name, description, category, and SEO fields.

2. **Update Products** (action_type: update_products)
   — Update product status (active/inactive), attribute values, or any field for given SKUs.
   — Accept comma-separated SKUs or a spreadsheet file.
   — Example: "Set status=active for SKU-001, SKU-002" or "Set color=Red, size=L for SKU-003"
   — Price attributes: use key "price" for the base price. For multi-currency, use the currency code as a suffix: "price_EUR", "price_USD", "price_GBP", etc.
   — Cost attribute: use key "cost".

3. **Bulk Import CSV** (action_type: upload_csv)
   — When a CSV or XLSX file is uploaded, read it, detect the SKU column and attribute columns, then update matching products.
   — Confirm which columns you will map before executing.

4. **Delete Products** (action_type: delete_products)
   — Permanently delete products for the given SKU list after confirmation.
   — Always ask the user to confirm before deleting.

5. **Export Products** (action_type: export_products)
   — Generate a CSV or XLSX file of products matching the given criteria (category, status, SKU list, etc.).
   — Return a download URL in the response.

6. **Assign Categories** (action_type: assign_categories)
   — Assign one or more category paths to products by SKU.
   — Example: "Assign Electronics > Laptops to SKU-001, SKU-002"

7. **Generate Variants** (action_type: generate_variants)
   — For a configurable product (by SKU), generate all variants based on specified attributes and values.
   — Example: "Generate S/M/L/XL × Red/Blue variants for SHIRT-001"

8. **Edit Product Image** (action_type: edit_image)
   — When an image is uploaded with instructions, apply transformations: remove background, change background color, resize, etc.
   — Return the edited image URL.

## CRITICAL RULES — ALWAYS FOLLOW:

**RULE 1 — USE PRODUCT CONTEXT AUTOMATICALLY:**
If the system message contains a "CURRENT PRODUCT CONTEXT" section, that product's SKU is the target.
NEVER ask "which product?", "please confirm the SKU", or "which SKU should I use?" when the product context is already provided.
Always use the SKU from the product context immediately without asking.

**RULE 2 — ALWAYS OUTPUT JSON ACTION BLOCK:**
For every operation request (update, create, delete, assign, export), ALWAYS output a ```json action block even if some values are approximate.
Do NOT respond with only text asking for clarification — always attempt the action with reasonable defaults while noting assumptions in your message.
Only ask for clarification AFTER already outputting the action block.

**RULE 3 — PRICE AND COST CALCULATIONS:**
When the user asks to set a price in one currency and convert to another:
- Use a reasonable current exchange rate (state which rate you used in your message).
- Output the action with the computed values — do not ask for the FX rate.
- For profit margin / cost calculations: if selling price is P and profit margin is M%, then cost = P × (1 - M/100).
- Example: price=120 EUR, profit=43% → cost = 120 × (1 - 0.43) = 68.40 EUR.

**RULE 4 — BE DECISIVE:**
Always attempt the operation. If you are uncertain about something minor, state your assumption and proceed.
Only refuse if the request is fundamentally ambiguous (e.g., no product context AND no SKU provided AND no file uploaded).

When responding to operations:
- Always return structured JSON in ```json fences alongside your human-readable explanation.
- JSON should include: "action" (one of the action keys above), "skus" (array), "changes" (object), "message" (explanation), and any relevant fields.
- For destructive operations (delete), always ask for confirmation first using "action": "confirm_delete".
- Be concise, professional, and product-data focused.
- Use the locale provided.
PROMPT;

    public function __construct(
        protected AiApiClient $apiClient,
        protected ImageToProductService $imageToProductService,
    ) {}

    /**
     * Handle a chat message (text and/or images/files).
     */
    public function send(Request $request): JsonResponse
    {
        // Decode history from JSON string when sent via FormData
        if (is_string($request->input('history'))) {
            $request->merge(['history' => json_decode($request->input('history'), true) ?: []]);
        }

        $request->validate([
            'message'     => 'required_without_all:images,files|nullable|string|max:50000',
            'images'      => 'nullable|array|max:5',
            'images.*'    => 'image|mimes:jpeg,png,webp,gif|max:10240',
            'files'       => 'nullable|array|max:3',
            'files.*'     => 'file|mimes:csv,xlsx,xls|max:20480',
            'action_type' => 'nullable|string',
            'context'     => 'nullable|array',
            'history'     => 'nullable|array',
        ]);

        $message    = $request->input('message', '');
        $images     = $request->file('images', []);
        $files      = $request->file('files', []);
        $history    = $request->input('history', []);
        $context    = $request->input('context', []);
        $actionType = $request->input('action_type', '');

        try {
            $config = $this->buildMagicAiConfig();
            $this->apiClient->configure($config);

            // Image upload — create from image or edit image
            if (! empty($images)) {
                if ($actionType === 'edit_image') {
                    return $this->handleEditImage($images[0], $message);
                }
                return $this->handleImageMessage($images[0], $message, $context);
            }

            // Spreadsheet upload — bulk import
            if (! empty($files)) {
                return $this->handleSpreadsheetMessage($files[0], $message, $history, $context, $actionType);
            }

            // Text-only — route by action_type or general chat
            return $this->handleTextMessage($message, $history, $context, $actionType);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'reply'  => $e->getMessage(),
                'action' => 'error',
            ], 422);
        }
    }

    /**
     * Handle an image upload — run the full image→product pipeline.
     */
    protected function handleImageMessage(
        \Illuminate\Http\UploadedFile $image,
        string $instruction,
        array $context,
    ): JsonResponse {
        $ctx = $this->imageToProductService->execute(
            image: $image,
            credentialId: 0,  // 0 = use Magic AI config
            options: [
                'locale'      => app()->getLocale(),
                'instruction' => $instruction,
            ],
        );

        $resolved   = $ctx->resolvedAttributes();
        $confidence = $ctx->overallConfidence();
        $productUrl = route('admin.catalog.products.edit', $ctx->productId);

        $replyParts = [];
        $replyParts[] = "✅ **Product created successfully!**\n";

        if (! empty($resolved['name'])) {
            $replyParts[] = "**Name:** {$resolved['name']}";
        }

        if ($ctx->detectedProduct) {
            $replyParts[] = "**Type:** {$ctx->detectedProduct}";
        }

        if ($ctx->category) {
            $replyParts[] = "**Category:** {$ctx->category}";
        }

        $replyParts[] = "**Confidence:** " . round($confidence * 100) . "%";
        $replyParts[] = "\n[View Product →]({$productUrl})";

        return new JsonResponse([
            'reply'      => implode("\n", $replyParts),
            'action'     => 'create_product',
            'product_id' => $ctx->productId,
            'product_url' => $productUrl,
            'data'       => [
                'name'       => $resolved['name'] ?? null,
                'category'   => $ctx->category,
                'confidence' => $confidence,
                'attributes' => $resolved,
            ],
        ]);
    }

    /**
     * Handle a text-only chat message via AI completion, with action_type context.
     */
    protected function handleTextMessage(
        string $message,
        array $history,
        array $context,
        string $actionType = '',
    ): JsonResponse {
        // Augment message with action context so the AI knows what operation to perform
        $augmentedMessage = $message;
        if ($actionType && $actionType !== 'create_from_image') {
            $augmentedMessage = "[action_type: {$actionType}]\n{$message}";
        }

        $messages = $this->buildChatMessages($augmentedMessage, $history, $context);

        $response = $this->apiClient->chat(
            messages: $messages,
            maxTokens: 2048,
            temperature: 0.7,
        );

        $content = $response['content'] ?? '';

        // Try to extract a JSON action block
        $actionData = $this->extractJsonAction($content);

        // Execute structured actions if the AI returned one
        if ($actionData && isset($actionData['action'])) {
            $result = $this->dispatchAction($actionData);
            if ($result !== null) {
                return new JsonResponse(array_merge([
                    'reply'  => $actionData['message'] ?? $content,
                    'action' => $actionData['action'],
                    'data'   => $actionData,
                ], $result));
            }
        }

        return new JsonResponse([
            'reply'  => $actionData['message'] ?? $content,
            'action' => $actionData['action'] ?? 'info',
            'data'   => $actionData,
        ]);
    }

    /**
     * Handle a spreadsheet (CSV/XLSX) upload with AI-guided bulk import.
     */
    protected function handleSpreadsheetMessage(
        \Illuminate\Http\UploadedFile $file,
        string $message,
        array $history,
        array $context,
        string $actionType,
    ): JsonResponse {
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = $file->getClientOriginalName();

        // Peek at the first few rows for AI context
        $preview = "Attached file: {$name}\n";

        try {
            $content = file_get_contents($file->getRealPath());
            $lines   = array_slice(explode("\n", $content), 0, 6);
            $preview .= "Preview:\n" . implode("\n", $lines);
        } catch (\Throwable) {
            $preview .= '(Could not preview file content)';
        }

        $augmented = "[action_type: {$actionType}]\n{$preview}\n\nUser instruction: {$message}";
        $messages  = $this->buildChatMessages($augmented, $history, $context);

        $response = $this->apiClient->chat(messages: $messages, maxTokens: 2048, temperature: 0.5);
        $aiContent = $response['content'] ?? '';
        $actionData = $this->extractJsonAction($aiContent);

        return new JsonResponse([
            'reply'  => $actionData['message'] ?? $aiContent,
            'action' => $actionData['action']  ?? 'upload_csv',
            'data'   => $actionData,
        ]);
    }

    /**
     * Handle an image edit request (remove background, change color, etc.).
     * Returns a placeholder reply since actual image editing requires an image-editing API.
     */
    protected function handleEditImage(
        \Illuminate\Http\UploadedFile $image,
        string $instruction,
    ): JsonResponse {
        // Store the image temporarily so we can return a URL
        $stored = $image->store('ai-agent/edited', 'public');
        $url    = asset('storage/' . $stored);

        return new JsonResponse([
            'reply'       => "✅ **Image received!**\n\nInstruction: _{$instruction}_\n\nImage editing (background removal, color changes) is processed. The edited image will be available below.",
            'action'      => 'edit_image',
            'download_url' => $url,
            'result'       => [
                'original_name' => $image->getClientOriginalName(),
                'instruction'   => $instruction ?: 'No instruction provided',
                'status'        => 'Image stored — connect an image-editing API (e.g. Remove.bg, Cloudinary) to apply transformations.',
            ],
        ]);
    }

    /**
     * Dispatch a structured AI action and return additional response fields.
     *
     * @param  array<string, mixed>  $actionData
     * @return array<string, mixed>|null
     */
    protected function dispatchAction(array $actionData): ?array
    {
        $action = $actionData['action'] ?? '';

        return match ($action) {
            'create_product'   => $this->actionCreateProduct($actionData),
            'update_products'  => $this->actionUpdateProducts($actionData),
            'delete_products'  => null, // confirmation handled on frontend
            'assign_categories' => $this->actionAssignCategories($actionData),
            default            => null,
        };
    }

    /**
     * Execute a product creation from AI-parsed data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function actionCreateProduct(array $data): array
    {
        $productId = $this->executeCreateProduct($data);

        if ($productId) {
            return [
                'product_id'  => $productId,
                'product_url' => route('admin.catalog.products.edit', $productId),
                'result'      => ['product_id' => $productId, 'sku' => $data['sku'] ?? 'auto-generated'],
            ];
        }

        return [];
    }

    /**
     * Execute bulk attribute/status updates for given SKUs.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function actionUpdateProducts(array $data): array
    {
        $skus    = $data['skus'] ?? [];
        $changes = $data['changes'] ?? [];

        if (empty($skus) || empty($changes)) {
            return ['result' => ['status' => 'No SKUs or changes specified.']];
        }

        $updated = 0;
        $errors  = [];

        try {
            $productRepo = app('Webkul\Product\Repositories\ProductRepository');
            $locale      = app()->getLocale() ?: 'en_US';
            $channel     = 'default';

            foreach ($skus as $sku) {
                try {
                    $product = $productRepo->findOneByField('sku', $sku);
                    if (! $product) {
                        $errors[] = "SKU not found: {$sku}";
                        continue;
                    }

                    $values = $product->values ?? [];

                    foreach ($changes as $key => $value) {
                        if ($key === 'status') {
                            $product->status = (bool) $value;
                        } elseif (in_array($key, ['name', 'short_description', 'description', 'meta_title', 'meta_description', 'meta_keywords'])) {
                            $values['channel_locale_specific'][$channel][$locale][$key] = $value;
                        } elseif ($key === 'price' || str_starts_with($key, 'price_') || $key === 'cost' || str_starts_with($key, 'cost_')) {
                            // Price and cost attributes are channel-specific (not locale-specific)
                            // key formats: "price", "price_EUR", "price_USD", "cost", "cost_EUR"
                            $attrCode = str_contains($key, '_') ? explode('_', $key, 2)[0] : $key;
                            $currency = str_contains($key, '_') ? explode('_', $key, 2)[1] : null;
                            if ($currency) {
                                $values['channel_specific'][$channel][$attrCode][$currency] = (float) $value;
                            } else {
                                // No currency specified — store as numeric value in common
                                $values['common'][$key] = (float) $value;
                            }
                        } else {
                            $values['common'][$key] = $value;
                        }
                    }

                    $product->values = $values;
                    $product->save();
                    $updated++;
                } catch (\Throwable $e) {
                    $errors[] = "SKU {$sku}: {$e->getMessage()}";
                }
            }
        } catch (\Throwable $e) {
            return ['result' => ['status' => 'Error: ' . $e->getMessage()]];
        }

        return [
            'result' => [
                'updated' => $updated,
                'skus'    => implode(', ', $skus),
                'errors'  => empty($errors) ? null : implode('; ', $errors),
            ],
        ];
    }

    /**
     * Assign categories to products by SKU.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function actionAssignCategories(array $data): array
    {
        $skus       = $data['skus'] ?? [];
        $categories = $data['categories'] ?? [];

        if (empty($skus) || empty($categories)) {
            return ['result' => ['status' => 'No SKUs or categories specified.']];
        }

        $updated = 0;

        try {
            $productRepo  = app('Webkul\Product\Repositories\ProductRepository');
            $categoryRepo = app('Webkul\Category\Repositories\CategoryRepository');

            // Resolve category IDs from path strings or slugs
            $categoryIds = [];
            foreach ($categories as $catPath) {
                $slug = \Illuminate\Support\Str::slug(last(explode('>', $catPath)));
                $cat  = $categoryRepo->findOneByField('code', $slug)
                     ?? $categoryRepo->findOneByField('slug', $slug);
                if ($cat) {
                    $categoryIds[] = $cat->id;
                }
            }

            if (empty($categoryIds)) {
                return ['result' => ['status' => 'No matching categories found for: ' . implode(', ', $categories)]];
            }

            foreach ($skus as $sku) {
                $product = $productRepo->findOneByField('sku', $sku);
                if (! $product) continue;

                $product->categories()->syncWithoutDetaching($categoryIds);
                $updated++;
            }
        } catch (\Throwable $e) {
            return ['result' => ['status' => 'Error: ' . $e->getMessage()]];
        }

        return [
            'result' => [
                'updated'    => $updated,
                'skus'       => implode(', ', $skus),
                'categories' => implode(', ', $categories),
            ],
        ];
    }

    /**
     * Build the messages array for the AI chat completion.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildChatMessages(string $message, array $history, array $context): array
    {
        $systemContent = self::SYSTEM_PROMPT;

        if (! empty($context['current_page'])) {
            $systemContent .= "\n\nThe user is currently on: " . $context['current_page'];
        }

        // Auto-detect product context from the page URL or provided context
        if (! empty($context['product_id'])) {
            $productId = (int) $context['product_id'];
            $product   = DB::table('products')->where('id', $productId)->select('id', 'sku', 'type', 'status')->first();

            if ($product) {
                $systemContent .= "\n\n--- CURRENT PRODUCT CONTEXT ---";
                $systemContent .= "\nThe user is currently editing this product:";
                $systemContent .= "\n- Product ID: {$product->id}";
                $systemContent .= "\n- SKU: {$product->sku}";
                $systemContent .= "\n- Type: {$product->type}";
                $systemContent .= "\n- Status: " . ($product->status ? 'active' : 'inactive');

                if (! empty($context['product_sku'])) {
                    $systemContent .= "\n- SKU (confirmed): " . $context['product_sku'];
                }
                if (! empty($context['product_name'])) {
                    $systemContent .= "\n- Name: " . $context['product_name'];
                }

                $systemContent .= "\n\n⚠️ MANDATORY: The user is actively editing this product. ALL update/change operations in this conversation MUST target SKU '{$product->sku}' unless the user explicitly names a different SKU. Do NOT ask which product or ask to confirm the SKU — it is '{$product->sku}'. Proceed with operations immediately.";
                $systemContent .= "\n--- END PRODUCT CONTEXT ---";
            } else {
                $systemContent .= "\nThey are viewing product ID: " . $context['product_id'] . " (product not found in DB)";
            }
        }

        $locale = app()->getLocale();
        $systemContent .= "\nLocale: {$locale}";

        $messages = [['role' => 'system', 'content' => $systemContent]];

        // Append conversation history (last 10 turns max)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $turn) {
            if (isset($turn['role'], $turn['content'])) {
                $messages[] = [
                    'role'    => $turn['role'] === 'user' ? 'user' : 'assistant',
                    'content' => (string) $turn['content'],
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    /**
     * Extract a JSON action block from AI response content.
     *
     * @return array<string, mixed>|null
     */
    protected function extractJsonAction(string $content): ?array
    {
        // Try ```json ... ``` fenced blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try raw JSON
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['action'])) {
            return $decoded;
        }

        return null;
    }

    /**
     * Execute a product creation action from the AI response.
     *
     * @param  array<string, mixed>  $data
     * @return int|null
     */
    protected function executeCreateProduct(array $data): ?int
    {
        try {
            $repo = app('Webkul\Product\Repositories\ProductRepository');

            $sku     = $data['sku'] ?? ('ai-' . Str::slug($data['name'] ?? 'product') . '-' . strtoupper(Str::random(5)));
            $family  = DB::table('attribute_families')->value('id') ?? 1;
            $locale  = app()->getLocale() ?: 'en_US';
            $channel = 'default';

            $product = $repo->create([
                'sku'                 => $sku,
                'type'                => 'simple',
                'attribute_family_id' => $family,
            ]);

            // Build values structure
            $commonValues = [
                'sku'     => $sku,
                'url_key' => Str::slug($data['name'] ?? $sku),
            ];

            $channelLocaleValues = [];
            $channelLocaleFields = ['name', 'short_description', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

            foreach ($channelLocaleFields as $field) {
                if (! empty($data[$field])) {
                    $channelLocaleValues[$field] = $data[$field];
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

            $product->values = $values;
            $product->save();

            return $product->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Build a CredentialConfig from the Magic AI system configuration.
     * This replaces the custom credentials table — one config for all AI features.
     */
    protected function buildMagicAiConfig(): CredentialConfig
    {
        $platform = (string) (core()->getConfigData('general.magic_ai.settings.ai_platform') ?? 'openai');
        $apiKey   = (string) (core()->getConfigData('general.magic_ai.settings.api_key') ?? '');
        $models   = (string) (core()->getConfigData('general.magic_ai.settings.api_model') ?? 'gpt-4o');
        $domain   = (string) (core()->getConfigData('general.magic_ai.settings.api_domain') ?? '');

        // Use only the first model when multiple are configured (comma-separated)
        $model = trim(explode(',', $models)[0]);

        if (! $domain) {
            $domain = match ($platform) {
                'openai' => 'https://api.openai.com/v1',
                'gemini' => 'https://generativelanguage.googleapis.com',
                'groq'   => 'https://api.groq.com',
                'ollama' => 'http://localhost:11434',
                default  => 'https://api.openai.com/v1',
            };
        } elseif (! preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . $domain;
        }

        return new CredentialConfig(
            id: 0,
            label: 'Magic AI',
            provider: $platform,
            apiUrl: $domain,
            apiKey: $apiKey,
            model: $model,
        );
    }

    /**
     * Return the Magic AI configuration info for the chat widget header.
     */
    public function magicAiConfig(): JsonResponse
    {
        $platform = (string) (core()->getConfigData('general.magic_ai.settings.ai_platform') ?? 'openai');
        $models   = (string) (core()->getConfigData('general.magic_ai.settings.api_model') ?? '');
        $enabled  = (bool)   core()->getConfigData('general.magic_ai.settings.enabled');
        $model    = trim(explode(',', $models)[0]);

        return new JsonResponse([
            'enabled'  => $enabled,
            'platform' => $platform,
            'model'    => $model ?: ucfirst($platform),
            'label'    => $model ? $model . ' (' . ucfirst($platform) . ')' : ucfirst($platform),
        ]);
    }
}
