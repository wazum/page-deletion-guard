import { expect, Page, Locator } from '@playwright/test';
import { getTypo3Version } from './typo3-version';
import { getTypo3Config } from './typo3-config';

function getTreeItemByName(page: Page, name: string): Locator {
  const config = getTypo3Config(getTypo3Version());
  return page.getByRole('treeitem', { name: config.treeItemNamePattern(name) });
}

export async function openPageModule(page: Page): Promise<void> {
  await page.goto('/typo3/module/web/layout');
  await expect(page.locator('.scaffold-content-navigation-component')).toBeVisible({ timeout: 15000 });
  await expect(getTreeItemByName(page, 'Root')).toBeVisible({ timeout: 15000 });
}

export async function expandRootNode(page: Page): Promise<void> {
  const config = getTypo3Config(getTypo3Version());
  const rootItem = getTreeItemByName(page, 'Root');
  await expect(rootItem).toBeVisible({ timeout: 10000 });

  const childPage = getTreeItemByName(page, 'Page Without Children');

  if (await childPage.isVisible()) {
    return;
  }

  const chevron = rootItem.locator(config.treeChevronSelector);

  if (await chevron.count() > 0) {
    await chevron.first().click();
  } else {
    await rootItem.dblclick();
  }

  await expect(childPage).toBeVisible({ timeout: 10000 });
}

export async function rightClickPageInTree(page: Page, pageName: string): Promise<void> {
  await expandRootNode(page);

  const pageItem = getTreeItemByName(page, pageName);
  await pageItem.waitFor({ state: 'visible', timeout: 10000 });
  await pageItem.click({ button: 'right' });
}

export async function clickDeleteInContextMenu(page: Page): Promise<void> {
  const deleteItem = page.getByRole('menuitem', { name: 'Delete' });
  await deleteItem.waitFor({ state: 'visible', timeout: 5000 });
  await deleteItem.click();
}

export async function openListModule(page: Page, pageId: number = 1): Promise<void> {
  const config = getTypo3Config(getTypo3Version());
  await page.goto(config.listModuleUrl(pageId));
  await expect(page.frameLocator('iframe').getByRole('heading', { level: 1 })).toBeVisible({ timeout: 15000 });
}

export async function clickDeleteButtonInList(page: Page, pageTitle: string): Promise<void> {
  const iframe = page.frameLocator('iframe');
  const pageLink = iframe.getByRole('link').filter({ hasText: pageTitle }).first();
  await expect(pageLink).toBeVisible({ timeout: 10000 });
  const row = iframe.getByRole('row').filter({ has: pageLink });
  const deleteButton = row.getByRole('button', { name: /Delete record/ });
  await deleteButton.click();
}
