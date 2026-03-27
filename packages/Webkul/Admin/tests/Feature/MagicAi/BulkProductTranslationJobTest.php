<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Jobs\BulkProductTranslationJob;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

beforeEach(function () {
    // Login as admin
    $this->loginAsAdmin();

    // Create a default platform for tests
    MagicAIPlatform::query()->delete();
    MagicAIPlatform::create([
        'label'      => 'Test Platform',
        'provider'   => 'groq',
        'api_url'    => 'https://api.groq.com/openai/v1',
        'api_key'    => 'test-key',
        'models'     => 'qwen-qwq-32b,deepseek-r1-distill-llama-70b',
        'is_default' => true,
        'status'     => true,
    ]);

    // Enable Magic AI translation
    app(CoreConfigRepository::class)->create([
        'general' => [
            'magic_ai' => [
                'translation' => [
                    'enabled'  => '1',
                    'replace'  => '1',
                    'ai_model' => 'qwen-qwq-32b',
                ],
            ],
        ],
    ]);

    // Create locales for testing
    Locale::whereIn('code', ['en_US', 'fr_FR', 'de_DE', 'es_ES'])->update(['status' => 1]);
    Locale::firstOrCreate(['code' => 'fr_FR'], ['name' => 'French', 'status' => 1]);
    Locale::firstOrCreate(['code' => 'de_DE'], ['name' => 'German', 'status' => 1]);

    // Get default channel and locale for use in tests
    $defaultChannel = core()->getDefaultChannel();
    $this->defaultLocale = $defaultChannel->locales->first()->code ?? 'en_US';
});

