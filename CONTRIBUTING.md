# Contributing

Thank you for your interest in contributing to laravel-raw-blade-components.

Guidelines

- Fork the repository and create a feature branch for your changes.
- Follow existing code style and keep changes focused and well-tested.
- Add or update tests for new behavior. This package uses Pest + Orchestra Testbench.

Running tests locally

```bash
composer install --dev
./vendor/bin/pest
```

Code style

- Keep PER style in PHP files. There's no automatic formatter enforced in the repository; please run a local linter if you use one.

Pull requests

- Open a PR against the `master` branch (or the default branch used by the project).
- Include a short description, motivation, and tests demonstrating the change.
- If the change is non-trivial, describe any backward-incompatible behavior.

Security

If you discover a security issue, please open a private issue or contact the maintainers directly rather than creating a public PR.

Maintainers will review PRs and request changes as needed. Thank you!
