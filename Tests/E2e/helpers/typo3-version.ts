import { Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

export type Typo3Version = 12 | 13 | 14;

const versionFile = path.join(__dirname, '../.auth/typo3-version.json');
let cachedVersion: Typo3Version | null = null;

export async function detectTypo3Version(page: Page): Promise<Typo3Version> {
  const versionElement = page.locator('.topbar-header-site-version, .topbar-site-version').first();
  const versionText = await versionElement.textContent();

  if (!versionText) {
    throw new Error('Cannot find TYPO3 version element in topbar');
  }

  const match = versionText.match(/(\d+)\./);
  if (!match) {
    throw new Error(`Cannot parse TYPO3 version from: "${versionText}"`);
  }

  const major = parseInt(match[1], 10);
  if (major < 12 || major > 14) {
    throw new Error(`Unsupported TYPO3 version: ${major}`);
  }

  const version = major as Typo3Version;
  fs.mkdirSync(path.dirname(versionFile), { recursive: true });
  fs.writeFileSync(versionFile, JSON.stringify({ version }));
  cachedVersion = version;
  return version;
}

export function getTypo3Version(): Typo3Version {
  if (cachedVersion) {
    return cachedVersion;
  }

  if (!fs.existsSync(versionFile)) {
    throw new Error('TYPO3 version not detected. Run setup first.');
  }

  const data = JSON.parse(fs.readFileSync(versionFile, 'utf-8'));
  cachedVersion = data.version as Typo3Version;
  return cachedVersion;
}
