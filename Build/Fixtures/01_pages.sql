-- pagebased Seed Fixtures: Example site structure
-- Marker: description = 'pagebased_seed' (used by `ddev seed --clean` to identify and remove seed rows)
--
-- Structure:
--   uid=1   Site Root
--   uid=10  News Category  (parent=1)
--   uid=11  News Object 1  (parent=10, visible)
--   uid=12  News Object 2  (parent=10, visible)
--   uid=13  News Object 3  (parent=10, hidden)
--   uid=20  Events Category (parent=1)
--   uid=21  Event Object 1 (parent=20, visible)

INSERT IGNORE INTO pages (uid, pid, title, doktype, slug, description, hidden, deleted, tstamp, crdate,
    _pagebased_registration, _pagebased_site, _pagebased_categories)
VALUES
    (1,  0,  'pagebased Dev Site',                      1, '/',                                    'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  ''),
    (10, 1,  'News',                                     1, '/news',                                'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  '10'),
    (11, 10, 'Breaking News: Pagebased Works!',          1, '/news/breaking-news-pagebased-works',  'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  '10'),
    (12, 10, 'Second News Article for Testing',          1, '/news/second-news-article',            'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  '10'),
    (13, 10, 'Hidden News Article',                      1, '/news/hidden-article',                 'pagebased_seed', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  '10'),
    (20, 1,  'Events',                                   1, '/events',                              'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  '20'),
    (21, 20, 'Annual Dev Conference 2025',               1, '/events/annual-dev-conference-2025',   'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '',  '20');
