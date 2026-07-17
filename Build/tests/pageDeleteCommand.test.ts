import { describe, it, expect } from 'vitest'
import { extractPageDeleteUids } from '../src/pageDeleteCommand.js'

describe('extractPageDeleteUids', () => {
  it('returns the uid from a string delete command', () => {
    expect(extractPageDeleteUids('cmd[pages][5][delete]=1')).toEqual(['5'])
  })

  it('returns every uid from a multi-page string command', () => {
    expect(extractPageDeleteUids('cmd[pages][1][delete]=1&cmd[pages][2][delete]=1')).toEqual(['1', '2'])
  })

  it('returns the uid from an object delete command', () => {
    expect(extractPageDeleteUids({ cmd: { pages: { 3: { delete: 1 } } } })).toEqual(['3'])
  })

  it('returns every page marked for deletion in an object command', () => {
    const command = { cmd: { pages: { 1: { delete: 1 }, 2: { delete: 1 } } } }
    expect(extractPageDeleteUids(command)).toEqual(['1', '2'])
  })

  it('ignores pages that are not marked for deletion', () => {
    const command = { cmd: { pages: { 1: { delete: 1 }, 2: {} } } }
    expect(extractPageDeleteUids(command)).toEqual(['1'])
  })

  it('returns an empty array when no pages are deleted', () => {
    expect(extractPageDeleteUids({ cmd: { pages: {} } })).toEqual([])
    expect(extractPageDeleteUids('cmd[tt_content][9][delete]=1')).toEqual([])
  })
})
