CREATE TABLE pages
(
	_rampage_top               tinyint(1) unsigned DEFAULT '0' NOT NULL,
	_rampage_tags              text,
	_rampage_relations_to      int(11) unsigned DEFAULT '0' NOT NULL,
	_rampage_relations_from    int(11) unsigned DEFAULT '0' NOT NULL,
	_rampage_redirect_category tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_rampage_relation_mm
(
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local, uid_foreign),
	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);
