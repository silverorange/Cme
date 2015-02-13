create table AccountCMEProgress (
	id serial,

	account integer not null references Account(id) on delete cascade,
	quiz integer references Inquisition(id) on delete cascade,
	evaluation integer references Inquisition(id) on delete cascade,

	primary key (id)
);

create index AccountCMEProgress_account_index
	on AccountCMEProgress(account);
