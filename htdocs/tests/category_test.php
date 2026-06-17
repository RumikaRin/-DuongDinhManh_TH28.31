<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app.php';

test_assert_same('sach-ky-nang-song', app_slugify('Sách kỹ năng sống'), 'Vietnamese category names have stable slugs');
test_assert_same('truyen-tranh', app_slugify('  Truyện   tranh  '), 'Slug generation collapses whitespace');
test_assert_same('danh-muc-moi', app_slugify('Danh mục mới!'), 'Slug generation removes punctuation');

$headerSource = file_get_contents(dirname(__DIR__) . '/header.php');
test_assert_same(false, str_contains($headerSource, 'CREATE TABLE'), 'Header rendering never creates database tables');
test_assert_same(false, str_contains($headerSource, 'INSERT IGNORE INTO `danhmuc`'), 'Header rendering never seeds category data');
test_assert_true(str_contains($headerSource, 'app_slugify'), 'Header uses the shared category slug helper');
test_assert_true(str_contains($headerSource, 'category-empty-state'), 'Empty categories render a useful state');
