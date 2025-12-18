import { test, expect } from '@playwright/test';
import { openListModule, clickDeleteButtonInList } from '../helpers/page-tree';

test.describe('List Module (Records) - Deletion Guard', () => {
  test.describe('Admin in list module', () => {
    test.use({ storageState: '.auth/admin.json' });

    test('admin sees warning modal when deleting page with children from list module', async ({ page }) => {
      await openListModule(page, 1);
      await clickDeleteButtonInList(page, 'Parent Page');

      const modal = page.getByRole('dialog');
      await expect(modal).toBeVisible({ timeout: 10000 });
      await expect(modal).toContainText('Warning:');
      await expect(modal).toContainText('subpages');

      await modal.getByRole('button', { name: 'Cancel' }).click();
      await expect(modal).toBeHidden({ timeout: 5000 });
    });
  });

  test.describe('Editor with bypass in list module', () => {
    test.use({ storageState: '.auth/editor-bypass.json' });

    test('editor with bypass sees warning modal when deleting page with children from list module', async ({ page }) => {
      await openListModule(page, 1);
      await clickDeleteButtonInList(page, 'Parent Page');

      const modal = page.getByRole('dialog');
      await expect(modal).toBeVisible({ timeout: 10000 });
      await expect(modal).toContainText('Warning:');
      await expect(modal).toContainText('subpages');

      const deleteButton = modal.getByRole('button', { name: /yes, delete/i });
      await expect(deleteButton).toBeVisible();

      await modal.getByRole('button', { name: 'Cancel' }).click();
      await expect(modal).toBeHidden({ timeout: 5000 });
    });
  });

  test.describe('Editor without bypass in list module', () => {
    test.use({ storageState: '.auth/editor-restricted.json' });

    test('editor without bypass sees error modal when deleting page with children from list module', async ({ page }) => {
      await openListModule(page, 1);
      await clickDeleteButtonInList(page, 'Parent Page');

      const modal = page.getByRole('dialog');
      await expect(modal).toBeVisible({ timeout: 10000 });
      await expect(modal).toContainText('Deletion Not Allowed');
      await expect(modal).toContainText('not allowed to delete pages with children');

      const okButton = modal.getByRole('button', { name: 'OK' });
      await expect(okButton).toBeVisible();

      await okButton.click();
      await expect(modal).toBeHidden({ timeout: 5000 });
    });
  });

});
