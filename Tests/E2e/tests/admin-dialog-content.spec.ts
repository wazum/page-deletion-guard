import { test, expect, Page } from '@playwright/test';
import { openPageModule, rightClickPageInTree, clickDeleteInContextMenu } from '../helpers/page-tree';

async function closeModal(page: Page): Promise<void> {
  const modal = page.getByRole('dialog');
  const cancelButton = modal.getByRole('button', { name: 'Cancel' });

  if (await cancelButton.isVisible()) {
    await cancelButton.click();
  }
  await expect(modal).toBeHidden({ timeout: 5000 });
}

test.describe('Admin Dialog Content Verification', () => {

  test('page without children shows standard confirmation dialog', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Page Without Children');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('Delete');
    await expect(modal).toContainText('Are you sure you want to delete');

    await expect(modal).not.toContainText('subpage');
    await expect(modal).not.toContainText('Warning:');

    await closeModal(page);
  });

  test('warning modal displays correct page title', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('Parent Page');
    await expect(modal).toContainText('Delete');

    await closeModal(page);
  });

  test('warning modal displays correct child count with plural "subpages"', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('2');
    await expect(modal).toContainText('subpages');

    const deleteButton = modal.getByRole('button', { name: /yes, delete/i });
    await expect(deleteButton).toContainText('2');
    await expect(deleteButton).toContainText('subpages');

    await closeModal(page);
  });

  test('warning modal displays singular "subpage" for single child', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Single Child Parent');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('1');
    await expect(modal).toContainText(/\bsubpage\b/);

    const deleteButton = modal.getByRole('button', { name: /yes, delete/i });
    await expect(deleteButton).toContainText('1');
    await expect(deleteButton).toContainText(/\bsubpage\b/);

    await closeModal(page);
  });

  test('warning modal contains deletion consequence message', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('delete all its subpages');
    await expect(modal).toContainText('Are you sure you want to proceed?');

    await closeModal(page);
  });

});
