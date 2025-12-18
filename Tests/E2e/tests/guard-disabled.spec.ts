import { test, expect } from '@playwright/test';
import { openPageModule, rightClickPageInTree, clickDeleteInContextMenu } from '../helpers/page-tree';

test.describe('Guard Disabled Tests', () => {
  test.use({
    extraHTTPHeaders: {
      'X-Playwright-Test-ID': 'guard-disabled'
    }
  });

  test('restricted editor can delete page with children when guard is disabled', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('Delete');
    await expect(modal).toContainText('Are you sure you want to delete');

    await expect(modal).not.toContainText('Warning:');
    await expect(modal).not.toContainText('Error:');

    await modal.getByRole('button', { name: 'Cancel' }).click();
    await expect(modal).toBeHidden({ timeout: 5000 });
  });

});
