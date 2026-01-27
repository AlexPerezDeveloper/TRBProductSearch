# WordPress Database Schema Reference

Comprehensive reference for WordPress database structure and custom table patterns.

## Core WordPress Tables

WordPress uses a prefix (default: `wp_`) for all table names. The prefix is configurable in `wp-config.php`.

### 1. wp_posts

Stores all post types (posts, pages, attachments, revisions, custom post types).

**Structure:**
```sql
CREATE TABLE wp_posts (
  ID bigint(20) unsigned NOT NULL auto_increment,
  post_author bigint(20) unsigned NOT NULL default '0',
  post_date datetime NOT NULL default '0000-00-00 00:00:00',
  post_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  post_content longtext NOT NULL,
  post_title text NOT NULL,
  post_excerpt text NOT NULL,
  post_status varchar(20) NOT NULL default 'publish',
  comment_status varchar(20) NOT NULL default 'open',
  ping_status varchar(20) NOT NULL default 'open',
  post_password varchar(255) NOT NULL default '',
  post_name varchar(200) NOT NULL default '',
  to_ping text NOT NULL,
  pinged text NOT NULL,
  post_modified datetime NOT NULL default '0000-00-00 00:00:00',
  post_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  post_content_filtered longtext NOT NULL,
  post_parent bigint(20) unsigned NOT NULL default '0',
  guid varchar(255) NOT NULL default '',
  menu_order int(11) NOT NULL default '0',
  post_type varchar(20) NOT NULL default 'post',
  post_mime_type varchar(100) NOT NULL default '',
  comment_count bigint(20) NOT NULL default '0',
  PRIMARY KEY  (ID),
  KEY post_name (post_name(191)),
  KEY type_status_date (post_type,post_status,post_date,ID),
  KEY post_parent (post_parent),
  KEY post_author (post_author)
);
```

**Common post_status values:**
- `publish`: Published content
- `draft`: Draft content
- `pending`: Awaiting review
- `private`: Private content
- `trash`: Trashed content
- `auto-draft`: Automatically saved draft
- `inherit`: Used by revisions and attachments

**Common post_type values:**
- `post`: Blog posts
- `page`: Pages
- `attachment`: Media uploads
- `revision`: Post revisions
- `nav_menu_item`: Menu items
- Custom post types

### 2. wp_postmeta

Stores post metadata (custom fields).

**Structure:**
```sql
CREATE TABLE wp_postmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  post_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY post_id (post_id),
  KEY meta_key (meta_key(191))
);
```

**Common meta_keys:**
- `_thumbnail_id`: Featured image ID
- `_edit_last`: Last user to edit
- `_edit_lock`: Edit lock timestamp
- `_wp_page_template`: Page template filename
- Custom field keys (typically prefixed with `_` for hidden fields)

### 3. wp_comments

Stores comments on posts.

**Structure:**
```sql
CREATE TABLE wp_comments (
  comment_ID bigint(20) unsigned NOT NULL auto_increment,
  comment_post_ID bigint(20) unsigned NOT NULL default '0',
  comment_author tinytext NOT NULL,
  comment_author_email varchar(100) NOT NULL default '',
  comment_author_url varchar(200) NOT NULL default '',
  comment_author_IP varchar(100) NOT NULL default '',
  comment_date datetime NOT NULL default '0000-00-00 00:00:00',
  comment_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  comment_content text NOT NULL,
  comment_karma int(11) NOT NULL default '0',
  comment_approved varchar(20) NOT NULL default '1',
  comment_agent varchar(255) NOT NULL default '',
  comment_type varchar(20) NOT NULL default 'comment',
  comment_parent bigint(20) unsigned NOT NULL default '0',
  user_id bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (comment_ID),
  KEY comment_post_ID (comment_post_ID),
  KEY comment_approved_date_gmt (comment_approved,comment_date_gmt),
  KEY comment_date_gmt (comment_date_gmt),
  KEY comment_parent (comment_parent),
  KEY comment_author_email (comment_author_email(10))
);
```

**comment_approved values:**
- `1`: Approved
- `0`: Pending approval
- `spam`: Marked as spam
- `trash`: In trash

### 4. wp_commentmeta

Stores comment metadata.

