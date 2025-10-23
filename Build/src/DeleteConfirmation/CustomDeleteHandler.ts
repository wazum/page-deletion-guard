import Modal from '@typo3/backend/modal.js'
import { SeverityEnum } from '@typo3/backend/enum/severity.js'
import Severity from '@typo3/backend/severity.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import type { ModalElement } from '@typo3/backend/modal.js'

interface ChildCheckResponse {
  hasChildren: boolean
  childCount: number
  pageTitle: string
  isAllowed: boolean
}

class CustomDeleteHandler {
  public checkAndShowModal = async (table: string, uid: string, _context: Record<string, unknown> = {}): Promise<boolean> => {
    if (table !== 'pages') {
      return true
    }

    const pageUid = Number.parseInt(uid, 10)
    if (!Number.isInteger(pageUid)) {
      return true
    }

    const childInfo = await this.fetchChildInfo(pageUid)

    if (!childInfo) {
      return this.showStandardConfirmation()
    }

    if (!childInfo.hasChildren) {
      return this.showStandardConfirmation()
    }

    if (!childInfo.isAllowed) {
      this.showErrorModal(childInfo)
      return false
    }

    return this.promptWarning(childInfo)
  }

  private async fetchChildInfo(pageUid: number): Promise<ChildCheckResponse | null> {
    try {
      const ajaxUrl = TYPO3.settings.ajaxUrls.ajax_page_deletion_guard_check_children ||
        TYPO3.settings.ajaxUrls.page_deletion_guard_check_children

      if (!ajaxUrl) {
        return null
      }

      const response = await new AjaxRequest(ajaxUrl).withQueryArguments({ pageUid }).get()
      const data = await response.resolve()
      return data
    } catch (error) {
      return null
    }
  }

  private showStandardConfirmation(): Promise<boolean> {
    return new Promise((resolve) => {
      Modal.confirm(
        TYPO3.lang['standard.title'] || 'Delete this record?',
        TYPO3.lang['standard.message'] || 'Are you sure you want to delete this page?',
        SeverityEnum.warning,
        [
          {
            text: TYPO3.lang['button.cancel'] || 'Cancel',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel',
            trigger: (_event: Event, modal: ModalElement): void => {
              modal.hideModal()
              resolve(false)
            }
          },
          {
            text: TYPO3.lang['button.delete'] || 'Delete',
            btnClass: 'btn-warning',
            name: 'delete',
            trigger: (_event: Event, modal: ModalElement): void => {
              modal.hideModal()
              resolve(true)
            }
          }
        ]
      )
    })
  }

  private showErrorModal(data: ChildCheckResponse): void {
    const childLabel = this.getChildLabel(data.childCount)

    const errorPrefix = TYPO3.lang['error.prefix'] || 'Error:'
    const notAllowed = TYPO3.lang['error.not_allowed'] || 'You are not allowed to delete pages with children.'
    const hasChildren = (TYPO3.lang['error.has_children'] || 'This page has %d %s and cannot be deleted.')
      .replaceAll('%d', `<strong>${data.childCount}</strong>`)
      .replaceAll('%s', `<strong>${childLabel}</strong>`)
    const instruction = TYPO3.lang['error.instruction'] || 'Please delete the child pages first or contact your administrator for permissions.'

    const content = document.createElement('div')
    content.innerHTML = `
      <p><strong>${errorPrefix}</strong> ${notAllowed}</p>
      <p>${hasChildren}</p>
      <p>${instruction}</p>
    `

    Modal.advanced({
      title: TYPO3.lang['error.title'] || 'Deletion Not Allowed',
      content,
      severity: SeverityEnum.error,
      buttons: [
        {
          text: TYPO3.lang['button.ok'] || 'OK',
          active: true,
          btnClass: 'btn-default',
          name: 'ok',
          trigger: (_event: Event, modal: ModalElement): void => {
            modal.hideModal()
          }
        }
      ]
    })
  }

  private getChildLabel(count: number): string {
    return count === 1
      ? (TYPO3.lang['label.subpage'] || 'subpage')
      : (TYPO3.lang['label.subpages'] || 'subpages')
  }

  private promptWarning(data: ChildCheckResponse): Promise<boolean> {
    return new Promise((resolve) => {
      const childLabel = this.getChildLabel(data.childCount)

      const warningPrefix = TYPO3.lang['warning.prefix'] || 'Warning:'
      const hasChildren = (TYPO3.lang['warning.has_children'] || 'This page has %d %s.')
        .replaceAll('%d', `<strong>${data.childCount}</strong>`)
        .replaceAll('%s', `<strong>${childLabel}</strong>`)
      const willDeleteAll = TYPO3.lang['warning.will_delete_all'] || 'Deleting this page will also delete all its subpages and their content.'
      const confirmText = TYPO3.lang['warning.confirm'] || 'Are you sure you want to proceed?'

      const content = document.createElement('div')
      content.innerHTML = `
        <p><strong>${warningPrefix}</strong> ${hasChildren}</p>
        <p>${willDeleteAll}</p>
        <p>${confirmText}</p>
      `

      const title = (TYPO3.lang['warning.title'] || 'Delete page "%s"?')
        .replaceAll('%s', data.pageTitle)

      const deleteButtonText = (TYPO3.lang['button.delete_with_children'] || 'Yes, delete page and %d %s')
        .replaceAll('%d', data.childCount.toString())
        .replaceAll('%s', childLabel)

      Modal.advanced({
        title,
        content,
        severity: SeverityEnum.warning,
        buttons: [
          {
            text: TYPO3.lang['button.cancel'] || 'Cancel',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel',
            trigger: (_event: Event, modal: ModalElement): void => {
              modal.hideModal()
              resolve(false)
            }
          },
          {
            text: deleteButtonText,
            btnClass: `btn-${Severity.getCssClass(SeverityEnum.warning)}`,
            name: 'delete',
            trigger: (_event: Event, modal: ModalElement): void => {
              modal.hideModal()
              resolve(true)
            }
          }
        ]
      })
    })
  }
}

export default new CustomDeleteHandler()
