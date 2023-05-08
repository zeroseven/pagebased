CREATE TABLE pages
(
	_rampage_top               tinyint(1) unsigned DEFAULT '0' NOT NULL,
	_rampage_date              int(11) DEFAULT '0' NOT NULL,
	_rampage_tags              text,
	_rampage_topics            int(11) unsigned DEFAULT '0' NOT NULL,
	_rampage_relations_to      int(11) unsigned DEFAULT '0' NOT NULL,
	_rampage_relations_from    int(11) unsigned DEFAULT '0' NOT NULL,
	_rampage_redirect_category tinyint(1) unsigned DEFAULT '0' NOT NULL,
	_rampage_site              int(11) DEFAULT '0' NOT NULL,
	_rampage_registration      varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tx_rampage_domain_model_topic
(
	uid   int(11) NOT NULL auto_increment,
	title varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY   parent (pid),
	KEY language (l10n_parent,sys_language_uid)
);

CREATE TABLE tx_rampage_object_topic_mm
(
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local, uid_foreign),
	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);

CREATE TABLE tx_rampage_relation_mm
(
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,
);