**Structure:**
```sql
CREATE TABLE wp_commentmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  comment_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY comment_id (comment_id),
  KEY meta_key (meta_key(191))
);
```

### 5. wp_users

Stores user account information.

**Structure:**
```sql
CREATE TABLE wp_users (
  ID bigint(20) unsigned NOT NULL auto_increment,
  user_login varchar(60) NOT NULL default '',
  user_pass varchar(255) NOT NULL default '',
  user_nicename varchar(50) NOT NULL default '',
  user_email varchar(100) NOT NULL default '',
  user_url varchar(100) NOT NULL default '',
  user_registered datetime NOT NULL default '0000-00-00 00:00:00',
  user_activation_key varchar(255) NOT NULL default '',
  user_status int(11) NOT NULL default '0',
  display_name varchar(250) NOT NULL default '',
  PRIMARY KEY  (ID),
  KEY user_login_key (user_login),
  KEY user_nicename (user_nicename),
  KEY user_email (user_email)
);
```

### 6. wp_usermeta

Stores user metadata.

**Structure:**
```sql
CREATE TABLE wp_usermeta (
  umeta_id bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (umeta_id),
  KEY user_id (user_id),
  KEY meta_key (meta_key(191))
);
```

**Important meta_keys:**
- `nickname`: User's nickname
- `first_name`: First name
- `last_name`: Last name
- `description`: User bio
- `wp_capabilities`: Serialized array of user roles
- `wp_user_level`: Numeric user level (deprecated)
- `session_tokens`: Active login sessions

### 7. wp_terms

Stores taxonomy terms (categories, tags, custom taxonomies).

**Structure:**
```sql
CREATE TABLE wp_terms (
  term_id bigint(20) unsigned NOT NULL auto_increment,
  name varchar(200) NOT NULL default '',
  slug varchar(200) NOT NULL default '',
  term_group bigint(10) NOT NULL default 0,
  PRIMARY KEY  (term_id),
  KEY slug (slug(191)),
  KEY name (name(191))
);
```

### 8. wp_term_taxonomy

Links terms to taxonomies and stores taxonomy-specific data.

**Structure:**
```sql
CREATE TABLE wp_term_taxonomy (
  term_taxonomy_id bigint(20) unsigned NOT NULL auto_increment,
  term_id bigint(20) unsigned NOT NULL default 0,
  taxonomy varchar(32) NOT NULL default '',
  description longtext NOT NULL,
  parent bigint(20) unsigned NOT NULL default 0,
  count bigint(20) NOT NULL default 0,
  PRIMARY KEY  (term_taxonomy_id),
  UNIQUE KEY term_id_taxonomy (term_id,taxonomy),
  KEY taxonomy (taxonomy)
);
```

**Common taxonomy values:**
- `category`: Post categories
- `post_tag`: Post tags
- `nav_menu`: Navigation menus
- `link_category`: Link categories (deprecated)
- Custom taxonomies

### 9. wp_term_relationships

Associates posts with terms.

**Structure:**
```sql
CREATE TABLE wp_term_relationships (
  object_id bigint(20) unsigned NOT NULL default 0,
  term_taxonomy_id bigint(20) unsigned NOT NULL default 0,
  term_order int(11) NOT NULL default 0,
  PRIMARY KEY  (object_id,term_taxonomy_id),
  KEY term_taxonomy_id (term_taxonomy_id)
);
```

### 10. wp_options

Stores site settings and options.

**Structure:**
```sql
CREATE TABLE wp_options (
  option_id bigint(20) unsigned NOT NULL auto_increment,
  option_name varchar(191) UNIQUE NOT NULL default '',
  option_value longtext NOT NULL,
  autoload varchar(20) NOT NULL default 'yes',
  PRIMARY KEY  (option_id),
  UNIQUE KEY option_name (option_name),
  KEY autoload (autoload)
);
```

**Important options:**
- `siteurl`: WordPress address (URL)
- `home`: Site address (URL)
- `blogname`: Site title
- `blogdescription`: Tagline
- `users_can_register`: Allow user registration
- `admin_email`: Admin email address
- `default_role`: Default user role
- `timezone_string`: Timezone
- `date_format`: Date format
- `time_format`: Time format
- `active_plugins`: Array of active plugins
- `template`: Active theme directory
- `stylesheet`: Active stylesheet directory

