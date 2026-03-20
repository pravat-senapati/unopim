const { test, expect } = require('../../utils/fixtures');

/**
 * Helpers
 */

/** Navigate to Job Scheduler → Scheduled Jobs list */
async function gotoJobsList(page) {
    await page.getByRole('link', { name: 'Cron Scheduler' }).click();
    await page.getByRole('link', { name: 'Scheduled Jobs' }).click();
    await page.waitForLoadState('networkidle');
}

/** Navigate to Job Scheduler → Destinations list */
async function gotoDestinations(page) {
    await page.getByRole('link', { name: 'Cron Scheduler' }).click();
    await page.getByRole('link', { name: 'Destinations' }).click();
    await page.waitForLoadState('networkidle');
}

/** Navigate to Job Scheduler → Execution History list */
async function gotoHistory(page) {
    await page.getByRole('link', { name: 'Cron Scheduler' }).click();
    await page.getByRole('link', { name: 'Execution History' }).click();
    await page.waitForLoadState('networkidle');
}

/** Navigate to Job Scheduler → Execution Logs list */
async function gotoLogs(page) {
    await page.getByRole('link', { name: 'Cron Scheduler' }).click();
    await page.getByRole('link', { name: 'Execution Logs' }).click();
    await page.waitForLoadState('networkidle');
}

/** Select option from an UnoPim multiselect by input name */
async function selectOption(page, inputName, optionText) {
    const wrapper = page.locator(`input[name="${inputName}"]`).locator('..');
    // Click the multiselect to open it
    await wrapper.locator('.multiselect__single, .multiselect__placeholder').first().click();
    await page.getByRole('option', { name: optionText }).locator('span').first().click();
}

