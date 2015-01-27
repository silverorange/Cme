alter table Inquisition add account integer
	references Account(id) on delete cascade;