**autoload values:**
- `yes`: Load on every page (cached)
- `no`: Load only when requested

### 11. wp_links (Legacy)

Stores blogroll links (deprecated but still present).

**Structure:**
```sql
CREATE TABLE wp_links (
  link_id bigint(20) unsigned NOT NULL auto_increment,
  link_url varchar(255) NOT NULL default '',
  link_name varchar(255) NOT NULL default '',
  link_image varchar(255) NOT NULL default '',
  link_target varchar(25) NOT NULL default '',
  link_description varchar(255) NOT NULL default '',
  link_visible varchar(20) NOT NULL default 'Y',
  link_owner bigint(20) unsigned NOT NULL default '1',
  link_rating int(11) NOT NULL default '0',
  link_updated datetime NOT NULL default '0000-00-00 00:00:00',
  link_rel varchar(255) NOT NULL default '',
  link_notes mediumtext NOT NULL,
  link_rss varchar(255) NOT NULL default '',
  PRIMARY KEY  (link_id),
  KEY link_visible (link_visible)
);
```

## Multisite Tables

WordPress multisite adds additional tables:

### wp_blogs
Lists all sites in the network.

### wp_blogmeta
Stores site-specific metadata.

### wp_site
Stores network information.

### wp_sitemeta
Stores network-wide settings.

### wp_registration_log
Logs new site registrations.

### wp_signups
Stores signup information.

## Custom Table Patterns

### Pattern 1: Simple Data Table

```php
function create_custom_data_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'custom_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        data text NOT NULL,
        status varchar(20) DEFAULT 'active' NOT NULL,
        PRIMARY KEY  (id),
        KEY name (name),
        KEY status (status),
        KEY email (email)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Store version for future updates
    add_option( 'custom_data_table_version', '1.0' );
}
```

### Pattern 2: Relational Table

```php
function create_relationship_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Main entity table
    $table_items = $wpdb->prefix . 'custom_items';
    $sql_items = "CREATE TABLE $table_items (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY title (title(191))
    ) $charset_collate;";

    // Attributes table
    $table_attributes = $wpdb->prefix . 'custom_attributes';
    $sql_attributes = "CREATE TABLE $table_attributes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        type varchar(50) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY name (name)
    ) $charset_collate;";

    // Junction table (many-to-many)
    $table_item_attrs = $wpdb->prefix . 'custom_item_attributes';
    $sql_item_attrs = "CREATE TABLE $table_item_attrs (
        item_id bigint(20) unsigned NOT NULL,
        attribute_id bigint(20) unsigned NOT NULL,
        value text NOT NULL,
        PRIMARY KEY (item_id, attribute_id),
        KEY attribute_id (attribute_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_items );
    dbDelta( $sql_attributes );
    dbDelta( $sql_item_attrs );
}
```

### Pattern 3: Logging Table

```php
function create_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'activity_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        action varchar(50) NOT NULL,
        object_type varchar(50) NOT NULL,
        object_id bigint(20) unsigned NOT NULL,
        details longtext,
        ip_address varchar(45) NOT NULL,
        user_agent varchar(255) NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY action (action),
        KEY timestamp (timestamp),
        KEY object (object_type, object_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
```

### Pattern 4: Cache Table

```php
function create_cache_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'custom_cache';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        cache_key varchar(255) NOT NULL,
        cache_value longtext NOT NULL,
        expires_at datetime NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (cache_key),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
```

## Database Query Patterns

### Safe Query with wpdb

```php
global $wpdb;

// SELECT with prepared statement
$user_id = 123;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}custom_data WHERE user_id = %d AND status = %s",
        $user_id,
        'active'
    )
);

// INSERT
$wpdb->insert(
    $wpdb->prefix . 'custom_data',
    array(
        'name'   => 'John Doe',
        'email'  => 'john@example.com',
        'status' => 'active',
        'data'   => json_encode( $data_array )
    ),
    array( '%s', '%s', '%s', '%s' )
);
$insert_id = $wpdb->insert_id;

// UPDATE
$wpdb->update(
    $wpdb->prefix . 'custom_data',
    array( 'status' => 'inactive' ),
    array( 'id' => $record_id ),
    array( '%s' ),
    array( '%d' )
);

// DELETE
$wpdb->delete(
    $wpdb->prefix . 'custom_data',
    array( 'id' => $record_id ),
    array( '%d' )
);

// Complex query
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT cd.*, u.display_name
        FROM {$wpdb->prefix}custom_data cd
        LEFT JOIN {$wpdb->users} u ON cd.user_id = u.ID
        WHERE cd.created_at > %s
        AND cd.status IN (%s, %s)
        ORDER BY cd.created_at DESC
        LIMIT %d",
        $date,
        'active',
        'pending',
        $limit
    )
);
```

