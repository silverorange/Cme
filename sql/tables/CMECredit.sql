create table CMECredit (
	id serial,

	front_matter integer not null references CMEFrontMatter(id) on delete cascade,
	quiz integer not null references Inquisition(id) on delete cascade,

	hours numeric(5, 2) not null,
	displayorder integer not null default 0,
	is_free boolean not null default false,
	expiry_date timestamp not null,

	primary key (id)
);

create index CMECredit_front_matter_index on CMECredit(front_matter);
