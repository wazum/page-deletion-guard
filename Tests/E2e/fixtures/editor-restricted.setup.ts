import { test as setup, expect } from '@playwright/test';
import path from 'path';
import { detectTypo3Version } from '../helpers/typo3-version';

const authFile = path.join(__dirname, '../.auth/editor-restricted.json');

setup('authenticate as restricted editor', async ({ page }) => {
  const username = process.env.TYPO3_EDITOR_RESTRICTED_USER || 'editor_restricted';
  const password = process.env.TYPO3_EDITOR_RESTRICTED_PASS || 'docker';

  await page.goto('/typo3');
  await page.locator('#t3-username').fill(username);
  await page.locator('#t3-password').fill(password);
  await page.getByRole('button', { name: 'Login' }).click();

  await expect(page.locator('.modulemenu')).toBeVisible({ timeout: 15000 });
  await detectTypo3Version(page);
  await page.context().storageState({ path: authFile });
});
