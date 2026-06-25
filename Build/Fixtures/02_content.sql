-- pagebased Seed Fixtures: frontend rendering + list-plugin content elements
--
-- Frontend rendering differs by TYPO3 version:
--   * v14: `typo3 setup --create-site` wires fluid_styled_content as a site set, which already
--     renders the page content. The UPDATE below then affects no rows.
--   * v13: `typo3 setup --create-site` instead creates a "Main TypoScript Rendering" sys_template
--     that includes fluid_styled_content but only renders a welcome placeholder (page.10 = TEXT).
--     The UPDATE switches it to render the page content so the News list plugin shows up.
--
-- The tt_content rows place the auto-registered "News list" plugin (CType pagebaseddemo_list) on
-- the category pages, each selecting its own category via the flexform field "settings.category".

UPDATE sys_template SET config = 'page = PAGE
page.10 = CONTENT
page.10 {
    table = tt_content
    select {
        orderBy = sorting
        where = colPos=0
    }
}
' WHERE pid = 1 AND root = 1;

INSERT IGNORE INTO tt_content (uid, pid, CType, header, colPos, sorting, pi_flexform, deleted, hidden, tstamp, crdate)
VALUES
    (100, 10, 'pagebaseddemo_list', 'Latest News',   0, 256,
'<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="filter">
            <language index="lDEF">
                <field index="settings.category">
                    <value index="vDEF">10</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
        0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (101, 20, 'pagebaseddemo_list', 'Latest Events', 0, 256,
'<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="filter">
            <language index="lDEF">
                <field index="settings.category">
                    <value index="vDEF">20</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
        0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
