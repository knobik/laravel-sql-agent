-- <query name>active_users_count</query name>
-- <query description>
-- Count the number of active users (logged in within 30 days)
-- </query description>
-- <query>
SELECT COUNT(*) as active_users
FROM users
WHERE deleted_at IS NULL
  AND last_login_at > NOW() - INTERVAL 30 DAY
-- </query>

-- <query name>top_authors_by_views</query name>
-- <query description>
-- Find the top 10 authors by total post views
-- </query description>
-- <query>
SELECT
    u.id,
    u.name,
    COUNT(p.id) as post_count,
    SUM(p.view_count) as total_views
FROM users u
JOIN posts p ON p.user_id = u.id
WHERE u.deleted_at IS NULL
  AND p.status = 'published'
GROUP BY u.id, u.name
ORDER BY total_views DESC
LIMIT 10
-- </query>

-- <query name>posts_per_day</query name>
-- <query description>
-- Get the number of posts published per day for the last 30 days
-- </query description>
-- <query>
SELECT
    DATE(published_at) as publish_date,
    COUNT(*) as posts_published
FROM posts
WHERE status = 'published'
  AND published_at >= NOW() - INTERVAL 30 DAY
GROUP BY DATE(published_at)
ORDER BY publish_date DESC
-- </query>

-- <query name>user_engagement_stats</query name>
-- <query description>
-- Get user engagement statistics including posts and comments count
-- </query description>
-- <query>
SELECT
    u.id,
    u.name,
    u.email,
    u.last_login_at,
    COUNT(DISTINCT p.id) as posts_count,
    COUNT(DISTINCT c.id) as comments_count
FROM users u
LEFT JOIN posts p ON p.user_id = u.id AND p.status = 'published'
LEFT JOIN comments c ON c.user_id = u.id
WHERE u.deleted_at IS NULL
GROUP BY u.id, u.name, u.email, u.last_login_at
ORDER BY posts_count DESC, comments_count DESC
-- </query>

-- <query name>draft_posts_by_user</query name>
-- <query description>
-- Find all draft posts for a specific user
-- </query description>
-- <query>
SELECT
    p.id,
    p.title,
    p.created_at,
    p.updated_at
FROM posts p
JOIN users u ON u.id = p.user_id
WHERE p.status = 'draft'
  AND u.deleted_at IS NULL
ORDER BY p.updated_at DESC
-- </query>

-- <query name>monthly_signups</query name>
-- <query description>
-- Get the number of new user signups per month for the past year
-- </query description>
-- <query>
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as signups
FROM users
WHERE created_at >= NOW() - INTERVAL 12 MONTH
  AND deleted_at IS NULL
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month DESC
-- </query>
