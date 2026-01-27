# WordPress MCP Integration Guide

Guide for integrating with WordPress via Model Context Protocol (MCP) server for programmatic site management.

## Overview

The WordPress MCP server provides a secure interface to interact with WordPress sites programmatically through the WordPress REST API. It enables operations like content management, plugin administration, user management, and site configuration.

## Connection Setup

### Authentication

WordPress MCP uses WordPress Application Passwords for secure authentication:

1. **Generate Application Password:**
   - Log into WordPress admin
   - Navigate to Users → Profile
   - Scroll to "Application Passwords" section
   - Enter application name (e.g., "MCP Integration")
   - Click "Add New Application Password"
   - Save the generated password securely

2. **Connection Parameters:**
   ```json
   {
     "site_url": "https://example.com",
     "username": "admin",
     "application_password": "xxxx xxxx xxxx xxxx xxxx xxxx"
   }
   ```

3. **Verify Connection:**
   - Test authentication with a simple GET request
   - Confirm REST API is accessible
   - Verify user has appropriate permissions

### Required Permissions

Different operations require different capability levels:

- **Read operations**: `read` capability (Subscriber level)
- **Create/Edit posts**: `edit_posts`, `publish_posts` (Author/Editor level)
- **Manage plugins**: `activate_plugins`, `install_plugins` (Administrator level)
- **Manage users**: `create_users`, `edit_users` (Administrator level)
- **Site settings**: `manage_options` (Administrator level)

## Core Operations

### 1. Post Management

#### List Posts
```http
GET /wp-json/wp/v2/posts
Parameters:
  - per_page: Number of posts to return (default: 10, max: 100)
  - page: Page number for pagination
  - status: Post status (publish, draft, private, etc.)
  - author: Filter by author ID
  - search: Search term
  - orderby: Order by field (date, title, etc.)
  - order: asc or desc
```

#### Get Single Post
```http
GET /wp-json/wp/v2/posts/{id}
```

#### Create Post
```http
POST /wp-json/wp/v2/posts
Body:
{
  "title": "Post Title",
  "content": "Post content here",
  "status": "publish",  // draft, publish, private
  "author": 1,
  "excerpt": "Brief excerpt",
  "featured_media": 123,
  "categories": [1, 2],
  "tags": [3, 4],
  "meta": {
    "custom_field": "value"
  }
}
```

#### Update Post
```http
PUT /wp-json/wp/v2/posts/{id}
or
PATCH /wp-json/wp/v2/posts/{id}
Body: Same structure as create, only include fields to update
```

#### Delete Post
```http
DELETE /wp-json/wp/v2/posts/{id}
Parameters:
  - force: true (permanent delete) or false (move to trash)
```

### 2. Page Management

Pages use the same endpoints as posts but at `/wp-json/wp/v2/pages`:

```http
GET    /wp-json/wp/v2/pages
GET    /wp-json/wp/v2/pages/{id}
POST   /wp-json/wp/v2/pages
PUT    /wp-json/wp/v2/pages/{id}
DELETE /wp-json/wp/v2/pages/{id}
```

Additional page-specific fields:
```json
{
  "parent": 0,        // Parent page ID (0 for top-level)
  "menu_order": 0,    // Order in page hierarchy
  "template": ""      // Page template filename
}
```

### 3. Media Management

#### Upload Media
```http
POST /wp-json/wp/v2/media
Headers:
  Content-Type: multipart/form-data
  Content-Disposition: attachment; filename="image.jpg"
Body: Binary file data
```

#### List Media
```http
GET /wp-json/wp/v2/media
Parameters:
  - media_type: Filter by type (image, video, application)
  - mime_type: Specific MIME type
```

#### Get Media Details
```http
GET /wp-json/wp/v2/media/{id}
```

#### Update Media
```http
POST /wp-json/wp/v2/media/{id}
Body:
{
  "title": "New Title",
  "alt_text": "Alt text for image",
  "caption": "Image caption",
  "description": "Media description"
}
```

#### Delete Media
```http
DELETE /wp-json/wp/v2/media/{id}
Parameters:
  - force: true (permanent delete)
```

### 4. User Management

#### List Users
```http
GET /wp-json/wp/v2/users
Parameters:
  - roles: Filter by role (administrator, editor, author, etc.)
  - orderby: Order by field
```

#### Get User
```http
GET /wp-json/wp/v2/users/{id}
or
GET /wp-json/wp/v2/users/me  // Current user
```

#### Create User
```http
POST /wp-json/wp/v2/users
Body:
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "secure_password",
  "roles": ["author"],
  "first_name": "First",
  "last_name": "Last",
  "description": "User bio"
}
```

#### Update User
```http
POST /wp-json/wp/v2/users/{id}
Body: Same structure as create, only include fields to update
```

#### Delete User
```http
DELETE /wp-json/wp/v2/users/{id}
Parameters:
  - reassign: User ID to reassign posts to
  - force: true (required for deletion)
```

