export type DeleteCommand = string | { cmd?: { pages?: Record<string, { delete?: number }> } }

const PAGE_DELETE_PATTERN = /cmd\[pages\]\[(\d+)\]\[delete\]=1/g

// A single DataHandler command can target more than one page; every page it
// deletes must be confirmed, not just the first one found.
export function extractPageDeleteUids(command: DeleteCommand): string[] {
  if (typeof command === 'string') {
    return [...command.matchAll(PAGE_DELETE_PATTERN)].map((match) => match[1])
  }

  const pages = command.cmd?.pages
  if (!pages) {
    return []
  }

  return Object.keys(pages).filter((uid) => Boolean(pages[uid]?.delete))
}
