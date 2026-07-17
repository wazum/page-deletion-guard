const Severity = {
  getCssClass(severity: number): string {
    return severity === 2 ? 'danger' : 'warning'
  },
}

export default Severity
