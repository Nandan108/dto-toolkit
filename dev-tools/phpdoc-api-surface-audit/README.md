# Internal Tool: phpdoc-api-surface-audit

Internal audit tool for DTO Toolkit that validates explicit phpdoc API intent tagging.

Currently audited:

- Classes, interfaces, and traits
- Public methods declared directly on those classes/interfaces/traits

Not currently audited:

- Public properties
- Class constants
- Global functions

Run:

```bash
php scripts/phpdoc-api-surface-audit --strict
```

Config file (repository root):

- `.phpdoc-api-surface-audit.php`
