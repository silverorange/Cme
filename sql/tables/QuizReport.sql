create table QuizReport (
	id serial,

	provider integer not null references CMEProvider(id),

	filename   varchar(255) not null,
	quarter    timestamp not null,
	createdate timestamp not null,

	primary key (id)
);
