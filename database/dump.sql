-- Smart Drink sample data for MySQL 8
--
-- Prerequisite: run Laravel migrations before importing this file.
-- Import into the database selected by backend/.env (normally `drink_app`):
--   docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < database/dump.sql
--
-- This is a data-only, repeatable fixture. It does not create, drop, or truncate tables.
-- Re-importing upserts only the named test accounts and their preference; it adds the other demo records only if absent.
--
-- Test accounts (the backslash in the task request is treated as an escape character):
--   admin@smartdrink.com  / admin@3618  (admin)
--   customer@smartdrink.com   / user@3618   (customer)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

-- The bcrypt hashes use cost 12, matching the default BCRYPT_ROUNDS in backend/.env.example.
INSERT INTO `users` (
    `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`
) VALUES (
    'Quản trị viên Một',
    'admin@smartdrink.com',
    '2026-08-20 08:00:00',
    '$2y$12$cB4nGcdqNOa44JmAQCmANeoStWlGYVTRzUEbrHP0P8bQgYYk353zy',
    'admin',
    NULL,
    '2026-08-20 08:00:00',
    '2026-08-20 08:00:00'
) ON DUPLICATE KEY UPDATE
    `id` = LAST_INSERT_ID(`id`),
    `name` = VALUES(`name`),
    `email_verified_at` = VALUES(`email_verified_at`),
    `password` = VALUES(`password`),
    `role` = VALUES(`role`),
    `remember_token` = NULL,
    `updated_at` = VALUES(`updated_at`);
SET @admin_one_id = LAST_INSERT_ID();

INSERT INTO `users` (
    `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`
) VALUES (
    'Khách hàng Demo',
    'customer@smartdrink.com',
    '2026-08-20 08:10:00',
    '$2y$12$qucU7g453cI.2U27Ylb3c.lQRm0U/CqnOHS1Se2PvnIOOBZKaVRO2',
    'customer',
    NULL,
    '2026-08-20 08:10:00',
    '2026-08-20 08:10:00'
) ON DUPLICATE KEY UPDATE
    `id` = LAST_INSERT_ID(`id`),
    `name` = VALUES(`name`),
    `email_verified_at` = VALUES(`email_verified_at`),
    `password` = VALUES(`password`),
    `role` = VALUES(`role`),
    `remember_token` = NULL,
    `updated_at` = VALUES(`updated_at`);
SET @customer_id = LAST_INSERT_ID();

INSERT INTO `user_preferences` (
    `user_id`, `taste_tags`, `sugar_level_default`, `ice_level_default`, `allergy_notes`,
    `profile_text`, `profile_embedding`, `created_at`, `updated_at`
) VALUES (
    @customer_id,
    JSON_ARRAY('trái_cây', 'ít_ngọt', 'có_caffeine'),
    '50',
    'less_ice',
    'Không dùng đậu phộng.',
    'Sở thích: trái_cây, ít_ngọt, có_caffeine. Mặc định: 50% đường, ít đá. Dị ứng: Không dùng đậu phộng. Từng đặt: [Demo] Trà đào cam sả, [Demo] Cà phê sữa đá. Đánh giá cao: [Demo] Trà đào cam sả (5 sao).',
    NULL,
    '2026-08-20 08:15:00',
    '2026-08-30 08:45:00'
) ON DUPLICATE KEY UPDATE
    `taste_tags` = VALUES(`taste_tags`),
    `sugar_level_default` = VALUES(`sugar_level_default`),
    `ice_level_default` = VALUES(`ice_level_default`),
    `allergy_notes` = VALUES(`allergy_notes`),
    `profile_text` = VALUES(`profile_text`),
    `profile_embedding` = NULL,
    `updated_at` = VALUES(`updated_at`);