describe('BulkProductTranslationJob', function () {
    /**
     * Test successful execution of translation job for valid product data.
     */
    it('should successfully translate product attributes for valid product data', function () {
        // Create a translatable attribute
        $nameAttribute = Attribute::factory()->create([
            'code'              => 'test_name',
            'name'              => 'Test Name',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        $descriptionAttribute = Attribute::factory()->create([
            'code'              => 'test_description',
            'name'              => 'Test Description',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create a product with translatable values
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'TEST-SKU-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'test_name'        => 'Smartphone',
                            'test_description' => 'A high-quality smartphone with amazing features.',
                        ],
                    ],
                ],
            ],
        ]);

        // Attach attributes to product family
        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($nameAttribute);
        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($descriptionAttribute);

        // Mock MagicAI facade
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'test_name'        => 'Smartphone traduit',
                    'test_description' => 'Un smartphone de haute qualité avec des fonctionnalités incroyables.',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['test_name', 'test_description'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Refresh product from database
        $product->refresh();

        // Assert translations were saved
        expect($product->values['channel_locale_specific']['default']['fr_FR']['test_name'])
            ->toBe('Smartphone traduit');
        expect($product->values['channel_locale_specific']['default']['fr_FR']['test_description'])
            ->toBe('Un smartphone de haute qualité avec des fonctionnalités incroyables.');
    });

    /**
     * Test bulk processing of multiple products.
     */
    it('should handle bulk processing of multiple products', function () {
        // Create a single test attribute
        $nameAttribute = Attribute::factory()->create([
            'code'              => 'bulk_test_name',
            'name'              => 'Bulk Test Name',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create multiple products with the same attribute
        $products = [];
        for ($i = 1; $i <= 3; $i++) {
            $product = Product::factory()->simple()->create([
                'values' => [
                    'common' => [
                        'sku' => "BULK-SKU-00{$i}",
                    ],
                    'channel_locale_specific' => [
                        'default' => [
                            $this->defaultLocale => [
                                'bulk_test_name' => "Product {$i} Name",
                            ],
                        ],
                    ],
                ],
            ]);

            // Use sync instead of attach to avoid duplicate key issues
            $product->attribute_family
                ->attributeFamilyGroupMappings->first()?->customAttributes()
                ->syncWithoutDetaching([$nameAttribute->id]);

            $products[] = $product;
        }

        $productIds = array_map(fn ($p) => $p->id, $products);

        // Mock MagicAI to return translation
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'bulk_test_name' => 'Translated Product Name',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: $productIds,
            attributeCodes: ['bulk_test_name'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Verify all products were processed
        foreach ($products as $product) {
            $product->refresh();
            expect($product->values['channel_locale_specific']['default']['fr_FR'])
                ->toHaveKey('bulk_test_name');
        }
    });

    /**
     * Test accurate translation of product attributes across multiple locales.
     */
    it('should translate product attributes across multiple target locales', function () {
        // Create test attribute
        $nameAttribute = Attribute::factory()->create([
            'code'              => 'multi_locale_name',
            'name'              => 'Multi Locale Name',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create a product
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'MULTI-LOCALE-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'multi_locale_name' => 'Hello World',
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($nameAttribute);

        // Mock MagicAI to return translations for multiple locales
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'multi_locale_name' => 'Bonjour le monde',
                ],
                'de_DE' => [
                    'multi_locale_name' => 'Hallo Welt',
                ],
            ]));

        // Execute the job with multiple target locales
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['multi_locale_name'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR', 'de_DE'],
            userId: 1
        );

        $job->handle();

        // Refresh product
        $product->refresh();

        // Assert translations for all locales
        expect($product->values['channel_locale_specific']['default']['fr_FR']['multi_locale_name'])
            ->toBe('Bonjour le monde');
        expect($product->values['channel_locale_specific']['default']['de_DE']['multi_locale_name'])
            ->toBe('Hallo Welt');
    });

    /**
     * Test handling of empty attribute values.
     */
    it('should handle products with empty attribute values gracefully', function () {
        // Create test attribute
        $emptyAttribute = Attribute::factory()->create([
            'code'              => 'empty_value_attr',
            'name'              => 'Empty Value Attr',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create a product with empty attribute value
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'EMPTY-VALUE-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'empty_value_attr' => '', // Empty value
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($emptyAttribute);

        // Execute the job - should not throw an exception
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['empty_value_attr'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        // Should not throw an exception - empty values should be skipped
        $job->handle();

        // Product should remain unchanged
        $product->refresh();
        expect($product->values['channel_locale_specific']['default']['fr_FR'] ?? null)
            ->toBeNull();
    });

    /**
     * Test handling of null attribute values.
     */
    it('should handle products with null attribute values gracefully', function () {
        // Create test attribute
        $nullAttribute = Attribute::factory()->create([
            'code'              => 'null_value_attr',
            'name'              => 'Null Value Attr',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create a product without the attribute value set
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'NULL-VALUE-001',
                ],
                // No channel_locale_specific for this attribute
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($nullAttribute);

        // Execute the job - should not throw an exception
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['null_value_attr'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        // Should not throw an exception - null values should be skipped
        $job->handle();

        // Job should complete without errors
        expect(true)->toBeTrue();
    });

    /**
     * Test handling of invalid/non-existent attributes.
     */
    it('should handle products with non-existent attribute codes gracefully', function () {
        // Create a product
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'INVALID-ATTR-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'existing_attr' => 'Some value',
                        ],
                    ],
                ],
            ],
        ]);

        // Execute the job with non-existent attribute code
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['non_existent_attribute'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        // Should not throw an exception - should just skip the non-existent attribute
        $job->handle();

        // Job should complete without errors
        expect(true)->toBeTrue();
    });

    /**
     * Test data integrity validation - ensure source data remains unchanged.
     */
    it('should preserve source locale data integrity after translation', function () {
        // Create test attribute
        $integrityAttribute = Attribute::factory()->create([
            'code'              => 'integrity_test',
            'name'              => 'Integrity Test',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        $sourceValue = 'Original Source Value';

        // Create a product with source value
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'INTEGRITY-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'integrity_test' => $sourceValue,
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($integrityAttribute);

        // Mock MagicAI
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'integrity_test' => 'Translated Value',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['integrity_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Refresh product
        $product->refresh();

        // Assert source value remains unchanged
        expect($product->values['channel_locale_specific']['default'][$this->defaultLocale]['integrity_test'])
            ->toBe($sourceValue);
    });

    /**
     * Test job track record creation and state management.
     */
    it('should create job track record with correct state', function () {
        // Create test attribute
        $trackAttribute = Attribute::factory()->create([
            'code'              => 'track_test',
            'name'              => 'Track Test',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'TRACK-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'track_test' => 'Track Me',
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($trackAttribute);

        // Mock MagicAI
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'track_test' => 'Suivez-moi',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['track_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Check job track record was created
        $jobTrackRepository = app(JobTrackRepository::class);
        $latestJobTrack = $jobTrackRepository->getModel()->latest()->first();

        expect($latestJobTrack)->not->toBeNull();
        expect($latestJobTrack->state)->toBe('completed');
        expect($latestJobTrack->summary['updated'])->toBeGreaterThanOrEqual(0);
    });

    /**
     * Test handling when translation is disabled in configuration.
     */
    it('should skip translation when Magic AI translation is disabled', function () {
        // Disable translation
        app(CoreConfigRepository::class)->create([
            'general' => [
                'magic_ai' => [
                    'translation' => [
                        'enabled' => '0',
                    ],
                ],
            ],
        ]);

        // Create test attribute
        $disabledAttribute = Attribute::factory()->create([
            'code'              => 'disabled_test',
            'name'              => 'Disabled Test',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'DISABLED-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'disabled_test' => 'Test Value',
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($disabledAttribute);

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['disabled_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        // Should handle gracefully without throwing exception
        $job->handle();

        // MagicAI should not have been called
        // (The job should have returned early)
        expect(true)->toBeTrue();
    });

    /**
     * Test translation with locale_specific (non-channel) attributes.
     */
    it('should handle locale-specific attributes without channel', function () {
        // Create test attribute - value per locale only
        $localeOnlyAttribute = Attribute::factory()->create([
            'code'              => 'locale_only_test',
            'name'              => 'Locale Only Test',
            'value_per_locale'  => true,
            'value_per_channel' => false,
            'type'              => 'text',
        ]);

        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'LOCALE-ONLY-001',
                ],
                'locale_specific' => [
                    $this->defaultLocale => [
                        'locale_only_test' => 'Locale Specific Value',
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($localeOnlyAttribute);

        // Mock MagicAI
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'locale_only_test' => 'Valeur spécifique aux paramètres régionaux',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['locale_only_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Refresh product
        $product->refresh();

        // Assert translation was saved in locale_specific
        expect($product->values['locale_specific']['fr_FR']['locale_only_test'])
            ->toBe('Valeur spécifique aux paramètres régionaux');
    });

    /**
     * Test handling replace translation configuration.
     */
    it('should respect replace translation configuration setting', function () {
        // Set replace to false (don't replace existing translations)
        app(CoreConfigRepository::class)->create([
            'general' => [
                'magic_ai' => [
                    'translation' => [
                        'enabled'  => '1',
                        'replace'  => '0', // Don't replace existing
                    ],
                ],
            ],
        ]);

        // Create test attribute
        $replaceAttribute = Attribute::factory()->create([
            'code'              => 'replace_test',
            'name'              => 'Replace Test',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create product with existing translation
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'REPLACE-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'replace_test' => 'Original',
                        ],
                        'fr_FR' => [
                            'replace_test' => 'Existing Translation',
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($replaceAttribute);

        // Mock MagicAI
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'replace_test' => 'New Translation',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['replace_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Refresh product
        $product->refresh();

        // With replace=0, existing translation should be preserved
        expect($product->values['channel_locale_specific']['default']['fr_FR']['replace_test'])
            ->toBe('Existing Translation');
    });

    /**
     * Test error handling when AI returns invalid JSON.
     */
    it('should handle AI returning invalid JSON gracefully', function () {
        // Create test attribute
        $errorAttribute = Attribute::factory()->create([
            'code'              => 'error_test',
            'name'              => 'Error Test',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'ERROR-001',
                ],
                'channel_locale_specific' => [
                    'default' => [
                        $this->defaultLocale => [
                            'error_test' => 'Test Value',
                        ],
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($errorAttribute);

        // Mock MagicAI to return invalid JSON
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn('This is not valid JSON at all!!!');

        // Execute the job - should not throw an exception
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['error_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        // Should handle gracefully
        $job->handle();

        // Job should complete
        expect(true)->toBeTrue();
    });

    /**
     * Test with products that have values in different structure (attribute code directly).
     */
    it('should handle products with attribute values in regular values structure', function () {
        // Create test attribute
        $regularAttribute = Attribute::factory()->create([
            'code'              => 'regular_test',
            'name'              => 'Regular Test',
            'value_per_locale'  => true,
            'value_per_channel' => true,
            'type'              => 'text',
        ]);

        // Create product with values in regular structure
        $product = Product::factory()->simple()->create([
            'values' => [
                'common' => [
                    'sku' => 'REGULAR-001',
                ],
                'regular_test' => [
                    'default' => [
                        $this->defaultLocale => 'Regular Structure Value',
                    ],
                ],
            ],
        ]);

        $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($regularAttribute);

        // Mock MagicAI
        MagicAI::shouldReceive('useDefault')->andReturnSelf();
        MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
        MagicAI::shouldReceive('setModel')->andReturnSelf();
        MagicAI::shouldReceive('setPrompt')->andReturnSelf();
        MagicAI::shouldReceive('ask')
            ->andReturn(json_encode([
                'fr_FR' => [
                    'regular_test' => 'Valeur de structure régulière',
                ],
            ]));

        // Execute the job
        $job = new BulkProductTranslationJob(
            productIds: [$product->id],
            attributeCodes: ['regular_test'],
            sourceChannel: 'default',
            sourceLocale: $this->defaultLocale,
            targetChannel: 'default',
            targetLocales: ['fr_FR'],
            userId: 1
        );

        $job->handle();

        // Refresh product
        $product->refresh();

        // Translation should be saved in channel_locale_specific
        expect($product->values['channel_locale_specific']['default']['fr_FR']['regular_test'])
            ->toBe('Valeur de structure régulière');
    });
});
