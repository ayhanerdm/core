# User Account & Profile Management (PHP)

This library provides robust classes for managing user accounts and profiles in a modern PHP application. It is designed for extensibility, security, and ease of use, leveraging traits and enums for best practices.

## Features
- Create, fetch, update, and delete user accounts
- Secure password handling (bcrypt)
- Turkish Identity Number validation
- Duplicate checks for email, phone, username, and identity number
- Flexible database connection (per-method or global)
- Automatic related table management via triggers
- Profile management (see `Profile.php`)

---

## Requirements
- PHP 8.2+
- PDO extension (MySQL recommended)
- Composer autoloading

---

## Quick Start

### 1. Setup Database Connection
```php
use ayhanerdm\Core\User\Account;

// Set a global PDO connection (recommended for most use cases)
Account::setDatabase($pdo); // $pdo is your PDO instance
```

Or, pass a PDO instance per method call:
```php
Account::Insert(/* ...args... */, $pdo); // $pdo is used only for this call
```

---

### 2. Creating a User Account
```php
use ayhanerdm\Core\User\Account;
use ayhanerdm\Core\Enums\UserOnlineStatuses;

$result = Account::Insert(
    user_id: null, // or your own ID
    tg_id: 12345678901, // Turkish Identity Number (11 digits)
    email: 'user@example.com',
    phone: '+905551112233',
    username: 'myuser',
    password: 'securePassword',
    registered_at: null, // defaults to now
    online_status: UserOnlineStatuses::ONLINE,
    last_online: null,
    pdo: $pdo // optional, can be omitted if set globally
);
if($result) {
    echo "User created!";
}
```

---

### 3. Fetching a User
```php
$user = Account::Fetch(userQuery: 'user@example.com'); // by email, phone, username, or user_id
if($user) {
    print_r($user);
}
```

---

### 4. Updating a User
```php
Account::Update(
    user_id: 1,
    email: 'new@example.com',
    password: 'newPassword',
    // ...other fields as needed
);
```

---

### 5. Deleting a User
```php
Account::Delete(userQuery: 1); // by user_id
```

---

### 6. Triggers for Related Tables
To (re)create triggers for automatic related table management:
```php
Account::addTrigger();
```

---

## Profile Management (`Profile.php`)

The `Profile` class provides methods to manage user profile data such as name, title, birthdate, gender, pronouns, and biographies. Usage is similar to `Account`:

```php
use ayhanerdm\Core\User\Profile;

// Insert a new profile
Profile::Insert(
    user_id: 1,
    title: 'Dr.',
    first_name: 'Ayhan',
    middle_name: 'E.',
    last_name: 'Erdem',
    birthdate: '1990-01-01',
    sex: 'M',
    gender: 'male',
    pronouns: 'he/him',
    short_biography: 'Short bio',
    long_biography: 'Longer biography here.'
);

// Fetch a profile (by user_id or username/email/phone if SearchUserID supports it)
$profile = Profile::Fetch(userQuery: 1);
if($profile) {
    // display_name is automatically built from first, middle, last name
    echo $profile['display_name'] ?? $profile->display_name;
}

// Update a profile (only fields you specify will be updated)
Profile::Update(
    user_id: 1,
    first_name: 'Ayhan',
    last_name: 'Erdem',
    short_biography: 'Updated short bio'
);

// Delete a profile
Profile::Delete(user_id: 1);

// Truncate the user_profiles table (dangerous: removes all profiles)
Profile::truncateTable();
```

---

## Best Practices
- Always use parameterized queries (as in these classes)
- Use the latest PHP version
- Store sensitive data securely
- Use the provided enums for statuses and table names

---

## License
MIT

---

## Author
ayhanerdm
