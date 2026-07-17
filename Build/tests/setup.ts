import { beforeEach } from 'vitest'

beforeEach(() => {
  // The production code reads the TYPO3 backend globals; provide a minimal
  // shape so imports and label lookups resolve during tests.
  ;(globalThis as unknown as { TYPO3: unknown }).TYPO3 = {
    settings: { ajaxUrls: { page_deletion_guard_check_children: '/ajax/check' } },
    lang: {},
  }
})
