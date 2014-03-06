create table AccountCMECreditBinding (
	account integer not null references Account(id) on delete cascade,
	credit integer not null references CMECredit(id) on delete cascade,

	primary key (account, credit)
);

create index AccountCMECreditBinding_account_index
	on AccountCMECreditBinding(account);
