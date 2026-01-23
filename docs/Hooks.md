# Lifecycle Hooks

DTOs in this toolkit are lean and explicit, but occasionally you may need to inject custom behavior at specific points during the lifecycle of a DTO instance. To support this, `BaseDto` provides two simple, overrideable hook methods:

---

## 🪝 `postLoad()`

```php
protected function postLoad(): void
```

Called automatically after hydration from an input source:

- `fromArray()`
- `fromRequest()` (via adapter)
- `fromEntity()` or other adapter-based hydrators

### ✅ Use Cases
- Compute derived values
- Normalize/clean up data after initial fill
- Resolve inter-property dependencies
- Auto-fill values based on others

---

## 🪝 `preOutput($raw): array|object`

```php
protected function preOutput(array|object $outputData): array|object
```

Called just before returning from:

- `toArray()`
- `exportToEntity()`
- `toModel()` (via adapter)

### ✅ Use Cases
- Modify final output structure
- Inject metadata, timestamps, or calculated fields
- Clean or remove values before returning

---

## 🧠 Best Practices

- Keep these methods **idempotent**
- Avoid side effects (e.g. logging, external calls)
- Do not rely on framework-specific services — these hooks live in the core

---

## 🧪 Example

```php
class UserDto extends FullDto
{
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $display_name = null;

    protected function postLoad(): void
    {
        if (! $this->display_name && $this->first_name && $this->last_name) {
            $this->display_name = "{$this->first_name} {$this->last_name}";
        }
    }

    protected function preOutput(array|object $outputData): array|object
    {
        if (is_array($raw)) {
            unset($raw['last_name']); // Only expose first name + display
        }

        return $raw;
    }
}
```

---

These hooks offer a safe, simple way to extend behavior without reaching for traits or complex lifecycle handlers. They're optional — but powerful when needed.
