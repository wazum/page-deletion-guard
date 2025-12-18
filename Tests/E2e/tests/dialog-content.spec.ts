import { test, expect, Page } from '@playwright/test';
import { openPageModule, rightClickPageInTree, clickDeleteInContextMenu } from '../helpers/page-tree';

async function closeModal(page: Page): Promise<void> {
  const modal = page.getByRole('dialog');
  const okButton = modal.getByRole('button', { name: 'OK' });

  if (await okButton.isVisible()) {
    await okButton.click();
  }
  await expect(modal).toBeHidden({ timeout: 5000 });
}

test.describe('Error Dialog Content Verification', () => {
  test.use({
    extraHTTPHeaders: {
      'X-Playwright-Test-ID': 'no-bypass-groups'
    }
  });

  test('error modal displays permission denied message', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('Deletion Not Allowed');
    await expect(modal).toContainText('Error:');
    await expect(modal).toContainText('not allowed to delete pages with children');
    await expect(modal).toContainText('cannot be deleted');
    await expect(modal).toContainText('delete the child pages first');

    await expect(modal.getByRole('button', { name: 'OK' })).toBeVisible();

    await closeModal(page);
  });

  test('error modal displays correct child count', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('2');
    await expect(modal).toContainText('subpages');

    await closeModal(page);
  });

});
