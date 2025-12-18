import { test, expect } from '@playwright/test';
import { openPageModule, rightClickPageInTree, clickDeleteInContextMenu } from '../helpers/page-tree';

test.describe('Admin Bypass Setting Tests', () => {

  test.describe('Admin with bypass enabled (default)', () => {

    test('shows warning modal when deleting page with children', async ({ page }) => {
      await openPageModule(page);
      await rightClickPageInTree(page, 'Parent Page');
      await clickDeleteInContextMenu(page);

      const modal = page.getByRole('dialog');
      await expect(modal).toBeVisible({ timeout: 10000 });

      await expect(modal).toContainText('Parent Page');
      await expect(modal).toContainText('Warning:');
      await expect(modal).toContainText('2');
      await expect(modal).toContainText('subpages');

      const deleteButton = modal.getByRole('button', { name: /yes, delete/i });
      await expect(deleteButton).toBeVisible();

      await modal.getByRole('button', { name: 'Cancel' }).click();
      await expect(modal).toBeHidden({ timeout: 5000 });
    });

  });

  test.describe('Admin with bypass disabled', () => {
    test.use({
      extraHTTPHeaders: {
        'X-Playwright-Test-ID': 'admin-bypass-off'
      }
    });

    test('shows error modal when deleting page with children', async ({ page }) => {
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

});
