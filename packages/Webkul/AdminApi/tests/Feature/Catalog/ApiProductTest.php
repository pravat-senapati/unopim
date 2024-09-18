<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all simple products', function () {
    $product = Product::factory()->simple()->create();
    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'sku',
                    'parent',
                    'family',
                    'type',
                    'additional',
                    'created_at',
                    'updated_at',
                    'values',
                ],
            ],
            'current_page',
            'last_page',
            'total',
            'links' => [
                'first',
                'last',
                'next',
                'prev',
            ],
        ])
        ->assertJsonFragment(['total' => Product::where('type', 'simple')->count()])
        ->json('data');

    $product = Product::where('type', 'simple')->limit(1)->first();

    $expectedProducts = [
        'sku'        => $product->sku,
        'parent'     => $product->parent,
        'family'     => $product->attribute_family->code,
        'type'       => $product->type,
        'additional' => $product->additional,
        'created_at' => $product->created_at->toISOString(),
        'updated_at' => $product->updated_at->toISOString(),
        'values'     => $product->values,
    ];

    $this->assertTrue(
        collect($response)->contains($expectedProducts),
    );
});

it('should return the simple product using the code', function () {
    $product = Product::factory()->simple()->create();
    $simpleProduct = Product::where('type', 'simple')->first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.get', ['code' => $simpleProduct->sku]))
        ->assertOK()
        ->assertJsonStructure([
            'sku',
            'parent',
            'family',
            'type',
            'created_at',
            'updated_at',
            'values',
        ])
        ->assertJsonFragment(['sku' => $simpleProduct->sku]);
});

it('should give warning if simple product sku does not exists', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.get', ['code' => 'abcxyz']))
        ->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should create the product', function () {
    $family = AttributeFamily::first();
    $sku = fake()->word();

    $product = [
        'sku'    => $sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $product)
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Product::class), ['sku' => $product['sku']]);
});

it('should give validation message when sku is not unique during create the product', function () {
    $product = Product::factory()->simple()->create([
        'sku'    => 'new_unique_sku',
        'values' => [
            'common' => ['sku' => 'new_unique_sku'],
        ],
    ]);

    $family = AttributeFamily::first();

    $product = [
        'sku'    => 'new_unique_sku',
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => 'new_unique_sku',
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $product)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors',
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseHas($this->getFullTableName(Product::class), ['sku' => $product['sku']]);
});

it('should give validation message if family does not exists during create the product', function () {
    $sku = fake()->word();

    $product = [
        'sku'    => $sku,
        'parent' => null,
        'family' => fake()->word(),
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $product)
        ->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should give validation message for all the required fields during create the product', function () {
    $product = [];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $product)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'values',
                'family',
                'values.common.sku',
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should update the product', function () {
    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);
    $attribute = Attribute::factory()->create(['value_per_locale' => false, 'value_per_channel' => false, 'type' => 'text']);
    $family->first()->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
    $category = Category::first();

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'            => $product->sku,
                $attribute->code => 'text update',
            ],
            'categories' => [
                $category->code,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals('text update', $product->values['common'][$attribute->code] ?? '');
    $this->assertEquals([$category->code], $product->values['categories'] ?? '');
});

it('should update the product associations', function () {
    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);
    $attribute = Attribute::factory()->create(['value_per_locale' => false, 'value_per_channel' => false, 'type' => 'text']);
    $family->first()->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $products = Product::factory()->simple()->createMany(2);

    $value = [$products->last()->sku];

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'associations' => [
                'related_products' => $value,
                'cross_sells'      => $value,
                'up_sells'         => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertArrayHasKey('associations', $product->values);

    foreach (['related_products', 'cross_sells', 'up_sells'] as $type) {
        $this->assertEquals($value, $product->values['associations'][$type] ?? '');
    }
});

it('should update the locale specific attribute in product', function () {
    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);
    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'value_per_channel' => false, 'type' => 'text']);
    $family->first()->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $locales = Locale::where('status', 1)->limit(2)->pluck('code')->toArray();

    $data = [];
    foreach ($locales as $locale) {
        $data[$locale] = [$attribute->code => 'Test '.$locale];
    }

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'locale_specific' => $data,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($data, $product->values['locale_specific'] ?? '');
});

it('should update the channel specific attribute in product', function () {
    $newChannel = Channel::factory()->create();

    $newChannelCode = $newChannel->code;

    $defaultChannel = core()->getDefaultChannel();

    $attribute = Attribute::factory()->create(['value_per_channel' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $product = Product::Factory()->simple()->create([
        'values' => [
            'channel_specific' => [
                'default' => [
                    $attributeCode => 'Default Channel Value',
                ],
            ],
        ],
    ]);

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'channel_specific' => [
                $newChannelCode => [
                    $attributeCode => 'New Channel Value',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();
    $product = Product::where('sku', $product->sku)->first();

    $this->assertArrayHasKey('channel_specific', $product->values);

    $this->assertEquals('New Channel Value', $product->values['channel_specific'][$newChannelCode][$attributeCode] ?? '');

    $this->assertEquals($updatedproduct['values']['channel_specific'], $product->values['channel_specific'] ?? '');
});

it('should store the channel and locale wise attribute value in product correctly', function () {
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);

    $newChannel = Channel::factory()->create();

    $newChannelLocale = $newChannel->locales->first()->code;

    $newChannelCode = $newChannel->code;

    $defaultChannel = core()->getDefaultChannel();

    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'value_per_channel' => false, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $product = Product::Factory()->simple()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $defaultChannelLocale => [
                        $attributeCode => 'Default Channel Value',
                    ],
                ],
            ],
        ],
    ]);

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'channel_locale_specific' => [
                $newChannelCode => [
                    $newChannelLocale => [
                        $attributeCode => 'New Channel Locale Value',
                    ],
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertArrayHasKey('channel_locale_specific', $product->values);

    $this->assertEquals('New Channel Locale Value', $product->values['channel_locale_specific'][$newChannelCode][$newChannelLocale][$attributeCode] ?? '');

    $this->assertEquals($updatedproduct['values']['channel_locale_specific'], $product->values['channel_locale_specific'] ?? '');
});

