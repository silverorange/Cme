create table AccountAttestedCMEFrontMatter (
	account integer not null references Account(id) on delete cascade,
	front_matter integer not null references CMEFrontMatter(id) on delete cascade,
	attested_date timestamp not null,

	primary key (account, front_matter)
);

create index AccountAttestedCMEFrontMatter_account_index
	on AccountAttestedCMEFrontMatter(account);
