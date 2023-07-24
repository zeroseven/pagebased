CREATE TABLE pages
(
	pagebased_top               tinyint(1) unsigned DEFAULT '0' NOT NULL,
	pagebased_date              int(11) DEFAULT '0' NOT NULL,
	pagebased_tags              text,
	pagebased_topics            int(11) unsigned DEFAULT '0' NOT NULL,
	pagebased_contact           int(11) unsigned DEFAULT '0' NOT NULL,
	pagebased_relations_to      int(11) unsigned DEFAULT '0' NOT NULL,
	pagebased_relations_from    int(11) unsigned DEFAULT '0' NOT NULL,
	pagebased_redirect_category tinyint(1) unsigned DEFAULT '0' NOT NULL,
	_pagebased_site             int(11) DEFAULT '0' NOT NULL,
	_pagebased_registration     varchar(255) DEFAULT '' NOT NULL,
	_pagebased_child_object     tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_pagebased_domain_model_topic
(
	uid   int(11) NOT NULL auto_increment,
	title varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY   parent (pid),
	KEY language (l10n_parent,sys_language_uid)
);

CREATE TABLE tx_pagebased_domain_model_contact
(
	uid         int(11) NOT NULL auto_increment,
	firstname   varchar(255) DEFAULT '' NOT NULL,
	lastname    varchar(255) DEFAULT '' NOT NULL,
	company     varchar(255) DEFAULT '' NOT NULL,
	expertise   varchar(255) DEFAULT '' NOT NULL,
	email       varchar(255) DEFAULT '' NOT NULL,
	phone       varchar(255) DEFAULT '' NOT NULL,
	website     varchar(255) DEFAULT '' NOT NULL,
	address     text                    NOT NULL,
	city        varchar(255) DEFAULT '' NOT NULL,
	zip         varchar(255) DEFAULT '' NOT NULL,
	country     varchar(255) DEFAULT '' NOT NULL,
	description text                    NOT NULL,
	image       int(11) unsigned NOT NULL default '0',
	page        varchar(255) DEFAULT '' NOT NULL,
	twitter     varchar(255) DEFAULT '' NOT NULL,
	facebook    varchar(255) DEFAULT '' NOT NULL,
	linkedin    varchar(255) DEFAULT '' NOT NULL,
	xing        varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY         parent (pid),
	KEY language (l10n_parent,sys_language_uid)
);

CREATE TABLE tx_pagebased_object_topic_mm
(
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local, uid_foreign),
	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);

CREATE TABLE tx_pagebased_relation_mm
(
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local, uid_foreign),
	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);
