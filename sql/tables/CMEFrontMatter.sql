create table CMEFrontMatter (
	id serial,

	evaluation integer references Inquisition(id) on delete set null,

	enabled boolean not null default true,
	objectives text,
	planning_committee_no_disclosures text,
	support_staff_no_disclosures text,
	review_date timestamp,

	primary key(id)
);
