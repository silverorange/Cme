alter table InquisitionResponse add account integer not null
	references Account(id) on delete cascade;

alter table InquisitionResponse add reset_date timestamp;
