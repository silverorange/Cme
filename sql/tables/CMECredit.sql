create table CMECredit (
	id serial,

	quiz integer references Inquisition(id) on delete set null,
	front_matter integer not null references CMEFrontMatter(id) on delete cascade,

	hours numeric(5, 2) not null,
	passing_grade decimal(5, 2),
	email_content_pass text,
	email_content_fail text,
	resettable boolean not null default true,

	primary key (id)
);

create index CMECredit_front_matter_index on CMECredit(front_matter);
