-- Page Deletion Guard E2E Test Fixtures

-- Admin user (uid=1)
-- Password: docker
INSERT INTO `be_users` (`uid`, `pid`, `username`, `password`, `admin`, `usergroup`, `disable`, `deleted`, `tstamp`, `crdate`, `realName`, `email`, `lang`)
VALUES (
    1,
    0,
    'admin',
    '$argon2i$v=19$m=65536,t=16,p=1$M2s3SFlCQkZZNXhjOVFNUg$+JVwo7NkqRqxQz7RhyvyE13SLth4mFaBrQdLNIGDQDo',
    1,
    '',
    0,
    0,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    'Administrator',
    'admin@example.com',
    'default'
)
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `lang` = VALUES(`lang`);

-- Backend user group for bypass permission (uid=1)
INSERT INTO `be_groups` (`uid`, `pid`, `title`, `description`, `hidden`, `deleted`, `tstamp`, `crdate`, `tables_modify`, `pagetypes_select`, `non_exclude_fields`, `groupMods`)
VALUES (
    1,
    0,
    'Bypass Deletion Guard',
    'Users in this group can delete pages with children',
    0,
    0,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    'pages,tt_content',
    '1,3,4',
    'pages:title,pages:slug,pages:hidden',
    'web_layout,web_list,web_info,web_ViewpageView'
)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `groupMods` = VALUES(`groupMods`);

-- Backend user group for restricted editors (uid=2)
INSERT INTO `be_groups` (`uid`, `pid`, `title`, `description`, `hidden`, `deleted`, `tstamp`, `crdate`, `tables_modify`, `pagetypes_select`, `non_exclude_fields`, `groupMods`)
VALUES (
    2,
    0,
    'Editors',
    'Regular editors without bypass permission',
    0,
    0,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    'pages,tt_content',
    '1,3,4',
    'pages:title,pages:slug,pages:hidden',
    'web_layout,web_list,web_info,web_ViewpageView'
)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `groupMods` = VALUES(`groupMods`);

-- Editor with bypass group (uid=2)
-- Password: docker
INSERT INTO `be_users` (`uid`, `pid`, `username`, `password`, `admin`, `usergroup`, `disable`, `deleted`, `tstamp`, `crdate`, `realName`, `email`, `db_mountpoints`, `file_mountpoints`, `options`, `lang`)
VALUES (
    2,
    0,
    'editor_bypass',
    '$argon2i$v=19$m=65536,t=16,p=1$M2s3SFlCQkZZNXhjOVFNUg$+JVwo7NkqRqxQz7RhyvyE13SLth4mFaBrQdLNIGDQDo',
    0,
    '1',
    0,
    0,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    'Editor Bypass',
    'editor_bypass@example.com',
    '1',
    '',
    3,
    'default'
)
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`), `password` = VALUES(`password`), `usergroup` = VALUES(`usergroup`), `lang` = VALUES(`lang`);

-- Editor without bypass group (uid=3)
-- Password: docker
INSERT INTO `be_users` (`uid`, `pid`, `username`, `password`, `admin`, `usergroup`, `disable`, `deleted`, `tstamp`, `crdate`, `realName`, `email`, `db_mountpoints`, `file_mountpoints`, `options`, `lang`)
VALUES (
    3,
    0,
    'editor_restricted',
    '$argon2i$v=19$m=65536,t=16,p=1$M2s3SFlCQkZZNXhjOVFNUg$+JVwo7NkqRqxQz7RhyvyE13SLth4mFaBrQdLNIGDQDo',
    0,
    '2',
    0,
    0,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    'Editor Restricted',
    'editor_restricted@example.com',
    '1',
    '',
    3,
    'default'
)
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`), `password` = VALUES(`password`), `usergroup` = VALUES(`usergroup`), `lang` = VALUES(`lang`);

-- Root page (uid=1) - site root
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (1, 0, 'Root', '/', 1, 1, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `is_siteroot` = 1, `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Page without children (uid=2) - for standard deletion tests
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (2, 1, 'Page Without Children', '/page-without-children', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Parent page with children (uid=3) - for guard tests
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (3, 1, 'Parent Page', '/parent-page', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Child page 1 (uid=4) - under parent page
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (4, 3, 'Child Page 1', '/parent-page/child-page-1', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Child page 2 (uid=5) - under parent page
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (5, 3, 'Child Page 2', '/parent-page/child-page-2', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Page with single child (uid=6) - for singular "subpage" text test
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (6, 1, 'Single Child Parent', '/single-child-parent', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Single child page (uid=7) - under single child parent
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (7, 6, 'Only Child', '/single-child-parent/only-child', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Page for actual deletion test (uid=8) - will be deleted and verified gone
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (8, 1, 'Delete Me Parent', '/delete-me-parent', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `deleted` = 0, `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;

-- Child of deletion test page (uid=9)
INSERT INTO `pages` (`uid`, `pid`, `title`, `slug`, `doktype`, `is_siteroot`, `hidden`, `deleted`, `tstamp`, `crdate`, `perms_userid`, `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`)
VALUES (9, 8, 'Delete Me Child', '/delete-me-parent/delete-me-child', 1, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 31, 31, 31)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `slug` = VALUES(`slug`), `deleted` = 0, `perms_user` = 31, `perms_group` = 31, `perms_everybody` = 31;
