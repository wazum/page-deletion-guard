import { Typo3Version } from './typo3-version';

interface Typo3Config {
  listModuleUrl: (pageId: number) => string;
  treeItemNamePattern: (name: string) => RegExp;
  treeChevronSelector: string;
}

const configs: Record<Typo3Version, Typo3Config> = {
  12: {
    listModuleUrl: (pageId) => `/typo3/module/web/list?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^id=\\d+ - ${name}$`),
    treeChevronSelector: '.node-toggle',
  },
  13: {
    listModuleUrl: (pageId) => `/typo3/module/web/list?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^${name}$`),
    treeChevronSelector: '.icon-actions-chevron-right, .icon-actions-chevron-end',
  },
  14: {
    listModuleUrl: (pageId) => `/typo3/module/content/records?id=${pageId}`,
    treeItemNamePattern: (name) => new RegExp(`^${name}$`),
    treeChevronSelector: '.icon-actions-chevron-right, .icon-actions-chevron-end',
  },
};

export function getTypo3Config(version: Typo3Version): Typo3Config {
  return configs[version];
}
