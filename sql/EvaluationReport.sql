create table EvaluationReport (
	id serial,

	credit_type integer not null references CMECreditType(id),

	filename   varchar(255) not null,
	quarter    timestamp not null,
	createdate timestamp not null,

	primary key (id)
);
