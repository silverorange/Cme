create table CMECredit (
	id serial,

	enabled boolean not null default true,
	hours numeric(5, 2) not null,
	objectives text,
	planning_committee_no_disclosures text,
	support_staff_no_disclosures text,
	review_date timestamp,

	quiz integer references Inquisition(id) on delete set null,
	evaluation integer references Inquisition(id) on delete set null,
	credit_type integer not null references CMECreditType(id) on delete cascade,

	primary key (id)
);

create index CMECredit_type_index on CMECredit(credit_type);