it('should return validation error for unique common attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['is_unique' => 1, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $value = 'Already Present Value';

    Product::factory()->create(['values' => ['common' => [$attributeCode => $value]]]);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'common.'.$attributeCode,
            ],
        ])
        ->assertJsonFragment(['success' => false]);

    $product->refresh();

    $this->assertNotEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should return validation error for unique channel and locale wise attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['is_unique' => true, 'value_per_channel' => true, 'value_per_locale' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $localeCode = core()->getDefaultChannel()->locales()->first()->code;

    $value = 'Already Present Value';

    Product::factory()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $localeCode => [
                        $attributeCode => $value,
                    ],
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'channel_locale_specific' => [
                'default' => [
                    $localeCode => [
                        $attributeCode => $value,
                    ],
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'channel_locale_specific.default.'.$localeCode.'.'.$attributeCode,
            ],
        ])
        ->assertJsonFragment(['success' => false]);

    $product->refresh();

    $this->assertNotEquals($value, $product->values['channel_locale_specific']['default'][$localeCode][$attributeCode] ?? '');
});

it('should return validation error for unique channel wise attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['is_unique' => true, 'value_per_channel' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $value = 'Already Present Value';

    Product::factory()->create([
        'values' => [
            'channel_specific' => [
                'default' => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'channel_specific' => [
                'default' => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'channel_specific.default.'.$attributeCode,
            ],
        ])
        ->assertJsonFragment(['success' => false]);

    $product->refresh();

    $this->assertNotEquals($value, $product->values['channel_specific']['default'][$attributeCode] ?? '');
});

it('should return validation error for unique locale wise attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['is_unique' => true, 'value_per_locale' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $localeCode = core()->getDefaultChannel()->locales->first()->code;

    $value = 'Already Present Value';

    Product::factory()->create([
        'values' => [
            'locale_specific' => [
                $localeCode => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ]);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'locale_specific' => [
                $localeCode => [
                    $attributeCode => $value,
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'locale_specific.'.$localeCode.'.'.$attributeCode,
            ],
        ])
        ->assertJsonFragment(['success' => false]);

    $product->refresh();

    $this->assertNotEquals($value, $product->values['locale_specific'][$localeCode][$attributeCode] ?? '');
});

/** Update cases for the simple product different attribute type values */
it('should store the price attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['type' => 'price']);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = [];

    foreach (core()->getDefaultChannel()->currencies as $currency) {
        $value[$currency->code] = (string) random_int(1, 1000);
    }

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the boolean attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['type' => 'boolean']);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = 'true';

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the select attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->first()->code;

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the multi select attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['type' => 'multiselect']);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = implode(',', $attribute->options->pluck('code')->toArray());

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the date time attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['type' => 'datetime']);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = '2024-09-04 12:00:00';

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $value,
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals($value, $product->values['common'][$attributeCode] ?? '');
});

it('should store the checkbox attribute value when updating simple product', function () {
    $attribute = Attribute::factory()->create(['type' => 'checkbox']);

    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $value = $attribute->options->pluck('code')->toArray();

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => implode(',', $value),
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertEquals(implode(',', $value), $product->values['common'][$attributeCode] ?? '');
});

it('should store the image attribute value when updating simple product', function () {
    $product = Product::factory()->simple()->create();
    $attribute = Attribute::factory()->create(['type' => 'image']);
    Storage::fake();

    $updatedCategory = [
        'sku'       => $product->sku,
        'file'      => UploadedFile::fake()->image('product.jpg'),
        'attribute' => $attribute->code,
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.media-files.product.store'), $updatedCategory);
    $response->assertStatus(200);

    if (! $response->status() === 200) {
        test()->skip('Media is not exported.');
    }

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $response->json()['data']['filePath'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});

it('should store the file attribute value when updating simple product', function () {
    $product = Product::factory()->simple()->create();
    $attribute = Attribute::factory()->create(['type' => 'file']);
    Storage::fake();

    $updatedCategory = [
        'sku'       => $product->sku,
        'file'      => UploadedFile::fake()->create('product.pdf', 100),
        'attribute' => $attribute->code,
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.media-files.product.store'), $updatedCategory);
    $response->assertStatus(200);

    if (! $response->status() === 200) {
        test()->skip('Media is not exported.');
    }

    $family = AttributeFamily::where('id', $product->attribute_family_id);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $attributeCode = $attribute->code;

    $updatedproduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku'          => $product->sku,
                $attributeCode => $response->json()['data']['filePath'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.products.update', ['code' => $updatedproduct['sku']]), $updatedproduct)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $product->refresh();

    $this->assertNotEmpty($product->values['common'][$attributeCode] ?? '');

    $this->assertTrue(Storage::exists($product->values['common'][$attributeCode]));
});