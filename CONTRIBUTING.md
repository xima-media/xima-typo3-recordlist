# Contributing to TYPO3 Recordlist

Thank you for considering contributing to TYPO3 Recordlist! We welcome contributions of all kinds.

## Local Development Setup

This extension uses ddev for local development.

### 1. Start the Environment

```bash
ddev start
```

### 2. Install Dependencies

```bash
ddev composer install
```

### 3. Initialize TYPO3 with Example Data

```bash
ddev init-typo3
```

This sets up a TYPO3 installation with example data and backend modules.

### 4. Access the Backend

After running `ddev start`, you'll see the URL to access the TYPO3 backend.

**Login Credentials:**
- **Admin:** `admin` / `Passw0rd!`
- **Editor:** `editor` / `Passw0rd!`

## Code Quality Standards

We use several tools to maintain code quality. Please run these checks before submitting a pull request.

### Run All Checks

```bash
ddev composer sca
```

### Individual Tools

```bash
# PHP CS Fixer - Code style fixer
ddev composer php:fixer

# PHPStan - Static analysis tool
ddev composer php:stan

# TypoScript Lint
ddev composer typoscript:lint

# YAML Lint
ddev composer yaml:lint

# EditorConfig Lint
ddev composer editorconfig:lint

# Translation Validator
ddev composer language:lint

# Composer Normalize
ddev composer composer:normalize

# Fix PHP code style issues
ddev composer php:fixer

# Normalize composer.json
ddev composer composer:normalize
```

## Getting Help

- **Issues:** Open an issue on GitHub for bugs or questions
- **Discussions:** Start a discussion for feature ideas or general questions

## Code of Conduct

Please be respectful and constructive in all interactions. We're all here to build something useful together.

## License

By contributing, you agree that your contributions will be licensed under GPL-2.0-or-later.

---

Thank you for contributing! 🎉
