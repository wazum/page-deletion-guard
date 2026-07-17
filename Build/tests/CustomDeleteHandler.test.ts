import { describe, it, expect } from 'vitest'
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

describe('getChildLabel', () => {
  it('uses the singular label for a single child', () => {
    expect(handler.getChildLabel(1)).toBe('subpage')
  })

  it('uses the plural label for multiple children', () => {
    expect(handler.getChildLabel(2)).toBe('subpages')
  })
})
