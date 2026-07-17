export type ModalElement = HTMLElement & { hideModal(): void }

const Modal = {
  confirm(): ModalElement {
    return document.createElement('div') as unknown as ModalElement
  },
  advanced(): ModalElement {
    return document.createElement('div') as unknown as ModalElement
  },
}

export default Modal
