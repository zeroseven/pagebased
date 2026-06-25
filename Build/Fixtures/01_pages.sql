-- pagebased Seed Fixtures: Example site structure
-- Marker: description = 'pagebased_seed' (used by `ddev seed --clean` to identify and remove seed rows)
--
-- pagebased classification (see CONTEXT.md):
--   * A Category is a page whose doktype matches a registered category documentType.
--     The dev-only "pagebased_demo" extension (Build/dev-extensions/) registers documentType 137.
--   * An Object is any non-system page below a Category in the rootline; `pagebased:detection`
--     then writes the registration identifier into the _pagebased_registration column.
--
-- Structure:
--   uid=1   Site Root       (doktype 1, created by `typo3 setup --create-site`; INSERT IGNORE keeps it)
--   uid=10  News Category   (doktype 137, parent=1)
--   uid=11  News Object 1   (parent=10, visible)
--   uid=12  News Object 2   (parent=10, visible)
--   uid=13  News Object 3   (parent=10, hidden)
--   uid=20  Events Category (doktype 137, parent=1)
--   uid=21  Event Object 1  (parent=20, visible)

INSERT IGNORE INTO pages (uid, pid, title, doktype, slug, description, hidden, deleted, tstamp, crdate)
VALUES
		(1,  0,  'pagebased Dev Site',                 1,   '/',                                    'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
		(10, 1,  'News',                               137, '/news',                                'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
		(11, 10, 'Breaking News: Pagebased Works!',    1,   '/news/breaking-news-pagebased-works',  'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
		(12, 10, 'Second News Article for Testing',    1,   '/news/second-news-article',            'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
		(13, 10, 'Hidden News Article',                1,   '/news/hidden-article',                 'pagebased_seed', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
		(20, 1,  'Events',                             137, '/events',                              'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
		(21, 20, 'Annual Dev Conference 2025',         1,   '/events/annual-dev-conference-2025',   'pagebased_seed', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
