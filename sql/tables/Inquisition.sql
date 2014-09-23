alter table Inquisition add account integer not null
	references Account(id) on delete cascade;
