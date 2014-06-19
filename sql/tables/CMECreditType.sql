create table CMECreditType (
	id serial,

	shortname varchar(255) not null,
	title varchar(255) not null,

	primary key (id)
);

create index CMECreditType_shortname_index on CMECreditType(shortname);
