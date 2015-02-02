create table AccountEarnedCMECredit (
	id serial,
	account integer not null references Account(id) on delete cascade,
	earned_date timestamp not null,
	primary key (id)
);

create index AccountEarnedCMECredit_account_index
	on AccountEarnedCMECredit(account);
