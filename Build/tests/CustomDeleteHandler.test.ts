import { describe, it, expect, vi } from 'vitest'
import Modal from '@typo3/backend/modal.js'
import CustomDeleteHandler from '../src/DeleteConfirmation/CustomDeleteHandler.js'

// The handler is a singleton with private members; casting exposes them for
// focused unit tests without reaching through the AJAX round-trip.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const handler = CustomDeleteHandler as any

describe('normalizeResponse', () => {
  it('rejects a non-object payload', () => {
    expect(handler.normalizeResponse('nope')).toBeNull()
  })

  it('rejects a negative child count', () => {
    expect(handler.normalizeResponse({ childCount: -1 })).toBeNull()
  })

  it('coerces a numeric string count and truncates it', () => {
    const result = handler.normalizeResponse({ childCount: '3.9', hasChildren: true, pageTitle: 'X', isAllowed: false })
    expect(result).toEqual({ hasChildren: true, childCount: 3, pageTitle: 'X', isAllowed: false })
  })

  it('defaults a non-string title to an empty string', () => {
    const result = handler.normalizeResponse({ childCount: 1, pageTitle: 42 })
    expect(result.pageTitle).toBe('')
  })
})

describe('promptWarning', () => {
  it('keeps dollar sequences in the page title verbatim', () => {
    const advanced = vi.spyOn(Modal, 'advanced')

    handler.promptWarning({ hasChildren: true, childCount: 2, pageTitle: 'Offer $& Sale', isAllowed: true })

    const title = advanced.mock.calls[0][0].title
    expect(title).toBe('Delete page "Offer $& Sale"?')

    advanced.mockRestore()
  })
})

describe('getChildLabel', () => {
  it('uses the singular label for a single child', () => {
    expect(handler.getChildLabel(1)).toBe('subpage')
  })

  it('uses the plural label for multiple children', () => {
    expect(handler.getChildLabel(2)).toBe('subpages')
  })
})