### 5. Category and Tag Management

#### Categories
```http
GET    /wp-json/wp/v2/categories
GET    /wp-json/wp/v2/categories/{id}
POST   /wp-json/wp/v2/categories
PUT    /wp-json/wp/v2/categories/{id}
DELETE /wp-json/wp/v2/categories/{id}
```

Category fields:
```json
{
  "name": "Category Name",
  "slug": "category-slug",
  "parent": 0,
  "description": "Category description"
}
```

#### Tags
```http
GET    /wp-json/wp/v2/tags
POST   /wp-json/wp/v2/tags
PUT    /wp-json/wp/v2/tags/{id}
DELETE /wp-json/wp/v2/tags/{id}
```

### 6. Comment Management

```http
GET    /wp-json/wp/v2/comments
GET    /wp-json/wp/v2/comments/{id}
POST   /wp-json/wp/v2/comments
PUT    /wp-json/wp/v2/comments/{id}
DELETE /wp-json/wp/v2/comments/{id}
```

Comment creation:
```json
{
  "post": 123,              // Post ID
  "author_name": "Name",
  "author_email": "email@example.com",
  "content": "Comment text",
  "parent": 0,              // Parent comment ID
  "status": "approved"      // approved, hold, spam
}
```

### 7. Plugin Management

#### List Plugins
```http
GET /wp-json/wp/v2/plugins
```

Response includes:
- Plugin slug
- Status (active, inactive)
- Version
- Name and description

#### Get Plugin Details
```http
GET /wp-json/wp/v2/plugins/{plugin-slug}
```

#### Install Plugin
```http
POST /wp-json/wp/v2/plugins
Body:
{
  "slug": "plugin-slug",
  "status": "inactive"  // or "active" to activate immediately
}
```

#### Activate Plugin
```http
PUT /wp-json/wp/v2/plugins/{plugin-slug}
Body:
{
  "status": "active"
}
```

#### Deactivate Plugin
```http
PUT /wp-json/wp/v2/plugins/{plugin-slug}
Body:
{
  "status": "inactive"
}
```

#### Delete Plugin
```http
DELETE /wp-json/wp/v2/plugins/{plugin-slug}
```

### 8. Theme Management

#### List Themes
```http
GET /wp-json/wp/v2/themes
```

#### Get Active Theme
```http
GET /wp-json/wp/v2/themes?status=active
```

#### Activate Theme
```http
POST /wp-json/wp/v2/themes/{theme-slug}/activate
```

### 9. Settings Management

#### Get Settings
```http
GET /wp-json/wp/v2/settings
```

Returns:
- Site title and tagline
- URL structure
- Default post/page settings
- Discussion settings
- Reading settings

#### Update Settings
```http
POST /wp-json/wp/v2/settings
Body:
{
  "title": "New Site Title",
  "description": "New tagline",
  "timezone_string": "America/New_York",
  "date_format": "F j, Y",
  "time_format": "g:i a",
  "start_of_week": 1,
  "language": "en_US",
  "posts_per_page": 10,
  "default_post_format": "standard"
}
```

### 10. Custom Post Types

For custom post types registered with `show_in_rest => true`:

```http
GET    /wp-json/wp/v2/{post-type}
GET    /wp-json/wp/v2/{post-type}/{id}
POST   /wp-json/wp/v2/{post-type}
PUT    /wp-json/wp/v2/{post-type}/{id}
DELETE /wp-json/wp/v2/{post-type}/{id}
```

Example for 'product' post type:
```http
GET /wp-json/wp/v2/products
```

## Error Handling

### Common HTTP Status Codes

- `200 OK`: Successful GET request
- `201 Created`: Successful POST request
- `400 Bad Request`: Invalid parameters or data
- `401 Unauthorized`: Authentication failed
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource doesn't exist
- `500 Internal Server Error`: Server error