// ─────────────────────────────────────────────────────────────────────────────
// Navigation & DataGrid Loading
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Navigation', () => {
    test('Cron Scheduler menu item is visible in admin sidebar', async ({ adminPage }) => {
        await expect(adminPage.getByRole('link', { name: 'Cron Scheduler' })).toBeVisible();
    });

    test('All Job Scheduler sub-menus are accessible', async ({ adminPage }) => {
        await adminPage.getByRole('link', { name: 'Cron Scheduler' }).click();
        await expect(adminPage.getByRole('link', { name: 'Scheduled Jobs' })).toBeVisible();
        await expect(adminPage.getByRole('link', { name: 'Execution History' })).toBeVisible();
        await expect(adminPage.getByRole('link', { name: 'Execution Logs' })).toBeVisible();
        await expect(adminPage.getByRole('link', { name: 'Destinations' })).toBeVisible();
    });

    test('Scheduled Jobs DataGrid loads with correct page title', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await expect(adminPage.getByText('Scheduled Jobs', { exact: true }).first()).toBeVisible();
    });

    test('Execution History DataGrid loads', async ({ adminPage }) => {
        await gotoHistory(adminPage);
        await expect(adminPage.getByText('Execution History', { exact: true }).first()).toBeVisible();
    });

    test('Execution Logs DataGrid loads', async ({ adminPage }) => {
        await gotoLogs(adminPage);
        await expect(adminPage.getByText('Execution Logs', { exact: true }).first()).toBeVisible();
    });

    test('Destinations DataGrid loads', async ({ adminPage }) => {
        await gotoDestinations(adminPage);
        await expect(adminPage.getByText('Destinations', { exact: true }).first()).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Jobs – Create: Validation
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Create Scheduled Job (validation)', () => {
    test('shows required errors when form is submitted empty', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');
        await adminPage.getByRole('button', { name: 'Save Job' }).click();
        await expect(adminPage.getByText('The Name field is required')).toBeVisible();
        await expect(adminPage.getByText('The Code field is required')).toBeVisible();
    });

    test('shows required error when only Name is filled', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');
        await adminPage.getByRole('textbox', { name: 'Name' }).fill('Test Job');
        await adminPage.getByRole('button', { name: 'Save Job' }).click();
        await expect(adminPage.getByText('The Code field is required')).toBeVisible();
    });

    test('shows duplicate code error when code already exists', async ({ adminPage }) => {
        // Create a job first via the form
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        await adminPage.getByRole('textbox', { name: 'Name' }).fill('Duplicate Code Test Job');
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('duplicate_code_test');

        // Select Type: Export
        await selectOption(adminPage, 'type', 'Export');

        // Select Entity Type: Products
        await selectOption(adminPage, 'entityType', 'Products');

        // Cron Expression
        await adminPage.getByRole('textbox', { name: 'Cron Expression' }).fill('0 0 * * *');

        // Timezone
        await adminPage.getByRole('textbox', { name: 'Timezone' }).fill('UTC');

        // File Format: CSV
        await selectOption(adminPage, 'fileFormat', 'CSV');

        await adminPage.getByRole('button', { name: 'Save Job' }).click();
        await expect(adminPage.getByText(/created successfully/i)).toBeVisible();

        // Try to create again with same code
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        await adminPage.getByRole('textbox', { name: 'Name' }).fill('Duplicate Code Test Job 2');
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('duplicate_code_test');
        await selectOption(adminPage, 'type', 'Export');
        await selectOption(adminPage, 'entityType', 'Products');
        await adminPage.getByRole('textbox', { name: 'Cron Expression' }).fill('0 0 * * *');
        await adminPage.getByRole('textbox', { name: 'Timezone' }).fill('UTC');
        await selectOption(adminPage, 'fileFormat', 'CSV');
        await adminPage.getByRole('button', { name: 'Save Job' }).click();

        await expect(adminPage.getByText(/has already been taken/i)).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Jobs – Create: Entity Type & Job Instance dropdowns
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Entity Type & Job Instance Dropdowns', () => {
    test('Entity Type dropdown shows Products and Categories options', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        // Open the Entity Type multiselect
        const entityWrapper = adminPage.locator('input[name="entityType"]').locator('..');
        await entityWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();

        await expect(adminPage.getByRole('option', { name: 'Products' })).toBeVisible();
        await expect(adminPage.getByRole('option', { name: 'Categories' })).toBeVisible();
    });

    test('Job Instance dropdown filters by type=export and entityType=products', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        // Select Type: Export
        const typeWrapper = adminPage.locator('input[name="type"]').locator('..');
        await typeWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();
        await adminPage.getByRole('option', { name: 'Export' }).locator('span').first().click();

        // Select Entity Type: Products
        const entityWrapper = adminPage.locator('input[name="entityType"]').locator('..');
        await entityWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();
        await adminPage.getByRole('option', { name: 'Products' }).locator('span').first().click();

        // Job Instance dropdown should only show product export instances
        const instanceWrapper = adminPage.locator('input[name="jobInstanceId"]').locator('..');
        await instanceWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();

        // product_export should be visible
        await expect(adminPage.getByRole('option', { name: /product_export.*export.*products/i })).toBeVisible();
        // categories_import should NOT be visible
        await expect(adminPage.getByRole('option', { name: /categories_import/i })).not.toBeVisible();
    });

    test('Job Instance dropdown filters by type=import and entityType=categories', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        // Select Type: Import
        const typeWrapper = adminPage.locator('input[name="type"]').locator('..');
        await typeWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();
        await adminPage.getByRole('option', { name: 'Import' }).locator('span').first().click();

        // Select Entity Type: Categories
        const entityWrapper = adminPage.locator('input[name="entityType"]').locator('..');
        await entityWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();
        await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();

        // Job Instance dropdown
        const instanceWrapper = adminPage.locator('input[name="jobInstanceId"]').locator('..');
        await instanceWrapper.locator('.multiselect__placeholder, .multiselect__single').first().click();

        // categories_import should be visible
        await expect(adminPage.getByRole('option', { name: /categories_import.*import.*categories/i })).toBeVisible();
        // product_export (export type) should NOT be visible
        await expect(adminPage.getByRole('option', { name: /product_export/i })).not.toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Jobs – Create Success
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Create Scheduled Job (success)', () => {
    test('Creates an export job successfully and redirects to edit', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        await adminPage.getByRole('textbox', { name: 'Name' }).fill('Nightly Product Export');
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('nightly_product_export_e2e');
        await selectOption(adminPage, 'type', 'Export');
        await selectOption(adminPage, 'entityType', 'Products');
        await adminPage.getByRole('textbox', { name: 'Cron Expression' }).fill('0 2 * * *');
        await adminPage.getByRole('textbox', { name: 'Timezone' }).fill('UTC');
        await selectOption(adminPage, 'fileFormat', 'CSV');

        await adminPage.getByRole('button', { name: 'Save Job' }).click();

        await expect(adminPage.getByText('Scheduled job created successfully.')).toBeVisible();
    });

    test('Creates an import job successfully', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('link', { name: 'Create Scheduled Job' }).click();
        await adminPage.waitForLoadState('networkidle');

        await adminPage.getByRole('textbox', { name: 'Name' }).fill('Weekly Category Import');
        await adminPage.getByRole('textbox', { name: 'Code' }).fill('weekly_category_import_e2e');
        await selectOption(adminPage, 'type', 'Import');
        await selectOption(adminPage, 'entityType', 'Categories');
        await adminPage.getByRole('textbox', { name: 'Cron Expression' }).fill('0 3 * * 1');
        await adminPage.getByRole('textbox', { name: 'Timezone' }).fill('UTC');
        await selectOption(adminPage, 'fileFormat', 'CSV');

        await adminPage.getByRole('button', { name: 'Save Job' }).click();

        await expect(adminPage.getByText('Scheduled job created successfully.')).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Jobs – DataGrid features
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Scheduled Jobs DataGrid', () => {
    test('DataGrid search filters results', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('Nightly Product Export');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');
        await expect(adminPage.getByText('Nightly Product Export')).toBeVisible();
    });

    test('DataGrid filter panel opens', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByText('Filter', { exact: true }).click();
        await expect(adminPage.getByText('Apply Filters')).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Jobs – Edit
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Edit Scheduled Job', () => {
    test('Opens edit form and updates job name successfully', async ({ adminPage }) => {
        await gotoJobsList(adminPage);

        // Find and click the edit link for the e2e product export job
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('nightly_product_export_e2e');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        // Click edit action (pencil icon link in datagrid)
        await adminPage.locator('a[href*="job_scheduler/jobs"][href*="edit"]').first().click();
        await adminPage.waitForLoadState('networkidle');

        // Change the name
        await adminPage.getByRole('textbox', { name: 'Name' }).fill('Nightly Product Export (Updated)');
        await adminPage.getByRole('button', { name: 'Save Job' }).click();

        await expect(adminPage.getByText('Scheduled job updated successfully.')).toBeVisible();
    });

    test('Edit form pre-populates Entity Type select with saved value', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('nightly_product_export_e2e');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        await adminPage.locator('a[href*="job_scheduler/jobs"][href*="edit"]').first().click();
        await adminPage.waitForLoadState('networkidle');

        // Entity type select should show 'Products' as the selected value
        const entityWrapper = adminPage.locator('input[name="entityType"]').locator('..');
        await expect(entityWrapper.locator('.multiselect__single')).toContainText('Products');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Scheduled Jobs – Delete
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Delete Scheduled Job', () => {
    test('Deletes a job from the edit page', async ({ adminPage }) => {
        await gotoJobsList(adminPage);
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('weekly_category_import_e2e');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        await adminPage.locator('a[href*="job_scheduler/jobs"][href*="edit"]').first().click();
        await adminPage.waitForLoadState('networkidle');

        // Click Delete button (opens confirmation modal)
        await adminPage.getByRole('button', { name: 'Delete' }).click();
        // Confirm the modal
        await adminPage.getByRole('button', { name: 'Agree' }).click();

        await expect(adminPage.getByText('Scheduled job deleted successfully.')).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Destinations – CRUD
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Job Scheduler – Destinations', () => {
    test('Opens Create Destination form', async ({ adminPage }) => {
        await gotoDestinations(adminPage);
        await adminPage.getByRole('link', { name: 'Create Destination' }).click();
        await adminPage.waitForLoadState('networkidle');
        await expect(adminPage.getByText('Create Destination', { exact: true }).first()).toBeVisible();
    });

    test('Shows required error when Destination label is empty', async ({ adminPage }) => {
        await gotoDestinations(adminPage);
        await adminPage.getByRole('link', { name: 'Create Destination' }).click();
        await adminPage.waitForLoadState('networkidle');
        await adminPage.getByRole('button', { name: 'Save Destination' }).click();
        await expect(adminPage.getByText('The Label field is required')).toBeVisible();
    });

    test('Creates a local destination successfully', async ({ adminPage }) => {
        await gotoDestinations(adminPage);
        await adminPage.getByRole('link', { name: 'Create Destination' }).click();
        await adminPage.waitForLoadState('networkidle');

        await adminPage.getByRole('textbox', { name: 'Label' }).fill('E2E Local Destination');
        // Type defaults to 'local', no further fields needed
        await adminPage.getByRole('button', { name: 'Save Destination' }).click();

        await expect(adminPage.getByText('Destination created successfully.')).toBeVisible();
    });

    test('DataGrid search finds created destination', async ({ adminPage }) => {
        await gotoDestinations(adminPage);
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('E2E Local Destination');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');
        await expect(adminPage.getByText('E2E Local Destination')).toBeVisible();
    });

    test('Deletes the destination', async ({ adminPage }) => {
        await gotoDestinations(adminPage);
        await adminPage.getByRole('textbox', { name: 'Search' }).fill('E2E Local Destination');
        await adminPage.keyboard.press('Enter');
        await adminPage.waitForLoadState('networkidle');

        await adminPage.locator('a[href*="destinations"][href*="edit"]').first().click();
        await adminPage.waitForLoadState('networkidle');

        await adminPage.getByRole('button', { name: 'Delete' }).click();
        await adminPage.getByRole('button', { name: 'Agree' }).click();

        await expect(adminPage.getByText('Destination deleted successfully.')).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Artisan command smoke-test (install command)
// ─────────────────────────────────────────────────────────────────────────────
// NOTE: Playwright cannot run artisan directly. The install command is covered
// by the PHP unit tests. Navigation tests above confirm the module is live.
