drop table CMECreditQuestionBinding;

create table CMECreditQuestionBinding (
	id serial,
	credit integer not null references CMECredit(id) on delete cascade,
	question integer not null references InquisitionQuestion(id) on delete cascade,
	displayorder integer not null default 0,

	primary key(id)
);

create index CMECreditQuestionBinding_credit_index on
	CMECreditQuestionBinding(credit);

create index CMECreditQuestionBinding_question_index on
	CMECreditQuestionBinding(question);
