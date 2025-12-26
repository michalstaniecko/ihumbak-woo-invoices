# Super-Admin Configuration

A super-admin is a user with extended permissions who can revert document status from `issued` back to `draft`. This is useful when an invoice was issued by mistake and requires editing.

## Why is this feature restricted?

Reverting an issued invoice status is an operation that should not normally be allowed - an issued invoice is an accounting document. However, in special cases (e.g., data errors, issuing mistakes) an administrator may need the ability to revert a document to draft.

## Method 1: Constant in wp-config.php (recommended)

Add a constant with a list of user IDs to your `wp-config.php` file:

```php
// List of user IDs with super-admin permissions
// Can revert document status from "issued" to "draft"
define('IHUMBAK_SUPER_ADMIN_IDS', '1,5,12');
```

### Parameters:
- Value is a string with user IDs separated by commas
- Spaces are ignored (e.g., `'1, 5, 12'` will also work)
- Empty value or missing constant = no super-admins

### How to find a user ID:

1. Go to **WP Admin → Users**
2. Click on the desired user
3. In the address bar you'll see a URL in format: `user-edit.php?user_id=X`
4. The value `X` is the user ID

## Method 2: PHP Filter (advanced)

For more dynamic configuration, you can use filters in your theme's `functions.php` or a custom plugin:

### Example 1: All administrators as super-admins

```php
add_filter('ihumbak_is_user_super_admin', function($is_super_admin, $user_id) {
    $user = get_user_by('id', $user_id);
    if ($user && in_array('administrator', $user->roles)) {
        return true;
    }
    return $is_super_admin;
}, 10, 2);
```

### Example 2: Users with a specific capability

```php
add_filter('ihumbak_is_user_super_admin', function($is_super_admin, $user_id) {
    $user = get_user_by('id', $user_id);
    if ($user && $user->has_cap('manage_options')) {
        return true;
    }
    return $is_super_admin;
}, 10, 2);
```

### Example 3: Checking current user

```php
add_filter('ihumbak_is_current_user_super_admin', function($is_super_admin, $user_id) {
    // Your logic here...
    return $is_super_admin;
}, 10, 2);
```

## Available Filters

| Filter | Description | Parameters |
|--------|-------------|------------|
| `ihumbak_is_user_super_admin` | Checks if a user with given ID is a super-admin | `$is_super_admin` (bool), `$user_id` (int) |
| `ihumbak_is_current_user_super_admin` | Checks if the current user is a super-admin | `$is_super_admin` (bool), `$user_id` (int) |

## Action After Status Revert

After reverting a document status, an action is triggered:

```php
do_action('ihumbak_document_reverted_to_draft', Document $document, int $user_id);
```

You can use it for logging such operations:

```php
add_action('ihumbak_document_reverted_to_draft', function($document, $user_id) {
    error_log(sprintf(
        'Document #%d (%s) reverted to draft by user #%d',
        $document->getId(),
        $document->getDocumentNumber(),
        $user_id
    ));
}, 10, 2);
```

## User Interface

When a user is a super-admin, the document edit panel for documents with `issued` status will display a section with a "Revert to Draft" button. The section is highlighted with a yellow background as a warning that this is a special operation.

## Security

- The feature requires `manage_woocommerce` capability
- Additionally, the super-admin list is checked
- The operation is verified by nonce
- It is recommended to limit the super-admin list to the minimum necessary people
