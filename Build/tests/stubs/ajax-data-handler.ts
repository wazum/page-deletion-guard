const AjaxDataHandler = {
  async process(_command: unknown, _context?: Record<string, unknown>): Promise<unknown> {
    return { hasErrors: false, messages: [] }
  },
}

export default AjaxDataHandler
