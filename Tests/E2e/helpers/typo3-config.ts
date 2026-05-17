import { Typo3Version } from './typo3-version';
import { FrameLocator } from '@playwright/test';

interface Typo3Config {
  listModuleUrl: (pageId: number) => string;
  treeItemNamePattern: (name: string) => RegExp;
  treeChevronSelector: string;
  listDeleteButtonSelector: (iframe: FrameLocator, pageTitle: string) => ReturnType<FrameLocator['locator']>;
  navigationContainerSelector: string;
}

const v14Config: Typo3Config = {
  listModuleUrl: (pageId) => `/typo3/module/content/records?id=${pageId}`,
  treeItemNamePattern: (name) => new RegExp(`^${name}$`),
  treeChevronSelector: '.node-toggle',
  listDeleteButtonSelector: (iframe, pageTitle) =>
    iframe.locator(`button.t3js-modal-trigger[data-content*="'${pageTitle}'"]`),
  navigationContainerSelector: 'typo3-backend-content-navigation[identifier="backend"]',
};

// Drop entries when their TYPO3 LTS line falls out of support.
const legacyOverrides: Partial<Record<Typo3Version, Partial<Typo3Config>>> = {
  12: {
    listModuleUrl: (pageId) => `/typo3/module/web/list?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^id=\\d+ - ${name}$`),
    listDeleteButtonSelector: (iframe, pageTitle) =>
      iframe.locator(`button.t3js-record-delete[data-message*="'${pageTitle}'"]`),
    navigationContainerSelector: '.scaffold-content-navigation-component',
  },
  13: {
    listModuleUrl: (pageId) => `/typo3/module/web/list?id=${pageId}`,
    treeChevronSelector: '.icon-actions-chevron-right, .icon-actions-chevron-end',
    listDeleteButtonSelector: (iframe, pageTitle) =>
      iframe.locator(`button.t3js-modal-trigger[data-bs-content*="'${pageTitle}'"]`),
    navigationContainerSelector: '.scaffold-content-navigation-component',
  },
};

export function getTypo3Config(version: Typo3Version): Typo3Config {
  return { ...v14Config, ...(legacyOverrides[version] ?? {}) };
}
