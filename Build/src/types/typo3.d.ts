// Minimal ambient types for the TYPO3 backend globals and JS modules we
// import. Kept permissive on purpose — full upstream typings live in the
// monorepo and are not shipped to extensions.

declare const TYPO3: {
  settings: {
    ajaxUrls: Record<string, string>;
  };
  lang: Record<string, string>;
  Backend?: {
    ContentContainer?: {
      get(): Window | null;
    };
  };
};

interface Window {
  TYPO3?: typeof TYPO3;
}

declare module '@typo3/backend/modal.js' {
  export type ModalElement = HTMLElement & { hideModal(): void };
  interface ModalButton {
    text: string;
    btnClass?: string;
    name?: string;
    active?: boolean;
    trigger?: (event: Event, modal: ModalElement) => void;
  }
  interface ModalAdvancedOptions {
    title: string;
    content: HTMLElement | string;
    severity: number;
    buttons: ModalButton[];
  }
  const Modal: {
    confirm(title: string, message: string, severity: number, buttons: ModalButton[]): ModalElement;
    advanced(options: ModalAdvancedOptions): ModalElement;
  };
  export default Modal;
}

declare module '@typo3/backend/enum/severity.js' {
  export enum SeverityEnum {
    notice = -2,
    info = -1,
    ok = 0,
    warning = 1,
    error = 2,
  }
}

declare module '@typo3/backend/severity.js' {
  const Severity: {
    getCssClass(severity: number): string;
  };
  export default Severity;
}

declare module '@typo3/core/ajax/ajax-request.js' {
  class AjaxRequest {
    constructor(url: string);
    withQueryArguments(args: Record<string, unknown>): AjaxRequest;
    get(): Promise<{ resolve(): Promise<unknown> }>;
  }
  export default AjaxRequest;
}

declare module '@typo3/backend/ajax-data-handler.js' {
  const AjaxDataHandler: {
    process(command: unknown, context?: Record<string, unknown>): Promise<unknown>;
  };
  export default AjaxDataHandler;
}

declare module '@typo3/backend/context-menu-actions.js' {
  const ContextMenuActions: {
    deleteRecord(table: string, uid: string, dataset?: Record<string, unknown>): Promise<void> | void;
    refreshPageTree(): void;
    triggerRefresh(url: string): void;
  };
  export default ContextMenuActions;
}