### Error Response Format

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): title",
  "data": {
    "status": 400,
    "params": {
      "title": "The title field is required."
    }
  }
}
```

### Error Handling Pattern

```javascript
async function safeApiCall(endpoint, options) {
  try {
    const response = await fetch(endpoint, options);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Request failed');
    }

    return { success: true, data };
  } catch (error) {
    return {
      success: false,
      error: error.message,
      code: error.code
    };
  }
}
```

## Batch Operations

For efficiency, batch multiple operations:

```javascript
async function batchCreatePosts(posts) {
  const promises = posts.map(post =>
    fetch('/wp-json/wp/v2/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Basic ' + btoa(username + ':' + password)
      },
      body: JSON.stringify(post)
    })
  );

  const results = await Promise.allSettled(promises);
  return results;
}
```

## Rate Limiting

WordPress REST API typically doesn't enforce rate limits by default, but:

1. **Be considerate**: Don't overwhelm the server
2. **Implement delays**: Add delays between requests for large operations
3. **Use pagination**: Don't request all data at once
4. **Cache responses**: Store frequently accessed data locally

```javascript
function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function processWithDelay(items, processor) {
  for (const item of items) {
    await processor(item);
    await delay(100); // 100ms delay between requests
  }
}
```

## Security Best Practices

1. **HTTPS Only**: Always use HTTPS for API requests
2. **Secure Credentials**: Never hardcode credentials
3. **Application Passwords**: Use application passwords, not account passwords
4. **Minimal Permissions**: Use accounts with only necessary capabilities
5. **Validate Input**: Always validate data before sending
6. **Log Operations**: Keep audit logs of MCP operations
7. **Rotate Credentials**: Regularly rotate application passwords
8. **Monitor Usage**: Track API usage for suspicious activity

## MCP Workflow Patterns

### Pattern 1: Content Migration

```javascript
async function migrateContent(sourceApi, targetApi, postIds) {
  for (const postId of postIds) {
    // Fetch from source
    const post = await sourceApi.getPost(postId);

    // Transform data if needed
    const transformedPost = {
      title: post.title.rendered,
      content: post.content.rendered,
      status: 'draft' // Publish manually after review
    };

    // Create in target
    const result = await targetApi.createPost(transformedPost);

    // Log result
    console.log(`Migrated post ${postId}: ${result.success}`);

    // Delay to avoid overwhelming server
    await delay(200);
  }
}
```

### Pattern 2: Bulk User Creation

```javascript
async function createUsersFromCsv(csvData, api) {
  const users = parseCsv(csvData);
  const results = { success: [], failed: [] };

  for (const userData of users) {
    try {
      const user = await api.createUser({
        username: userData.username,
        email: userData.email,
        password: generateSecurePassword(),
        roles: [userData.role || 'subscriber']
      });

      results.success.push(user);

      // Send welcome email with credentials
      await sendWelcomeEmail(userData.email, user);
    } catch (error) {
      results.failed.push({
        userData,
        error: error.message
      });
    }
  }

  return results;
}
```

### Pattern 3: Site Backup

```javascript
async function backupSite(api) {
  const backup = {
    timestamp: new Date().toISOString(),
    posts: [],
    pages: [],
    media: [],
    users: [],
    settings: null
  };

  // Backup posts (with pagination)
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const posts = await api.getPosts({ per_page: 100, page });
    backup.posts.push(...posts);
    hasMore = posts.length === 100;
    page++;
  }

  // Backup pages
  page = 1;
  hasMore = true;
  while (hasMore) {
    const pages = await api.getPages({ per_page: 100, page });
    backup.pages.push(...pages);
    hasMore = pages.length === 100;
    page++;
  }

  // Backup settings
  backup.settings = await api.getSettings();

  // Save backup
  await saveBackupFile(backup);

  return backup;
}
```

## Troubleshooting

### Issue: 401 Unauthorized

**Solutions:**
- Verify application password is correct
- Check username is correct
- Ensure user account is active
- Verify Authorization header format

### Issue: 403 Forbidden

**Solutions:**
- Check user has required capabilities
- Verify REST API is not disabled
- Check for security plugins blocking REST API
- Review .htaccess for restrictions

### Issue: 404 Not Found

**Solutions:**
- Verify permalink structure is not "Plain"
- Check REST API route is correct
- Ensure post/resource exists
- Verify custom post type has `show_in_rest => true`

### Issue: REST API Disabled

**Check:**
```php
// In theme functions.php or plugin
add_filter( 'rest_authentication_errors', function( $result ) {
    // If REST API is disabled, this will return WP_Error
    return $result;
});
```

**Enable REST API:**
```php
// Remove filter that disables REST API
remove_filter( 'rest_authentication_errors', 'disable_rest_api_filter' );
```

## Advanced: Custom MCP Endpoints

To extend MCP with custom operations, register custom REST endpoints:

```php
function register_custom_mcp_endpoint() {
    register_rest_route( 'mcp/v1', '/batch-operation', array(
        'methods'             => 'POST',
        'callback'            => 'handle_batch_operation',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
        'args'                => array(
            'operation' => array(
                'required' => true,
                'type'     => 'string',
            ),
            'items' => array(
                'required' => true,
                'type'     => 'array',
            ),
        ),
    ));
}
add_action( 'rest_api_init', 'register_custom_mcp_endpoint' );

function handle_batch_operation( $request ) {
    $operation = $request->get_param( 'operation' );
    $items = $request->get_param( 'items' );

    $results = array();

    foreach ( $items as $item ) {
        switch ( $operation ) {
            case 'publish':
                wp_publish_post( $item['id'] );
                break;
            case 'trash':
                wp_trash_post( $item['id'] );
                break;
            // Add more operations
        }
        $results[] = array( 'id' => $item['id'], 'status' => 'success' );
    }

    return new WP_REST_Response( $results, 200 );
}
```

## Resources

- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [Application Passwords Documentation](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/)
- [REST API Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
- [REST API Schema Reference](https://developer.wordpress.org/rest-api/reference/)
