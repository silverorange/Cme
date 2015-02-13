create table CMEFrontMatter (
	id serial,

	evaluation integer references Inquisition(id) on delete set null,

	enabled boolean not null default true,
	objectives text,
	planning_committee_no_disclosures text,
	planning_committee_with_disclosures text,
	support_staff_no_disclosures text,
	support_staff_with_disclosures text,
	review_date timestamp,

	passing_grade decimal(5, 2),
	email_content_pass text,
	email_content_fail text,
	resettable boolean not null default true,

	primary key(id)
);
