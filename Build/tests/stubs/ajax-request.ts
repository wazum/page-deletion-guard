let nextResponse: unknown = null
let shouldReject = false

export function __setResponse(response: unknown): void {
  nextResponse = response
  shouldReject = false
}

export function __setRejection(): void {
  shouldReject = true
}

class AjaxRequest {
  constructor(_url: string) {}

  withQueryArguments(_args: Record<string, unknown>): AjaxRequest {
    return this
  }

  async get(): Promise<{ resolve(): Promise<unknown> }> {
    if (shouldReject) {
      throw new Error('network error')
    }
    return { resolve: async (): Promise<unknown> => nextResponse }
  }
}

export default AjaxRequest
