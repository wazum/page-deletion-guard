import AjaxDataHandler from '@typo3/backend/ajax-data-handler.js'
import ContextMenuActions from '@typo3/backend/context-menu-actions.js'
import CustomDeleteHandler from './DeleteConfirmation/CustomDeleteHandler.js'

type DeleteCommand = string | { cmd?: { pages?: Record<string, { delete?: number }> } }

class DeleteInterceptor {
  private static readonly PAGE_DELETE_PATTERN = /cmd\[pages\]\[(\d+)\]\[delete\]=1/

  private originalContextMenuDelete: typeof ContextMenuActions.deleteRecord
  private originalAjaxDataHandlerProcess: typeof AjaxDataHandler.process

  public init(): void {
    this.patchContextMenuActions()
    this.patchAjaxDataHandler()
    this.interceptModalTriggers()
  }

  private patchContextMenuActions(): void {
    this.originalContextMenuDelete = ContextMenuActions.deleteRecord.bind(ContextMenuActions)

    ContextMenuActions.deleteRecord = async (table: string, uid: string, dataset: Record<string, unknown> = {}): Promise<void> => {
      if (table !== 'pages') {
        this.originalContextMenuDelete(table, uid, dataset)
        return
      }

      const shouldProceed = await CustomDeleteHandler.checkAndShowModal(table, uid, dataset)
      if (!shouldProceed) {
        return
      }

      const context = {
        component: 'contextmenu',
        action: 'delete',
        table,
        uid: Number.parseInt(uid, 10)
      }

      await this.originalAjaxDataHandlerProcess(`cmd[${table}][${uid}][delete]=1`, context)
      ContextMenuActions.refreshPageTree()
      const contentContainer = top.TYPO3?.Backend?.ContentContainer?.get()
      if (contentContainer) {
        ContextMenuActions.triggerRefresh(contentContainer.location.href)
      }
    }
  }

  private patchAjaxDataHandler(): void {
    this.originalAjaxDataHandlerProcess = AjaxDataHandler.process.bind(AjaxDataHandler)

    AjaxDataHandler.process = (command: DeleteCommand, context?: Record<string, unknown>): Promise<unknown> => {
      const pageUid = this.extractPageUid(command)
      if (pageUid) {
        return this.handleDirectPageDelete(pageUid, command, context ?? {})
      }

      return this.originalAjaxDataHandlerProcess(command, context)
    }
  }

  private interceptModalTriggers(): void {
    document.addEventListener('click', async (event: Event) => {
      const target = event.target as HTMLElement
      const triggerButton = target.closest('.t3js-modal-trigger') as HTMLElement

      if (!triggerButton) {
        return
      }

      const dataUri = triggerButton.dataset.uri || triggerButton.dataset.href
      if (!dataUri) {
        return
      }

      const decodedUri = decodeURIComponent(dataUri)
      const pageDeleteMatch = decodedUri.match(DeleteInterceptor.PAGE_DELETE_PATTERN)

      if (!pageDeleteMatch) {
        return
      }

      event.preventDefault()
      event.stopImmediatePropagation()

      const pageUid = pageDeleteMatch[1]

      try {
        const shouldProceed = await CustomDeleteHandler.checkAndShowModal('pages', pageUid)

        if (shouldProceed) {
          window.location.href = dataUri
        }
      } catch (error) {
        window.location.href = dataUri
      }
    }, true)
  }

  private async handleDirectPageDelete(pageUid: string, command: DeleteCommand, context: Record<string, unknown>): Promise<unknown> {
    try {
      const shouldProceed = await CustomDeleteHandler.checkAndShowModal('pages', pageUid, context)
      if (shouldProceed) {
        return this.originalAjaxDataHandlerProcess(command, context)
      }

      return { hasErrors: false, messages: [] }
    } catch (error) {
      return this.originalAjaxDataHandlerProcess(command, context)
    }
  }

  private extractPageUid(command: DeleteCommand): string | null {
    if (typeof command === 'string') {
      const match = command.match(DeleteInterceptor.PAGE_DELETE_PATTERN)
      return match ? match[1] : null
    }

    const pages = command.cmd?.pages
    if (!pages) {
      return null
    }

    const [firstUid] = Object.keys(pages)
    if (!firstUid) {
      return null
    }

    return pages[firstUid]?.delete ? firstUid : null
  }
}

const interceptor = new DeleteInterceptor()
interceptor.init()

export default interceptor
