create table AccountEarnedCMECredit (
	account integer not null references Account(id) on delete cascade,
	credit integer not null references CMECredit(id) on delete cascade,
	earned_date timestamp not null,

	primary key (account, credit)
);

create index AccountEarnedCMECredit_account_index
	on AccountEarnedCMECredit(account);
