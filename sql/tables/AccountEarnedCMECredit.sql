create table AccountEarnedCMECredit (
	id serial,
	account integer not null references Account(id) on delete cascade,
	credit integer not null references CMECredit(id) on delete cascade,
	earned_date timestamp not null,
	primary key (id)
);

create index AccountEarnedCMECredit_account_index
	on AccountEarnedCMECredit(account);

create index AccountEarnedCMECredit_credit_index
	on AccountEarnedCMECredit(credit);

create index AccountEarnedCMECredit_earned_date_index
	on AccountEarnedCMECredit(earned_date);

create index AccountEarnedCMECredit_earned_date_los_angeles_index
	on AccountEarnedCMECredit(convertTZ(earned_date, 'America/Los_Angeles'));
