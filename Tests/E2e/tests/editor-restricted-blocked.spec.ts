import { test, expect } from '@playwright/test';
import { openPageModule, rightClickPageInTree, clickDeleteInContextMenu } from '../helpers/page-tree';

test.describe('Editor NOT in bypass group - Blocked', () => {
  test.use({
    extraHTTPHeaders: {
      'X-Playwright-Test-ID': 'no-bypass-groups'
    }
  });

  test('editor without bypass group sees error modal and cannot delete page with children', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('Deletion Not Allowed');
    await expect(modal).toContainText('Error:');
    await expect(modal).toContainText('not allowed to delete pages with children');

    const okButton = modal.getByRole('button', { name: 'OK' });
    await expect(okButton).toBeVisible();

    await okButton.click();
    await expect(modal).toBeHidden({ timeout: 5000 });
  });

});
