const ContextMenuActions = {
  async deleteRecord(_table: string, _uid: string, _dataset?: Record<string, unknown>): Promise<void> {},
  refreshPageTree(): void {},
  triggerRefresh(_url: string): void {},
}

export default ContextMenuActions
