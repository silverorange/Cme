create table CMECreditQuestionBinding (
	credit integer not null references CMECredit(id) on delete cascade,
	question integer not null references InquisitionQuestion(id) on delete cascade,
	displayorder integer not null default 0,

	primary key(credit, question)
);