### Transaction Pattern

```php
function safe_multi_table_operation() {
    global $wpdb;

    // Start transaction
    $wpdb->query( 'START TRANSACTION' );

    try {
        // Insert into first table
        $result1 = $wpdb->insert(
            $wpdb->prefix . 'table1',
            array( 'data' => 'value1' ),
            array( '%s' )
        );

        if ( ! $result1 ) {
            throw new Exception( 'Failed to insert into table1' );
        }

        $id = $wpdb->insert_id;

        // Insert into second table
        $result2 = $wpdb->insert(
            $wpdb->prefix . 'table2',
            array( 'table1_id' => $id, 'data' => 'value2' ),
            array( '%d', '%s' )
        );

        if ( ! $result2 ) {
            throw new Exception( 'Failed to insert into table2' );
        }

        // Commit transaction
        $wpdb->query( 'COMMIT' );
        return true;

    } catch ( Exception $e ) {
        // Rollback on error
        $wpdb->query( 'ROLLBACK' );
        error_log( 'Transaction failed: ' . $e->getMessage() );
        return false;
    }
}
```

## Database Upgrade Pattern

```php
function upgrade_custom_table() {
    global $wpdb;

    $installed_version = get_option( 'custom_table_version', '0' );
    $current_version = '2.0';

    if ( version_compare( $installed_version, $current_version, '<' ) ) {
        $table_name = $wpdb->prefix . 'custom_data';
        $charset_collate = $wpdb->get_charset_collate();

        // Add new column
        $wpdb->query( "ALTER TABLE $table_name ADD COLUMN new_field VARCHAR(100) DEFAULT '' AFTER existing_field" );

        // Add index
        $wpdb->query( "ALTER TABLE $table_name ADD INDEX idx_new_field (new_field)" );

        // Update version
        update_option( 'custom_table_version', $current_version );
    }
}
add_action( 'plugins_loaded', 'upgrade_custom_table' );
```

## Performance Best Practices

1. **Use indexes** on columns used in WHERE, JOIN, ORDER BY
2. **Avoid SELECT *** - select only needed columns
3. **Use prepared statements** always for security and performance
4. **Limit result sets** with LIMIT clause
5. **Use appropriate data types** - don't use TEXT when VARCHAR is sufficient
6. **Cache query results** with transients or object cache
7. **Batch operations** instead of individual queries in loops
8. **Use InnoDB engine** for transactional support
9. **Monitor slow queries** and optimize
10. **Regular database optimization** with WP-CLI or plugins

## Database Maintenance

### Optimize Tables
```php
function optimize_custom_tables() {
    global $wpdb;
    $tables = array(
        $wpdb->prefix . 'custom_data',
        $wpdb->prefix . 'custom_log'
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "OPTIMIZE TABLE $table" );
    }
}
```

### Clean Old Data
```php
function clean_old_log_entries() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'activity_log';

    // Delete logs older than 90 days
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            90
        )
    );
}
```

## Common Queries

### Get posts with meta
```php
$posts = $wpdb->get_results(
    "SELECT p.*, pm.meta_value as custom_field
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_custom_field'
    WHERE p.post_type = 'post'
    AND p.post_status = 'publish'
    ORDER BY p.post_date DESC"
);
```

### Get posts with taxonomy terms
```php
$posts = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DISTINCT p.*
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        WHERE p.post_type = %s
        AND p.post_status = 'publish'
        AND tt.taxonomy = %s
        AND t.slug = %s",
        'post',
        'category',
        'news'
    )
);
```

### Count posts by author
```php
$stats = $wpdb->get_results(
    "SELECT u.display_name, COUNT(p.ID) as post_count
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author
        AND p.post_type = 'post'
        AND p.post_status = 'publish'
    GROUP BY u.ID
    ORDER BY post_count DESC"
);
```