-- The [Demo] prefix keeps this fixture distinct from the normal DrinkSeeder menu.
INSERT INTO `drinks` (
    `name`, `description`, `ingredients`, `category`, `price`, `calories`, `temperature_type`,
    `tags`, `image_url`, `is_available`, `description_embedding`, `created_at`, `updated_at`, `deleted_at`
)
SELECT
    '[Demo] Trà đào cam sả',
    'Trà đen ủ lạnh với đào, cam tươi và sả; vị thanh mát cho ngày nóng.',
    'Trà đen, đào ngâm, cam tươi, sả, đường',
    'trà trái cây',
    45000.00,
    180,
    'cold',
    JSON_ARRAY('best_seller', 'trái_cây', 'giải_khát'),
    NULL,
    TRUE,
    NULL,
    '2026-08-15 09:00:00',
    '2026-08-15 09:00:00',
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `drinks` WHERE `name` = '[Demo] Trà đào cam sả' AND `deleted_at` IS NULL
);
SET @peach_tea_id = (
    SELECT `id` FROM `drinks`
    WHERE `name` = '[Demo] Trà đào cam sả' AND `deleted_at` IS NULL
    ORDER BY `id` ASC LIMIT 1
);

INSERT INTO `drinks` (
    `name`, `description`, `ingredients`, `category`, `price`, `calories`, `temperature_type`,
    `tags`, `image_url`, `is_available`, `description_embedding`, `created_at`, `updated_at`, `deleted_at`
)
SELECT
    '[Demo] Cà phê sữa đá',
    'Cà phê phin Việt Nam kết hợp sữa đặc và đá.',
    'Cà phê, sữa đặc, đá',
    'cà phê',
    35000.00,
    180,
    'cold',
    JSON_ARRAY('có_caffeine', 'truyền_thống'),
    NULL,
    TRUE,
    NULL,
    '2026-08-15 09:05:00',
    '2026-08-15 09:05:00',
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `drinks` WHERE `name` = '[Demo] Cà phê sữa đá' AND `deleted_at` IS NULL
);
SET @milk_coffee_id = (
    SELECT `id` FROM `drinks`
    WHERE `name` = '[Demo] Cà phê sữa đá' AND `deleted_at` IS NULL
    ORDER BY `id` ASC LIMIT 1
);

INSERT INTO `drinks` (
    `name`, `description`, `ingredients`, `category`, `price`, `calories`, `temperature_type`,
    `tags`, `image_url`, `is_available`, `description_embedding`, `created_at`, `updated_at`, `deleted_at`
)
SELECT
    '[Demo] Trà sữa ô long',
    'Trà ô long thơm kết hợp sữa tươi, dùng nóng hoặc lạnh.',
    'Trà ô long, sữa tươi, đường',
    'trà sữa',
    42000.00,
    250,
    'both',
    JSON_ARRAY('thơm', 'ít_ngọt'),
    NULL,
    TRUE,
    NULL,
    '2026-08-15 09:10:00',
    '2026-08-15 09:10:00',
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `drinks` WHERE `name` = '[Demo] Trà sữa ô long' AND `deleted_at` IS NULL
);
SET @oolong_milk_tea_id = (
    SELECT `id` FROM `drinks`
    WHERE `name` = '[Demo] Trà sữa ô long' AND `deleted_at` IS NULL
    ORDER BY `id` ASC LIMIT 1
);

-- A completed order provides data for order history, ratings, and best-seller reports.
INSERT INTO `orders` (`user_id`, `status`, `total_price`, `context_snapshot`, `created_at`, `updated_at`)
SELECT
    @customer_id,
    'done',
    80000.00,
    JSON_OBJECT(
        'hour', 8,
        'weather', 'sunny',
        'temperature', 31.5,
        'order_type', 'takeaway',
        'occasion', 'Trước giờ làm',
        'lat', 10.7769,
        'lon', 106.7009
    ),
    '2026-08-30 08:30:00',
    '2026-08-30 08:45:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `orders`
    WHERE `user_id` = @customer_id AND `created_at` = '2026-08-30 08:30:00'
);
SET @done_order_id = (
    SELECT `id` FROM `orders`
    WHERE `user_id` = @customer_id AND `created_at` = '2026-08-30 08:30:00'
    ORDER BY `id` ASC LIMIT 1
);

