import { test, expect } from '@playwright/test';
import { openPageModule, rightClickPageInTree, clickDeleteInContextMenu, expandRootNode } from '../helpers/page-tree';

test.describe('Editor in bypass group - Allowed', () => {

  test('editor in bypass group sees warning modal and can delete page with children', async ({ page }) => {
    await openPageModule(page);
    await rightClickPageInTree(page, 'Parent Page');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });

    await expect(modal).toContainText('Parent Page');
    await expect(modal).toContainText('Warning:');
    await expect(modal).toContainText('subpages');

    const deleteButton = modal.getByRole('button', { name: /yes, delete/i });
    await expect(deleteButton).toBeVisible();

    await modal.getByRole('button', { name: 'Cancel' }).click();
    await expect(modal).toBeHidden({ timeout: 5000 });
  });

  test('editor in bypass group can actually delete page with children', async ({ page }) => {
    await openPageModule(page);

    await expandRootNode(page);
    const pageItem = page.getByRole('treeitem', { name: 'Delete Me Parent' });
    await expect(pageItem).toBeVisible({ timeout: 10000 });

    await rightClickPageInTree(page, 'Delete Me Parent');
    await clickDeleteInContextMenu(page);

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 10000 });
    await expect(modal).toContainText('Warning:');

    await modal.getByRole('button', { name: /yes, delete/i }).click();
    await expect(modal).toBeHidden({ timeout: 10000 });

    await page.reload();
    await page.getByRole('treeitem', { name: 'Root' }).waitFor({ state: 'visible', timeout: 15000 });
    await expandRootNode(page);

    await expect(page.getByRole('treeitem', { name: 'Delete Me Parent' })).toBeHidden({ timeout: 10000 });
  });

});
