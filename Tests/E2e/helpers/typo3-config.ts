import { Typo3Version } from './typo3-version';
import { FrameLocator } from '@playwright/test';

interface Typo3Config {
  listModuleUrl: (pageId: number) => string;
  treeItemNamePattern: (name: string) => RegExp;
  treeChevronSelector: string;
  listDeleteButtonSelector: (iframe: FrameLocator, pageTitle: string) => ReturnType<FrameLocator['locator']>;
}

const configs: Record<Typo3Version, Typo3Config> = {
  12: {
    listModuleUrl: (pageId) => `/typo3/module/web/list?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^id=\\d+ - ${name}$`),
    treeChevronSelector: '.node-toggle',
    listDeleteButtonSelector: (iframe, pageTitle) => {
      return iframe.locator(`button.t3js-record-delete[data-message*="'${pageTitle}'"]`);
    },
  },
  13: {
    listModuleUrl: (pageId) => `/typo3/module/web/list?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^${name}$`),
    treeChevronSelector: '.icon-actions-chevron-right, .icon-actions-chevron-end',
    listDeleteButtonSelector: (iframe, pageTitle) => {
      return iframe.locator(`button.t3js-modal-trigger[data-bs-content*="'${pageTitle}'"]`);
    },
  },
  14: {
    listModuleUrl: (pageId) => `/typo3/module/content/records?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^${name}$`),
    treeChevronSelector: '.icon-actions-chevron-right, .icon-actions-chevron-end',
    listDeleteButtonSelector: (iframe, pageTitle) => {
      return iframe.locator(`button.t3js-modal-trigger[data-content*="'${pageTitle}'"]`);
    },
  },
};

export function getTypo3Config(version: Typo3Version): Typo3Config {
  return configs[version];
}
