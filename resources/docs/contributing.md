# Contributing & License

## License

ALTechnics ERP is released under the MIT License.

```
MIT License

Copyright (c) 2026 Apparel & Leather Technics Pvt. Ltd.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Contributing Guidelines

We welcome contributions to improve ALTechnics ERP. Please follow these guidelines.

### Getting Started

1. Fork the repository
2. Clone your fork locally
3. Create a feature branch: `git checkout -b feature/your-feature-name`
4. Install dependencies: `composer install && npm install`
5. Set up your `.env` and run migrations

### Code Standards

- Follow PSR-12 coding standards for PHP
- Run Laravel Pint before committing: `./vendor/bin/pint`
- Write meaningful commit messages
- Keep controllers thin; use service classes for complex logic
- Add Form Request validation for all store/update actions

### Submitting Changes

1. Write tests for new features or bug fixes
2. Run the full test suite: `php artisan test`
3. Run code formatting: `./vendor/bin/pint`
4. Commit your changes with a clear message
5. Push to your fork and open a Pull Request
6. Describe what your PR does and link any related issues

### Pull Request Checklist

- [ ] Code follows PSR-12 standards
- [ ] Tests written and passing
- [ ] Migrations included if database changes are needed
- [ ] Form Request validation added for new endpoints
- [ ] Permissions added to `RolePermissionSeeder` for new modules
- [ ] Documentation updated in `resources/docs/`

### Reporting Bugs

Open an issue with:
- Steps to reproduce
- Expected behavior
- Actual behavior
- PHP version, Laravel version, and database engine
- Relevant error logs from `storage/logs/laravel.log`

### Feature Requests

Open an issue labeled "feature request" with:
- Description of the feature
- Use case / business justification
- Suggested implementation approach (optional)

## Code of Conduct

Be respectful, constructive, and professional in all interactions. We are committed to providing a welcoming and inclusive environment for everyone.
