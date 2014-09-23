create table AccountEarnedCMECreditBinding (
	earned_credit integer not null references AccountEarnedCMECredit(id) on delete cascade,
	credit integer not null references CMECredit(id) on delete cascade,

	primary key (earned_credit, credit)
);
