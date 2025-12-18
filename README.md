# TYPO3 Page Deletion Guard

[![CI](https://github.com/wazum/page-deletion-guard/workflows/CI/badge.svg)](https://github.com/wazum/page-deletion-guard/actions)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-blue.svg)](https://www.php.net/)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4%20|%2014-orange.svg)](https://typo3.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

A TYPO3 extension that prevents accidental deletion of pages with child pages in the backend. This extension restores the safety guard that was removed in TYPO3 v11 ([Breaking Change #92560](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Breaking-92560-BackendEditorsCanAlwaysDeletePagesRecursive.html)).

> [!WARNING]
> TYPO3 core developers recommend using the Recycler to restore accidentally deleted pages. However, since TYPO3 does not use database transactions for page operations, any timeout or error during recursive deletion or restoration can leave your page tree in an inconsistent, partially deleted state. The Recycler cannot reliably fix such broken structures. This extension prevents these scenarios by blocking risky deletions before they even happen!

## Quick Start

```bash
composer require wazum/page-deletion-guard
```

The extension works immediately after installation with sensible defaults. Administrators can bypass the guard, regular editors cannot delete pages with children.

## The Problem

Since TYPO3 v11, backend editors can delete entire page trees with a single click. This increases the risk of:
- Accidental deletion of important page structures
- Loss of content without warning
- No built-in protection against recursive deletion

## Features

- Prevents accidental deletion of pages with child pages
- Shows warning modal with exact child page count before deletion
- Administrators can bypass the protection when needed
- Works in all backend modules (page tree, list module, etc.)
- Configurable per backend user group
- Logs all blocked deletion attempts

## Installation

```bash
composer require wazum/page-deletion-guard
```

The extension activates automatically with these defaults:
- Protection **enabled**
- Admin bypass **allowed**
- Regular editors **cannot** delete pages with children

No additional configuration required unless you want to customize the behavior.

## Configuration

Configure the extension via **Admin Tools → Settings → Extension Configuration → page_deletion_guard**:

### Available Options

- **`enabled`** (boolean, default: `true`)
  Enable or disable the deletion guard entirely.

- **`allowAdminBypass`** (boolean, default: `true`)
  Allow administrators to delete pages with children. When disabled, even admins must delete child pages first.

- **`bypassBackendGroups`** (string, default: empty)
  Comma-separated list of backend user group IDs that can bypass the guard. Example: `5,10,15`

- **`respectWorkspaces`** (boolean, default: `true`)
  When enabled, workspace restrictions are applied to child page queries. Disable to count all children regardless of workspace.

## License

GNU General Public License version 2 or later (GPL-2.0-or-later)

## Author

Wolfgang Klinger
[wolfgang@wazum.com](mailto:wolfgang@wazum.com)