INSERT INTO `order_items` (
    `order_id`, `drink_id`, `quantity`, `sugar_level`, `ice_level`, `note`,
    `unit_price`, `subtotal`, `created_at`, `updated_at`
)
SELECT @done_order_id, @peach_tea_id, 1, '50', 'less_ice', 'Ít ngọt', 45000.00, 45000.00,
       '2026-08-30 08:30:00', '2026-08-30 08:30:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `order_items` WHERE `order_id` = @done_order_id AND `drink_id` = @peach_tea_id
);

INSERT INTO `order_items` (
    `order_id`, `drink_id`, `quantity`, `sugar_level`, `ice_level`, `note`,
    `unit_price`, `subtotal`, `created_at`, `updated_at`
)
SELECT @done_order_id, @milk_coffee_id, 1, '30', 'normal_ice', NULL, 35000.00, 35000.00,
       '2026-08-30 08:30:00', '2026-08-30 08:30:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `order_items` WHERE `order_id` = @done_order_id AND `drink_id` = @milk_coffee_id
);

-- A pending order is useful for testing the customer/admin cancellation flow.
INSERT INTO `orders` (`user_id`, `status`, `total_price`, `context_snapshot`, `created_at`, `updated_at`)
SELECT
    @customer_id,
    'pending',
    42000.00,
    JSON_OBJECT(
        'hour', 14,
        'weather', NULL,
        'temperature', NULL,
        'order_type', 'dine_in',
        'occasion', 'Học bài',
        'lat', NULL,
        'lon', NULL
    ),
    '2026-09-02 14:15:00',
    '2026-09-02 14:15:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `orders`
    WHERE `user_id` = @customer_id AND `created_at` = '2026-09-02 14:15:00'
);
SET @pending_order_id = (
    SELECT `id` FROM `orders`
    WHERE `user_id` = @customer_id AND `created_at` = '2026-09-02 14:15:00'
    ORDER BY `id` ASC LIMIT 1
);

INSERT INTO `order_items` (
    `order_id`, `drink_id`, `quantity`, `sugar_level`, `ice_level`, `note`,
    `unit_price`, `subtotal`, `created_at`, `updated_at`
)
SELECT @pending_order_id, @oolong_milk_tea_id, 1, '50', 'normal_ice', NULL, 42000.00, 42000.00,
       '2026-09-02 14:15:00', '2026-09-02 14:15:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `order_items` WHERE `order_id` = @pending_order_id AND `drink_id` = @oolong_milk_tea_id
);

INSERT INTO `ratings` (
    `user_id`, `drink_id`, `order_id`, `rating`, `comment`, `created_at`, `updated_at`
) VALUES (
    @customer_id,
    @peach_tea_id,
    @done_order_id,
    5,
    'Vị đào cam sả thanh mát, sẽ đặt lại.',
    '2026-08-30 09:00:00',
    '2026-08-30 09:00:00'
) ON DUPLICATE KEY UPDATE
    `rating` = VALUES(`rating`),
    `comment` = VALUES(`comment`),
    `updated_at` = VALUES(`updated_at`);

-- Recommendation remains sample data only until UC-04 is implemented.
INSERT INTO `recommendation_logs` (
    `user_id`, `context_snapshot`, `candidate_drink_ids`, `final_ranked_ids`, `llm_explanation`, `created_at`
)
SELECT
    @customer_id,
    JSON_OBJECT('hour', 8, 'weather', 'sunny', 'temperature', 31.5),
    JSON_ARRAY(@peach_tea_id, @milk_coffee_id, @oolong_milk_tea_id),
    JSON_ARRAY(@peach_tea_id, @oolong_milk_tea_id, @milk_coffee_id),
    JSON_OBJECT(
        CAST(@peach_tea_id AS CHAR), 'Phù hợp sở thích trái cây, ít ngọt và thời tiết nóng.',
        CAST(@oolong_milk_tea_id AS CHAR), 'Phù hợp khi cần đồ uống có thể dùng nóng hoặc lạnh.',
        CAST(@milk_coffee_id AS CHAR), 'Phù hợp sở thích có caffeine vào buổi sáng.'
    ),
    '2026-08-30 08:10:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `recommendation_logs`
    WHERE `user_id` = @customer_id AND `created_at` = '2026-08-30 08:10:00'
);

COMMIT;
