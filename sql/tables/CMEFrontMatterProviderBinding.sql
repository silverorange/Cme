create table CMEFrontMatterProviderBinding (
	front_matter integer not null references CMEFrontMatter(id) on delete cascade,
	provider integer not null references CMEProvider(id) on delete cascade,

	primary key(front_matter, provider)
);
