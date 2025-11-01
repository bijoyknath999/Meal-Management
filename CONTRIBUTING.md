# 🤝 Contributing to Meal Management System

First off, thank you for considering contributing to the Meal Management System! 🎉

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)

---

## 📜 Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

### Our Standards

- Be respectful and inclusive
- Welcome newcomers
- Focus on what is best for the community
- Show empathy towards others
- Accept constructive criticism

---

## 🚀 How Can I Contribute?

### Reporting Bugs 🐛

Before creating bug reports, please check the existing issues to avoid duplicates.

**How to Submit a Good Bug Report:**
- Use a clear and descriptive title
- Describe the exact steps to reproduce the problem
- Provide specific examples
- Describe the behavior you observed and expected
- Include screenshots if possible
- Note your environment (OS, PHP version, MySQL version)

### Suggesting Enhancements ✨

Enhancement suggestions are tracked as GitHub issues.

**How to Submit a Good Enhancement Suggestion:**
- Use a clear and descriptive title
- Provide a detailed description of the suggested enhancement
- Provide examples of how it would work
- Explain why this enhancement would be useful

### Pull Requests 🔧

- Fill in the required template
- Follow the coding standards
- Include appropriate test cases
- Update documentation if needed
- End all files with a newline

---

## 💻 Development Setup

### Prerequisites

- PHP >= 7.4
- MySQL >= 5.7
- Git
- Code editor (VS Code recommended)

### Setup Steps

1. **Fork the Repository**
```bash
# Click 'Fork' button on GitHub
```

2. **Clone Your Fork**
```bash
git clone https://github.com/YOUR_USERNAME/meal-management-system.git
cd meal-management-system
```

3. **Add Upstream Remote**
```bash
git remote add upstream https://github.com/ORIGINAL_OWNER/meal-management-system.git
```

4. **Create a Branch**
```bash
git checkout -b feature/your-feature-name
```

5. **Setup Local Environment**
```bash
# Copy config file
cp config-sample.php config.php

# Edit with your local credentials
nano config.php

# Import database
mysql -u root -p meal_management < database.sql
```

6. **Make Your Changes**
```bash
# Write awesome code!
```

7. **Test Your Changes**
```bash
# Test all functionality
# Check for errors
# Verify on different screen sizes
```

8. **Commit Your Changes**
```bash
git add .
git commit -m "feat: Add awesome feature"
```

9. **Push to Your Fork**
```bash
git push origin feature/your-feature-name
```

10. **Create Pull Request**
- Go to your fork on GitHub
- Click 'New Pull Request'
- Fill in the template
- Submit!

---

## 📝 Pull Request Process

1. **Before Submitting:**
   - [ ] Code follows the style guidelines
   - [ ] Self-review completed
   - [ ] Commented complex code
   - [ ] Documentation updated
   - [ ] No new warnings generated
   - [ ] Tested on different devices
   - [ ] Screenshots added (if UI changes)

2. **Pull Request Template:**
```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
Describe how you tested the changes

## Screenshots (if applicable)
Add screenshots here

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-reviewed
- [ ] Commented code
- [ ] Updated documentation
- [ ] No new warnings
- [ ] Tested thoroughly
```

3. **Review Process:**
   - Maintainers will review your PR
   - Address any feedback
   - Once approved, it will be merged
   - Congratulations! 🎉

---

## 🎨 Coding Standards

### PHP Standards

Follow **PSR-12** coding standard:

```php
<?php
// Class names in PascalCase
class MealManager {
    // Properties in camelCase
    private $totalMeals;
    
    // Methods in camelCase
    public function calculateMealRate() {
        // Code here
    }
    
    // Constants in UPPER_CASE
    const MAX_MEALS = 100;
}

// Functions in camelCase
function formatCurrency($amount) {
    return number_format($amount, 2);
}
```

### File Organization

```
feature/
├── controller.php    # Business logic
├── model.php         # Database operations
├── view.php          # User interface
└── functions.php     # Helper functions
```

### Database Queries

Always use prepared statements:

```php
// Good ✅
$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Bad ❌
$result = $db->query("SELECT * FROM members WHERE id = $id");
```

### Security

```php
// Always escape output
echo htmlspecialchars($userInput);

// Use password hashing
$hash = password_hash($password, PASSWORD_DEFAULT);

// Validate input
$email = filter_var($email, FILTER_VALIDATE_EMAIL);
```

### Code Comments

```php
/**
 * Calculate total meal cost for a member
 *
 * @param int $memberId Member's ID
 * @param int $periodId Period ID
 * @return float Total cost
 */
function calculateMemberCost($memberId, $periodId) {
    // Implementation
}
```

---

## 📋 Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding tests
- **chore**: Maintenance tasks

### Examples

```bash
# Good ✅
git commit -m "feat(meals): Add bulk meal entry functionality"
git commit -m "fix(settlements): Correct rounding in calculations"
git commit -m "docs(readme): Update installation instructions"

# Bad ❌
git commit -m "updates"
git commit -m "fixed stuff"
git commit -m "asdfghj"
```

### Detailed Commit

```
feat(expenses): Add expense categories

- Added category dropdown to expense form
- Updated database schema with categories table
- Added migration script for existing data
- Updated expense report to show categories

Closes #123
```

---

## 🧪 Testing

### Manual Testing Checklist

Before submitting, test:

- [ ] Login/logout functionality
- [ ] Adding/editing/deleting members
- [ ] Daily meal entry
- [ ] Expense management
- [ ] Settlement calculations
- [ ] Report generation
- [ ] Public view page
- [ ] Language switching
- [ ] Mobile responsiveness
- [ ] Different browsers (Chrome, Firefox, Safari)
- [ ] Print functionality

### Test Data

Use realistic test data:
- Multiple members (at least 5)
- Different meal counts (0-3)
- Various expense amounts
- Multiple days of data

---

## 🌐 Internationalization

When adding new features:

1. **Add translations in view.php:**
```php
$translations = [
    'bn' => [
        'new_feature' => 'নতুন ফিচার'
    ],
    'en' => [
        'new_feature' => 'New Feature'
    ]
];
```

2. **Use in code:**
```php
echo $t['new_feature'];
```

---

## 📚 Documentation

### When to Update Documentation

Update docs when you:
- Add new features
- Change existing behavior
- Fix bugs that affect usage
- Add new configuration options
- Change database schema

### What to Document

- **README.md**: Feature overview
- **INSTALLATION.md**: Setup changes
- **DEPLOYMENT.md**: Production considerations
- **Code comments**: Complex logic
- **API docs**: New functions

---

## 🎯 Feature Requests

Have an idea? Great! Here's how to suggest it:

1. **Check existing issues** - Maybe it's already planned
2. **Open a new issue** - Use the feature request template
3. **Describe thoroughly**:
   - What problem does it solve?
   - How would it work?
   - Who would benefit?
   - Are there alternatives?

---

## 💬 Questions?

- **GitHub Issues**: For bugs and features
- **GitHub Discussions**: For questions and ideas
- **Email**: For private matters

---

## 🏆 Recognition

Contributors will be recognized in:
- README.md Contributors section
- CONTRIBUTORS.md file
- Release notes

---

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for making the Meal Management System better! 🙏**

Happy coding! 💻✨

